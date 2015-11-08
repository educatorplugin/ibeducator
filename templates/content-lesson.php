<?php
/**
 * This template renders each lesson in the lessons list on the single course page.
 * It's used by the edr_display_lessons function.
 *
 * @version 1.1.0
 */

$lesson_id = get_the_ID();
$classes = apply_filters( 'ib_educator_lesson_classes', array( 'ib-edu-lesson' ), $lesson_id );
?>
<article id="lesson-<?php the_ID(); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>

	<?php if ( has_excerpt() ) : ?>
	<div class="excerpt">
		<?php the_excerpt(); ?>
	</div>
	<?php endif; ?>

	<?php
		if ( ib_edu_has_quiz( $lesson_id ) ) {
			echo '<div class="ib-edu-lesson-meta"><span class="quiz">' . __( 'Quiz', 'ibeducator' ) . '</span></div>';
		}
	?>
</article>