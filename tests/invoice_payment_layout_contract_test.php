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
$styles=$get('assets/css/invoice-clean.css');
$checkout=$get('assets/css/client-checkout.css');
$payments=$get('views/payments.php');
$paymentCss=$get('assets/css/payment-review-mobile.css');
$header=$get('views/partials/header.php');
$deployer=$get('scripts/deploy_invoice_payment_layout.sh');
$logo=$get('assets/branding/iworks-desktop.svg');

$assert(str_contains($invoice,"portal_absolute_url('assets/branding/iworks-desktop.svg')")
  && str_contains($invoice,'assets/css/invoice-clean.css')
  && str_contains($invoice,'class="iw-invoice')
  && str_contains($invoice,'alt="iWORKS GLOBAL"')
  && str_contains($logo,'viewBox="0 0 1338 123"'),
  'Use the verified current iWORKS header logo, including on printable receipts.');
$assert(str_contains($invoice,'Payment receipt')
  && str_contains($invoice,'Total paid')
  && str_contains($invoice,'Total payable')
  && str_contains($invoice,'Payment records')
  && str_contains($invoice,'iw-invoice-payment-record')
  && !str_contains($invoice,'<th>Reference</th><th>Amount</th><th>Status</th><th>Proof</th><th>Date</th>'),
  'Replace awkward wide payment-history print table with compact labelled records.');
$assert(str_contains($invoice,'iw-invoice-no-checkout')
  && str_contains($styles,'.iw-invoice-no-checkout .iw-invoice-checkout')
  && str_contains($styles,'display:none')
  && str_contains($styles,'@media print')
  && str_contains($styles,'@page{size:A4;margin:10mm}')
  && str_contains($styles,'break-inside:avoid')
  && str_contains($styles,'overflow-wrap:anywhere'),
  'Paid or staff invoices must fit A4 and prevent long references/dates breaking every character.');
$assert(str_contains($styles,'max-width:830px')
  && str_contains($styles,'.iw-invoice-payment-reference')
  && str_contains($styles,'font-variant-numeric:tabular-nums'),
  'One-job invoice and transaction dates must have restrained spacing and clear numerals.');
$assert(str_contains($invoice,"if ($"."user['role'] === 'client' && $"."invoice['status'] !== 'paid'):"),
  'Do not offer duplicate checkout on paid receipts.');
$assert(str_contains($invoice,'require_csrf()')
  && str_contains($invoice,'start_gateway_checkout(')
  && str_contains($invoice,'submit_invoice_payment(')
  && str_contains($invoice,'get_invoice('),
  'Checkout and authorisation must retain existing trusted payment functions.');

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
  && str_contains($deployer,'rollback()')
  && str_contains($deployer,'--check')
  && str_contains($deployer,'--apply'),
  'Guarded publisher must validate, backup and roll back source-only updates.');
echo "invoice_payment_layout_contract_test passed\n";
