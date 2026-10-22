<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget wrapper around the [on_this_day] shortcode's renderer.
 */
class OnThisDay_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'on_this_day_widget',
			__( 'On This Day', 'on-this-day' ),
			array(
				'description' => __( 'Displays historical events that happened on today\'s date.', 'on-this-day' ),
			)
		);
	}

	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$count = ! empty( $instance['count'] ) ? (int) $instance['count'] : (int) get_option( 'onthisday_default_count', 15 );
		$count = max( 1, min( 100, $count ) );
		$mode  = isset( $instance['mode'] ) && in_array( $instance['mode'], OnThisDay_Events_API::MODES, true ) ? $instance['mode'] : 'mix';

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo OnThisDay_Shortcode::render_html( $count, $mode ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		$count = isset( $instance['count'] ) ? $instance['count'] : '';
		$mode  = isset( $instance['mode'] ) ? $instance['mode'] : 'mix';
		$modes = array(
			'mix'   => __( 'Mix: about a third faith entries (default)', 'on-this-day' ),
			'faith' => __( 'Faith entries first, then random', 'on-this-day' ),
			'only'  => __( 'Faith entries only', 'on-this-day' ),
		);
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'on-this-day' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Number of events and births (leave blank to use the default from Settings > On This Day):', 'on-this-day' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number" min="1" max="100" value="<?php echo esc_attr( $count ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'mode' ) ); ?>"><?php esc_html_e( 'Faith entries:', 'on-this-day' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'mode' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'mode' ) ); ?>">
				<?php foreach ( $modes as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $mode, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance          = array();
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['count'] = ! empty( $new_instance['count'] ) ? max( 1, min( 100, (int) $new_instance['count'] ) ) : '';
		$instance['mode']  = isset( $new_instance['mode'] ) && in_array( $new_instance['mode'], OnThisDay_Events_API::MODES, true ) ? $new_instance['mode'] : 'mix';
		return $instance;
	}
}
