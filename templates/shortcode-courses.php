<?php
/**
 * Renders the [courses] shortcode.
 *
 * @version 1.1.0
 */
?>
<?php if ( $courses->have_posts() ) : ?>
	<?php
		$columns = isset( $atts['columns'] ) ? intval( $atts['columns'] ) : 1;
		$classes = apply_filters( 'ib_educator_courses_list_classes', array(
			'ib-edu-courses-list',
			'ib-edu-courses-list-' . $columns,
		) );
	?>
	<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
		<?php
			while ( $courses->have_posts() ) {
				$courses->the_post();
				Edr_View::template_part( 'content', 'course' );
			}
		?>
	</div>

	<?php
		wp_reset_postdata();
	?>

	<?php if ( ! isset( $atts['nopaging'] ) || 1 != $atts['nopaging'] ) : ?>
		<div class="ib-edu-pagination">
			<?php
				$big = 999999999;

				echo paginate_links( array(
					'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
					'format'  => '?paged=%#%',
					'current' => max( 1, $args['paged'] ),
					'total'   => $courses->max_num_pages,
				) );
			?>
		</div>
	<?php endif; ?>
<?php endif; ?>
