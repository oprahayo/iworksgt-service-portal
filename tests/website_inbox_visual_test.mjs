
import {chromium} from 'playwright';
import {resolve} from 'node:path';
import {pathToFileURL} from 'node:url';
const dir=resolve('docs/visual-qa/communications');
const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
try {
 for(const width of [320,390,768,1440]){
  const page=await browser.newPage({viewport:{width,height:900}});
  for(const screen of ['list','detail']){
   await page.goto(pathToFileURL(resolve(dir,screen+'.html')).href);
   if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+2))throw Error('Inbox overflow '+screen+' '+width);
   if(await page.locator('.iw-communications-nav a').count()!==4)throw Error('Staff tabs missing');
   if(screen==='list'&&!await page.locator('.iw-enquiry-row').first().isVisible())throw Error('Missing enquiry list');
   if(screen==='detail'&&!await page.locator('.iw-enquiry-message').isVisible())throw Error('Missing message');
   if(width<900&&screen==='detail'&&await page.locator('.iw-enquiry-list').isVisible())throw Error('Mobile reading pane must be focused');
   if(width===390||width===1440)await page.screenshot({path:resolve(dir,screen+'-'+width+'.png'),fullPage:true});
  }
  await page.close();
 }
}finally{await browser.close();}
console.log('PASS inbox responsive layouts at four viewport widths.');
