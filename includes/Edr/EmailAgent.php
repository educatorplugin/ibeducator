<?php

class Edr_EmailAgent {
	/**
	 * @var string
	 */
	protected $template = '';

	/**
	 * @var array
	 */
	protected $to = array();

	/**
	 * @var string
	 */
	protected $subject = '';

	/**
	 * @var string
	 */
	protected $body = '';

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Filter headers.
	 * Replaces new lines.
	 *
	 * @param string $value
	 * @return string
	 */
	public function filter( $value ) {
		return str_ireplace( array( "\r", "\n", '%0A', '%0D', '<CR>', '<LF>' ), '', $value );
	}

	/**
	 * Set email template.
	 *
	 * @param string $template_name
	 */
	public function set_template( $template_name ) {
		$template_settings = get_option( 'ib_educator_' . $template_name );
		$this->template = isset( $template_settings['template'] ) ? $template_settings['template'] : '';
		$this->subject = isset( $template_settings['subject'] ) ? $template_settings['subject'] : '';
	}

	/**
	 * Set email subject.
	 *
	 * @param string $subject
	 */
	public function set_subject( $subject ) {
		$this->subject = $this->filter( $subject );
	}

	/**
	 * Parse subject placeholders.
	 *
	 * @param array $vars
	 */
	public function parse_subject( $vars ) {
		if ( empty( $vars ) ) {
			return;
		}
		
		foreach ( $vars as $key => $value ) {
			$this->subject = str_replace( '{' . $key . '}', $value, $this->subject );
		}
	}

	/**
	 * Add email recipient.
	 *
	 * @param string $email
	 */
	public function add_recipient( $email ) {
		$this->to[] = $email;
	}

	/**
	 * Parse template placeholders.
	 *
	 * @param array $vars
	 */
	public function parse_template( $vars ) {
		if ( ! $this->template ) {
			return;
		}

		$keys = array();
		$values = array();

		foreach ( $vars as $key => $value ) {
			$keys[] = '{' . sanitize_key( $key ) . '}';
			$values[] = esc_html( $value );
		}

		$this->body = str_replace( $keys, $values, $this->template );
	}

	/**
	 * Send email.
	 *
	 * @return boolean
	 */
	public function send() {
		$email_settings = get_option( 'ib_educator_email' );
		$headers = '';

		if ( is_array( $email_settings ) && ! empty( $email_settings['from_email'] ) ) {
			$headers .= 'From:';

			if ( ! empty( $email_settings['from_name'] ) ) {
				$headers .= ' ' . $this->filter( $email_settings['from_name'] );
			}

			$headers .= ' <' . sanitize_email( $email_settings['from_email'] ) . ">\r\n";
		}
		
		return wp_mail( $this->to, $this->filter( $this->subject ), $this->body, $headers );
	}
}
