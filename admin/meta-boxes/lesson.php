<?php
wp_nonce_field( 'ib_educator_lesson_meta_box', 'ib_educator_lesson_meta_box_nonce' );

// Lesson access.
$access = get_post_meta( $post->ID, '_ib_educator_access', true );

if ( empty( $access ) ) {
	$access = 'registered';
}

// Course.
$course_id = get_post_meta( $post->ID, '_ibedu_course', true );
$courses = get_posts( array( 'post_type' => 'ib_educator_course', 'posts_per_page' => -1 ) );
?>
<div class="ib-edu-field">
	<div class="ib-edu-label">
		<label for="ib-educator-access"><?php _e( 'Access', 'ibeducator' ); ?></label>
	</div>
	<div class="ib-edu-control">
		<select id="ib-educator-access" name="_ib_educator_access">
			<?php
				$access_options = array(
					'registered' => __( 'Registered users', 'ibeducator' ),
					'logged_in'  => __( 'Logged in users', 'ibeducator' ),
					'public'     => __( 'Everyone', 'ibeducator' ),
				);

				foreach ( $access_options as $key => $label ) {
					echo '<option value="' . $key . '"' . selected( $key, $access, false ) . '>' . $label . '</option>';
				}
			?>
		</select>
	</div>
</div>

<?php if ( ! empty( $courses ) ) : ?>
	<div class="ib-edu-field">
		<div class="ib-edu-label">
			<label for="ib-educator-course"><?php _e( 'Course', 'ibeducator' ); ?></label>
		</div>
		<div class="ib-edu-control">
			<select id="ib-educator-course" name="_ibedu_course">
				<option value=""><?php _e( 'Select Course', 'ibeducator' ); ?></option>
				<?php foreach ( $courses as $post ) : ?>
					<option value="<?php echo intval( $post->ID ); ?>"<?php if ( $course_id == $post->ID ) echo ' selected="selected"'; ?>>
						<?php echo esc_html( $post->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>

	<script>
	(function($) {
		var selectCourse = $('#ib-educator-course');

		// Make sure that user selects a course.
		$('form#post').on('submit', function(e) {
			if (selectCourse.val() == '') {
				e.preventDefault();
				selectCourse.focus();
			}
		});
	})(jQuery);
	</script>
<?php endif; ?>