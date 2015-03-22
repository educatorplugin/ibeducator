<?php
wp_nonce_field( 'ib_educator_lesson_meta_box', 'ib_educator_lesson_meta_box_nonce' );

$value = get_post_meta( $post->ID, '_ibedu_course', true );
$courses = get_posts( array( 'post_type' => 'ib_educator_course', 'posts_per_page' => -1 ) );
?>
<?php if ( ! empty( $courses ) ) : ?>
<div class="ib-edu-field">
	<div class="ib-edu-label"><label for="ib-educator-course"><?php _e( 'Course', 'ibeducator' ); ?></label></div>
	<div class="ib-edu-control">
		<select id="ib-educator-course" name="_ibedu_course">
			<option value=""><?php _e( 'Select Course', 'ibeducator' ); ?></option>
			<?php foreach ( $courses as $post ) : ?>
			<option value="<?php echo intval( $post->ID ); ?>"<?php if ( $value == $post->ID ) echo ' selected="selected"'; ?>><?php echo esc_html( $post->post_title ); ?></option>
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