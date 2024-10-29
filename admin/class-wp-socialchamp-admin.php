<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.socialchamp.io/
 * @since      1.0.0
 *
 * @package    Wp_Socialchamp
 * @subpackage Wp_Socialchamp/admin
 * @author     SocialChamp <torontodigits@gmail.com>
 */
class Wp_Socialchamp_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Fixes redirect issues when header is already sent
		if ( $this->is_sc_admin_page() ) {
			add_action(
				'admin_init',
				function () {
					ob_start();
				}
			);
		}

		/**
		 * The class responsible for defining all the plugin settings that occur in the front end area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-socialchamp-api.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-socialchamp-post.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-socialchamp-settings-init.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-socialchamp-logs-table.php';

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * It is used to enqueue admin Styles
		 */

		if ( $this->is_sc_admin_screen() ) {
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-socialchamp-admin.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name . '-sc-bootstrap-css', plugin_dir_url( __FILE__ ) . 'css/sc-bootstrap.css', array(), $this->version, 'all' );
			wp_enqueue_style( $this->plugin_name . '-font-awesome.min-css', plugin_dir_url( __FILE__ ) . 'css/font-awesome.min.css', array(), $this->version, 'all' );
		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * It is used to enqueue admin JavaScript script
		 */

		if ( $this->is_sc_admin_screen() ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wp-socialchamp-admin.js', array( 'jquery' ), $this->version, false );
			wp_enqueue_script( $this->plugin_name . '-sc-bootstrap-js', plugin_dir_url( __FILE__ ) . 'js/sc-bootstrap.js', array(), $this->version, false );
		}

		// wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/bootstrap.js', $this->version, false );
	}

	public function is_sc_admin_screen() {

		$screen   = get_current_screen();
		$sc_pages = array(
			'social-champ_page_wp-socialchamp-logs',
			'toplevel_page_wp-socialchamp-settings',
		);
		return in_array( $screen->id, $sc_pages );

	}

	public function is_sc_admin_page() {
		return isset( $_GET['page'] ) && in_array( //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_unslash( $_GET['page'] ),
			array(
				'wp-socialchamp-logs',
				'wp-socialchamp-settings',
			)
		);
	}

}
