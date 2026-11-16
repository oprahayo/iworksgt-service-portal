import { chromium } from 'playwright';
import { mkdir, writeFile } from 'node:fs/promises';
const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
await mkdir('docs/visual-qa',{recursive:true});
try{
  // Real PHP invoice markup with synthetic values matching one paid order and two attempts.
  const desktop=await browser.newContext({viewport:{width:1100,height:1000},deviceScaleFactor:1});
  const invoice=await desktop.newPage();
  const response=await invoice.goto('http://127.0.0.1:8011/tests/invoice_mobile_visual_preview.php',{waitUntil:'load'});
  if(!response?.ok())throw Error('Invoice fixture HTTP '+response?.status());
  await invoice.evaluate(()=>document.fonts.ready);
  const bill=await invoice.evaluate(()=>{
    const logo=document.querySelector('.brand img');
    const records=[...document.querySelectorAll('.iw-invoice-payment-record')];
    return {
      title:document.querySelector('.iw-invoice-hero h1')?.textContent?.trim(),
      logoLoaded:!!logo&&logo.complete&&logo.naturalWidth>0,
      logos:[...document.querySelectorAll('.brand img')].map(i=>i.getAttribute('src')),
      total:document.querySelector('.iw-invoice-hero-amount strong')?.textContent?.trim(),
      records:records.length,
      badTable:!!document.querySelector('.iw-invoice-payment-history table'),
      hiddenCheckout:getComputedStyle(document.querySelector('.iw-invoice-checkout')).display==='none',
      width:window.innerWidth,scroll:document.documentElement.scrollWidth,
    };
  });
  if(bill.title!=='Payment receipt'||!bill.logoLoaded||bill.records!==2||bill.badTable||!bill.hiddenCheckout||bill.scroll>bill.width+2)
    throw Error('Invoice/receipt visual check failed: '+JSON.stringify(bill));
  await invoice.screenshot({path:'docs/visual-qa/invoice-desktop.png',fullPage:true,animations:'disabled'});
  const pdf=await invoice.pdf({format:'A4',printBackground:true,preferCSSPageSize:true});
  await writeFile('docs/visual-qa/invoice-one-job.pdf',pdf);
  const raw=pdf.toString('latin1');
  const pages=(raw.match(/\/Type\s*\/Page\b/g)||[]).length;
  console.log(JSON.stringify({mode:'invoice',...bill,pdfPages:pages}));
  if(pages!==1)throw Error('Single-job receipt must fit exactly one A4 page; got '+pages);
  await desktop.close();

  for(const width of [390,320]){
    const mobile=await browser.newContext({viewport:{width,height:844},deviceScaleFactor:2});
    const page=await mobile.newPage();
    const r=await page.goto('http://127.0.0.1:8011/tests/invoice_mobile_visual_preview.php?mode=payments',{waitUntil:'load'});
    if(!r?.ok())throw Error('Payment fixture HTTP '+r?.status());
    const metrics=await page.evaluate(()=>{
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
        note:!!form?.querySelector('input[name=review_note]'),
        reference:card?.querySelector('.iw-payment-reference')?.textContent?.trim(),
      };
    });
    console.log(JSON.stringify({mode:'mobile-review',width,...metrics}));
    if(!metrics.card||!metrics.form||!metrics.button||
       metrics.scroll>metrics.viewport+2||metrics.card.height>570||
       metrics.button.right>width+2||!metrics.select||!metrics.csrf||!metrics.note)
      throw Error('Mobile admin review layout failed at '+width+'px');
    await page.screenshot({path:'docs/visual-qa/payments-'+width+'.png',fullPage:true,animations:'disabled'});
    await mobile.close();
  }
}finally{await browser.close();}
