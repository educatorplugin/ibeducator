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
$ms = IB_Educator_Memberships::get_instance();

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

		<?php
			$statuses = $ms->get_statuses();
		?>
		<div class="ib-edu-field">
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
		
		<div class="ib-edu-field">
			<div class="ib-edu-label"><label for="membership-expiration"><?php _e( 'Expiration Date', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<input type="text" id="membership-expiration" name="expiration" value="<?php echo ( ! empty( $user_membership['expiration'] ) ) ? esc_attr( date( 'Y-m-d H:i:s', $user_membership['expiration'] ) ) : '0000-00-00 00:00:00'; ?>">
				<div class="description">
					<?php _e( 'Enter the date like yyyy-mm-dd hh:mm:ss', 'ibeducator' ); ?>
				</div>
			</div>
		</div>

		<?php
			$payments = $api->get_payments( array(
				'payment_type' => 'membership',
				'user_id'      => $user_membership['user_id'],
			) );

			if ( ! empty( $payments ) ) :
		?>
		<div class="ib-edu-field">
			<div class="ib-edu-label"><label><?php _e( 'Payments', 'ibeducator' ); ?></label></div>
			<div class="ib-edu-control">
				<ul>
				<?php
					foreach ( $payments as $payment ) {
						echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=ib_educator_payments&edu-action=edit-payment&payment_id=' . $payment->ID ) )
							. '">#' . intval( $payment->ID ) . '</a> (' . esc_html( date( 'Y-m-d', strtotime( $payment->payment_date ) ) ) . ')</li>';
					}
				?>
				</ul>
			</div>
		</div>
		<?php endif; ?>

		<?php submit_button(); ?>
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
});
</script>