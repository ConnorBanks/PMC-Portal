<?php 
$contact = get_field('primary_contact'); ?>
<div class="list-item client-item">
    <div class="primary-contact"><?= $contact['name']; ?></div>
    <div class="name"><?= get_the_title(); ?></div>
    <div class="sites"></div>
    <div class="edit"><a href="<?= get_permalink(); ?>"><i class="fas fa-edit"></i></a></div>
</div>