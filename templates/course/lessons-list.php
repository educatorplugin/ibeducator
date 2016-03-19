<?php if ( $query && $query->have_posts() ) : ?>
	<ul class="edr-lessons">
		<?php while ( $query->have_posts() ) : $query->the_post(); ?>
			<li id="lesson-<?php the_ID(); ?>" class="edr-lesson">
				<div class="lesson-header"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></div>

				<?php if ( has_excerpt() ) : ?>
					<div class="excerpt">
						<?php the_excerpt(); ?>
					</div>
				<?php endif; ?>

				<?php
					if ( edr_post_has_quiz( get_the_ID() ) ) {
						echo '<div class="lesson-meta"><span class="quiz">' . __( 'Quiz', 'ibeducator' ) . '</span></div>';
					}
				?>
			</li>
		<?php endwhile; ?>

		<?php wp_reset_postdata(); ?>
	</ul>
<?php endif; ?>
