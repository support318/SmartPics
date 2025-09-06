<?php

namespace Uncanny_Automator_Pro;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
//use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\EpsWriter;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\WriterInterface;
use Uncanny_Automator\Recipe;
use Uncanny_Automator\Recipe\Log_Properties;

/**
 * Class UOA_GENERATE_QR_CODE
 *
 * Generates a QR code based on user-provided data.
 */
class UOA_GENERATE_QR_CODE {

	use Recipe\Actions;
	use Recipe\Action_Tokens;
	use Log_Properties;

	/**
	 * Constructor for initializing the action.
	 */
	public function __construct() {
		$this->setup_action();
	}

	/**
	 * Sets up the action's details such as integration code, action code, and meta.
	 */
	protected function setup_action() {
		$this->set_integration( 'AUTOMATOR_QR_CODE' );
		$this->set_is_pro( true );
		$this->set_requires_user( false );
		$this->set_action_meta( 'GENERATE_QR_CODE_META' );
		$this->set_action_code( 'GENERATE_QR_CODE' );
		$this->set_sentence( sprintf( esc_html_x( 'Generate a {{QR code:%1$s}}', 'Uncanny Automator', 'uncanny-automator-pro' ), 'QR_DATA:' . $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_html_x( 'Generate a {{QR code}}', 'Uncanny Automator', 'uncanny-automator-pro' ) );
		$this->set_options_callback( array( $this, 'load_options' ) );
		$this->set_action_tokens(
			array(
				'QR_CODE_URL' => array(
					'name' => _x( 'Generated QR code - URL', 'Uncanny Automator', 'uncanny-automator-pro' ),
					'type' => 'url',
				),
				'QR_CODE_IMG' => array(
					'name' => _x( 'Generated QR code - Image tag', 'Uncanny Automator', 'uncanny-automator-pro' ),
					'type' => 'text',
				),
			),
			$this->get_action_code()
		);
		$this->register_action();
	}

	/**
	 * Defines the options for customizing the QR code generation.
	 *
	 * @return array The options array.
	 */
	public function load_options() {
		return array(
			'options_group' => array(
				$this->get_action_meta() => array(
					Automator()->helpers->recipe->field->text(
						array(
							'option_code'      => 'DATA',
							'label'            => esc_html__( 'Data to encode', 'uncanny-automator-pro' ),
							'description'      => esc_html__( 'Enter the content that the QR code will represent.', 'uncanny-automator-pro' ),
							'required'         => true,
							'input_type'       => 'textarea',
							'supports_tinymce' => false,
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'QR_SIZE',
							'label'       => esc_html__( 'QR code size', 'uncanny-automator-pro' ),
							'description' => esc_html__( 'Set the size of the QR code in pixels. Default is 300.', 'uncanny-automator-pro' ),
							'required'    => false,
							'input_type'  => 'int',
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'FOREGROUND_COLOR',
							'label'       => esc_html__( 'Foreground color', 'uncanny-automator-pro' ),
							'description' => esc_html__( 'Set the foreground color of the QR code in RGB format (e.g., 0,0,0 for black). Default is black 0,0,0.', 'uncanny-automator-pro' ),
							'required'    => false,
							'input_type'  => 'text', // Consider enhancing with a color picker.
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'BACKGROUND_COLOR',
							'label'       => esc_html__( 'Background color', 'uncanny-automator-pro' ),
							'description' => esc_html__( 'Set the background color of the QR code in RGB format (e.g., 255,255,255 for white). Default is white 255,255,255.', 'uncanny-automator-pro' ),
							'required'    => false,
							'input_type'  => 'text', // Consider enhancing with a color picker.
						)
					),
//					Automator()->helpers->recipe->field->text(
//						array(
//							'option_code' => 'LABEL_TEXT',
//							'label'       => esc_html__( 'Label text', 'uncanny-automator-pro' ),
//							'description' => esc_html__( 'Enter text for a label to appear below the QR code. Leave empty for no label.', 'uncanny-automator-pro' ),
//							'required'    => false,
//							'input_type'  => 'text',
//						)
//					),
//					Automator()->helpers->recipe->field->text(
//						array(
//							'option_code' => 'LABEL_COLOR',
//							'label'       => esc_html__( 'Label color', 'uncanny-automator-pro' ),
//							'description' => esc_html__( 'Set the color of the QR code label in RGB format (e.g., 0,0,0 for black). Default is black 0,0,0.', 'uncanny-automator-pro' ),
//							'required'    => false,
//							'input_type'  => 'text', // Consider enhancing with a color picker.
//						)
//					),
					Automator()->helpers->recipe->field->select(
						array(
							'option_code' => 'ENCODING',
							'label'       => esc_html__( 'Encoding', 'uncanny-automator-pro' ),
							'description' => esc_html__( 'Choose the character encoding.', 'uncanny-automator-pro' ),
							'required'    => false,
							'options'     => array(
								array(
									'value' => 'UTF-8',
									'text'  => 'UTF-8',
								),
								array(
									'value' => 'ISO-8859-1',
									'text'  => 'ISO-8859-1',
								),
							),
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'LOGO',
							'label'       => esc_html__( 'Media ID', 'uncanny-automator-pro' ),
							'description' => esc_html__( 'Enter a media library ID for the logo. You cannot use URL of an image.', 'uncanny-automator-pro' ),
							'required'    => false,
							'input_type'  => 'int',
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'LOGO_SIZE',
							'label'       => esc_html__( 'Logo size', 'uncanny-automator-pro' ),
							'description' => esc_html__( 'Enter the size of the logo in pixels. Default is 48.', 'uncanny-automator-pro' ),
							'required'    => false,
							'input_type'  => 'int',
						)
					),
					Automator()->helpers->recipe->field->text(
						array(
							'option_code' => 'MARGIN',
							'label'       => esc_html__( 'Margin', 'uncanny-automator-pro' ),
							'description' => esc_html__( 'Set the margin (border) around the QR code in pixels.', 'uncanny-automator-pro' ),
							'required'    => false,
							'input_type'  => 'int',
						)
					),
					Automator()->helpers->recipe->field->select(
						array(
							'option_code' => 'OUTPUT_FORMAT',
							'label'       => esc_html__( 'Output format', 'uncanny-automator-pro' ),
							'description' => esc_html__( 'Select the output format of the QR code.', 'uncanny-automator-pro' ),
							'required'    => false,
							'options'     => array(
								array(
									'value' => 'png',
									'text'  => 'PNG',
								),
								array(
									'value' => 'svg',
									'text'  => 'SVG',
								),
								array(
									'value' => 'eps',
									'text'  => 'EPS',
								),
							),
						)
					),
					Automator()->helpers->recipe->field->select(
						array(
							'option_code' => 'ERROR_CORRECTION_LEVEL',
							'label'       => esc_html__( 'Error correction level', 'uncanny-automator-pro' ),
							'description' => esc_html__( 'Select the error correction level for the QR code.', 'uncanny-automator-pro' ),
							'required'    => false,
							'options'     => array(
								array(
									'value' => 'low',
									'text'  => esc_html__( 'Low - L', 'uncanny-automator-pro' ),
								),
								array(
									'value' => 'medium',
									'text'  => esc_html__( 'Medium - M', 'uncanny-automator-pro' ),
								),
								array(
									'value' => 'quartile',
									'text'  => esc_html__( 'Quartile - Q', 'uncanny-automator-pro' ),
								),
								array(
									'value' => 'high',
									'text'  => esc_html__( 'High - H', 'uncanny-automator-pro' ),
								),
							),
						)
					),
				),
			),
		);
	}

	/**
	 * Processes the action to generate and save the QR code based on provided data and options.
	 *
	 * @param int $user_id The user ID.
	 * @param array $action_data Data associated with the action.
	 * @param int $recipe_id The recipe ID.
	 * @param array $args Additional arguments.
	 *
	 * @throws \Exception
	 */
	protected function process_action( $user_id, $action_data, $recipe_id, $args, $parsed ) {
		try {
			$data = $parsed['DATA'];

			if ( empty( $data ) ) {
				throw new \Exception( __( 'Data to encode field is empty.', 'uncanny-automator-pro' ) );
			}

			$qr_size    = ! empty( $parsed['QR_SIZE'] ) ? intval( $parsed['QR_SIZE'] ) : 300;
			//$label_text = ! empty( $parsed['LABEL_TEXT'] ) ? sanitize_text_field( $parsed['LABEL_TEXT'] ) : '';
			$label_text = '';

//			$label_color = ! empty( $parsed['LABEL_COLOR'] ) ? $this->parse_colour( sanitize_text_field( $parsed['LABEL_COLOR'] ) ) : array(
//				0,
//				0,
//				0,
//			);

			$foreground_color = ! empty( $parsed['BACKGROUND_COLOR'] ) ? $this->parse_colour( sanitize_text_field( $parsed['FOREGROUND_COLOR'] ) ) : array(
				0,
				0,
				0,
			);

			$background_color = ! empty( $parsed['BACKGROUND_COLOR'] ) ? $this->parse_colour( sanitize_text_field( $parsed['BACKGROUND_COLOR'] ) ) : array(
				255,
				255,
				255,
			);
			$encoding         = ! empty( $parsed['ENCODING'] ) ? sanitize_text_field( $parsed['ENCODING'] ) : 'UTF-8';
			$logo_path        = ! empty( $parsed['LOGO'] ) ? sanitize_text_field( $parsed['LOGO'] ) : '';
			$logo_size        = ! empty( $parsed['LOGO_SIZE'] ) ? intval( $parsed['LOGO_SIZE'] ) : 48;
			$margin           = ! empty( $parsed['MARGIN'] ) ? intval( $parsed['MARGIN'] ) : 10;
			$output_format    = ! empty( $parsed['OUTPUT_FORMAT'] ) ? sanitize_text_field( $parsed['OUTPUT_FORMAT'] ) : 'png';

			// Initialize logo and label as null
			$logo             = null;
			$label            = null;
			$error_correction = ! empty( $parsed['ERROR_CORRECTION_LEVEL'] ) ? $parsed['ERROR_CORRECTION_LEVEL'] : 'low';

			switch ( $error_correction ) {
				case 'low':
					$error_correct_level = new ErrorCorrectionLevel\ErrorCorrectionLevelLow();
					break;
				case 'medium':
					$error_correct_level = new ErrorCorrectionLevel\ErrorCorrectionLevelMedium();
					break;
				case 'quartile':
					$error_correct_level = new ErrorCorrectionLevel\ErrorCorrectionLevelQuartile();
					break;
				case 'high':
					$error_correct_level = new ErrorCorrectionLevel\ErrorCorrectionLevelHigh();
					break;
				default:
					$error_correct_level = new ErrorCorrectionLevel\ErrorCorrectionLevelLow();
			}

			$qr_code = QrCode::create( $data )
							 ->setSize( $qr_size )
							 ->setEncoding( new Encoding( $encoding ) )
							 ->setErrorCorrectionLevel( $error_correct_level )
							 ->setForegroundColor( new Color( (int) $foreground_color[0], (int) $foreground_color[1], (int) $foreground_color[2] ) )
							 ->setBackgroundColor( new Color( (int) $background_color[0], (int) $background_color[1], (int) $background_color[2] ) )
							 ->setMargin( $margin );

			// Logo handling (revised with Logo object)
			$upload_dir = wp_upload_dir();
			$base_url   = $upload_dir['baseurl'];

			if ( ! empty( $logo_path ) ) {

				$logo_attachment_url = $logo_path;
				// Assume $logo_path could be an ID or URL
				if ( is_numeric( $logo_path ) ) {
					$logo_attachment_url = get_attached_file( $logo_path );
				}

				if ( ! is_numeric( $logo_path ) && strpos( $logo_path, $base_url ) === 0 ) {
					$relative_path       = str_replace( $base_url, '', $logo_path );
					$logo_attachment_url = $upload_dir['basedir'] . $relative_path;
				}

				$logo = Logo::create( $logo_attachment_url )
							->setResizeToWidth( $logo_size )
							->setPunchoutBackground( true );
			}

			// Label handling
//			if ( ! empty( $label_text ) ) {
//				$label = Label::create( $label_text )
//							  ->setTextColor( new Color( (int) $label_color[0], (int) $label_color[1], (int) $label_color[2] ) );
//			}

			// Determine the writer based on output format
			$writer = $this->get_writer_by_format( $output_format );

			// Generate and save the QR code
			$file_name = uniqid( 'qr_code_', true ) . '.' . $output_format;

			// Define the QR code storage directory within the WordPress uploads directory
			$qr_dir_path = $upload_dir['basedir'] . '/automator_qr';

			// Check if the directory exists, if not, create it and secure it
			if ( ! file_exists( $qr_dir_path ) ) {
				wp_mkdir_p( $qr_dir_path );

				// Secure the directory
				// For Apache servers
				$htaccess_rules = "Options -Indexes\n<FilesMatch '\.(jpg|jpeg|png|gif|svg|eps)$'>\n  Order Allow,Deny\n  Allow from all\n</FilesMatch>";
				file_put_contents( $qr_dir_path . '/.htaccess', $htaccess_rules );
			}

			// Complete file path where the QR code image will be saved
			$file_path = $qr_dir_path . '/' . $file_name;

			$writer->write( $qr_code, $logo, $label )->saveToFile( $file_path );

			// Provide a URL to access the QR code image
			$qr_url = wp_upload_dir()['baseurl'] . '/automator_qr/' . $file_name;

			// Hydrate tokens with the URL to the generated QR code
			$this->hydrate_tokens(
				array(
					'QR_CODE_URL' => $qr_url,
					'QR_CODE_IMG' => sprintf( '<img src="%s" alt="%s" />', $qr_url, $label_text ),
				)
			);

			// Set log properties.
			$this->set_log_properties(
				array(
					'type'  => 'url',
					'label' => __( 'Generated QR', 'uncanny-automator-pro' ),
					'value' => $qr_url,
				)
			);

			Automator()->complete->action( $user_id, $action_data, $recipe_id );
		} catch ( \Exception $e ) {
			throw new \Exception( __( 'Unable to generate the QR code.', 'uncanny-automator-pro' ) . ' ' . $e->getMessage() );
		}
	}

	/**
	 * @param $colour_string
	 *
	 * @return array
	 */
	private function parse_colour( $colour_string ) {
		return array_map( 'absint', explode( ',', $colour_string ) );
	}

	/**
	 * Get the appropriate writer object based on the desired output format.
	 *
	 * @param string $format The desired output format (e.g., 'png', 'svg', 'eps').
	 *
	 * @return WriterInterface The writer object for the specified format.
	 */
	private function get_writer_by_format( $format ) {
		switch ( $format ) {
			case 'svg':
				return new SvgWriter();
			case 'eps':
				return new EpsWriter();
			case 'png':
			default:
				return new PngWriter();
		}
	}
}
