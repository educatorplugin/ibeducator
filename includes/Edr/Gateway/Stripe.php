<?php

class Edr_Gateway_Stripe extends Edr_Gateway_Base {
	/**
	 * Setup payment gateway.
	 */
	public function __construct() {
		$this->id = 'stripe';
		$this->title = __( 'Stripe', 'ibeducator' );

		// Setup options.
		$this->init_options( array(
			'secret_key' => array(
				'type'      => 'text',
				'label'     => __( 'Secret key', 'ibeducator' ),
				'id'        => 'ib-edu-secret-key',
			),
			'publishable_key' => array(
				'type'      => 'text',
				'label'     => __( 'Publishable key', 'ibeducator' ),
				'id'        => 'ib-edu-publishable-key',
			),
			'thankyou_message' => array(
				'type'      => 'textarea',
				'label'     => __( 'Thank you message', 'ibeducator' ),
				'id'        => 'ib-edu-thankyou-message',
				'rich_text' => true,
			),
		) );

		add_action( 'ib_educator_pay_' . $this->get_id(), array( $this, 'pay_page' ) );
		add_action( 'ib_educator_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'ib_educator_request_stripe_token', array( $this, 'process_stripe_token' ) );
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
		$redirect = '';

		if ( $payment->ID ) {
			$redirect = ib_edu_get_endpoint_url( 'edu-pay', $payment->ID, get_permalink( ib_edu_page_id( 'payment' ) ) );
		} else {
			$redirect = ib_edu_get_endpoint_url( 'edu-pay', '', get_permalink( ib_edu_page_id( 'payment' ) ) );
		}

		return array(
			'status'   => 'pending',
			'redirect' => $redirect,
			'payment'  => $payment,
		);
	}

	/**
	 * Output the Stripe's payment dialog.
	 * Step 2 in the payment process.
	 */
	public function pay_page() {
		$payment_id = absint( get_query_var( 'edu-pay' ) );

		if ( ! $payment_id ) {
			return;
		}

		$user = wp_get_current_user();

		if ( 0 == $user->ID ) {
			return;
		}

		$payment = edr_get_payment( $payment_id );

		if ( ! $payment->ID || $user->ID != $payment->user_id ) {
			// The payment must exist and it must be associated with the current user.
			return;
		}

		if ( 'course' == $payment->payment_type ) {
			$post = get_post( $payment->course_id );
		} elseif ( 'membership' == $payment->payment_type ) {
			$post = get_post( $payment->object_id );
		}

		if ( ! $post ) {
			return;
		}
		?>
		<p id="ib-edu-payment-processing-msg">
			<?php _e( 'The payment is getting processed...', 'ibeducator' ); ?>
		</p>
		<script src="https://checkout.stripe.com/checkout.js"></script>
		<script>
		(function($) {
			var handler = StripeCheckout.configure({
				key: <?php echo json_encode( $this->get_option( 'publishable_key' ) ); ?>,
				image: '',
				email: <?php echo json_encode( $user->user_email ); ?>,
				token: function(token) {
					$.ajax({
						type: 'POST',
						cache: false,
						url: <?php echo json_encode( ib_edu_request_url( 'stripe_token' ) ); ?>,
						data: {
							payment_id: <?php echo intval( $payment->ID ); ?>,
							token: token.id,
							_wpnonce: <?php echo json_encode( wp_create_nonce( 'ib_educator_stripe_token' ) ); ?>
						},
						success: function(response) {
							if (response === '1') {
								$('#ib-edu-payment-processing-msg').text(<?php echo json_encode( __( 'Redirecting to the payment summary page...', 'ibeducator' ) ); ?>);
								var redirectTo = <?php echo json_encode( ib_edu_get_endpoint_url( 'edu-thankyou', $payment->ID, get_permalink( ib_edu_page_id( 'payment' ) ) ) ); ?>;
								document.location = redirectTo;
							}
						}
					});
				}
			});

			handler.open({
				name: <?php echo json_encode( esc_html( $post->post_title ) ); ?>,
				description: <?php echo json_encode( ib_edu_format_price( $payment->amount, false, false ) ); ?>,
				currency: <?php echo json_encode( ib_edu_get_currency() ); ?>,
				amount: <?php echo absint( $payment->amount * 100 ); ?>
			});

			$(window).on('popstate', function() {
				handler.close();
			});
		})(jQuery);
		</script>
		<?php
	}

	/**
	 * Output thank you information.
	 */
	public function thankyou_page() {
		// Thank you message.
		$thankyou_message = $this->get_option( 'thankyou_message' );

		if ( ! empty( $thankyou_message ) ) {
			echo '<div class="ib-edu-payment-description">' . wpautop( stripslashes( $thankyou_message ) ) . '</div>';
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
				case 'thankyou_message':
					$input[ $option_name ] = wp_kses_data( $value );
					break;

				case 'secret_key':
				case 'publishable_key':
					$input[ $option_name ] = sanitize_text_field( $value );
					break;
			}
		}

		return $input;
	}

	/**
	 * Charge the card using Stripe.
	 * It's an AJAX action.
	 */
	public function process_stripe_token() {
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ib_educator_stripe_token' ) ) {
			exit( '0' );
		}

		if ( ! isset( $_POST['token'] ) || ! isset( $_POST['payment_id'] ) ) {
			exit( '0' );
		}

		$user = wp_get_current_user();

		if ( 0 == $user->ID ) {
			exit( '0' );
		}

		$payment = edr_get_payment( $_POST['payment_id'] );

		if ( ! $payment->ID || $user->ID != $payment->user_id ) {
			// The payment must exist and it must be associated with the current user.
			exit( '0' );
		}

		require_once IBEDUCATOR_PLUGIN_DIR . 'lib/Stripe/Stripe.php';

		$token = $_POST['token'];
		$amount = round( (float) $payment->amount, 2 );
		$description = sprintf( __( 'Payment #%d', 'ibeducator' ), $payment->ID );

		if ( 'course' == $payment->payment_type ) {
			$description .= ' , ' . get_the_title( $payment->course_id );
		} elseif ( 'membership' == $payment->payment_type ) {
			$description .= ' , ' . get_the_title( $payment->object_id );
		}

		try {
			Stripe::setApiKey( $this->get_option( 'secret_key' ) );
			Stripe_Charge::create( array(
				'amount'      => $amount * 100,
				'currency'    => $payment->currency,
				'card'        => $token,
				'description' => $description,
			) );

			// Update the payment status.
			$payment->payment_status = 'complete';
			$payment->save();

			// Setup course or membership for the student.
			IB_Educator::get_instance()->setup_payment_item( $payment );

			exit( '1' );
		} catch ( Exception $e ) {}

		exit( '0' );
	}
}
