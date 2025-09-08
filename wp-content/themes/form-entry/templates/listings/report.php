<?php 
$status = get_post_status();

if($status == 'publish'){
    $postStatus = 'Sent'; 
}
if($status == 'draft'){
    $postStatus = 'Draft'; 
} 

$client = get_field('client');
$cname = get_the_title($client);

$site = get_field('site');
$sname = get_the_title($site);?>


<div class="list-item report-item">
    <div class="name"><?= get_the_title()?></div>
    <div class="site"><?= $sname; ?></div>
    <div class="client"><?= $cname; ?></div>
    <div class="status <?= $status; ?>"><p class="btn"><span><?= $postStatus ?></span></p></div>
    <a class="full-link" href="<?= the_permalink(); ?>"></a>
</div>