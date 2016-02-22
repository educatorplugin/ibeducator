<?php

class Edr_AdminNotices {
	public function __construct() {
		add_action( 'admin_notices', array( __CLASS__, 'quiz_uploads_notice' ) );
	}

	public static function quiz_uploads_notice() {
		$user_can_manage = current_user_can( 'manage_educator' );

		if ( stristr( $_SERVER['SERVER_SOFTWARE'], 'apache' ) ) {
			// Apache.
			if ( $user_can_manage && ! edr_protect_htaccess_exists() ) {
				$private_uploads_dir = edr_get_private_uploads_dir();
				$upload = new Edr_Upload();

				ob_start();
				?>
				<div class="error">
					<p><?php printf( __( 'Private uploads are not currently protected in %s, because the Educator .htaccess file is missing.', 'ibeducator' ), $private_uploads_dir ); ?></p>
					<p><?php printf( __( 'Please create the .htaccess file in %s directory, and paste the following code into it:', 'ibeducator' ), $private_uploads_dir ); ?></p>
					<p><pre><?php echo $upload->generate_protect_htaccess(); ?></pre></p>
				</div>
				<?php
				echo ob_get_clean();
			}
		} elseif ( stristr( $_SERVER['SERVER_SOFTWARE'], 'nginx' ) ) {
			// nginx.
			if ( $user_can_manage ) {
				$private_uploads_dir = edr_get_private_uploads_dir();

				ob_start();
				?>
				<div class="error">
					<p><?php printf( __( 'Private uploads are not currently protected in %s. You must add a redirect rule to protect them.', 'ibeducator' ), $private_uploads_dir ); ?></p>
					<p><?php printf( __( 'Please read the <a href="http://educatorplugin.com/protect-private-uploads" target="_blank">Protect Private Uploads</a> article.', 'ibeducator' ) ); ?></p>
				</div>
				<?php
				echo ob_get_clean();
			}
		} else {
			// Other web servers.
			if ( $user_can_manage ) {
				$private_uploads_dir = edr_get_private_uploads_dir();

				ob_start();
				?>
				<div class="error">
					<p><?php printf( __( 'Private uploads may not be currently protected in %s.', 'ibeducator' ), $private_uploads_dir ); ?></p>
					<p><?php printf( __( 'Please read the <a href="http://educatorplugin.com/protect-private-uploads" target="_blank">Protect Private Uploads</a> article.', 'ibeducator' ) ); ?></p>
				</div>
				<?php
				echo ob_get_clean();
			}
		}
	}
}
