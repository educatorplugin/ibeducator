<?php
if ( ! defined( 'ABSPATH' ) ) exit();

if ( ! current_user_can( 'manage_educator' ) ) {
	echo '<p>' . __( 'Access denied', 'ibeducator' ) . '</p>';

	exit();
}

$payments_table = new Edr_Admin_PaymentsTable();
$payments_table->prepare_items();
?>

<div class="wrap">
	<h2>
		<?php _e( 'Educator Payments', 'ibeducator' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ib_educator_payments&edu-action=edit-payment' ) ); ?>" class="add-new-h2"><?php _e( 'Add New', 'ibeducator' ); ?></a>
	</h2>

	<?php $payments_table->display_payment_filters(); ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ib_educator_payments' ) ); ?>">
		<?php $payments_table->display(); ?>
	</form>
</div>

<script>
	(function($) {
		$('table.payments').on('click', 'a.delete-payment', function(e) {
			if (!confirm( '<?php _e( 'Are you sure you want to delete this item?', 'ibeducator' ); ?>')) {
				e.preventDefault();
			}
		});
	})(jQuery);
</script>
