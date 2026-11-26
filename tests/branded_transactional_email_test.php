<?php
declare(strict_types=1);
require __DIR__.'/../app/support_email.php';
function verify(bool $ok,string $msg):void{if(!$ok)throw new RuntimeException($msg);}
$otp=iworks_email_html('Your iWorks GT login OTP',"Hello Test <script>,\n\nYour OTP code is: 123456\nThis code expires in 10 minutes.");
verify(str_contains($otp,'123456')&&str_contains($otp,'user-select:all')&&str_contains($otp,'Copy code: select or press and hold'),'OTP code highlighted');
verify(!str_contains($otp,'href=')&&!str_contains($otp,'navigator.clipboard')&&str_contains($otp,'text-align:center'),'OTP contains no links or fake copy button');
verify(str_contains($otp,'&copy; iWorks Global')&&!str_contains($otp,'iWorks Global Limited')&&str_contains($otp,'#fff4eb'),'Compact original orange design and approved copyright');
verify(str_contains($otp,'&lt;script&gt;')&&!str_contains($otp,'<script>'),'Escape untrusted content');
$reset=iworks_email_html('Reset your iWorks GT portal password',"Use this link:\nhttps://portal.iworksgt.com/index.php?page=reset_password&token=test");
verify(str_contains($reset,'Reset password')&&str_contains($reset,'page=reset_password&amp;token=test'),'Reset action and token preserved');
verify(!iworks_email_safe_url('https://portal.iworksgt.com.evil.test/reset')&&!iworks_email_safe_url('https://portal.iworksgt.com@evil.test/reset'),'Reject third-party actions');
foreach(['Payment confirmed','Job status updated','Certificate issued','Course approved','Commission payout updated','You have a new reply'] as $subject){
 $html=iworks_email_html($subject,"Hello Synthetic User,\n\nOpen: https://portal.iworksgt.com/index.php?page=dashboard");
 verify(str_contains($html,'iworks-email-logo.png')&&str_contains($html,'#ff6600')&&str_contains($html,'max-width:460px'),'Approved branded layout');
 verify(!str_contains($html,'Client portal')&&str_contains($html,'Synthetic User'),'Preserve text without footer navigation');
 if($subject!=='You have a new reply')verify(!str_contains($html,'href='),'Routine update has no unnecessary action links');
}
$plain='Hello, your code is 123456.';
$mime=iworks_email_alternative_body('fixture-boundary',$plain,$otp);
verify(str_contains($mime,'Content-Type: text/plain')&&str_contains($mime,'Content-Type: text/html')&&str_contains($mime,'--fixture-boundary--'),'Multipart alternative structure');
verify(str_contains(quoted_printable_decode($mime),$plain),'Plain fallback preserved');
// Test the actual approved source raster in a non-delivery MIME fixture.
// The deployment test uses the cropped official logo if installed.
$logo=iworks_email_logo_bytes()??file_get_contents(__DIR__.'/../assets/branding/iworks-social-share.png');
verify(is_string($logo)&&str_starts_with($logo,"\x89PNG\r\n\x1a\n"),'Approved PNG source available');
$related=iworks_email_alternative_body('fixture-inline',$plain,$otp,$logo);
$decoded=quoted_printable_decode($related);
verify(str_contains($related,'Content-Type: multipart/related')&&str_contains($related,'Content-Disposition: inline; filename="iworks-email-logo.png"'),'PNG is embedded inline in HTML alternative');
verify(preg_match('/Content-ID: <([^>]+)>/',$related,$cid)===1&&str_contains($decoded,'src="cid:'.$cid[1].'"'),'Image markup and MIME Content-ID match');
verify(!str_contains($decoded,'https://portal.iworksgt.com/assets/branding/iworks-email-logo.png'),'Embedded email does not rely on remote image loading');
verify(preg_match('/Content-Disposition: inline; filename="iworks-email-logo.png"\r\n\r\n([A-Za-z0-9+\/=\r\n]+)\r\n--/',$related,$payload)===1&&base64_decode(preg_replace('/\s+/','',$payload[1]),true)===$logo,'Inline asset bytes match approved PNG exactly');
$academy=file_get_contents(__DIR__.'/../app/academy.php');
verify(str_contains($academy,'multipart/mixed')&&str_contains($academy,'multipart/alternative')&&str_contains($academy,'application/pdf')&&str_contains($academy,'iworks_email_html'),'Nested branded certificate with PDF');
echo "PASS shared branded email rendering, safe actions and attachment contract\n";
