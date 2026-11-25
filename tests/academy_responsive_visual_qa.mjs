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
      if(['student-dashboard.html','student-courses.html','student-certificates.html','student-profile.html'].includes(file)){
        const bar=page.locator('.ac-student-topbar'),profile=bar.getByRole('link',{name:'My profile'}),dlg=page.locator('#ac-profile-dialog');
        const mobile=width<992;
        if(await bar.locator('[data-student-profile-name]').isVisible()===mobile)throw Error('Profile name visibility incorrect');
        const title=await bar.locator('.topbar-title').boundingBox(),bell=await bar.locator('#topbarNotificationBtn').boundingBox(),button=await profile.boundingBox(),barBox=await bar.boundingBox();
        if(!title||!bell||!button||!barBox||Math.abs(bell.y-button.y)>2||barBox.height>80||button.x+button.width>width+1)throw Error('Student header not compact');
        // Isolated browser fixture: mock only the authenticated profile response, never send a real write.
        await page.evaluate(()=>{const native=window.fetch;window.fetch=async (url,opts)=>{if(String(url).includes('academy.php?action=profile')){if(opts.method!=='POST'||opts.body.get('csrf_token')!=='fixture'||opts.headers.Accept!=='application/json')throw Error('Profile request contract');return new Response(JSON.stringify({ok:true,name:opts.body.get('name'),phone:opts.body.get('phone')}),{headers:{'Content-Type':'application/json'}});}return native(url,opts);};});
        const original=page.url();await profile.click();await dlg.waitFor({state:'visible'});
        if(page.url()!==original)throw Error('Profile click navigated away');
        const bounds=await dlg.boundingBox();if(!bounds||bounds.x<0||bounds.x+bounds.width>width+1||bounds.y<0||bounds.y+bounds.height>901)throw Error('Profile dialog overflow');
        await dlg.locator('input[name=name]').fill('Updated Fixture Name');
        await dlg.locator('input[name=phone]').fill('1234567890');
        await dlg.getByRole('button',{name:'Save profile'}).click();await dlg.getByText('Profile saved.',{exact:true}).waitFor();
        await page.mouse.click(1,1);await dlg.waitFor({state:'hidden'});
        await profile.click();if(await dlg.locator('input[name=name]').inputValue()!=='Updated Fixture Name')throw Error('Saved profile not retained');
        await page.keyboard.press('Escape');await dlg.waitFor({state:'hidden'});
        if(page.url()!==original)throw Error('Profile save navigated away');
        // Reload fixture to reset its welcome-guide state after testing profile on the dashboard.
        if(file==='student-dashboard.html')await page.reload({waitUntil:'load'});
      }
      if(file==='student-dashboard.html'){
        const dialog=page.locator('#ac-welcome-guide');
        await dialog.waitFor({state:'visible',timeout:10000});
        if(await dialog.locator('a[data-guide-link]').count()!==2)throw Error('Welcome paths missing');
        if(await dialog.locator('a[data-guide-link]').nth(0).getAttribute('href')!=='/academy.php?action=certificates#ac-new-certificate'||await dialog.locator('a[data-guide-link]').nth(1).getAttribute('href')!=='/academy.php?action=courses#ac-explore-courses')throw Error('Guide destinations incorrect');
        if(await dialog.evaluate(el=>el.matches(':modal')))throw Error('Welcome guide must not block the workspace');
        const d=await dialog.boundingBox();
        if(!d||d.x<0||d.y<0||d.x+d.width>width+1||d.y+d.height>900+1)throw Error('Welcome guide exceeds viewport');
        if(width===320)await page.keyboard.press('Escape');else await dialog.getByRole('button',{name:'Got it, continue'}).click();
        await dialog.waitFor({state:'hidden'});
        await page.waitForFunction(()=>{const d=document.getElementById('ac-welcome-guide');return localStorage.getItem(d.dataset.guideKey)==='1';});
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
      if(file==='student-courses.html'){
        const pageHead=page.locator('.ac-course-page-head h1');
        const cards=page.locator('.ac-catalogue-grid .ac-course-card');
        if(await cards.count()<2||!(await cards.first().evaluate(el=>getComputedStyle(el).backgroundColor==='rgb(255, 255, 255)'&&parseFloat(getComputedStyle(el).borderTopWidth)>0)))throw Error('Individual course cards missing');
        if(await pageHead.evaluate(el=>parseFloat(getComputedStyle(el).fontSize))<=await cards.first().locator('h3').evaluate(el=>parseFloat(getComputedStyle(el).fontSize)))throw Error('Course title hierarchy missing');
        await page.evaluate(()=>location.hash='#ac-explore-courses');
        await page.waitForFunction(()=>document.activeElement?.id==='ac-explore-courses');
        await page.evaluate(()=>{history.replaceState(null,'',location.pathname);document.activeElement.blur();});
      }
      if(file==='student-certificates.html'){
        const workspace=page.locator('.ac-cert-compact');
        const box=await workspace.boundingBox();
        if(!box||box.width>941)throw Error('Certificate workspace too wide');
        const request=page.locator('#ac-new-certificate');
        const course=request.locator('[data-cert-course]');
        const name=request.locator('[data-cert-name]');
        const confirmation=request.locator('[data-cert-confirm]');
        const output=request.locator('[data-cert-name-output]');
        if(await course.isVisible()||await name.isVisible())throw Error('Request form must start collapsed');
        await page.evaluate(()=>location.hash='#ac-new-certificate');
        await course.waitFor({state:'visible'});
        if(!await course.isVisible()||await name.isVisible()||await confirmation.isVisible())throw Error('Only course selection should be shown initially');
        await course.selectOption({index:1});
        if(!await name.isVisible()||!await confirmation.isVisible())throw Error('Name and confirmation did not reveal after course selection');
        await name.fill('CI Correct Certificate Name');
        if(await output.textContent()!=='CI Correct Certificate Name')throw Error('Name confirmation did not update');
        if(await confirmation.isChecked()||await request.locator('form').evaluate(form=>form.checkValidity()))throw Error('Unconfirmed name was accepted');
        await confirmation.check();
        if(!await request.locator('form').evaluate(form=>form.checkValidity()))throw Error('Confirmed form should validate');
        await name.fill('CI Changed Name');
        if(await confirmation.isChecked()||await output.textContent()!=='CI Changed Name')throw Error('Changing name must clear consent');
        await confirmation.check();
        await course.selectOption({index:0});
        if(await name.isVisible()||await confirmation.isVisible()||await confirmation.isChecked())throw Error('Clearing course must reset later steps');
        await request.locator(':scope > summary').click();
        await page.evaluate(()=>location.hash='#ac-certificate-history');
        await page.waitForFunction(()=>document.activeElement?.id==='ac-certificate-history');
        await page.evaluate(()=>{history.replaceState(null,'',location.pathname);document.activeElement.blur();});
        const dialog=page.locator('#ac-cert-dialog');
        const declined=workspace.locator('details.ac-cert-record').filter({hasText:'Training could not be verified.'});
        if(await declined.locator('.ac-query-reason').isVisible())throw Error('Decline reason should start hidden');
        await declined.locator(':scope > summary').click();
        await dialog.waitFor({state:'visible'});
        if(!await dialog.locator('.ac-query-reason').isVisible())throw Error('Decline reason not in dialog');
        await dialog.getByRole('button',{name:'Close certificate details'}).click();
        await dialog.waitFor({state:'hidden'});
        const approved=workspace.locator('details.ac-cert-record').filter({has:page.locator('button').filter({hasText:'Get Certificate'})}).first();
        if(await approved.getByRole('button',{name:'Get Certificate',exact:true}).isVisible())throw Error('Approved certificate form starts expanded');
        await approved.locator(':scope > summary').click();
        await dialog.waitFor({state:'visible'});
        if(!await dialog.getByRole('button',{name:'Get Certificate',exact:true}).isVisible())throw Error('Approved certificate action not in dialog');
        const bounds=await dialog.boundingBox();
        if(!bounds||bounds.x<0||bounds.y<0||bounds.x+bounds.width>width+1||bounds.y+bounds.height>901)throw Error('Certificate dialog exceeds viewport');
        await page.keyboard.press('Escape');
        await dialog.waitFor({state:'hidden'});
        await page.waitForFunction(()=>document.querySelector('#ac-cert-dialog [data-cert-dialog-body]').childElementCount===0);
        if(await approved.getByRole('button',{name:'Get Certificate',exact:true}).isVisible())throw Error('Record not restored after closing');
        const pending=workspace.locator('details.ac-cert-record').filter({has:page.locator('button').filter({hasText:'Check Payment'})}).first();
        await pending.locator(':scope > summary').click();
        await dialog.waitFor({state:'visible'});
        if(!await dialog.getByRole('button',{name:'Check Payment',exact:true}).isVisible()||!await dialog.getByRole('button',{name:'Continue Payment',exact:true}).isVisible())throw Error('Pending payment actions missing');
        await dialog.getByRole('button',{name:'Close certificate details'}).click();
        await page.waitForFunction(()=>document.querySelector('#ac-cert-dialog [data-cert-dialog-body]').childElementCount===0);
        const issued=workspace.locator('details.ac-cert-record').filter({has:page.locator('a').filter({hasText:'Download Certificate'})}).first();
        await issued.locator(':scope > summary').click();
        await dialog.waitFor({state:'visible'});
        if(!await dialog.getByRole('link',{name:'Download Certificate',exact:true}).isVisible())throw Error('Issued certificate download missing');
        await dialog.getByRole('button',{name:'Close certificate details'}).click();
        await page.waitForFunction(()=>document.querySelector('#ac-cert-dialog [data-cert-dialog-body]').childElementCount===0);
        if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+2))throw Error('Certificate history overflows viewport');
      }
      if(file==='student-learning.html'){
        const results=page.locator('.ac-results-panel details');
        await results.locator('summary').click();
        if(await results.locator('.ac-result-row').count()!==3||!await results.getByText('100% · Passed',{exact:true}).isVisible())throw Error('Academic results not available');
        if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+2))throw Error('Academic results overflow');
        await results.locator('summary').click();
      }
      const metrics=await page.evaluate(()=>{
        const root=document.querySelector('.academy-workspace');
        const transparent=el=>getComputedStyle(el).backgroundColor==='rgba(0, 0, 0, 0)';
        const visible=el=>el.getClientRects().length>0&&getComputedStyle(el).visibility!=='hidden';
        const bounds=root.getBoundingClientRect();
        const panels=[...root.querySelectorAll('.ac-panel,.ac-course-card:not(.ac-catalogue-grid .ac-course-card),.ac-student-stats a,.ac-student-welcome')];
        const controls=[...root.querySelectorAll('button,a,input:not([type=hidden]),select')].filter(visible);
        const bad=controls.filter(el=>{const r=el.getBoundingClientRect();return r.left<bounds.left-2||r.right>bounds.right+2;}).map(el=>el.textContent.trim().slice(0,60)||el.getAttribute('name'));
        const stats=[...root.querySelectorAll('.ac-student-stats>a')].map(el=>{const r=el.getBoundingClientRect();return {top:r.top,width:r.width,height:r.height}});
        const tasks=root.querySelector('.ac-dashboard-actions');const available=root.querySelector('#available-courses');
        const courseRows=[...root.querySelectorAll('.ac-course-card')].map(el=>el.getBoundingClientRect().height);
        const studentPanels=[...root.querySelectorAll('.ac-student-panel,.ac-request-shell,.ac-catalogue-grid .ac-course-card')];
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
