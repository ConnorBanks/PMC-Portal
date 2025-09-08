<?php

function webkul_add_woocommerce_support() {
    //Add WoocCommerce theme support to our theme
    add_theme_support( 'woocommerce' );
    // To enable gallery features add WooCommerce Product zoom effect, lightbox and slider support to our theme
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'webkul_add_woocommerce_support' );

// Prouct Page Hooks



add_action( 'woocommerce_before_single_product', 'breadcrumbs_on_product_page' );
add_action( 'woocommerce_share', 'delivery_and_shipping_info' );


function breadcrumbs_on_product_page() {
    echo '<div id="breadcrumbs">';
      if ( function_exists('bcn_display')) {
        if (function_exists ('bcn_display')) {   
          bcn_display (); 
        }
      }
      echo '</div>';
}

function delivery_and_shipping_info(){?>
    <div class="delivery-shipping-information">
        <div class="estimated-delivery">
            <?php 
            $product = wc_get_product($id); ?>
            <p><span class="label"><i class="fa-solid fa-truck"></i> </span><span id="delivery-date-output" class="delivery-date">Select options to see estimated delivery</span></p>
        </div>
        <div class="shipping-information">
           

            <p><i class="fa-solid fa-circle-info"></i> Shipping Information</p>
        </div>
        <div class="payment-methods">
            <p>Payment Options:
                <?php $pm = get_field('payment_methods', 'options'); 
                foreach($pm as $p){ ?>
                    <img src="<?= $p['url']; ?>" alt="<?= $p['alt']; ?>"/>
                <?php
                }?>
            </p>
        </div>
</div>
<?php
}

function get_delivery_date($product) {
    if ( ! $product ) {
        global $product;
    }

    if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
        return 'Invalid product.';
    }

    // Check for backorder status
    if ( $product->is_on_backorder( 1 ) ) {
        return 'This product is currently on backorder';
    }

    $shipping_class_id   = $product->get_shipping_class_id();
    $shipping_class_term = get_term($shipping_class_id, 'product_shipping_class');

    if( ! is_wp_error($shipping_class_term) && is_a($shipping_class_term, 'WP_Term') ) {
        preg_match_all('/\d+/', $shipping_class_term->name, $matches);
        $numbers = $matches[0];
        $days_to_add = $numbers[0] ?? null;
    } else {
        $days_to_add = get_field('standard_delivery_time_length', 'option');
    }

    if ( ! $days_to_add || ! is_numeric( $days_to_add ) ) {
        return 'Delivery time not available.';
    }

    $delivery_date = date_i18n( 'jS F Y', strtotime("+{$days_to_add} days") );

    return 'Estimated delivery: <span><strong>' . esc_html( $delivery_date ) . '</strong></span>';
}

//////////////////////////////////////////////////////////////////////
// NEW ADD & MINUS PRODUCT SELECTOR   ///
//////////////////////////////////////////////////////////////////////

add_action( 'woocommerce_after_add_to_cart_quantity', 'ts_quantity_plus_sign' );

function ts_quantity_plus_sign() {
   echo '<button type="button" class="plus" ><i class="fa-solid fa-plus"></i></button>';
}

add_action( 'woocommerce_before_add_to_cart_quantity', 'ts_quantity_minus_sign' );

function ts_quantity_minus_sign() {
   echo '<button type="button" class="minus" ><i class="fa-solid fa-minus"></i></button>';
}

add_action( 'wp_footer', 'ts_quantity_plus_minus' );

function ts_quantity_plus_minus() {
   // To run this on the single product page
   if ( ! is_product() ) return;
   ?>
   <script type="text/javascript">

      jQuery(document).ready(function($){

            $('form.cart').on( 'click', 'button.plus, button.minus', function() {

            // Get current quantity values
            var qty = $( this ).closest( 'form.cart' ).find( '.qty' );
            var val   = parseFloat(qty.val());
            var max = parseFloat(qty.attr( 'max' ));
            var min = parseFloat(qty.attr( 'min' ));
            var step = parseFloat(qty.attr( 'step' ));

            // Change the value if plus or minus
            if ( $( this ).is( '.plus' ) ) {
               if ( max && ( max <= val ) ) {
                  qty.val( max );
               }
            else {
               qty.val( val + step );
                 }
            }
            else {
               if ( min && ( min >= val ) ) {
                  qty.val( min );
               }
               else if ( val > 1 ) {
                  qty.val( val - step );
               }
            }

         });

      });

   </script>
   <?php
}

add_filter('woocommerce_get_price_html', 'show_price_with_and_without_tax', 20, 2);
function show_price_with_and_without_tax($price, $product) {
    if (!$product->is_taxable()) {
        return $price;
    }

    $price_excluding_tax = wc_get_price_excluding_tax($product);
    $price_including_tax = wc_get_price_including_tax($product);

    return '<span class="incl">' . wc_price($price_including_tax) . ' incl. tax</span><span class="excl">' . wc_price($price_excluding_tax) . '</span>';
}

add_action( 'woocommerce_after_single_product', 'single_product_content_blocks' );
function single_product_content_blocks(){
    get_template_part('templates/blocks/faqs');
    get_template_part('templates/blocks/page-links');
}

add_action( 'woocommerce_share', 'quote_or_dealer' );
function quote_or_dealer(){
    $product = get_product();

    $type = get_field('checkout_options');

    if($type == 'quote'){ ?>
    <div class="request-quote">
        <p class="btn btn-red" data-toggle="modal" data-target="#request-quote-modal" id="quote-request"><i class="fa-solid fa-envelope"></i><span>Request A Quote</span></p>
        <div class="contact-details">
            <p><span class="label">Phone: </span><a href="tel:<?= get_field('telephone_number', 'options'); ?>"><?= get_field('telephone_number', 'options'); ?></a></p>
            <p><span class="label">Email: </span><a href="mailto:<?= get_field('email_address', 'options'); ?>"><?= get_field('email_address', 'options'); ?></a></p>
        </div>
        <?php scs_share_buttons(array('facebook', 'twitter', 'linkedin', 'email')); ?>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="request-quote-modal" tabindex="-1" role="dialog" aria-labelledby="request-quote-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request A Quote</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Complete the form to receive stock information and pricing about your selected product from Safety Devices within 2 business days.</p>
                <?php $form = get_field('request_a_quote_form', 'options');
                if($form){
                    echo do_shortcode('[forminator_form id=" ' . $form->ID . ' "]');
                }?>
            </div>
            </div>
        </div>
    </div>
    <?php
    }
    else if($type == 'dealer'){ ?>
        <div class="find-dealer">
            <p class="message">Safety devices has an extensive list of trusted dealers across the globe, providing our products in your area.</p>
            <div class="dealer-selector">
                <select name="dealer" id="product-dealer">
                    <option value="">Select Your Location</option>
                    <option value="Mainland UK (Excl. NI, Scottish Highlands & Channel Islands)">Mainland UK (Excl. NI, Scottish Highlands & Channel Islands)</option>
                    <option value="Europe">Europe</option>
                    <option value="Rest of world">Rest of the World</option>
                </select>
                <p class="btn btn-red disabled find-dealer-btn" data-toggle="modal" data-target="#select-dealer-modal"><span><i class="fa-solid fa-location-dot"></i> Find A Dealer</span></p>
            </div>
            <?php scs_share_buttons(array('facebook', 'twitter', 'linkedin', 'email')); ?>
        </div>

        <!-- Modal -->
    <div class="modal fade" id="select-dealer-modal" tabindex="-1" role="dialog" aria-labelledby="select-dealer-modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select A Dealer</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="location-selector">
                        <h5>Selected Location</h5>
                        <select name="location" id="location">
                            <option value="">Inherit from previous</option>
                            <option value="Mainland UK (Excl. NI, Scottish Highlands & Channel Islands)">Mainland UK (Excl. NI, Scottish Highlands & Channel Islands)</option>
                            <option value="Europe">Europe</option>
                            <option value="Rest of world">Rest of the World</option>
                        </select>
                    </div>
                    <div class="dealer-selector">
                        <h5>Select A Dealer</h5>
                        <select name="dealer" id="dealer" class="generated-dealers">
                            <option value="">Please Select A Dealer</option>
                        </select>
                        <div class="dealer-information">
                            
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <p>Complete the form to receive stock information and pricing about your selected product from Safety Devices within 2 business days.</p>
                    <?php $form = get_field('dealer_contact_form', 'options');
                    if($form){
                        echo do_shortcode('[forminator_form id=" ' . $form->ID . ' "]');
                    }?>
                </div>
            </div>
        </div>
    </div>
    <?php
    }
}


function western_custom_buy_buttons(){

    $product = get_product();

    $type = get_field('checkout_options');

    if($type == 'quote'){
        
    add_filter( 'woocommerce_product_get_price', '__return_empty_string' );
    }
 
 }
 
 add_action( 'wp', 'western_custom_buy_buttons' );


add_filter( 'woocommerce_product_tabs', 'woo_custom_product_tabs' );
function woo_custom_product_tabs( $tabs ) {

    // 1) Removing tabs

    //unset( $tabs['description'] );              // Remove the description tab
    // unset( $tabs['reviews'] );               // Remove the reviews tab
    //unset( $tabs['additional_information'] );   // Remove the additional information tab


    // 2 Adding new tabs and set the right order

    //Attribute Description tab
    $tabs['attrib_desc_tab'] = array(
        'title'     => __( 'Resources', 'woocommerce' ),
        'priority'  => 100,
        'callback'  => 'woo_resources_tab_content'
    );

    return $tabs;

}

function woo_resources_tab_content() {
    
        global $product;

        // Get the downloadable files
        $files = get_field('resources', $product->get_id());
       
        echo '<h2>' . __( 'Resources', 'woocommerce' ) . '</h2>';
        if ( ! empty( $files ) ) {
            echo '<ul class="downloadable-files">';
            foreach ( $files as $file ) {
                set_query_var( 'resource_id', $file);
                get_template_part( 'templates/cards/resource-card' );
                //echo '<li><a href="' . esc_url( $file['file'] ) . '" target="_blank">' . esc_html( $file['name'] ) . '</a></li>';
            }
            echo '</ul>';
        } else {
            echo '<p>' . __( 'No resources available.', 'woocommerce' ) . '</p>';
            echo '<p>' . __( 'Looking for something specific? <a href="/contact">Contact Us</a>.', 'woocommerce' ) . '</p>';
        }
    }

add_action('wp_ajax_get_delivery_date', 'get_delivery_date_ajax');
add_action('wp_ajax_nopriv_get_delivery_date', 'get_delivery_date_ajax');

function get_delivery_date_ajax() {
    if ( ! isset($_POST['variation_id']) ) {
        wp_send_json_error('Missing variation ID.');
    }

    $variation_id = absint($_POST['variation_id']);
    $product = wc_get_product($variation_id);

    if ( ! $product ) {
        wp_send_json_error('Invalid product.');
    }

    $message = get_delivery_date($product);
    wp_send_json_success($message);
}
function enqueue_woocommerce_ajax_support() {
    wp_localize_script('jquery', 'woocommerce_params', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
}
add_action('wp_enqueue_scripts', 'enqueue_woocommerce_ajax_support');

?>