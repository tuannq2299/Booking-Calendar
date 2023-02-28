<?php

class StartBooking_Services extends WP_Widget {
	function __construct() {
		$widget_ops = array(
			'description' => __( 'Start Booking Services', 'calendar-booking' ),
		);
		parent::__construct( false, __( 'Start Booking Services', 'calendar-booking' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( isset( $instance['title'] ) && ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . $instance['title'] . $args['after_title'];
		}
		cbsb_load_service_block_scripts_styles();
		$markup = '
			<div class="wp-block-calendar-booking-default-booking-flow">
				<div id="startbooking-block-default">' . cbsb_get_application_loader() . '</div>
			</div>
		';
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
		<p><?php _e( 'To edit the booking form experience, please use the ', 'calendar-booking' ); ?> <a href="<?php echo add_query_arg( array( 'page' => 'cbsb-editor' ), admin_url( 'admin.php/wp-admin/admin.php' ) ); ?>" target="_blank"><?php _e( 'form editor', 'calendar-booking' ); ?></a>.</p>
		<?php
	}
}