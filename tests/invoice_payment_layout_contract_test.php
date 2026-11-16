<?php
declare(strict_types=1);
/**
 * Invoice and staff-payment layout regression. Source-only: no customer DB access.
 * Layout may change; payment verification and CSRF workflows must not.
 */
$root=dirname(__DIR__);
$get=static fn(string $p):string=>(string)file_get_contents($root.'/'.$p);
$assert=static function(bool $yes,string $message):void{
  if(!$yes)throw new RuntimeException($message);
};
$invoice=$get('invoice.php');
$sheet=$get('views/partials/invoice-wave-sheet.php');
$styles=$get('assets/css/invoice-wave.css');
$checkout=$get('assets/css/client-checkout.css');
$payments=$get('views/payments.php');
$paymentCss=$get('assets/css/payment-review-mobile.css');
$header=$get('views/partials/header.php');
$deployer=$get('scripts/deploy_invoice_payment_layout.sh');
$logo=$get('assets/branding/iworks-desktop.svg');

$assert(str_contains($invoice,"portal_absolute_url('assets/branding/iworks-desktop.svg')")
  && str_contains($invoice,"require __DIR__ . '/views/partials/invoice-wave-sheet.php'")
  && str_contains($invoice,'assets/css/invoice-wave.css')
  && str_contains($invoice,'class="iw-invoice')
  && str_contains($sheet,'alt="iWORKS GLOBAL"')
  && str_contains($logo,'viewBox="0 0 1338 123"'),
  'Use current iWORKS logo in the Wave-reference bill, without a Wave-branded image.');
$assert(str_contains($sheet,'iw-wave-banner')
  && str_contains($sheet,'iw-wave-banner-title')
  && str_contains($sheet,'>INVOICE</div>')
  && str_contains($sheet,'iw-wave-banner-due')
  && str_contains($sheet,'BILL TO')
  && str_contains($sheet,'Invoice Number:')
  && str_contains($sheet,'Invoice Date:')
  && str_contains($sheet,'Payment Due:')
  && str_contains($sheet,'<th scope="col">ITEMS</th>')
  && str_contains($sheet,'<th scope="col">QUANTITY</th>')
  && str_contains($sheet,'<th scope="col">PRICE</th>')
  && str_contains($sheet,'<th scope="col">AMOUNT</th>')
  && str_contains($sheet,'iw-wave-totals')
  && str_contains($sheet,'iw-wave-balance')
  && str_contains($sheet,'iw-wave-footer'),
  'Match both provided Wave invoice references: orange band, two-column bill-to/meta, four-column items, totals and footer.');
$assert(str_contains($sheet,'$iwConfirmedSum')
  && str_contains($sheet,"=== 'paid'")
  && str_contains($sheet,"!== 'confirmed'")
  && str_contains($sheet,'max(0.0, $iwTotal - $iwConfirmedSum)')
  && str_contains($sheet,'iw-wave-payment-line')
  && str_contains($sheet,'iw-wave-transaction-records'),
  'Paid invoices show zero balance while unconfirmed attempts are not counted as payments; staff can still inspect all attempts.');
$assert(str_contains($styles,'.iw-wave-sheet')
  && str_contains($styles,'grid-template-columns:minmax(0,65%) minmax(0,35%)')
  && str_contains($styles,'background:#f56613')
  && str_contains($styles,'background:#cb4e1e')
  && str_contains($styles,'.iw-wave-items tbody{background:#f0f1f1}')
  && str_contains($styles,'@page{size:A4;margin:0}')
  && str_contains($styles,'.iw-wave-footer')
  && str_contains($styles,'.print-hide{display:none!important}')
  && str_contains($styles,'@media(max-width:370px)'),
  'Wave-style one-page A4 PDF and phone layout must be explicit; checkout cannot be printed.');
$assert(!str_contains($sheet,'Powered by Wave')
  && !str_contains($sheet,'waveapps.com')
  && !str_contains($sheet,'logo-wave')
  && str_contains($sheet,'www.iworksgt.com'),
  'Use the iWORKS-owned invoice, not provider branding.');
$assert(str_contains($invoice,'require_csrf()')
  && str_contains($invoice,'start_gateway_checkout(')
  && str_contains($invoice,'submit_invoice_payment(')
  && str_contains($invoice,'get_invoice(')
  && str_contains($invoice,'id="invoicePaymentForm"')
  && str_contains($invoice,'iw-wave-with-checkout'),
  'Existing secure payment flow must be unchanged and visible on unpaid invoices.');

$assert(str_contains($payments,'iw-payment-table')
  && str_contains($payments,'iw-payments-page')
  && str_contains($payments,'iw-payment-review-form')
  && str_contains($payments,'csrf_token()')
  && str_contains($payments,'name="form_action" value="review_payment"')
  && str_contains($payments,'name="payment_id"')
  && str_contains($payments,'name="status"')
  && str_contains($payments,'name="review_note"'),
  'Staff review must keep the actual CSRF-protected approval action.');
$assert(str_contains($paymentCss,'grid-template-areas:')
  && str_contains($paymentCss,'"invoice amount"')
  && str_contains($paymentCss,'"review review"')
  && str_contains($paymentCss,'grid-template-columns:minmax(0,.84fr) minmax(0,1.16fr)')
  && str_contains($paymentCss,'@media(max-width:360px)'),
  'Mobile layout must arrange invoice/amount first, then status and full-width review controls.');
$assert(str_contains($header,'assets/css/payment-review-mobile.css')
  && str_contains($header,"$"."currentPage === 'payments'")
  && str_contains($header,"['admin','finance']"),
  'Only authorised admin/finance payment pages should load the new CSS.');
$assert(str_contains($checkout,'@media print')
  && str_contains($deployer,'invoice_payment_layout_contract_test.php')
  && str_contains($deployer,'check_remediation_release.php')
  && str_contains($deployer,'cleanup_and_restore()')
  && str_contains($deployer,'--check')
  && str_contains($deployer,'--apply'),
  'Guarded publisher must validate, backup and roll back source-only updates.');
echo "invoice_payment_layout_contract_test passed\n";
