<?php
////////////////////////////////////////////////////////////////////////////////////
// Flamingo Permissions for different users
remove_filter( 'map_meta_cap', 'flamingo_map_meta_cap' );

add_filter( 'map_meta_cap', 'mycustom_flamingo_map_meta_cap', 9, 4 );

function mycustom_flamingo_map_meta_cap( $caps, $cap, $user_id, $args ) {
    $meta_caps = array(

		'flamingo_edit_contact' => 'edit_posts',
		'flamingo_edit_contacts' => 'edit_posts',
		'flamingo_delete_contact' => 'edit_posts',
		'flamingo_edit_inbound_message' => 'publish_posts',
		'flamingo_edit_inbound_messages' => 'publish_posts',
		'flamingo_delete_inbound_message' => 'publish_posts',
		'flamingo_delete_inbound_messages' => 'publish_posts',
		'flamingo_spam_inbound_message' => 'publish_posts',
		'flamingo_unspam_inbound_message' => 'publish_posts',
		'flamingo_edit_outbound_message' => 'publish_posts',
		'flamingo_edit_outbound_messages' => 'publish_posts',
		'flamingo_delete_outbound_message' => 'publish_posts',
	);

    $caps = array_diff( $caps, array_keys( $meta_caps ) );

    if ( isset( $meta_caps[$cap] ) )
        $caps[] = $meta_caps[$cap];

    return $caps;
	}
?>
