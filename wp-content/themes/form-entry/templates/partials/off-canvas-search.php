<section class="off-canvas-search">
  <div class="off-canvas-search-content">
    <div class="search-bar">
      <?php $img = get_field('site_logo', 'options'); ?>
      <img class="logo" src="<?php echo $img["url"]; ?>" alt="site-logo"/>
      <form action="/search-results/"  method="get" class="search-form">
        <input type="search" placeholder="Search &hellip;" value="" name="_search" aria-label="Search Input">
        <select>
          <option value="value-one">Value One</option>
          <option value="value-two">Value Two</option>
          <option value="value-three">Value Three</option>
        </select>
        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      </form>
      <div class="close-button search-toggle">
        <button><i class="fa-regular fa-x"></i></button>
      </div>
    </div>
    <div class="product-recommendations">
        <h6>Top Products</h6>
        <div class="top-products">
          <?php 

          $args = array(
            'post_type'      => 'product',
            'posts_per_page' => 8, // Adjust as needed
            'meta_key'       => 'total_sales',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC',
          );

          $top_selling_query = new WP_Query($args);

          if ($top_selling_query->have_posts()) {
            while ($top_selling_query->have_posts()) {
                $top_selling_query->the_post();
                get_template_part('/templates/cards/product-card');
            }
          }
          wp_reset_postdata(); ?>
        </div>
    </div>
    <div class="content-recommendations">
        <h6>Safety Devices Latest</h6>
        <?php
          $news = get_posts(array(
            'post_type' => 'post',
            'numberposts' => 6, // Number of recent posts thumbnails to display
            'post_status' => 'publish' // Show only the published posts
          ));

          foreach($news as $new){ ?>
            <div class="post-overview">
              <div class="post-meta">
                <p class="category"><?= $cat[0]->name;  ?></p>
                <p class="date"><?= get_the_date('d/m/y', $new->ID); ?></p>
              </div>
              <h4><a href="<?= get_permalink($new->ID); ?>"><?= get_the_title($new->ID); ?></a></h4>
              <div class="description">
                <?= 'Get Short Description'; ?>
              </div>
            </div>
          <?php
          }
        ?>
    </div>
  </div>
</section>
