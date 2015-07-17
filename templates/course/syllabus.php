<h2 id="edr-syllabus-title"><?php _e( 'Lessons', 'ibeducator' ); ?></h2>
<ul class="edr-syllabus">
	<?php foreach ( $syllabus as $group ) : ?>
		<li class="group">
			<div class="group-header"><h3><?php echo esc_html( $group['title'] ); ?></h3></div>
			<?php if ( ! empty( $group['lessons'] ) ) : ?>
				<div class="group-body">
					<ul class="edr-syllabus-lessons">
						<?php
							global $post;

							foreach ( $group['lessons'] as $lesson_id ) {
								if ( isset( $lessons[ $lesson_id ] ) ) {
									$post = $lessons[ $lesson_id ];

									setup_postdata( $post );
									?>
										<li>
											<h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>

											<?php if ( has_excerpt() ) : ?>
												<div class="excerpt">
													<?php the_excerpt(); ?>
												</div>
											<?php endif; ?>
										</li>
									<?php
								}
							}

							wp_reset_postdata();
						?>
					</ul>
				</div>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
