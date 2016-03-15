<?php

function edr_course_meta( $course_id ) {
	$categories = get_the_term_list( $course_id, 'ib_educator_category', '', __( ', ', 'ibeducator' ) );
	$difficulty = edr_get_difficulty( $course_id );
	$html = '';

	if ( $difficulty ) {
		$html .= '<li>' . esc_html( $difficulty['label'] ) . '</li>';
	}

	if ( $categories ) {
		$html .= '<li>' . $categories . '</li>';
	}

	if ( $html ) {
		echo '<ul>', $html, '</ul>';
	}
}

function edr_breadcrumbs() {
	$breadcrumbs = array();
	$is_lesson = is_singular( 'ib_educator_lesson' );

	if ( $is_lesson ) {
		$course_id = Edr_Courses::get_instance()->get_course_id( get_the_ID() );

		if ( $course_id ) {
			$course = get_post( $course_id );

			if ( $course ) {
				$breadcrumbs[] = '<a href="' . esc_url( get_permalink( $course->ID ) ) . '">' . esc_html( $course->post_title ) . '</a>';
			}
		}
	}

	$breadcrumbs[] = '<span>' . get_the_title() . '</span>';

	echo implode( '&raquo;', $breadcrumbs );
}

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

function edr_filter_course_content( $content ) {
	$post_type = 'ib_educator_course';
	$post = get_post();

	if ( $post && $post_type == $post->post_type && is_singular( $post_type ) && is_main_query() ) {
		ob_start();
		do_action( 'edr_before_course_content', $post->ID );
		$content = ob_get_contents() . $content;
		ob_clean();
		do_action( 'edr_after_course_content', $post->ID );
		$content .= ob_get_clean();
	}

	return $content;
}

function edr_filter_lesson_content( $content ) {
	$post_type = 'ib_educator_lesson';
	$post = get_post();

	if ( $post && $post_type == $post->post_type && is_singular( $post_type ) && is_main_query() ) {
		ob_start();
		do_action( 'edr_before_lesson_content', $post->ID );
		$content = ob_get_contents() . $content;
		ob_clean();
		do_action( 'edr_after_lesson_content', $post->ID );
		$content .= ob_get_clean();
	}

	return $content;
}

function edr_display_course_status( $course_id ) {
	$user_id = get_current_user_id();
	$access_status = '';

	if ( $user_id ) {
		$access_status = Edr_Access::get_instance()->get_course_access_status( $course_id, $user_id );
	}

	switch ( $access_status ) {
		case 'inprogress':
			echo '<div class="edr-message ib-edu-message info">' . __( 'You are registered for this course.', 'ibeducator' ) . '</div>';
			break;

		case 'pending_entry':
			echo '<div class="edr-message ib-edu-message info">' . __( 'Your registration for this course is pending.', 'ibeducator' ) . '</div>';
			break;

		case 'pending_payment':
			echo '<div class="edr-message ib-edu-message info">' . __( 'Your payment for this course is pending.', 'ibeducator' ) . '</div>';
			break;

		default:
			echo edr_get_price_widget( $course_id, $user_id );
	}
}

function edr_display_course_errors( $course_id ) {
	$errors = ib_edu_message( 'course_join_errors' );

	if ( $errors ) {
		$messages = $errors->get_error_messages();

		foreach ( $messages as $message ) {
			echo '<div class="edr-message ib-edu-message error">' . $message . '</div>';
		}
	}
}

function edr_lesson_after( $lesson_id ) {
	$course_id = Edr_Courses::get_instance()->get_course_id( $lesson_id );
	$can_study = Edr_Access::get_instance()->can_study_lesson( $lesson_id );

	if ( ! $can_study ) {
		echo '<p>';
		printf(
			__( 'Please register for %s to view this lesson.', 'ibeducator' ),
			'<a href="' . esc_url( get_permalink( $course_id ) ) . '">' . get_the_title( $course_id ) . '</a>'
		);
		echo '</p>';
	} else {
		Edr_View::template_part( 'quiz' );
	}
}

/**
 * Get a link to an adjacent lesson.
 *
 * @param string $dir
 * @param string $format
 * @param string $title
 * @return string
 */
function edr_get_adjacent_lesson_link( $dir = 'previous', $format, $title ) {
	$previous = ( 'previous' == $dir ) ? true : false;

	if ( ! $lesson = Edr_Courses::get_instance()->get_adjacent_lesson( $previous ) ) {
		return '';
	}

	$url = apply_filters( "ib_educator_{$dir}_lesson_url", get_permalink( $lesson->ID ), get_the_ID() );
	$title = str_replace( '%title', esc_html( $lesson->post_title ), $title );
	$link = '<a href="' . esc_url( $url ) . '">' . $title . '</a>';

	return str_replace( '%link', $link, $format );
}

function edr_lessons_nav() {
	?>
	<nav class="edr-lessons-nav">
		<?php
			echo edr_get_adjacent_lesson_link( 'previous', '<div class="nav-previous">%link</div>', __( '&laquo; Previous Lesson', 'ibeducator' ) );
			echo edr_get_adjacent_lesson_link( 'next', '<div class="nav-next">%link</div>', __( 'Next Lesson &raquo;', 'ibeducator' ) );
		?>
	</nav>
	<?php
}

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
	$answer_choice_id = is_object( $answer ) ? $answer->choice_id : $answer;

	echo '<div class="ib-edu-question">';
	echo '<div class="label">' . apply_filters( 'edr_get_question_title', $question->question ) . '</div>';

	if ( '' != $question->question_content ) {
		echo '<div class="content">' . edr_get_question_content( $question ) . '</div>';
	}

	echo '<ul class="ib-edu-answers">';

	if ( $edit ) {
		foreach ( $choices as $choice ) {
			$checked = ( $answer_choice_id == $choice->ID ) ? ' checked="checked"' : '';
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
			} elseif ( $choice->ID == $answer_choice_id && ! $choice->correct ) {
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
	$answer_text = is_object( $answer ) ? $answer->answer_text : $answer;

	echo '<div class="ib-edu-question">';
	echo '<div class="label">' . apply_filters( 'edr_get_question_title', $question->question ) . '</div>';

	if ( '' != $question->question_content ) {
		echo '<div class="content">' . edr_get_question_content( $question ) . '</div>';
	}

	if ( $edit ) {
		echo '<div class="ib-edu-question-answer">'
			. '<textarea name="answers[' . intval( $question->ID ) . ']" cols="50" rows="3">'
			. esc_textarea( $answer_text )
			. '</textarea>'
			. '</div>';
	} elseif ( $answer_text ) {
		echo '<div class="ib-edu-question-answer">' . esc_html( $answer_text ) . '</div>';
	}

	echo '</div>';
}

/**
 * Display quiz answer file uploads list.
 *
 * @param array $files
 * @param int $lesson_id
 * @param int $question_id
 * @param int $grade_id
 */
function edr_quiz_file_list( $files, $question_id, $grade_id, $lesson_id ) {
	if ( is_array( $files ) ) {
		$quizzes = Edr_Manager::get( 'edr_quizzes' );

		echo '<ul>';

		foreach ( $files as $file ) {
			$file_url = $quizzes->get_file_url( $lesson_id, $question_id, $grade_id );

			echo '<li><a href="' . esc_url( $file_url ) . '">' . esc_html( $file['original_name'] ) . '</a></li>';
		}

		echo '</ul>';
	}
}

/**
 * Display a file upload question.
 *
 * @param IB_Educator_Question $question
 * @param mixed $answer
 * @param boolean $edit
 * @param object $grade
 */
function edr_question_file_upload( $question, $answer, $edit, $grade ) {
	echo '<div class="ib-edu-question">';
	echo '<div class="label">' . apply_filters( 'edr_get_question_title', $question->question ) . '</div>';

	if ( '' != $question->question_content ) {
		echo '<div class="content">' . edr_get_question_content( $question ) . '</div>';
	}

	$files = ( $answer ) ? maybe_unserialize( $answer->answer_text ) : array();

	if ( $edit ) {
		echo '<div class="ib-edu-question-answer">';

		if ( ! empty( $files ) ) {
			edr_quiz_file_list( $files, $question->ID, $grade->ID, $grade->lesson_id );
		}

		echo '<input type="file" name="answer_' . intval( $question->ID ) . '">';

		echo '</div>';
	} elseif ( ! empty( $answer ) ) {
		edr_quiz_file_list( $files, $question->ID, $grade->ID, $grade->lesson_id );
	}

	echo '</div>';
}

/* Deprecated functions */

if ( ! function_exists( 'edr_show_course_difficulty' ) ) :
/**
 * Display course difficulty level.
 *
 * @deprecated 1.8.0
 */
function edr_show_course_difficulty() {
	edr_deprecated_function( 'edr_show_course_difficulty', '1.8.0' );

	$difficulty = ib_edu_get_difficulty( get_the_ID() );

	if ( $difficulty ) {
		?>
		<div class="ib-edu-course-difficulty">
			<span class="label"><?php _e( 'Difficulty:', 'ibeducator' ); ?></span>
			<?php echo esc_html( $difficulty['label'] ); ?>
		</div>
		<?php
	}
}
endif;

if ( ! function_exists( 'edr_show_course_categories' ) ) :
/**
 * Display course categories.
 *
 * @deprecated 1.8.0
 */
function edr_show_course_categories() {
	edr_deprecated_function( 'edr_show_course_categories', '1.8.0' );

	$categories = get_the_term_list( get_the_ID(), 'ib_educator_category', '', __( ', ', 'ibeducator' ) );

	if ( $categories ) {
		?>
		<div class="ib-edu-course-categories">
			<span class="label"><?php _e( 'Categories:', 'ibeducator' ); ?></span>
			<?php echo $categories; ?>
		</div>
		<?php
	}
}
endif;

/**
 * Get HTML for the course price widget.
 *
 * @param int $course_id
 * @param int $user_id
 * @param string $before
 * @param string $after
 * @return string
 */
function edr_get_price_widget( $course_id, $user_id, $before = '<div class="edr-course-price">', $after = '</div>' ) {
	// Registration allowed?
	if ( 'closed' == Edr_Courses::get_instance()->get_register_status( $course_id ) ) {
		return '';
	}

	// Check membership.
	$membership_access = Edr_Memberships::get_instance()->membership_can_access( $course_id, $user_id );

	/**
	 * Filter the course price widget.
	 *
	 * @since 1.3.2
	 *
	 * @param bool $membership_access Whether the user's current membership allows him/her to take the course.
	 */
	$output = apply_filters( 'ib_educator_course_price_widget', null, $membership_access, $course_id, $user_id );

	if ( null !== $output ) {
		return $output;
	}

	// Generate the widget.
	$output = $before;

	if ( $membership_access ) {
		$register_url = ib_edu_get_endpoint_url( 'edu-action', 'join', get_permalink( $course_id ) );
		$output .= '<form action="' . esc_url( $register_url ) . '" method="post">';
		$output .= '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'ib_educator_join' ) . '">';
		$output .= '<input type="submit" class="ib-edu-button" value="' . __( 'Join', 'ibeducator' ) . '">';
		$output .= '</form>';
	} else {
		$price = ib_edu_get_course_price( $course_id );
		$price = ( 0 == $price ) ? __( 'Free', 'ibeducator' ) : ib_edu_format_course_price( $price );
		$register_url = ib_edu_get_endpoint_url( 'edu-course', $course_id, get_permalink( ib_edu_page_id( 'payment' ) ) );
		$output .= '<span class="price">' . $price . '</span><a href="' . esc_url( $register_url )
				. '" class="ib-edu-button">' . __( 'Register', 'ibeducator' ) . '</a>';
	}

	$output .= $after;

	return $output;
}
