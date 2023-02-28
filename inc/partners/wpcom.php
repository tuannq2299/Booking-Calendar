<?php

function cbsb_wpcom_is() {
    if ( defined( 'IS_ATOMIC' ) && IS_ATOMIC && defined( 'ATOMIC_CLIENT_ID' )  && '2' === ATOMIC_CLIENT_ID ) {
        return (bool) true;
    } else {
        return (bool) false;
    }
}

function cbsb_wpcom_has_active_subscription() {
    $active_subscription = (bool) false;
    if ( cbsb_wpcom_is() &&  !is_null( cbsb_wpcom_subscription_get() ) ) {
		$active_subscription = (bool) true;
	}
    return $active_subscription;
}

function cbsb_wpcom_subscription_get() {
    $subscription = null;
    if ( cbsb_wpcom_is() ) {
		$wpcom_active_subscriptions = get_option( 'wpcom_active_subscriptions', array() );
        if ( count( $wpcom_active_subscriptions ) > 0 ) {

            if ( isset( $wpcom_active_subscriptions['calendar-booking-wpcom'] ) ) {
                $subscription = $wpcom_active_subscriptions['calendar-booking-wpcom'];
            }
        }
	}
    return $subscription;
}

function cbsb_wpcom_get_site_id() {
    if ( cbsb_wpcom_is() ) {
        if ( defined('ATOMIC_SITE_ID') && ATOMIC_SITE_ID ) {
            return ATOMIC_SITE_ID;
        }
    }
    return null;
}

function cbsb_access_wpcom_account() {
	$user = wp_get_current_user();
	$hash = md5( $user->data->user_email . '_' . get_site_url() . '_' . time() );
	$has_wpcom_subscription = cbsb_wpcom_has_active_subscription();
	$subscription = cbsb_wpcom_subscription_get();

	if ( !is_null($subscription) && isset($subscription->ownership_id) ) {
		global $wp_version;

		$body = array_filter( array(
			'ownership_id' => strval($subscription->ownership_id),
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
	} else {
		$response = array( 'status' => 'error', 'message' => __('Invalid response from startbooking.com.', 'calendar-booking'), 'reload' => false );
	}

	wp_send_json( $response );
}
add_action( 'wp_ajax_cbsb_access_wpcom_account', 'cbsb_access_wpcom_account' );
