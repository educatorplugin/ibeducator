<?php

class IB_Educator_Form {
	/**
	 * @var array
	 */
	protected $values = array();

	/**
	 * @var array
	 */
	protected $groups = array();

	/**
	 * @var array
	 */
	protected $fields = array();

	/**
	 * @var array
	 */
	protected $grouped = array();

	/**
	 * @var array
	 */
	protected $field_classes = array();

	/**
	 * @var array
	 */
	protected $decorators = array(
		'label_before'   => '',
		'label_after'    => '',
		'control_before' => '',
		'control_after'  => '',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Set default decorators.
	 */
	public function default_decorators() {
		if ( is_admin() ) {
			$this->add_field_class( 'ib-edu-field' );
			$this->set_decorator( 'label_before', '<div class="ib-edu-label">' );
			$this->set_decorator( 'label_after', '</div>' );
			$this->set_decorator( 'control_before', '<div class="ib-edu-control">' );
			$this->set_decorator( 'control_after', '</div>' );
		} else {
			$this->add_field_class( 'ib-edu-form-field' );
			$this->set_decorator( 'control_before', '<div class="ib-edu-form-control">' );
			$this->set_decorator( 'control_after', '</div>' );
		}
	}

	/**
	 * Add CSS class for a field container.
	 *
	 * @param string $class
	 */
	public function add_field_class( $class ) {
		$this->field_classes[] = $class;
	}

	/**
	 * Set a form decorator.
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function set_decorator( $key, $value ) {
		$this->decorators[ $key ] = $value;
	}

	/**
	 * Set value.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set_value( $key, $value ) {
		$this->values[ $key ] = $value;
	}

	/**
	 * Get value.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get_value( $key ) {
		return isset( $this->values[ $key ] ) ? $this->values[ $key ] : null;
	}

	/**
	 * Set values.
	 *
	 * @param array $values
	 */
	public function set_values( $values ) {
		$this->values = $values;
	}

	/**
	 * Add group.
	 *
	 * @param array $data
	 */
	public function add_group( $data ) {
		$this->groups[] = array(
			'name'  => $data['name'],
			'label' => $data['label'],
		);
	}

	/**
	 * Add field.
	 *
	 * @param array $field.
	 * @param string $group
	 */
	public function add( $field, $group = null ) {
		$this->fields[ $field['name'] ] = $field;

		if ( $group ) {
			if ( ! isset( $this->grouped[ $group ] ) ) {
				$this->grouped[ $group ] = array();
			}

			$this->grouped[ $group ][] = $field['name'];
		}
	}

	/**
	 * Display form.
	 */
	public function display() {
		if ( ! empty( $this->groups ) ) {
			foreach ( $this->groups as $group ) {
				if ( ! isset( $this->grouped[ $group['name'] ] ) ) {
					continue;
				}

				echo '<fieldset>';

				if ( isset( $group['label'] ) ) {
					echo '<legend>' . $group['label'] . '</legend>';
				}

				foreach ( $this->grouped[ $group['name'] ] as $fname ) {
					if ( ! isset( $this->fields[ $fname ] ) ) {
						continue;
					}

					echo $this->get_field( $this->fields[ $fname ] );
				}

				echo '</fieldset>';
			}
		} else {
			foreach ( $this->fields as $field ) {
				echo $this->get_field( $field );
			}
		}
	}

	/**
	 * Get form field.
	 *
	 * @param string $value
	 * @param array $data
	 * @return string
	 */
	public function get_field( $data ) {
		$method_name = 'field_' . $data['type'];
		$output = '';

		if ( method_exists( $this, $method_name ) ) {
			$container_id = isset( $data['container_id'] ) ? ' id="' . esc_attr( $data['container_id'] ) . '"' : '';

			$output .= '<div' . $container_id . ' class="' . esc_attr( implode( ' ', $this->field_classes ) ) . '">';
			$output .= $this->$method_name( $data );
			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Get field description HTML.
	 *
	 * @param string $description
	 * @return string
	 */
	public function description_html( $description ) {
		return '<div class="description">' . $description . '</div>';
	}

	/**
	 * Get label HTML.
	 *
	 * @param string $label
	 * @param string $for
	 * @return string
	 */
	public function label_html( $label, $for = '', $required = false ) {
		$output = $this->decorators['label_before'] . '<label';

		if ( ! empty( $for ) ) {
			$output .= ' for="' . esc_attr( $for ) . '"';
		}

		$output .= '>' . $label;

		if ( $required ) {
			$output .= ' <span class="required">*</span>';
		}

		$output .= '</label>' . $this->decorators['label_after'];

		return $output;
	}

	/**
	 * Get text field.
	 *
	 * @param array $data
	 * @return string
	 */
	public function field_text( $data ) {
		$data = wp_parse_args( $data, array(
			'label'       => '',
			'class'       => 'regular-text',
			'description' => '',
			'id'          => '',
			'required'    => false,
			'disabled'    => false,
		) );

		// Label.
		$output = $this->label_html( $data['label'], $data['id'], $data['required'] );

		// Open field container.
		$output .= $this->decorators['control_before'];

		// Get value.
		$value = isset( $this->values[ $data['name'] ] ) ? $this->values[ $data['name'] ] : '';

		// Input.
		if ( isset( $data['before'] ) ) {
			$output .= $data['before'];
		}

		$output .= '<input type="text"';

		if ( ! empty( $data['id'] ) ) {
			$output .= ' id="' . esc_attr( $data['id'] ) . '"';
		}

		if ( ! empty( $data['class'] ) ) {
			$output .= ' class="' . esc_attr( $data['class'] ) . '"';
		}

		if ( $data['disabled'] ) {
			$output .= ' disabled="disabled"';
		}

		$output .= ' name="' . esc_attr( $data['name'] ) . '" value="' . esc_attr( $value ) . '">';

		if ( isset( $data['after'] ) ) {
			$output .= $data['after'];
		}

		// Description.
		if ( ! empty( $data['description'] ) ) {
			$output .= $this->description_html( $data['description'] );
		}

		// Close field container.
		$output .= $this->decorators['control_after'];

		return $output;
	}

	/**
	 * Get select field.
	 *
	 * @param array $data
	 * @return string
	 */
	public function field_select( $data ) {
		$data = wp_parse_args( $data, array(
			'label'       => '',
			'class'       => '',
			'description' => '',
			'id'          => '',
			'options'     => null,
			'required'    => false,
			'default'     => '',
			'multiple'    => false,
			'size'        => null,
		) );

		// Label.
		$output = $this->label_html( $data['label'], $data['id'], $data['required'] );

		// Open field container.
		$output .= $this->decorators['control_before'];

		// Get value.
		if ( isset( $this->values[ $data['name'] ] ) ) {
			$value = $this->values[ $data['name'] ];
		} else {
			$value = $data['default'];
		}

		// Select.
		$output .= '<select';

		if ( $data['multiple'] ) {
			$output .= ' multiple="multiple"';
		}

		if ( $data['size'] ) {
			$output .= ' size="' . intval( $data['size'] ) . '"';
		}

		if ( ! empty( $data['id'] ) ) {
			$output .= ' id="' . esc_attr( $data['id'] ) . '"';
		}

		if ( ! empty( $data['class'] ) ) {
			$output .= ' class="' . esc_attr( $data['class'] ) . '"';
		}

		if ( $data['multiple'] ) {
			$output .= ' name="' . esc_attr( $data['name'] ) . '[]">';

			if ( ! is_array( $value ) ) {
				$value = array();
			}

			foreach ( $data['options'] as $option_value => $option_label ) {
				$selected = in_array( $option_value, $value ) ? ' selected="selected"' : '';
				$output .= '<option value="' . esc_attr( $option_value ) . '"' . $selected. '>' . esc_html( $option_label ) . '</option>';
			}
		} else {
			$output .= ' name="' . esc_attr( $data['name'] ) . '">';
			
			foreach ( $data['options'] as $option_value => $option_label ) {
				$output .= '<option value="' . esc_attr( $option_value ) . '"' . selected( $value, $option_value, false ) . '>' . esc_html( $option_label ) . '</option>';
			}
		}

		$output .= '</select>';

		// Description.
		if ( ! empty( $data['description'] ) ) {
			$output .= $this->description_html( $data['description'] );
		}

		// Close field container.
		$output .= $this->decorators['control_after'];

		return $output;
	}

	/**
	 * Get checkbox field.
	 *
	 * @param array $data
	 * @return string
	 */
	public function field_checkbox( $data ) {
		$data = wp_parse_args( $data, array(
			'label'       => '',
			'class'       => '',
			'description' => '',
			'id'          => '',
			'required'    => false,
		) );
		
		// Label.
		$output = $this->label_html( $data['label'], $data['id'], $data['required'] );

		// Open field container.
		$output .= $this->decorators['control_before'];

		// Get value.
		$value = isset( $this->values[ $data['name'] ] ) ? $this->values[ $data['name'] ] : false;

		// Input.
		$output .= '<input type="checkbox"';

		if ( ! empty( $data['id'] ) ) {
			$output .= ' id="' . esc_attr( $data['id'] ) . '"';
		}

		if ( ! empty( $data['class'] ) ) {
			$output .= ' class="' . esc_attr( $data['class'] ) . '"';
		}

		$output .= ' name="' . esc_attr( $data['name'] ) . '" value="1"' . checked( $value, true, false ) . '>';
		
		// Description.
		if ( ! empty( $data['description'] ) ) {
			$output .= $this->description_html( $data['description'] );
		}

		// Close field container.
		$output .= $this->decorators['control_after'];

		return $output;
	}

	/**
	 * Get textarea field.
	 *
	 * @param array $data
	 * @return string
	 */
	public function field_textarea( $data ) {
		$data = wp_parse_args( $data, array(
			'label'       => '',
			'class'       => 'large-text code',
			'description' => '',
			'cols'        => 40,
			'rows'        => 5,
			'id'          => '',
			'rich_text'   => false,
			'required'    => false,
		) );
		
		$output = $this->label_html( $data['label'], $data['id'], $data['required'] );

		// Open field container.
		$output .= $this->decorators['control_before'];

		// Get value.
		$value = isset( $this->values[ $data['name'] ] ) ? $this->values[ $data['name'] ] : '';

		// Textarea.
		if ( false == $data['rich_text'] || ! user_can_richedit() ) {
			$output .= '<textarea';

			if ( ! empty( $data['id'] ) ) {
				$output .= ' id="' . esc_attr( $data['id'] ) . '"';
			}

			if ( ! empty( $data['class'] ) ) {
				$output .= ' class="' . esc_attr( $data['class'] ) . '"';
			}

			if ( ! empty( $data['cols'] ) ) {
				$output .= ' cols="' . absint( $data['cols'] ) . '"';
			}

			if ( ! empty( $data['rows'] ) ) {
				$output .= ' rows="' . absint( $data['rows'] ) . '"';
			}

			$output .= ' name="' . esc_attr( $data['name'] ) . '">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
		} else {
			ob_start();
			wp_editor( stripslashes( $value ), $data['id'], array(
				'media_buttons' => false,
				'tinymce'       => false,
				'quicktags'     => array( 'buttons' => 'strong,em,link,del,ins,img,ul,ol,li,code,close' ),
				'textarea_name' => $data['name'],
				'textarea_rows' => $data['rows'],
			) );
			$output .= ob_get_clean();
		}

		// Description.
		if ( ! empty( $data['description'] ) ) {
			$output .= $this->description_html( $data['description'] );
		}

		// Close field container.
		$output .= $this->decorators['control_after'];

		return $output;
	}
}