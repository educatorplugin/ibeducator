<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * SHORTCODE: courses.
 */
function ib_edu_courses( $atts, $content = null ) {
	$template = IB_Educator_View::locate_template( array( 'shortcode-courses.php' ) );

	if ( ! $template ) {
		return;
	}
	
	$args = array(
		'post_type'      => 'ib_educator_course',
		'posts_per_page' => isset( $atts['number'] ) ? intval( $atts['number'] ) : 10,
		'post_status'    => 'publish',
	);

	if ( ! isset( $atts['nopaging'] ) || 1 != $atts['nopaging'] ) {
		$args['paged'] = get_query_var( 'paged' );
	}

	if ( isset( $atts['categories'] ) ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'ib_educator_category',
				'field'    => 'term_id',
				'terms'    => array_map( 'intval', explode( ',', $atts['categories'] ) ),
			),
		);
	}

	if ( isset( $atts['ids'] ) ) {
		$args['post__in'] = array_map( 'intval', explode( ',', $atts['ids'] ) );
	}

	if ( isset( $atts['orderby'] ) ) {
		switch ( $atts['orderby'] ) {
			case 'id':
				$args['orderby'] = 'ID';
				break;

			case 'random':
				$args['orderby'] = 'rand';
				break;

			default:
				$args['orderby'] = $atts['orderby'];
		}
	}

	if ( isset( $atts['order'] ) ) {
		$args['order'] = $atts['order'];
	}

	$query_args = apply_filters( 'ib_educator_courses_query_args', $args, $atts );
	$courses = new WP_Query( $query_args );

	ob_start();
	include $template;
	return ob_get_clean();
}

/**
 * SHORTCODE: output student's courses.
 */
function ib_edu_student_courses( $atts, $content = null ) {
	$template = IB_Educator_View::locate_template( array( 'shortcode-student-courses.php' ) );

	if ( ! $template ) {
		return;
	}

	ob_start();
	include $template;
	return ob_get_clean();
}

/**
 * SHORTCODE: output payment page.
 */
function ib_edu_payment_page( $atts, $content = null ) {
	$template = IB_Educator_View::locate_template( array( 'shortcode-payment.php' ) );

	if ( ! $template ) {
		return;
	}

	ob_start();
	include $template;
	return ob_get_clean();
}

/**
 * SHORTCODE: output membership page.
 */
function ib_edu_memberships_page( $atts, $content = null ) {
	$template = IB_Educator_View::locate_template( array( 'shortcode-memberships.php' ) );

	if ( ! $template ) {
		return;
	}

	ob_start();
	include $template;
	return ob_get_clean();
}

/**
 * SHORTCODE: output membership page.
 */
function ib_edu_user_membership_page( $atts, $content = null ) {
	$template = IB_Educator_View::locate_template( array( 'shortcode-user-membership.php' ) );

	if ( ! $template ) {
		return;
	}

	ob_start();
	include $template;
	return ob_get_clean();
}

/**
 * SHORTCODE: output the user's payments page.
 */
function ib_edu_user_payments_page( $atts, $content = null ) {
	$template = IB_Educator_View::locate_template( array( 'shortcode-user-payments.php' ) );

	if ( ! $template ) {
		return;
	}

	ob_start();
	include $template;
	return ob_get_clean();
}

/**
 * SHORTCODE: output the course prerequisites.
 */
function ib_edu_course_prerequisites( $atts, $content = null ) {
	$template = IB_Educator_View::locate_template( array( 'shortcode-course-prerequisites.php' ) );

	if ( ! $template ) {
		return;
	}

	$api = IB_Educator::get_instance();
	$prerequisites = $api->get_prerequisites( get_the_ID() );
	$courses = null;

	if ( ! empty( $prerequisites ) ) {
		$courses = get_posts( array(
			'post_type'   => 'ib_educator_course',
			'post_status' => 'publish',
			'include'     => $prerequisites,
		) );
	} else {
		$courses = array();
	}
	
	ob_start();
	include $template;
	return ob_get_clean();
}

/**
 * Register the shortcodes.
 */
function ib_edu_register_shortcodes() {
	$shortcodes = array(
		'courses'               => 'ib_edu_courses',
		'ibedu_student_courses' => 'ib_edu_student_courses',
		'ibedu_payment_page'    => 'ib_edu_payment_page',
		'memberships_page'      => 'ib_edu_memberships_page',
		'user_membership_page'  => 'ib_edu_user_membership_page',
		'user_payments_page'    => 'ib_edu_user_payments_page',
		'course_prerequisites'  => 'ib_edu_course_prerequisites',
	);

	foreach ( $shortcodes as $key => $function ) {
		add_shortcode( apply_filters( 'ib_educator_shortcode_tag', $key ), $function );
	}
}
add_action( 'init', 'ib_edu_register_shortcodes' );
