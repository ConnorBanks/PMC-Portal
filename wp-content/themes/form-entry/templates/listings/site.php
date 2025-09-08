<?php $client = get_field('client'); ?>
<div class="list-item site-item">
    <div class="name"><?= get_the_title(); ?></div>
    <div class="location"><?= get_field('location'); ?></div>
    <div class="primary-contact"><a href="<?= get_permalink($client); ?>"><?= get_the_title($client); ?></a></div>
    <div class="edit"><a href="<?= the_permalink(); ?>"><i class="fas fa-edit"></i></a></div>
</div>