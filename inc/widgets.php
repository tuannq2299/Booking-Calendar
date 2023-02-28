<?php

require_once( CBSB_BASE_DIR . 'inc/widgets/address.php' );
require_once( CBSB_BASE_DIR . 'inc/widgets/classes.php' );
require_once( CBSB_BASE_DIR . 'inc/widgets/hours.php' );
require_once( CBSB_BASE_DIR . 'inc/widgets/services.php' );
require_once( CBSB_BASE_DIR . 'inc/widgets/single-service.php' );

function sb_register_widgets() {
	register_widget( 'StartBooking_Hours_Widget' );
	register_widget( 'StartBooking_Address_Widget' );
	register_widget( 'StartBooking_Group_Booking' );
	register_widget( 'StartBooking_Services' );
	register_widget( 'StartBooking_Single_Service' );
}

add_action( 'widgets_init', 'sb_register_widgets' );

