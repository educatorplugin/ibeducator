<?php
$user_id = get_current_user_id();

if ( ! $user_id ) {
	echo '<p>' . __( 'Please log in to view this page.', 'ibeducator' ) . '</p>';
	return;
}

global $post;
$api = IB_Educator::get_instance();
$courses = $api->get_student_courses( $user_id );
$pending_courses = $api->get_pending_courses( $user_id );
$ms = IB_Educator_Memberships::get_instance();

// Output status message.
$message = get_query_var( 'edu-message' );

if ( 'payment-cancelled' == $message ) {
	echo '<div class="ib-edu-message success">' . __( 'Payment has been cancelled.', 'ibeducator' ) . '</div>';
}

if ( $courses || $pending_courses ) {
	if ( $pending_courses ) {
		/**
		 * Pending Payment.
		 */
		echo '<h3>' . __( 'Pending Payment', 'ibeducator' ) . '</h3>';
		echo '<table class="ib-edu-courses ib-edu-courses-pending">';
		echo '<thead><tr><th style="width:20%;">' . _x( 'Payment', 'Table column heading', 'ibeducator' ) . '</th><th style="width:50%;">' . __( 'Course', 'ibeducator' ) . '</th><th>' . __( 'Actions', 'ibeducator' ) . '</th></tr></thead>';
		echo '<tbody>';

		$gateways = IB_Educator_Main::get_gateways();
		
		foreach ( $pending_courses as $course ) {
			?>
			<tr>
				<td><?php echo intval( $course->edu_payment_id ); ?></td>
				<td class="title">
					<a href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>"><?php echo esc_html( $course->post_title ); ?></a>
					<?php
						// Output payment gateway instructions.
						if ( isset( $gateways[ $course->edu_payment->payment_gateway ] ) ) {
							$description = $gateways[ $course->edu_payment->payment_gateway ]->get_option( 'description' );

							if ( $description ) {
								?>
								<div class="payment-description">
									<a class="open-description" href="#"><?php _e( 'View payment instructions', 'ibeducator' ); ?></a>
									<div class="text"><?php echo wpautop( stripslashes( $description ) ); ?></div>
								</div>
								<?php
							}
						}
					?>
				</td>
				<td>
					<form action="<?php echo esc_url( ib_edu_get_endpoint_url( 'edu-action', 'cancel-payment', get_permalink() ) ); ?>" method="post">
						<?php wp_nonce_field( 'ibedu_cancel_payment' ); ?>
						<input type="hidden" name="payment_id" value="<?php echo absint( $course->edu_payment_id ); ?>">
						<button type="submit" class="ib-edu-button"><?php _e( 'Cancel', 'ibeducator' ); ?></a>
					</form>
				</td>
			</tr>
			<?php
		}

		echo '</tbody></table>';
	}

	if ( $courses && $courses['entries'] ) {
		/**
		 * In Progress.
		 */
		if ( array_key_exists( 'inprogress', $courses['statuses'] ) ) {
			echo '<h3>' . __( 'In Progress', 'ibeducator' ) . '</h3>';
			echo '<table class="ib-edu-courses ib-edu-courses-inprogress">';
			echo '<thead><tr><th style="width:20%;">' . __( 'Entry ID', 'ibeducator' ) . '</th><th style="width:50%;">' . __( 'Course', 'ibeducator' ) . '</th><th>' . __( 'Date taken', 'ibeducator' ) . '</th></tr></thead>';
			echo '<tbody>';
			
			foreach ( $courses['entries'] as $entry ) {
				// If the entry has status "inprogress" and course exists.
				if ( 'inprogress' == $entry->entry_status && isset( $courses['courses'][ $entry->course_id ] ) ) {
					$course = $courses['courses'][ $entry->course_id ];
					$date = date_i18n( get_option( 'date_format' ), strtotime( $entry->entry_date ) );
					?>
					<tr>
						<td><?php echo intval( $entry->ID ); ?></td>
						<td><a class="title" href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>"><?php echo esc_html( $course->post_title ); ?></a></td>
						<td class="date"><?php echo esc_html( $date ); ?></td>
					</tr>
					<?php
				}
			}

			echo '</tbody></table>';
		}

		/**
		 * Complete.
		 */
		if ( array_key_exists( 'complete', $courses['statuses'] ) ) {
			echo '<h3>' . __( 'Complete', 'ibeducator' ) . '</h3>';
			echo '<table class="ib-edu-courses ib-edu-courses-complete">';
			echo '<thead><tr><th style="width:20%;">' . __( 'Entry ID', 'ibeducator' ) . '</th><th style="width:50%;">' . __( 'Course', 'ibeducator' ) . '</th><th>' . __( 'Grade', 'ibeducator' ) . '</th></tr></thead>';
			echo '<tbody>';
			
			foreach ( $courses['entries'] as $entry ) {
				// If the entry has status "inprogress" and course exists.
				if ( 'complete' == $entry->entry_status && isset( $courses['courses'][ $entry->course_id ] ) ) {
					$course = $courses['courses'][ $entry->course_id ];
					?>
					<tr>
						<td><?php echo intval( $entry->ID ); ?></td>
						<td><a class="title" href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>"><?php echo esc_html( $course->post_title ); ?></a></td>
						<td class="grade"><?php echo ib_edu_format_grade( $entry->grade ); ?></td>
					</tr>
					<?php
				}
			}

			echo '</tbody></table>';
		}

		/**
		 * Paused.
		 */
		if ( array_key_exists( 'paused', $courses['statuses'] ) ) {
			echo '<h3>' . __( 'Paused', 'ibeducator' ) . '</h3>';
			echo '<table class="ib-edu-courses ib-edu-courses-paused">';
			echo '<thead><tr><th style="width:20%;">' . __( 'Entry ID', 'ibeducator' ) . '</th><th style="width:50%;">' . __( 'Course', 'ibeducator' ) . '</th><th>' . __( 'Actions', 'ibeducator' ) . '</th></tr></thead>';
			echo '<tbody>';
			
			foreach ( $courses['entries'] as $entry ) {
				// If the entry has status "inprogress" and course exists.
				if ( 'paused' == $entry->entry_status && isset( $courses['courses'][ $entry->course_id ] ) ) {
					$course = $courses['courses'][ $entry->course_id ];
					?>
					<tr>
						<td><?php echo intval( $entry->ID ); ?></td>
						<td><a class="title" href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>"><?php echo esc_html( $course->post_title ); ?></a></td>
						<td>
							<form action="<?php echo esc_url( ib_edu_get_endpoint_url( 'edu-action', 'resume-entry', get_permalink() ) ); ?>" method="post">
								<?php wp_nonce_field( 'ib_educator_resume_entry' ); ?>
								<input type="hidden" name="entry_id" value="<?php echo absint( $entry->ID ); ?>">
								<button type="submit" class="ib-edu-button" <?php if ( ! $ms->membership_can_access( $course->ID, $user_id ) ) echo ' disabled="disabled"'; ?>><?php _e( 'Resume', 'ibeducator' ); ?></button>
							</form>
						</td>
					</tr>
					<?php
				}
			}

			echo '</tbody></table>';
		}
	}
} else {
	echo '<p>' . __( 'You are not registered for any course.', 'ibeducator' ) . ' <a href="' . esc_url( get_post_type_archive_link( 'ib_educator_course' ) ) . '">' . __( 'Browse courses', 'ibeducator' ) . '</a></p>';
}
?>

<script>
(function($) {
	$('.ib-edu-courses-pending .open-description').on('click', function(e) {
		e.preventDefault();
		$(this).parent().toggleClass('open');
	});
})(jQuery);
</script>