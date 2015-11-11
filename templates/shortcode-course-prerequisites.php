<?php
/**
 * Renders the prerequisites of a course.
 *
 * @version 1.1.0
 */

if ( ! empty( $courses ) ) : ?>
	<ul class="ib-edu-prerequisites">
		<?php foreach ( $courses as $course ) : ?>
			<li><a href="<?php echo esc_url( get_permalink( $course->ID ) ); ?>"><?php echo esc_html( $course->post_title ); ?></a></li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
