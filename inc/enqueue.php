<?php
/*
Manage/Load CSS/JS
*/

function cbsb_admin_enqueue() {
	if ( isset( $_GET['page'] ) && $_GET['page'] === 'start-booking' ) {
		if ( WP_DEBUG === true ) {
			$v = md5( time() );
		} else {
			$v = CBSB_VERSION;
		}

		wp_enqueue_style( 'cbsb-admin-app', CBSB_BASE_URL . 'public/css/admin/app.css', array(), $v );
		wp_enqueue_script( 'cbsb-admin-react-index', CBSB_BASE_URL . 'public/js/admin/index.js', array( 'wp-i18n' ), $v, true );
		wp_enqueue_script( 'cbsb-admin-stripe-js', 'https://js.stripe.com/v3/', array(), $v, true );

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'cbsb-admin-react-index', 'calendar-booking', CBSB_BASE_DIR . 'languages/json' );
		}
	}
}
add_action( 'admin_enqueue_scripts', 'cbsb_admin_enqueue', 40 );

function cbsb_admin_data() {

	if ( isset( $_GET['page'] ) && $_GET['page'] == 'start-booking' ) {
		
		$settings = cbsb_current_settings();
		
		$rest_token = get_transient( 'cbsb_rest_token', false );

		$has_wpcom_subscription = cbsb_wpcom_has_active_subscription();

		if ( false === $rest_token ) {
			$rest_token = cbsb_generate_rest_token();
		}
	?>
		<script type="text/javascript">
			var startbooking = {};
			startbooking.app_url = '<?php echo CBSB_APP_URL; ?>';
			startbooking.api_url = '<?php echo CBSB_API_URL; ?>';
			startbooking.base_url = '<?php echo CBSB_BASE_URL; ?>';
			startbooking.rest_api = '<?php echo cbsb_get_rest_api(); ?>';
			startbooking.wp_url = '<?php echo home_url(); ?>';
			startbooking.wp_admin_url = '<?php echo admin_url(); ?>';
			startbooking.pricing = '';
			startbooking.token = '<?php echo $rest_token; ?>';
			startbooking.settings = <?php echo json_encode( cbsb_current_settings() ); ?>;
			startbooking.connected = <?php echo json_encode( array(
				'account' => ( cbsb_is_connected() ) ? true : false,
				'user'    => ( cbsb_is_user_connected() ) ? true : false
			) ); ?>;
			startbooking.current_wp_user = <?php echo json_encode( wp_get_current_user() ); ?>;
			startbooking.has_wpcom_subscription = <?php echo json_encode( ( $has_wpcom_subscription ) ? true : false); ?>;
			<?php

			global $wp_version;
			$connection = get_option( 'cbsb_connection' );
			$token = isset( $connection['wp-admin'] ) ? $connection['wp-admin'] : null;
			$account = isset( $connection['account'] ) ? $connection['account'] : null;
			$direct = array(
				'user-agent'  => 'WP:BK:Direct/' . $wp_version . ':' . CBSB_VERSION . ':StartBookingComAPI; ' . home_url(),
				'token'       => $token,
				'account'     => $account
			);

			{ ?>
				startbooking.direct = <?php echo json_encode( $direct ); ?>;
			<?php } ?>
			
		</script>
	<?php
	}
}
add_action( 'admin_head', 'cbsb_admin_data' );

function cbsb_fe_data() {
	global $wp_version;
	global $cbsb;
	$settings = cbsb_current_settings();
	if ( $settings['booking_window_start'] ) {
		$start_time = time() + $settings['booking_window_start'] + DAY_IN_SECONDS;
	} else {
		$start_time = time();
	}

	$initial_state =  new stdClass();
	$initial_state->step = 'services';
	$initial_state->default_cart = array();
	$initial_state->dateTime = $start_time;

	$initial_state->day = (int) date( 'j', $start_time );
	$initial_state->month = (int) date( 'n', $start_time );

	$initial_classes_state = new stdClass();
	$initial_classes_state->groupFilter = new stdClass();
	$initial_classes_state->cart = new stdClass();
	$initial_classes_state->customers = array();
	$initial_classes_state->step = ( $settings['default_class_view'] == 'list' ) ? 1 : 2;

	if ( is_user_logged_in() && current_user_can( 'administrator' ) ) {
		$isAdmin = 'true';
	} else {
		$isAdmin = null;
	}

	$all_services = $cbsb->get( 'services', null, 60 );

	if ( isset( $all_services['data'] ) && ! is_null( $all_services['data'] ) && property_exists( $all_services['data'], 'all_services' ) ) {
		$all_services = $all_services['data'];
		$initial_state->all_services = $all_services->all_services;
	}

	$wp_data = array(
		'baseUrl'      => admin_url( 'admin-ajax.php' ),
		'endPoints'    => array(
			'processAppointment'    => 'cbsb_proccess_appointment',
			'getServices'           => 'cbsb_get_services',
			'getServiceDates'       => 'cbsb_get_service_dates',
			'getAvailabilityByDate' => 'cbsb_get_availablility_by_date',
		),
		'initialState'        => $initial_state,
		'initialClassesState' => $initial_classes_state,
		'settings'     => $settings,
		'mixpanelKey'  => 'eb7a78544eed4e6a20e481834df14d18',
		'appointmentStepOrder' => array(
			50  => 'services',
			100 => 'provider',
			150 => 'time',
			200 => 'details',
			250 => 'confirmation'
		),
		'skipSteps'    => array(),
		'isAdmin'      => $isAdmin
	);

	if ( $settings['automatic_provider'] == 'true' ) {
		$wp_data['skipSteps'][] = 'provider';
	}

	$wp_data = apply_filters( 'cbsb_react_fe', $wp_data );

	$wp_data['appointmentStepOrder'] = array_values( $wp_data['appointmentStepOrder'] );
	$wp_data['skipSteps'] = array_unique( $wp_data['skipSteps'] );
	$wp_data['progressSteps'] = array_values( array_diff( $wp_data['appointmentStepOrder'], $wp_data['skipSteps'] ) );
	return $wp_data;
}

function cbsb_fe_script() {
	global $cbsb;

	wp_register_script( 'cbsb-react-js', CBSB_BASE_URL . 'public/js/legacy.js' );

	$wp_data = cbsb_fe_data();

	wp_localize_script( 'cbsb-react-js', 'cbsbData', $wp_data );

	$startbooking_js_global = cbsb_get_startbooking_global();

	wp_localize_script( 'cbsb-react-js', 'startbooking', $startbooking_js_global );

	$details = $cbsb->get( 'account/details' );

	if ( 'success' == $details['status'] ) {
		$details = $details['data'];
	}

	if ( ! is_null( $details ) && property_exists( $details, 'payments' ) && property_exists( $details->payments, 'payment_key' ) ) {
		$allow_payment = true;
	} else {
		$allow_payment = false;
	}

	if ( $allow_payment ) {
		wp_enqueue_script( 'cbsb-stripe-v3', 'https://js.stripe.com/v3/' );
	}
	wp_enqueue_script( 'cbsb-react-js' );
}

function cbsb_fe_classes_styles() {
	wp_register_style(
		'cbsb-default-classes-flow-style',
		CBSB_BASE_URL . 'public/css/flows/default.css',
		array(),
		CBSB_VERSION
	);
	wp_enqueue_style( 'cbsb-default-classes-flow-style' );
}

function cbsb_fe_classes_script() {
	wp_register_script(
		'cbsb-stripe-v3',
		'https://js.stripe.com/v3/',
		array(),
		CBSB_VERSION
	);

	wp_register_script(
		'cbsb-default-classes-flow',
		CBSB_BASE_URL . 'public/js/flows/default-classes.js',
		array( 'wp-blocks', 'wp-i18n', 'wp-element' ),
		CBSB_VERSION,
		true
	);

	global $cbsb;
	$account = $cbsb->get( 'account/details' );

	if ( 'success' == $account['status'] &&
		isset( $account['data'] ) &&
		! is_null( $account['data'] ) &&
		property_exists( $account['data'], 'payments' ) &&
		property_exists( $account['data']->payments, 'payment_key' ) &&
		is_null( $account['data']->payments->payment_key ) == false ) {
			wp_deregister_script( 'cbsb-default-classes-flow' );
			wp_register_script(
				'cbsb-default-classes-flow',
				CBSB_BASE_URL . 'public/js/flows/default-classes.js',
				array( 'wp-blocks', 'wp-i18n', 'wp-element', 'cbsb-stripe-v3' ),
				CBSB_VERSION,
				true
			);
	}

	$startbooking_js_global = cbsb_get_startbooking_global();
	wp_add_inline_script( 'cbsb-default-classes-flow-editor', 'var startbooking = ' . json_encode( $startbooking_js_global ), 'before' );
	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations( 'cbsb-default-classes-flow-editor', 'calendar-booking', CBSB_BASE_DIR . 'languages/json' );
		wp_set_script_translations( 'cbsb-default-classes-flow', 'calendar-booking', CBSB_BASE_DIR . 'languages/json' );
	}
	wp_localize_script( 'cbsb-default-classes-flow', 'startbooking', $startbooking_js_global );
	wp_enqueue_script( 'cbsb-default-classes-flow' );
}

function cbsb_load_single_service_block_scripts_styles() {

	wp_register_style(
		'cbsb-single-service-flow-style',
		CBSB_BASE_URL . 'public/css/flows/default.css',
		array(),
		CBSB_VERSION
	);

	wp_register_script(
		'cbsb-stripe-v3',
		'https://js.stripe.com/v3/',
		array(),
		CBSB_VERSION
	);

	wp_register_script(
		'cbsb-single-service-flow',
		CBSB_BASE_URL . 'public/js/flows/single-service.js',
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'cbsb-stripe-v3' ),
		CBSB_VERSION,
		true
	);

	wp_enqueue_script( 'cbsb-single-service-flow' );
	wp_enqueue_style( 'cbsb-single-service-flow-style' );
	add_filter( 'print_late_styles', '__return_true', 30 );

	$startbooking_js_global = cbsb_get_startbooking_global();
	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations( 'cbsb-single-service-flow', 'calendar-booking', CBSB_BASE_DIR . 'languages/json' );
	}
	wp_localize_script( 'cbsb-single-service-flow', 'startbooking', $startbooking_js_global );
}

function cbsb_load_service_block_scripts_styles() {
	wp_register_style(
		'cbsb-default-booking-flow-style',
		CBSB_BASE_URL . 'public/css/flows/default.css',
		array(),
		CBSB_VERSION
	);

	wp_register_script(
		'cbsb-stripe-v3',
		'https://js.stripe.com/v3/',
		array(),
		CBSB_VERSION
	);

	wp_register_script(
		'cbsb-default-booking-flow',
		CBSB_BASE_URL . 'public/js/flows/default-appointment.js',
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'cbsb-stripe-v3' ),
		CBSB_VERSION,
		true
	);

	wp_enqueue_script( 'cbsb-default-booking-flow' );
	wp_enqueue_style( 'cbsb-default-booking-flow-style' );
	add_filter( 'print_late_styles', '__return_true', 30 );

	$startbooking_js_global = cbsb_get_startbooking_global();
	if ( function_exists( 'wp_set_script_translations' ) ) {
		wp_set_script_translations( 'cbsb-default-booking-flow-editor', 'calendar-booking', CBSB_BASE_DIR . 'languages/json' );
		wp_set_script_translations( 'cbsb-default-booking-flow', 'calendar-booking', CBSB_BASE_DIR . 'languages/json' );
	}
	wp_localize_script( 'cbsb-default-booking-flow', 'startbooking', $startbooking_js_global );
}

function cbsb_fe_styles() {
	wp_enqueue_style( 'startbooking-flow', CBSB_BASE_URL . 'public/css/flows/legacy-booking-flow-layout.css' );
	$settings = cbsb_current_settings();
	$background_brightness = cbsb_get_brightness( $settings['btn_bg_color'] );
	if ( $background_brightness < 130 ) {
		$default_text = '#ffffff';
	} else {
		$default_text = '#5e5e5e';
	}
	echo '<style type="text/css">
		#startbooking-flow .DayPicker-Day--today{
			color: #' . $settings['btn_bg_color'] . ';
			background-color:#fcf8e3;
		}
		#startbooking-flow .DayPicker-Day--selected{
			color: ' . $default_text . ';
		}
		$startbooking-flow .DayPicker-Day--disabled {
			background-color: rgba(128,128,128,0.5);
			opacity: 0.5;
		}
		#startbooking-flow .DayPicker-Day--selected,
		#startbooking-flow .sb-button-wrap .sb-primary-action button,
		#startbooking-flow button.sb-styled-button,
		.sb-primary-action button {
			background-color: #' . $settings['btn_bg_color'] . ';
			color: #' . $settings['btn_txt_color'] . ';
			border: 0px;
		}
		#startbooking-flow .sb-button-wrap .sb-primary-action button:hover,
		#startbooking-flow button.sb-styled-button:hover,
		.sb-primary-action button:hover{
			opacity: .75;
		}
		#startbooking-flow .rc-steps-item-finish .rc-steps-item-icon {
			border-color: #' . $settings['btn_bg_color'] . ';
		}
		#startbooking-flow .rc-steps-item-finish .rc-steps-item-icon>.rc-steps-icon {
			color: #' . $settings['btn_bg_color'] . ';
		}
		#startbooking-flow .rc-steps-item-finish .rc-steps-item-icon>.rc-steps-icon .rc-steps-icon-dot {
			background: #' . $settings['btn_bg_color'] . ';
		}
		#startbooking-flow .rc-steps-item-finish .rc-steps-item-title:after,
		#startbooking-flow .rc-steps-item-process .rc-steps-item-icon > .rc-steps-icon .rc-steps-icon-dot,
		#startbooking-flow .rc-steps-item-finish .rc-steps-item-tail:after {
			background-color: #' . $settings['btn_bg_color'] . ';
		}
	</style>' ."\r\n";
}
