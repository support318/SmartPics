<?php
/**
 * Admin: About
 *
 * @package     AffiliateWP
 * @subpackage  Admin
 * @copyright   Copyright (c) 2023, Awesome Motive, Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.14.0
 *
 * phpcs:disable PEAR.Functions.FunctionCallSignature.EmptyLine
 */

namespace Affwp\Admin;

use AffWP\Core\License\License_Data;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

#[\AllowDynamicProperties]

/**
 * About page.
 *
 * Add about page and tabs.
 *
 * @since 2.14.0
 */
class About {

	/**
	 * Image path.
	 *
	 * @since 2.14.0
	 * @var string
	 */
	private string $images_url = AFFILIATEWP_PLUGIN_URL . 'assets/images/about/';

	/**
	 * Constructor.
	 *
	 * @since 2.14.0
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Get the instance of a class and store it in itself.
	 *
	 * @since 2.14.0
	 */
	public static function get_instance() : self {

		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Hooks.
	 *
	 * @since 2.14.0
	 */
	private function hooks() {

		// Output tab `About` contents.
		add_action( 'affwp_admin_about_contents_about', array( $this, 'about_us' ) );
		add_action( 'affwp_admin_about_contents_about', array( $this, 'am_plugins_grid' ) );

		// Output tab `Getting Started` contents.
		add_action( 'affwp_admin_about_contents_getting_started', array( $this, 'getting_started' ) );
		add_action( 'affwp_admin_about_contents_getting_started', array( $this, 'getting_started_upgrade' )  );

		// Output tab `Personal/Plus vs Pro` contents.
		// add_action( 'affwp_admin_about_contents_versus', array( $this, 'plan_comparison' )  );

		// Enqueue styles and scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 99 );

	}

	/**
	 * Enqueue JS and CSS files.
	 *
	 * This will be borrowed from our addons page.
	 *
	 * @since 2.14.0
	 */
	public function enqueue_assets() : void {

		// Addons page style and script.
		wp_enqueue_style(
			'affwp_admin_addons',
			sprintf(
				"%s/assets/css/admin-addons%s.css",
				untrailingslashit( AFFILIATEWP_PLUGIN_URL ),
				defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min'
			),
			null,
			AFFILIATEWP_VERSION
		);

		wp_localize_script(
			'affwp-admin',
			'affwp_about_vars',
			array(
				'nonce' => wp_create_nonce( 'affiliate-wp-admin' ),
				'i18n'  => array(
					'active'                      => __( 'Active', 'affiliate-wp' ),
					'inactive'                    => __( 'Inactive', 'affiliate-wp' ),
					'activate'                    => __( 'Activate', 'affiliate-wp' ),
					'activated'                   => __( 'Activated', 'affiliate-wp' ),
					'pluginActivated'             => __( 'Plugin activated.', 'affiliate-wp' ),
					'pluginInstalledAndActivated' => __( 'Plugin installed and activated.', 'affiliate-wp' ),
					'genericError'                => __( 'Could not install the plugin. Please reload the page and try again.', 'affiliate-wp' ),
				),
			)
		);

	}

	/**
	 * Retrieves the user license type (always in lowercase).
	 *
	 * @since 2.14.0
	 *
	 * @return string $license_type User license type.
	 */
	public function get_user_license_type() : string {

		$license_data = affiliate_wp()->settings->get( 'license_status', array() );
		$license_id   = isset( $license_data->price_id ) ? absint( $license_data->price_id ) : false;

		return strtolower( ( new License_Data() )->get_license_type( $license_id ) );

	}

	/**
	 * Retrieves the about tabs.
	 *
	 * @since 2.14.0
	 *
	 * @return array $tabs Settings tabs.
	 */
	public function get_tabs() : array {

		// $license_type = self::get_instance()->get_user_license_type();

		return array_filter(
			array(
				'about'           => __( 'About', 'affiliate-wp' ),
				'getting_started' => __( 'Getting Started', 'affiliate-wp' ),
//				'versus'          => in_array( $license_type, array( 'personal', 'plus' ) )
//					? sprintf( '%1$s vs Pro', ucfirst( $license_type ) )
//					: ''
			)
		);

	}

	/**
	 * Generate and output page HTML.
	 *
	 * @since 2.14.0
	 *
	 * @return void
	 */
	public function output() : void {

		$tabs       = self::get_instance()->get_tabs();
		$active_tab = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs )
			? sanitize_text_field( $_GET['tab'] )
			: 'about';

		ob_start();
		?>
		<div id="affwp-admin-about-page" class="wrap">
			<h2 class="nav-tab-wrapper">
				<?php affwp_navigation_tabs( $tabs, $active_tab ); ?>
			</h2>
			<div id="tab_container" class="tab-active--<?php echo esc_attr( $active_tab ); ?>">
				<div class="affwp-admin-contents">
					<?php do_action( "affwp_admin_about_contents_{$active_tab}" ); ?>
				</div>
			</div><!-- #tab_container-->
		</div><!-- .wrap -->
		<?php
		echo ob_get_clean();
	}

	/**
	 * Renders the About page content.
	 *
	 * @since 2.14.0
	 *
	 * @return void
	 */
	public static function display() : void {
		self::get_instance()->output();
	}

	/**
	 * Renders the `About Us` content.
	 *
	 * @since 2.14.0
	 *
	 * @return void
	 */
	public function about_us() : void {

		ob_start();

		?>

		<div class="affwp-admin-section affwp-admin-about-section affwp-admin-flex">
			<div class="affwp-admin-content">
				<h3><?php esc_html_e( 'Hello and welcome to AffiliateWP, the premier affiliate marketing plugin for WordPress. At AffiliateWP, we build software that empowers you to easily create, manage, and grow your affiliate program, driving more revenue to your business.', 'affiliate-wp' ); ?></h3>
				<p><?php esc_html_e( "Over the years, we discovered that many affiliate marketing solutions were complex, difficult to navigate, and lacked the essential features that businesses needed. That's why we designed AffiliateWP to be user-friendly and powerful, with all the tools you need to succeed.", 'affiliate-wp' ); ?></p>
				<p><?php esc_html_e( 'Our goal is to take the pain out of setting up and managing your affiliate program, making it simple and efficient.', 'affiliate-wp' ); ?></p>
				<p>
					<?php
						echo sprintf(
							'%1$s <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>, %4$s <a href="%5$s" target="_blank" rel="noopener noreferrer">%6$s</a>, %7$s <a href="%8$s" target="_blank" rel="noopener noreferrer">%9$s</a>%10$s',
							esc_html__( 'AffiliateWP is brought to you by the same team that’s behind the largest WordPress resource site, ', 'affiliate-wp' ),
							esc_url( 'https://www.wpbeginner.com/?utm_source=affiliatewp-plugin&utm_medium=plugin-about-page&utm_campaign=about-affiliatewp&utm_content=wpbeginner' ),
							esc_html__( 'WPBeginner', 'affiliate-wp' ),
							esc_html__( 'the most popular lead-generation software, ', 'affiliate-wp' ),
							esc_url( 'https://www.optinmonster.com/?utm_source=affiliatewp-plugin&utm_medium=plugin-about-page&utm_campaign=about-affiliatewp&utm_content=optinmonster' ),
							esc_html__( 'OptinMonster', 'affiliate-wp' ),
							esc_html__( 'the best WordPress analytics plugin, ', 'affiliate-wp' ),
							esc_url( 'https://www.monsterinsights.com/?utm_source=affiliatewp-plugin&utm_medium=plugin-about-page&utm_campaign=about-affiliatewp&utm_content=monsterinsights' ),
							esc_html__( 'MonsterInsights', 'affiliate-wp' ),
							esc_html__( ', and more!', 'affiliate-wp' ),
						);
					?>
				</p>
				<p><?php esc_html_e( 'Yup, we know a thing or two about building awesome products that customers love.', 'affiliate-wp' ); ?></p>
			</div>
			<figure class="affwp-admin-figure">
				<img
					src="<?php echo esc_attr( "{$this->images_url}about-us-affiliatewp-team.png" ); ?>"
					width="800"
					height="432"
					alt="<?php esc_html_e( 'The AffiliateWP Team', 'affiliate-wp' ); ?>"
				/>
				<figcaption><?php esc_html_e( 'The AffiliateWP Team', 'affiliate-wp' ); ?></figcaption>
			</figure>
		</div>

		<?php

		echo ob_get_clean();
	}

	/**
	 * Renders the `Getting started` content.
	 *
	 * @since 2.14.0
	 *
	 * @return void
	 */
	public function getting_started() : void {
		ob_start();

		?>

		<div class="affwp-admin-section affwp-admin-getting-started-section affwp-admin-flex">
			<div class="affwp-admin-content">
				<h3><?php esc_html_e( 'Adding Your First Affiliate', 'affiliate-wp' ); ?></h3>
				<p><?php esc_html_e( 'Want to get started adding your first affiliate with AffiliateWP? By following the step by step instructions in this walkthrough, you can easily add your first affiliate (and many more!).', 'affiliate-wp' ); ?></p>
				<p><?php esc_html_e( 'To begin, click Affiliates (under AffiliateWP) in the left-hand menu and then click the Add New button.', 'affiliate-wp' ); ?></p>
				<p>
					<?php
					echo sprintf(
						'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
						esc_url( 'https://affiliatewp.com/docs/adding-new-affiliates/?utm_source=wordpress&utm_medium=plugin-about-us&utm_campaign=getting-started&utm_content=how-to-add-your-first-affiliate#manual-add' ),
						esc_html__( 'How to Add Your First Affiliate', 'affiliate-wp' ),
					);
					?>
				</p>
			</div>
			<figure class="affwp-admin-figure">
				<img
					src="<?php echo "{$this->images_url}getting-started-add-first-affiliate.svg"; ?>"
					width="800"
					height="432"
					alt="<?php esc_html_e( 'Adding Your First Affiliate', 'affiliate-wp' ); ?>"
				/>
			</figure>
		</div>

		<?php

		echo ob_get_clean();
	}

	/**
	 * Renders the `Getting Started` upgrade content.
	 *
	 * @since 2.14.0
	 *
	 * @return void
	 */
	public function getting_started_upgrade() : void {

		if ( in_array( self::get_instance()->get_user_license_type(), array( 'professional', 'ultimate' ), true ) ) {
			return; // Show only for non-pro users.
		}

		ob_start();

		?>

		<div class="affwp-admin-section">
			<div class="affwp-admin-about-section affwp-admin-about-section-hero-main">
				<h3><?php esc_html_e( 'Get AffiliateWP Pro and Unlock all the Powerful Features', 'affiliate-wp' ); ?></h3>

				<p class="bigger">
					<?php
					echo wp_kses(
						__(
							'Thanks for being a loyal AffiliateWP user. <strong>Upgrade to AffiliateWP Pro</strong> to unlock all the awesome features and see why AffiliateWP is the best affiliate marketing plugin for WordPress.',
							'affiliate-wp'
						),
						array( 'strong' => '' )
					);
					?>
				</p>

				<p>
					<?php
					echo wp_kses(
						__(
							'AffiliateWP is used by over <strong>30,000+ smart business owners</strong>. We know that you will truly love using it.',
							'affiliate-wp'
						),
						array( 'strong' => '' )
					);
					?>
				</p>
			</div>

			<div class="affwp-admin-about-section affwp-admin-about-section-hero-extra">
				<div class="affwp-admin-features-list">
					<ul>
						<li><?php esc_html_e( 'Flag or block referrals that are suspected of being fraudulent', 'affiliate-wp' ); ?></li>
						<li><?php esc_html_e( 'Provide an optimized experience for your affiliates with the Affiliate Portal', 'affiliate-wp' ); ?></li>
						<li><?php esc_html_e( 'Generate custom slugs automatically when affiliates register, or let them create their own', 'affiliate-wp' ); ?></li>
						<li><?php esc_html_e( 'Allow affiliates to promote your landing pages without using an affiliate link', 'affiliate-wp' ); ?></li>
						<li><?php esc_html_e( 'Allow affiliates to receive commission when customers renew subscription payments', 'affiliate-wp' ); ?></li>
					</ul>

					<ul>
						<li><?php esc_html_e( 'Allow affiliates to receive a commission on all future purchases by a customer', 'affiliate-wp' ); ?></li>
						<li><?php esc_html_e( 'Allow affiliates to link directly to your site, from their site, without the need for an affiliate link', 'affiliate-wp' ); ?></li>
						<li><?php esc_html_e( 'Reward affiliates with higher commission rates as they earn more or generate more referrals', 'affiliate-wp' ); ?></li>
						<li><?php esc_html_e( 'Allow affiliates to request a custom name for their coupons', 'affiliate-wp' ); ?></li>
						<li><?php esc_html_e( 'Receive priority support from our world-class support team', 'affiliate-wp' ); ?></li>
					</ul>
				</div>
				<hr/>

				<h4 class="call-to-action">
					<a href="<?php echo esc_url( affwp_admin_upgrade_link( 'affiliatewp-about-page', 'Get AffiliateWP Pro Today' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get AffiliateWP Pro Today and Unlock all the Powerful Features', 'affiliate-wp' ); ?></a>
				</h4>
				<p>
					<?php
					echo wp_kses(
						__(
							'Bonus: AffiliateWP users get <span class="discount">60% off the regular price</span>, automatically applied at checkout.',
							'affiliate-wp'
						),
						array(
							'span' => array(
								'class' => array(),
							),
						)
					);
					?>
				</p>

			</div>

		</div>
		<?php
		echo ob_get_clean();
	}

	/**
	 * Returns a list of features to be displayed on the comparison table.
	 *
	 * @since 2.14.0
	 *
	 * @param string $license_type Optionally filter features by license type .
	 * @return array[] Array of features.
	 */
	public function get_features_list( string $license_type = '' ) : array {

		$features_list =  array(
			array(
				'name'            => __( 'Form Entries', 'affiliate-wp' ),
				'description'     => __( 'Entries via Email Only', 'affiliate-wp' ),
				'pro_description' => __( 'Complete Entry Management inside WordPress', 'affiliate-wp' ),
				'icon'            => 'partial',
				'plans'           => array( 'personal', 'plus' )
			),
			array(
				'name'            => __( 'Form Fields', 'affiliate-wp' ),
				'description'     => __( "<b>Standard Fields Only</b><br>Name, Email, Single Line Text, Paragraph Text, Dropdown, Multiple Choice, Checkboxes, Numbers, and Number Slider", 'affiliate-wp' ),
				'pro_description' => __( "<b>Access to all Standard and Fancy Fields</b><br>Address, Phone, Website / URL, Date / Time, Password, File Upload, Layout, Rich Text, Content, HTML, Pagebreaks, Entry Preview, Section Dividers, Ratings, and Hidden Field", 'affiliate-wp' ),
				'icon'            => 'none',
				'plans'           => array( 'personal', 'plus' )
			),
			array(
				'name'            => __( 'Form Fields Plus Only', 'affiliate-wp' ),
				'description'     => __( "<b>Standard Fields Only</b><br>Name, Email, Single Line Text, Paragraph Text, Dropdown, Multiple Choice, Checkboxes, Numbers, and Number Slider", 'affiliate-wp' ),
				'pro_description' => __( "<b>Access to all Standard and Fancy Fields</b><br>Address, Phone, Website / URL, Date / Time, Password, File Upload, Layout, Rich Text, Content, HTML, Pagebreaks, Entry Preview, Section Dividers, Ratings, and Hidden Field", 'affiliate-wp' ),
				'icon'            => 'partial',
				'plans'           => array( 'plus' )
			),
		);

		if ( empty( $license_type ) ) {
			return $features_list;
		}

		return array_filter( $features_list, function( $feature ) use ( $license_type ) {
			return in_array( $license_type, $feature['plans'], true );
		});
	}

	/**
	 * Renders the plan comparison table.
	 *
	 * @since 2.14.0
	 *
	 * @return void
	 */

	public function plan_comparison() : void {

		$license_type = self::get_instance()->get_user_license_type();
		$feature_list = self::get_instance()->get_features_list( $license_type );

		ob_start();
		?>
		<div class="affwp-admin-section affwp-admin-plan-comparison">
			<div class="affwp-admin-plan-header">
				<h3>
					<?php
						printf(
							'<strong>%1$s</strong> %2$s <strong>%3$s</strong>',
							esc_html( ucfirst( $license_type ) ),
							esc_html__( 'vs', 'affiliate-wp' ),
							esc_html__( 'Pro', 'affiliate-wp' )
						);
					?>
				</h3>
				<p><?php esc_html_e( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'affiliate-wp' ); ?></p>
			</div>
			<table>
				<thead>
				<tr>
					<th><?php esc_html_e( 'Feature', 'affiliate-wp' ); ?></th>
					<th><?php esc_html_e( ucfirst( $license_type ) ); ?></th>
					<th>Pro</th>
				</tr>
				</thead>
				<tbody>
				<?php foreach( $feature_list as $feature ) : ?>
					<tr>
						<td><?php echo wp_kses( $feature['name'], array( 'b' => array(), 'br' => array() ) ) ?></td>
						<td class="affwp-admin-feature-icon affwp-admin-feature-icon--<?php echo esc_attr( $feature['icon'] ) ?>">
							<?php echo wp_kses( $feature['description'], array( 'b' => array(), 'br' => array() ) ) ?>
						</td>
						<td class="affwp-admin-feature-icon affwp-admin-feature-icon--full">
							<?php echo wp_kses( $feature['pro_description'], array( 'b' => array(), 'br' => array() ) ) ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<div class="affwp-admin-plan-footer">
				<h3>
					<?php
						printf(
							'<a href="%1$s">%2$s</a>',
							esc_attr( 'https://affiliatewp.com/' ),
							esc_html( __( 'Get AffiliateWP Pro Today and Unlock all the Powerful Features', 'affiliate-wp' ) )
						);
					?>
				</h3>
				<p>
					<?php
						echo wp_kses(
							__( 'Bonus: AffiliateWP users get <span>60% off regular price</span>, automatically applied at checkout.', 'affiliate-wp' ),
							array( 'span' => array( 'class' => array() ) )
						);
					?>
				</p>
			</div>
		</div>

		<?php
		echo ob_get_clean();
	}

	/**
	 * List of AM plugins that we propose to install.
	 *
	 * @since 2.14.0
	 *
	 * @return array
	 */
	protected function get_am_plugins() : array {

		return array(
			'optinmonster/optin-monster-wp-api.php' => array(
				'icon'  => $this->images_url . 'plugin-om.png',
				'name'  => esc_html__( 'OptinMonster', 'affiliate-wp' ),
				'desc'  => esc_html__( 'Instantly get more subscribers, leads, and sales with the #1 conversion optimization toolkit. Create high converting popups, announcement bars, spin a wheel, and more with smart targeting and personalization.', 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/optinmonster/',
				'url'   => 'https://downloads.wordpress.org/plugin/optinmonster.zip',
			),
			'google-analytics-for-wordpress/googleanalytics.php' => array(
				'icon'  => $this->images_url . 'plugin-mi.png',
				'name'  => esc_html__( 'MonsterInsights', 'affiliate-wp' ),
				'desc'  => esc_html__( 'The leading WordPress analytics plugin that shows you how people find and use your website, so you can make data driven decisions to grow your business. Properly set up Google Analytics without writing code.', 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/google-analytics-for-wordpress/',
				'url'   => 'https://downloads.wordpress.org/plugin/google-analytics-for-wordpress.zip',
				'pro'   => array(
					'plug' => 'google-analytics-premium/googleanalytics-premium.php',
					'icon' => $this->images_url . 'plugin-mi.png',
					'name' => esc_html__( 'MonsterInsights Pro', 'affiliate-wp' ),
					'desc' => esc_html__( 'The leading WordPress analytics plugin that shows you how people find and use your website, so you can make data driven decisions to grow your business. Properly set up Google Analytics without writing code.', 'affiliate-wp' ),
					'url'  => 'https://www.monsterinsights.com/?utm_source=wpformsplugin&utm_medium=link&utm_campaign=About%20AffiliateWP',
					'act'  => 'go-to-url',
				),
			),
			'wp-mail-smtp/wp_mail_smtp.php' => array(
				'icon'  => $this->images_url . 'plugin-smtp.png',
				'name'  => esc_html__( 'WP Mail SMTP', 'affiliate-wp' ),
				'desc'  => esc_html__( "Improve your WordPress email deliverability and make sure that your website emails reach user's inbox with the #1 SMTP plugin for WordPress. Over 3 million websites use it to fix WordPress email issues.", 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/wp-mail-smtp/',
				'url'   => 'https://downloads.wordpress.org/plugin/wp-mail-smtp.zip',
				'pro'   => array(
					'plug' => 'wp-mail-smtp-pro/wp_mail_smtp.php',
					'icon' => $this->images_url . 'plugin-smtp.png',
					'name' => esc_html__( 'WP Mail SMTP Pro', 'affiliate-wp' ),
					'desc' => esc_html__( "Improve your WordPress email deliverability and make sure that your website emails reach user's inbox with the #1 SMTP plugin for WordPress. Over 3 million websites use it to fix WordPress email issues.", 'affiliate-wp' ),
					'url'  => 'https://wpmailsmtp.com/?utm_source=wpformsplugin&utm_medium=link&utm_campaign=About%20AffiliateWP',
					'act'  => 'go-to-url',
				),
			),
			'all-in-one-seo-pack/all_in_one_seo_pack.php'  => array(
				'icon'  => $this->images_url . 'plugin-aioseo.png',
				'name'  => esc_html__( 'AIOSEO', 'affiliate-wp' ),
				'desc'  => esc_html__( "The original WordPress SEO plugin and toolkit that improves your website's search rankings. Comes with all the SEO features like Local SEO, WooCommerce SEO, sitemaps, SEO optimizer, schema, and more.", 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/all-in-one-seo-pack/',
				'url'   => 'https://downloads.wordpress.org/plugin/all-in-one-seo-pack.zip',
				'pro'   => array(
					'plug' => 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php',
					'icon' => $this->images_url . 'plugin-aioseo.png',
					'name' => esc_html__( 'AIOSEO Pro', 'affiliate-wp' ),
					'desc' => esc_html__( "The original WordPress SEO plugin and toolkit that improves your website's search rankings. Comes with all the SEO features like Local SEO, WooCommerce SEO, sitemaps, SEO optimizer, schema, and more.", 'affiliate-wp' ),
					'url'  => 'https://aioseo.com/?utm_source=wpformsplugin&utm_medium=link&utm_campaign=About%20AffiliateWP',
					'act'  => 'go-to-url',
				),
			),
			'coming-soon/coming-soon.php' => array(
				'icon'  => $this->images_url . 'plugin-seedprod.png',
				'name'  => esc_html__( 'SeedProd', 'affiliate-wp' ),
				'desc'  => esc_html__( 'The fastest drag & drop landing page builder for WordPress. Create custom landing pages without writing code, connect them with your CRM, collect subscribers, and grow your audience. Trusted by 1 million sites.', 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/coming-soon/',
				'url'   => 'https://downloads.wordpress.org/plugin/coming-soon.zip',
				'pro'   => array(
					'plug' => 'seedprod-coming-soon-pro-5/seedprod-coming-soon-pro-5.php',
					'icon' => $this->images_url . 'plugin-seedprod.png',
					'name' => esc_html__( 'SeedProd Pro', 'affiliate-wp' ),
					'desc' => esc_html__( 'The fastest drag & drop landing page builder for WordPress. Create custom landing pages without writing code, connect them with your CRM, collect subscribers, and grow your audience. Trusted by 1 million sites.', 'affiliate-wp' ),
					'url'  => 'https://www.seedprod.com/?utm_source=wpformsplugin&utm_medium=link&utm_campaign=About%20AffiliateWP',
					'act'  => 'go-to-url',
				),
			),
			'rafflepress/rafflepress.php' => array(
				'icon'  => $this->images_url . 'plugin-rp.png',
				'name'  => esc_html__( 'RafflePress', 'affiliate-wp' ),
				'desc'  => esc_html__( 'Turn your website visitors into brand ambassadors! Easily grow your email list, website traffic, and social media followers with the most powerful giveaways & contests plugin for WordPress.', 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/rafflepress/',
				'url'   => 'https://downloads.wordpress.org/plugin/rafflepress.zip',
				'pro'   => array(
					'plug' => 'rafflepress-pro/rafflepress-pro.php',
					'icon' => $this->images_url . 'plugin-rp.png',
					'name' => esc_html__( 'RafflePress Pro', 'affiliate-wp' ),
					'desc' => esc_html__( 'Turn your website visitors into brand ambassadors! Easily grow your email list, website traffic, and social media followers with the most powerful giveaways & contests plugin for WordPress.', 'affiliate-wp' ),
					'url'  => 'https://rafflepress.com/?utm_source=wpformsplugin&utm_medium=link&utm_campaign=About%20AffiliateWP',
					'act'  => 'go-to-url',
				),
			),
			'pushengage/main.php' => array(
				'icon'  => $this->images_url . 'plugin-pushengage.png',
				'name'  => esc_html__( 'PushEngage', 'affiliate-wp' ),
				'desc'  => esc_html__( 'Connect with your visitors after they leave your website with the leading web push notification software. Over 10,000+ businesses worldwide use PushEngage to send 15 billion notifications each month.', 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/pushengage/',
				'url'   => 'https://downloads.wordpress.org/plugin/pushengage.zip',
			),
			'instagram-feed/instagram-feed.php' => array(
				'icon'  => $this->images_url . 'plugin-sb-instagram.png',
				'name'  => esc_html__( 'Smash Balloon Instagram Feeds', 'affiliate-wp' ),
				'desc'  => esc_html__( 'Easily display Instagram content on your WordPress site without writing any code. Comes with multiple templates, ability to show content from multiple accounts, hashtags, and more. Trusted by 1 million websites.', 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/instagram-feed/',
				'url'   => 'https://downloads.wordpress.org/plugin/instagram-feed.zip',
				'pro'   => array(
					'plug' => 'instagram-feed-pro/instagram-feed.php',
					'icon' => $this->images_url . 'plugin-sb-instagram.png',
					'name' => esc_html__( 'Smash Balloon Instagram Feeds Pro', 'affiliate-wp' ),
					'desc' => esc_html__( 'Easily display Instagram content on your WordPress site without writing any code. Comes with multiple templates, ability to show content from multiple accounts, hashtags, and more. Trusted by 1 million websites.', 'affiliate-wp' ),
					'url'  => 'https://smashballoon.com/instagram-feed/?utm_source=wpformsplugin&utm_medium=link&utm_campaign=About%20AffiliateWP',
					'act'  => 'go-to-url',
				),
			),
			'custom-facebook-feed/custom-facebook-feed.php' => array(
				'icon'  => $this->images_url . 'plugin-sb-fb.png',
				'name'  => esc_html__( 'Smash Balloon Facebook Feeds', 'affiliate-wp' ),
				'desc'  => esc_html__( 'Easily display Facebook content on your WordPress site without writing any code. Comes with multiple templates, ability to embed albums, group content, reviews, live videos, comments, and reactions.', 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/custom-facebook-feed/',
				'url'   => 'https://downloads.wordpress.org/plugin/custom-facebook-feed.zip',
				'pro'   => array(
					'plug' => 'custom-facebook-feed-pro/custom-facebook-feed.php',
					'icon' => $this->images_url . 'plugin-sb-fb.png',
					'name' => esc_html__( 'Smash Balloon Facebook Feeds Pro', 'affiliate-wp' ),
					'desc' => esc_html__( 'Easily display Facebook content on your WordPress site without writing any code. Comes with multiple templates, ability to embed albums, group content, reviews, live videos, comments, and reactions.', 'affiliate-wp' ),
					'url'  => 'https://smashballoon.com/custom-facebook-feed/?utm_source=wpformsplugin&utm_medium=link&utm_campaign=About%20AffiliateWP',
					'act'  => 'go-to-url',
				),
			),
			'feeds-for-youtube/youtube-feed.php' => array(
				'icon'  => $this->images_url . 'plugin-sb-youtube.png',
				'name'  => esc_html__( 'Smash Balloon YouTube Feeds', 'affiliate-wp' ),
				'desc'  => esc_html__( 'Easily display YouTube videos on your WordPress site without writing any code. Comes with multiple layouts, ability to embed live streams, video filtering, ability to combine multiple channel videos, and more.', 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/feeds-for-youtube/',
				'url'   => 'https://downloads.wordpress.org/plugin/feeds-for-youtube.zip',
				'pro'   => array(
					'plug' => 'youtube-feed-pro/youtube-feed.php',
					'icon' => $this->images_url . 'plugin-sb-youtube.png',
					'name' => esc_html__( 'Smash Balloon YouTube Feeds Pro', 'affiliate-wp' ),
					'desc' => esc_html__( 'Easily display YouTube videos on your WordPress site without writing any code. Comes with multiple layouts, ability to embed live streams, video filtering, ability to combine multiple channel videos, and more.', 'affiliate-wp' ),
					'url'  => 'https://smashballoon.com/youtube-feed/?utm_source=wpformsplugin&utm_medium=link&utm_campaign=About%20AffiliateWP',
					'act'  => 'go-to-url',
				),
			),
			'custom-twitter-feeds/custom-twitter-feed.php' => array(
				'icon'  => $this->images_url . 'plugin-sb-twitter.png',
				'name'  => esc_html__( 'Smash Balloon Twitter Feeds', 'affiliate-wp' ),
				'desc'  => esc_html__( 'Easily display Twitter content in WordPress without writing any code. Comes with multiple layouts, ability to combine multiple Twitter feeds, Twitter card support, tweet moderation, and more.', 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/custom-twitter-feeds/',
				'url'   => 'https://downloads.wordpress.org/plugin/custom-twitter-feeds.zip',
				'pro'   => array(
					'plug' => 'custom-twitter-feeds-pro/custom-twitter-feed.php',
					'icon' => $this->images_url . 'plugin-sb-twitter.png',
					'name' => esc_html__( 'Smash Balloon Twitter Feeds Pro', 'affiliate-wp' ),
					'desc' => esc_html__( 'Easily display Twitter content in WordPress without writing any code. Comes with multiple layouts, ability to combine multiple Twitter feeds, Twitter card support, tweet moderation, and more.', 'affiliate-wp' ),
					'url'  => 'https://smashballoon.com/custom-twitter-feeds/?utm_source=wpformsplugin&utm_medium=link&utm_campaign=About%20AffiliateWP',
					'act'  => 'go-to-url',
				),
			),
			'trustpulse-api/trustpulse.php' => array(
				'icon'  => $this->images_url . 'plugin-trustpulse.png',
				'name'  => esc_html__( 'TrustPulse', 'affiliate-wp' ),
				'desc'  => esc_html__( 'Boost your sales and conversions by up to 15% with real-time social proof notifications. TrustPulse helps you show live user activity and purchases to help convince other users to purchase.', 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/trustpulse-api/',
				'url'   => 'https://downloads.wordpress.org/plugin/trustpulse-api.zip',
			),
			'searchwp/index.php' => array(
				'icon'  => $this->images_url . 'plugin-searchwp.png',
				'name'  => esc_html__( 'SearchWP', 'affiliate-wp' ),
				'desc'  => esc_html__( 'The most advanced WordPress search plugin. Customize your WordPress search algorithm, reorder search results, track search metrics, and everything you need to leverage search to grow your business.', 'affiliate-wp' ),
				'wporg' => false,
				'url'   => 'https://searchwp.com/?utm_source=wpformsplugin&utm_medium=link&utm_campaign=About%20AffiliateWP',
				'act'   => 'go-to-url',
			),
			'wpforms-lite/wpforms.php' => array(
				'icon'  => $this->images_url . 'plugin-wpforms.png',
				'name'  => esc_html__( 'WPForms', 'affiliate-wp' ),
				'desc'  => esc_html__( 'The best drag & drop WordPress form builder. Easily create beautiful contact forms, surveys, payment forms, and more with our 100+ form templates. Trusted by over 4 million websites as the best forms plugin.', 'simple-pay' ),
				'wporg' => 'https://wordpress.org/plugins/wpforms-lite/',
				'url'   => 'https://downloads.wordpress.org/plugin/wpforms-lite.zip',
				'pro'   => array(
					'plug' => 'wpforms/wpforms.php',
					'icon' => $this->images_url . 'plugin-wpforms.png',
					'name'  => esc_html__( 'WPForms Pro', 'affiliate-wp' ),
					'desc'  => esc_html__( 'The best drag & drop WordPress form builder. Easily create beautiful contact forms, surveys, payment forms, and more with our 100+ form templates. Trusted by over 4 million websites as the best forms plugin.', 'simple-pay' ),
					'url'  => 'https://wpforms.com/?utm_source=wpsimplepay-plugin&utm_medium=link&utm_campaign=about-wpsimplepay',
					'act'  => 'go-to-url',
				),
			),
			'stripe/stripe-checkout.php' => array(
				'icon'  => $this->images_url . 'plugin-wp-simple-pay.png',
				'name'  => esc_html__( 'WP Simple Pay', 'affiliate-wp' ),
				'desc'  => esc_html__( 'The #1 Stripe payments plugin for WordPress. Start accepting one-time and recurring payments on your WordPress site without setting up a shopping cart. No code required.', 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/stripe/',
				'url'   => 'https://downloads.wordpress.org/plugin/stripe.zip',
				'pro'   => array(
					'plug' => 'wp-simple-pay-pro-3/simple-pay.php',
					'icon' => $this->images_url . 'plugin-wp-simple-pay.png',
					'name' => esc_html__( 'WP Simple Pay Pro', 'affiliate-wp' ),
					'desc' => esc_html__( 'The #1 Stripe payments plugin for WordPress. Start accepting one-time and recurring payments on your WordPress site without setting up a shopping cart. No code required.', 'affiliate-wp' ),
					'url'  => 'https://wpsimplepay.com/?utm_source=wpformsplugin&utm_medium=link&utm_campaign=About%20AffiliateWP',
					'act'  => 'go-to-url',
				),
			),
			'easy-digital-downloads/easy-digital-downloads.php' => array(
				'icon'  => $this->images_url . 'plugin-edd.png',
				'name'  => esc_html__( 'Easy Digital Downloads', 'affiliate-wp' ),
				'desc'  => esc_html__( 'The best WordPress eCommerce plugin for selling digital downloads. Start selling eBooks, software, music, digital art, and more within minutes. Accept payments, manage subscriptions, advanced access control, and more.', 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/easy-digital-downloads/',
				'url'   => 'https://downloads.wordpress.org/plugin/easy-digital-downloads.zip',
			),
			'sugar-calendar-lite/sugar-calendar-lite.php' => array(
				'icon'  => $this->images_url . 'plugin-sugarcalendar.png',
				'name'  => esc_html__( 'Sugar Calendar', 'affiliate-wp' ),
				'desc'  => esc_html__( 'A simple & powerful event calendar plugin for WordPress that comes with all the event management features including payments, scheduling, timezones, ticketing, recurring events, and more.', 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/sugar-calendar-lite/',
				'url'   => 'https://downloads.wordpress.org/plugin/sugar-calendar-lite.zip',
				'pro'   => array(
					'plug' => 'sugar-calendar/sugar-calendar.php',
					'icon' => $this->images_url . 'plugin-sugarcalendar.png',
					'name' => esc_html__( 'Sugar Calendar Pro', 'affiliate-wp' ),
					'desc' => esc_html__( 'A simple & powerful event calendar plugin for WordPress that comes with all the event management features including payments, scheduling, timezones, ticketing, recurring events, and more.', 'affiliate-wp' ),
					'url'  => 'https://sugarcalendar.com/?utm_source=wpformsplugin&utm_medium=link&utm_campaign=About%20AffiliateWP',
					'act'  => 'go-to-url',
				),
			),
			'charitable/charitable.php' => array(
				'icon'  => $this->images_url . 'plugin-charitable.png',
				'name'  => esc_html__( 'WP Charitable', 'affiliate-wp' ),
				'desc'  => esc_html__( 'Top-rated WordPress donation and fundraising plugin. Over 10,000+ non-profit organizations and website owners use Charitable to create fundraising campaigns and raise more money online.', 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/charitable/',
				'url'   => 'https://downloads.wordpress.org/plugin/charitable.zip',
			),
			'insert-headers-and-footers/ihaf.php' => array(
				'icon'  => $this->images_url . 'plugin-wpcode.png',
				'name'  => esc_html__( 'WPCode', 'affiliate-wp' ),
				'desc'  => esc_html__( 'Future proof your WordPress customizations with the most popular code snippet management plugin for WordPress. Trusted by over 1,500,000+ websites for easily adding code to WordPress right from the admin area.', 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/insert-headers-and-footers/',
				'url'   => 'https://downloads.wordpress.org/plugin/insert-headers-and-footers.zip',
			),
			'duplicator/duplicator.php' => array(
				'icon'  => $this->images_url . 'plugin-duplicator.png',
				'name'  => esc_html__( 'Duplicator', 'affiliate-wp' ),
				'desc'  => esc_html__( 'Leading WordPress backup & site migration plugin. Over 1,500,000+ smart website owners use Duplicator to make reliable and secure WordPress backups to protect their websites. It also makes website migration really easy.', 'affiliate-wp' ),
				'wporg' => 'https://wordpress.org/plugins/duplicator/',
				'url'   => 'https://downloads.wordpress.org/plugin/duplicator.zip',
			),
		);
	}

	/**
	 * Get AM plugin data to display in the Addons section of About tab.
	 *
	 * @since 2.14.0
	 * @since 2.27.3 Improved checking for pro plugins.
	 *
	 * @param string $plugin      Plugin slug.
	 * @param array  $details     Plugin details.
	 * @param array  $all_plugins List of all plugins.
	 *
	 * @return array
	 */
	protected function get_plugin_data( string $plugin, array $details, array $all_plugins ) : array {

		$have_pro = ( ! empty( $details['pro'] ) && ! empty( $details['pro']['plug'] ) );
		$show_pro = false;

		$plugin_data = [];

		if ( $have_pro ) {

			if ( array_key_exists( $plugin, $all_plugins ) ) {
				if ( is_plugin_active( $plugin ) ) {
					$show_pro = true;
				}
			}

			if ( array_key_exists( $details['pro']['plug'], $all_plugins ) ) {
				$show_pro = true;
			}

			if ( $show_pro ) {
				$plugin  = $details['pro']['plug'];
				$details = $details['pro'];
			}
		}

		if (
			in_array(
				$plugin,
				array_merge(
					array_keys( $all_plugins ),

					// Include the pro plugin if there is one, it could be pro or non-pro.
					isset( $details['plug'] )
						? [ $details['plug'] ]
						: []
				),
				true
			)
		) {
			if (
				is_plugin_active( $plugin ) ||

				// Check the pro versions.
				is_plugin_active( str_replace( '-pro', '', $plugin ) ) ||
				is_plugin_active( str_replace( '_pro', '', $plugin ) )
			) {
				// Status text/status.
				$plugin_data['status_class'] = 'status-active';
				$plugin_data['status_text']  = esc_html__( 'Active', 'affiliate-wp' );
				// Button text/status.
				$plugin_data['action_class'] = $plugin_data['status_class'] . ' button button-secondary disabled';
				$plugin_data['action_text']  = esc_html__( 'Activated', 'affiliate-wp' );
			} else {
				// Status text/status.
				$plugin_data['status_class'] = 'status-installed';
				$plugin_data['status_text']  = esc_html__( 'Inactive', 'affiliate-wp' );
				// Button text/status.
				$plugin_data['action_class'] = $plugin_data['status_class'] . ' button button-primary';
				$plugin_data['action_text']  = esc_html__( 'Activate', 'affiliate-wp' );
			}
			$plugin_data['plugin_src'] = esc_attr( $plugin );
		} else {
			// Doesn't exist, install.
			// Status text/status.
			$plugin_data['status_class'] = 'status-missing';

			if ( isset( $details['act'] ) && 'go-to-url' === $details['act'] ) {
				$plugin_data['status_class'] = 'status-go-to-url';
			}
			$plugin_data['status_text'] = esc_html__( 'Not Installed', 'affiliate-wp' );
			// Button text/status.
			$plugin_data['action_class'] = $plugin_data['status_class'] . ' button button-secondary';
			$plugin_data['action_text']  = esc_html__( 'Install Plugin', 'affiliate-wp' );
			$plugin_data['plugin_src']   = esc_url( $details['url'] );
		}

		$plugin_data['details'] = $details;

		return $plugin_data;
	}

	/**
	 * Renders the AM plugins grid.
	 *
	 * @since 2.14.0
	 *
	 * @return void
	 */
	public function am_plugins_grid() : void {

		if ( ! current_user_can( 'install_plugins' ) ) {
			return; // User can't install plugins.
		}

		$plugins = self::get_instance()->get_am_plugins();

		if ( empty( $plugins ) ) {
			return; // For some reason we have no plugins to display.
		}

		$all_plugins = get_plugins();

		ob_start();
		?>
		<div id="affwp-addons" class="about-us">
			<div class="affwp-addons-list">
				<?php foreach ( $plugins as $plugin => $details ) : ?>
					<?php $plugin_data = $this->get_plugin_data( $plugin, $details, $all_plugins ); ?>
					<div class="affwp-addon">
						<div class="affwp-addon-details">
							<span class="affwp-addon-img" style="background: none;">
								<img
									src="<?php echo esc_url( $plugin_data['details']['icon']  ); ?>"
									class="attachment-affwp-post-thumbnail size-affwp-post-thumbnail wp-post-image"
									alt="<?php echo esc_attr( $plugin_data['details']['name'] ); ?>"
									loading="lazy"
									title="<?php echo esc_attr( $plugin_data['details']['name'] ); ?>"
								/>
							</span>
							<div class="affwp-addon-text">
								<h3 class="affwp-addon-name"><?php echo esc_html( $plugin_data['details']['name'] ); ?></h3>
								<p><?php echo esc_html( $plugin_data['details']['desc'] ); ?></p>
							</div>
						</div>
						<div class="affwp-addon-action">
							<p class="affwp-status"><?php esc_html_e( 'Status:', 'affiliate-wp' ); ?>
								<?php
									printf(
										'<span class="status-label %1$s">%2$s</span>',
										esc_attr( $plugin_data['status_class'] ),
										esc_html( $plugin_data['status_text'] )
									);
								?>
							</p>
							<div class="affwp-action-button-container">
								<button
									class="affwp-action-button <?php echo esc_attr( $plugin_data['action_class'] ); ?>"
									data-plugin="<?php echo esc_attr( $plugin_data['plugin_src'] ); ?>">
										<?php echo esc_html( $plugin_data['action_text'] ?? '' ); ?>
								</button>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		echo ob_get_clean();
	}
}

// Init instance.
About::get_instance();
