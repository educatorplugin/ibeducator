<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! current_user_can( 'manage_educator' ) ) {
	echo '<p>' . __( 'Access denied', 'ibeducator' ) . '</p>';
	exit;
}

$api = IB_Educator::get_instance();
$page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
$statuses = IB_Educator_Payment::get_statuses();
$types = IB_Educator_Payment::get_types();
$args = array(
	'per_page' => 10,
	'page'     => $page
);

if ( ! empty( $_GET['status'] ) && array_key_exists( $_GET['status'], $statuses ) ) {
	$args['payment_status'] = array( $_GET['status'] );
}

if ( ! empty( $_GET['id'] ) ) {
	$args['payment_id'] = $_GET['id'];
}

if ( ! empty( $_GET['payment_type'] ) ) {
	$args['payment_type'] = $_GET['payment_type'];
}

$payments = $api->get_payments( $args );
?>
<div class="wrap">
	<h2>
		<?php _e( 'Educator Payments', 'ibeducator' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ib_educator_payments&edu-action=edit-payment' ) ); ?>" class="add-new-h2"><?php _e( 'Add New', 'ibeducator' ); ?></a>
	</h2>

	<div class="ib-edu-tablenav top">
		<form class="ib-edu-admin-search alignleft" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get">
			<input type="hidden" name="page" value="ib_educator_payments">

			<div class="block">
				<label for="search-payment-id"><?php echo _x( 'ID', 'ID of an item', 'ibeducator' ); ?></label>
				<input type="text" id="search-payment-id" name="id" value="<?php if ( ! empty( $args['payment_id'] ) ) echo intval( $args['payment_id'] ); ?>">
			</div>

			<div class="block">
				<label for="search-payment-type"><?php _e( 'Payment Type', 'ibeducator' ); ?></label>
				<select id="search-payment-type" name="payment_type">
					<option value=""><?php _e( 'All', 'ibeducator' ); ?></option>
					<?php
						foreach ( $types as $t_value => $t_name ) {
							$selected = ( isset( $args['payment_type'] ) && $args['payment_type'] == $t_value ) ? ' selected="selected"' : '';
							echo '<option value="' . esc_attr( $t_value ) . '"' . $selected . '>' . esc_html( $t_name ) . '</option>';
						}
					?>
				</select>
			</div>

			<div class="block">
				<label for="search-payment-status"><?php _e( 'Status', 'ibeducator' ); ?></label>
				<select id="search-payment-status" name="status">
					<option value=""><?php _e( 'All', 'ibeducator' ); ?></option>
					<?php
						foreach ( $statuses as $key => $value ) {
							$selected = ( isset( $args['payment_status'] ) && in_array( $key, $args['payment_status'] ) ) ? ' selected="selected"' : '';
							echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $value ) . '</option>';
						}
					?>
				</select>
			</div>

			<div class="block">
				<input type="submit" class="button" value="<?php _e( 'Search', 'ibeducator' ); ?>">
			</div>
		</form>

		<div class="num-items">
			<?php echo sprintf( _n( '%d item', '%d items', $payments['num_items'], 'ibeducator' ), intval( $payments['num_items'] ) ); ?>
		</div>

		<br class="clear">
	</div>

	<?php if ( ! empty( $payments['rows'] ) ) : ?>

	<table id="ib-edu-payments-table" class="wp-list-table widefat">
		<thead>
			<th><?php _e( 'ID', 'ibeducator' ); ?></th>
			<th><?php _e( 'Item', 'ibeducator' ); ?></th>
			<th><?php _e( 'Payment Type', 'ibeducator' ); ?></th>
			<th><?php _e( 'Username', 'ibeducator' ); ?></th>
			<th><?php _e( 'Amount', 'ibeducator' ); ?></th>
			<th><?php _e( 'Method', 'ibeducator' ); ?></th>
			<th><?php _e( 'Status', 'ibeducator' ); ?></th>
			<th><?php _e( 'Date', 'ibeducator' ); ?></th>
		</thead>
		<tbody>
		<?php $i = 0; ?>
		<?php foreach ( $payments['rows'] as $payment ) : ?>
		<?php
			$student = get_user_by( 'id', $payment->user_id );
			$username = '';

			if ( $student ) {
				$username = $student->user_login;
			}

			if ( 'course' == $payment->payment_type ) {
				$post = get_post( $payment->course_id );
			} elseif ( 'membership' == $payment->payment_type ) {
				$post = get_post( $payment->object_id );
			}

			$object_title = '';

			if ( $post ) {
				$object_title = $post->post_title;
			}

			$edit_url = admin_url( 'admin.php?page=ib_educator_payments&edu-action=edit-payment&payment_id=' . $payment->ID );
		?>
		<tr<?php if ( 0 == $i % 2 ) echo ' class="alternate"'; ?>>
			<td><?php echo absint( $payment->ID ); ?></td>
			<td>
				<span class="row-title"><?php echo esc_html( $object_title ); ?></span>
				<div class="row-actions">
					<span class="edit">
						<a class="ib-edu-item-edit"
						   href="<?php echo esc_url( $edit_url ); ?>">
						   <?php _e( 'Edit', 'ibeducator' ); ?>
						</a> |
					</span>
					<span class="trash">
						<a class="ib-edu-item-delete"
						   data-payment_id="<?php echo absint( $payment->ID ); ?>"
						   data-wpnonce="<?php echo wp_create_nonce( 'ib_educator_delete_payment_' . absint( $payment->ID ) ); ?>"
						   href="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
						   <?php _e( 'Delete', 'ibeducator' ); ?>
						</a>
					</span>
				</div>
			</td>
			<td><?php echo esc_html( $payment->payment_type ); ?></td>
			<td><?php echo esc_html( $username ); ?></td>
			<td><?php echo sanitize_title( $payment->currency ) . ' ' . number_format( $payment->amount, 2 ); ?></td>
			<td><?php echo sanitize_title( $payment->payment_gateway ); ?></td>
			<td><?php echo sanitize_title( $payment->payment_status ); ?></td>
			<td><?php echo date( 'j M, Y H:i', strtotime( $payment->payment_date ) ); ?></td>
		</tr>
		<?php ++$i; ?>
		<?php endforeach; ?>
		</tbody>
	</table>

	<div class="ib-edu-tablenav bottom">
		<div class="ib-edu-pagination alignleft">
			<?php
				$big = 999999999;

				echo paginate_links( array(
					'base'     => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
					'format'   => '?paged=%#%',
					'current'  => $page,
					'total'    => $payments['num_pages'],
					'add_args' => false,
				) );
			?>
		</div>

		<div class="num-items">
			<?php echo sprintf( _n( '%d item', '%d items', $payments['num_items'], 'ibeducator' ), intval( $payments['num_items'] ) ); ?>
		</div>

		<br class="clear">
	</div>

	<?php else : ?>

	<p><?php _e( 'No payments found.', 'ibeducator' ); ?></p>

	<?php endif; ?>
</div>
<script>
(function($) {
	$('#ib-edu-payments-table').on('click', 'a.ib-edu-item-delete', function(e) {
		e.preventDefault();

		if ( confirm( '<?php _e( 'Are you sure you want to delete this item?', 'ibeducator' ); ?>' ) ) {
			var a = $(this);

			$.ajax({
				type: 'post',
				cache: false,
				data: {
					action: 'ib_educator_delete_payment',
					payment_id: a.data('payment_id'),
					_wpnonce: a.data('wpnonce')
				},
				url: a.attr('href'),
				success: function(response) {
					if (response === 'success') {
						a.closest('tr').remove();

						var paymentsTable = $('#ib-edu-payments-table');

						if (!paymentsTable.find('> tbody > tr').length) {
							paymentsTable.hide();
						}
					}
				}
			});
		}
	});
})(jQuery);
</script>