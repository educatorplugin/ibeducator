<?php

class Edr_Admin_Settings_Base {
	/**
	 * Output admin settings tabs.
	 *
	 * @param string $current_tab
	 */
	public function settings_tabs( $current_tab ) {
		$tabs = apply_filters( 'ib_educator_settings_tabs', array() );
		?>
		<h2 class="nav-tab-wrapper">
		<?php foreach ( $tabs as $tab_key => $tab_name ) : ?>
		<a class="nav-tab<?php if ( $tab_key == $current_tab ) echo ' nav-tab-active'; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=ib_educator_admin&tab=' . $tab_key ) ); ?>"><?php echo esc_html( $tab_name ); ?></a>
		<?php endforeach; ?>
		</h2>
		<?php
	}

	/**
	 * Dummy section description callback.
	 */
	public function section_description( $args ) {}
	
	/**
	 * Text field.
	 *
	 * @param array $args
	 */
	public function setting_text( $args ) {
		if ( isset( $args['settings_group'] ) ) {
			$settings = get_option( $args['settings_group'], array() );
			$value = ! isset( $settings[ $args['name'] ] ) ? '' : $settings[ $args['name'] ];
			$name = $args['settings_group'] . '[' . $args['name'] . ']';
		} else {
			$value = get_option( $args['name'] );
			$name = $args['name'];
		}

		if ( empty( $value ) && isset( $args['default'] ) ) {
			$value = $args['default'];
		}

		$size = isset( $args['size'] ) ? ' size="' . intval( $args['size'] ) . '"' : '';

		if ( ! isset( $args['class'] ) ) {
			$args['class'] = 'regular-text';
		}

		echo '<input type="text" name="' . esc_attr( $name ) . '" class="' . esc_attr( $args['class'] ) . '"' . $size . ' value="' . esc_attr( $value ) . '">';

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}

	/**
	 * Textarea field.
	 *
	 * @param array $args
	 */
	public function setting_textarea( $args ) {
		if ( isset( $args['settings_group'] ) ) {
			$settings = get_option( $args['settings_group'], array() );
			$value = ! isset( $settings[ $args['name'] ] ) ? '' : $settings[ $args['name'] ];
			$name = $args['settings_group'] . '[' . $args['name'] . ']';
		} else {
			$value = get_option( $args['name'] );
			$name = $args['name'];
		}

		if ( empty( $value ) && isset( $args['default'] ) ) $value = $args['default'];

		echo '<textarea name="' . esc_attr( $name ) . '" class="large-text" rows="5" cols="40">' . esc_textarea( $value ) . '</textarea>';

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}

	/**
	 * Select field.
	 *
	 * @param array $args
	 */
	public function setting_select( $args ) {
		if ( isset( $args['settings_group'] ) ) {
			$settings = get_option( $args['settings_group'], array() );
			
			$value = '';

			if ( ! isset( $settings[ $args['name'] ] ) ) {
				if ( isset( $args['default'] ) ) {
					$value = $args['default'];
				}
			} else {
				$value = $settings[ $args['name'] ];
			}
			
			$name = $args['settings_group'] . '[' . $args['name'] . ']';
		} else {
			$value = get_option( $args['name'], isset( $args['default'] ) ? $args['default'] : '' );
			$name = $args['name'];
		}

		$multiple = ( isset( $args['multiple'] ) && $args['multiple'] );
		$multiple_attr = '';

		if ( $multiple ) {
			$multiple_attr = ' multiple="multiple"';
			$name .= '[]';
			$empty_choice = __( 'None', 'ibeducator' );

			if ( ! is_array( $value ) ) {
				$value = (array) $value;
			}
		} else {
			$empty_choice = __( 'Select', 'ibeducator' );
		}

		echo '<select name="' . esc_attr( $name ) . '"' . $multiple_attr . '>';
		echo '<option value="">&mdash; ' . $empty_choice . ' &mdash;</option>';

		foreach ( $args['choices'] as $choice => $label ) {
			if ( $multiple ) {
				$selected = ( in_array( $choice, $value ) ) ? ' selected="selected"' : '';
			} else {
				$selected = ( $choice == $value ) ? ' selected="selected"' : '';
			}

			echo '<option value="' . esc_attr( $choice ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
		}

		echo '</select>';

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}

	/**
	 * Checkbox field.
	 *
	 * @param array $args
	 */
	public function setting_checkbox( $args ) {
		if ( isset( $args['settings_group'] ) ) {
			$settings = get_option( $args['settings_group'], array() );
			$value = ! isset( $settings[ $args['name'] ] ) ? '' : $settings[ $args['name'] ];
			$name = $args['settings_group'] . '[' . $args['name'] . ']';
		} else {
			$value = get_option( $args['name'] );
			$name = $args['name'];
		}

		if ( empty( $value ) && 0 !== $value && isset( $args['default'] ) ) {
			$value = $args['default'];
		}

		$id_attr = ! empty( $args['id'] ) ? $id_attr = ' id="' . esc_attr( $args['id'] ) . '"' : '';

		echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="0">';
		echo '<input type="checkbox"' . $id_attr . ' name="' . esc_attr( $name ) . '" value="1" ' . checked( 1, $value, false ) . '>';

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . $args['description'] . '</p>';
		}
	}

	public function setting_location( $args ) {
		if ( isset( $args['settings_group'] ) ) {
			$settings = get_option( $args['settings_group'], array() );
			$value = ! isset( $settings[ $args['name'] ] ) ? '' : $settings[ $args['name'] ];
			$name = $args['settings_group'] . '[' . $args['name'] . ']';
		} else {
			$value = get_option( $args['name'] );
			$name = $args['name'];
		}
		$edu_countries = Edr_Countries::get_instance();
		$countries = $edu_countries->get_countries();

		$parts = explode( ';', $value );

		if ( 2 == count( $parts ) ) {
			$country = $parts[0];
			$state = $parts[1];
		} else {
			$country = $value;
			$state = '';
		}
		?>
		<div class="ib-edu-autocomplete">
			<input
				type="text"
				name="<?php echo esc_attr( $name ); ?>"
				id="store-location"
				class="regular-text"
				autocomplete="off" 
				value="<?php echo esc_attr( $value ); ?>"
				data-label="<?php
					if ( isset( $countries[ $country ] ) ) {
						echo esc_attr( $countries[ $country ] );
					}

					$states = ! ( empty( $state ) ) ? $edu_countries->get_states( $country ) : array();

					if ( isset( $states[ $state ] ) ) {
						echo ' - ' . esc_attr( $states[ $state ] );
					}
				?>">
		</div>

		<?php
			if ( isset( $args['description'] ) ) {
				echo '<p class="description">' . $args['description'] . '</p>';
			}
		?>
		<script>
		(function($) {
			'use strict';

			ibEducatorAutocomplete(document.getElementById('store-location'), {
				key: 'code',
				value: 'country',
				searchBy: 'country',
				items: [
					<?php
						$i = 0;

						foreach ( $countries as $code => $country ) {
							if ( $i > 0 ) {echo ',';}

							echo '{"code":' . json_encode( esc_html( $code ) ) . ',"country":' . json_encode( esc_html( $country ) ) . '}';

							$states = $edu_countries->get_states( $code );

							if ( ! empty( $states ) ) {
								foreach ( $states as $scode => $sname ) {
									echo ',{"code":' . json_encode( esc_html( $code . ';' . $scode ) ) . ',"country":' . json_encode( esc_html( $country . ' - ' . $sname ) ) . ', "_lvl":1}';
								}
							}

							++$i;
						}
					?>
				]
			});
		})(jQuery);
		</script>
		<?php
	}
}
