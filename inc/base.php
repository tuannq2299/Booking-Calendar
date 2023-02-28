<?php

function cbsb_activate( $plugin ) {
	if ( $plugin == 'calendar-booking/start-booking.php' ) {

		$connect_hash = get_option( 'cbsb_login_hash', false );
		$previous_connection = get_option( 'cbsb_connection', false );

		$user = wp_get_current_user();
		$hash = md5( $user->data->user_email . '_' . get_site_url() . '_' . time() );

		$has_wpcom_subscription = cbsb_wpcom_has_active_subscription();

		// Boolean means an option was not found
		if ( false === $connect_hash && false === $previous_connection && false === $has_wpcom_subscription ) {
			// Create new trial
			
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
				'ip'             => $ip,
			) );

			if ( !is_null(cbsb_wpcom_get_site_id()) ) {
				$body['account_options'] = array('wpcom_site_id' => cbsb_wpcom_get_site_id());
			}

			$http_response = wp_remote_post( CBSB_APP_URL . 'api/v1/register', array( 'timeout' => 60, 'body' => $body ) );
			$http_body = wp_remote_retrieve_body( $http_response );
			$json = json_decode( $http_body );

			if ( ! is_null( $json ) && property_exists( $json, 'error' ) ) {
				
				cbsb_connection_clear_all();
				
				if ( wp_redirect( admin_url( 'admin.php?page=start-booking#/' ) ) ) {
					exit;
				}

			} else {

				if ( isset( $json->data ) ) {
					update_option( 'cbsb_connection', (array) $json->data->tokens );
					update_option( 'cbsb_login_hash', $hash );

					update_user_meta( $user->data->ID, 'cbsb_connection', $json->data->tokens->{'wp-admin'} );

					if ( false == get_option( 'cbsb_booking_page' ) && !cbsb_has_book_now_page()) {
						// If they don't have a booking page, create one
						cbsb_create_booking_page( 'Book Now' );
					}

					if ( wp_redirect( admin_url( 'admin.php?page=start-booking&welcome=true#/welcome' ) ) ) {
						exit;
					}

				} else {
					cbsb_connection_clear_all();

					if ( wp_redirect( admin_url( 'admin.php?page=start-booking#/' ) ) ) {
						exit;
					}
				}

			}

		} elseif ( $has_wpcom_subscription ) {
			global $wp_version;

			$wpcom_subscription = cbsb_wpcom_subscription_get();

			$email = array_filter( array('email' => $user->data->user_email) );
			$emailArgs = array(
				'user-agent'  => 'WP:BK:API/' . $wp_version . ':' . CBSB_VERSION . ':CBSB_Api; ' . home_url(),
				'blocking'    => true,
				'headers'     => array(
					'Accept'        => 'application/json',
					'Content-Type'  => 'application/json',
					'X-Requested-With' => 'XMLHttpRequest'
				),
				'timeout'     => 20,
				'body'        => json_encode( $email )
			);

			$email_http_response = wp_remote_post( CBSB_APP_URL . 'api/v1/users/check', $emailArgs );
			$email_http_body = wp_remote_retrieve_body( $email_http_response );
			$email_json = json_decode( $email_http_body );

			if ( ! is_null( $email_json ) && $email_json->message === 'error' ) {
				// Show login screen because their email already exists
				cbsb_connection_clear_all();
				
				if ( wp_redirect( admin_url( 'admin.php?page=start-booking' ) ) ) {
					exit;
				}

			} else {

				$body = array_filter( array(
					'ownership_id' => strval($wpcom_subscription->ownership_id),
					'site_id'      => cbsb_wpcom_get_site_id(),
					'scopes'       => ['booking-flow', 'wp-admin'],
					'hash'         => $hash,
				));
			
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
	
				$http_response = wp_remote_post( CBSB_APP_URL . 'api/v1/initialize_wpcom', $args );
				$http_body = wp_remote_retrieve_body( $http_response );
				$json = json_decode( $http_body );
	
				if ( ! is_null( $json ) && property_exists( $json, 'error' ) ) {
					// Error state
					cbsb_connection_clear_all();
					
					if ( wp_redirect( admin_url( 'plugins.php' ) ) ) {
						exit;
					}
				} else {
					// API returned the correct information
					update_option( 'cbsb_connection', (array) $json->tokens );
					update_option( 'cbsb_login_hash', $hash );
					update_option( 'cbsb_user_token_migrated', true );
		
					update_user_meta( $user->data->ID, 'cbsb_connection', $json->tokens->{'wp-admin'} );
	
					if ( false == get_option( 'cbsb_booking_page' ) && !cbsb_has_book_now_page()) {
						// If they don't have a booking page, create one
						cbsb_create_booking_page( 'Book Now' );
					}
	
					if ( wp_redirect( admin_url( 'admin.php?page=start-booking' ) ) ) {
						exit;
					}
				}
			}

		} else {
			// Already have an account and setup properly
			if ( wp_redirect( admin_url( 'admin.php?page=start-booking#/' ) ) ) {
				exit;
			}
		}
	}
}
add_action( 'activated_plugin', 'cbsb_activate' );

function cbsb_deactivate( $plugin ) {
	if ( $plugin == 'calendar-booking/start-booking.php' ) {

		$body = array_filter( array(
			'key'   => 'plugin_deactivated',
			'value' => true 
		) );

		if ( cbsb_is_connected() ) {
			cbsb_api_request( 'account/options', $body, 'POST' );
		}
	}
}
add_action( 'deactivated_plugin', 'cbsb_deactivate' );

function cbsb_dismiss_notice() {
	$notice = $_POST['notice'];
	update_option( 'cbsb_dismissed_' . $notice, true );
	wp_send_json( array( 'status' => 'success' ) );
}
add_action( 'wp_ajax_cbsb_dismiss_notice', 'cbsb_dismiss_notice' );

function cbsb_admin_activate_notice() {

	$has_wpcom_subscription = cbsb_wpcom_has_active_subscription();

	$notice_dismissed = get_option( 'cbsb_dismissed_start-booking-notice-activate', false );
	
	if ( cbsb_is_connected() && !cbsb_is_activated() && !$notice_dismissed && !$has_wpcom_subscription ) {
		echo '<div id="start-booking-notice-activate" class="notice notice-success is-dismissible">
			<h3 style="padding:2px;font-weight:normal;margin:.5em 0 0;">' . __( 'Get started with online scheduling from', 'calendar-booking' ) . ' Start Booking</h3>
			<p>' . __( 'Great news, your booking page is live! Activate your account to ensure you don\'t miss appointments.', 'calendar-booking' ) . '</p>
			<p>
				<a href="/wp-admin/admin.php?page=start-booking#/activate" class="button button-primary button-large" title="' . __( 'Activate', 'calendar-booking' ) . '">' . __( 'Activate', 'calendar-booking' ) . '</a>
				<a style="margin-left:8px" href="/wp-admin/admin.php?page=start-booking#/" title="' . esc_attr__( 'Continue setup', 'calendar-booking' ) . '">' . __( 'Continue setup', 'calendar-booking' ) . '  &rarr;</a>
			</p>
		</div>
		<script type="text/javascript">
			window.addEventListener( "load", function() {
				var dismissBtn  = document.querySelector( "#start-booking-notice-activate .notice-dismiss" );
				if ( dismissBtn !== null) {
					dismissBtn.addEventListener( "click", function( event ) {
						var httpRequest = new XMLHttpRequest(),
							postData    = "";
							postData += "&action=cbsb_dismiss_notice";
							postData += "&notice=start-booking-notice-activate";

						httpRequest.open( "POST", "' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" );
						httpRequest.setRequestHeader( "Content-Type", "application/x-www-form-urlencoded" )
						httpRequest.send( postData );
					});
				}
			});
		</script>
		';
	}
}
add_action( 'admin_notices', 'cbsb_admin_activate_notice' );

function cbsb_admin_trial_ending_notice() {
	$notice_dismissed = get_option( 'cbsb_dismissed_start-booking-notice-plan', false );
	if ( cbsb_is_connected() && cbsb_in_trial() && cbsb_is_activated() && !$notice_dismissed ) {
		$days_remaining = cbsb_trial_days_remaining();
		if ( (int) $days_remaining === 1 ) {
			$days_remaining_message = __( 'Your Start Booking trial expires in ', 'calendar-booking' ) . $days_remaining . ' ' . __( 'day', 'calendar-booking' ) .'!';
		} else {
			$days_remaining_message = __( 'Your Start Booking trial expires in ', 'calendar-booking' ) . $days_remaining . ' ' . __( 'days', 'calendar-booking' ) .'!';
		}

		$hours_remaining = cbsb_trial_hours_remaining();
		if ( (int) $hours_remaining === 1 ) {
			$hours_remaining_message = __( 'Your Start Booking trial expires in the next hour!', 'calendar-booking' );	
		} else {
			$hours_remaining_message = __( 'Your Start Booking trial expires in ', 'calendar-booking' ) . $hours_remaining . ' ' . __( 'hours', 'calendar-booking' ) . '!';
		}

		if ( (int) $days_remaining <= 3 ) {

			if ( (int) $days_remaining === 0 ) {
				
				echo '<div id="start-booking-notice-plan" class="notice notice-error is-dismissible">
					<h3 style="padding:2px;font-weight:normal;margin:.5em 0 0;">' . $hours_remaining_message . '</h3>
					<p>' . __( 'To keep your premium online booking features and data, select a plan today.', 'calendar-booking' ) . '</p>
					<p>
						<a href="' . CBSB_APP_URL . 'account/billing/upgrade?sso=' . cbsb_sso_hash() . '&utm_source=wp_admin&utm_medium=sso&utm_campaign=notification_' . $hours_remaining . '_hours_remaining" id="cbsb_notify_select_plan" class="button button-primary button-large" title="' . __( 'Select a Plan', 'calendar-booking' ) . '">' . __( 'Select a Plan', 'calendar-booking' ) . '</a>
					</p>
				</div>
				<script type="text/javascript">
					window.addEventListener( "load", function() {
						var dismissBtn  = document.querySelector( "#start-booking-notice-plan .notice-dismiss" );

						dismissBtn.addEventListener( "click", function( event ) {
							var httpRequest = new XMLHttpRequest(),
								postData    = "";
								postData += "&action=cbsb_dismiss_notice";
								postData += "&notice=start-booking-notice-plan";

							httpRequest.open( "POST", "' . esc_url( admin_url("admin-ajax.php" ) ) . '" );
							httpRequest.setRequestHeader( "Content-Type", "application/x-www-form-urlencoded" )
							httpRequest.send( postData );
						});
					});
				</script>
				';

			} else {

				echo '<div id="start-booking-notice-plan" class="notice notice-warning is-dismissible">
					<h3 style="padding:2px;font-weight:normal;margin:.5em 0 0;">' . $days_remaining_message . '</h3>
					<p>' . __( 'To keep your premium online booking features and data, select a plan today.', 'calendar-booking' ) . '</p>
					<p>
						<a href="' . CBSB_APP_URL . 'account/billing/upgrade?sso=' . cbsb_sso_hash() . '&utm_source=wp_admin&utm_medium=sso&utm_campaign=notification_' . $days_remaining . '_days_remaining" id="cbsb_notify_select_plan" class="button button-primary button-large" title="' . __( 'Select a Plan', 'calendar-booking' ) . '">' . __( 'Select a Plan' ) . '</a>
					</p>
				</div>
				<script type="text/javascript">
					window.addEventListener( "load", function() {
						var dismissBtn  = document.querySelector( "#start-booking-notice-plan .notice-dismiss" );

						dismissBtn.addEventListener( "click", function( event ) {
							var httpRequest = new XMLHttpRequest(),
								postData    = "";
								postData += "&action=cbsb_dismiss_notice";
								postData += "&notice=start-booking-notice-plan";

							httpRequest.open( "POST", "' . esc_url( admin_url("admin-ajax.php" ) ) . '" );
							httpRequest.setRequestHeader( "Content-Type", "application/x-www-form-urlencoded" )
							httpRequest.send( postData );
						});
					});
				</script>
				';
			}
		}
	}
}
add_action( 'admin_notices', 'cbsb_admin_trial_ending_notice' );

function cbsb_sso_hash() {
	$request = cbsb_api_request( 'sso', array(), 'GET', 50 );
	return $request->data;
}

function cbsb_store_account_activated() {
	global $wp_version;
	update_option( 'cbsb_account_activated', true );
	$response = array( 'status' => 'success' );
	wp_send_json( $response );
}
add_action( 'wp_ajax_cbsb_store_account_activated', 'cbsb_store_account_activated' );

function cbsb_load_textdomain() {
	load_plugin_textdomain( 'calendar-booking', false, basename( CBSB_BASE_DIR ) . '/languages/' );
}
add_action( 'plugins_loaded', 'cbsb_load_textdomain' );

function cbsb_create_booking_page( $title ) {

	$content = '[startbooking flow="services"]';

	$post = array(
		'post_title'     => $title,
		'post_content'   => $content,
		'post_status'    => 'publish',
		'post_type'      => 'page',
	);
	$create = wp_insert_post( $post );
	if ( $create ) {
		update_option( 'cbsb_booking_page', $create );
		$response = array( 'status' => 'success', 'message' => __( 'Booking Page Created.', 'calendar-booking' ), 'reload' => true );
	} else {
		$response = array( 'status' => 'error', 'message' => __( 'Unable to create page.', 'calendar-booking' ), 'reload' => false );
	}
}

function cbsb_add_services( $wp_data ) {
	if ( isset( $_GET['add_service'] ) ) {
		$wp_data['initialState']->services = array();
		if ( ! is_array( $_GET['add_service'] ) ) { $_GET['add_service'] = array( $_GET['add_service'] ); }
		$wp_data['initialState']->services = array_map( 'esc_html', $_GET['add_service'] );
		$service_details = cbsb_api_request( 'services' );
		if ( 'Success' == $service_details->message && isset( $service_details->services ) ) {
			$wp_data['initialState']->total_duration = 0;
			foreach ( $service_details->services as $service ) {
				if ( in_array( $service->url_string, $wp_data['initialState']->services ) ) {
					$wp_data['initialState']->total_duration += $service->duration;
					$wp_data['initialState']->service_names[] = $service->name;

					//TODO this is temp until we can get the front end in sync
					$service->uid = $service->url_string;
					if ( is_array( $service->types ) ) {
						foreach ( $service->types as $type ) {
							$type->uid = $type->url_string;
						}
					}
					//End TODO

					$wp_data['initialState']->default_cart[] = $service;
				}
			}
			$wp_data['skipSteps'][] = 'services';
		}
	}
	return $wp_data;
}
add_filter( 'cbsb_react_fe', 'cbsb_add_services', 20, 1 );

function cbsb_get_account_timezone() {
	$details = cbsb_account_details();
	if ( isset( $details['account_details'] ) && ! is_null( $details['account_details'] ) && property_exists( $details['account_details'], 'timezone' ) ) {
		return $details['account_details']->timezone;
	} else {
		return false;
	}
}

function cbsb_get_account_location_type() {
	$details = cbsb_account_details();
	if ( isset( $details['account_details'] ) && ! is_null( $details['account_details'] ) && property_exists( $details['account_details'], 'location_type' ) ) {
		return $details['account_details']->location_type;
	} else {
		return false;
	}
}

function cbsb_account_details( $wp_data = array() ) {
	global $cbsb;
	$details = $cbsb->get( 'account/details' );
	if ( 'success' == $details['status'] && isset( $details['data'] ) ) {
		$wp_data['account_details'] = $details['data'];
		$wp_data['account_details']->domain = get_site_url();
		if ( ! is_null( $wp_data['account_details'] ) && property_exists( $wp_data['account_details'], 'address' ) ) {
			$wp_data['account_details']->account_uid = $wp_data['account_details']->address->account_url_string;
		}
		$wp_data['account_details']->days_closed = array();
		$days = array_flip( array(
			'Sunday',
			'Monday',
			'Tuesday',
			'Wednesday',
			'Thursday',
			'Friday',
			'Saturday'
		) );
		if ( isset( $wp_data['account_details'] ) && ! is_null( $wp_data['account_details'] ) && property_exists( $wp_data['account_details'], 'location_hours' ) && is_array( $wp_data['account_details']->location_hours ) ) {
			foreach ( $wp_data['account_details']->location_hours as $weekday ) {
				if ( 'closed' == $weekday->day_type ) {
					$wp_data['account_details']->days_closed[] = $days[$weekday->day];
				}
			}
		}
		if ( isset( $wp_data['account_details']->payments ) ) {
			$wp_data['account_details']->payments->plugin_name = 'WordPress: Calendar Booking by Start Booking';
			$wp_data['account_details']->payments->plugin_version = CBSB_VERSION;
			$wp_data['account_details']->payments->site_url = site_url();
		}
	}

	return $wp_data;
}
add_filter( 'cbsb_react_fe', 'cbsb_account_details', 10, 1 );

function cbsb_cast_settings($settings) {
	foreach ( $settings as $key => $value ) {
		if ( 'true' === $value ) {
			$settings[ $key ] = true;
		}
		if ( 'false' === $value ) {
			$settings[ $key ] = false;
		}
	}
	return $settings;
}

function cbsb_get_banner() {
	if ( ! cbsb_is_connected() ) { return false; }
	$banner = cbsb_api_request( 'banner', array(), 'GET', 900 );
	return $banner;
}

function cbsb_current_settings() {
	$defaults = array(
		'btn_bg_color'                      => '000',
		'btn_txt_color'                     => 'fff',
		'endorse_us'                        => false,
		'show_progress'                     => true,
		'allow_data_collection'             => false,
		'is_connected'                      => ( get_option( 'cbsb_connection' ) ) ? true : false,
		'disable_booking'                   => false,
		'expedited_single_service'          => true,
		'expedited_single_service_type'     => true,
		'expedited_qty_services'            => true,
		'booking_window'                    => 0,
		'default_class_view'                => 'list',
		'show_sold_out_classes'             => true,
		'show_room_details'                 => true,
		'show_remaining_availability'       => true,
		'class_destination'                 => '',
		'service_destination'               => '',
		'automatic_provider'                => true,
		'appointment_use_visitor_timezone'  => true,
		'use_visitor_timezone'              => true,
		'booking_window_start_qty'          => 0,
		'booking_window_end_qty'            => 0,
		'booking_window_start_type'         => 'none',
		'booking_window_end_type'           => 'none',
		'calendar_locale'                   => 'en',
		'hour_format'                       => 12,
		'api_communication'                 => null
	);

	$current_settings = get_option( 'start_booking_settings' );

	$current_settings = wp_parse_args( $current_settings, $defaults );

	$channel_settings = cbsb_api_request( 'channel/wordpress', array(), 'GET', 30 );

	if ( isset( $channel_settings->data ) ) {
		$channel_settings = (array) $channel_settings->data;
		$channel_settings = array_diff( $channel_settings, array( null ) );
		$current_settings = wp_parse_args( $channel_settings, $current_settings );
	}

	$editors = cbsb_api_request( 'editors/settings', array(), 'GET', 30 );
	if ( ! isset( $editors->error ) && isset( $editors->data->settings ) ) {
		$current_settings['booking_window_start_qty'] = intval( $editors->data->settings->booking_window_start_qty );
		$current_settings['booking_window_end_qty'] = intval( $editors->data->settings->booking_window_end_qty );
		$current_settings['booking_window_start_type'] = $editors->data->settings->booking_window_start_type;
		$current_settings['booking_window_end_type'] = $editors->data->settings->booking_window_end_type;
		$current_settings['use_visitor_timezone'] = $editors->data->settings->use_visitor_timezone;
		$current_settings['allow_data_collection'] = $editors->data->settings->allow_data_collection;
		$current_settings['calendar_locale'] = $editors->data->settings->calendar_locale;
	}

	$current_settings = cbsb_calculate_window( $current_settings );
	return cbsb_cast_settings( $current_settings );
}

function cbsb_calculate_window( $settings ) {
	$settings['booking_window_start_qty'] = (int) $settings['booking_window_start_qty'];
	$settings['booking_window_end_qty'] = (int) $settings['booking_window_end_qty'];

	switch ( $settings['booking_window_start_type'] ) {
		case 'hours':
			$start_multiplier = HOUR_IN_SECONDS;
			break;
		case 'days':
			$start_multiplier = DAY_IN_SECONDS;
			break;
		case 'weeks':
			$start_multiplier = WEEK_IN_SECONDS;
			break;
		case 'months':
			$start_multiplier = MONTH_IN_SECONDS;
			break;
		default:
			$start_multiplier = 0;
			break;
	}

	$end_multiplier = 0;
	switch ( $settings['booking_window_end_type'] ) {
		case 'hours':
			$end_multiplier = HOUR_IN_SECONDS;
			break;
		case 'days':
			$end_multiplier = DAY_IN_SECONDS;
			break;
		case 'weeks':
			$end_multiplier = WEEK_IN_SECONDS;
			break;
		case 'months':
			$end_multiplier = MONTH_IN_SECONDS;
			break;
		default:
			$end_multiplier = 0;
			break;
	}

	$settings['booking_window_start'] = $settings['booking_window_start_qty'] * $start_multiplier;

	$settings['booking_window_end'] = $settings['booking_window_end_qty'] * $end_multiplier;

	return $settings;
}

function cbsb_account_subscription() {
	if ( ! cbsb_is_connected() ) { return false; }
	$account_status = cbsb_api_request( 'account/billing/status' );
	if ( is_object( $account_status ) && ! is_null( $account_status ) && property_exists( $account_status, 'valid' ) ) {
		$account_status->valid;
	} else {
		return false;
	}
}

function cbsb_active_subscription( $wp_data ) {
	$wp_data['account_status'] = cbsb_account_subscription();
	return $wp_data;
}
add_filter( 'cbsb_react_fe', 'cbsb_active_subscription', 10, 1 );

function cbsb_get_rest_api() {
	$settings = cbsb_current_settings();
	if ( $settings['api_communication'] === 'proxy' ) {
		$rest_api = get_rest_url() . 'startbooking';
	} else {
		$rest_api = CBSB_API_URL . 'api';
	}
	return $rest_api;
}

function cbsb_copy_transfer( $wp_data ) {
	$wp_data['copy'] = cbsb_get_copy();
	return $wp_data;
}
add_filter( 'cbsb_react_fe', 'cbsb_copy_transfer', 10, 1 );

function cbsb_array_merge_recursive_simple() {

	if ( func_num_args() < 2 ) {
		trigger_error( __FUNCTION__ .' needs two or more array arguments', E_USER_WARNING );
		return;
	}
	$arrays = func_get_args();
	$merged = array();
	while ( $arrays ) {
		$array = array_shift( $arrays );
		if ( !is_array( $array ) ) {
			trigger_error( __FUNCTION__ .' encountered a non array argument', E_USER_WARNING );
			return;
		}
		if ( !$array )
			continue;
		foreach ( $array as $key => $value ) {
			if ( is_string( $key ) ) {
				if ( is_array( $value ) && array_key_exists( $key, $merged ) && is_array( $merged[ $key ] ) ) {
					$merged[ $key ] = call_user_func( __FUNCTION__, $merged[ $key ], $value );
				} else {
					$merged[ $key ] = $value;
				}
			} else {
				$merged[] = $value;
			}
		}
	}
	return $merged;
}

function cbsb_get_brightness($hex) {
	$hex = str_replace( '#', '', $hex );
	$c_r = hexdec( substr( $hex, 0, 2 ) );
	$c_g = hexdec( substr( $hex, 2, 2 ) );
	$c_b = hexdec( substr( $hex, 4, 2 ) );

	return ( ( $c_r * 299 ) + ( $c_g * 587 ) + ( $c_b * 114 ) ) / 1000;
}

function cbsb_set_groups( $wp_data ) {
	if ( cbsb_is_connected() ) {
		$groups = cbsb_api_request( 'classes' );
		if ( 'Success' == $groups->message && isset( $groups->data ) ) {
			$wp_data['groups'] = array();
			foreach ( $groups->data as $group ) {
				$wp_data['groups'][] = (array) $group;
			}
		}
	}
	return $wp_data;
}
add_filter( 'cbsb_react_fe', 'cbsb_set_groups', 10, 1 );

function cbsb_set_locale( $wp_data ) {
	$wp_data['locale'] = $wp_data['settings']['calendar_locale'];
	return $wp_data;
}
add_filter( 'cbsb_react_fe', 'cbsb_set_locale', 10, 1 );

function cbsb_set_users( $wp_data ) {
	if ( cbsb_is_connected() ) {
		$users = cbsb_api_request( 'services/providers' );
		if ( $users && is_array( $users ) && count( $users ) > 0 ) {
			foreach ( $users as $k => $user ) {
				$user->fullname = $user->first_name . ' ' . $user->last_name;
				$users[ $k ] = $user;
			}
			$wp_data['users'] = $users;
		}
	}
	return $wp_data;
}
add_filter( 'cbsb_react_fe', 'cbsb_set_users', 10, 1 );

function cbsb_set_integrations( $wp_data ) {
	if ( cbsb_is_connected() ) {
		$integrations = cbsb_api_request( 'integrations' );
		if ( 'Success' == $integrations->message && isset( $integrations->data ) ) {
			$wp_data['integrations'] = $integrations->data;
		}
	}
	return $wp_data;
}
add_filter( 'cbsb_react_fe', 'cbsb_set_integrations', 10, 1 );

function cbsb_get_plan() {
	global $cbsb;
	$account_details = $cbsb->get( 'account/details' );
	if ( isset( $account_details['data'] ) && ! is_null( $account_details['data'] ) && property_exists( $account_details['data'], 'billing' ) ) {
		$plan = $account_details['data']->billing->has_valid_subscription;
	} else {
		$plan = 'free';
	}
	return $plan;
}

function cbsb_get_application_loader() {
	return '
	<div class="sb-loader">
		<div style="width: 300px; text-align: center; line-height:initial; margin:auto">
			<p>' . __( 'Loading', 'calendar-booking' ) . '</p>
			<svg style="display: \'block\'; margin: \'auto\'"
				version="1.1"
				xmlns="http://www.w3.org/2000/svg"
				xmlnsXlink="http://www.w3.org/1999/xlink"
				enableBackground="new 0 0 0 0"
				xmlSpace="preserve"
			>
				<circle fill="#888" stroke="none" cx="100" cy="50" r="6">
					<animate
						attributeName="opacity"
						dur="2s"
						values="0;1;0"
						repeatCount="indefinite"
						begin="0"
					/>
				</circle>
				<circle fill="#888" stroke="none" cx="150" cy="50" r="6">
					<animate
						attributeName="opacity"
						dur="2s"
						values="0;1;0"
						repeatCount="indefinite"
						begin="0.5"
					/>
				</circle>
				<circle fill="#888" stroke="none" cx="200" cy="50" r="6">
					<animate
						attributeName="opacity"
						dur="2s"
						values="0;1;0"
						repeatCount="indefinite"
						begin="1"
					/>
				</circle>
			</svg>
		</div>
		<noscript>
			For online booking to function it is necessary to enable JavaScript.
			Here are the <a href="https://www.enable-javascript.com/" target="_blank">
			instructions to enable JavaScript in your web browser</a>.
		</noscript>
	</div>';
}

function cbsb_is_connected() {
	return ( get_option( 'cbsb_connection', false ) ) ? true : false;
}

function cbsb_is_user_connected() {
	$user = wp_get_current_user();
	if ( ! ( $user instanceof WP_User ) || ! $user->ID ) {
		return false;
	}
	return ( get_user_meta( $user->ID, 'cbsb_connection', true ) ) ? true : false;
}

function cbsb_is_activated() {
	return ( get_option( 'cbsb_account_activated', false ) ) ? true : false;
}

function cbsb_in_trial() {
	$trial_ends_at = get_option( 'cbsb_trial_ends_at', false );
    if ( $trial_ends_at ) {
    	$diff = ( $trial_ends_at - time() );
		return ( $diff > 0 && ! cbsb_account_subscription() ) ? true : false;
    } else {
    	return false;
    }
}

function cbsb_trial_days_remaining() {
	$trial_ends_at = get_option( 'cbsb_trial_ends_at', false );
	if ( $trial_ends_at ) {
		$diff = ( $trial_ends_at - time() );
		return floor( $diff / 86400 );
	}
	return 0;
}

function cbsb_trial_hours_remaining() {
	$trial_ends_at = get_option( 'cbsb_trial_ends_at', false );
	if ( $trial_ends_at ) {
		$diff = ( $trial_ends_at - time() );
		return floor( $diff / 3600 );
	}
	return 0;
}

function cbsb_format_minutes( $duration ) {
	$hours = 0;
	$minutes = 0;
	$parts = $duration / 60;
	$hours = (int) floor( $parts );
	$min_parts = $parts - $hours;
	$minutes = $min_parts * 60;
	$output = '';

	if ( 1 === $hours ) {
		$output .= '1 ' . __( 'hour', 'calendar-booking');
	} else if ( $hours > 1 ) {
		$output .= $hours . ' ' . __( 'hours', 'calendar-booking');
	}
	$output .= ' ';
	if ( 1 === $minutes ) {
		$output .= '1 ' . __( 'minute', 'calendar-booking');
	} else if ( $minutes > 1 ) {
		$output .= $minutes . ' ' . __( 'minutes', 'calendar-booking');
	}
	return trim( $output );
}

function cbsb_get_startbooking_global() {
	if ( cbsb_is_connected() ) {
		$account = cbsb_api_request( 'account/details' );

		if ( ! is_null( $account ) && property_exists( $account, 'url_string' ) ) {
			$translations = cbsb_api_request( 'account/' . $account->url_string . '/translations' );
		} else {
			$translations = null;
		}

		$channel = cbsb_api_request( 'channel/wordpress' );
		if ( ! is_null( $channel ) && property_exists( $channel, 'message' ) && 'Success' === $channel->message && property_exists( $channel, 'data' ) ) {
			$channel = $channel->data;
		}
	} else {
		return array();
	}

	$user = wp_get_current_user();

	$rest_token = get_transient( 'cbsb_rest_token', false );
	if ( false === $rest_token ) {
		$rest_token = cbsb_generate_rest_token();
	}

	$settings = cbsb_current_settings();
	if ( $settings['api_communication'] !== 'proxy' ) {
		global $wp_version;

		if ( is_admin() ) {
			$user_connection = get_user_meta( $user->data->ID, 'cbsb_connection', true );
			$token = ( $user_connection ) ? $user_connection : null;
		} else {
			$connection = get_option( 'cbsb_connection' );
			$token = isset( $connection['booking-flow'] ) ? $connection['booking-flow'] : null;
		}
		$direct = array(
			'user_agent'  => 'WP:BK:Direct/' . $wp_version . ':' . CBSB_VERSION . ':StartBookingComAPI; ' . home_url(),
			'token'       => $token,
		);
	}

	$integrations = cbsb_api_request( 'integrations', array(), 'GET', 30 );

	if ( is_object( $integrations ) &&
		! isset( $integrations->error ) &&
		'Success' == $integrations->message &&
		isset( $integrations->data ) ) {
		$integrations = (array) $integrations->data;
	} else {
		$integrations = array();
	}

	$return = array(
		'rest_api'     => cbsb_get_rest_api(),
		'base_url'     => CBSB_BASE_URL,
		'app_url'      => CBSB_APP_URL,
		'api_url'      => CBSB_API_URL,
		'wp_url'       => home_url(),
		'wp_admin_url' => admin_url(),
		'settings'     => cbsb_current_settings(),
		'token'        => $rest_token,
		'connected'    => array(
			'account' => ( cbsb_is_connected() ) ? 'true' : 'false',
			'user'    => ( cbsb_is_user_connected() ) ? 'true' : 'false'
		),
		'product'      => ( isset( $account->billing->product ) ) ? $account->billing->product : null,
		'direct'       => ( isset( $direct ) ) ? $direct : null,
		'integrations' => ( isset( $integrations ) ) ? $integrations : null,
		'translations' => ( ! is_null( $translations ) && property_exists( $translations, 'data' ) ) ? $translations->data : array()
	);

	if ( ! is_null( $account ) ) {
		$default_store = new StdClass;
		$default_store->account = $account;
		$default_store->account->channel_settings = $channel;
		$default_store->account->branding = array(
			'logo' => null,
			'primary_color' => null,
			'secondary_color' => null,
			'primary_btn_text_color' => null,
			'secondary_btn_text_color' => null,
			'updated' => null
		);

		if ( ! is_admin() && is_user_logged_in() && isset($channel->populate_customer) && $channel->populate_customer) {
			$default_store->customer = array(
				'validated'  => null,
				'url_string' => null,
				'email'      => $user->get( 'user_email' ),
				'first_name' => !empty( $user->get( 'first_name' ) ) ? $user->get( 'first_name' ) : null,
				'last_name'  => !empty( $user->get( 'last_name' ) ) ? $user->get( 'last_name' ) : null,
				'mobile_phone' => null,
				'spam' => false
			);
		}

		$return['default_store'] = $default_store;
	}

	return array_filter( $return );
}

function cbsb_check_token( $token ) {
	$active_token = get_transient( 'cbsb_rest_token', false );
	$recent_tokens = get_option( 'cbsb_rest_token_history', array() );
	if ( $token === $active_token ) {
		return 'active';
	}

	if ( in_array( $token, $recent_tokens ) ) {
		return 'recent';
	}

	return 'invalid';
}

function cbsb_generate_rest_token() {
	$rest_token = wp_generate_password( 10, false );
	set_transient( 'cbsb_rest_token', $rest_token, 4 * HOUR_IN_SECONDS );
	$rest_token_history = get_option( 'cbsb_rest_token_history', array() );
	foreach ( $rest_token_history as $timestamp => $token ) {
		if ( $timestamp < ( time() -  ( DAY_IN_SECONDS * 2 ) ) ) {
			unset( $rest_token_history[ $timestamp ] );
		}
	}
	$rest_token_history[ time() ] = $rest_token;
	update_option( 'cbsb_rest_token_history', $rest_token_history );
	return $rest_token;
}

function cbsb_suppress_notices() {
	if ( isset( $_GET['page'] ) && false !== strpos( $_GET['page'], 'start-booking' ) ) {
		?>
		<style type="text/css">
			.update-nag,
			.notice,
			.notice-error,
			.notice-warning,
			.notice-success,
			.notice-info {
				display:none;
			}
		</style>
		<?php
	}
}

function cbsb_booking_flow() {
	return "[startbooking flow='services']";
}

function cbsb_force_booking_flow() {
	if ( isset( $_GET['cbsb_force'] ) && true == $_GET['cbsb_force'] ) {
		$allowed_params = array( 'type', 'service', 'add_services', 'provider', 'date' );
		nocache_headers();
		cbsb_load_service_block_scripts_styles();
		add_filter( 'the_content', 'cbsb_booking_flow', 9 );
	}
}
add_action( 'init', 'cbsb_force_booking_flow' );
