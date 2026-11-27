<?php if (has_role(['admin','agent'])):
$communicationCurrent=(string)($_GET['page']??'website_inbox');
$communicationTabs=[
    ['website_inbox','Website enquiries','bi-envelope-paper'],
    ['live_inbox','Live chat','bi-chat-dots'],
    ['messages','Team messages','bi-people'],
];
if(has_role(['admin']))$communicationTabs[]=['support','Support tickets','bi-life-preserver'];
?>
<nav class="iw-communications-nav" aria-label="Communications">
    <?php foreach($communicationTabs as [$route,$label,$icon]): ?>
    <a href="<?= e(url($route)) ?>"<?= $communicationCurrent===$route?' aria-current="page"':'' ?>><i class="bi <?= e($icon) ?>" aria-hidden="true"></i><span><?= e($label) ?></span></a>
    <?php endforeach; ?>
</nav>
<?php endif; ?>
