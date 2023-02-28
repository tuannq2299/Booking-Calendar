<?php

class StartBooking_Group_Booking extends WP_Widget {
	function __construct() {
		$widget_ops = array(
			'description' => __( 'Start Booking Classes', 'calendar-booking' ),
		);
		parent::__construct( false, __( 'Start Booking Classes', 'calendar-booking' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		add_action( 'wp_footer', 'cbsb_fe_styles' );
		add_action( 'wp_footer', 'cbsb_fe_script' );
		echo $args['before_widget'];
		if ( isset( $instance['title'] ) && ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . $instance['title'] . $args['after_title'];
		}
		$markup = '
		<div id="startbooking-flow">
			<div id="startbooking-class-flow">
				' . cbsb_get_application_loader() . '
			</div>
		</div>';
		echo $markup;
		echo $args['after_widget'];
	}

	function form( $instance ) {
		$instance = wp_parse_args( $instance, array( 'title' => null ) );
		?>
		<p>
			<label for="<?php echo $this->get_field_name( 'title' ); ?>"><?php _e( 'Title', 'calendar-booking' ); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<p><?php _e( 'Please use the Start Booking dashboard for class schedules and configuration.', 'calendar-booking' ); ?></p>
		<?php
	}
}