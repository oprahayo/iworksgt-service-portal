// Public, nonauthenticated website captures. No checkout, user data or orders.
import { chromium } from 'playwright';
import fs from 'node:fs/promises';

const out='assets/branding/solutions';
await fs.mkdir(out,{recursive:true});
const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
const pages=[
  {name:'gansell-home-desktop.png',url:'https://gansell.com/',width:1440,height:1000,mobile:false,match:'Gansell'},
  {name:'gansell-home-mobile.png',url:'https://gansell.com/',width:390,height:844,mobile:true,match:'Gansell'},
  {name:'ab-african-store-desktop.png',url:'https://gansell.com/@ab-african',width:1440,height:1000,mobile:false,match:'AFRICAN FOOD AND MALL'},
  {name:'ab-african-store-mobile.png',url:'https://gansell.com/@ab-african',width:390,height:844,mobile:true,match:'AFRICAN FOOD AND MALL'},
];
let ok=0;
try{
for(const item of pages){
 const ctx=await browser.newContext({
  viewport:{width:item.width,height:item.height},
  deviceScaleFactor:item.mobile?2:1,
  isMobile:item.mobile,
  hasTouch:item.mobile,
  locale:'en-NG',
  reducedMotion:'reduce',
 });
 const page=await ctx.newPage();
 console.log('Capture',item.url,item.width);
 const response=await page.goto(item.url,{waitUntil:'domcontentloaded',timeout:45000});
 if(!response || !response.ok()) throw new Error(item.name+' HTTP '+(response?.status()??'unknown'));
 await page.waitForLoadState('networkidle',{timeout:12000}).catch(()=>{});
 await page.waitForTimeout(1800);
 const title=await page.title();
 const text=(await page.locator('body').innerText()).toUpperCase();
 const normalize=(value)=>value.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toUpperCase();
 const needle=normalize(item.match);
 if(!normalize(text).includes(needle) && !normalize(title).includes(needle)) {
   throw new Error('Page not recognizable: '+item.url+' title='+title);
 }
 if(text.includes('VERIFY YOU ARE HUMAN')||text.includes('CLOUDFLARE CHALLENGE'))throw new Error('Challenge page cannot be used as screenshot');
 await page.screenshot({path:out+'/'+item.name,fullPage:false,animations:'disabled',type:'png'});
 console.log('Saved',out+'/'+item.name,'title:',title);
 ok++;
 await ctx.close();
}
}finally{await browser.close();}
if(ok!==pages.length)throw new Error('Incomplete captures');
