<?php
$ms = IB_Educator_Memberships::get_instance();

// Get membership meta.
$meta = $ms->get_membership_meta( $post->ID );

// Get membership periods.
$membership_periods = $ms->get_periods();

// Setup form object.
require_once IBEDUCATOR_PLUGIN_DIR . 'includes/ib-educator-form.php';
$form = new IB_Educator_Form();
$form->default_decorators();

// Price.
$form->set_value( '_ib_educator_price', $meta['price'] );
$form->add( array(
	'type'   => 'text',
	'name'   => '_ib_educator_price',
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

// Duration.
$period = '<select name="_ib_educator_period">';

foreach ( $membership_periods as $mp_value => $mp_name ) {
	$period .= '<option value="' . esc_attr( $mp_value ) . '"' . selected( $meta['period'], $mp_value, false ) . '>' . esc_html( $mp_name ) . '</option>';
}

$period .= '</select>';

$form->set_value( '_ib_educator_duration', $meta['duration'] );
$form->add( array(
	'type'   => 'text',
	'name'   => '_ib_educator_duration',
	'id'     => 'ib-educator-duration',
	'class'  => 'small-text',
	'label'  => __( 'Duration', 'ibeducator' ),
	'after'  => " $period",
) );

// Categories.
$categories = array( '' => __( 'Select Categories', 'ibeducator' ) );
$terms = get_terms( 'ib_educator_category' );

if ( $terms && ! is_wp_error( $terms ) ) {
	foreach ( $terms as $term ) {
		$categories[ $term->term_id ] = $term->name;
	}
}

$form->set_value( '_ib_educator_categories', $meta['categories'] );
$form->add( array(
	'type'     => 'select',
	'name'     => '_ib_educator_categories',
	'label'    => __( 'Categories', 'ibeducator' ),
	'multiple' => true,
	'size'     => 5,
	'options'  => $categories,
) );

// Display the form.
wp_nonce_field( 'ib_edu_membership', 'ib_edu_membership_nonce' );
$form->display();
?>