<?php
	if ( ! defined( 'ABSPATH' ) ) exit;
	
	$entry_id = isset( $_GET['entry_id'] ) ? absint( $_GET['entry_id'] ) : 0;
	$entry = IB_Educator_Entry::get_instance( $entry_id );
	$who = '';

	if ( current_user_can( 'manage_educator' ) ) {
		$who = 'admin';
	} elseif ( $entry->course_id && current_user_can( 'edit_ib_educator_course', $entry->course_id ) ) {
		$who = 'lecturer';
	}

	// Check capabilities.
	if ( empty( $who ) ) {
		// Current user cannot create entries.
		echo '<p>' . __( 'Access denied', 'ibeducator' ) . '</p>';
		return;
	}

	$statuses = IB_Educator_Entry::get_statuses();
	$origins = IB_Educator_Entry::get_origins();
	$student = null;
	$course = null;
	$input = array(
		'payment_id'    => isset( $_POST['payment_id'] ) ? $_POST['payment_id'] : $entry->payment_id,
		'membership_id' => isset( $_POST['membership_id'] ) ? $_POST['membership_id'] : $entry->object_id,
		'entry_origin'  => isset( $_POST['entry_origin'] ) ? $_POST['entry_origin'] : $entry->entry_origin,
		'entry_status'  => isset( $_POST['entry_status'] ) ? $_POST['entry_status'] : $entry->entry_status,
		'grade'         => isset( $_POST['grade'] ) ? $_POST['grade'] : $entry->grade,
		'entry_date'    => isset( $_POST['entry_date'] ) ? $_POST['entry_date'] : ( ! empty( $entry->entry_date ) ? $entry->entry_date : date( 'Y-m-d H:i:s' ) ),
	);

	if ( 'admin' == $who && isset( $_POST['student_id'] ) ) {
		$student = get_user_by( 'id', $_POST['student_id'] );
	} elseif ( $entry->ID ) {
		$student = get_user_by( 'id', $entry->user_id );
	}

	if ( 'admin' == $who && isset( $_POST['course_id'] ) ) {
		$course = get_post( $_POST['course_id'] );
	} elseif ( $entry->ID ) {
		$course = get_post( $entry->course_id );
	}
?>
<div class="wrap">
	<h2><?php
		if ( $entry->ID ) {
			_e( 'Edit Entry', 'ibeducator' );
		} else {
			_e( 'Add Entry', 'ibeducator' );
		}
	?></h2>

	<?php if ( isset( $_GET['edu-message'] ) && 'saved' == $_GET['edu-message'] ) : ?>
		<div id="message" class="updated below-h2">
			<p><?php _e( 'Entry saved.', 'ibeducator' ); ?></p>
		</div>
	<?php endif; ?>

	<?php
		// Output error messages.
		$errors = ib_edu_message( 'edit_entry_errors' );

		if ( $errors ) {
			$messages = $errors->get_error_messages();

			foreach ( $messages as $message ) {
				echo '<div class="error"><p>' . $message . '</p></div>';
			}
		}
	?>

	<form id="edu_edit_entry_form" class="ib-edu-admin-form" action="<?php echo esc_url( admin_url( 'admin.php?page=ib_educator_entries&edu-action=edit-entry&entry_id=' . $entry_id ) ); ?>" method="post">
		<?php wp_nonce_field( 'ib_educator_edit_entry_' . $entry->ID ); ?>
		<input type="hidden" id="autocomplete-nonce" value="<?php echo wp_create_nonce( 'ib_educator_autocomplete' ); ?>">

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
				<div id="postbox-container-1" class="postbox-container">
					<div id="side-sortables" class="meta-box-sortables">
						<div id="entry-settings" class="postbox">
							<div class="handlediv"><br></div>
							<h3 class="hndle"><span><?php _e( 'Entry', 'ibeducator' ); ?></span></h3>
							<div class="inside">
								<!-- Status -->
								<div class="ib-edu-field edu-block">
									<div class="ib-edu-label">
										<label for="entry-status"><?php _e( 'Status', 'ibeducator' ); ?></label>
									</div>
									<div class="ib-edu-control">
										<select name="entry_status" id="entry-status">
											<?php foreach ( $statuses as $key => $label ) : ?>
												<option value="<?php echo esc_attr( $key ); ?>"<?php if ( $key == $input['entry_status'] ) echo ' selected="selected"'; ?>>
													<?php echo esc_html( $label ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>

								<!-- Date -->
								<div class="ib-edu-field edu-block">
									<div class="ib-edu-label">
										<label for="ib-edu-entry-date"><?php _e( 'Date', 'ibeducator' ); ?></label>
									</div>
									<div class="ib-edu-control">
										<input type="text" id="ib-edu-entry-date" class="regular-text" maxlength="19" size="19" name="entry_date" value="<?php echo esc_attr( $input['entry_date'] ); ?>">
										<div class="description"><?php _e( 'Date format: yyyy-mm-dd hh:mm:ss', 'ibeducator' ); ?></div>
									</div>
								</div>
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
						<div id="entry-settings" class="postbox">
							<div class="handlediv"><br></div>
							<h3 class="hndle"><span><?php _e( 'Entry Data', 'ibeducator' ); ?></span></h3>
							<div class="inside">
								<?php if ( 'admin' == $who ) : ?>
									<!-- Membership -->
									<?php
										$ms = IB_Educator_Memberships::get_instance();
										$memberships = $ms->get_memberships();
									?>
									<div class="ib-edu-field" data-origin="membership"<?php if ( 'membership' != $input['entry_origin'] ) echo ' style="display:none;"'; ?>>
										<div class="ib-edu-label">
											<label for="ib-edu-membership-id"><?php _e( 'Membership', 'ibeducator' ); ?></label>
										</div>
										<div class="ib-edu-control">
											<select name="membership_id" id="ib-edu-membership-id">
												<option value=""><?php _e( 'Select Membership', 'ibeducator' ); ?></option>
												<?php
													if ( $memberships ) {
														foreach ( $memberships as $membership ) {
															$selected = ( $input['membership_id'] == $membership->ID ) ? ' selected="selected"' : '';
															echo '<option value="' . esc_attr( $membership->ID ) . '"' . $selected . '>' . esc_html( $membership->post_title ) . '</option>';
														}
													}
												?>
											</select>
										</div>
									</div>

									<!-- Payment ID -->
									<div class="ib-edu-field" data-origin="payment"<?php if ( 'payment' != $input['entry_origin'] ) echo ' style="display:none;"'; ?>>
										<div class="ib-edu-label">
											<label for="ib-edu-payment-id"><?php _e( 'Payment ID', 'ibeducator' ); ?></label>
										</div>
										<div class="ib-edu-control">
											<input type="text" id="ib-edu-payment-id" class="small-text" maxlength="20" size="6" name="payment_id" value="<?php echo intval( $input['payment_id'] ); ?>">
											<div class="description">
												<?php
													printf( __( 'Please find payment ID on %s page.', 'ibeducator' ), '<a href="'
														. admin_url( 'admin.php?page=ib_educator_payments' ) . '" target="_blank">'
														. __( 'Payments', 'ibeducator' ) . '</a>' );
												?>
											</div>
										</div>
									</div>

									<!-- Origin -->
									<div class="ib-edu-field">
										<div class="ib-edu-label">
											<label for="entry-origin"><?php _e( 'Origin', 'ibeducator' ); ?></label>
										</div>
										<div class="ib-edu-control">
											<select name="entry_origin" id="entry-origin">
												<?php foreach ( $origins as $key => $label ) : ?>
													<option value="<?php echo esc_attr( $key ); ?>"<?php if ( $key == $input['entry_origin'] ) echo ' selected="selected"'; ?>>
														<?php echo esc_html( $label ); ?>
													</option>
												<?php endforeach; ?>
											</select>
										</div>
									</div>
								<?php endif; ?>

								<!-- Student -->
								<div class="ib-edu-field">
									<div class="ib-edu-label">
										<label><?php _e( 'Student', 'ibeducator' ); ?></label>
									</div>
									<div class="ib-edu-control">
										<div class="ib-edu-autocomplete">
											<input
												type="text"
												name="student_id"
												id="entry-student-id"
												class="regular-text"
												autocomplete="off"
												value="<?php if ( $student ) echo intval( $student->ID ); ?>"
												data-label="<?php if ( $student ) echo esc_attr( $student->display_name ); ?>"<?php if ( 'admin' != $who ) echo ' disabled="disabled"'; ?>>
										</div>
									</div>
								</div>

								<!-- Course -->
								<div class="ib-edu-field">
									<div class="ib-edu-label">
										<label><?php _e( 'Course', 'ibeducator' ); ?></label>
									</div>
									<div class="ib-edu-control">
										<div class="ib-edu-autocomplete">
											<input
												type="text"
												name="course_id"
												id="entry-course-id"
												class="regular-text"
												autocomplete="off"
												value="<?php if ( $course ) echo intval( $course->ID ); ?>"
												data-label="<?php if ( $course ) echo esc_attr( $course->post_title ); ?>"<?php if ( 'admin' != $who ) echo ' disabled="disabled"'; ?>>
										</div>
									</div>
								</div>

								<!-- Grade -->
								<div class="ib-edu-field">
									<div class="ib-edu-label">
										<label for="ib-edu-grade"><?php _e( 'Grade', 'ibeducator' ); ?></label>
									</div>
									<div class="ib-edu-control">
										<input type="text" id="ib-edu-grade" class="small-text" maxlength="6" size="6" name="grade" value="<?php echo esc_attr( $input['grade'] ); ?>">
										<div class="description"><?php _e( 'A number between 0 and 100.', 'ibeducator' ); ?></div>
									</div>
								</div>

								<!-- Prerequisites -->
								<?php if ( 'admin' == $who ) : ?>
									<div class="ib-edu-field" data-origin="payment"<?php if ( 'payment' != $input['entry_origin'] ) echo ' style="display:none;"'; ?>>
										<div class="ib-edu-label">
											<label><?php _e( 'Prerequisites', 'ibeducator' ); ?></label>
										</div>
										<div class="ib-edu-control">
											<label><input type="checkbox" name="ignore_prerequisites"> <?php _e( 'Ignore prerequisites', 'ibeducator' ); ?></label>
										</div>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<br class="clear">
		</div>
	</form>
</div>

<script>
jQuery(document).ready(function() {
	function fieldsByOrigin( origin ) {
		jQuery('#edu_edit_entry_form .ib-edu-field').each(function() {
			var forOrigin = this.getAttribute('data-origin');

			if ( forOrigin && forOrigin !== origin ) {
				this.style.display = 'none';
			} else {
				this.style.display = 'block';
			}
		});
	}

	var entryOrigin = jQuery('#entry-origin');

	entryOrigin.on('change', function() {
		fieldsByOrigin(this.value);
	});

	ibEducatorAutocomplete(document.getElementById('entry-student-id'), {
		key: 'id',
		value: 'name',
		searchBy: 'name',
		nonce: jQuery('#autocomplete-nonce').val(),
		url: <?php echo json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
		entity: 'user'
	});

	ibEducatorAutocomplete(document.getElementById('entry-course-id'), {
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