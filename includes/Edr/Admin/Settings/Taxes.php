<?php

class Edr_Admin_Settings_Taxes extends Edr_Admin_Settings_Base {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'ib_educator_settings_tabs', array( $this, 'add_tab' ) );
		add_action( 'ib_educator_settings_page', array( $this, 'settings_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_ib_edu_taxes', array( $this, 'ajax_taxes' ) );
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		add_settings_section(
			'ib_educator_taxes_settings', // id
			__( 'Taxes', 'ibeducator' ),
			array( $this, 'section_description' ),
			'ib_educator_taxes_page' // page
		);

		// Setting: Enable Taxes.
		add_settings_field(
			'ib_educator_taxes_enable',
			__( 'Enable Taxes', 'ibeducator' ),
			array( $this, 'setting_checkbox' ),
			'ib_educator_taxes_page', // page
			'ib_educator_taxes_settings', // section
			array(
				'name'           => 'enable',
				'settings_group' => 'ib_educator_taxes',
				'default'        => 5,
				'id'             => 'ib_educator_taxes_enable',
			)
		);

		// Setting: Prices Entered With Tax.
		add_settings_field(
			'ib_educator_tax_inclusive',
			__( 'Prices Entered With Tax', 'ibeducator' ),
			array( $this, 'setting_select' ),
			'ib_educator_taxes_page', // page
			'ib_educator_taxes_settings', // section
			array(
				'name'           => 'tax_inclusive',
				'settings_group' => 'ib_educator_taxes',
				'default'        => 'y',
				'id'             => 'ib_educator_tax_inclusive',
				'choices'        => array(
					'y' => __( 'Yes', 'ibeducator' ),
					'n' => __( 'No', 'ibeducator' ),
				),
			)
		);

		// Setting: Tax Classes.
		add_settings_field(
			'ib_educator_taxes_classes',
			__( 'Tax Classes', 'ibeducator' ),
			array( $this, 'render_tax_classes' ),
			'ib_educator_taxes_page', // page
			'ib_educator_taxes_settings' // section
		);

		register_setting(
			'ib_educator_taxes_settings', // option group
			'ib_educator_taxes',
			array( $this, 'validate' )
		);
	}

	/**
	 * Validate settings before saving.
	 *
	 * @param array $input
	 * @return array
	 */
	public function validate( $input ) {
		if ( ! is_array( $input ) ) {
			return '';
		}

		$clean = array();

		foreach ( $input as $key => $value ) {
			switch ( $key ) {
				case 'enable':
					$clean[ $key ] = ( 1 != $value ) ? 0 : 1;
					break;

				case 'tax_inclusive':
					$clean[ $key ] = ( 'y' != $value ) ? 'n' : 'y';
					break;
			}
		}

		return $clean;
	}

	/**
	 * Add the tab to the tabs on the settings admin page.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public function add_tab( $tabs ) {
		$tabs['taxes'] = __( 'Taxes', 'ibeducator' );

		return $tabs;
	}

	/**
	 * Output the settings.
	 *
	 * @param string $tab
	 */
	public function settings_page( $tab ) {
		if ( 'taxes' == $tab ) {
			include IBEDUCATOR_PLUGIN_DIR . 'admin/templates/settings-taxes.php';
		}
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		if ( $screen && 'toplevel_page_ib_educator_admin' == $screen->id && isset( $_GET['tab'] ) && 'taxes' == $_GET['tab'] ) {
			wp_enqueue_script( 'edr-admin-tax-rates', IBEDUCATOR_PLUGIN_URL . 'admin/js/tax-rates.js', array( 'backbone', 'jquery-ui-sortable' ), '1.0.0', true );
		}
	}

	/**
	 * Process AJAX actions of the tax rates app.
	 */
	public function ajax_taxes() {
		if ( ! isset( $_GET['method'] ) ) {
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'manage_educator' ) ) {
			return;
		}

		switch ( $_GET['method'] ) {
			case 'add-tax-class':
			case 'edit-tax-class':
				$input = json_decode( file_get_contents( 'php://input' ), true );

				if ( $input ) {
					if ( empty( $input['_wpnonce'] ) || ! wp_verify_nonce( $input['_wpnonce'], 'ib_educator_tax_rates' ) ) {
						return;
					}

					$edu_tax = Edr_TaxManager::get_instance();

					// Get and sanitize input.
					$data = $edu_tax->sanitize_tax_class( $input );

					if ( is_wp_error( $data ) ) {
						http_response_code( 400 );
						return;
					}

					// Save the tax class.
					$edu_tax->add_tax_class( $data );

					echo json_encode( $data );
				}
				break;

			case 'delete-tax-class':
				if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ib_educator_tax_rates' ) ) {
					http_response_code( 400 );
					return;
				}

				if ( ! isset( $_GET['name'] ) || 'default' == $_GET['name'] ) {
					http_response_code( 400 );
					return;
				}

				Edr_TaxManager::get_instance()->delete_tax_class( $_GET['name'] );
				break;

			case 'rates':
				switch ( $_SERVER['REQUEST_METHOD'] ) {
					case 'POST':
					case 'PUT':
						$input = json_decode( file_get_contents( 'php://input' ), true );

						if ( $input ) {
							if ( empty( $input['_wpnonce'] ) || ! wp_verify_nonce( $input['_wpnonce'], 'ib_educator_tax_rates' ) ) {
								return;
							}

							$edu_tax = Edr_TaxManager::get_instance();
							$rate = $edu_tax->sanitize_tax_rate( $input );
							$rate['ID'] = $edu_tax->update_tax_rate( $rate );

							echo json_encode( $rate );
						}
						break;

					case 'GET':
						if ( empty( $_GET['class_name'] ) ) {
							return;
						}

						$class_name = preg_replace( '/[^a-zA-Z0-9-_]+/', '', $_GET['class_name'] );
						$edu_tax = Edr_TaxManager::get_instance();
						$rates = $edu_tax->get_tax_rates( $class_name );
						$edu_countries = Edr_Countries::get_instance();
						$countries = $edu_countries->get_countries();

						if ( ! empty( $rates ) ) {
							foreach ( $rates as $key => $rate ) {
								$rate = $edu_tax->sanitize_tax_rate( $rate );

								// Get country name.
								if ( $rate['country'] ) {
									if ( isset( $countries[ $rate['country'] ] ) ) {
										$rate['country_name'] = esc_html( $countries[ $rate['country'] ] );
									}
								}

								// Get state name.
								if ( $rate['state'] ) {
									$states = $edu_countries->get_states( $rate['country'] );

									if ( isset( $states[ $rate['state'] ] ) ) {
										$rate['state_name'] = esc_html( $states[ $rate['state'] ] );
									} else {
										$rate['state_name'] = $rate['state'];
									}
								}

								$rates[ $key ] = $rate;
							}

							header( 'Content-Type: application/json' );
							echo json_encode( $rates );
						}
						break;

					case 'DELETE':
						if ( empty( $_GET['ID'] ) || empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'ib_educator_tax_rates' ) ) {
							return;
						}

						Edr_TaxManager::get_instance()->delete_tax_rate( $_GET['ID'] );
						break;
				}
				break;

			case 'save-rates-order':
				if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'ib_educator_tax_rates' ) ) {
					return;
				}

				if ( ! isset( $_POST['order'] ) || ! is_array( $_POST['order'] ) ) {
					return;
				}

				global $wpdb;
				$tables = ib_edu_table_names();

				foreach ( $_POST['order'] as $id => $order ) {
					if ( ! is_numeric( $id ) || ! is_numeric( $order ) ) {
						continue;
					}

					$wpdb->update( $tables['tax_rates'], array( 'rate_order' => $order ), array( 'id' => $id ), array( '%d' ), array( '%d' ) );
				}
				break;
		}

		exit();
	}

	/**
	 * Render tax rates app.
	 */
	public function render_tax_classes() {
		$countries = Edr_Countries::get_instance()->get_countries();
		?>
		<div id="edu-tax-classes-container"></div>

		<!-- TEMPLATE: TaxClassView -->
		<script id="edu-tax-class" type="text/html">
		<td><%= description %></td>
		<td>
			<button class="button edit-tax-class"><?php _e( 'Edit', 'ibeducator' ); ?></button>
			<button class="button edit-rates"><?php _e( 'Rates', 'ibeducator' ); ?></button>
			<button class="button delete-tax-class"><?php _e( 'Delete', 'ibeducator' ); ?></button>
		</td>
		</script>

		<!-- TEMPLATE: TaxClassesView -->
		<script id="edu-tax-classes" type="text/html">
		<table class="edu-tax-classes-table edu-table">
			<thead>
				<tr>
					<th><?php _e( 'Tax Class', 'ibeducator' ); ?></th>
					<th><?php _e( 'Options', 'ibeducator' ); ?></th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
		<p class="actions">
			<button class="button add-new-class"><?php _e( 'Add New', 'ibeducator' ); ?></button>
		</p>
		</script>

		<!-- TEMPLATE: EditTaxClassView -->
		<script id="edu-edit-tax-class" type="text/html">
		<h4 class="title-add-new"><?php _e( 'Add New Tax Rate', 'ibeducator' ); ?></h4>
		<h4 class="title-edit"><?php _e( 'Edit Tax Rate', 'ibeducator' ); ?></h4>
		<p>
			<label><?php _e( 'Short Name', 'ibeducator' ); ?></label>
			<input type="text" class="short-name" value="<%= name %>">
		</p>
		<p>
			<label><?php _e( 'Description', 'ibeducator' ); ?></label>
			<input type="text" class="description" value="<%= description %>">
		</p>
		<p>
			<button class="button button-primary save-tax-class"><?php _e( 'Save', 'ibeducator' ); ?></button>
			<button class="button cancel"><?php _e( 'Cancel', 'ibeducator' ); ?></button>
		</p>
		</script>

		<!-- TEMPLATE: view tax rate -->
		<script id="edu-tax-rate" type="text/html">
		<td class="handle"><div class="ib-edu-sort-y dashicons dashicons-sort"></div></td>
		<td class="country"><%= country_name %></td>
		<td class="state"><%= state_name %></td>
		<td class="name"><%= name %></td>
		<td class="rate"><%= rate %></td>
		<td class="priority"><%= priority %></td>
		<td class="options">
			<a class="edit-rate ib-edu-action" href="#"><?php _e( 'Edit', 'ibeducator' ); ?></a> <span>|</span>
			<a class="delete-rate ib-edu-action" href="#"><?php _e( 'Delete', 'ibeducator' ); ?></a>
		</td>
		</script>

		<!-- TEMPLATE: edit tax rate -->
		<script id="edu-tax-rate-edit" type="text/html">
		<td class="handle"><div class="ib-edu-sort-y dashicons dashicons-sort"></div></td>
		<td class="country">
			<select class="country">
				<option value=""></option>
				<?php
					foreach ( $countries as $code => $country ) {
						echo '<option value="' . esc_attr( $code ) . '">' . esc_html( $country ) . '</option>';
					}
				?>
			</select>
		</td>
		<td class="state"></td>
		<td class="name"><input type="text" value="<%= name %>"></td>
		<td class="rate"><input type="number" value="<%= rate %>"></td>
		<td class="priority"><input type="number" value="<%= priority %>"></td>
		<td class="options">
			<a class="save-rate ib-edu-action" href="#"><?php _e( 'Save', 'ibeducator' ); ?></a> <span>|</span>
			<a class="delete-rate ib-edu-action" href="#"><?php _e( 'Delete', 'ibeducator' ); ?></a>
		</td>
		</script>

		<!-- TEMPLATE: TaxRatesView -->
		<script id="edu-tax-rates" type="text/html">
		<table class="edu-tax-rates-table edu-table">
			<thead>
				<tr>
					<th></th>
					<th><?php _e( 'Country', 'ibeducator' ); ?></th>
					<th><?php _e( 'State', 'ibeducator' ); ?></th>
					<th><?php _e( 'Name', 'ibeducator' ); ?></th>
					<th><?php _e( 'Rate (%)', 'ibeducator' ); ?></th>
					<th><?php _e( 'Priority', 'ibeducator' ); ?></th>
					<th><?php _e( 'Options', 'ibeducator' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr class="loading">
					<td colspan="7">
						<?php _e( 'Loading', 'ibeducator' ); ?>
					</td>
				</tr>
			</tbody>
			<tfoot>
				<tr>
					<td colspan="7">
						<button class="button button-primary add-new-rate"><?php _e( 'Add New', 'ibeducator' ); ?></button>
						<button class="button save-order" disabled="disabled"><?php _e( 'Save Order', 'ibeducator' ); ?></button>
						<button class="button cancel"><?php _e( 'Close', 'ibeducator' ); ?></button>
					</td>
				</tr>
			</tfoot>
		</table>
		</script>

		<script>
		var eduTaxAppNonce = <?php echo json_encode( wp_create_nonce( 'ib_educator_tax_rates' ) ); ?>;
		var eduGetStatesNonce = <?php echo json_encode( wp_create_nonce( 'ib_edu_get_states' ) ); ?>;
		var eduTaxClasses = <?php
			$json = '[';
			$classes = Edr_TaxManager::get_instance()->get_tax_classes();
			$i = 0;

			foreach ( $classes as $name => $description ) {
				if ( $i > 0 ) {
					$json .= ',';
				}

				$json .= '{name:' . json_encode( esc_html( $name ) ) . ',description:' . json_encode( esc_html( $description ) ) . '}';
				++$i;
			}

			$json .= ']';

			echo $json;
		?>;
		var eduTaxAppErrors = {
			name: <?php echo json_encode( __( 'The name is invalid.', 'ibeducator' ) ); ?>,
			nameNotUnique: <?php echo json_encode( __( 'Tax class with this name exists.', 'ibeducator' ) ); ?>,
			description: <?php echo json_encode( __( 'Description cannot be empty.', 'ibeducator' ) ); ?>,
			ratesNotSaved: <?php echo json_encode( __( 'Rates could not be saved.', 'ibeducator' ) ); ?>
		};
		</script>
		<?php
	}
}
