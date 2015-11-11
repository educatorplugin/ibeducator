<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! current_user_can( 'manage_educator' ) ) {
	echo '<p>' . __( 'Access denied', 'ibeducator' ) . '</p>';
	return;
}

$payment_id = isset( $_GET['payment_id'] ) ? absint( $_GET['payment_id'] ) : 0;
$payment = edr_get_payment( $payment_id );
$payment_statuses = edr_get_payment_statuses();
$types = edr_get_payment_types();
$api = IB_Educator::get_instance();
$student = null;
$post = null;

if ( $payment->ID ) {
	$student = get_user_by( 'id', $payment->user_id );

	if ( 'course' == $payment->payment_type ) {
		$post = get_post( $payment->course_id );
	} elseif ( 'membership' == $payment->payment_type ) {
		$post = get_post( $payment->object_id );
	}
} else {
	if ( isset( $_POST['payment_type'] ) && array_key_exists( $_POST['payment_type'], $types ) ) {
		$payment->payment_type = $_POST['payment_type'];
	} else {
		$payment->payment_type = 'course';
	}
}

$edu_countries = Edr_Countries::get_instance();
$lines = $payment->get_lines();
?>
<div class="wrap">
	<h2><?php
		if ( $payment->ID ) {
			_e( 'Edit Payment', 'ibeducator' );
		} else {
			_e( 'Add Payment', 'ibeducator' );
		}
	?></h2>

	<?php
		$errors = ib_edu_message( 'edit_payment_errors' );

		if ( $errors ) {
			echo '<div class="error below-h2"><ul>';

			foreach ( $errors as $error ) {
				switch ( $error ) {
					case 'empty_student_id':
						echo '<li>' . __( 'Please select a student', 'ibeducator' ) . '</li>';
						break;

					case 'empty_course_id':
						echo '<li>' . __( 'Please select a course', 'ibeducator' ) . '</li>';
						break;
				}
			}

			echo '</ul></div>';
		}
	?>

	<?php if ( isset( $_GET['edu-message'] ) && 'saved' == $_GET['edu-message'] ) : ?>
		<div id="message" class="updated below-h2">
			<p><?php _e( 'Payment saved.', 'ibeducator' ); ?></p>
		</div>
	<?php endif; ?>

	<form id="edu_edit_payment_form" class="ib-edu-admin-form" action="<?php echo esc_url( admin_url( 'admin.php?page=ib_educator_payments&edu-action=edit-payment&payment_id=' . $payment_id ) ); ?>" method="post">
		<?php wp_nonce_field( 'ib_educator_edit_payment_' . $payment->ID ); ?>
		<input type="hidden" id="autocomplete-nonce" value="<?php echo wp_create_nonce( 'ib_educator_autocomplete' ); ?>">
		<input type="hidden" id="ib-edu-get-states-nonce" value="<?php echo wp_create_nonce( 'ib_edu_get_states' ); ?>">
		
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
				<div id="postbox-container-1" class="postbox-container">
					<div id="side-sortables" class="meta-box-sortables">
						<div id="payment-settings" class="postbox">
							<div class="handlediv"><br></div>
							<h3 class="hndle"><span><?php _e( 'Payment', 'ibeducator' ); ?></span></h3>
							<div class="inside">
								<!-- Payment Type -->
								<div class="ib-edu-field edu-block">
									<div class="ib-edu-label">
										<label for="payment-type"><?php _e( 'Payment Type', 'ibeducator' ); ?></label>
									</div>
									<div class="ib-edu-control">
										<select name="payment_type" id="payment-type">
											<?php foreach ( $types as $key => $label ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>"<?php if ( $key == $payment->payment_type ) echo ' selected="selected"'; ?>>
													<?php echo esc_html( $label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>

								<!-- Status -->
								<div class="ib-edu-field edu-block">
									<div class="ib-edu-label"><label for="payment-status"><?php _e( 'Status', 'ibeducator' ); ?></label></div>
									<div class="ib-edu-control">
										<select name="payment_status" id="payment-status">
											<?php foreach ( $payment_statuses as $key => $label ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>"<?php if ( $key == $payment->payment_status ) echo ' selected="selected"'; ?>><?php echo esc_html( $label ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>

								<!-- Entry ID -->
								<div class="ib-edu-field edu-block" data-type="course"<?php if ( 'course' != $payment->payment_type ) echo ' style="display:none;"'; ?>>
									<div class="ib-edu-label">
										<label for="ib-edu-entry-id"><?php _e( 'Entry ID', 'ibeducator' ); ?></label>
									</div>
									<div class="ib-edu-control">
										<?php
											if ( $payment->ID ) {
												$entry = $api->get_entry( array( 'payment_id' => $payment->ID ) );
											} else {
												$entry = false;
											}

											$entry_value = $entry ? intval( $entry->ID ) : __( 'This payment is not connected to any entry.', 'ibeducator' );
										?>
										<input type="text" id="ib-edu-entry-id" value="<?php echo $entry_value; ?>" disabled="disabled">
										<?php if ( ! $entry ) : ?>
											<p id="edu-create-entry-checkbox">
												<label><input type="checkbox" name="create_entry" value="1"> <?php _e( 'Create an entry for this student', 'ibeducator' ); ?></label>
											</p>
										<?php endif; ?>
									</div>
								</div>

								<!-- Student -->
								<?php
									$student_id = 0;
									$username = '';

									if ( $student ) {
										$student_id = $student->ID;
										$username = $student->display_name;
									}
								?>
								<div class="ib-edu-field edu-block">
									<div class="ib-edu-label">
										<label><?php _e( 'Student', 'ibeducator' ); ?><span class="required">*</span></label>
									</div>
									<div class="ib-edu-control">
										<div class="ib-edu-autocomplete">
											<input
												type="text"
												name="student_id"
												id="payment-student-id"
												class="regular-text"
												autocomplete="off"
												value="<?php echo intval( $student_id ); ?>"
												data-label="<?php echo esc_attr( $username ); ?>"<?php if ( $payment->ID ) echo ' disabled="disabled"'; ?>>
										</div>
									</div>
								</div>
								
								<!-- Payment Method -->
								<div class="ib-edu-field edu-block">
									<div class="ib-edu-label">
										<label for="ib-edu-amount"><?php _e( 'Payment Method', 'ibeducator' ); ?></label>
									</div>
									<div class="ib-edu-control">
										<select name="payment_gateway">
											<option value="">&mdash; <?php _e( 'Select', 'ibeducator' ); ?> &mdash;</option>
											<?php
												$gateways = IB_Educator_Main::get_gateways();

												foreach ( $gateways as $gateway ) {
													echo '<option value="' . esc_attr( $gateway->get_id() ) . '" '
														 . selected( $payment->payment_gateway, $gateway->get_id() ) . '>'
														 . esc_html( $gateway->get_title() ) . '</option>';
												}
											?>
										</select>
									</div>
								</div>

								<!-- Transaction ID -->
								<div class="ib-edu-field edu-block">
									<div class="ib-edu-label">
										<label for="ib-edu-txn_id"><?php _e( 'Transaction ID', 'ibeducator' ); ?></label>
									</div>
									<div class="ib-edu-control">
										<input type="text" id="ib-edu-txn_id" name="txn_id" value="<?php echo esc_attr( $payment->txn_id ); ?>">
									</div>
								</div>

								<!-- IP -->
								<?php if ( $payment->ip ) : ?>
									<div class="ib-edu-field edu-block">
										<div class="ib-edu-label"><label for="ib-edu-ip"><?php _e( 'IP', 'ibeducator' ); ?></label></div>
										<div class="ib-edu-control"><?php echo esc_html( $payment->ip ); ?></div>
									</div>
								<?php endif; ?>
							</div>
							<div class="edu-actions-box">
								<div id="major-publishing-actions">
									<div id="publishing-action">
										<?php submit_button( null, 'primary', 'submit', false ); ?>
									</div>
									<div class="clear"></div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div id="postbox-container-2" class="postbox-container">
					<div id="normal-sortables" class="meta-box-sortables">
						<div id="payment-items" class="postbox">
							<div class="handlediv"><br></div>
							<h3 class="hndle"><span><?php _e( 'Items', 'ibeducator' ); ?></span></h3>
							<div class="inside">
								<!-- Course -->
								<div class="ib-edu-field" data-type="course"<?php if ( 'course' != $payment->payment_type ) echo ' style="display:none;"'; ?>>
									<div class="ib-edu-label"><label><?php _e( 'Course', 'ibeducator' ); ?><span class="required">*</span></label></div>
									<div class="ib-edu-control">
										<?php
											$course_id = $payment->course_id;
											$course_title = $course_id ? get_the_title( $course_id ) : '';
										?>
										<div class="ib-edu-autocomplete">
											<input
												type="text"
												name="course_id"
												id="payment-course-id"
												class="regular-text"
												autocomplete="off"
												value="<?php echo intval( $course_id ); ?>"
												data-label="<?php echo esc_attr( $course_title ); ?>"<?php if ( $payment->ID ) echo ' disabled="disabled"'; ?>>
										</div>
									</div>
								</div>

								<!-- Membership -->
								<?php
									$ms = Edr_Memberships::get_instance();
									$memberships = $ms->get_memberships();
									$user_membership = $ms->get_user_membership( $payment->user_id );
								?>
								<div class="ib-edu-field" data-type="membership"<?php if ( 'membership' != $payment->payment_type ) echo ' style="display:none;"'; ?>>
									<div class="ib-edu-label"><label><?php _e( 'Membership', 'ibeducator' ); ?></label></div>
									<div class="ib-edu-control">
										<div>
											<select name="object_id">
												<option value="0"><?php _e( 'Select Membership', 'ibeducator' ); ?></option>
												<?php
													if ( $memberships ) {
														foreach ( $memberships as $membership ) {
															$selected = ( $membership->ID == $payment->object_id ) ? ' selected="selected"' : '';

															echo '<option value="' . intval( $membership->ID ) . '"' . $selected . '>'
																 . esc_html( $membership->post_title ) . '</option>';
														}
													}
												?>
											</select>

											<p>
												<label><input type="checkbox" name="setup_membership" value="1"> <?php
													if ( $user_membership ) {
														_e( 'Update membership for this student', 'ibeducator' );
													} else {
														_e( 'Setup membership for this student', 'ibeducator' );
													}
												?></label>
											</p>
										</div>
									</div>
								</div>

								<!-- Tax -->
								<div class="ib-edu-field">
									<div class="ib-edu-label"><label for="ib-edu-tax"><?php _e( 'Tax', 'ibeducator' ); ?></label></div>
									<div class="ib-edu-control">
										<input type="text" id="ib-edu-tax" class="regular-text" name="tax" value="<?php echo ( $payment->tax ) ? (float) $payment->tax : 0.00; ?>">
									</div>
								</div>

								<!-- Total Amount -->
								<div class="ib-edu-field">
									<div class="ib-edu-label"><label for="ib-edu-amount"><?php _e( 'Total Amount', 'ibeducator' ); ?></label></div>
									<div class="ib-edu-control">
										<input type="text" id="ib-edu-amount" class="regular-text" name="amount" value="<?php echo ( $payment->amount ) ? (float) $payment->amount : 0.00; ?>">
										<div class="description"><?php _e( 'A number with a maximum of 2 figures after the decimal point (for example, 9.99).', 'ibeducator' ); ?></div>
									</div>
								</div>

								<!-- Currency -->
								<div class="ib-edu-field">
									<div class="ib-edu-label"><label for="ib-edu-currency"><?php _e( 'Currency', 'ibeducator' ); ?></label></div>
									<div class="ib-edu-control">
										<select id="ib-edu-currency" name="currency">
											<option value=""><?php _e( 'Select Currency', 'ibeducator' ); ?></option>
											<?php
												$current_currency = empty( $payment->currency ) ? ib_edu_get_currency() : $payment->currency;
												$currencies = ib_edu_get_currencies();

												foreach ( $currencies as $key => $value ) {
													$selected = ( $key == $current_currency ) ? ' selected="selected"' : '';

													echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $value ) . '</option>';
												}
											?>
										</select>
									</div>
								</div>
							</div>
						</div>

						<div id="payment-billing" class="postbox">
							<div class="handlediv"><br></div>
							<h3 class="hndle"><span><?php _e( 'Billing', 'ibeducator' ); ?></span></h3>
							<div class="inside">
								<!-- First Name -->
								<div class="ib-edu-field">
									<div class="ib-edu-label"><label for="ib-edu-first-name"><?php _e( 'First Name', 'ibeducator' ); ?></label></div>
									<div class="ib-edu-control">
										<input type="text" id="ib-edu-first-name" class="regular-text" name="first_name" value="<?php echo esc_attr( $payment->first_name ); ?>">
									</div>
								</div>

								<!-- Last Name -->
								<div class="ib-edu-field">
									<div class="ib-edu-label"><label for="ib-edu-last-name"><?php _e( 'Last Name', 'ibeducator' ); ?></label></div>
									<div class="ib-edu-control">
										<input type="text" id="ib-edu-last-name" class="regular-text" name="last_name" value="<?php echo esc_attr( $payment->last_name ); ?>">
									</div>
								</div>

								<!-- Address -->
								<div class="ib-edu-field">
									<div class="ib-edu-label"><label for="ib-edu-address"><?php _e( 'Address', 'ibeducator' ); ?></label></div>
									<div class="ib-edu-control">
										<input type="text" id="ib-edu-address" class="regular-text" name="address" value="<?php echo esc_attr( $payment->address ); ?>">
									</div>
								</div>

								<!-- Address Line 2 -->
								<div class="ib-edu-field">
									<div class="ib-edu-label"><label for="ib-edu-address-2"><?php _e( 'Address Line 2', 'ibeducator' ); ?></label></div>
									<div class="ib-edu-control">
										<input type="text" id="ib-edu-address-2" class="regular-text" name="address_2" value="<?php echo esc_attr( $payment->address_2 ); ?>">
									</div>
								</div>

								<!-- City -->
								<div class="ib-edu-field">
									<div class="ib-edu-label"><label for="ib-edu-city"><?php _e( 'City', 'ibeducator' ); ?></label></div>
									<div class="ib-edu-control">
										<input type="text" id="ib-edu-city" class="regular-text" name="city" value="<?php echo esc_attr( $payment->city ); ?>">
									</div>
								</div>

								<!-- Postcode / Zip -->
								<div class="ib-edu-field">
									<div class="ib-edu-label"><label for="ib-edu-postcode"><?php _e( 'Postcode / Zip', 'ibeducator' ); ?></label></div>
									<div class="ib-edu-control">
										<input type="text" id="ib-edu-postcode" class="regular-text" name="postcode" value="<?php echo esc_attr( $payment->postcode ); ?>">
									</div>
								</div>

								<!-- State / Province -->
								<div class="ib-edu-field">
									<div class="ib-edu-label"><label for="ib-edu-state"><?php _e( 'State / Province', 'ibeducator' ); ?></label></div>
									<div class="ib-edu-control">
										<?php
											$states = ! empty( $payment->country ) ? $edu_countries->get_states( $payment->country ) : null;

											if ( ! empty( $states ) ) {
												echo '<select id="ib-edu-state" name="state"><option value=""></option>';

												foreach ( $states as $scode => $sname ) {
													echo '<option value="' . esc_attr( $scode ) . '"' . selected( $payment->state, $scode, false ) . '>' . esc_html( $sname ) . '</option>';
												}

												echo '</select>';
											} else {
												echo '<input type="text" id="ib-edu-state" class="regular-text" name="state" value="' . esc_attr( $payment->state ) . '">';
											}
										?>
									</div>
								</div>

								<!-- Country -->
								<div class="ib-edu-field">
									<div class="ib-edu-label"><label for="ib-edu-country"><?php _e( 'Country', 'ibeducator' ); ?></label></div>
									<div class="ib-edu-control">
										<select id="ib-edu-country" class="regular-text" name="country">
											<option value=""></option>
											<?php
												$countries = Edr_Countries::get_instance()->get_countries();

												foreach ( $countries as $code => $country ) {
													echo '<option value="' . esc_attr( $code ) . '"' . selected( $payment->country, $code, false ) . '>' . esc_html( $country ) . '</option>';
												}
											?>
										</select>
									</div>
								</div>
							</div>
						</div>

						<?php if ( ! empty( $lines ) ) : ?>
							<div class="postbox">
								<div class="handlediv"><br></div>
								<h3 class="hndle"><?php _e( 'Payment Lines', 'ibeducator' ); ?></h3>
								<div class="inside">
									<table class="ib-edu-meta">
										<thead>
											<tr>
												<th><?php _e( 'Type', 'ibeducator' ); ?></th>
												<th><?php _e( 'Reference ID', 'ibeducator' ); ?></th>
												<th><?php _e( 'Amount', 'ibeducator' ); ?></th>
												<th><?php _e( 'Tax', 'ibeducator' ); ?></th>
												<th><?php _e( 'Name', 'ibeducator' ); ?></th>
											</tr>
										</thead>
										<tbody>
											<?php
												foreach ( $lines as $line ) {
													?>
													<tr>
														<td>
															<input type="hidden" name="line_id[]" value="<?php echo intval( $line->ID ); ?>">
															<select name="line_type[]">
																<?php
																	$types = array(
																		'tax'  => __( 'Tax', 'ibeducator' ),
																		'item' => __( 'Item', 'ibeducator' ),
																	);

																	foreach ( $types as $key => $value ) {
																		echo '<option value="' . esc_attr( $key ) . '"' . selected( $line->line_type, $key, false ) . '>' . esc_html( $value ) . '</option>';
																	}
																?>
															</select>
														</td>
														<td><input type="text" name="line_object_id[]" value="<?php echo (int) $line->object_id; ?>"></td>
														<td><input type="text" name="line_amount[]" value="<?php echo (float) $line->amount; ?>"></td>
														<td><input type="text" name="line_tax[]" value="<?php echo (float) $line->tax; ?>"></td>
														<td><input type="text" name="line_name[]" value="<?php echo esc_attr( $line->name ); ?>"></td>
													</tr>
													<?php
												}
											?>
										</tbody>
									</table>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<br class="clear">
		</div>
	</form>
</div>

<script>
jQuery(document).ready(function() {
	function fieldsByType( type ) {
		jQuery('#edu_edit_payment_form .ib-edu-field').each(function() {
			var forType = this.getAttribute('data-type');

			if ( forType && forType !== type ) {
				this.style.display = 'none';
			} else {
				this.style.display = 'block';
			}
		});
	}

	var paymentType = jQuery('#payment-type');

	paymentType.on('change', function() {
		fieldsByType(this.value);
	});

	ibEducatorAutocomplete(document.getElementById('payment-student-id'), {
		key: 'id',
		value: 'name',
		searchBy: 'name',
		nonce: jQuery('#autocomplete-nonce').val(),
		url: <?php echo json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
		entity: 'user'
	});

	ibEducatorAutocomplete(document.getElementById('payment-course-id'), {
		key: 'id',
		value: 'title',
		searchBy: 'title',
		nonce: jQuery('#autocomplete-nonce').val(),
		url: <?php echo json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
		entity: 'course'
	});

	postboxes.add_postbox_toggles(pagenow);
});
</script>