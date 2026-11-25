import { chromium } from 'playwright';
import { readdir, mkdir } from 'node:fs/promises';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';
const dir=resolve('docs/visual-qa/academy');
const files=(await readdir(dir)).filter(f=>f.endsWith('.html'));
if(files.length!==11)throw Error('Expected all eleven real Academy view fixtures; got '+files.length);
const browser=await chromium.launch({headless:true,args:['--no-sandbox']});
await mkdir(dir,{recursive:true});
try{
  for(const width of [320,390,768,1440]){
    const page=await browser.newPage({viewport:{width,height:900},deviceScaleFactor:1});
    for(const file of files){
      await page.goto(pathToFileURL(resolve(dir,file)).href,{waitUntil:'load'});
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
        const studentPanels=[...root.querySelectorAll('.ac-student-panel')];
        return {studentSurfaceErrors:studentPanels.filter(transparent).length,stats,courseRows,availableTop:available?.getBoundingClientRect().top??null,viewport:innerWidth,scroll:document.documentElement.scrollWidth,panels:panels.length+studentPanels.length,boxed:panels.filter(el=>!transparent(el)).length,bad,tableOverflow:[...root.querySelectorAll('.ac-table-wrap')].some(el=>el.scrollWidth>el.clientWidth+2)};
      });
      console.log(file,width,JSON.stringify(metrics));
      if(!metrics.panels||metrics.studentSurfaceErrors||metrics.boxed||metrics.scroll>width+2||metrics.bad.length||metrics.tableOverflow)throw Error('Academy layout overflow or unwanted card: '+file+' at '+width);
      if(file==='student-dashboard.html'&&(metrics.stats.length!==3||Math.max(...metrics.stats.map(x=>x.top))-Math.min(...metrics.stats.map(x=>x.top))>1||metrics.stats.some(x=>x.height>90||x.width<70)))throw Error('Dashboard metrics must occupy exactly one compact row: '+width);
      if(width<=390&&file==='student-dashboard.html'&&(metrics.availableTop>650||metrics.courseRows.some(x=>x>100)))throw Error('Dashboard content too tall on phone: '+JSON.stringify(metrics));
      if((width===390||width===1440)&&['student-dashboard.html','student-certificates.html','admin-students.html','admin-enrollments.html','student-learning.html','admin-builder.html'].includes(file)){
        await page.screenshot({path:resolve(dir,file.replace('.html','-'+width+'.png')),fullPage:true,animations:'disabled'});
      }
    }
    await page.close();
  }
}finally{await browser.close();}
console.log('All eleven Academy views passed purposeful-panel and responsive checks at four viewport widths.');
