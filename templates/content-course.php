<?php
$classes = apply_filters( 'ib_educator_course_classes', array( 'course' ) );
?>
<article id="course-<?php the_ID(); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<?php if ( has_post_thumbnail() ) : ?>
	<div class="course-image">
		<a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'thumbnail' ); ?></a>
	</div>
	<?php endif; ?>

	<header class="course-header">
		<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

		<div class="ib-edu-course-price"><?php echo ib_edu_format_price( ib_edu_get_course_price( get_the_ID() ) ); ?></div>
	</header>

	<section class="course-summary">
		<?php the_excerpt(); ?>
	</section>
</article>