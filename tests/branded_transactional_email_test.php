<?php
declare(strict_types=1);
require __DIR__.'/../app/support_email.php';
function verify(bool $ok,string $msg):void{if(!$ok)throw new RuntimeException($msg);}
$otp=iworks_email_html('Your iWorks GT login OTP',"Hello Test <script>,\n\nYour OTP code is: 123456\nThis code expires in 10 minutes.");
verify(str_contains($otp,'123456')&&str_contains($otp,'user-select:all')&&str_contains($otp,'press and hold to copy'),'OTP code highlighted');
verify(!str_contains($otp,'href=')&&!str_contains($otp,'navigator.clipboard')&&str_contains($otp,'text-align:center'),'OTP contains no links or fake copy button');
verify(str_contains($otp,'&lt;script&gt;')&&!str_contains($otp,'<script>'),'Escape untrusted content');
$reset=iworks_email_html('Reset your iWorks GT portal password',"Use this link:\nhttps://portal.iworksgt.com/index.php?page=reset_password&token=test");
verify(str_contains($reset,'Reset password')&&str_contains($reset,'page=reset_password&amp;token=test'),'Reset action and token preserved');
verify(!iworks_email_safe_url('https://portal.iworksgt.com.evil.test/reset')&&!iworks_email_safe_url('https://portal.iworksgt.com@evil.test/reset'),'Reject third-party actions');
foreach(['Payment confirmed','Job status updated','Certificate issued','Course approved','Commission payout updated','You have a new reply'] as $subject){
 $html=iworks_email_html($subject,"Hello Synthetic User,\n\nOpen: https://portal.iworksgt.com/index.php?page=dashboard");
 verify(str_contains($html,'iworks-email-logo.png')&&str_contains($html,'#ff6600')&&str_contains($html,'max-width:600px'),'Approved branded layout');
 verify(!str_contains($html,'Client portal')&&str_contains($html,'Synthetic User'),'Preserve text without footer navigation');
 if($subject!=='You have a new reply')verify(!str_contains($html,'href='),'Routine update has no unnecessary action links');
}
$plain='Hello, your code is 123456.';
$mime=iworks_email_alternative_body('fixture-boundary',$plain,$otp);
verify(str_contains($mime,'Content-Type: text/plain')&&str_contains($mime,'Content-Type: text/html')&&str_contains($mime,'--fixture-boundary--'),'Multipart alternative structure');
verify(str_contains(quoted_printable_decode($mime),$plain),'Plain fallback preserved');
$academy=file_get_contents(__DIR__.'/../app/academy.php');
verify(str_contains($academy,'multipart/mixed')&&str_contains($academy,'multipart/alternative')&&str_contains($academy,'application/pdf')&&str_contains($academy,'iworks_email_html'),'Nested branded certificate with PDF');
echo "PASS shared branded email rendering, safe actions and attachment contract\n";
