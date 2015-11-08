<?php
/**
 * This template renders the course content in the single-ib_educator_course.php template.
 *
 * @version 1.1.0
 */

$api = IB_Educator::get_instance();
$user_id = get_current_user_id();
$course_id = get_the_ID();
?>
<article id="course-<?php the_ID(); ?>" <?php post_class( 'ib-edu-course-single' ); ?>>
	<h1 class="course-title entry-title"><?php the_title(); ?></h1>

	<?php do_action( 'ib_educator_after_course_title' ); ?>

	<div class="course-content entry-content">
		<?php
			$access_status = '';

			if ( $user_id ) {
				$access_status = $api->get_access_status( $course_id, $user_id );
			}
			
			switch ( $access_status ) {
				case 'inprogress':
					echo '<div class="ib-edu-message info">' . __( 'You are registered for this course.', 'ibeducator' ) . '</div>';
					break;

				case 'pending_entry':
					echo '<div class="ib-edu-message info">' . __( 'Your registration for this course is pending.', 'ibeducator' ) . '</div>';
					break;

				case 'pending_payment':
					echo '<div class="ib-edu-message info">' . __( 'Your payment for this course is pending.', 'ibeducator' ) . '</div>';
					break;

				default:
					echo ib_edu_get_price_widget( $course_id, $user_id );
			}

			// Output error messages.
			$errors = ib_edu_message( 'course_join_errors' );

			if ( $errors ) {
				$messages = $errors->get_error_messages();

				foreach ( $messages as $message ) {
					echo '<div class="ib-edu-message error">' . $message . '</div>';
				}
			}

			do_action( 'ib_educator_before_course_content' );
			the_content();
		?>
	</div>

	<?php
		/**
		 * Fires to add various elements to the course footer.
		 *
		 * @param int $course_id
		 */
		do_action( 'edr_course_footer', $course_id );
	?>
</article>
