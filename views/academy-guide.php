<?php
declare(strict_types=1);
// Rendered only from the authenticated student views.
function ac_guide(string $screen,int $student,bool $welcome=false):void{
    $guides=[
        'dashboard'=>['Getting started','Choose your next step.','Use the links below to go straight to the right action.','certificates'],
        'courses'=>['How to apply for a course','Find a course under Explore courses and select Apply for course.','Wait for administrator approval. Once approved, select Open course to begin learning.','courses'],
        'certificates'=>['How to get your certificate','Start a new request, select the completed course and confirm your exact name.','Tap any activity to see the next action: preview, pay, check payment, download or read a decline reason.','certificates'],
        'profile'=>['How to update your profile','Check your full name and phone number.','Select Save profile. Your verified email cannot be changed here.','profile'],
        'learn'=>['How to study your course','Choose a published lesson, read the content and use the video or materials if available.','Complete any available assessments, select Mark as complete and move to the next lesson. Certification requires separate approval.','courses'],
        'preview'=>['Before you pay','Check your name, course title and training duration on the watermarked preview.','If the details are correct, review the fee and select Pay Now when available. After payment, return to My Certificates. If already paid, use Check Payment instead of paying again.','certificates'],
    ];
    [$title,$one,$two,$dest]=$guides[$screen]??$guides['dashboard'];
    $id='ac-guide-'.$screen;
    echo '<details class="ac-guide" id="'.e($id).'"><summary><span class="ac-guide-icon" aria-hidden="true"><i class="bi bi-question-circle"></i></span><span>'.e($title).'</span><span class="ac-guide-label">View guide</span></summary><div class="ac-guide-body"><ol><li>'.e($one).'</li><li>'.e($two).'</li></ol>';
    if($screen==='dashboard')echo '<div class="ac-guide-links"><a href="/academy.php?action=certificates#ac-new-certificate">Request a certificate</a><a href="/academy.php?action=courses#ac-explore-courses">Explore courses</a><a href="/academy.php?action=profile">My Profile</a></div>';
    if($screen==='certificates')echo '<div class="ac-guide-links"><a href="#ac-new-certificate">Start a request</a><a href="#ac-certificate-history">View my activity</a></div>';
    if($screen==='courses')echo '<div class="ac-guide-links"><a href="#ac-explore-courses">Find a course</a></div>';
    echo '<p class="ac-guide-note">Administrator decisions appear in your notifications.</p></div></details>';
    echo '<script src="/assets/js/academy-guide.js?v=3" defer></script>';
    if($screen!=='dashboard')return;
    echo '<dialog class="ac-welcome-guide" id="ac-welcome-guide" aria-labelledby="ac-guide-title" data-guide-key="iworks-academy-guide-v1-'.max(0,$student).'" data-guide-auto="'.($welcome?'1':'0').'"><div class="ac-welcome-head"><span class="ac-guide-kicker">STUDENT GUIDE</span><button type="button" class="ac-guide-close" data-guide-close aria-label="Close guide"><i class="bi bi-x-lg" aria-hidden="true"></i><span>Close</span></button></div><h2 id="ac-guide-title">Welcome to iWorks Global Academy</h2><p>Choose a starting point. You can keep browsing while this guide is open.</p><div class="ac-welcome-path"><h3>Get a certificate</h3><p>Request a certificate for completed training. Approval comes before payment.</p><a href="/academy.php?action=certificates#ac-new-certificate" data-guide-link>Go to My Certificates</a></div><div class="ac-welcome-path"><h3>Start a new course</h3><p>Find a course, apply and begin learning after approval.</p><a href="/academy.php?action=courses#ac-explore-courses" data-guide-link>Go to My Courses</a></div><button type="button" class="ac-guide-dismiss" data-guide-close>Got it, continue</button><p class="ac-guide-note">You can reopen the guide anytime from your dashboard.</p></dialog>';
}
