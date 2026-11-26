import { chromium } from 'playwright';
import { readdir, mkdir } from 'node:fs/promises';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';
const dir=resolve('docs/visual-qa/academy');
const files=(await readdir(dir)).filter(f=>f.endsWith('.html'));
if(files.length!==13)throw Error('Expected all thirteen real Academy view fixtures; got '+files.length);
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
        if(file==='student-learning.html'){
        const outline=page.locator('.ac-study-directory');
        if(await outline.count()!==1||await outline.evaluate(el=>el.open))throw Error('Lesson outline should start collapsed');
        await outline.locator(':scope > summary').click();
        if(!await outline.locator('[aria-current="page"]').isVisible())throw Error('Current lesson not visible in outline');
        await outline.locator(':scope > summary').click();
        if(!await page.locator('.ac-study-heading').isVisible()||!await page.locator('.ac-study-nav').isVisible())throw Error('Study navigation missing');
      }
      if(file==='student-dashboard.html'){await page.evaluate(()=>localStorage.removeItem(document.querySelector('#ac-welcome-guide').dataset.guideKey));await page.reload({waitUntil:'load'});}
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
        const gap=await workspace.evaluate(el=>{const a=el.querySelector('.ac-cert-start').getBoundingClientRect(),b=el.querySelector('.ac-cert-activity').getBoundingClientRect();return b.top-a.bottom;});if(gap<20)throw Error('Certificate request and history need distinct spacing');
        const box=await workspace.boundingBox();
        if(!box||box.width>941)throw Error('Certificate workspace too wide');
        const request=page.locator('#ac-new-certificate');
        const course=workspace.locator('[data-cert-course]');
        const name=workspace.locator('[data-cert-name]');
        const confirmation=workspace.locator('[data-cert-confirm]');
        const output=workspace.locator('[data-cert-name-output]');
        const requestDialog=page.locator('#ac-cert-dialog');
        if(await course.isVisible()||await name.isVisible())throw Error('Request form must start collapsed');
        await page.evaluate(()=>location.hash='#ac-new-certificate');
        await course.waitFor({state:'visible'});
        if(!await course.isVisible()||await name.isVisible()||await confirmation.isVisible())throw Error('Only course selection should be shown initially');
        await course.selectOption({index:1});
        if(!await name.isVisible()||!await confirmation.isVisible())throw Error('Name and confirmation did not reveal after course selection');
        await name.fill('CI Correct Certificate Name');
        if(await output.textContent()!=='CI Correct Certificate Name')throw Error('Name confirmation did not update');
        if(await confirmation.isChecked()||await course.locator('xpath=ancestor::form').evaluate(form=>form.checkValidity()))throw Error('Unconfirmed name was accepted');
        await confirmation.check();
        if(!await course.locator('xpath=ancestor::form').evaluate(form=>form.checkValidity()))throw Error('Confirmed form should validate');
        await name.fill('CI Changed Name');
        if(await confirmation.isChecked()||await output.textContent()!=='CI Changed Name')throw Error('Changing name must clear consent');
        await confirmation.check();
        await course.selectOption({index:0});
        if(await name.isVisible()||await confirmation.isVisible()||await confirmation.isChecked())throw Error('Clearing course must reset later steps');
        if(!await requestDialog.isVisible())throw Error('Request deep link did not open popup');
        await page.mouse.click(1,1);await requestDialog.waitFor({state:'hidden'});
        await page.waitForFunction(()=>document.querySelector('#ac-cert-dialog [data-cert-dialog-body]').childElementCount===0);
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
        if(await results.locator('.ac-result-row').count()!==4||!await results.getByText('100% · Passed',{exact:true}).isVisible())throw Error('Academic results not available');
        if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth+2))throw Error('Academic results overflow');
        await results.locator('summary').click();
        const practice=page.locator('details[data-ac-popup]').filter({has:page.locator('summary').filter({hasText:'Practice quiz'})});
        await practice.locator('summary').click();
        const action=page.locator('.ac-action-dialog');await action.waitFor({state:'visible'});
        if(await action.locator('fieldset:visible').count()!==1)throw Error('Quiz must show one question at a time');
        await action.getByRole('button',{name:'Next question'}).click();
        if(!await action.getByText('Question 1 of 2',{exact:true}).isVisible())throw Error('Unanswered question advanced');
        await action.locator('fieldset:visible input').first().check();await action.getByRole('button',{name:'Next question'}).click();
        if(!await action.getByText('Question 2 of 2',{exact:true}).isVisible())throw Error('Question did not advance');
        await action.getByRole('button',{name:'Previous'}).click();
        if(!await action.locator('fieldset:visible input').first().isChecked())throw Error('Previous answer lost');

        await action.getByRole('button',{name:'Next question'}).click();
        if(await action.getByRole('button',{name:'Submit assessment'}).isVisible())throw Error('Submission exposed before review');
        await action.getByRole('button',{name:'Review answers'}).click();
        if(!await action.getByText('Question 2 of 2',{exact:true}).isVisible())throw Error('Unanswered final question advanced');
        await action.locator('fieldset:visible input').first().check();
        await action.getByRole('button',{name:'Review answers'}).click();
        if(!await action.getByRole('heading',{name:'Review your answers'}).isVisible()||await action.locator('.ac-quiz-review-row').count()!==2)throw Error('Answer review missing');
        if(await action.locator('.ac-quiz-meter').evaluate(el=>el.value)!==2)throw Error('Question progress incorrect');
        await action.getByRole('button',{name:'Edit question 1'}).click();
        await action.locator('fieldset:visible input').nth(1).check();
        await action.getByRole('button',{name:'Next question'}).click();
        if(!await action.locator('fieldset:visible input').first().isChecked())throw Error('Second answer lost after editing first');
        await action.getByRole('button',{name:'Review answers'}).click();
        if(!await action.getByRole('button',{name:'Submit assessment'}).isVisible())throw Error('Reviewed answers cannot be submitted');

        await page.mouse.click(1,1);await action.waitFor({state:'hidden'});
      }
      if(file==='admin-builder.html'){
        const author=page.locator('details[data-ac-popup]').filter({has:page.locator('summary').filter({hasText:'Add quiz, exam or assignment'})}).first();
        await author.locator('summary').click();const action=page.locator('.ac-action-dialog');await action.waitFor({state:'visible'});
        if(await action.locator('input[name=title]').isVisible())throw Error('Authoring starts with type only');
        await action.locator('select[name=kind]').selectOption('exam');await action.getByRole('button',{name:'Continue',exact:true}).click();
        if(!await action.locator('input[name=duration_minutes]').isVisible()||!await action.locator('textarea[name=questions]').isVisible())throw Error('Exam fields missing');
        await action.locator('select[name=kind]').selectOption('assignment');await action.getByRole('button',{name:'Continue',exact:true}).click();
        if(await action.locator('input[name=duration_minutes]').isVisible()||await action.locator('textarea[name=questions]').isVisible())throw Error('Assignment shows irrelevant fields');
        await action.getByRole('button',{name:'Close action'}).click();await action.waitFor({state:'hidden'});
      }
      if(file==='student-dashboard.html'){
        const icons=page.locator('.ac-student-stats strong[data-ac-icon]');
        if(await icons.count()!==3)throw Error('Student dashboard icons missing');
        if(!await icons.first().evaluate(el=>getComputedStyle(el,'::before').maskImage.startsWith('url(')))throw Error('Dashboard icon asset missing');
      }
      if(file.startsWith('admin-')){
        const canvas=page.locator('.ac-admin');
        const nav=canvas.locator('.ac-tabs a[data-ac-icon]');
        if(await nav.count()&&await nav.count()!==6)throw Error('Admin navigation icons incomplete');
        if(await nav.count()&&!await nav.first().evaluate(el=>getComputedStyle(el,'::before').maskImage.startsWith('url(')))throw Error('Admin navigation icon asset missing');

        if(await canvas.evaluate(el=>getComputedStyle(el).backgroundColor)!=='rgb(241, 244, 248)')throw Error('Admin background lacks the approved visual hierarchy');
      }
      if(file==='admin-courses.html'){
        const rows=page.locator('.ac-admin-course-row');
        if(await rows.count()<2)throw Error('Course catalogue rows missing');
        const hosting=rows.filter({hasText:'Web Hosting & Server Administration'});
        if(!await hosting.getByText('Learning published',{exact:true}).isVisible()||!await hosting.getByText('1 published lessons',{exact:true}).isVisible())throw Error('Published counts missing or draft counted');
        const settings=hosting.locator('.ac-course-settings');
        if(await settings.locator('select').isVisible())throw Error('Course settings exposed by default');
        await settings.locator('summary').click();
        const dialog=page.locator('.ac-action-dialog');await dialog.waitFor({state:'visible'});
        if(!await dialog.locator('select[name=colour]').isVisible()||!await dialog.getByRole('button',{name:'Save',exact:true}).isVisible())throw Error('Settings popup missing');
        await page.keyboard.press('Escape');await dialog.waitFor({state:'hidden'});
        if(await settings.locator('select').isVisible())throw Error('Settings not collapsed');
        await page.locator('.ac-catalogue-create summary').click();await dialog.waitFor({state:'visible'});
        if(!await dialog.locator('input[name=title]').isVisible())throw Error('Create course popup missing');
        await page.mouse.click(1,1);await dialog.waitFor({state:'hidden'});
        if(!(await hosting.getByRole('link',{name:'Course builder'}).getAttribute('href'))?.includes('action=builder'))throw Error('Builder route missing');
      }
      if(file==='admin-students.html'){
        const rows=page.locator('.ac-roster-row');
        if(await rows.count()<1)throw Error('Student directory rows missing');
        const sizes=await rows.evaluateAll(els=>els.map(el=>el.getBoundingClientRect().height));
        if(sizes.some(h=>h>105))throw Error('Student directory rows too tall: '+width+' '+JSON.stringify(sizes));
        const href=await rows.first().getAttribute('href');
        if(!href?.includes('tab=student')||!href.includes('id='))throw Error('Student row must open profile');
      }
      if(file==='admin-student.html'){
        await page.emulateMedia({reducedMotion:'reduce'});
        if(!await page.locator('.ac-course-chevron').first().evaluate(el=>getComputedStyle(el).transitionDuration==='0s'))throw Error('Reduced motion preference ignored');
        await page.emulateMedia({reducedMotion:'no-preference'});

        const deletion=page.locator('.ac-profile-danger summary');
        await deletion.click();const danger=page.locator('.ac-action-dialog');await danger.waitFor({state:'visible'});
        if(!await danger.getByText('Deletion is unavailable because approved training, certificate or payment records must be preserved.',{exact:true}).isVisible()||await danger.getByRole('button',{name:'Permanently delete student'}).count())throw Error('Protected student deletion not blocked in UI');
        await page.keyboard.press('Escape');await danger.waitFor({state:'hidden'});

        const courses=page.locator('.ac-profile-course');
        if(await courses.count()<1||await courses.evaluateAll(els=>els.some(el=>el.open)))throw Error('Course history must start collapsed');
        const target=courses.filter({has:page.locator('summary').filter({hasText:'Change status'})}).first();
        await target.locator(':scope > summary').click();
        const change=target.getByText('Change status',{exact:true});
        await change.click();
        const popup=page.locator('.ac-action-dialog');await popup.waitFor({state:'visible'});
        if(!await popup.locator('select[name=status]').isVisible()||!await popup.locator('textarea[name=reason]').isVisible())throw Error('Enrollment correction missing');
        const bounds=await popup.boundingBox();
        if(!bounds||bounds.width>461||bounds.width>width-20)throw Error('Profile action dialog too wide');
        await page.keyboard.press('Escape');await popup.waitFor({state:'hidden'});
        await target.locator(':scope > summary').click();
      }
      if(file==='admin-analytics.html'){
        const strip=page.locator('.ac-metric-strip');
        const bounds=await strip.boundingBox();
        if(await strip.locator('.ac-metric').count()!==4||!bounds||bounds.height>100||bounds.width>width+1)throw Error('Analytics metric strip must stay compact');
        await page.getByText('Review assignment',{exact:true}).first().click();
        const review=page.locator('.ac-action-dialog');
        await review.waitFor({state:'visible'});
        const popup=await review.boundingBox();
        if(!popup||popup.width>461||popup.width>width-20||popup.height>873)throw Error('Grading popup exceeds compact bounds');
        if(!await review.getByRole('link',{name:'Download submitted PDF'}).isVisible()||!await review.getByRole('button',{name:'Save grade'}).isVisible())throw Error('Review controls missing');
        await page.keyboard.press('Escape');await review.waitFor({state:'hidden'});

      }
      if(file==='admin-requests.html'){
        await page.locator('details[data-ac-popup]').filter({has:page.locator('summary').filter({hasText:'Decline request'})}).first().locator('summary').click();
        const action=page.locator('.ac-action-dialog');await action.waitFor({state:'visible'});
        if(!await action.locator('textarea[name=reason]').isVisible()||(await action.locator('textarea[name=reason]').getAttribute('required'))===null)throw Error('Decline reason missing');
        await page.keyboard.press('Escape');await action.waitFor({state:'hidden'});
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
        return {studentSurfaceErrors:studentPanels.filter(transparent).length,stats,courseRows,availableTop:available?available.getBoundingClientRect().top-(document.querySelector('.ac-student-topbar')?.getBoundingClientRect().height??0):null,viewport:innerWidth,scroll:document.documentElement.scrollWidth,panels:panels.length+studentPanels.length,boxed:panels.filter(el=>!transparent(el)).length,bad,tableOverflow:[...root.querySelectorAll('.ac-table-wrap')].some(el=>el.scrollWidth>el.clientWidth+2)};
      });
      console.log(file,width,JSON.stringify(metrics));
      if(!metrics.panels||metrics.studentSurfaceErrors||metrics.boxed||metrics.scroll>width+2||metrics.bad.length||metrics.tableOverflow)throw Error('Academy layout overflow or unwanted card: '+file+' at '+width);
      if(file==='student-dashboard.html'&&(metrics.stats.length!==3||Math.max(...metrics.stats.map(x=>x.top))-Math.min(...metrics.stats.map(x=>x.top))>1||metrics.stats.some(x=>x.height>90||x.width<70)))throw Error('Dashboard metrics must occupy exactly one compact row: '+width);
      if(width<=390&&file==='student-dashboard.html'&&(metrics.availableTop>650||metrics.courseRows.some(x=>x>100)))throw Error('Dashboard content too tall on phone: '+JSON.stringify(metrics));
      if((width===390||width===1440)&&['student-dashboard.html','student-certificates.html','admin-students.html','admin-courses.html','admin-enrollments.html','admin-requests.html','admin-student.html','admin-analytics.html','student-learning.html','admin-builder.html'].includes(file)){
        await page.screenshot({path:resolve(dir,file.replace('.html','-'+width+'.png')),fullPage:true,animations:'disabled'});
      }
    }
    await page.close();
  }
}finally{await browser.close();}
console.log('All thirteen Academy views passed purposeful-panel and responsive checks at four viewport widths.');
