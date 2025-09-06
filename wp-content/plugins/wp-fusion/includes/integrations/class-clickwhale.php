<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * ClickWhale Integration.
 *
 * @since 3.45.8
 */
class WPF_Clickwhale extends WPF_Integrations_Base {

	/**
	 * The slug for WP Fusion's module tracking.
	 *
	 * @since 3.45.8
	 * @var string $slug
	 */

	public $slug = 'clickwhale';

	/**
	 * The plugin name for WP Fusion's module tracking.
	 *
	 * @since 3.45.8
	 * @var string $name
	 */
	public $name = 'Clickwhale';

	/**
	 * The link to the documentation on the WP Fusion website.
	 *
	 * @since 3.45.8
	 * @var string $docs_url
	 */
	public $docs_url = 'https://wpfusion.com/documentation/affiliates/clickwhale/';


	/**
	 * Gets things started.
	 *
	 * @since 3.45.8
	 */
	public function init() {
		add_filter( 'clickwhale_link_tabs', array( $this, 'add_tab' ) );
		add_action( 'clickwhale_link_after_tabs_content', array( $this, 'add_content' ), 10, 2 );
		add_action( 'clickwhale_link_updated', array( $this, 'save_settings' ), 10, 2 );

		add_action( 'clickwhale/link_clicked', array( $this, 'link_clicked' ), 10, 3 );
	}

	/**
	 * Link Clicked.
	 *
	 * Apply tags when a ClickWhale link is clicked.
	 *
	 * @since 3.45.8
	 *
	 * @param string $link The ClickWhale link.
	 * @param int    $link_id The ClickWhale link ID.
	 * @param int    $user_id The user ID.
	 */
	public function link_clicked( $link, $link_id, $user_id ) {

		$settings = get_post_meta( $link_id, 'wpf_settings_clickwhale', true );

		if ( empty( $settings ) || empty( $settings['apply_tags'] ) ) {
			return;
		}

		wp_fusion()->user->apply_tags( $settings['apply_tags'] );
	}

	/**
	 * Add Tab
	 * Add the WP Fusion tab to the edit ClickWhale link page.
	 *
	 * @since 3.45.8
	 *
	 * @param array $tabs The tabs.
	 * @return array $tabs The tabs.
	 */
	public function add_tab( $tabs ) {
		$tabs['wpf_tab'] = array(
			'name' => __( 'WP Fusion', 'wp-fusion' ),
			'url'  => 'wpf_tab',
		);
		return $tabs;
	}

	/**
	 * Add Content
	 * Add the WP Fusion page to the WP Fusion tab.
	 *
	 * @since 3.45.8
	 *
	 * @param array $item The ClickWhale link.
	 */
	public function add_content( $item ) {
		$settings = get_post_meta( $item['id'], 'wpf_settings_clickwhale', true );

		if ( empty( $settings ) ) {
			$settings = array();
		}

		if ( ! isset( $settings['apply_tags'] ) ) {
			$settings['apply_tags'] = array();
		}

		wp_nonce_field( 'wpf_meta_box_clickwhale', 'wpf_meta_box_clickwhale_nonce' );
		?>
		<div id="link-tab-wpf_tab">
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="wpf-apply-tags"><?php esc_html_e( 'Apply Tags', 'wp-fusion' ); ?></label>
					</th>
					<td>
						<?php
						wpf_render_tag_multiselect(
							array(
								'setting'   => $settings['apply_tags'],
								'meta_name' => 'wpf_settings_clickwhale',
								'field_id'  => 'apply_tags',
							)
						);
						?>
						<p class="description"><?php printf( __( 'These tags will be applied in %s when a logged-in user clicks the link.', 'wp-fusion' ), wp_fusion()->crm->name ); ?></p>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Save Settings
	 * Save the apply tags for the ClickWhale link.
	 *
	 * @since 3.45.8
	 *
	 * @param int   $item_id The ID of the ClickWhale link.
	 * @param array $post The post data.
	 */
	public function save_settings( $item_id, $post ) {

		// Check if our nonce is set.
		if ( ! isset( $_POST['wpf_meta_box_clickwhale_nonce'] ) ) {
			return;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['wpf_meta_box_clickwhale_nonce'], 'wpf_meta_box_clickwhale' ) ) {
			return;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! empty( $post['wpf_settings_clickwhale'] ) ) {
			update_post_meta( $item_id, 'wpf_settings_clickwhale', $post['wpf_settings_clickwhale'] );
		} else {
			delete_post_meta( $item_id, 'wpf_settings_clickwhale' );
		}
	}
}

new WPF_Clickwhale();
