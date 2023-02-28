<?php

class StartBooking_Hours_Widget extends WP_Widget {
	function __construct() {
		$widget_ops = array( 
			'description' => __( 'Start Booking Hours', 'calendar-booking' ),
		);
		parent::__construct( false, __( 'Start Booking Hours', 'calendar-booking' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		global $cbsb;

		wp_enqueue_style( 'startbooking-widgets', CBSB_BASE_URL . 'public/css/widgets/widgets.css' );
		$duration_icon = CBSB_BASE_URL . '/public/images/icons/time.svg';
		$hide_icon = ( isset( $instance['hide_icon'] ) && 'on' == $instance['hide_icon'] ) ? true : false;

		$details = $cbsb->get( 'account/details' );

		echo $args['before_widget'];
		$title  = $args['before_title'];
		if ( $hide_icon == false ) {
			$title .= '<img style="vertical-align:middle;margin-right:5px;" src="' . $duration_icon . '" />';
		}
		$title .= $instance['title'];
		$title .= $args['after_title'];
		echo  $title;
		if ( 'success' == $details['status'] && isset( $details['data'] ) && ! is_null( $details['data'] ) && property_exists( $details['data'], 'location_hours' ) ) {
			$hours = $details['data']->location_hours;

			echo '<dl class="working-hours">';
			foreach ( $hours as $hour ) {
				echo '<dt>' . $hour->day . '</dt>';
				if ( 'open' == $hour->day_type ) {
					echo '<dd>' . date( 'g:ia', strtotime( $hour->open_time ) ) . ' - ' . date('g:ia', strtotime( $hour->close_time ) ) . '</dd>';
				} else {
					echo '<dd>' . _e( 'Closed', 'calendar-booking' ) . '</dd>';
				}
			}
			echo '</dl>';
		}
		echo $args['after_widget'];
	}

	function form( $instance ) {
		$title = ( isset( $instance['title'] ) ) ? $instance['title'] : null;
		$hide_icon = ( isset( $instance['hide_icon'] ) && 'on' == $instance['hide_icon'] ) ? true : false;
		?>
		<p>
			<label for="<?php echo $this->get_field_name( 'title' ); ?>"><?php _e( 'Title', 'calendar-booking' ); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_name( 'hide_icon' ); ?>"><?php _e( 'Hide Icon', 'calendar-booking' ); ?>:</label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'hide_icon' ); ?>" name="<?php echo $this->get_field_name( 'hide_icon' ); ?>" type="checkbox" <?php if ($hide_icon) { echo 'checked '; } ?>/>
		</p>
		<p><?php _e( 'Your hours are loaded from your account availability at Start Booking.com.', 'calendar-booking' ); ?> <a href="<?php esc_attr_e( CBSB_APP_URL . 'account/availability' ); ?>"><?php _e( 'Manage Availability Hours', 'calendar-booking' ); ?></a></p>
		<?php
	}
}