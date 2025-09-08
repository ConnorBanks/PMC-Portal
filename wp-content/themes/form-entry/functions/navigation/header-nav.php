<?php

function blacksheep_custom_menu($location = 'primary') {
  wp_nav_menu([
      'theme_location' => $location,
      'menu_class'     => 'main-menu',
      'container'      => 'nav',
      'walker'         => new BlackSheep_Walker_Nav_Menu()
  ]);
}

class BlackSheep_Walker_Nav_Menu extends Walker_Nav_Menu {
  public function start_lvl( &$output, $depth = 0, $args = null ) {
      $indent = str_repeat("\t", $depth);
      $output .= "\n$indent<ul class=\"submenu hidden\">\n";
  }

  public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
      $has_children = in_array('menu-item-has-children', $item->classes);
      $toggle = $has_children ? '<button class="submenu-toggle" aria-label="Toggle submenu"><i class="fas fa-chevron-right submenu-arrow"></i></button>' : '';

      // Get ACF icon field
      $icon_html = '';
      $icon = get_field('icon', $item);
      if ($icon) {
          if (strpos($icon, '<svg') !== false || strpos($icon, '<img') !== false) {
              $icon_html = $icon;
          } else {
              $icon_html = '<span class="menu-icon">' . $icon . '</span>';
          }
      }

      // Add chevron for submenu items (depth 1 or greater)
      $chevron = $depth > 0 ? '<i class="fas fa-chevron-right submenu-arrow"></i>' : '';

      $output .= sprintf(
          '<li class="%s"><a href="%s">%s%s<span class="menu-label">%s</span></a>%s',
          implode(' ', $item->classes),
          esc_url($item->url),
          $chevron,
          $icon_html,
          esc_html($item->title),
          $toggle
      );
  }

  public function end_el( &$output, $item, $depth = 0, $args = null ) {
      $output .= "</li>\n";
  }
}

// Main Navigation
function header_nav() {
    ?>
    <div class="nav-container">
      <?php
      $menu_name = 'header-menu';
      $locations = get_nav_menu_locations();
      $menu = wp_get_nav_menu_object( $locations[ $menu_name ] );
      $menuitems = wp_get_nav_menu_items( $menu->term_id, array( 'order' => 'DESC' ) ); ?>
        <ul class="main-nav">
            <?php
            $count = 0;
            $submenu = false;
            if($menuitems){
              foreach( $menuitems as $item ):
                  // set up title and url
                  $active = '';
                  $title = $item->title;
                  $link = $item->url;
                  $ID = $item->ID;
                  
                  // item does not have a parent so menu_item_parent equals 0 (false)
                  if ( !$item->menu_item_parent ):
                  // save this id for later comparison with sub-menu items
                  $parent_id = $item->ID;?>
                  <li class="item parent-link <?= $active; ?>">
                    <?php 
                    ?>
                      <p class="title" data-type="<?= $colour; ?>"><a href="<?php echo $link; ?>"><span class="text"><?php echo $title; ?></span><span style="background-color:<?= $colour; ?>" class="colour-bar"></span></a> </p>
                      <?php endif; ?>
                          <?php if ( $parent_id == $item->menu_item_parent ): ?>
                              <?php if ( !$submenu ): $submenu = true;?>
                              <ul class="sub-menu">
                                <div class="sub-menu-container">
      
                                  <div class="menu-items">
                                  <?php endif; ?>
                                    <li class="item child-link <?php if(get_the_title() == $title){ echo 'current-page'; } ?>">
                                        <a href="<?php echo $link . $urlExt; ?>" class="title">
                                          <span class="text"><?php echo $title; ?></span>
                                        </a>
                                    </li>
                                    ?>
                                  </div>
                                </div>
                              </ul>
                              <?php $submenu = false; endif; ?>
                          <?php //endif; ?>
                    </li>
                  <?php//$submenu = false; endif; ?>
              <?php $count++; endforeach; ?>
            <?php
            } ?>
          </ul>
    </div>
<?php
}
