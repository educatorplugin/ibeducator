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
		Edr_View::the_template( 'course/difficulty', array( 'difficulty' => $difficulty ) );
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
		Edr_View::the_template( 'course/categories', array( 'categories' => $categories ) );
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

		Edr_View::the_template( 'course/syllabus', array(
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
						Edr_View::template_part( 'content', 'lesson' );
					}

					wp_reset_postdata();
				?>
			</section>
		<?php
		}
	}
}
endif;

/**
 * Get question content.
 *
 * @param IB_Educator_Question $question
 * @return string
 */
function edr_get_question_content( $question ) {
	/**
	 * Filter question content.
	 *
	 * @param string $question_content
	 * @param IB_Educator_Question $question
	 */
	return apply_filters( 'edr_get_question_content', $question->question_content, $question );
}

/**
 * Display a multiple choice question.
 *
 * @param IB_Educator_Question $question
 * @param mixed $answer If $edit is false, must be an object, else string (user input).
 * @param boolean $edit Display either a form or result.
 * @param array $choices
 */
function edr_question_multiple_choice( $question, $answer, $edit, $choices ) {
	echo '<div class="ib-edu-question">';
	echo '<div class="label">' . apply_filters( 'edr_get_question_title', $question->question ) . '</div>';

	if ( '' != $question->question_content ) {
		echo '<div class="content">' . edr_get_question_content( $question ) . '</div>';
	}

	echo '<ul class="ib-edu-answers">';

	if ( $edit ) {
		foreach ( $choices as $choice ) {
			$checked = ( $answer == $choice->ID ) ? ' checked="checked"' : '';
			$choice_text = apply_filters( 'edr_get_choice_text', $choice->choice_text );

			echo '<li><label><input type="radio" name="answers[' . intval( $question->ID )
				. ']" value="' . intval( $choice->ID ) . '"' . $checked . '> '
				. $choice_text . '</label></li>';
		}
	} elseif ( ! is_null( $answer ) ) {
		foreach ( $choices as $choice ) {
			$class = '';
			$check = '';

			if ( 1 == $choice->correct ) {
				// Correct answer.
				$class = 'correct';
				$check = '<span class="custom-radio correct checked"></span>';
			} elseif ( $choice->ID == $answer->choice_id && ! $choice->correct ) {
				// Wrong answer.
				$class = 'wrong';
				$check = '<span class="custom-radio wrong checked"></span>';
			}

			$class = ( ! empty( $class ) ) ? ' class="' . $class . '"' : '';
			$choice_text = apply_filters( 'edr_get_choice_text', $choice->choice_text );

			echo '<li' . $class . '><label>' . $check . $choice_text . '</label></li>';
		}
	}

	echo '</ul>';
	echo '</div>';
}

/**
 * Display a multiple choice question.
 *
 * @param IB_Educator_Question $question
 * @param mixed $answer If $edit is false, must be an object, else string (user input).
 * @param boolean $edit Display either a form or result.
 */
function edr_question_written_answer( $question, $answer, $edit ) {
	echo '<div class="ib-edu-question">';
	echo '<div class="label">' . apply_filters( 'edr_get_question_title', $question->question ) . '</div>';

	if ( '' != $question->question_content ) {
		echo '<div class="content">' . edr_get_question_content( $question ) . '</div>';
	}

	if ( $edit ) {
		echo '<div class="ib-edu-question-answer">'
			. '<textarea name="answers[' . intval( $question->ID ) . ']" cols="50" rows="3">'
			. esc_textarea( $answer )
			. '</textarea>'
			. '</div>';
	} elseif ( ! is_null( $answer ) ) {
		echo '<div class="ib-edu-question-answer">' . esc_html( $answer->answer_text ) . '</div>';
	}

	echo '</div>';
}
