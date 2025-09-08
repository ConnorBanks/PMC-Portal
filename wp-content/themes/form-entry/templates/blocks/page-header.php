<?php
/**
 * Page Header
 *
 * @param   array $block The block settings and attributes.
 * @param   string $content The block inner HTML (empty).
 * @param   bool $is_preview True during AJAX preview.
 * @param   (int|string) $post_id The post ID this block is saved to.
 */

// Create id attribute allowing for custom "anchor" value.
// if(!is_page('search-results')){
//   $id = 'page-header-' . $block['id'];
//   if( !empty($block['anchor']) ) {
//       $id = $block['anchor'];
//   }
// }


// Create class attribute allowing for custom "className" and "align" values.
$className = 'page-header-block';
if( !empty($block['className']) ) {
    $className .= ' ' . $block['className'];
}
if( !empty($block['align']) ) {
    $className .= ' align' . $block['align'];
}

$title = get_the_title();

if(is_page('search-results')){
  $type = 'text-only';
  if($_GET['_search'] != ''){
    $title .= ': "' . $_GET['_search'] . '"';
  }
  $media = 'none';
} 

if(is_singular('report')){
  $report_number = $report_number
    ?? ( function_exists('get_query_var') ? get_query_var('report_number', '') : '' )
    ?? '';

if ($report_number === '' && function_exists('get_field') && is_singular('report')) {
    // fallback: pull from ACF if this is a single Report post
    $report_number = (string) get_field('report_number', get_the_ID()) ?: '';
}
}?>
  <div id="<?php echo esc_attr($id); ?>" class="<?php echo esc_attr($className); ?>">
    <div class="content">
      <h1><span class="title"><?php if(isset($report_number)){ echo ' Report Number: #';}?><?= $title; ?></span></h1>
      <?php if(is_page('clients')){ ?>
        <p class="btn"><a href="<?= get_site_url() . '/add-new-client' ?>">Add New Client</a></p>
        <?php
      } ?>
      <?php if(is_page('sites')){ ?>
        <p class="btn"><a href="<?= get_site_url() . '/add-new-site' ?>">Add New Site</a></p>
        <?php
      } ?>
      <?php if(is_page('reports')){ ?>
        <p class="btn"><a href="<?= get_site_url() . '/add-new-report' ?>">Add New Report</a></p>
        <?php
      } ?>
      <?php if(is_page('reports') || is_page('sites') || is_page('clients')){ ?>
        <div class="facets-container">
        <?php echo do_shortcode('[facetwp facet="search"]'); ?>
        <?php if(is_page('clients') || is_page('sites')){ ?>
          <div class="facet-container"><span>Sort by:</span><?= do_shortcode('[facetwp facet="sort_a_to_z"]'); ?></div>
        <?php
        } 
        else{ ?>
        <div class="facet-container"><span>Sort by:</span><?php echo do_shortcode('[facetwp facet="sort_"]'); ?></div>
        <?php
        }?>
        </div>
      <?php
      } ?>
    </div>
  </div>














