<?php

function cbsb_new_free_trial() {

	$user = wp_get_current_user();
	$hash = md5( $user->data->user_email . '_' . get_site_url() . '_' . time() );

	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) { 
		$ip = $_SERVER['HTTP_CLIENT_IP']; 
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR']; 
	} else { 
		$ip = $_SERVER['REMOTE_ADDR']; 
	}

	$body = array_filter( array(
		'source'         => 'plugin',
		'channel_source' => 'wp',
		'login_hash'     => $hash,
		'website'        => get_site_url(),
		'timezone'       => sanitize_post( $_POST['timezone'] ),
		'ip'             => $ip
	) );

	$http_response = wp_remote_post( CBSB_APP_URL . 'api/v1/register', array( 'timeout' => 20, 'body' => $body ) );
	$http_body = wp_remote_retrieve_body( $http_response );
	$json = json_decode( $http_body );

	if ( ! is_null( $json ) && property_exists( $json, 'error' ) ) {

		$response = array( 'status' => 'error', 'message' => $json->error, 'reload' => false );

	} else {

		if ( isset( $json->data ) ) {

			update_option( 'cbsb_connection', (array) $json->data->tokens );

			update_option( 'cbsb_user_token_migrated', true );

			update_user_meta( $user->data->ID, 'cbsb_connection', $json->data->tokens->{'wp-admin'} );

			// If they don't have a booking page, create one
			if ( false == get_option( 'cbsb_booking_page' ) && !cbsb_has_book_now_page()) {
				cbsb_create_booking_page( 'Book Now' );
			}

			update_option( 'cbsb_login_hash', $hash );
			
			update_option( 'cbsb_trial_ends_at', time() + (DAY_IN_SECONDS * 14) );

			$response = array( 'status' => 'success', 'message' => __('Processing', 'calendar-booking') . '...', 'reload' => false );

		} else {

			$response = array( 'status' => 'error', 'message' => __('Invalid response from startbooking.com.', 'calendar-booking'), 'reload' => false );
		}
	}

	wp_send_json( $response );
}
add_action( 'wp_ajax_cbsb_new_free_trial', 'cbsb_new_free_trial' );

function cbsb_check_free_trial() {
	$data = cbsb_api_request( 'register_check', $params = array(), $method = 'GET', $duration = 0 );

	if ( $data->ready ) {
		$response = array( 'status' => $data->message, 'reload' => true );
	} else {
		$response = array( 'status' => $data->message, 'reload' => false );
	}

	wp_send_json( $response );
}
add_action( 'wp_ajax_cbsb_check_free_trial', 'cbsb_check_free_trial' );

function cbsb_app_connect_user() {
	global $wp_version;

	$email = sanitize_email( $_POST['email'] );
	$password = sanitize_post( $_POST['password'] );
	$account_id = ( isset( $_POST['account_id'] ) ) ? sanitize_text_field( $_POST['account_id'] ) : false;

	$user = wp_get_current_user();
	$hash = md5( $user->data->user_email . '_' . get_site_url() . '_' . time() );

	$body = array_filter( array(
		'email'       => $email,
		'password'    => $password,
		'website'     => get_site_url(),
		'account_id'  => $account_id,
		'scopes'      => ['wp-admin']
	) );

	$args = array(
		'user-agent'  => 'WP:BK:API/' . $wp_version . ':' . CBSB_VERSION . ':CBSB_Api; ' . home_url(),
		'blocking'    => true,
		'headers'     => array(
			'Accept'        => 'application/json',
			'Content-Type'  => 'application/json',
			'X-Requested-With' => 'XMLHttpRequest'
		),
		'timeout'     => 20,
		'body'        => json_encode( $body )
	);

	$http_response = wp_remote_post( CBSB_APP_URL . 'api/v1/initialize', $args );
	$http_body = wp_remote_retrieve_body( $http_response );
	$json = json_decode( $http_body );

	if ( property_exists( $json, 'error' ) && __( 'Invalid Credentials', 'calendar-booking' ) == $json->error ) {
		$json->errors = array( 'password' => __( 'Invalid Password', 'calendar-booking' ) );
	}

	if ( property_exists( $json, 'errors' ) ) {

		$response = array(
			'status'  => 'error',
			'message' => __( 'Invalid Authentication.', 'calendar-booking' ),
			'reload'  => false,
			'code'    => 422,
			'errors'  => $json->errors
		);

	} else {

		if ( isset( $json->tokens ) ) {

			update_user_meta( $user->data->ID, 'cbsb_connection', $json->tokens->{'wp-admin'} );

			$response = array( 'status' => 'success', 'message' => __('Connection Established.', 'calendar-booking'), 'reload' => true );

		} else {

			$response = array( 'status' => 'error', 'message' => __('Invalid credentials. Contact Support at startbooking.com', 'calendar-booking'), 'reload' => false );
		}
	}

	wp_send_json( $response );
}
add_action( 'wp_ajax_cbsb_app_connect_user', 'cbsb_app_connect_user' );

function cbsb_app_connect_account() {

	global $wp_version;

	$email = sanitize_email( $_POST['email'] );
	$password = sanitize_post( $_POST['password'] );
	$account_id = ( isset( $_POST['account_id'] ) ) ? sanitize_text_field( $_POST['account_id'] ) : false;

	$user = wp_get_current_user();
	$hash = md5( $user->data->user_email . '_' . get_site_url() . '_' . time() );

	$body = array_filter( array(
		'email'       => $email,
		'password'    => $password,
		'website'     => get_site_url(),
		'account_id'  => $account_id,
		'hash'        => $hash,
		'scopes'      => ['booking-flow', 'wp-admin']
	) );

	$args = array(
		'user-agent'  => 'WP:BK:API/' . $wp_version . ':' . CBSB_VERSION . ':CBSB_Api; ' . home_url(),
		'blocking'    => true,
		'headers'     => array(
			'Accept'        => 'application/json',
			'Content-Type'  => 'application/json',
			'X-Requested-With' => 'XMLHttpRequest'
		),
		'timeout'     => 20,
		'body' => json_encode( $body )
	);

	$http_response = wp_remote_post( CBSB_APP_URL . 'api/v1/initialize', $args );
	$http_body = wp_remote_retrieve_body( $http_response );
	$json = json_decode( $http_body );

	if ( property_exists( $json, 'error' ) && __('Invalid Credentials', 'calendar-booking') == $json->error ) {
		$json->errors = array( 'password' => __('Invalid Password', 'calendar-booking') );
	}

	if ( property_exists( $json, 'errors' ) ) {

		$response = array(
			'status' => 'error',
			'message' => __( 'Invalid Authentication.', 'calendar-booking' ),
			'reload' => false,
			'code' => 422,
			'errors' => $json->errors
		);

	} else {

		if ( isset( $json->tokens ) ) {

			update_option( 'cbsb_connection', (array) $json->tokens );

			update_option( 'cbsb_user_token_migrated', true );

			update_user_meta( $user->data->ID, 'cbsb_connection', $json->tokens->{'wp-admin'} );

			// If they don't have a booking page, create one
			if ( false == get_option( 'cbsb_booking_page' ) && !cbsb_has_book_now_page()) {
				cbsb_create_booking_page( 'Book Now' );
			}

			update_option( 'cbsb_login_hash', $hash );

			$response = array( 'status' => 'success', 'message' => __('Connection Established.', 'calendar-booking'), 'reload' => true );

		} else {

			$response = array( 'status' => 'error', 'message' => __('Invalid credentials. Contact Support at startbooking.com', 'calendar-booking'), 'reload' => false );
		}
	}

	wp_send_json( $response );
}
add_action( 'wp_ajax_cbsb_app_connect_account', 'cbsb_app_connect_account' );

function cbsb_access_account() {
	$user = wp_get_current_user();

	$hash = get_option( 'cbsb_login_hash' );

	$body = array_filter( array(
		'website' => get_site_url(),
		'hash'    => $hash,
		'scopes'  => ['booking-flow', 'wp-admin']
	) );

	$http_response = wp_remote_post( CBSB_APP_URL . 'api/v1/initialize_access', array( 'timeout' => 20, 'body' => $body ) );
	$http_body = wp_remote_retrieve_body( $http_response );
	$json = json_decode( $http_body );

	if ( property_exists( $json, 'error' ) ) {

		$response = array( 'status' => 'error', 'message' => $json->error, 'reload' => false );

	} else {

		if ( isset( $json->tokens ) ) {

			update_option( 'cbsb_connection', (array) $json->tokens );

			update_option( 'cbsb_user_token_migrated', true );

			update_user_meta( $user->data->ID, 'cbsb_connection', $json->tokens->{'wp-admin'} );

			$response = array( 'status' => 'success', 'message' => __('Connection Established.', 'calendar-booking'), 'reload' => true );

		} else {

			$response = array( 'status' => 'error', 'message' => __('Invalid response from startbooking.com.', 'calendar-booking'), 'reload' => false );
		}
	}

	wp_send_json( $response );
}
add_action( 'wp_ajax_cbsb_access_account', 'cbsb_access_account' );

function cbsb_get_booking_pages() {
	global $wpdb;
	$query = "SELECT ID, post_title, post_status, post_name FROM " . $wpdb->posts . " WHERE (post_content LIKE '%[startbooking%' AND post_status = 'publish') OR (post_content LIKE '%wp:calendar-booking/%' AND post_status = 'publish')";
	$query_results = $wpdb->get_results($query);

	$query_results_as_array = array();
	foreach( $query_results as $qr ) {		
		$post = array();
		$post['id'] = (int) $qr->ID;
		$post['title'] = $qr->post_title;
		$post['slug'] = get_permalink( $qr->ID );
		
		if ( $post['slug'] === 'book-now' ) {
			$query_results_as_array = array_merge( [ $post ], $query_results_as_array );
		} else {
			$query_results_as_array[] = $post;
		}
	}

	if ( count( $query_results_as_array ) === 0 ) {
		$all_pages = get_pages( array( 'number' => 1 ) );
		$p = array();
		$p['id'] = (int) $all_pages[0]->ID;
		$p['title'] = 'Book Now';
		$permalink = get_permalink( $qr->ID );
		$p['slug'] = $permalink . '?cbsb_force=true';
		$query_results_as_array[] = $p;
	}

	$response = array( 
		'status' => 'success', 
		'pages' => $query_results_as_array
	);

	wp_send_json( $response );
}
add_action( 'wp_ajax_cbsb_get_booking_pages', 'cbsb_get_booking_pages' );

function cbsb_has_book_now_page() {
     $page = get_page_by_path( 'book-now' , OBJECT );
     if ( isset($page) )
        return true;
     else
        return false;
}

function cbsb_connection_clear_all() {
	global $cbsb;
	delete_option( 'cbsb_connection' );
	delete_option( 'cbsb_service_type_map' );
	delete_option( 'widget_startbooking_hours_widget' );
	delete_option( 'widget_startbooking_address_widget' );
	delete_option( 'cbsb_connect_step' );
	delete_option( 'cbsb_onboard' );
	delete_option( 'cbsb_plan' );
	delete_option( 'cbsb_overview_step' );
	delete_option( 'cbsb_service_map' );
	delete_metadata(
		'user',
		0,
		'cbsb_connection',
		'',
		true
	);
	if (!is_null($cbsb)) {
		$cbsb->clear_transients();
	}
}

function cbsb_disconnect_settings() {
	global $cbsb;
	if ( isset( $_GET['startbooking-disconnect'] ) &&  $_GET['startbooking-disconnect'] ) {
		delete_option( 'cbsb_connection' );
		delete_option( 'cbsb_service_type_map' );
		delete_option( 'widget_startbooking_hours_widget' );
		delete_option( 'widget_startbooking_address_widget' );
		delete_option( 'cbsb_connect_step' );
		delete_option( 'cbsb_onboard' );
		delete_option( 'cbsb_plan' );
		delete_option( 'cbsb_overview_step' );
		delete_option( 'cbsb_service_map' );
		delete_metadata(
			'user',
			0,
			'cbsb_connection',
			'',
			true
		);
		if (!is_null($cbsb)) {
			$cbsb->clear_transients();
		}
	}

	if ( isset( $_GET['startbooking-disconnect-user'] ) &&  $_GET['startbooking-disconnect-user'] ) {
		$user = wp_get_current_user();
		delete_user_meta( $user->data->ID, 'cbsb_connection' );
		if (!is_null($cbsb)) {
			$cbsb->clear_transients();
		}
	}
}
add_action( 'admin_init', 'cbsb_disconnect_settings' );

function cbsb_migrate_token() {
	$hash = md5( $user->data->user_email . '_' . get_site_url() . '_' . time() );
	update_option( 'cbsb_login_hash', $hash );
	$data = (array) cbsb_api_request( 'migrations/wordpress/token-style', array( 'login_hash' => $hash ), 'GET', 0 );
	if ( is_array( $data ) && isset( $data['wp-admin'] ) && isset( $data['booking-flow'] ) ) {
		update_option( 'cbsb_connection', $data );
	}
}
add_action( 'cbsb_migrate_token_hook', 'cbsb_migrate_token', 10 );

function cbsb_token_check( $connection ) {
	if ( is_array( $connection ) && isset( $connection['token'] ) ) {
		$connection['wp-admin'] = $connection['token'];
		$connection['booking-flow'] = $connection['token'];
		$next = (int) wp_next_scheduled( 'cbsb_migrate_token_hook' );
		if ( $next < time() - 60 ) {
			wp_schedule_single_event( time(), 'cbsb_migrate_token_hook' );
		}
	}
	return $connection;
}
add_filter( 'option_cbsb_connection', 'cbsb_token_check' );

function cbsb_migrate_user_token( $connection ) {
	$users = get_users( array( 'role' => 'administrator' ) );
	global $wp_version;

	if ( count( $users ) === 1 ) {
		$token = isset( $connection['wp-admin'] ) ? $connection['wp-admin'] : null;
		if ( ! is_null( $token ) ) {
			update_user_meta( $users[0]->ID, 'cbsb_connection', $token );
		}
	} else {
		$token = isset( $connection['wp-admin'] ) ? $connection['wp-admin'] : null;
		if ( ! is_null( $token ) ) {
			$admins = array();
			$user_map = array();
			foreach( $users as $user ) {
				$user_map[ md5( $user->data->user_email ) ] = $user->data->ID;
				$admins[] = md5( $user->data->user_email );
			}

			$args = array(
				'user-agent'  => 'WP:BK:API/' . $wp_version . ':' . CBSB_VERSION . ':WPRemoteMigration; ' . home_url(),
				'blocking'    => true,
				'headers'     => array(
					'Accept'              => 'application/json',
					'Authorization'       => 'Bearer ' . $token,
					'Content-Type'        => 'application/json',
					'X-Requested-With'    => 'XMLHttpRequest',
					'X-WP-Version'        => $wp_version,
					'X-WP-Plugin-Version' => CBSB_VERSION,
					'X-Token-Scope'       => 'wp-admin'
				),
				'timeout'     => 30,
				'body'        => json_encode( array( 'admins' => $admins ) ),
				'method'      => 'POST'
			);

			$response = wp_remote_request( CBSB_APP_URL . 'api/v1/migrations/wordpress/user-tokens', $args );
			$user_tokens = (array) json_decode( wp_remote_retrieve_body( $response ) );

			foreach( $user_tokens as $md5 => $user_token ) {
				if ( isset( $user_map[ $md5 ] ) ) {
					update_user_meta( $user_map[ $md5 ], 'cbsb_connection', $user_token );
				}
			}
		}
	}
	update_option( 'cbsb_user_token_migrated', true );
}

function cbsb_user_token_check( $site_connection ) {
	if ( false === get_option( 'cbsb_user_token_migrated', false ) ) {
		cbsb_migrate_user_token( $site_connection );
	}
	return $site_connection;
}
add_filter( 'option_cbsb_connection', 'cbsb_user_token_check' );

function cbsb_request_access() {
	$user = wp_get_current_user();
	$key = sanitize_key( substr( wp_generate_uuid4(), 0, 5 ) );
	$token = sanitize_text_field( wp_generate_uuid4() );

	$connected_users = get_users( array( 'meta_key' => 'cbsb_connection' ) );

	$connected = $connected_users[0];

	$access = array(
		'requested_by'   => $user->ID,
		'token_holder'   => $connected->ID,
		'token'          => $token
	);

	set_transient( 'cbsb_admin_access_request_' . $key , $access, DAY_IN_SECONDS );

	$link = add_query_arg( array(
		'action' => 'cbsb_grant_access',
		'key'    => $key,
		'token'  => $token
	), admin_url( 'admin-ajax.php' ) );

	$email_body = $user->get( 'display_name' ) . ' ';
	
	$email_body .= __( 'would like access to use Start Booking in the WordPress admin.', 'calendar-booking' ) . "\r\n\r\n";

	$email_body .= __( 'Click link to grant access, otherwise do nothing.', 'calendar-booking' ) . "\r\n\r\n";

	$email_body .= $link;

	$status = wp_mail( $connected->user_email, $user->get( 'display_name' ) . ' ' . __( 'requesting access to Start Booking', 'calendar-booking' ), $email_body );

	if ( $status ) {
		$message = $connected->get( 'display_name' ) . ' ' . __( 'has been sent an email requesting access.', 'calendar-booking' );
	} else {
		$message = __( 'Unable to send email to request access.', 'calendar-booking' );
	}

	wp_send_json( array(
		'status'  => ( $status ) ? 'success' : 'error',
		'message' => $message,
	) );
}
add_action( 'wp_ajax_cbsb_request_access', 'cbsb_request_access' );

function cbsb_grant_access() {
	$key = sanitize_key( $_GET['key'] );
	$token = sanitize_text_field( $_GET['token'] );
	$transient = get_transient( 'cbsb_admin_access_request_' . $key );
	
	if ( $transient !== false && $transient['token'] === $token ) {
		$requester = get_user_by( 'ID', $transient['requested_by'] );
		$granter = get_user_by( 'ID', $transient['token_holder'] );
		$existing_token = get_user_meta( $transient['token_holder'], 'cbsb_connection', true );

		$updated = update_user_meta( $transient['requested_by'], 'cbsb_connection', $existing_token );
		if ( $updated ) {
			$requester_title = 'Start Booking: ' . $granter->get( 'display_name' ) . ' ' . __( 'granted access.', 'calendar-booking' );
			$requester_body = $granter->get( 'display_name' ) . ' ' . __( 'has granted access to', 'calendar-booking' ) . ' Start Booking in WordPress.' . "\r\n\r\n";
			$requester_body .= add_query_arg( array(
				'page' => 'start-booking',
			), admin_url( 'admin.php' ) );
 			wp_mail( $requester->user_email, $requester_title, $requester_body );

			$granter_title = 'Start Booking: ' . __( 'Access granted to', 'calendar-booking' ) . ' ' . $requester->get( 'display_name' );
			$granter_body = __( 'You have granted access to', 'calendar-booking' ) . ' ' . $requester->get( 'display_name' ) .  '. ';
			$granter_body .= __( 'If this is not your intention please contact Start Booking support.', 'calendar-booking' ) . "\r\n\r\n";
			$granter_body .= 'https://app.startbooking.com';
			wp_mail( $granter->user_email, $granter_title, $granter_body );

			$message = __( 'Access granted to', 'calendar-booking' ) . ' ' . $requester->get( 'display_name' ) . '.';
			set_transient( 'cbsb_login_message', $message, 30 );
			
			$redirect = add_query_arg( array(
				'page' => 'start-booking',
			), admin_url( 'admin.php' ) );

			wp_redirect( wp_login_url( 
				$redirect,
				true
			) );
		} else {
			$message = __( 'Unable to grant access to', 'calendar-booking' ) . ' ' . $requester->get( 'display_name' ) . '.';
			set_transient( 'cbsb_login_message', $message, 30 );
			
			$redirect = add_query_arg( array(
				'page' => 'start-booking',
			), admin_url( 'admin.php' ) );

			wp_redirect( wp_login_url( 
				$redirect,
				true
			) );
		}
	}
	die;
}
add_action( 'wp_ajax_nopriv_cbsb_grant_access', 'cbsb_grant_access' );

function cbsb_access_message( $default ) {
	$message = get_transient( 'cbsb_login_message' );
    if ( $message ) {
		return '<div class="login message"><strong>Start Booking: </strong>' . $message . '</div>';
	}
	return $default;
}
add_filter( 'login_message', 'cbsb_access_message' );