<?php

class IB_Educator_Autocomplete {
	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts_styles' ), 9 );
		add_action( 'wp_ajax_ib_educator_autocomplete', array( __CLASS__, 'ajax_autocomplete' ) );
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public static function enqueue_scripts_styles() {
		$screen = get_current_screen();

		if ( $screen && in_array( $screen->id, array( 'toplevel_page_ib_educator_admin', 'educator_page_ib_educator_payments', 'educator_page_ib_educator_entries', 'educator_page_ib_educator_members' ) ) ) {
			wp_enqueue_script( 'ib-educator-autocomplete', IBEDUCATOR_PLUGIN_URL . 'admin/js/autocomplete.js', array( 'jquery' ), '1.0' );
		}
	}

	/**
	 * AJAX: autocomplete.
	 */
	public static function ajax_autocomplete() {
		// Check capability.
		if ( ! current_user_can( 'manage_educator' ) ) {
			exit;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ib_educator_autocomplete' ) ) {
			exit;
		}

		if ( ! isset( $_GET['entity'] ) ) {
			exit;
		}

		$entity = $_GET['entity'];
		$response = array();
		
		switch ( $entity ) {
			case 'user':
			case 'student':
				$user_args = array();
				
				if ( ! empty( $_GET['input'] ) ) {
					$user_args['search'] = '*' . $_GET['input'] . '*';
				}

				$user_args['number'] = 15;

				if ( 'student' == $entity ) {
					$user_args['role'] = 'student';
				}
				
				$user_query = new WP_User_Query( $user_args );

				if ( ! empty( $user_query->results ) ) {
					foreach ( $user_query->results as $user ) {
						$response[] = array(
							'id'   => intval( $user->ID ),
							'name' => esc_html( $user->display_name . ' (' . $user->user_nicename . ')' ),
						);
					}
				}
				break;

			case 'post':
			case 'course':
				$post_args = array();

				if ( ! empty( $_GET['input'] ) ) {
					$post_args['s'] = $_GET['input'];
				}

				if ( 'course' == $entity ) {
					$post_args['post_type'] = 'ib_educator_course';
				}

				$post_args['post_status'] = 'publish';
				$post_args['posts_per_page'] = 15;
				$posts_query = new WP_Query( $post_args );

				if ( $posts_query->have_posts() ) {
					while ( $posts_query->have_posts() ) {
						$posts_query->the_post();

						$response[] = array(
							'id'   => get_the_ID(),
							'title' => get_the_title(),
						);
					}

					wp_reset_postdata();
				}
				break;
		}

		echo json_encode( $response );
		exit;
	}
}