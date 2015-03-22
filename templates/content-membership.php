<?php
$ms = IB_Educator_Memberships::get_instance();
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
		<?php
			the_content( '' );
		?>
	</div>

	<div class="membership-options">
		<?php
			$purchase_url = ib_edu_get_endpoint_url( 'edu-membership', $membership_id, get_permalink( ib_edu_page_id( 'payment' ) ) );
		?>
		<a href="<?php echo esc_url( $purchase_url ); ?>"><?php _e( 'Purchase', 'ibeducator' ); ?></a>
	</div>
</article>