import { chromium } from 'playwright';
import { mkdir, writeFile } from 'node:fs/promises';
const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
await mkdir('docs/visual-qa',{recursive:true});
try {
  const desktop=await browser.newContext({viewport:{width:1100,height:1050},deviceScaleFactor:1});
  const invoice=await desktop.newPage();
  const response=await invoice.goto('http://127.0.0.1:8011/tests/invoice_mobile_visual_preview.php',{waitUntil:'load'});
  if(!response?.ok())throw Error('Invoice fixture HTTP '+response?.status());
  await invoice.evaluate(()=>document.fonts.ready);
  const bill=await invoice.evaluate(()=>{
    const $=s=>document.querySelector(s);
    const bbox=el=>{const r=el.getBoundingClientRect();return{x:r.x,y:r.y,width:r.width,height:r.height,bottom:r.bottom};};
    const sheet=$('.iw-wave-sheet');
    const logo=$('.iw-wave-footer-logo img');
    return {
      bannerText:$('.iw-wave-banner-title')?.textContent?.trim(),
      billTo:$('.iw-wave-overline')?.textContent?.trim(),
      columns:[...document.querySelectorAll('.iw-wave-items thead th')].map(e=>e.textContent.trim()),
      invoiceRows:document.querySelectorAll('.iw-wave-items tbody tr').length,
      balance:$('.iw-wave-balance strong')?.textContent?.trim(),
      credits:document.querySelectorAll('.iw-wave-payment-line').length,
      transactionDetails:document.querySelectorAll('.iw-wave-transaction-row').length,
      logoLoaded:!!logo&&logo.complete&&logo.naturalWidth>0,
      sheet:sheet?bbox(sheet):null,
      band:bbox($('.iw-wave-banner')),
      footer:bbox($('.iw-wave-footer')),
      footerLogo:logo?.getAttribute('src'),
      hasCheckout:!!$('.iw-invoice-checkout'),
      hasWaveBranding:document.body.innerText.includes('Powered by Wave'),
      viewport:window.innerWidth,
      scroll:document.documentElement.scrollWidth,
    };
  });
  if(bill.bannerText!=='INVOICE'||bill.billTo!=='BILL TO'
    ||bill.columns.join('|')!=='ITEMS|QUANTITY|PRICE|AMOUNT'
    ||bill.invoiceRows!==1||bill.balance!=='₦0.00'
    ||bill.credits!==1||bill.transactionDetails!==2
    ||!bill.logoLoaded||bill.hasWaveBranding||bill.hasCheckout
    ||bill.scroll>bill.viewport+2
    ||!bill.sheet||bill.band.width<bill.sheet.width-3
    ||bill.footer.bottom>bill.sheet.bottom+2)
    throw Error('Wave reference invoice mismatch: '+JSON.stringify(bill));
  await invoice.screenshot({path:'docs/visual-qa/invoice-desktop.png',fullPage:true,animations:'disabled'});
  const pdf=await invoice.pdf({format:'A4',printBackground:true,preferCSSPageSize:true});
  await writeFile('docs/visual-qa/invoice-one-job.pdf',pdf);
  const pages=(pdf.toString('latin1').match(/\/Type\s*\/Page\b/g)||[]).length;
  console.log(JSON.stringify({mode:'invoice',pdfPages:pages,...bill}));
  if(pages!==1)throw Error('One paid job must print on one A4 page; got '+pages);
  await desktop.close();

  for(const width of [390,320]){
    const mobile=await browser.newContext({viewport:{width,height:844},deviceScaleFactor:2});
    const mobileInvoice=await mobile.newPage();
    await mobileInvoice.goto('http://127.0.0.1:8011/tests/invoice_mobile_visual_preview.php',{waitUntil:'load'});
    const invoiceMetrics=await mobileInvoice.evaluate(()=>({
      viewport:innerWidth,scroll:document.documentElement.scrollWidth,
      header:!!document.querySelector('.iw-wave-banner'),
      footer:!!document.querySelector('.iw-wave-footer'),
      balance:document.querySelector('.iw-wave-balance strong')?.textContent.trim()
    }));
    console.log(JSON.stringify({mode:'mobile-invoice',width,...invoiceMetrics}));
    if(invoiceMetrics.scroll>width+2||!invoiceMetrics.header||!invoiceMetrics.footer||invoiceMetrics.balance!=='₦0.00')
      throw Error('Mobile invoice overflow or missing key sections at '+width);
    await mobileInvoice.screenshot({path:'docs/visual-qa/invoice-'+width+'.png',fullPage:true,animations:'disabled'});

    const page=await mobile.newPage();
    const r=await page.goto('http://127.0.0.1:8011/tests/invoice_mobile_visual_preview.php?mode=payments',{waitUntil:'load'});
    if(!r?.ok())throw Error('Payment fixture HTTP '+r?.status());
    const review=await page.evaluate(()=>{
      const card=document.querySelector('.iw-payment-table tbody tr:not(.table-empty-row)');
      const form=document.querySelector('.iw-payment-review-form');
      const button=form?.querySelector('button');
      const rect=e=>{let r=e.getBoundingClientRect();return{x:r.x,y:r.y,width:r.width,height:r.height,right:r.right}};
      return {
        viewport:window.innerWidth,scroll:document.documentElement.scrollWidth,
        card:card?rect(card):null,
        form:form?rect(form):null,
        button:button?rect(button):null,
        select:!!form?.querySelector('select[name=status]'),
        csrf:!!form?.querySelector('input[name=csrf_token]'),
        note:!!form?.querySelector('input[name=review_note]')
      };
    });
    console.log(JSON.stringify({mode:'mobile-review',width,...review}));
    if(!review.card||!review.form||!review.button
      ||review.scroll>review.viewport+2||review.card.height>570
      ||review.button.right>width+2||!review.select||!review.csrf||!review.note)
      throw Error('Mobile admin review layout failed at '+width);
    await page.screenshot({path:'docs/visual-qa/payments-'+width+'.png',fullPage:true,animations:'disabled'});
    await mobile.close();
  }
} finally {await browser.close();}
