<?php
/**
 * Renders the student's membership page.
 *
 * @version 1.1.0
 */

$user_id = get_current_user_id();

if ( ! $user_id ) {
	echo '<p>' . __( 'Please log in to view this page.', 'ibeducator' ) . '</p>';
	return;
}

$ms = Edr_Memberships::get_instance();

// Get current user's membership data.
$user_membership = $ms->get_user_membership( $user_id );

if ( ! $user_membership ) {
	echo '<p>' . __( 'Your account is not connected to a membership.', 'ibeducator' ) . '</p>';
	return;
}

// Get membership data.
$membership = $ms->get_membership( $user_membership['membership_id'] );
$membership_meta = $ms->get_membership_meta( $user_membership['membership_id'] );

if ( ! $membership ) {
	return;
}

$action = get_query_var( 'edu-action' );

switch ( $action ) {
	default:
		?>
		<table class="ib-edu-membership">
			<tbody>
				<tr>
					<th style="width:30%;"><?php _e( 'Membership Level', 'ibeducator' ); ?></th>
					<td>
						<?php echo esc_html( $membership->post_title ); ?>
						<div>
							<?php
								echo ib_edu_purchase_link( array(
									'object_id' => $membership->ID,
									'type'      => 'membership',
									'class'     => array( 'extend-membership' ),
									'text'      => __( 'Extend', 'ibeducator' ),
								) );
							?>
						</div>
					</td>
				</tr>

				<tr>
					<th><?php _e( 'Status', 'ibeducator' ); ?></th>
					<td>
						<?php
							$statuses = $ms->get_statuses();

							if ( ! empty( $user_membership['status'] ) && array_key_exists( $user_membership['status'], $statuses ) ) {
								echo esc_html( $statuses[ $user_membership['status'] ] );
							}
						?>

						<div>
						<?php
							if ( 1 == ib_edu_get_option( 'pause_memberships', 'memberships' ) ) {
								if ( 'active' == $user_membership['status'] ) {
									$pause_url = ib_edu_get_endpoint_url( 'edu-action', 'pause-membership', get_permalink() );
									$pause_url = add_query_arg( '_wpnonce', wp_create_nonce( 'ib_educator_pause_membership' ), $pause_url );

									echo ' <span class="pause-membership"><a class="ib-edu-button" href="' . esc_url( $pause_url )
										. '">' . __( 'Pause', 'ibeducator' ) . '</a></span>';
								} elseif ( 'paused' == $user_membership['status'] ) {
									$resume_url = ib_edu_get_endpoint_url( 'edu-action', 'resume-membership', get_permalink() );
									$resume_url = add_query_arg( '_wpnonce', wp_create_nonce( 'ib_educator_resume_membership' ), $resume_url );

									echo ' <span class="pause-membership"><a class="ib-edu-button" href="' . esc_url( $resume_url )
										. '">' . __( 'Resume', 'ibeducator' ) . '</a></span>';
								}
							}
						?>
						</div>
					</td>
				</tr>

				<tr>
					<th><?php _e( 'Expiration Date', 'ibeducator' ); ?></th>
					<td>
						<?php
							if ( ! $user_membership['expiration'] ) {
								_e( 'None', 'ibeducator' );
							} else {
								$date_format = get_option( 'date_format' );

								if ( 'days' == $membership_meta['period'] ) {
									$date_format .= ' ' . get_option( 'time_format' );
								}

								echo esc_html( date_i18n( $date_format, $user_membership['expiration'] ) );
							}
						?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
}
?>
