<?php
declare(strict_types=1);
require __DIR__.'/../app/academy.php';
function academy_assert(bool $condition,string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
academy_assert(ACADEMY_FEE_MINOR===10200000,'Initial checkout amount');
academy_assert(academy_valid_name("Adeola Michael O'Connor"),'Normal name');
academy_assert(!academy_valid_name('<script>'),'Reject markup');
academy_assert(academy_checkout_url_valid('https://checkout.paystack.com/example'),'Valid checkout');
foreach(['http://checkout.paystack.com/example','https://checkout.paystack.com.evil.test/a','https://user@checkout.paystack.com/a','https://checkout.paystack.com:123/a'] as $url) academy_assert(!academy_checkout_url_valid($url),'Reject unsafe URL');
$entry=file_get_contents(__DIR__.'/../academy.php');
$student=file_get_contents(__DIR__.'/../views/academy-student.php');
academy_assert(str_contains($student,'Get Certificate')&&!str_contains($entry,'Preview certificate ·'),'Simple dashboard CTA');
academy_assert(str_contains($entry,'Pay Now')&&str_contains($entry,'number_format(ACADEMY_FEE_MINOR/100,2)'),'Checkout amount disclosed');
academy_assert(str_contains($entry,"require_roles(['student'])"),'Student role check');
academy_assert(str_contains($entry,"require_roles(['admin'])"),'Admin role check');
require __DIR__.'/../app/auth_rate_limits.php';
foreach (['academy_register_ip','academy_verify_ip','academy_inquiry'] as $scope) academy_assert(iworks_auth_bucket($scope,'fixture',900)['scope']===$scope,'Academy auth rate limit scope');
academy_assert(str_contains($student,'Request course review')&&str_contains($entry,'academy_student_notice'),'Student course review and confirmation');
academy_assert(str_contains($student,"\$screen==='dashboard'")&&str_contains($student,"\$screen==='courses'")&&str_contains($student,"\$screen==='certificates'"),'Dedicated student screens');
$public=file_get_contents(__DIR__.'/../views/public-service-detail.php');
academy_assert(str_contains($public,"'pro-training'")&&str_contains($public,'iwAcademyCoursesTitle')&&str_contains($public,"'action'=>'apply'"),'Public training application link');
academy_assert(str_contains($entry,"academy_course_intent")&&str_contains($student,'Submit application')&&str_contains($student,'Pending application'),'Course selection retained and confirmed after login');
$learning=file_get_contents(__DIR__.'/../app/academy_learning.php');
$learningView=file_get_contents(__DIR__.'/../views/academy-learning.php');
academy_assert(str_contains($learning,"en.status IN ('enrolled','completed') AND c.active=1")&&str_contains($learning,"en.student_id=:student AND en.course_id=:course"),'Learning area limited to approved owned enrollment');
academy_assert(str_contains($learningView,'Course lessons and modules have not been published yet.'),'No false claim of published lessons');
$adminView=file_get_contents(__DIR__.'/../views/academy-admin.php');
academy_assert(str_contains($entry,"\$mode==='verify_payment'")&&str_contains($adminView,"ac_admin_post('verify_payment','certificates')"),'Admin verifies existing payments');
academy_assert(str_contains($entry,"\$mode==='retry_email'")&&str_contains($adminView,"ac_admin_post('retry_email','certificates')"),'Admin can retry issued certificate email');
academy_assert(str_contains($entry,'do not pay twice')&&str_contains($student,'Certificate email queued for delivery.'),'Clear student payment and delivery feedback');
$reg=file_get_contents(__DIR__.'/../views/academy-register.php');$router=file_get_contents(__DIR__.'/../index.php');
academy_assert(str_contains($reg,'data-iw-password="iwAcademyPassword"')&&str_contains($reg,'data-iw-password="iwAcademyConfirm"'),'Inline password toggles');
academy_assert(str_contains($router,"['public_auth_status','notifications','realtime']")&&str_contains($router,"\$scope !== 'notifications'"),'Student notifications only');
academy_assert(str_contains($student,"ac_form('inquiry')")&&str_contains($adminView,"ac_admin_post('send_update','student',"),'Two-way inquiry and update forms');
echo "Academy smoke checks passed.\n";
