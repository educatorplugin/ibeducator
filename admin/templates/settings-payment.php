<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Get all gateway objects
$gateways = IB_Educator_Main::get_gateways();
$gateway_id = '';

if ( isset( $_GET['gateway_id'] ) ) {
	$gateway_id = $_GET['gateway_id'];
} elseif ( isset( $_POST['gateway_id'] ) ) {
	$gateway_id = $_POST['gateway_id'];
}

if ( ! empty( $gateway_id ) && ! isset( $gateways[ $gateway_id ] ) ) {
	return;
} elseif ( empty( $gateway_id ) ) {
	reset( $gateways );
	$gateway_id = key( $gateways );
}

$message = isset( $_GET['edu-message'] ) ? $_GET['edu-message'] : '';
?>

<div class="wrap">
	<h2><?php _e( 'Educator Settings', 'ibeducator' ); ?></h2>

	<?php if ( 'saved' == $message ) : ?>
	<div id="message" class="updated below-h2">
		<p><?php _e( 'Payment options updated.', 'ibeducator' ); ?></p>
	</div>
	<?php elseif ( 'not_saved' == $message ) : ?>
	<div id="message" class="updated below-h2">
		<p><?php _e( 'Update failed or options values have not changed.', 'ibeducator' ); ?></p>
	</div>
	<?php endif; ?>

	<?php
		self::settings_tabs( 'payment' );

		if ( ! $gateway_id ) {
			return;
		}
	?>

	<ul class="ib-edu-tabs">
		<li class="title"><span><?php _e( 'Payment Gateways:', 'ibeducator' ); ?></span></li>
		<?php foreach ( $gateways as $id => $obj ) : ?>
			<?php
				if ( ! $obj->is_editable() ) {
					continue;
				}
			?>
			<?php if ( $gateway_id == $id ) : ?>
			<li class="active"><span><?php echo $obj->get_title(); ?></span></li>
			<?php else : ?>
			<li><a href="<?php echo admin_url( 'admin.php?page=ib_educator_admin&tab=payment&gateway_id=' . $id ); ?>"><?php echo $obj->get_title(); ?></a></li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ul>

	<form action="<?php echo admin_url( 'admin.php?page=ib_educator_admin&edu-action=edit-payment-gateway&tab=payment' ); ?>" method="post">
		<?php wp_nonce_field( 'ib_educator_payments_settings' ); ?>

		<input type="hidden" name="gateway_id" value="<?php echo esc_attr( $gateway_id ); ?>">

		<?php
			if ( isset( $gateways[ $gateway_id ] ) ) {
				$gateways[ $gateway_id ]->admin_options_form();
				submit_button();
			}
		?>
	</form>
</div>