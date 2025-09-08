<div class="social-channels">
  <p>Follow Us</p>
  <?php

    $social = get_field('social_media', 'options');

  
    if(isset($social['facebook'])){ ?>
      <a target="_blank" href="<?= $social['facebook']; ?>"><i class="fa-brands fa-facebook-f"></i></a>
    <?php
    }
    if(isset($social['instagram'])){ ?>
      <a target="_blank" href="<?= $social['instagram']; ?>"><i class="fa-brands fa-instagram"></i></a>
    <?php
    }
    if(isset($social['linkedin'])){ ?>
      <a target="_blank" href="<?= $social['linkedin']; ?>"><i class="fa-brands fa-linkedin"></i></a>
    <?php
    }
    if(isset($social['tiktok'])){ ?>
      <a target="_blank" href="<?= $social['tiktok']; ?>"><i class="fa-brands fa-tiktok"></i></a>
    <?php
    }
    if(isset($social['whatsapp'])){ ?>
      <a target="_blank" href="<?= $social['whatsapp']; ?>"><i class="fa-brands fa-whatsapp"></i></a>
    <?php
    }
    if(isset($social['twitch'])){ ?>
      <a target="_blank" href="<?= $social['facebook']; ?>"><i class="fa-brands fa-twitch"></i></a>
    <?php
    }
  ?>
</div>
