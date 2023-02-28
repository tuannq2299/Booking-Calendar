<?php
/*
*  Routes
*/

function cbsb_main_menu() {
	$menu_titles = array( 'index' => __( 'Start Booking', 'calendar-booking') );

	$icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB2aWV3Qm94PSIwIDAgODkuMSA4OC43MiI+IAogIDxkZWZzPgogICAgPHN0eWxlPgogICAgICBzdmd7CiAgICAgIAlwYWRkaW5nOiAxMCU7CiAgICAgIH0KICAgICAgLmEgewogICAgICAgIGZpbGw6IHVybCgjYSk7CiAgICAgIH0KCiAgICAgIC5iIHsKICAgICAgICBmaWxsOiB1cmwoI2IpOwogICAgICB9CiAgICA8L3N0eWxlPgogICAgPGxpbmVhckdyYWRpZW50IGlkPSJhIiB4MT0iNDAuMiIgeTE9IjQ4LjAyIiB4Mj0iNDAuMiIgeTI9Ijg2LjQ2IiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+CiAgICAgIDxzdG9wIG9mZnNldD0iMCIgc3RvcC1jb2xvcj0iI2ZmZmZmZiIvPgogICAgICA8c3RvcCBvZmZzZXQ9IjEiIHN0b3AtY29sb3I9IiNmZmZmZmYiLz4KICAgIDwvbGluZWFyR3JhZGllbnQ+CiAgICA8bGluZWFyR3JhZGllbnQgaWQ9ImIiIHgxPSI0MC4yIiB5MT0iMzQuMjUiIHgyPSI0MC4yIiB5Mj0iLTEuMDQiIGdyYWRpZW50VW5pdHM9InVzZXJTcGFjZU9uVXNlIj4KICAgICAgPHN0b3Agb2Zmc2V0PSIwIiBzdG9wLWNvbG9yPSIjZmZmZmZmIi8+CiAgICAgIDxzdG9wIG9mZnNldD0iMSIgc3RvcC1jb2xvcj0iI2ZmZmZmZiIvPgogICAgPC9saW5lYXJHcmFkaWVudD4KICA8L2RlZnM+CiAgPHRpdGxlPnN0YXJ0LWJvb2tpbmctaWNvbjwvdGl0bGU+CiAgPGc+CiAgICA8cGF0aCBjbGFzcz0iYSIgZD0iTTU4LjYxLDc5LjkzaDBsLTQyLjg1LS4wOGExNS4xNCwxNS4xNCwwLDAsMSwwLTMwLjI4SDU4LjY3YTkuMSw5LjEsMCwxLDEsMCwxOC4xOWgtNDZWNjEuNzFoNDZhMywzLDAsMCwwLDMtMy4wNywzLDMsMCwwLDAtMy0zSDE1Ljc1YTkuMDksOS4wOSwwLDAsMCwwLDE4LjE4bDQyLjg1LjA4aDBhMTUuMTQsMTUuMTQsMCwwLDAsLjA2LTMwLjI4bC0xOC41NC0uMDcsMC02LjA1LDE4LjU0LjA3QTIxLjI0LDIxLjI0LDAsMCwxLDc5LjgxLDU4Ljc0YTIxLjIsMjEuMiwwLDAsMS0yMS4yLDIxLjE5WiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTAuNiAtMS4wNykiLz4KICAgIDxwYXRoIGNsYXNzPSJiIiBkPSJNNDAuMjYsNDMuNTNsLTE4LjU0LS4wN2EyMS4yLDIxLjIsMCwwLDEsLjA3LTQyLjM5aC4wNWw0Mi44NS4wOWExNS4xNCwxNS4xNCwwLDAsMSwwLDMwLjI4SDIxLjc0YTkuMTMsOS4xMywwLDAsMS05LjEtOSw5LjEsOS4xLDAsMCwxLDkuMS05LjE2aDQ2VjE5LjNoLTQ2YTMsMywwLDAsMC0yLjE2LjksMywzLDAsMCwwLDIuMTYsNS4xOUg2NC42NmE5LjA5LDkuMDksMCwwLDAsMC0xOC4xOEwyMS44Miw3LjEyaDBhMTUuMTUsMTUuMTUsMCwwLDAtLjA1LDMwLjI5bDE4LjU0LjA3WiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTAuNiAtMS4wNykiLz4KICA8L2c+Cjwvc3ZnPgo=';
	
	add_menu_page( $menu_titles['index'], $menu_titles['index'], 'manage_options', 'start-booking', 'start_booking_admin_index', $icon, 61 );
	
	add_submenu_page( 'start-booking', $menu_titles['index'], $menu_titles['index'], 'manage_options', 'start-booking', 'start_booking_admin_index' );
}
add_action( 'admin_menu', 'cbsb_main_menu' );

function start_booking_admin_index() {
	$setup = ( isset( $_GET['welcome'] ) && $_GET['welcome'] );
	if ($setup) {
		echo "<div id='start-booking-welcome'></div>";	
	} else {
		echo "<div id='start-booking'></div>";
	}
	
	add_action( 'admin_footer', 'cbsb_suppress_notices' );
}

function cbsb_class_redirect() {
	echo "<script type='text/javascript'>setTimeout( function() { window.location = window.startbooking.app_url + 'classes?utm_source=plugin&utm_medium=handoff&utm_content=classes'; }, 1000 );</script>";
}

function cbsb_depricated_page_redirects() {
	if ( is_admin() ) {
		$old_pages = array(
			'cbsb-dashboard' => array (
				'page' => 'start-booking',
				'hash' => '/'
			),
			'cbsb-services' => array (
				'page' => 'start-booking',
				'hash' => '/services'
			),
			'cbsb-classes' => array (
				'page' => 'start-booking',
				'hash' => '/'
			),
			'cbsb-editor' => array (
				'page' => 'start-booking',
				'hash' => '/'
			),
			'cbsb-settings' => array (
				'page' => 'start-booking',
				'hash' => '/settings'
			),
			'cbsb-connect' => array (
				'page' => 'start-booking',
				'hash' => '/'
			),
			'cbsb-signup' => array (
				'page' => 'start-booking',
				'hash' => '/'
			),
			'cbsb-pricing' => array (
				'page' => 'start-booking',
				'hash' => '/'
			),
			'cbsb-account' => array (
				'page' => 'start-booking',
				'hash' => '/settings'
			),
			'cbsb-onboarding' => array (
				'page' => 'start-booking&welcome=true',
				'hash' => '/'
			)
		);

		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], array_keys( $old_pages ) ) ) {
			$redirect = $old_pages[ $_GET['page'] ];
			if ( wp_redirect( admin_url( 'admin.php' . '?page=' . $redirect['page'] . '#' . $redirect['hash']  ) ) ) {
				exit;
			}
		}
	}
}
add_action( 'init', 'cbsb_depricated_page_redirects', 5 );