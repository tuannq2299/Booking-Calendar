<?php 

function cbsb_legacy_service_flow() {
	add_action( 'wp_footer', 'cbsb_fe_styles' );
	add_action( 'wp_footer', 'cbsb_fe_script' );
	$markup = '
	<div id="startbooking-flow">
		<div id="startbooking-appointment-flow">
			' . cbsb_get_application_loader() . '
		</div>
	</div>';
	return $markup;
}

function cbsb_legacy_classes_flow() {
	add_action( 'wp_footer', 'cbsb_fe_styles' );
	add_action( 'wp_footer', 'cbsb_fe_script' );
	$markup = '
	<div id="startbooking-flow">
		<div id="startbooking-class-flow">
			' . cbsb_get_application_loader() . '
		</div>
	</div>';
	return $markup;
}

function cbsb_classes_block_to_shortcode( $atts ) {
	add_action( 'wp_footer', 'cbsb_fe_classes_styles' );
	add_action( 'wp_footer', 'cbsb_fe_classes_script' );
	if ( is_null( $atts['class'] ) ) {
		$markup = '<div id="startbooking-classes"
			data-provider="' . $atts['provider'] . '"
			data-range_start="' . $atts['range_start'] . '"
			data-range_end="' . $atts['range_end'] . '"
			data-hide_calendar="' . $atts['hide_calendar'] . '"
		></div>';
	} else {
		$markup = '<div id="startbooking-classes" 
			data-group="' . $atts['class'] . '" 
			data-show_filter="' . $atts['show_filter'] . '"
			data-provider="' . $atts['provider'] . '"
			data-range_start="' . $atts['range_start'] . '"
			data-range_end="' . $atts['range_end'] . '"
			data-hide_calendar="' . $atts['hide_calendar'] . '"
		></div>';
	}
	return $markup;
}

function cbsb_service_block_to_shortcode() {
	cbsb_load_service_block_scripts_styles();
	$markup = '
		<div class="wp-block-calendar-booking-default-booking-flow">
			<div id="startbooking-block-default">' . cbsb_get_application_loader() . '</div>
		</div>
	';
	return $markup;
}

function cbsb_single_service_block_to_shortcode( $atts ) {
	if ( is_array( $atts ) ) {
		$atts = array_map( 'trim', $atts );
	}

	cbsb_load_single_service_block_scripts_styles();

	if ( ! isset( $atts['service'] ) ) {
		return '<div><p style="color: red; font-weight:bold;">Service is a required parameter with single service shortcode.</p></div>';
	}

	if ( ! isset( $atts['details'] ) || ! in_array( $atts['details'], array( 'true', 'false' ) ) ) {
		$atts['details'] = 'true';
	}

	if ( $atts['details'] === 'true' ) {
		global $cbsb;
		$startbooking = cbsb_get_startbooking_global();
		$editors = $cbsb->get( 'editors', array(), 300 );
		$branding = $cbsb->get( 'account/'. $startbooking['default_store']->account->url_string . '/branding', array(), 300 );
		$service = $cbsb->get( 'services/' . $atts['service'], array(), 300 );

		if ( 'success' === $editors['status'] && ! is_null( $editors['data'] ) && property_exists( $editors['data'], 'data' ) ) {
			$editors = $editors['data']->data;
		} else {
			return '<div id="startbooking-block-single-service" class="wp-block-calendar-booking-single-service-flow startbooking-block-single-service"
				data-block-service="' . trim( $atts['service'] ) . '"
				data-block-display-service="' . trim( $atts['details'] ) . '"
			></div>';
		}

		if ( 'success' === $service['status'] && ! is_null( $service['data'] ) && property_exists( $service['data'], 'data' ) ) {
			$service = $service['data']->data;
		} else {
			return '<div id="startbooking-block-single-service" class="wp-block-calendar-booking-single-service-flow startbooking-block-single-service"
				data-block-service="' . trim( $atts['service'] ) . '"
				data-block-display-service="' . trim( $atts['details'] ) . '"
			></div>';
		}

		$markup = '<div class="wp-block-calendar-booking-single-service-flow startbooking-block-single-service"><div class="items-list"><div class="item">';

		if ( count( $service->media ) > 0) {
			$markup .= '<div style="background-image: url(' . $service->media[0]->url . ')" class="sb-thumbnail" /></div>';
		}

		$markup .= '<div class="sb-content"><div class="item-head">';

		if ( ! $editors->services->hide_price ) {
			$markup .= '<strong style="color:' . $editors->services->price_color . '" class="price">' . $service->readable_price . '</strong>';
		}

		$markup .= '<h2>'. $service->name . '</h2></div>';

		if ( ! $editors->services->hide_description ) {
			$markup .= '<p>' . $service->description . '</p>';
		}

		$markup .= '<div class="item-footer">';
		if ( ! $editors->services->hide_duration ) {
			$markup .= '<div class="time">' . cbsb_format_minutes( $service->duration ) . '</div>';
		} else {
			$markup .= '<div class="time"></div>';
		}

		if ( 'success' === $branding['status'] && ! is_null( $branding['data'] ) && property_exists( $branding['data'], 'data' ) ) {
			$branding = $branding['data']->data;
			$button_style = "
				color: $branding->primary_btn_text_color;
				background: $branding->primary_color none repeat scroll 0% 0%;
				box-shadow: $branding->primary_color 0px 0px 0px 2px inset;
				border-radius: 4px;
				font-size: 14px;
				font-weight: bold;
				line-height: 1.2;
				display: inline-block;
				vertical-align:top;
				text-align: center;
				padding: 10px 13px;
				cursor: pointer;
				text-decoration: none;
			";
		} else {
			$button_style = "
				color: rgb(250, 250, 250);
				background: rgb(65, 117, 5) none repeat scroll 0% 0%;
				box-shadow: rgb(65, 117, 5) 0px 0px 0px 2px inset;
				border-radius: 4px;
				font-size: 14px;
				font-weight: bold;
				line-height: 1.2;
				display: inline-block;
				vertical-align:top;
				text-align: center;
				padding: 10px 13px;
				cursor: pointer;
				text-decoration: none;
			";
		}
		$button_style = trim( str_replace( array("\r", "\n"), '', $button_style ) );

		$markup .= '<a dusk="select-service" style="' . $button_style . '" class="button" href="?cbsb_force=true&service=' . $atts['service'].'" >' . __( 'Continue', 'calendar-booking' ) . '</a>';
		$markup .= '</div></div></div></div></div>';
	} else {
		$markup = '<div id="startbooking-block-single-service" class="wp-block-calendar-booking-single-service-flow startbooking-block-single-service"
				data-block-service="' . trim( $atts['service'] ) . '"
				data-block-display-service="' . trim( $atts['details'] ) . '"
			></div>';
	}
	return $markup;
}

function cbsb_sc_flow( $atts ) {
	if ( is_array( $atts ) ) {
		$atts = array_map( 'trim', $atts );
	}
	$atts = wp_parse_args( $atts, array( 
		'flow' => null, 
		'service' => null, 
		'class' => null, 
		'show_filter' => null,
		'provider' => null,
		'range_start' => null,
		'range_end' => null,
		'hide_calendar' => null
	) );

	switch ( $atts['flow'] ) {
		case 'services':
			$markup = cbsb_service_block_to_shortcode();
			break;
		case 'classes':
			$markup = cbsb_classes_block_to_shortcode( $atts );
			break;
		case 'class':
			$markup = cbsb_classes_block_to_shortcode( $atts );
			break;
		case 'legacy-classes':
			$markup = cbsb_classes_block_to_shortcode( $atts );
			break;
		case 'single-service':
			$markup = cbsb_single_service_block_to_shortcode( $atts );
			break;
		case 'legacy':
			$markup = cbsb_legacy_service_flow();
			break;
		default:
			$markup = cbsb_service_block_to_shortcode();
			break;
	}
	return $markup;
}
add_shortcode( 'startbooking', 'cbsb_sc_flow' );

function cbsb_sc_book_items( $atts, $content = 'Book Now' ) {
	if ( is_array( $atts ) ) {
		$atts = array_map( 'trim', $atts );
	}
	$booking_page_id = get_option( 'cbsb_booking_page' );

	if ( false === get_option( 'cbsb_connection' ) ) {
		return '<p>Unable to display quick book link because StartBooking is not connected.';
	}

	if ( isset( $_GET['in_page_book'] ) && $_GET['in_page_book'] ) {
		return cbsb_sc_flow();
	}

	$default_atts = array(
		'services' => array(),
	);
	if ( isset( $atts['services'] ) ) {
		$atts['services'] = explode( ',', $atts['services'] );
	}

	$atts = wp_parse_args( $atts, $default_atts );

	$args = array(
		'cbsb_force' => true,
		'add_service' => $atts['services']
	);

	if ( is_numeric( $booking_page_id ) ) {
		$booking_url = get_permalink( $booking_page_id );
	} else {
		$booking_url = get_permalink( get_the_ID() );
	}
	$booking_url = add_query_arg( $args, $booking_url );
	$href = $booking_url . '#appointment-page';
	return "<a href='" . $href . "'>" . $content . "</a>";
}
add_shortcode( 'startbooking_cta', 'cbsb_sc_book_items' );

function cbsb_sc_class_flow( $atts = array(), $content = null ) {
	add_action( 'wp_footer', 'cbsb_fe_styles' );
	add_action( 'wp_footer', 'cbsb_fe_script' );
	$markup = '
	<div id="startbooking-flow">
		<div id="startbooking-class-flow">
			' . cbsb_get_application_loader() . '
		</div>
	</div>';
	return $markup;
}
add_shortcode( 'startbooking_classes', 'cbsb_sc_class_flow' );