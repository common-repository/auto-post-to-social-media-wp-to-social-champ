<?php
/**
 * The Wp_Socialchamp_Admin_Settings_Init class is used to initialize admin settings
 *
 * @link       https://www.socialchamp.io/
 * @since      1.0.0
 *
 * @package    Wp_Socialchamp
 * @subpackage Wp_Socialchamp/admin
 * @author     SocialChamp <torontodigits@gmail.com>
 */
class Wp_Socialchamp_Admin_Settings_Init {

	public $api;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->api = new WP_SocialChamp_API();
		// Action - Add Settings Menu
		add_action( 'admin_menu', array( $this, 'wpsc_admin_menu' ), 12 );
	}

	/**
	 * Create Admin Menu Page
	 *
	 * @since   1.0.0
	 */
	public function wpsc_admin_menu() {
		$settings_slug = 'wp-socialchamp-settings';
		add_menu_page( __( 'Social Champ Settings', 'wp-socialchamp' ), __( 'Social Champ', 'wp-socialchamp' ), 'manage_options', $settings_slug, false, plugins_url( 'images/admin-menu-icon.png', __FILE__ ) );

		add_submenu_page(
			$settings_slug,
			'Settings',
			'Settings',
			'manage_options',
			$settings_slug,
			array( $this, 'wpsc_settings_tab_menu' )
		);

		add_submenu_page(
			'wp-socialchamp-settings',
			'Logs',
			'Logs',
			'manage_options',
			'wp-socialchamp-logs',
			array( $this, 'wpsc_settings_logs' )
		);
	}

	private function wpsc_save_checkbox( $key ) {
		$_val = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : 0; //phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( in_array( $_val, array( 0, 1 ) ) ) {  // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			update_option( 'wpsc_' . $key, $_val );
		}
	}

	private function wpsc_checked( $key ) {
		echo checked( 1, get_option( 'wpsc_' . $key ) );
	}

	private function wpsc_save_array( $key ) {

		$publish_data = array();

		for ( $i = 0; $i < count( $_POST[ $key ]['image']  ); $i++ ) {
			$publish_data[] = array(
				'image'        => ! empty( $_POST[ $key ]['image'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ]['image'][ $i ] ) ) : '', //phpcs:ignore WordPress.Security.NonceVerification.Missing
				'content'      => ! empty( $_POST[ $key ]['content'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ]['content'][ $i ] ) ) : '', //phpcs:ignore WordPress.Security.NonceVerification.Missing
				'queue_bottom' => ! empty( $_POST[ $key ]['queue_bottom'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ]['queue_bottom'][ $i ] ) ) : '', //phpcs:ignore WordPress.Security.NonceVerification.Missing
			);
		}

		update_option( 'wpsc_' . $key, $publish_data );
	}

	public function setSocialInfo( $profile ) {

		$profile = ! is_array( $profile ) ? [ 'id' => 'nan', 'name' => 'none'] : $profile;
		$type = isset( $profile['type'] ) ? $profile['type'] : '';

		switch ( $type ) {
			case 'FB_PAGE':
				$profile['icon']  = 'facebook';
				$profile['title'] = 'Facebook Page';
				break;
			case 'FB_GROUP':
				$profile['icon']  = 'facebook';
				$profile['title'] = 'Facebook Group';
				break;
			case 'TW':
				$profile['icon']  = 'twitter';
				$profile['title'] = 'Twitter';
				break;
			case 'PINIT_PAGE':
				$profile['icon']  = 'pinterest';
				$profile['title'] = 'Pinterest';
				break;
			case 'IG':
				$profile['icon']  = 'instagram';
				$profile['title'] = 'Instagram';
				break;
			case 'IG_BUSINESS':
				$profile['icon']  = 'instagram';
				$profile['title'] = 'Instagram Business';
				break;
			case 'IN':
				$profile['icon']  = 'linkedin';
				$profile['title'] = 'Linkedin';
				break;
			case 'IN_PAGE':
				$profile['icon']  = 'linkedin';
				$profile['title'] = 'Linkedin Page';
				break;
			case 'G_BUSINESS':
				$profile['icon']  = 'google';
				$profile['title'] = 'Google My Business';
				break;
			default:
				$profile['icon']  = 'circle';
				$profile['title'] = 'none';
		}//end switch
		return $profile;
	}

	/**
	 * Display Settings
	 *
	 * @since   1.0.0
	 */
	public function wpsc_settings_tab_menu() {

		$post = new WP_SocialChamp_Post();

		$socialchampSidebar = $post->getPostTypes();

		$socialchampSubtab = array(
			'publish' => array(
				'title'       => 'Publish',
				'action'      => 'Published',
				'description' => esc_html__( 'If enabled, any status(es) configured here will be sent to Social Champ when a :singular_name is', 'wp-socialchamp' ),
			),
			'update'  => array(
				'title'       => 'Update',
				'action'      => 'Updated',
				'description' => esc_html__( 'If enabled, any status(es) defined here will be sent to Social Champ when a :singular_name is', 'wp-socialchamp' ),
			),
		);

		$display_message = '';

		if ( isset( $_POST['save'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
			// General Settings
			$this->wpsc_save_checkbox( 'test_mode' );
			$this->wpsc_save_checkbox( 'force_trailing_forwardslash' );
			// $this->wpsc_save_checkbox('proxy');

			$this->wpsc_save_checkbox( 'log_enabled' );
			$this->wpsc_save_checkbox( 'log_display_on_posts' );
			$this->wpsc_save_checkbox( 'log_level_success' );
			$this->wpsc_save_checkbox( 'log_level_test' );
			$this->wpsc_save_checkbox( 'log_level_pending' );
			$this->wpsc_save_checkbox( 'log_level_warning' );
			$this->wpsc_save_checkbox( 'log_level_error' );
			$this->wpsc_save_checkbox( 'log_preserve_days' );

			foreach ( $socialchampSidebar as $type => $value ) {

				$this->wpsc_save_checkbox( "{$type}_publish_enabled" );
				$this->wpsc_save_checkbox( "{$type}_update_enabled" );

				$_profiles = ! empty( $_POST[ $type ]['profiles'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST[ $type ]['profiles'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

				update_option( "wpsc_{$type}_profiles", $_profiles );

				$key = "{$type}_publish";
				$this->wpsc_save_array( $key );

				$key = "{$type}_update";
				$this->wpsc_save_array( $key );

			}

			$display_message = esc_html__( 'Settings updated succesfully.', 'wp-socialchamp' );

		}//end if

		$this->api->handleAuthCode();
		$this->api->handleDisconnect();
		$this->api->handleProfileRefresh();

		$profiles = $this->api->getProfiles();
		?>
		<!-- SocialChamp Settings Page Start-->
		<div class="wpsc-settings-wrap sc-wrap">

			<?php

			if ( ! empty( $display_message ) ) {
				echo '<div class="notice notice-success is-dismissible">
                        <p>' . esc_html( $display_message ) . '</p>
                    </div>';
			}
			?>

			<form action="<?php echo esc_url( $this->api->redirectUrl ); ?>" method="POST">
			<!-- <p>This page is used for SC Settings.</p>
			<h2 class="title">Hello</h2> -->
			<div class="container-fluid">
			  <div class="sc-inner-wrap">
				<div class="row">
					<div class="col-md-3">
						<aside class="sc-sidebar">
							<!--Logo Start-->
							<div class="sc-log_wrap">
								<strong class="sc-title">
									<!-- WP Social Champ -->
										<img src="<?php echo esc_url( plugin_dir_url( __DIR__ ) ); ?>admin/images/SC-logo-SVG.svg">
								</strong>

							</div>
							<!--Logo End-->

							<!--Tabs Content Start-->
							<div class="sc-tabs-wrap">
								<ul class="nav nav-tabs" id="sc-tabs" role="tablist">
								  <li class="nav-item">
									<a class="nav-link active" id="setting-tab" data-toggle="tab" href="#setting" role="tab" aria-controls="setting" aria-selected="true">
									  <i class="fa fa-lock" aria-hidden="true"></i>
									  Settings
									</a>
								  </li>
									<?php foreach ( $socialchampSidebar as $postType => $champSidebar ) : ?>
								  <li class="nav-item">
									<a class="nav-link" id="<?php echo esc_attr( $postType ); ?>-tab" data-toggle="tab" href="#<?php echo esc_attr( $postType ); ?>" role="tab" aria-controls="<?php echo esc_attr( $postType ); ?>" aria-selected="false">
										<?php echo '<i class="dashicons ' . esc_attr( $champSidebar['icon'] ) . '" aria-hidden="true"></i>'; ?>
										<?php echo esc_html( $champSidebar['title'] ); ?>
									</a>
								  </li>
								  <?php endforeach; ?>
								  <!--<li class="nav-item">
									<a class="nav-link" id="pages-tab" data-toggle="tab" href="#pages" role="tab" aria-controls="pages" aria-selected="false">
									  <i class="fa fa-clone" aria-hidden="true"></i>
									  Pages
									</a>
								  </li>-->
								</ul>
							</div>
							<div class="bottom-brand">
								<span class="name">
									Auto Post to Social Champ
								</span>
								<span class="version"><?php echo WP_SOCIALCHAMP_VERSION; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							</div>
						</aside>
					</div>
					<div class="col-md-9">
						<div class="sc-content-wrap">
						  <div class="tab-content sc-tabs-content" id="sc-TabContent">
							  <div class="tab-pane fade show active" id="setting" role="tabpanel" aria-labelledby="setting-tab">
								<!--Setting Tabs Content Start-->
								<div class="sc-setting-content">
								  <!--Sub Tabs Listed Start-->
								  <ul class="nav nav-tabs" id="sc-sub-tabs" role="tablist-sub">
									<li class="nav-item">
									  <a class="nav-link active" id="authentication-tab" data-toggle="tab" href="#authentication" role="tab" aria-controls="authentication" aria-selected="true">
										<i class="fa fa-unlock-alt" aria-hidden="true"></i>
										<?php echo esc_html__( 'Authentication', 'wp-socialchamp' ); ?>
									  </a>
									</li>
									<li class="nav-item">
									  <a class="nav-link" id="general-tab" data-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="false">
										<i class="fa fa-cog" aria-hidden="true"></i>
									   <?php echo esc_html__( 'General Settings', 'wp-socialchamp' ); ?>
									  </a>
									</li>
									<li class="nav-item">
									  <a class="nav-link" id="log-tab" data-toggle="tab" href="#log" role="tab" aria-controls="log" aria-selected="false">
										<i class="fa fa-file-text" aria-hidden="true"></i>
										Log Settings
									  </a>
									</li>
								  </ul><!--Sub Tabs Listed End-->

								  <!--Tabs Content Start-->
								  <div class="tab-content sc-subtabs-content" id="sc-sub-TabContent">
									<div class="tab-pane fade show active" id="authentication" role="tabpanel" aria-labelledby="authentication-tab">
									  <div class="sc-postbox">
										<div class="sc-post-top">
										  <h5><?php echo esc_html__( 'Authentication', 'wp-socialchamp' ); ?></h5>
										  <p><?php echo esc_html__( 'Authentication allows WordPress to schedule or post on your Social Champ account.', 'wp-socialchamp' ); ?></p>
										</div>
										<div class="sc-content-wrap">

											<?php if ( $this->api->isLoggedIn() ) : ?>
										  <div class="sc-plugin-conent">
											<p><?php echo esc_html__( 'Thanks - you\'ve authorized the plugin to connect with your Social Champ account.', 'wp-socialchamp' ); ?> -<strong> <?php echo esc_html( get_option( 'wpsc_auth_name', '' ) ); ?></strong>.
											</p>
										  </div>
											  <div class="btn-wrap">
												  <a class="sc-btn sc-button-red" href="<?php echo esc_url( $this->api->getDisconnectUrl() ); ?>"> <?php echo esc_html__( 'Deauthorize Social Champ Profile.', 'wp-socialchamp' ); ?></a>
												  <a class="sc-btn sc-button-red refresh-profiles" href="<?php echo esc_url( $this->api->getProfilesUrl() ); ?>" style=""><?php echo esc_html__( 'Refresh Profiles.', 'wp-socialchamp' ); ?></a>
											  </div>
											<?php else : ?>
												<div class="sc-plugin-conent">
													<p><?php echo esc_html__( 'To allow this Plugin to post to your Social Champ account, please authorize below.', 'wp-socialchamp' ); ?>
													</p>
												</div>
												<div class="btn-wrap">
													<a class="sc-btn sc-button-blue" href="<?php echo esc_url( $this->api->getAuthUrl() ); ?>"> <?php echo esc_html__( 'Authorize SocialChamp Profile', 'wp-socialchamp' ); ?></a>
												</div>

											<?php endif ?>

										</div>

									  </div>
									</div>

									<div class="tab-pane fade" id="general" role="tabpanel" aria-labelledby="general-tab">
									  <div class="sc-postbox">
										<div class="sc-post-top">
										  <h5><?php echo esc_html__( 'General Settings', 'wp-socialchamp' ); ?></h5>
										  <p><?php echo esc_html__( 'Provides options to enable test mode and force trailing forward-slash when publishing or updating Posts.', 'wp-socialchamp' ); ?></p>
										</div>
										<div class="sc-content-wrap">
										  <div class="sc-wpzinc-option">
											  <div class="left">
												  <label for="test_mode"><?php echo esc_html__( 'Enable Test Mode', 'wp-socialchamp' ); ?></label>
											  </div>
											  <div class="right">
												  <input type="checkbox" name="test_mode" id="test_mode" value="1" <?php $this->wpsc_checked( 'test_mode' ); ?>>

												  <p class="description">
													  <?php echo esc_html__( 'If enabled, status(es) are not sent to the Social Champ account but will appear in the Logs, if logging is enabled.', 'wp-socialchamp' ); ?></p>
											  </div>
										  </div>

										  <div class="sc-wpzinc-option">
											  <div class="left">
												  <label for="force_trailing_forwardslash"><?php echo esc_html__( 'Force Trailing Forwardslash?', 'wp-socialchamp' ); ?></label>
											  </div>
											  <div class="right">
												  <input type="checkbox" name="force_trailing_forwardslash" id="force_trailing_forwardslash" value="1"  <?php $this->wpsc_checked( 'force_trailing_forwardslash' ); ?>>

												  <p class="description">
													  <?php echo esc_html__( 'If enabled, any URLs in statuses will always end with a forwardslash.', 'wp-socialchamp' ); ?></p>
											  </div>
										  </div>


										</div>

									  </div>


									</div>

									<div class="tab-pane fade" id="log" role="tabpanel" aria-labelledby="log-tab">
									  <div class="sc-postbox">
										<div class="sc-post-top">
										  <h5><?php echo esc_html__( 'Log Settings', 'wp-socialchamp' ); ?></h5>
										  <p><?php echo esc_html__( 'Provides options to enable logging, display logs on Posts, and how long to keep logs for.', 'wp-socialchamp' ); ?></p>
										</div>
										<div class="sc-content-wrap sc-content-wrap">
											<div class="sc-wpzinc-option">
											  <div class="left">
												  <label for="log_enabled"><?php echo esc_html__( 'Enable Logging?', 'wp-socialchamp' ); ?></label>
											  </div>
											  <div class="right">
												  <input type="checkbox" name="log_enabled" id="log_enabled" value="1"  <?php $this->wpsc_checked( 'log_enabled' ); ?> data-conditional="enable_logging">

												  <p class="description">
													  <?php echo esc_html__( 'If enabled, the Plugin Logs will list status(es) sent to the Social Champ account.', 'wp-socialchamp' ); ?>
												  </p>
											  </div>
										  </div>

										  <div id="enable_logging">
											  <!-- <div class="sc-wpzinc-option">
												  <div class="left">
													  <label for="log_display_on_posts">Display on Posts?</label>
												  </div>
												  <div class="right">
													  <input type="checkbox" name="log_display_on_posts" id="log_display_on_posts" value="1"  <?php $this->wpsc_checked( 'log_display_on_posts' ); ?>>

													  <p class="description">
														  If enabled, a Log will be displayed when editing a Post.
													  </p>

														   </div>
											  </div> -->

											  <div class="sc-wpzinc-option">
												  <div class="left">
													  <label for="log_level"><?php echo esc_html__( 'Log Level', 'wp-socialchamp' ); ?></label>
												  </div>
												  <div class="right">
												  <label for="log_level_success">
												  <input type="checkbox" name="log_level_success" id="log_level_success" value="1"  <?php $this->wpsc_checked( 'log_level_success' ); ?>>Success
												  </label>
													<br>
												  <label for="log_level_test">
												  <input type="checkbox" name="log_level_test" id="log_level_test" value="1" <?php $this->wpsc_checked( 'log_level_test' ); ?>><?php echo esc_html__( 'Tests', 'wp-socialchamp' ); ?>
												</label>
												<br>
												<label for="log_level_pending">
												<input type="checkbox" name="log_level_pending" id="log_level_pending" value="1" <?php $this->wpsc_checked( 'log_level_pending' ); ?>><?php echo esc_html__( 'Pending', 'wp-socialchamp' ); ?> </label>
												  <br>
													<label for="log_level_warning">
													<input type="checkbox" name="log_level_warning" id="log_level_warning" value="1" <?php $this->wpsc_checked( 'log_level_warning' ); ?>><?php echo esc_html__( 'Warnings', 'wp-socialchamp' ); ?> </label>
													<br>
													<label for="log_level_error">
													<input type="checkbox" name="log_level_error" id="log_level_error" value="1" <?php $this->wpsc_checked( 'log_level_error' ); ?>><?php echo esc_html__( 'Errors', 'wp-socialchamp' ); ?></label>
														  <br>
													<p class="description">
														<?php echo esc_html__( 'Defines which log results to save to the Log database. Errors will always be logged.', 'wp-socialchamp' ); ?></p>
												  </div>
											  </div>

											  <div class="sc-wpzinc-option">
												  <div class="left">
													  <label for="log_preserve_days"><?php echo esc_html__( 'Preserve Logs', 'wp-socialchamp' ); ?>
												  </label></div>
												  <div class="right">
													  <input type="number" name="log_preserve_days" id="log_preserve_days" value="<?php echo esc_attr( get_option( 'wpsc_log_preserve_days', 30 ) ); ?>" min="0" max="9999" step="1">
													  days
													  <p class="description">
														  <?php echo esc_html__( 'The number of days to preserve logs for.  Zero means logs are kept indefinitely.', 'wp-socialchamp' ); ?>                            </p>
												  </div>
											  </div>
										  </div>


										</div>

									  </div>

									</div>

								  </div>
								</div><!--Setting Tabs Content End-->

							  </div>

							  <?php foreach ( $socialchampSidebar as $postType => $champSidebar ) : ?>
							  <div class="tab-pane fade" id="<?php echo esc_attr( $postType ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $postType ); ?>-tab">
								<!--Post Tabs Content Start-->
								<div class="sc-setting-content sc-vertical-content">
								  <!--Sub Tabs Listed Start-->
								  <ul class="nav nav-tabs" id="sc-post-sub-tabs" role="tablist-sub">
									<li class="nav-item">
									  <a class="nav-link active" id="<?php echo esc_attr( $postType ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>default-tab" data-toggle="tab" href="#<?php echo $postType; ?>default" role="tab" aria-controls="<?php echo esc_attr( $postType ); ?>default" aria-selected="true">
										<i class="fa fa-cog" aria-hidden="true"></i>
										Defaults
									  </a>
									</li>
									  <?php
										foreach ( $profiles as $profile ) :
											$profile = $this->setSocialInfo( $profile );
											?>
									<li class="nav-item">
									  <a class="nav-link" id="<?php echo esc_attr( $postType . $profile['id'] ); ?>-tab" data-toggle="tab" href="#<?php echo esc_attr( $postType . $profile['id'] ); ?>" role="tab" aria-controls="<?php echo esc_attr( $postType . $profile['id'] ); ?>" aria-selected="false">
										<i class="fa fa-<?php echo esc_attr( $profile['icon'] ); ?>" aria-hidden="true"></i>
											<?php echo esc_html( $profile['name'] ); ?>
									  </a>
									</li>

									  <?php endforeach; ?>
								  </ul><!--Sub Tabs Listed End-->

								  <!--Tabs Content Start-->
								  <div class="tab-content sc-subtabs-content pt-4" id="sc-post-sub-TabContent">
									<div class="tab-pane sc-tab-sub-content-wrap fade show active" id="<?php echo esc_attr( $postType ); ?>default" role="tabpanel" aria-labelledby="default-tab">

									  <?php include 'partials/wp-socialchamp-settings-init-default-display.php'; ?>

									</div>

									  <?php
										$profiles_enabled = get_option( "wpsc_{$postType}_profiles", array() );

										foreach ( $profiles as $profile ) :
											$profile = $this->setSocialInfo( $profile );
											?>

									<div class="tab-pane fade" id="<?php echo esc_attr( $postType . $profile['id'] ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $postType . $profile['id'] ); ?>-tab">
									  <div class="sc-postbox">
										<div class="sc-post-top">
										  <h5><?php echo esc_html( $profile['title'] ); ?> : <?php echo esc_html( $profile['name'] ); ?> Settings</h5>
<!--                                          <div class="notice-inline notice-warning">-->
<!--                                            <p>-->
<!--                                                This Profile's Timezone does not match your WordPress timezone.  They must be the same, to ensure that statuses-->
<!--                                                can be scheduled, and are scheduled at the correct time.<br>-->
<!--                                                <br>-->
<!--                                                Right now, your timezones are configured as:<br>-->
<!--                                                WordPress Timezone: +0000 (2020-10-23 17:45) [<a href="#" target="_blank">Fix</a></p>-->
<!--                                          </div>-->
										</div>
										<div class="sc-content-wrap">
										  <div class="sc-wpzinc-option">
											  <div class="left">
												  <label for="<?php echo esc_attr( $postType . $profile['id'] ); ?>>"><?php echo esc_html__( 'Account Enabled', 'wp-socialchamp' ); ?></label>
											  </div>
											  <div class="right">
												  <input <?php echo checked( 1, ! empty( $profiles_enabled[ $profile['id'] ] ) ? @$profiles_enabled[ $profile['id'] ] : 0 ); ?> type="checkbox" name="<?php echo esc_attr( $postType ); ?>[profiles][<?php echo esc_attr( $profile['id'] ); ?>]" id="<?php echo esc_attr( $postType ) . esc_attr( $profile['id'] ); //phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged ?>" value="1">

												  <p class="description">
														<?php echo esc_html__( 'Enabling this means that the plugin will post to this social network if the conditions in the Defaults and Settings are met.', 'wp-socialchamp' ); ?></p>
											  </div>
										  </div>

										</div>

									  </div>

									</div>

								  <?php endforeach; ?>

								  </div>
								</div><!--Post Tabs Content End-->
							  </div>
							  <?php endforeach; ?>

							</div>
						</div>
					</div>
				</div>
			  </div>

			  <div class="btn-save-wrap mt-4">
				  <input class="sc-btn button-primary btn-save" type="submit" name="save" value="Save">
			  </div>

			</div>
		</div><!-- SocialChamp Settings Page End-->
		<?php
	}

	public function wpsc_settings_logs() {
		?>

		<!-- SocialChamp Log Content Start-->
		<div class="wpsc-log-wrap sc-wrap">

			<div class="container-fluid">
			  <div class="sc-inner-wrap sc-log-inner-wrap">
				<div class="row">
				  <div class="col-md-12">
					<!--Logo Start-->
					<aside class="sc-sidebar">
						<div class="sc-log_wrap">
							<div class="logs-logo">
								<img src="<?php echo plugin_dir_url( __DIR__ ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>admin/images/SC-logo-SVG.svg">
							</div>
							<div class="bottom-brand">
								<span class="name">
									Auto Post to Social Champ
								</span>
								<span class="version"><?php echo WP_SOCIALCHAMP_VERSION; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							</div>
						</div>

					</aside><!--Logo End-->
				  </div>
				  <div class="col-md-12">
					<!--Log Content Start-->
					<div class="sc-log-content">
					  <form action="" method="post" id="wpsc_logs-table">
						  <input type="hidden" name="page" value="<?php echo ! empty( $_REQUEST['page'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>"/>
<!--                         <p class="search-box">-->
<!--                            <label class="screen-reader-text" for="wp-to-social-log-search-input">Search:</label>-->
<!--                            <input type="search" id="wp-to-social-log-search-input" name="s" value="" placeholder="Post ID or Title">-->
<!--                            <input type="submit" id="search-submit" class="button" value="Search">-->
<!--                         </p>-->
<!--                         <input type="hidden" id="_wpnonce" name="">-->
<!--                         <input type="hidden" name="">-->
<!--                         <div class="tablenav top">-->
<!--                            <div class="alignleft actions bulkactions">-->
<!--                               <label for="bulk-action-selector-top" class="screen-reader-text">-->
<!--                               Select bulk action   </label>-->
<!--                               <select name="bulk_action" id="bulk-action-selector-top" size="1">-->
<!--                                  <option value="-1">Bulk Actions</option>-->
<!--                                  <option value="delete">Delete</option>-->
<!--                               </select>-->
<!--                               <select name="action" size="1">-->
<!--                                  <option value="" selected="selected">Filter by Action</option>-->
<!--                                  <option value="publish">Publish</option>-->
<!--                                  <option value="update">Update</option>-->
<!--                               </select>-->
<!--                               <select name="profile_id" size="1">-->
<!--                                  <option value="" selected="selected">Filter by Profile</option>-->
<!--                               </select>-->
<!--                               <select name="result" size="1">-->
<!--                                  <option value="" selected="selected">Filter by Result</option>-->
<!--                                  <option value="success">-->
<!--                                     Success-->
<!--                                  </option>-->
<!--                                  <option value="test">-->
<!--                                     Test-->
<!--                                  </option>-->
<!--                                  <option value="warning">-->
<!--                                     Warning-->
<!--                                  </option>-->
<!--                                  <option value="error">-->
<!--                                     Error-->
<!--                                  </option>-->
<!--                               </select>-->
<!--                               <input type="date" name="" value="">-->
<!--                               --->
<!--                               <input type="date" name="" value="">-->
<!--                               <input type="submit" id="doaction" class="button action" value="Apply">-->
<!--                               <a href="#" class="clear-log button wpzinc-button-red">-->
<!--                               Clear Log    </a>-->
<!--                            </div>-->
<!--                            <div class="tablenav-pages no-pages">-->
<!--                              <span class="displaying-num">0 items</span>-->
<!--                               <span class="pagination-links">-->
<!--                               <span class="tablenav-pages-navspan button disabled">«</span>-->
<!--                               <span class="tablenav-pages-navspan button disabled">‹</span>-->
<!--                               <span class="paging-input">-->
<!--                                <label for="current-page-selector" class="screen-reader-text">Current Page</label><input class="current-page" id="current-page-selector" type="text" name="" value="1" size="1">-->
<!--                                <span class="tablenav-paging-text"> of -->
<!--                                  <span class="total-pages">0</span>-->
<!--                                  </span>-->
<!--                                </span>-->
<!--                               <a class="next-page button" href="#">-->
<!--                                <span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span>-->
<!--                              </a>-->
<!--                               <a class="last-page button" href="#">-->
<!--                                <span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span>-->
<!--                              </a>-->
<!--                            </span>-->
<!--                            </div>-->
<!--                            <br class="clear">-->
<!--                         </div>-->

						  <?php
							$table = new WP_SocialChamp_Logs_Table();
							$table->prepare_items();
							$table->display();

							?>
					  </form>
					</div><!--Log content End-->
				  </div>
				</div>
			  </div>

			  <div class="btn-save-wrap mt-4">
				<a href="#" class="sc-btn button-primary btn-save" target="_blank">Save</a>
			  </div>

			</div>
			</form>
		</div><!-- SocialChamp Log Content End-->
		<?php
	}

}

new Wp_Socialchamp_Admin_Settings_Init();
