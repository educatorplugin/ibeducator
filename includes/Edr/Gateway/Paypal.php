<?php

class Edr_Gateway_Paypal extends Edr_Gateway_Base {
	protected $business_email;
	protected $live_url;
	protected $test_url;
	protected $notify_url;
	protected $test;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id = 'paypal';
		$this->title = __( 'PayPal', 'ibeducator' );
		$this->live_url = 'https://www.paypal.com/cgi-bin/webscr';
		$this->test_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';

		$this->init_options( array(
			'business_email' => array(
				'type'  => 'text',
				'label' => __( 'Business Email', 'ibeducator' ),
				'id'    => 'ib-edu-business-email',
			),

			'test' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Test', 'ibeducator' ),
				'description' => __( 'If checked, Educator will use PayPal sandbox URL and PayPal payments will be in testing mode.', 'ibeducator' ),
				'id'          => 'ib-edu-test',
			),

			'thankyou_message' => array(
				'type'      => 'textarea',
				'label'     => __( 'Thank you message', 'ibeducator' ),
				'id'        => 'ib-edu-thankyou-message',
				'rich_text' => true,
			),
		) );

		add_action( 'ib_educator_pay_' . $this->get_id(), array( $this, 'pay_page' ) );
		add_action( 'ib_educator_thankyou_' . $this->get_id(), array( $this, 'thankyou_page' ) );
		add_action( 'ib_educator_request_paypalipn', array( $this, 'process_ipn' ) );
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
	 * Output the form to the step 2 (pay page) of the payment page.
	 */
	public function pay_page() {
		$action_url = ( $this->get_option( 'test' ) ) ? $this->test_url : $this->live_url;
		$payment_id = absint( get_query_var( 'edu-pay' ) );

		if ( ! $payment_id ) {
			return;
		}

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$payment = edr_get_payment( $payment_id );

		// The payment must exist in the database
		// and it must belong to the current user.
		if ( ! $payment->ID || $user_id != $payment->user_id ) {
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

		$amount = $payment->amount - $payment->tax;
		$return_url = '';
		$payment_page_id = ib_edu_page_id( 'payment' );

		if ( $payment_page_id ) {
			$return_url = ib_edu_get_endpoint_url( 'edu-thankyou', ( $payment->ID ? $payment->ID : '' ), get_permalink( $payment_page_id ) );
		}

		echo '<form id="ib-edu-paypal-form" action="' . esc_url( $action_url ) . '" method="post">';
		echo '<input type="hidden" name="cmd" value="_xclick">';
		echo '<input type="hidden" name="charset" value="utf-8">';
		echo '<input type="hidden" name="business" value="' . esc_attr( $this->get_option( 'business_email' ) ) . '">';
		echo '<input type="hidden" name="return" value="' . esc_url( $return_url ) . '">';
		echo '<input type="hidden" name="notify_url" value="' . esc_url( ib_edu_request_url( 'paypalipn' ) ) . '">';
		echo '<input type="hidden" name="currency_code" value="' . esc_attr( ib_edu_get_currency() ) . '">';
		echo '<input type="hidden" name="item_name" value="' . esc_attr( $post->post_title ) . '">';
		echo '<input type="hidden" name="item_number" value="' . absint( $payment->ID ) . '">';

		if ( $payment->tax ) {
			echo '<input type="hidden" name="tax" value="' . (float) $payment->tax . '">';
		}

		echo '<input type="hidden" name="amount" value="' . (float) $amount . '">';
		echo '<div id="paypal-form-buttons"><button type="submit">' . __( 'Continue', 'ibeducator' ) . '</button></div>';
		echo '</form>';
		echo '<div id="paypal-redirect-notice" style="display: none;">' . __( 'Redirecting to PayPal...', 'ibeducator' ) . '</div>';
		echo '<script>(function() {
			function goToPayPal() {
				document.getElementById("paypal-form-buttons").style.display = "none";
				document.getElementById("paypal-redirect-notice").style.display = "block";
				document.getElementById("ib-edu-paypal-form").submit();
			}

			if ( typeof jQuery === "undefined" ) {
				setTimeout(goToPayPal, 500);
			} else {
				jQuery(document).on("ready", function() {
					goToPayPal();
				});
			}
		})();</script>';
	}

	public function thankyou_page() {
		// Thank you message.
		$thankyou_message = $this->get_option( 'thankyou_message' );

		if ( ! empty( $thankyou_message ) ) {
			echo '<div class="ib-edu-payment-description">' . wpautop( stripslashes( $thankyou_message ) ) . '</div>';
		}
	}

	public function process_ipn() {
		$debug = 0;
		$log_file = IBEDUCATOR_PLUGIN_DIR . 'ipn.log';
		
		// Read POST data
		// reading posted data directly from $_POST causes serialization
		// issues with array data in POST. Reading raw POST data from input stream instead.
		$raw_post_data = file_get_contents( 'php://input' );
		$raw_post_array = explode( '&', $raw_post_data );
		$myPost = array();
		
		foreach ( $raw_post_array as $keyval ) {
			$keyval = explode ('=', $keyval);

			if ( 2 == count( $keyval ) ) {
				$myPost[ $keyval[0] ] = urldecode( $keyval[1] );
			}
		}

		// read the post from PayPal system and add 'cmd'
		$req = 'cmd=_notify-validate';
		
		if ( function_exists( 'get_magic_quotes_gpc' ) ) {
			$get_magic_quotes_exists = true;
		} else {
			$get_magic_quotes_exists = false;
		}
		
		foreach ( $myPost as $key => $value ) {
			if( true == $get_magic_quotes_exists && 1 == get_magic_quotes_gpc() ) {
				$value = urlencode( stripslashes( $value ) );
			} else {
				$value = urlencode( $value );
			}

			$req .= "&$key=$value";
		}

		// Post IPN data back to PayPal to validate the IPN data is genuine.
		// Without this step anyone can fake IPN data.
		if ( $this->get_option( 'test' ) ) {
			$paypal_url = $this->test_url;
		} else {
			$paypal_url = $this->live_url;
		}

		$ch = curl_init( $paypal_url );

		if ( ! $ch ) {
			return;
		}

		curl_setopt( $ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1 );
		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER,1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $req );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 1 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 2 );
		curl_setopt( $ch, CURLOPT_FORBID_REUSE, 1 );

		if ( $debug ) {
			curl_setopt( $ch, CURLOPT_HEADER, 1 );
			curl_setopt( $ch, CURLINFO_HEADER_OUT, 1 );
		}

		// Set TCP timeout to 30 seconds.
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 30 );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Connection: Close' ) );

		$res = curl_exec( $ch );

		if ( 0 != curl_errno( $ch ) ) {
			if ( true == $debug ) {
				error_log( date( '[Y-m-d H:i e] ' ) . 'Can\'t connect to PayPal to validate IPN message: ' . curl_error( $ch ) . PHP_EOL, 3, $log_file );
			}

			curl_close( $ch );
			exit;
		} else {
			// Log the entire HTTP response if debug is switched on.
			if ( $debug ) {
				error_log( date( '[Y-m-d H:i e] ' ). 'HTTP request of validation request:' . curl_getinfo( $ch, CURLINFO_HEADER_OUT ) . ' for IPN payload: ' . $req . PHP_EOL, 3, $log_file );
				error_log( date( '[Y-m-d H:i e] ' ). 'HTTP response of validation request: ' . $res . PHP_EOL, 3, $log_file );
			}

			curl_close( $ch );
		}

		// Inspect IPN validation result and act accordingly.
		if ( false !== strpos( $res, 'VERIFIED' ) ) {
			if ( isset( $_POST['payment_status'] ) ) {
				$payment_id = ! isset( $_POST['item_number'] ) ? 0 : absint( $_POST['item_number'] );
				$currency = ! isset( $_POST['mc_currency'] ) ? '' : $_POST['mc_currency'];
				$receiver_email = ! isset( $_POST['receiver_email'] ) ? '' : $_POST['receiver_email'];
				$payment_amount = ! isset( $_POST['mc_gross'] ) ? '' : $_POST['mc_gross'];

				if ( $receiver_email != $this->get_option( 'business_email' ) ) {
					return;
				}

				if ( 0 == $payment_id ) {
					return;
				}

				$payment = edr_get_payment( $payment_id );

				if ( ! $payment->ID ) {
					return;
				}

				if ( $payment_amount != $payment->amount ) {
					return;
				}

				if ( $currency != $payment->currency ) {
					return;
				}

				switch ( $_POST['payment_status'] ) {
					case 'Completed':
						// Update payment status.
						$payment->payment_status = 'complete';

						if ( isset( $_POST['txn_id'] ) ) {
							$payment->txn_id = sanitize_text_field( $_POST['txn_id'] );
						}

						$payment->save();
						
						// Setup course or membership for the student.
						IB_Educator::get_instance()->setup_payment_item( $payment );
						break;

					case 'Failed':
					case 'Expired':
					case 'Denied':
					case 'Voided':
						// Update payment status.
						$payment->payment_status = 'failed';
						$payment->save();
						break;
				}
			}
			
			if ( $debug ) {
				error_log( date( '[Y-m-d H:i e] ' ) . 'Verified IPN: ' . $req . PHP_EOL, 3, $log_file );
			}
		} else if ( 0 == strcmp( $res, 'INVALID' ) ) {
			if ( $debug ) {
				error_log( date( '[Y-m-d H:i e] ' ) . 'Invalid IPN: ' . $req . PHP_EOL, 3, $log_file );
			}
		}
	}

	public function sanitize_admin_options( $input ) {
		foreach ( $input as $option_name => $value ) {
			switch ( $option_name ) {
				case 'business_email':
					$input[ $option_name ] = sanitize_email( $value );
					break;

				case 'thankyou_message':
					$input[ $option_name ] = wp_kses_data( $value );
					break;

				case 'test':
					if ( 1 != $value ) {
						$input[ $option_name ] = 0;
					}
					break;
			}
		}

		return $input;
	}
}
