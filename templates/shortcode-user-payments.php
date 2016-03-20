<?php
/**
 * Renders the student's payments page.
 *
 * @version 1.1.0
 */

$user_id = get_current_user_id();

if ( ! $user_id ) {
	echo '<p>' . __( 'Please log in to view this page.', 'ibeducator' ) . '</p>';
	return;
}

$edr_payments = Edr_Payments::get_instance();
$payments = $edr_payments->get_payments( array( 'user_id' => $user_id ) );

// Output status message.
$message = get_query_var( 'edu-message' );

if ( 'payment-cancelled' == $message ) {
	echo '<div class="edr-message ib-edu-message success">' . __( 'Payment has been cancelled.', 'ibeducator' ) . '</div>';
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
			$statuses = edr_get_payment_statuses();
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
			<td><?php echo edr_format_price( $payment->amount, false ); ?></td>
			<td class="actions-group">
				<?php
					$invoice_url = edr_get_endpoint_url( 'edu-thankyou', $payment->ID, get_permalink( edr_get_page_id( 'payment' ) ) );
				?>
				<a href="<?php echo esc_url( $invoice_url ); ?>"><?php _e( 'Details', 'ibeducator' ); ?></a>

				<?php if ( 'pending' == $payment->payment_status ) : ?>
					<?php
						$cancel_payment_url = edr_get_endpoint_url( 'edu-action', 'cancel-payment', get_permalink() );
						$cancel_payment_url = add_query_arg( 'payment_id', $payment->ID, $cancel_payment_url );
						$cancel_payment_url = wp_nonce_url( $cancel_payment_url, 'edr_cancel_payment', '_wpnonce' );
					?>
					<a href="<?php echo esc_url( $cancel_payment_url ); ?>" class="cancel-payment"><?php _e( 'Cancel', 'ibeducator' ); ?></a>
				<?php endif; ?>
			</td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
<?php else : ?>
<p><?php _e( 'No payments found.', 'ibeducator' ); ?></p>
<?php endif; ?>
