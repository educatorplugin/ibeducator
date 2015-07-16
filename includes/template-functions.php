<?php

/**
 * Display lessons of a given course.
 *
 * @param int $course_id
 */
function edr_display_lessons( $course_id ) {
	$syllabus = get_post_meta( $course_id, '_edr_syllabus', true );

	if ( is_array( $syllabus ) && ! empty( $syllabus ) ) {
		$lesson_ids = array();
		$lessons = array();

		foreach ( $syllabus as $group ) {
			if ( ! empty( $group['lessons'] ) ) {
				$lesson_ids = array_merge( $lesson_ids, $group['lessons'] );
			}
		}

		if ( ! empty( $lesson_ids ) ) {
			$tmp = get_posts( array(
				'post_type'   => 'ib_educator_lesson',
				'post__in'    => $lesson_ids,
				'post_status' => 'publish'
			) );

			foreach ( $tmp as $lesson ) {
				$lessons[ $lesson->ID ] = $lesson;
			}

			unset( $tmp );
		}

		IB_Educator_View::the_template( 'syllabus', array(
			'syllabus' => $syllabus,
			'lessons'  => $lessons,
		) );
	} else {
		$query = IB_Educator::get_instance()->get_lessons( $course_id );

		if ( $query && $query->have_posts() ) {
		?>
			<section class="ib-edu-lessons">
				<h2><?php _e( 'Lessons', 'ibeducator' ); ?></h2>
				<?php
					while ( $query->have_posts() ) {
						$query->the_post();
						IB_Educator_View::template_part( 'content', 'lesson' );
					}

					wp_reset_postdata();
				?>
			</section>
		<?php
		}
	}
}
