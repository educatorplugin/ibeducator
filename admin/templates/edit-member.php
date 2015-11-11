<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! current_user_can( 'manage_educator' ) ) {
	echo '<p>' . __( 'Access denied', 'ibeducator' ) . '</p>';
	exit;
}

$api = IB_Educator::get_instance();
$member_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
$user = null;
$user_membership = null;
$ms = Edr_Memberships::get_instance();

if ( $member_id ) {
	$user_membership = $ms->get_user_membership( $member_id );

	if ( $user_membership ) {
		$user = get_user_by( 'id', $user_membership['user_id'] );
	}
}

if ( ! $user_membership ) {
	$user_membership = array(
		'ID'            => 0,
		'user_id'       => 0,
		'membership_id' => 0,
		'status'        => '',
		'expiration'    => 0,
		'paused'        => 0,
	);
}
?>
<div class="wrap">
	<h2><?php
		if ( $member_id ) {
			_e( 'Edit Member', 'ibeducator' );
		} else {
			_e( 'Add Member', 'ibeducator' );
		}
	?></h2>

	<?php
		$errors = ib_edu_message( 'edit_member_errors' );

		if ( $errors ) {
			echo '<div class="error below-h2"><ul>';

			foreach ( $errors as $error ) {
				switch ( $error ) {
					case 'member_exists':
						echo '<li>' . __( 'The membership for this student already exists.', 'ibeducator' ) . '</li>';
						break;
				}
			}

			echo '</ul></div>';
		}
	?>

	<?php if ( isset( $_GET['edu-message'] ) && 'saved' == $_GET['edu-message'] ) : ?>
		<div id="message" class="updated below-h2">
			<p><?php _e( 'Member updated.', 'ibeducator' ); ?></p>
		</div>
	<?php endif; ?>

	<form id="edu_edit_member_form"
	      class="ib-edu-admin-form"
	      action="<?php echo esc_url( admin_url( 'admin.php?page=ib_educator_members&edu-action=edit-member&id=' . $member_id ) ); ?>"
	      method="post">
		<?php wp_nonce_field( 'ib_educator_edit_member' ); ?>
		<input type="hidden" id="autocomplete-nonce" value="<?php echo wp_create_nonce( 'ib_educator_autocomplete' ); ?>">

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">
				<div id="postbox-container-1" class="postbox-container">
					<div id="side-sortables" class="meta-box-sortables">
						<div id="member-settings" class="postbox">
							<div class="handlediv"><br></div>
							<h3 class="hndle"><span><?php _e( 'Member', 'ibeducator' ); ?></span></h3>
							<div class="inside">
								<!-- Status -->
								<?php
									$statuses = $ms->get_statuses();
								?>
								<div class="ib-edu-field edu-block">
									<div class="ib-edu-label"><label for="membership-status"><?php _e( 'Status', 'ibeducator' ); ?></label></div>
									<div class="ib-edu-control">
										<select id="membership-status" name="membership_status">
											<?php
												foreach ( $statuses as $key => $value ) {
													$selected = ( $key == $user_membership['status'] ) ? ' selected="selected"' : '';
													
													echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $value ) . '</option>';
												}
											?>
										</select>
									</div>
								</div>
							</div>
							<div class="edu-actions-box">
								<div id="major-publishing-actions">
									<div id="publishing-action">
										<?php submit_button( null, 'primary', 'submit', false ); ?>
									</div>
									<div class="clear"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div id="postbox-container-2" class="postbox-container">
					<div id="normal-sortables" class="meta-box-sortables">
						<div id="member-settings" class="postbox">
							<div class="handlediv"><br></div>
							<h3 class="hndle"><span><?php _e( 'Data', 'ibeducator' ); ?></span></h3>
							<div class="inside">
								<!-- Member -->
								<?php
									$username = '';

									if ( $user ) {
										$username = $user->display_name;
									}
								?>
								<div class="ib-edu-field">
									<div class="ib-edu-label"><label for="member"><?php _e( 'Member', 'ibeducator' ); ?></label></div>
									<div class="ib-edu-control">
										<div class="ib-edu-autocomplete">
											<input type="hidden" name="user_id" class="ib-edu-autocomplete-value" value="<?php echo intval( $user_membership['user_id'] ); ?>">
											<input
												type="text"
												id="member-id"
												name="user_id"
												class="regular-text"
												autocomplete="off"
												value="<?php echo intval( $user_membership['user_id'] ); ?>"
												data-label="<?php echo esc_attr( $username ); ?>"<?php if ( $user_membership['ID'] ) echo ' disabled="disabled"'; ?>>
										</div>
									</div>
								</div>

								<!-- Membership Level -->
								<?php
									$memberships = $ms->get_memberships();
								?>
								<div class="ib-edu-field">
									<div class="ib-edu-label"><label for="membership-id"><?php _e( 'Membership Level', 'ibeducator' ); ?></label></div>
									<div class="ib-edu-control">
										<select id="membership-id" name="membership_id">
											<?php
												if ( $memberships ) {
													foreach ( $memberships as $membership ) {
														$selected = ( $membership->ID == $user_membership['membership_id'] ) ? ' selected="selected"' : '';

														echo '<option value="' . intval( $membership->ID ) . '"' . $selected . '>'
															 . esc_html( $membership->post_title ) . '</option>';
													}
												}
											?>
										</select>
									</div>
								</div>
								
								<!-- Expiration Date -->
								<div class="ib-edu-field">
									<div class="ib-edu-label"><label for="membership-expiration"><?php _e( 'Expiration Date', 'ibeducator' ); ?></label></div>
									<div class="ib-edu-control">
										<input type="text" id="membership-expiration" name="expiration" value="<?php echo ( ! empty( $user_membership['expiration'] ) ) ? esc_attr( date( 'Y-m-d H:i:s', $user_membership['expiration'] ) ) : '0000-00-00 00:00:00'; ?>">
										<div class="description">
											<?php _e( 'Enter the date like yyyy-mm-dd hh:mm:ss', 'ibeducator' ); ?>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div id="member-payments" class="postbox closed">
							<div class="handlediv"><br></div>
							<h3 class="hndle"><span><?php _e( 'Payments', 'ibeducator' ); ?></span></h3>
							<div class="inside">
								<?php
									$payments = $api->get_payments( array(
										'payment_type' => 'membership',
										'user_id'      => $user_membership['user_id'],
									) );

									if ( ! empty( $payments ) ) :
								?>
									<ul>
										<?php
											foreach ( $payments as $payment ) {
												echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=ib_educator_payments&edu-action=edit-payment&payment_id=' . $payment->ID ) )
													. '">#' . intval( $payment->ID ) . '</a> (' . esc_html( date( 'Y-m-d', strtotime( $payment->payment_date ) ) ) . ')</li>';
											}
										?>
									</ul>
								<?php else : ?>
									<p><?php _e( 'No payments found.', 'ibeducator' ); ?></p>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<br class="clear">
		</div>
	</form>
</div>

<script>
jQuery(document).ready(function() {
	ibEducatorAutocomplete(document.getElementById('member-id'), {
		key: 'id',
		value: 'name',
		searchBy: 'name',
		nonce: jQuery('#autocomplete-nonce').val(),
		url: <?php echo json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
		entity: 'user'
	});

	postboxes.add_postbox_toggles(pagenow);
});
</script>