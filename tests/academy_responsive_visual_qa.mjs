import { chromium } from 'playwright';
import { readdir, mkdir } from 'node:fs/promises';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';
const dir=resolve('docs/visual-qa/academy');
const files=(await readdir(dir)).filter(f=>f.endsWith('.html'));
if(files.length!==12)throw Error('Expected all twelve real Academy view fixtures; got '+files.length);
const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
await mkdir(dir,{recursive:true});
try{
  for(const width of [320,390,768,1440]){
    const page=await browser.newPage({viewport:{width,height:900},deviceScaleFactor:1});
    for(const file of files){
      await page.goto(pathToFileURL(resolve(dir,file)).href,{waitUntil:'load'});
      if(file==='student-dashboard.html'){
        const dialog=page.locator('#ac-welcome-guide');
        await dialog.waitFor({state:'visible',timeout:10000});
        if(await dialog.locator('a[data-guide-link]').count()!==2)throw Error('Welcome paths missing');
        if(await dialog.locator('a[data-guide-link]').nth(0).getAttribute('href')!=='/academy.php?action=certificates'||await dialog.locator('a[data-guide-link]').nth(1).getAttribute('href')!=='/academy.php?action=courses')throw Error('Guide destinations incorrect');
        const d=await dialog.boundingBox();
        if(!d||d.x<0||d.y<0||d.x+d.width>width+1||d.y+d.height>900+1)throw Error('Welcome guide exceeds viewport');
        if(width===320)await page.keyboard.press('Escape');else await dialog.getByRole('button',{name:'Got it, continue'}).click();
        await dialog.waitFor({state:'hidden'});
        await page.reload({waitUntil:'load'});
        if(await dialog.isVisible())throw Error('Dismissed welcome guide reopened on reload');
        const help=page.locator('#ac-guide-dashboard');
        await help.locator('summary').click();
        await help.getByRole('button',{name:'Open welcome guide'}).click();
        await dialog.waitFor({state:'visible'});
        await dialog.getByRole('button',{name:'Close guide'}).click();
        await dialog.waitFor({state:'hidden'});
        await help.locator('summary').click();
      }else if(file.startsWith('student-')){
        const hint=page.locator('details.ac-guide');
        if(await hint.count()!==1)throw Error('Contextual guide absent: '+file);
        await hint.locator('summary').click();
        if(!await hint.locator('.ac-guide-body').isVisible())throw Error('Hint failed to open: '+file);
        const wide=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+2);
        if(wide)throw Error('Expanded hint overflows: '+file);
        await hint.locator('summary').click();
      }
      const metrics=await page.evaluate(()=>{
        const root=document.querySelector('.academy-workspace');
        const transparent=el=>getComputedStyle(el).backgroundColor==='rgba(0, 0, 0, 0)';
        const visible=el=>el.getClientRects().length>0&&getComputedStyle(el).visibility!=='hidden';
        const bounds=root.getBoundingClientRect();
        const panels=[...root.querySelectorAll('.ac-panel,.ac-course-card,.ac-student-stats a,.ac-student-welcome')];
        const controls=[...root.querySelectorAll('button,a,input:not([type=hidden]),select')].filter(visible);
        const bad=controls.filter(el=>{const r=el.getBoundingClientRect();return r.left<bounds.left-2||r.right>bounds.right+2;}).map(el=>el.textContent.trim().slice(0,60)||el.getAttribute('name'));
        const stats=[...root.querySelectorAll('.ac-student-stats>a')].map(el=>{const r=el.getBoundingClientRect();return {top:r.top,width:r.width,height:r.height}});
        const tasks=root.querySelector('.ac-dashboard-actions');const available=root.querySelector('#available-courses');
        const courseRows=[...root.querySelectorAll('.ac-course-card')].map(el=>el.getBoundingClientRect().height);
        const studentPanels=[...root.querySelectorAll('.ac-student-panel,.ac-request-shell')];
        return {studentSurfaceErrors:studentPanels.filter(transparent).length,stats,courseRows,availableTop:available?.getBoundingClientRect().top??null,viewport:innerWidth,scroll:document.documentElement.scrollWidth,panels:panels.length+studentPanels.length,boxed:panels.filter(el=>!transparent(el)).length,bad,tableOverflow:[...root.querySelectorAll('.ac-table-wrap')].some(el=>el.scrollWidth>el.clientWidth+2)};
      });
      console.log(file,width,JSON.stringify(metrics));
      if(!metrics.panels||metrics.studentSurfaceErrors||metrics.boxed||metrics.scroll>width+2||metrics.bad.length||metrics.tableOverflow)throw Error('Academy layout overflow or unwanted card: '+file+' at '+width);
      if(file==='student-dashboard.html'&&(metrics.stats.length!==3||Math.max(...metrics.stats.map(x=>x.top))-Math.min(...metrics.stats.map(x=>x.top))>1||metrics.stats.some(x=>x.height>90||x.width<70)))throw Error('Dashboard metrics must occupy exactly one compact row: '+width);
      if(width<=390&&file==='student-dashboard.html'&&(metrics.availableTop>650||metrics.courseRows.some(x=>x>100)))throw Error('Dashboard content too tall on phone: '+JSON.stringify(metrics));
      if((width===390||width===1440)&&['student-dashboard.html','student-certificates.html','admin-students.html','admin-enrollments.html','admin-requests.html','student-learning.html','admin-builder.html'].includes(file)){
        await page.screenshot({path:resolve(dir,file.replace('.html','-'+width+'.png')),fullPage:true,animations:'disabled'});
      }
    }
    await page.close();
  }
}finally{await browser.close();}
console.log('All twelve Academy views passed purposeful-panel and responsive checks at four viewport widths.');
