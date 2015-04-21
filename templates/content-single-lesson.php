<?php
$student_can_study = ib_edu_student_can_study( get_the_ID() );
$classes = array( 'ib-edu-lesson-single' );

if ( ! $student_can_study ) {
	$classes[] = 'ib-edu-lesson-locked';
}
?>
<article id="lesson-<?php the_ID(); ?>" <?php post_class( $classes ); ?>>
	<h1 class="lesson-title entry-title"><?php the_title(); ?></h1>

	<div id="ib-edu-breadcrumbs"><?php ib_edu_breadcrumbs(); ?></div>

	<div class="lesson-content entry-content">
		<?php
			if ( $student_can_study ) {
				the_content();
				IB_Educator_View::template_part( 'quiz' );
			} else {
				echo '<p>';
				printf(
					__( 'Please register for the %s to view this lesson.', 'ibeducator' ),
					'<a href="' . esc_url( get_permalink( ib_edu_get_course_id() ) ) . '">' . __( 'course', 'ibeducator' ) . '</a>'
				);
				echo '</p>';
			}
		?>
	</div>

	<nav class="ib-edu-lesson-nav">
		<?php
			echo ib_edu_get_adjacent_lesson_link( 'previous', '<div class="nav-previous">&laquo; %link</div>', __( 'Previous Lesson', 'ibeducator' ) );
			echo ib_edu_get_adjacent_lesson_link( 'next', '<div class="nav-next">%link &raquo;</div>', __( 'Next Lesson', 'ibeducator' ) );
		?>
	</nav>
</article>

<?php
	// Comments.
	if ( $student_can_study
		 && 1 == ib_edu_get_option( 'lesson_comments', 'learning' )
		 && ( comments_open() || get_comments_number() ) ) {
		comments_template();
	}
?>