<?php
$user_id = get_current_user_id();

if ( ( $thankyou = get_query_var( 'edu-thankyou' ) ) ) {
	// Thank you page, payment summary.
	if ( ! $user_id ) {
		return;
	}

	if ( ! is_numeric( $thankyou ) ) {
		return;
	}
	
	$payment = IB_Educator_Payment::get_instance( $thankyou );

	if ( ! $payment->ID || $payment->user_id != $user_id ) {
		return;
	}

	$post = null;
	
	if ( 'course' == $payment->payment_type ) {
		$post = get_post( $payment->course_id );
	} elseif ( 'membership' == $payment->payment_type ) {
		$post = get_post( $payment->object_id );
	}
	
	if ( ! $post || ! in_array( $post->post_type, array( 'ib_educator_course', 'ib_edu_membership' ) ) ) {
		return;
	}

	$lines = $payment->get_lines();
	?>
	<h3><?php _e( 'Payment Summary', 'ibeducator' ); ?></h3>

	<dl id="payment-details" class="edu-dl">
		<dt class="payment-id"><?php _e( 'Payment', 'ibeducator' ); ?></dt>
		<dd><?php echo intval( $payment->ID ); ?></dd>

		<dt class="payment-date"><?php _e( 'Date', 'ibeducator' ); ?></dt>
		<dd><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $payment->payment_date ) ) ); ?></dd>

		<dt class="payment-status"><?php _e( 'Payment Status', 'ibeducator' ); ?></dt>
		<dd>
			<?php
				$statuses = IB_Educator_Payment::get_statuses();

				if ( array_key_exists( $payment->payment_status, $statuses ) ) {
					echo esc_html( $statuses[ $payment->payment_status ] );
				}
			?>
		</dd>
	</dl>

	<table class="edu-payment-table">
		<thead>
			<tr>
				<th><?php _e( 'Item', 'ibeducator' ); ?></th>
				<th><?php _e( 'Price', 'ibeducator' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php echo esc_html( $post->post_title ); ?></td>
				<td><?php echo ib_edu_format_price( $payment->amount - $payment->tax, false ); ?></td>
			</tr>
		</tbody>
	</table>

	<dl class="edu-payment-summary edu-dl">
		<?php
			if ( $payment->tax > 0.0 ) {
				echo '<dt class="payment-subtotal">' . __( 'Subtotal', 'ibeducator' ) .'</dt><dd>' . ib_edu_format_price( $payment->amount - $payment->tax, false ) . '</dd>';

				foreach ( $lines as $line ) {
					if ( 'tax' == $line->line_type ) {
						echo '<dt>' . esc_html( $line->name ) . '</dt><dd>' . ib_edu_format_price( $line->amount, false ) . '</dd>';
					}
				}
			}
		?>

		<dt class="payment-total"><?php _e( 'Total', 'ibeducator' ); ?></dt>
		<dd><?php echo ib_edu_format_price( $payment->amount, false ) ?></dd>
	</dl>
	<?php
	if ( $payment->ID && $payment->user_id == $user_id ) {
		do_action( 'ib_educator_thankyou_' . $payment->payment_gateway );
	}

	// Show link to the payments page.
	$payments_page = get_post( ib_edu_page_id( 'user_payments' ) );
	
	if ( $payments_page ) {
		echo '<p>' . sprintf( __( 'Go to %s page', 'ibeducator' ), '<a href="' . esc_url( get_permalink( $payments_page->ID ) ) . '">'
			. esc_html( $payments_page->post_title ) . '</a>' ) . '</p>';
	}
} else if ( ( $pay = get_query_var( 'edu-pay' ) ) ) {
	// Can be used for step 2 of the payment process.
	// PayPal gateway uses it.
	if ( ! is_numeric( $pay ) ) {
		return;
	}

	$payment = IB_Educator_Payment::get_instance( $pay );

	// The payment must exist and it must belong to the current user.
	if ( $payment->ID && $payment->user_id == $user_id ) {
		do_action( 'ib_educator_pay_' . $payment->payment_gateway );
	}
} else {
	// Step 1 of the payment process.
	$object_id = get_query_var( 'edu-course' );
	$post = null;

	if ( ! is_numeric( $object_id ) && isset( $_POST['course_id'] ) ) {
		$object_id = intval( $_POST['course_id'] );
	}

	if ( $object_id ) {
		$post = get_post( $object_id );
	} else {
		// No course id? Try to get membership id.
		$object_id = get_query_var( 'edu-membership' );

		if ( ! is_numeric( $object_id ) && isset( $_POST['membership_id'] ) ) {
			$object_id = intval( $_POST['membership_id'] );
		}

		if ( $object_id ) {
			$post = get_post( $object_id );
		}
	}

	// Check whether the selected item exists.
	if ( ! $post || ! in_array( $post->post_type, array( 'ib_educator_course', 'ib_edu_membership' ) ) ) {
		echo '<p>' . __( 'Please select a course to continue.', 'ibeducator' ) . '</p>';
		return;
	}

	// Determine whether a user can pay for the item.
	$access_status = '';

	if ( 'ib_educator_course' == $post->post_type ) {
		$access_status = IB_Educator::get_instance()->get_access_status( $post->ID, $user_id );

		if ( ! in_array( $access_status, array( 'course_complete', 'forbidden' ) ) ) {
			echo '<p>' . ib_edu_get_access_status_message( $access_status ) . '</p>';
			return;
		}
	}

	// Show a login link to non-authenticated users.
	if ( ! $user_id ) {
		$login_url = apply_filters( 'ib_educator_login_url', '' );

		if ( empty( $login_url ) ) {
			if ( 'ib_educator_course' == $post->post_type ) {
				$login_url = wp_login_url( ib_edu_get_endpoint_url( 'edu-course', $post->ID, get_permalink() ) );
			} elseif ( 'ib_edu_membership' == $post->post_type ) {
				$login_url = wp_login_url( ib_edu_get_endpoint_url( 'edu-membership', $post->ID, get_permalink() ) );
			}
		}

		echo '<p>' . __( 'Already have an account?', 'ibeducator' ) . ' <a href="' . esc_url( $login_url ) . '">' . __( 'Log in', 'ibeducator' ) . '</a></p>';
	}

	// Output error messages.
	$errors = ib_edu_message( 'payment_errors' );
	$error_codes = $errors ? $errors->get_error_codes() : array();

	if ( ! empty( $error_codes ) ) {
		$messages = $errors->get_error_messages();

		foreach ( $messages as $message ) {
			echo '<div class="ib-edu-message error">' . $message . '</div>';
		}
	}

	$form_action = ib_edu_get_endpoint_url( 'edu-action', 'payment', get_permalink() );
	?>
		<form id="ib-edu-payment-form" class="ib-edu-form" action="<?php echo esc_url( $form_action ); ?>" method="post">
			<?php
				wp_nonce_field( 'ibedu_submit_payment' );

				/**
				 * Hook into payment form output.
				 *
				 * @param null|WP_Error $errors
				 * @param mixed $post
				 */
				do_action( 'ib_educator_register_form', $errors, $post );
			?>

			<fieldset>
				<legend><?php _e( 'Payment Information', 'ibeducator' ); ?></legend>

				<?php
					$args = array();
					$billing = IB_Educator::get_instance()->get_billing_data( $user_id );

					// Get country.
					if ( isset( $_POST['billing_country'] ) ) $args['country'] = $_POST['billing_country'];
					elseif ( ! empty( $billing['country'] ) ) $args['country'] = $billing['country'];
					else $args['country'] = ib_edu_get_location( 'country' );

					// Get state.
					if ( isset( $_POST['billing_state'] ) ) $args['state'] = $_POST['billing_state'];
					elseif ( ! empty( $billing['state'] ) ) $args['state'] = $billing['state'];
					else $args['state'] = ib_edu_get_location( 'state' );

					// Get price.
					if ( 'ib_educator_course' == $post->post_type ) $args['price'] = ib_edu_get_course_price( $post->ID );
					elseif ( 'ib_edu_membership' == $post->post_type ) $args['price'] = IB_Educator_Memberships::get_instance()->get_price( $post->ID );

					// Output payment summary.
					echo '<div id="edu-payment-info" class="edu-payment-info">' . IB_Educator_Account::payment_info( $post, $args ) . '</div>';

					// Payment gateways.
					$gateways = IB_Educator_Main::get_gateways();
				?>

				<?php if ( $args['price'] && ! empty( $gateways ) ) : ?>
					<div class="ib-edu-form-field<?php if ( in_array( 'empty_payment_method', $error_codes ) ) echo ' error'; ?>">
						<label><?php _e( 'Payment Method', 'ibeducator' ); ?> <span class="required">*</span></label>

						<div class="ib-edu-form-control">
							<ul class="ib-edu-payment-method">
								<?php
									$current_gateway_id = isset( $_POST['payment_method'] ) ? $_POST['payment_method'] : '';

									foreach ( $gateways as $gateway_id => $gateway ) {
										if ( 'free' == $gateway_id ) {
											continue;
										}

										$checked = '';

										if ( ! empty( $current_gateway_id ) && $current_gateway_id === $gateway_id ) {
											$checked = ' checked';
										} elseif ( empty( $current_gateway_id ) && $gateway->is_default() ) {
											$checked = ' checked';
										}
										?>
										<li>
											<label>
												<input
													type="radio"
													name="payment_method"
													value="<?php echo esc_attr( $gateway_id ); ?>"
													<?php echo $checked ?>> <?php echo esc_html( $gateway->get_title() ); ?>
											</label>
										</li>
										<?php
									}
								?>
							</ul>
						</div>
					</div>
				<?php elseif ( 0.0 == $args['price'] ) : ?>
					<input type="hidden" name="payment_method" value="free">
				<?php endif; ?>
			</fieldset>

			<div class="ib-edu-form-actions">
				<button type="submit" class="ib-edu-button"><?php _e( 'Continue', 'ibeducator' ) ?></button>
			</div>
		</form>
	<?php
}
?>