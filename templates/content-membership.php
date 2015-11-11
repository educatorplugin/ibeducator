<?php
/**
 * Renders each membership in the [memberships_page] shortcode.
 *
 * @version 1.1.0
 */

$ms = Edr_Memberships::get_instance();
$membership_id = get_the_ID();
$membership_meta = $ms->get_membership_meta( $membership_id );
$classes = apply_filters( 'ib_educator_membership_classes', array( 'ib-edu-membership' ) );
?>
<article id="membership-<?php the_ID(); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

	<div class="price">
		<?php echo $ms->format_price( $membership_meta['price'], $membership_meta['duration'], $membership_meta['period'] ); ?>
	</div>

	<div class="membership-summary">
		<?php the_content( '' ); ?>
	</div>

	<div class="membership-options">
		<?php
			echo ib_edu_purchase_link( array(
				'object_id' => $membership_id,
				'type'      => 'membership',
			) );
		?>
	</div>
</article>
