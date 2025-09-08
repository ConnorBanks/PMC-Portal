<?php
// Main Navigation
function mobile_nav() {
  ?>
    <div class="nav-container">
      <?php
      $menu_name = 'mobile-menu';
      $locations = get_nav_menu_locations();
      $menu = wp_get_nav_menu_object( $locations[ $menu_name ] );
      $menuitems = wp_get_nav_menu_items( $menu->term_id, array( 'order' => 'DESC' ) ); ?>
      <nav class="desktop-nav" role="navigation">
        <ul class="main-nav">
            <?php
            $count = 0;
            $submenu = false;
            foreach( $menuitems as $item ):
                // set up title and url
                $title = $item->title;
                $link = $item->url;
                // item does not have a parent so menu_item_parent equals 0 (false)
                if ( !$item->menu_item_parent ):
                // save this id for later comparison with sub-menu items
                $parent_id = $item->ID;?>
            <li class="item parent-link">
                <p class="title"> <a href="<?php echo $link; ?>"><?php echo $title; ?></a> </p>
            <?php endif; ?>
                <?php if ( $parent_id == $item->menu_item_parent ): ?>
                    <?php if ( !$submenu ): $submenu = true;?>
                    <ul class="sub-menu">
                      <div class="sub-menu-container">
                        <div class="menu-items">
                        <?php endif; ?>
                          <li class="item child-link">
                              <a href="<?php echo $link; ?>"><?php echo $title; ?></a>
                          </li>
                        <?php if ( $menuitems[ $count + 1 ]->menu_item_parent != $parent_id && $submenu ): ?>
                        </div>
                      </div>
                    </ul>
                    <?php $submenu = false; endif; ?>
                <?php endif; ?>
            <?php if ( $menuitems[ $count + 1 ]->menu_item_parent != $parent_id ): ?>
            </li>
            <?php $submenu = false; endif; ?>
        <?php $count++; endforeach; ?>
          </ul>
        </nav>
    </div>
<?php
}
