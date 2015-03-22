<?php
$user_id = get_current_user_id();

if ( ! $user_id ) {
	echo '<p>' . __( 'Please log in to view this page.', 'ibeducator' ) . '</p>';
	return;
}

$api = IB_Educator::get_instance();
$payments = $api->get_payments( array(
	'user_id' => $user_id,
) );

// Output status message.
$message = get_query_var( 'edu-message' );

if ( 'payment-cancelled' == $message ) {
	echo '<div class="ib-edu-message success">' . __( 'Payment has been cancelled.', 'ibeducator' ) . '</div>';
}
?>

<?php if ( ! empty( $payments ) ) : ?>
	<table class="ib-edu-payments">
		<thead>
			<tr>
				<th><?php _e( 'ID', 'ibeducator' ); ?></th>
				<th><?php _e( 'Date', 'ibeducator' ); ?></th>
				<th><?php _e( 'Payment Status', 'ibeducator' ); ?></th>
				<th><?php _e( 'Amount', 'ibeducator' ); ?></th>
				<th></th>
			</tr>
		</thead>
		<tbody>
		<?php
			$statuses = IB_Educator_Payment::get_statuses();
		?>
		<?php foreach ( $payments as $payment ) : ?>
		<tr>
			<td><?php echo absint( $payment->ID ); ?></td>
			<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $payment->payment_date ) ) ); ?></td>
			<td>
				<?php
					if ( array_key_exists( $payment->payment_status, $statuses ) ) {
						echo esc_html( $statuses[ $payment->payment_status ] );
					}
				?>
			</td>
			<td><?php echo ib_edu_format_price( $payment->amount, false ); ?></td>
			<td class="actions-group">
				<?php
					$invoice_url = ib_edu_get_endpoint_url( 'edu-thankyou', $payment->ID, get_permalink( ib_edu_page_id( 'payment' ) ) );
				?>
				<a href="<?php echo esc_url( $invoice_url ); ?>"><?php _e( 'Details', 'ibeducator' ); ?></a>

				<?php if ( 'pending' == $payment->payment_status ) : ?>
				<form action="<?php echo esc_url( ib_edu_get_endpoint_url( 'edu-action', 'cancel-payment', get_permalink() ) ); ?>" method="post">
					<?php wp_nonce_field( 'ibedu_cancel_payment' ); ?>
					<input type="hidden" name="payment_id" value="<?php echo absint( $payment->ID ); ?>">
					<button type="submit" class="ib-edu-button"><?php _e( 'Cancel', 'ibeducator' ); ?></button>
				</form>
			<?php endif; ?>
			</td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
<?php else : ?>
<p><?php _e( 'No payments found.', 'ibeducator' ); ?></p>
<?php endif; ?>