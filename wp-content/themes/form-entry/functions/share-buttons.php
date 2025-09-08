<?php

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Share Buttons
function scs_share_buttons($platforms){
      $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

      ?>
  <div class="share-buttons-container">
    <p class="btn btn-ghost share-toggle">Share <i class="fa-solid fa-share"></i></p>
    <div class="share-buttons">
    <?php
    foreach($platforms as $plat){
      if($plat == 'facebook'){ ?>
        <a class="facebook-share social-share" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $link; ?>" target="_blank"><i class="fa-brands fa-facebook"></i> Share on Facebook</a><?php
      }
      else if($plat == 'twitter'){ ?>
        <a class="twitter-share social-share" href="https://twitter.com/intent/tweet?text=Check this out - &url=<?=urlencode($link)?>" target="_blank"><i class="fa-brands fa-x-twitter"></i> Share on X</a> <?php
      }
      else if($plat == 'linkedin'){ ?>
        <a class="linkedin-share social-share" href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $link;?>" target="_blank"><i class="fa-brands fa-linkedin"></i> Share on LinkedIn</a> <?php
      }
      else if($plat == 'email'){ ?>
        <a class="email-share" href="mailto:?subject=Check this out &amp;body=Check out this article on <?php echo $link; ?> - Shared via the onsite share buttons" title="Share by Email"><i class="fa-solid fa-envelope"></i> Share by Email</a><?php
      }
    } ?>
    </div>
  </div><?php
} ?>
