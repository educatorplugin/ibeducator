<?php

if ( ! function_exists( 'edr_before_main_loop' ) ) :
/**
 * Default html before the main loop.
 *
 * @param string $where
 */
function edr_before_main_loop( $where = '' ) {
	$template = get_template();

	switch ( $template ) {
		case 'twentyfourteen':
			echo '<div id="main-content" class="main-content"><div id="primary" class="content-area"><div id="content" class="site-content" role="main">';

			if ( 'archive' != $where ) {
				echo '<div class="ib-edu-twentyfourteen">';
			}

			break;

		case 'twentyfifteen':
			echo '<div id="primary" class="content-area"><main id="main" class="site-main" role="main">';

			if ( 'archive' != $where ) {
				echo '<div class="ib-edu-twentyfifteen">';
			}

			break;
	}
}
endif;

if ( ! function_exists( 'edr_after_main_loop' ) ) :
/**
 * Default html after the main loop.
 *
 * @param string $where
 */
function edr_after_main_loop( $where = '' ) {
	$template = get_template();

	switch ( $template ) {
		case 'twentyfourteen':
			echo '</div></div></div>';

			if ( 'archive' != $where ) {
				echo '</div>';
			}

			break;

		case 'twentyfifteen':
			echo '</main></div>';

			if ( 'archive' != $where ) {
				echo '</div>';
			}

			break;
	}
}
endif;

if ( ! function_exists( 'edr_show_sidebar' ) ) :
/**
 * Show sidebar.
 */
function edr_show_sidebar() {
	get_sidebar( 'educator' );
}
endif;

if ( ! function_exists( 'edr_show_course_difficulty' ) ) :
/**
 * Display course difficulty level.
 */
function edr_show_course_difficulty() {
	$difficulty = ib_edu_get_difficulty( get_the_ID() );

	if ( $difficulty ) {
		IB_Educator_View::the_template( 'course/difficulty', array( 'difficulty' => $difficulty ) );
	}
}
endif;

if ( ! function_exists( 'edr_show_course_categories' ) ) :
/**
 * Display course categories.
 */
function edr_show_course_categories() {
	$categories = get_the_term_list( get_the_ID(), 'ib_educator_category', '', __( ', ', 'ibeducator' ) );

	if ( $categories ) {
		IB_Educator_View::the_template( 'course/categories', array( 'categories' => $categories ) );
	}
}
endif;

if ( ! function_exists( 'edr_display_lessons' ) ) :
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
				'post_type'      => 'ib_educator_lesson',
				'post__in'       => $lesson_ids,
				'post_status'    => 'publish',
				'posts_per_page' => count( $lesson_ids ),
			) );

			foreach ( $tmp as $lesson ) {
				$lessons[ $lesson->ID ] = $lesson;
			}

			unset( $tmp );
		}

		IB_Educator_View::the_template( 'course/syllabus', array(
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
endif;
