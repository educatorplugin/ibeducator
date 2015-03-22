<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! current_user_can( 'manage_educator' ) ) {
	echo '<p>' . __( 'Access denied', 'ibeducator' ) . '</p>';
	exit;
}

$ms = IB_Educator_Memberships::get_instance();
$per_page = 10;
$page = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
$offset = ( $page - 1 ) * $per_page;
$args = array(
	'number' => $per_page,
	'offset' => $offset,
);
$member_name = '';

if ( ! empty( $_GET['member_name'] ) ) {
	$member_name = $_GET['member_name'];
	$args['search'] = '*' . $member_name . '*';
}

$members_query = $ms->get_members( $args );
?>
<div class="wrap">
	<h2>
		<?php _e( 'Educator Members', 'ibeducator' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=ib_educator_members&edu-action=edit-member' ) ); ?>" class="add-new-h2"><?php _e( 'Add New', 'ibeducator' ); ?></a>
	</h2>

	<div class="ib-edu-tablenav top">
		<form class="ib-edu-admin-search alignleft" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get">
			<input type="hidden" name="page" value="ib_educator_members">

			<div class="block">
				<label for="search-member-name"><?php _e( 'Search Members', 'ibeducator' ); ?></label>
				<input type="text" id="search-member-name" name="member_name" value="<?php if ( ! empty( $member_name ) ) echo esc_attr( $member_name ); ?>">
			</div>

			<div class="block">
				<input type="submit" class="button" value="<?php _e( 'Search', 'ibeducator' ); ?>">
			</div>
		</form>

		<div class="num-items">
			<?php echo sprintf( _n( '%d item', '%d items', $members_query->total_users, 'ibeducator' ), intval( $members_query->total_users ) ); ?>
		</div>

		<br class="clear">
	</div>

	<?php if ( ! empty( $members_query->results ) ) : ?>

	<table id="ib-edu-members-table" class="wp-list-table widefat">
		<thead>
			<th><?php _e( 'Username', 'ibeducator' ); ?></th>
			<th><?php _e( 'Name', 'ibeducator' ); ?></th>
			<th><?php _e( 'Membership Level', 'ibeducator' ); ?></th>
			<th><?php _e( 'Status', 'ibeducator' ); ?></th>
			<th><?php _e( 'Expiration Date', 'ibeducator' ); ?></th>
		</thead>
		<tbody>
		<?php
			$statuses = $ms->get_statuses();
			$i = 0;
		?>
		<?php foreach ( $members_query->results as $member ) : ?>
		<tr<?php if ( 0 == $i % 2 ) echo ' class="alternate"'; ?>>
			<?php
				$user_membership = $ms->get_user_membership( $member->ID );
				$edit_member_url = admin_url( 'admin.php?page=ib_educator_members&edu-action=edit-member&id=' . $member->ID );
			?>
			<td>
				<span class="row-title">
					<?php
						echo '<a href="' . esc_url( $edit_member_url ) . '">' . esc_html( $member->user_login ) . '</a>';
					?>
				</span>

				<div class="row-actions">
					<span class="edit">
						<a class="ib-edu-item-edit"
						   href="<?php echo esc_url( $edit_member_url ); ?>">
						   <?php _e( 'Edit', 'ibeducator' ); ?>
						</a>
					</span>
				</div>
			</td>
			<td>
				<?php echo esc_html( $member->display_name ); ?>
			</td>
			<td>
				<?php
					if ( $user_membership['membership_id'] ) {
						echo get_the_title( $user_membership['membership_id'] );
					}
				?>
			</td>
			<td>
				<?php
					if ( array_key_exists( $user_membership['status'], $statuses ) ) {
						echo esc_html( $statuses[ $user_membership['status'] ] );
					}
				?>
			</td>
			<td>
				<?php
					if ( ! empty( $user_membership['expiration'] ) ) {
						echo esc_html( date_i18n( get_option( 'date_format' ), $user_membership['expiration'] ) );
					} else {
						_e( 'None', 'ibeducator' );
					}
				?>
			</td>
		</tr>
		<?php ++$i; ?>
		<?php endforeach; ?>
		</tbody>
	</table>

	<div class="ib-edu-tablenav bottom">
		<div class="ib-edu-pagination alignleft">
			<?php
				$big = 999999999;
				$num_pages = ceil( $members_query->total_users / $per_page );

				echo paginate_links( array(
					'base'     => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
					'format'   => '?paged=%#%',
					'current'  => $page,
					'total'    => $num_pages,
					'add_args' => false,
				) );
			?>
		</div>

		<div class="num-items">
			<?php echo sprintf( _n( '%d item', '%d items', $members_query->total_users, 'ibeducator' ), intval( $members_query->total_users ) ); ?>
		</div>

		<br class="clear">
	</div>

	<?php else : ?>

	<p><?php _e( 'No members found.', 'ibeducator' ); ?></p>

	<?php endif; ?>
</div>