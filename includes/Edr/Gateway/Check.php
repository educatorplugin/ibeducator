<?php

class Edr_Gateway_Check extends Edr_Gateway_Base {
	/**
	 * Setup payment gateway.
	 */
	public function __construct() {
		$this->id = 'check';
		$this->title = __( 'Check', 'ibeducator' );

		// Setup options.
		$this->init_options( array(
			'description' => array(
				'type'      => 'textarea',
				'label'     => __( 'Instructions for a student', 'ibeducator' ),
				'id'        => 'ib-edu-description',
				'rich_text' => true,
			)
		) );

		add_action( 'ib_educator_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
	}

	/**
	 * Process payment.
	 *
	 * @return array
	 */
	public function process_payment( $object_id, $user_id = 0, $payment_type = 'course', $atts = array() ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return array( 'redirect' => home_url( '/' ) );
		}

		$payment = $this->create_payment( $object_id, $user_id, $payment_type, $atts );
		$redirect_args = array();

		if ( $payment->ID ) {
			$redirect_args['value'] = $payment->ID;
		}

		return array(
			'status'   => 'pending',
			'redirect' => $this->get_redirect_url( $redirect_args ),
			'payment'  => $payment,
		);
	}

	/**
	 * Output thank you information.
	 */
	public function thankyou_page() {
		$description = $this->get_option( 'description' );

		if ( ! empty( $description ) ) {
			echo '<h3>' . __( 'Payment Instructions', 'ibeducator' ) . '</h3>';
			echo '<div class="ib-edu-payment-description">' . wpautop( stripslashes( $description ) ) . '</div>';
		}
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_admin_options( $input ) {
		foreach ( $input as $option_name => $value ) {
			switch ( $option_name ) {
				case 'description':
					$input[ $option_name ] = wp_kses_data( $value );
					break;
			}
		}

		return $input;
	}
}
