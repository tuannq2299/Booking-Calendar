<?php

if ( ! defined( 'WPINC' ) ) { die; }

class CBSB_Block_Call_To_Action {
	public $slug = 'calendar-booking/call-to-action';
	public $deps = array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-components' );
	public $enqueued = false;
	public $block_args = array();

	function init() {
		if ( ! function_exists( 'register_block_type' ) ) {
			// Gutenberg is not active.
			return;
		}

		$this->block_args = array(
			'editor_style'    => 'cbsb-block-editor-style',
			'editor_script'   => 'cbsb-call-to-action-editor',
			'style'           => 'cbsb-default-style',
		);
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
				$this->enqueue();
			}
		}
		return $posts;
	}

	function logic_single( $post ) {
		if ( function_exists( 'has_block' ) && has_block( $this->slug, $post ) && $this->enqueued == false ) {
			$this->register_styles();
			$this->enqueue();
		}
	}

	function register_styles() {
		wp_register_style(
			'cbsb-default-style',
			CBSB_BASE_URL . 'public/css/flows/default.css',
			array(),
			CBSB_VERSION
		);
		wp_register_style(
			'cbsb-block-editor-style',
			CBSB_BASE_URL . 'public/css/admin/block-editor.css',
			array(),
			CBSB_VERSION
		);
	}

	function register_scripts() {
		wp_register_script(
			'cbsb-call-to-action-editor',
			CBSB_BASE_URL . 'public/js/blocks/call-to-action.js',
			array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
			CBSB_VERSION
		);
	}

	function enqueue() {
		$this->enqueued = true;

		$startbooking_js_global = cbsb_get_startbooking_global();
		wp_add_inline_script( 'cbsb-call-to-action-editor', 'var startbooking = ' . json_encode( $startbooking_js_global ), 'before' );
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'cbsb-call-to-action-editor', 'calendar-booking', CBSB_BASE_DIR . 'languages/json' );
		}
	}

	function render_callback($attributes) {}

	function register_block() {
		register_block_type( $this->slug, array_filter( $this->block_args ) );
	}
}
add_action( 'init', array( new CBSB_Block_Call_To_Action, 'init' ) );
