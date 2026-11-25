<?php
declare(strict_types=1);
// Run in a private staged checkout, never from an HTTP document root.
if (PHP_SAPI!=='cli') {http_response_code(404);exit(2);}
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/academy.php';
$failed=0;
function academy_preflight(string $label,callable $check):void {
    global $failed;
    try { if (!$check()) throw new RuntimeException('not ready'); echo "PASS ".$label."\n"; }
    catch(Throwable $e){$failed++;echo "BLOCK ".$label."\n";}
}
academy_preflight('PHP 8.2 or later',fn()=>PHP_VERSION_ID>=80200);
academy_preflight('PDO MySQL',fn()=>extension_loaded('pdo_mysql'));
academy_preflight('cURL',fn()=>extension_loaded('curl'));
academy_preflight('UTF-8 handling',fn()=>extension_loaded('mbstring'));
academy_preflight('Inkscape PDF renderer',fn()=>is_executable('/usr/bin/inkscape')&&function_exists('proc_open'));
foreach(['orange','purple','green','blue'] as $colour){
    academy_preflight('Approved '.$colour.' artwork and PDF output',function()use($colour){
        $pdf=academy_pdf(['colour'=>$colour,'certificate_number'=>'IWGA-TEST-ONLY','student_name'=>'TEST STUDENT - NOT VALID',
            'course_title'=>'WordPress & CMS Web Design','duration'=>'12 weeks','issued_at'=>'2026-11-25 12:00:00']);
        return str_starts_with($pdf,'%PDF-')&&strlen($pdf)>1000;
    });
}
academy_preflight('Read-only database connectivity',fn()=> (int)(db_one('SELECT 1 AS ok')['ok']??0)===1);
academy_preflight('Existing users role schema',function(){
    $row=db_one("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='role'");
    $type=(string)($row['COLUMN_TYPE']??'');
    foreach(['admin','agent','client','finance','author'] as $role) if(!str_contains($type,"'".$role."'"))return false;
    // Refuse to alter a newer enum with additional values that the migration cannot preserve.
    return in_array($type,["enum('admin','agent','client','finance','author')","enum('admin','agent','client','finance','author','student')"],true);
});
academy_preflight('Existing mail enabled and sender valid',fn()=>email_enabled()&&filter_var(setting('mail_from_email',''),FILTER_VALIDATE_EMAIL)!==false);
academy_preflight('Existing Paystack configuration present',function(){return academy_paystack_key()!=='';});
echo "No migration, charge, webhook registration, email or live deployment performed.\n";
echo "Preflight blocks: ".$failed."\n";exit($failed?1:0);
