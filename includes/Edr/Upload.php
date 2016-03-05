<?php

class Edr_Upload {
	/**
	 * Generate file path.
	 *
	 * @param string $file_path
	 * @return null|array
	 */
	protected function get_file_path( $file_path ) {
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
	public function check_upload_error( $error_code ) {
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
	 * Get allowed mime types.
	 *
	 * @return array
	 */
	protected function get_allowed_mime_types() {
		return apply_filters( 'edr_allowed_mime_types', array(
			'jpg|jpeg' => 'image/jpeg',
			'gif' => 'image/gif',
			'png' => 'image/png',
			'txt' => 'text/plain',
			'csv' => 'text/csv',
			'pdf' => 'application/pdf',
			'tar' => 'application/x-tar',
			'zip' => 'application/zip',
			'gz|gzip' => 'application/x-gzip',
			'rar' => 'application/rar',
			'7z' => 'application/x-7z-compressed',
			'psd' => 'application/octet-stream',
			'doc' => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xls' => 'application/vnd.ms-excel',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'ppt|pps' => 'application/vnd.ms-powerpoint',
			'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
			'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
			'odt' => 'application/vnd.oasis.opendocument.text',
			'odp' => 'application/vnd.oasis.opendocument.presentation',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		) );
	}

	/**
	 * Check mime type of a given file.
	 * Also, check extension.
	 *
	 * @param string $file_path
	 * @return array
	 */
	protected function check_mime_type( $file_path ) {
		$finfo = new finfo( FILEINFO_MIME_TYPE );
		$type = $finfo->file( $file_path );
		$ext_regexp = '';

		if ( $type ) {
			$allowed_mime_types = $this->get_allowed_mime_types();
			$ext_regexp = array_search( $type, $allowed_mime_types, true );

			if ( false === $ext_regexp ) {
				$type = false;
			}
		}

		return compact( 'type', 'ext_regexp' );
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
		$file_info = $this->check_mime_type( $file['tmp_name'] );

		if ( ! $file_info['type'] ) {
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
		$ext = '';
		$original_name = $file['name'];
		$name_parts = explode( '.', $original_name );

		if ( count( $name_parts ) > 1 ) {
			$ext = array_pop( $name_parts );

			if ( ! preg_match( '#^(' . $file_info['ext_regexp'] . ')$#i', $ext ) ) {
				$ext = '';
				$original_name = implode( '.', $name_parts );
			}
		}

		$file_name = ( $ext ) ? $path['name'] . '.' . $ext : $path['name'];
		$file_name = wp_unique_filename( $file_dir, $file_name );
		$file_path = $file_dir . '/' . $file_name;

		// Move uploaded file to a new path.
		if ( false === move_uploaded_file( $file['tmp_name'], $file_path ) ) {
			return array( 'error' => __( 'Could not upload the file.', 'ibeducator' ) );
		}

		// Set proper file permissions.
		$stat = stat( dirname( $file_path ) );
		$perms = $stat['mode'] & 0000666;
		chmod( $file_path, $perms );

		$original_name = sanitize_file_name( $original_name );

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
