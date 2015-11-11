<?php

class IB_Educator_Main {
	/**
	 * @var array
	 */
	protected static $gateways = array();

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
		add_action( 'init', array( __CLASS__, 'init_gateways' ) );
		add_action( 'init', array( __CLASS__, 'add_rewrite_endpoints' ), 8 ); // Run before the plugin update.
		add_action( 'template_redirect', array( __CLASS__, 'process_actions' ) );
		add_filter( 'template_include', array( __CLASS__, 'override_templates' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts_styles' ) );
		add_filter( 'wp_nav_menu_objects', array( __CLASS__, 'add_menu_classes' ) );

		// Add template functions.
		add_action( 'after_setup_theme', array( __CLASS__, 'require_template_functions' ) );

		// Include scripts for the front-end only.
		if ( ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) ) {
			require_once IBEDUCATOR_PLUGIN_DIR . 'includes/template-hooks.php';
		}

		// Update splitted shared terms.
		add_action( 'split_shared_term', array( __CLASS__, 'split_shared_term' ), 10, 4 );
	}

	/**
	 * Get the payment gateways objects.
	 *
	 * @return array
	 */
	public static function get_gateways() {
		return self::$gateways;
	}

	/**
	 * Plugin activation hook.
	 */
	public static function plugin_activation() {
		$install = new Edr_Install();
		$install->activate();
	}

	/**
	 * Plugin deactivation hook.
	 */
	public static function plugin_deactivation() {
		$install = new Edr_Install();
		$install->deactivate();
	}

	/**
	 * Load plugin textdomain.
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'ibeducator', false, 'ibeducator/languages' );
	}

	/**
	 * Initialize payment gateways.
	 */
	public static function init_gateways() {
		$gateways = apply_filters( 'ib_educator_payment_gateways', array(
			'paypal'        => array( 'class' => 'Edr_Gateway_Paypal' ),
			'cash'          => array( 'class' => 'Edr_Gateway_Cash' ),
			'check'         => array( 'class' => 'Edr_Gateway_Check' ),
			'bank-transfer' => array( 'class' => 'Edr_Gateway_BankTransfer' ),
			'free'          => array( 'class' => 'Edr_Gateway_Free' ),
			'stripe'        => array( 'class' => 'Edr_Gateway_Stripe' ),
		) );

		// Get the list of enabled gateways.
		$enabled_gateways = null;

		if ( ! is_admin() || ! current_user_can( 'manage_educator' ) ) {
			$gateways_options = get_option( 'ibedu_payment_gateways', array() );
			$enabled_gateways = array( 'free' );

			foreach ( $gateways_options as $gateway_id => $options ) {
				if ( isset( $options['enabled'] ) && 1 == $options['enabled'] ) {
					$enabled_gateways[] = $gateway_id;
				}
			}

			$enabled_gateways = apply_filters( 'ib_educator_enabled_gateways', $enabled_gateways );
		}

		foreach ( $gateways as $gateway_id => $gateway ) {
			if ( null !== $enabled_gateways && ! in_array( $gateway_id, $enabled_gateways ) ) {
				continue;
			}

			if ( isset( $gateway['file'] ) && is_readable( $gateway['file'] ) ) {
				require_once $gateway['file'];
			}

			$gateway_instance = new $gateway['class']();

			self::$gateways[ $gateway_instance->get_id() ] = $gateway_instance;
		}
	}

	/**
	 * Add rewrite endpoints.
	 */
	public static function add_rewrite_endpoints() {
		add_rewrite_endpoint( 'edu-pay', EP_PAGES );
		add_rewrite_endpoint( 'edu-course', EP_PAGES );
		add_rewrite_endpoint( 'edu-thankyou', EP_PAGES );
		add_rewrite_endpoint( 'edu-action', EP_PAGES | EP_PERMALINK );
		add_rewrite_endpoint( 'edu-message', EP_PAGES | EP_PERMALINK );
		add_rewrite_endpoint( 'edu-request', EP_ROOT );
		add_rewrite_endpoint( 'edu-membership', EP_PAGES );
	}

	/**
	 * Process actions.
	 */
	public static function process_actions() {
		if ( ! isset( $GLOBALS['wp_query']->post )
			|| ! isset( $GLOBALS['wp_query']->post->ID )
			|| ! isset( $GLOBALS['wp_query']->query_vars['edu-action'] ) ) {
			return;
		}

		$post_id = $GLOBALS['wp_query']->post->ID;
		$action = $GLOBALS['wp_query']->query_vars['edu-action'];

		switch ( $action ) {
			case 'cancel-payment':
				Edr_FrontActions::cancel_payment();
				break;

			case 'submit-quiz':
				Edr_FrontActions::submit_quiz();
				break;

			case 'payment':
				Edr_FrontActions::payment();
				break;

			case 'join':
				Edr_FrontActions::join();
				break;

			case 'resume-entry':
				Edr_FrontActions::resume_entry();
				break;

			case 'pause-membership':
				Edr_FrontActions::pause_membership();
				break;

			case 'resume-membership':
				Edr_FrontActions::resume_membership();
				break;
		}
	}

	/**
	 * Override templates.
	 *
	 * @param string $template
	 * @return string
	 */
	public static function override_templates( $template ) {
		if ( is_post_type_archive( 'ib_educator_course' ) ) {
			if ( false === strpos( $template, 'archive-ib_educator_course.php' ) ) {
				return IBEDUCATOR_PLUGIN_DIR . 'templates/archive-ib_educator_course.php';
			}
		} elseif ( is_singular( 'ib_educator_course' ) ) {
			if ( false === strpos( $template, 'single-ib_educator_course.php' ) ) {
				return IBEDUCATOR_PLUGIN_DIR . 'templates/single-ib_educator_course.php';
			}
		} elseif ( is_singular( 'ib_educator_lesson' ) ) {
			if ( false === strpos( $template, 'single-ib_educator_lesson.php' ) ) {
				return IBEDUCATOR_PLUGIN_DIR . 'templates/single-ib_educator_lesson.php';
			}
		} elseif ( is_post_type_archive( 'ib_educator_lesson' ) ) {
			if ( false === strpos( $template, 'archive-ib_educator_lesson.php' ) ) {
				return IBEDUCATOR_PLUGIN_DIR . 'templates/archive-ib_educator_lesson.php';
			}
		}

		return $template;
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public static function enqueue_scripts_styles() {
		if ( apply_filters( 'ib_educator_stylesheet', true ) ) {
			wp_enqueue_style( 'ib-educator-base', IBEDUCATOR_PLUGIN_URL . 'css/base.css' );

			switch ( get_template() ) {
				case 'twentyfourteen':
					wp_enqueue_style( 'ib-educator-twentyfourteen', IBEDUCATOR_PLUGIN_URL . 'css/twentyfourteen.css' );
					break;

				case 'twentyfifteen':
					wp_enqueue_style( 'ib-educator-twentyfifteen', IBEDUCATOR_PLUGIN_URL . 'css/twentyfifteen.css' );
					break;
			}
		}

		if ( ib_edu_is_payment() ) {
			// Scripts for the payment page.
			wp_enqueue_script( 'ib-educator-payment', IBEDUCATOR_PLUGIN_URL . 'js/payment.js', array( 'jquery' ), '1.0.0', true );
			wp_localize_script( 'ib-educator-payment', 'eduPaymentVars', array(
				'ajaxurl'          => admin_url( 'admin-ajax.php' ),
				'nonce'            => wp_create_nonce( 'ib_educator_ajax' ),
				'get_states_nonce' => wp_create_nonce( 'ib_edu_get_states' )
			) );
		}
	}

	/**
	 * Add classes to menu items.
	 *
	 * @param array $items
	 * @return array
	 */
	public static function add_menu_classes( $items ) {
		$courses_url = get_post_type_archive_link( 'ib_educator_course' );

		foreach ( $items as $key => $item ) {
			if ( $item->url == $courses_url ) {
				if ( is_singular( 'ib_educator_course' )
					|| is_post_type_archive( 'ib_educator_course' )
					|| is_tax( 'ib_educator_category' ) ) {
					$items[ $key ]->classes[] = 'current-menu-item';
				}

				break;
			}
		}

		return $items;
	}

	/**
	 * Update term_id when a shared term is split.
	 */
	public static function split_shared_term( $old_term_id, $new_term_id, $term_taxonomy_id, $taxonomy ) {
		if ( 'ib_educator_category' == $taxonomy ) {
			$memberships = get_posts( array(
				'post_type'      => 'ib_edu_membership',
				'posts_per_page' => -1,
			) );

			if ( ! empty( $memberships ) ) {
				foreach ( $memberships as $post ) {
					$meta = get_post_meta( $post->ID, '_ib_educator_membership', true );
					
					if ( is_array( $meta ) && isset( $meta['categories'] ) && is_array( $meta['categories'] ) ) {
						$update = false;

						foreach ( $meta['categories'] as $key => $term_id ) {
							if ( $term_id == $old_term_id ) {
								$meta['categories'][ $key ] = $new_term_id;
								$update = true;
							}
						}

						if ( $update ) {
							update_post_meta( $post->ID, '_ib_educator_membership', $meta );
						}
					}
				}
			}
		}
	}

	/**
	 * Require the template functions,
	 * so they are included only when needed.
	 */
	public static function require_template_functions() {
		require_once IBEDUCATOR_PLUGIN_DIR . 'includes/template-functions.php';
	}
}
