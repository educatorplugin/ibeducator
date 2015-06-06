<?php
$query = new WP_Query( array(
	'post_type'      => 'ib_edu_membership',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'order'          => 'ASC',
	'orderby'        => 'menu_order',
) );

if ( $query->have_posts() ) :
	$tmp_more = $GLOBALS['more'];
	$GLOBALS['more'] = 0;
	?>
	<div class="ib-edu-memberships">
	<?php
		while ( $query->have_posts() ) {
			$query->the_post();
			IB_Educator_View::template_part( 'content', 'membership' );
		}
	?>
	</div>
	<?php
	$GLOBALS['more'] = $tmp_more;
	wp_reset_postdata();
else :
	echo '<p>' . __( 'No memberships found.', 'ibeducator' ) . '</p>';
endif;
?>
