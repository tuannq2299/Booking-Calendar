<?php

if ( ! defined( 'WPINC' ) ) { die; }

function cbsb_block_category( $categories, $context ) {
	return array_merge(
		$categories,
		array(
			array(
				'slug'  => 'startbooking',
				'title' => __( 'Start Booking', 'calendar-booking' ),
			),
		)
	);
}
add_filter( 'block_categories_all', 'cbsb_block_category', 10, 2 );

if (!defined( 'DOING_AJAX' ) || DOING_AJAX === false) {
	require_once( CBSB_BASE_DIR . 'blocks/default-appointment.php' );
	require_once( CBSB_BASE_DIR . 'blocks/single-service.php' );
	require_once( CBSB_BASE_DIR . 'blocks/call-to-action.php' );
	require_once( CBSB_BASE_DIR . 'blocks/default-classes.php' );
}
