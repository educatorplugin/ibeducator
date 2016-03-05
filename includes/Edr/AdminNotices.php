<?php

/**
 * This class displays admin notices.
 */
class Edr_AdminNotices {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'quiz_uploads_notice' ) );
		add_action( 'edr_action_dismiss-notice', array( $this, 'dismiss_notice' ) );
	}

	/**
	 * Display quiz uploads protection notice if necessary.
	 */
	public static function quiz_uploads_notice() {
		$user_can_manage = current_user_can( 'manage_educator' );
		$user_id = get_current_user_id();

		if ( stristr( $_SERVER['SERVER_SOFTWARE'], 'apache' ) ) {
			// Apache.
			if ( $user_can_manage && ! edr_protect_htaccess_exists() && ! get_user_meta( $user_id, '_edr_uploads_htaccess_dismissed', true ) ) {
				$private_uploads_dir = edr_get_private_uploads_dir();
				$upload = new Edr_Upload();

				ob_start();
				?>
				<div class="error">
					<p><?php printf( __( 'Private uploads are not currently protected in %s, because the Educator .htaccess file is missing.', 'ibeducator' ), $private_uploads_dir ); ?></p>
					<p><?php printf( __( 'Please create the .htaccess file in %s directory, and paste the following code into it:', 'ibeducator' ), $private_uploads_dir ); ?></p>
					<pre><?php echo $upload->generate_protect_htaccess(); ?></pre>
					<p><?php _e( 'Please make sure that the private uploads directory is protected before dismissing this notice.', 'ibeducator' ); ?></p>
					<p><?php printf( '<a href="%s">%s</a>', esc_url( add_query_arg( array( 'edu-action' => 'dismiss-notice', 'edr-notice' => 'uploads_htaccess' ) ) ), __( 'Dismiss Notice', 'ibeducator' ) ) ?></p>
				</div>
				<?php
				echo ob_get_clean();
			}
		} elseif ( stristr( $_SERVER['SERVER_SOFTWARE'], 'nginx' ) ) {
			// nginx.
			if ( $user_can_manage && ! get_user_meta( $user_id, '_edr_uploads_nginx_dismissed', true ) ) {
				$private_uploads_dir = edr_get_private_uploads_dir();

				ob_start();
				?>
				<div class="error">
					<p><?php printf( __( 'Private uploads are not currently protected in %s. You must add a redirect rule to protect them.', 'ibeducator' ), $private_uploads_dir ); ?></p>
					<p><?php printf( __( 'Please read the <a href="http://educatorplugin.com/protect-private-uploads" target="_blank">Protect Private Uploads</a> article.', 'ibeducator' ) ); ?></p>
					<p><?php _e( 'Please make sure that the private uploads directory is protected before dismissing this notice.', 'ibeducator' ); ?></p>
					<p><?php printf( '<a href="%s">%s</a>', esc_url( add_query_arg( array( 'edu-action' => 'dismiss-notice', 'edr-notice' => 'uploads_nginx' ) ) ), __( 'Dismiss Notice', 'ibeducator' ) ) ?></p>
				</div>
				<?php
				echo ob_get_clean();
			}
		} else {
			// Other web servers.
			if ( $user_can_manage && ! get_user_meta( $user_id, '_edr_uploads_other_dismissed', true ) ) {
				$private_uploads_dir = edr_get_private_uploads_dir();

				ob_start();
				?>
				<div class="error">
					<p><?php printf( __( 'Private uploads may not be currently protected in %s.', 'ibeducator' ), $private_uploads_dir ); ?></p>
					<p><?php printf( __( 'Please read the <a href="http://educatorplugin.com/protect-private-uploads" target="_blank">Protect Private Uploads</a> article.', 'ibeducator' ) ); ?></p>
					<p><?php _e( 'Please make sure that the private uploads directory is protected before dismissing this notice.', 'ibeducator' ); ?></p>
					<p><?php printf( '<a href="%s">%s</a>', esc_url( add_query_arg( array( 'edu-action' => 'dismiss-notice', 'edr-notice' => 'uploads_other' ) ) ), __( 'Dismiss Notice', 'ibeducator' ) ) ?></p>
				</div>
				<?php
				echo ob_get_clean();
			}
		}
	}

	/**
	 * Dismiss notice action processor.
	 */
	public function dismiss_notice() {
		if ( isset( $_GET['edr-notice'] ) ) {
			$notice = sanitize_key( $_GET['edr-notice'] );

			update_user_meta( get_current_user_id(), "_edr_{$notice}_dismissed", 1 );
			wp_redirect( remove_query_arg( array( 'edu-action', 'edr-notice' ) ) );
			exit();
		}
	}
}
