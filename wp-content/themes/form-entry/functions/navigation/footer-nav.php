<?php
// Footer Navigation
function footer_nav() { ?>
  <div class="nav-container">
    <?php
    $menu_name = 'footer-menu';
    $locations = get_nav_menu_locations();
    $menu = wp_get_nav_menu_object( $locations[ $menu_name ] );
    $menuitems = wp_get_nav_menu_items( $menu->term_id, array( 'order' => 'DESC' ) ); ?>
    <nav class="desktop-nav" role="navigation">
      <ul class="main-nav">
          <?php
          $submenu = false;
          if($menuitems){
            foreach( $menuitems as $item ){
                $title = $item->title;
                $link = $item->url; ?>
            <li class="item parent-link">
              <a href="<?php echo $link; ?>">
                <span class="text"><?php echo $title; ?></span>
              </a>
            </li>
      <?php }
          } ?>
        </ul>
      </nav>
  </div>
  <?php
}
