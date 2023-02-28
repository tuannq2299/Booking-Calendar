<?php

class StartBooking_Single_Service extends WP_Widget {
	function __construct() {
		$widget_ops = array(
			'description' => __( 'Start Booking Single Service', 'calendar-booking' ),
		);
		parent::__construct( false, __( 'Start Booking Single Service', 'calendar-booking' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		cbsb_load_single_service_block_scripts_styles();
		if ( ! isset( $instance['service'] ) || empty( $instance['service'] ) ) {
			return '<div><p style="color: red; font-weight:bold;">Service is a required parameter with single service widget.</p></div>';
		}

		if ( ! isset( $instance['details'] ) || ! in_array( $instance['details'], array( 'true', 'false' ) ) ) {
			$instance['details'] = 'false';
		}

		$markup = '<div id="startbooking-block-single-service" class="wp-block-calendar-booking-single-service-flow"
			data-block-service="' . $instance['service'] . '"
			data-block-display-service="' . $instance['details'] . '"></div>';

		echo $args['before_widget'];
		if ( isset( $instance['title'] ) && ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . $instance['title'] . $args['after_title'];
		}
		
		echo $markup;
		echo $args['after_widget'];
	}

	function form( $instance ) {
		global $cbsb;
		
		$defaults = array(
			'title'    => null,
			'service'  => null,
			'details'  => true,
		);
		
		$services = $cbsb->get( 'services' );
		$services = $services['data']->all_services;
		$instance = wp_parse_args( $instance, $defaults );

		?>
		<p>
			<label for="<?php echo $this->get_field_name( 'title' ); ?>"><?php _e( 'Title', 'calendar-booking' ); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_name( 'service' ); ?>"><?php _e( 'Services', 'calendar-booking' ); ?>:</label>
			<select name="<?php echo $this->get_field_name( 'service' ); ?>">
				<option><?php _e( 'Select a Service', 'calendar-booking' ); ?></option>
				<?php
				foreach ( $services as $service ) {
					?>
					<option value="<?php echo $service->uid; ?>" <?php selected( $service->uid, $instance['service'], true ); ?>><?php _e( $service->name, 'calendar-booking' ); ?></option>
					<?php
				}
				?>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_name( 'details' ); ?>"><?php _e( 'Service Details', 'calendar-booking' ); ?>:</label>
			<select name="<?php echo $this->get_field_name( 'details' ); ?>">
				<option value="true" <?php selected( 'true', $instance['details'], true ); ?>><?php _e( 'Show Details', 'calendar-booking' ); ?></option>
				<option value="false" <?php selected( 'false', $instance['details'], true ); ?>><?php _e( 'Skip Details', 'calendar-booking' ); ?></option>
			</select>
		</p>

		<?php
	}
}