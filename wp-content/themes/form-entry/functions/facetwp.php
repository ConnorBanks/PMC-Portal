<?php

//add_filter( 'facetwp_template_use_archive', '__return_true' );

add_filter( 'facetwp_index_row', function( $params, $class ) {
    if ( 'post_date' == $params['facet_name'] ) { // change date_as_year to name of your facet
        $raw_value = $params['facet_value'];
        $params['facet_value'] = date( 'Y', strtotime( $raw_value ) );
        $params['facet_display_value'] = $params['facet_value'];
    }
    return $params;
}, 10, 2 );

?>
