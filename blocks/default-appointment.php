<?php

if ( ! defined( 'WPINC' ) ) { die; }

class CBSB_Block_Default_Appointment {
	public $slug = 'calendar-booking/default-booking-flow';
	public $deps = array( 'wp-blocks', 'wp-i18n', 'wp-element' );
	public $enqueued = false;

	function init() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active.
			return;
		}
		add_action( 'admin_init', array( $this, 'logic_admin' ) );
		add_action( 'the_posts', array( $this, 'logic_list' ) );
		add_action( 'the_post', array( $this, 'logic_single' ) );
		$this->register_block();
	}

	function logic_admin() {
		if ( $this->enqueued == false ) {
			$this->register_styles();
			$this->register_scripts();
			$this->enqueue();
		}
	}

	function logic_list( $posts ) {
		foreach ( $posts as $post ) {
			if ( function_exists( 'has_block' ) && has_block( $this->slug, $post ) && $this->enqueued == false ) {
				$this->register_styles();
				$this->register_scripts();
				$this->enqueue();
			}
		}
		return $posts;
	}

	function logic_single( $post ) {
		if ( function_exists( 'has_block' ) && has_block( $this->slug, $post ) && $this->enqueued == false ) {
			$this->register_styles();
			$this->register_scripts();
			$this->enqueue();
		}
	}

	function register_styles() {
		wp_register_style(
			'cbsb-default-booking-flow-editor-style',
			CBSB_BASE_URL . 'public/css/admin/block-editor.css',
			array(),
			CBSB_VERSION
		);

		wp_register_style(
			'cbsb-default-booking-flow-style',
			CBSB_BASE_URL . 'public/css/flows/default.css',
			array(),
			CBSB_VERSION
		);
	}

	function register_scripts() {
		wp_register_script(
			'cbsb-stripe-v3',
			'https://js.stripe.com/v3/',
			array(),
			CBSB_VERSION
		);

		wp_register_script(
			'cbsb-default-booking-flow-editor',
			CBSB_BASE_URL . 'public/js/blocks/default-appointment.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element' ),
			CBSB_VERSION
		);

		wp_register_script(
			'cbsb-default-booking-flow',
			CBSB_BASE_URL . 'public/js/flows/default-appointment.js',
			$this->deps,
			CBSB_VERSION,
			true
		);
	}

	function enqueue() {
		$this->enqueued = true;

		global $cbsb;
		$account = $cbsb->get( 'account/details' );

		if ( 'success' == $account['status'] &&
			isset( $account['data'] ) &&
			property_exists( $account['data'], 'payments' ) &&
			property_exists( $account['data']->payments, 'payment_key' ) &&
			is_null( $account['data']->payments->payment_key ) == false ) {
				$this->deps[] = 'cbsb-stripe-v3';
				wp_deregister_script( 'cbsb-default-booking-flow' );
				wp_register_script(
					'cbsb-default-booking-flow',
					CBSB_BASE_URL . 'public/js/flows/default-appointment.js',
					$this->deps,
					CBSB_VERSION,
					true
				);
		}

		$startbooking_js_global = cbsb_get_startbooking_global();
		wp_add_inline_script( 'cbsb-default-booking-flow-editor', 'var startbooking = ' . json_encode( $startbooking_js_global ), 'before' );
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'cbsb-default-booking-flow-editor', 'calendar-booking', CBSB_BASE_DIR . 'languages/json' );
			wp_set_script_translations( 'cbsb-default-booking-flow', 'calendar-booking', CBSB_BASE_DIR . 'languages/json' );
		}
		wp_localize_script( 'cbsb-default-booking-flow', 'startbooking', $startbooking_js_global );
	}

	function register_block() {
		register_block_type( $this->slug, array(
			'editor_style'  => 'cbsb-default-booking-flow-editor-style',
			'editor_script' => 'cbsb-default-booking-flow-editor',
			'script'        => 'cbsb-default-booking-flow',
			'style'         => 'cbsb-default-booking-flow-style'
		) );
	}
}
add_action( 'init', array( new CBSB_Block_Default_Appointment, 'init' ) );
