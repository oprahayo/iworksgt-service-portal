import { chromium } from 'playwright';
import { mkdir } from 'node:fs/promises';

const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
await mkdir('docs/visual-qa',{recursive:true});
try {
  for (const v of [
    {name:'desktop',width:1440,height:950,scale:1},
    {name:'mobile',width:390,height:844,scale:2}
  ]) {
    const ctx=await browser.newContext({viewport:{width:v.width,height:v.height},deviceScaleFactor:v.scale});
    const page=await ctx.newPage();
    const failures=[];
    page.on('pageerror',e=>failures.push(e.message));
    const response=await page.goto('http://127.0.0.1:8011/tests/gansell_visual_preview.php',{waitUntil:'load',timeout:25000});
    if (!response?.ok()) throw Error('Preview HTTP '+response?.status());
    // Visual evidence must show actual storefront photographs, not unpainted
    // lazy elements or empty framed boxes in a full-page screenshot.
    await page.locator('.iw-gansell-example-wide img').scrollIntoViewIfNeeded();
    await page.locator('.iw-gansell-example-phone img').scrollIntoViewIfNeeded();
    await page.evaluate(async ()=>{
      await Promise.all([...document.querySelectorAll('.iw-solutions img')].map(img=>img.decode()));
      await new Promise(resolve=>requestAnimationFrame(()=>requestAnimationFrame(resolve)));
    });
    await page.evaluate(()=>window.scrollTo(0,0));
    const observed=await page.evaluate(()=>{
      const $=s=>document.querySelector(s);
      const photos=[...document.querySelectorAll('.iw-solutions img')];
      const rect=el=>{let r=el.getBoundingClientRect();return {x:r.x,y:r.y,width:r.width,height:r.height,bottom:r.bottom};};
      return {
        title:$('h1')?.textContent?.trim(),
        photoCount:photos.length,
        photosValid:photos.every(img=>img.complete&&img.naturalWidth>0&&img.src.startsWith('data:image/png;base64,')),
        screen:window.innerWidth,
        total:document.documentElement.scrollWidth,
        seal:$('.iw-gansell-free-seal')?.textContent?.trim(),
        gallery:rect($('.iw-gansell-product-gallery')),
        browser:rect($('.iw-gansell-browser-shot')),
        phone:rect($('.iw-gansell-phone-shot')),
        checks:document.querySelectorAll('.iw-gansell-functions li').length,
      storefrontPainted:[...document.querySelectorAll('.iw-gansell-example-gallery img')].every(img=>{
        let c=document.createElement('canvas');c.width=24;c.height=24;
        let ctx=c.getContext('2d',{willReadFrequently:true});
        ctx.drawImage(img,0,0,24,24);
        let d=ctx.getImageData(0,0,24,24).data,dark=0;
        for(let k=0;k<d.length;k+=4){
          if(d[k]<220||d[k+1]<220||d[k+2]<220)dark++;
        }
        return dark>15;
      })
      };
    });
    if (observed.title!=='Put your business online with Gansell.')failures.push('Wrong heading');
    if (observed.photoCount!==4 || !observed.photosValid)failures.push('Actual photographs failed to render');
    if (!observed.storefrontPainted)failures.push('Vendor screenshots visually blank after decode');
    if (observed.total>observed.screen+2)failures.push('Horizontal overflow');
    if (observed.seal!=='FREE')failures.push('Missing FREE sticker');
    if (observed.checks!==6)failures.push('Feature list must contain exactly six points');
    if (observed.browser.width<=observed.phone.width*1.8)failures.push('Desktop preview not dominant');
    if (observed.phone.bottom>observed.gallery.bottom+6)failures.push('Mobile screenshot clips out of the gallery');
    await page.screenshot({path:'docs/visual-qa/gansell-'+v.name+'.png',fullPage:true,animations:'disabled'});
    console.log(JSON.stringify({variant:v.name,...observed,failures}));
    await ctx.close();
    if (failures.length)throw Error(v.name+': '+failures.join('; '));
  }
} finally {
  await browser.close();
}
