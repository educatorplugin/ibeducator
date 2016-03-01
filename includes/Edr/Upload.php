<?php

class Edr_Upload {
	/**
	 * Generate file path.
	 *
	 * @param string $file_path
	 * @return null|array
	 */
	private function get_file_path( $file_path ) {
		$file_hash = sha1_file( $file_path );
		$new_path = null;

		if ( $file_hash ) {
			$new_path = array(
				'dir'  => $file_hash[0] . $file_hash[1] . '/' . $file_hash[2] . $file_hash[3],
				'name' => $file_hash,
			);
		}

		return $new_path;
	}

	/**
	 * Get file upload's error message, given error code.
	 *
	 * @param int $error_code
	 * @return string
	 */
	function check_upload_error( $error_code ) {
		$message = '';

		switch ( $error_code ) {
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_NO_FILE:
				$message = __( 'No file sent.', 'ibeducator' );
				break;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$message = __( 'Exceeded file size limit.', 'ibeducator' );
				break;
			default:
				$message = __( 'Unknown upload error.', 'ibeducator' );
		}

		return $message;
	}

	/**
	 * Upload a file.
	 *
	 * @param array $file
	 * @return array
	 */
	public function upload_file( $file ) {
		// Check if the file was uploaded through HTTP POST.
		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			return array( 'error' => __( 'A file failed upload test.', 'ibeducator' ) );
		}

		// Check the file type.
		$finfo = new finfo( FILEINFO_MIME_TYPE );
		$allowed_mime_types = get_allowed_mime_types();
		$ext = array_search( $finfo->file( $file['tmp_name'] ), $allowed_mime_types, true );

		if ( false === $ext ) {
			return array( 'error' => __( 'The type of the uploaded file is not supported.', 'ibeducator' ) );
		}

		// Determine the directory where to upload the file.
		$file_dir = edr_get_private_uploads_dir();

		if ( ! $file_dir ) {
			return array( 'error' => __( 'Could not determine the uploads directory.', 'ibeducator' ) );
		}

		$path = $this->get_file_path( $file['tmp_name'] );

		if ( ! $path ) {
			return array( 'error' => __( 'Could not determine the file path.', 'ibeducator' ) );
		}

		if ( ! empty( $file['context_dir'] ) ) {
			$file_dir .= '/' . $file['context_dir'];
		}

		$file_dir .= '/' . $path['dir'];

		if ( ! file_exists( $file_dir ) ) {
			wp_mkdir_p( $file_dir );
		}

		// Prepare the file name.
		$file_name = wp_unique_filename( $file_dir, $path['name'] . '.' . $ext );
		$file_path = $file_dir . '/' . $file_name;

		// Move uploaded file to a new path.
		if ( false === move_uploaded_file( $file['tmp_name'], $file_path ) ) {
			return array( 'error' => __( 'Could not upload the file.', 'ibeducator' ) );
		}

		// Set proper file permissions.
		$stat = stat( dirname( $file_path ) );
		$perms = $stat['mode'] & 0000666;
		chmod( $file_path, $perms );

		$original_name = sanitize_file_name( $file['name'] );

		return array(
			'name'          => $file_name,
			'dir'           => $path['dir'],
			'original_name' => ( $original_name ) ? $original_name : $file_name,
		);
	}

	/**
	 * Get .htaccess rules to protect directory content,
	 * from being accessed directly.
	 *
	 * @return string
	 */
	public function generate_protect_htaccess() {
		$htaccess = "Options -Indexes\n";
		$htaccess .= "deny from all\n";

		return apply_filters( 'edr_protect_htaccess_content', $htaccess );
	}

	/**
	 * Create .htaccess file in the private uploads directory
	 * to prevent its content from being served directly.
	 * This solution works for Apache web server only.
	 */
	public function create_protect_files() {
		$dir = edr_get_private_uploads_dir();

		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$htaccess_content = $this->generate_protect_htaccess();
		$htaccess_path = $dir . '/.htaccess';

		if ( ! file_exists( $htaccess_path ) || file_get_contents( $htaccess_path ) !== $htaccess_content ) {
			file_put_contents( $htaccess_path, $htaccess_content );
		}
	}
}
