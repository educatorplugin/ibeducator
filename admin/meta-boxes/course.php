<?php
// Setup form object.
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator-form.php';
$form = new IB_Educator_Form();
$form->default_decorators();

// Price.
$form->set_value( '_ibedu_price', ib_edu_get_course_price( $post->ID ) );
$form->add( array(
	'type'   => 'text',
	'name'   => '_ibedu_price',
	'id'     => 'ib-educator-price',
	'label'  => __( 'Price', 'ibeducator' ),
	'before' => esc_html( ib_edu_get_currency_symbol( ib_edu_get_currency() ) ) . ' ',
) );

// Tax Class.
$edu_tax = IB_Educator_Tax::get_instance();
$form->set_value( '_ib_educator_tax_class', $edu_tax->get_tax_class_for( $post->ID ) );
$form->add( array(
	'type'    => 'select',
	'name'    => '_ib_educator_tax_class',
	'label'   => __( 'Tax Class', 'ibeducator' ),
	'options' => $edu_tax->get_tax_classes(),
	'default' => 'default',
) );

// Difficulty.
$form->set_value( '_ib_educator_difficulty', get_post_meta( $post->ID, '_ib_educator_difficulty', true ) );
$form->add( array(
	'type'    => 'select',
	'name'    => '_ib_educator_difficulty',
	'id'      => 'ib-educator-difficulty',
	'label'   => __( 'Difficulty', 'ibeducator' ),
	'options' => array_merge( array( '' => __( 'None', 'ibeducator' ) ), ib_edu_get_difficulty_levels() ),
) );

// Prerequisite.
$courses = array( '' => __( 'None', 'ibeducator' ) );
$tmp = get_posts( array(
	'post_type'      => 'ib_educator_course',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
) );

foreach ( $tmp as $course ) {
	$courses[ $course->ID ] = $course->post_title;
}
$prerequisites = IB_Educator::get_instance()->get_prerequisites( $post->ID );
$form->set_value( '_ib_educator_prerequisite', array_pop( $prerequisites ) );
$form->add( array(
	'type'    => 'select',
	'name'    => '_ib_educator_prerequisite',
	'id'      => 'ib-educator-prerequisite',
	'label'   => __( 'Prerequisite', 'ibeducator' ),
	'options' => $courses,
) );

wp_nonce_field( 'ib_educator_course_meta_box', 'ib_educator_course_meta_box_nonce' );
$form->display();
?>