<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPF_ProfilePress extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $slug
	 */

	public $slug = 'profilepress';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.38.14
	 * @var string $name
	 */
	public $name = 'Profilepress';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.38.14
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/membership/profilepress/';

	/**
	 * If PPress is full version.
	 *
	 * @var bool $full_version
	 */
	private $full_version = false;

	/**
	 * Init
	 * Gets things started.
	 *
	 * @since 1.0
	 * @since 3.43.2 Added compatability for the free plugin.
	 */
	public function init() {

		// The full version of ProfilePress's class is called ProfilePress_Dir.
		// The free version of ProfilePress's class is called ProfilePress\Core\Base.
		$this->full_version = class_exists( 'ProfilePress_Dir' ) ? true : false;

		add_filter( 'admin_menu', array( $this, 'page_menu' ) );

		add_filter( 'wpf_user_register', array( $this, 'user_register_filter' ), 10, 2 );

		// User Meta hooks.
		// The free plugin has different hooks.
		if ( $this->full_version ) {
			add_action( 'pp_after_profile_update', array( $this, 'user_update' ), 10, 2 );
			add_filter( 'pp_after_registration', array( $this, 'user_register' ), 10, 3 );
		} else {
			add_action( 'ppress_after_profile_update', array( $this, 'user_update' ), 10, 2 );
			add_action( 'ppress_after_registration', array( $this, 'user_register' ), 10, 3 );
		}
	}

	/**
	 * Page Menu
	 * Creates WFPP submenu item.
	 *
	 * @since unknown
	 * @since 3.43.2 Added compatability for the free plugin.
	 */
	public function page_menu(): void {

		// Create the WP Fusion submenu item.
		// If the free plugin is installed, change the parent slug to 'ppress-dashboard'.
		$id = add_submenu_page(
			$this->full_version ? 'pp-config' : 'ppress-dashboard',
			'WP Fusion - ProfilePress',
			'WP Fusion',
			'manage_options',
			'pp-wpf',
			array( $this, 'wpf_settings_page' ),
			11, // We set 11 here so that our settings appear above the highlighted Addons setting, instead of at the very bottom.
		);

		add_action( 'load-' . $id, array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Renders WPPP Styles
	 *
	 * @access public
	 * @return void
	 */
	public function enqueue_scripts() {

		wp_enqueue_style( 'options-css', WPF_DIR_URL . 'includes/admin/options/css/options.css' );
		wp_enqueue_style( 'wpf-options', WPF_DIR_URL . 'assets/css/wpf-options.css' );
	}


	/**
	 * WPF Settings Page
	 * Renders PP submenu item.
	 *
	 * @since unknown
	 * @since 3.43.2 Added compatability for the free plugin.
	 *
	 * @return mixed HTML output.
	 */
	public function wpf_settings_page(): void {

		// phpcs:ignore -- Ignoring unslashed and santization errors.
		if ( isset( $_POST['PROFILEPRESS_sql::sql_wp_list_table_registration_builder();'] ) && wp_verify_nonce( $_POST['PROFILEPRESS_sql::sql_wp_list_table_registration_builder();'], 'wpf_pp_settings' ) && ! empty( $_POST['wpf-settings'] ) ) {

			// phpcs:ignore -- Ignoring unslashed and santization errors.
			update_option( 'wpf_pp_settings', $_POST['wpf-settings'] );
			echo '<div id="message" class="updated fade"><p><strong>Settings saved.</strong></p></div>';
		}

		$settings = get_option( 'wpf_pp_settings', array() );

		if ( $this->full_version ) {
			$registration_builder = PROFILEPRESS_sql::sql_wp_list_table_registration_builder();
		} else {
			$registration_builder = ProfilePress\Core\Classes\FormRepository::get_forms( 'registration' );
		}

		?>
		<div id="wrap">
		
			<form id="wpf-pp-settings" action="" method="post">
				<?php wp_nonce_field( 'wpf_pp_settings', 'PROFILEPRESS_sql::sql_wp_list_table_registration_builder();' ); ?>	        	
				<input type="hidden" name="action" value="update">				
					<h4>Registration Forms</h4>
				
					<p class="description">For each Registration Form below, specify tags to be applied in <?php echo wp_fusion()->crm->name; ?> when user is registered.</p>
				
					<br/>
				
					<table class="table table-hover" id="wpf-coursewre-levels-table">
						<thread>
				
							<tr>
							
								<th style="text-align:left;">Registration Forms</th>
					
								<th style="text-align:left;">Apply Tags</th>
					
							</tr> 
						</thread>
						<tbody>
							<?php foreach ( $registration_builder as $data ) : ?>
					
								<?php
									// The free version saves the form title as name.
									$title = isset( $data['title'] ) ? $data['title'] : $data['name'];
									$id    = $data['id'];
								?>

								<?php

								if ( ! isset( $settings[ $id ] ) ) {
									$settings[ $id ] = array( 'apply_tags' => array() );
								}
								?>
					
								<tr style="border-bottom: 2px solid #ddd !important;">
					
									<td style="font-weight: bold;text-transform: uppercase;"><?php echo $title; ?></td>
					
									<td>
					
										<?php
											$args = array(
												'setting' => $settings[ $id ]['apply_tags'],
												'meta_name' => "wpf-settings[{$id}][apply_tags]",
											);
											wpf_render_tag_multiselect( $args );
											?>
									</td>
								
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table> 
				<p class="submit"><input name="Submit" type="submit" class="button-primary" value="Save Changes"/>
					</p>
			</form>
		</div>
		<?php
	}


	/**
	 * Adds User Meta field group to meta fields list
	 *
	 * @access  public
	 * @return  array Field groups
	 */
	public function add_meta_field_group( $field_groups ) {

		$field_groups['profilepress'] = array(
			'title' => __( 'ProfilePress', 'wp-fusion' ),
			'url'   => 'https://wpfusion.com/documentation/membership/profilepress/',
		);

		return $field_groups;
	}

	/**
	 * Prepare Meta Fields
	 * Adds User Meta meta fields to WPF contact fields list.
	 *
	 * @since unknown
	 * @since 3.43.2 Added compatability for the free plugin.
	 *
	 * @param array $meta_fields The meta fields.
	 *
	 * @return array The meta fields.
	 */
	public function add_meta_fields( $meta_fields ) {

		// The free version doesn't allow custom fields, but we can check anyway in case that changes.
		$profilepress_fields = $this->full_version ? PROFILEPRESS_sql::sql_wp_list_table_profile_fields() : ProfilePress\Core\Classes\PROFILEPRESS_sql::get_profile_custom_fields();

		foreach ( $profilepress_fields as $field ) {

			$meta_fields[ $field['field_key'] ] = array(
				'label' => $field['label_name'],
				'type'  => $field['type'],
				'group' => 'profilepress',
			);

		}

		return $meta_fields;
	}

	/**
	 * Push changes to user meta on profile update and registration
	 *
	 * @access  public
	 * @return  void
	 */
	public function user_update( $user_data, $form_id ) {

		wp_fusion()->user->push_user_meta( $user_data['ID'], $user_data );
	}

	/**
	 * User Register
	 * Triggered when new user registered through a ProfilePress Registration Form.
	 *
	 * @since unknown
	 * @since 3.43.2 Added compatability for the free plugin.
	 *
	 * @param int   $form_id The form ID of the registration form.
	 * @param array $user_data The user data.
	 * @param int   $user_id The user ID.
	 */
	public function user_register( int $form_id, array $user_data, int $user_id ): void {

		if ( empty( $form_id ) || empty( $user_data ) || empty( $user_id ) ) {
			return;
		}

		$settings = get_option( 'wpf_pp_settings', array() );

		if ( $this->full_version ) {

			// Full Version.

			$this->update_user( $settings, $form_id, $user_data, isset( $user_data['ID'] ) ? $user_data['ID'] : $user_id );

		} else {

			// Free Version.

			$forms = ProfilePress\Core\Classes\FormRepository::get_forms( 'registration' );

			// We need to get the form IDs this way because the free version saves them differently.
			foreach ( $forms as $form ) {

				// phpcs:ignore Universal.Operators.StrictComparisons -- Strict comparison doesn't work here.
				if ( isset( $form['id'] ) && $form_id == $form['form_id'] ) {

					// The free version handles the user submitted data differently, so we need to adjust for that.
					$this->update_user( $settings, $form['id'], $user_data, isset( $user_data['ID'] ) ? $user_data['ID'] : $user_id );
					break;
				}
			}
		}
	}

	/**
	 * Update User
	 * Updates user data and applies tags if specified in settings.
	 *
	 * @since 3.43.2
	 *
	 * @param array $settings The settings.
	 * @param int   $key The key.
	 * @param array $user_data The user data.
	 * @param int   $user_id The user ID.
	 */
	public function update_user( array $settings, int $key, array $user_data, int $user_id ): void {

		if ( ! empty( $settings[ $key ] ) && ! empty( $settings[ $key ]['apply_tags'] ) ) {

			wp_fusion()->user->apply_tags( $settings[ $key ]['apply_tags'], $user_id );
		}

		wp_fusion()->user->push_user_meta( $user_id, $user_data );
	}

	/**
	 * Triggered when new member is added
	 *
	 * @access  public
	 * @return  array Post data
	 */
	public function user_register_filter( $post_data, $user_id ) {

		if ( ! isset( $post_data['pp_current_url'] ) ) {
			return $post_data;
		}

		$field_map = array(
			'reg_password' => 'user_pass',
		);

		$post_data = $this->map_meta_fields( $post_data, $field_map );

		return $post_data;
	}
}

new WPF_ProfilePress();
