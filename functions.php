<?php
/**
 * Created by IntelliJ IDEA.
 * User: stephen
 * Date: 2020-10-14
 * Time: 11:04 AM
 */


/*
Plugin Name: Restricted Login for WordPress
Plugin URI: https://github.com/schrauger/restricted-login-for-wordpress
Description: Allow admins and super admins, but block everyone else from logging in unless they are added to a whitelist.
			 This is useful for copying a production site to a development area where you don't want users to be able to login to the dev environment.
Version: 0.1
Author: Stephen Schrauger
Author URI: https://www.schrauger.com/
License: GPLv2 or later
*/

class admin_only_login {
	const option_group_name = 'restricted-login-for-wordpress-settings-group';

	const section_admin     = 'restricted-login-for-wordpress-admins'; // section in network setting to be able to disable standard admin logins
	const section_userlist      = 'restricted-login-for-wordpress-users'; // section in site settings to be able to allow specified standard users to login

	const page_title        = 'Restricted Login for WordPress Settings'; //
	const menu_title        = 'Restricted Login for WordPress Settings';
	const capability        = 'manage_options'; // user capability required to view the page
	const page_site_slug         = 'restricted-login-for-wordpress-site-settings'; // unique page name, also called menu_slug
	const page_network_slug         = 'restricted-login-for-wordpress-network-settings'; // unique page name, also called menu_slug

    public $is_network = false;

	public function __construct() {
		register_activation_hook( __FILE__, array(
			$this,
			'on_activation'
		) ); //call the 'on_activation' function when plugin is first activated
		register_deactivation_hook( __FILE__, array(
			$this,
			'on_deactivation'
		) ); //call the 'on_deactivation' function when plugin is deactivated
		register_uninstall_hook( __FILE__, array(
			$this,
			'on_uninstall'
		) ); //call the 'uninstall' function when plugin is uninstalled completely

		// Register the 'settings' page
//		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
//		add_action( 'network_admin_menu', array($this,'add_plugin_page') ); // adds converter settings page
//      add_action( 'admin_init', array($this, 'admin_init'));
//		add_action( 'admin_post_update_my_settings',  array($this, 'update_my_settings'));

		add_action( 'init', array($this, 'logout_users'));
		// Add a link from the plugin page to this plugin's settings page
//		add_filter( 'plugin_row_meta', array( $this, 'plugin_action_links' ), 10, 2 );

	}

	/**
	 * Function that is run when the plugin is activated via the plugins page
	 */
	public function on_activation() {
		// stub
	}

	public function on_deactivation() {
		// stub
	}

	public function on_uninstall() {
		// stub
	}

	public function logout_users() {
		// logout all non-admin users
		// added 2020-09-22 10:00am
		$block_login = true;

		if ( is_user_logged_in()) {

			// network admins must always be allowed to login
			if ( is_super_admin()){
				$block_login = false;
			}

			// site admins can be blocked if needed. setting is defined in the network admin settings page.
			// @TODO add this ability
			if ( is_admin()) {
				$block_login = false;
			}

			// list of allowed users. if not in the list, they're not allowed to login.
			// @TODO add this ability



			if ($block_login){
				wp_logout();
			}

		} else {
			// user is not logged in. do nothing.
		}
	}

	/**
	 * Adds a link to this plugin's setting page directly on the WordPress plugin list page
	 *
	 * @param $links
	 * @param $file
	 *
	 * @return array
	 */
	public function plugin_action_links( $links, $file ) {
		if ( strpos( __FILE__, $file ) !== false ) {
			$links = array_merge(
				$links,
				array(
					'settings' => '<a href="' . admin_url( 'options-general.php?page=' . self::page_slug ) . '">' . __( 'Settings', self::page_slug ) . '</a>'
				)
			);
		}

		return $links;
	}

	/**
	 * Adds a settings page for the plugin, both in site admin and network admin areas.
	 */
	public function add_plugin_page() {
        // add settings page for standard sites
		add_submenu_page(
			'options-general.php',
			self::page_title,
			self::menu_title,
			self::capability,
			self::page_site_slug,
			array($this,'options_page_site_contents')
		);

		// add settings page for network
		add_submenu_page(
			'settings.php',
			self::page_title,
			self::menu_title,
			self::capability,
			self::page_network_slug,
			array($this,'options_page_network_contents')
		);
	}

	public function options_page_site_contents(){
	    $this->is_network = false;
		$this->options_page_contents_site();
	}

    public function options_page_network_contents() {
	    $this->is_network = true;
	    $this->options_page_contents_network();
    }

	/**
	 * Tells WordPress how to output the page
	 */
	public function options_page_contents_site() {
		?>
        <div class="wrap" >

            <h2 ><?php echo self::page_title ?></h2 >

            <form method="post" action="options.php" >
				<?php
				// This prints out all hidden setting fields
				settings_fields( self::option_group_name );
				do_settings_sections( $this->is_network ? self::page_network_slug : self::page_site_slug );
				submit_button();
				?>
            </form >
        </div >
		<?php
	}
	/**
	 * Tells WordPress how to output the page
	 */
	public function options_page_contents_network() {
		?>
        <div class="wrap" >

            <h2 ><?php echo self::page_title ?></h2 >

            <form method="post" action="settings.php" >
				<?php
				// This prints out all hidden setting fields
				settings_fields( self::option_group_name );
				do_settings_sections( $this->is_network ? self::page_network_slug : self::page_site_slug );
				submit_button();
				?>
            </form >
        </div >
		<?php
	}

	function update_my_settings(){
		check_admin_referer('your_plugin_nonce');
		if(!current_user_can('manage_network_options')) wp_die('FU');

		// process your fields from $_POST here and update_site_option

		wp_redirect(admin_url('network/settings.php?page=my-netw-settings'));
		exit;
	}


	public function admin_init() {

		$this->add_settings_section();
		$this->add_setting_network_admin();

//		$this->posttypes = get_post_types('', 'objects');
//		foreach ($this->posttypes as $post_object) {
//			$this->add_setting($post_object);
//		}
	}

	public function add_settings_section() {

		add_settings_section(
			self::section_admin,
			"Admins", // start of section text shown to user
			"Caution",
			self::page_network_slug
		);
		add_settings_section(
			self::section_userlist,
			"Whitelisted Users",
			"No caution",
			self::page_site_slug
		);
	}

	public function add_setting_network_admin(){
		add_settings_field(
			'standard-admin-block',  // Unique ID used to identify the field
			"BLOCK site admins from logging their sites",  // The label to the left of the option.
			array(
				$this,
				'settings_input_checkbox'
			),   // The name of the function responsible for rendering the option interface
			self::page_network_slug,                         // The page on which this option will be displayed
			self::section_admin,         // The name of the section to which this field belongs
			array(   // The array of arguments to pass to the callback. These 4 are referenced in setting_input_checkbox.
			         'id'      => 'standard-admin-block', // copy/paste id here
			         'label'   => "Prevent standard site admins from logging in",
			         'section' => self::page_network_slug,
			         'value'   => esc_attr(get_option(self::section_admin)['standard-admin-block']),
			)
		);
		register_setting(
			self::option_group_name,
			self::section_admin
		//array( $this, 'sanitize' ) // sanitize function
		);
    }

	/**
	 * Grabs the database value for the $settings_id option. The value is stored in a serialized array in the database.
	 * It returns the value after sanitizing it.
	 *
	 * @param $setting_object
	 *
	 * @return string|void
	 */
	public function get_database_settings_value( $setting_object ) {
		$data = get_option( $this->get_proper_section($setting_object) );

		return esc_attr( $data[ $this->unique_setting_id($setting_object) ] );
	}

	/**
	 * Creates the HTML code that is printed for each setting input
	 *
	 * @param $args
	 */
	public function settings_input_checkbox( $args ) {
		// Note the ID and the name attribute of the element should match that of the ID in the call to add_settings_field.
		// Because we only call register_setting once, all the options are stored in an array in the database. So we
		// have to name our inputs with the name of an array. ex <input type="text" id=option_key name="option_group_name[option_key]" />.
		// WordPress will automatically serialize the inputs that are in this array form and store it under
		// the option_group_name field. Then get_option will automatically unserialize and grab the value already set and pass it in via the $args as the 'value' parameter.
		if ($args[ 'value' ]) {
			$checked = 'checked="checked"';
		} else {
			$checked = '';
		}

		$html = '';

		// create a hidden variable with the same name and no value. if the box is unchecked, the hidden value will be POSTed.
		// If the value is checked, only the checkbox will be sent.
		// This way, we don't have to uncheck everything server-side and then re-check the POSTed values.
		// This is particularly useful to prevent preferences from being deleted if a post type is removed from a theme's code.
		// If we just unchecked everything, old post types would lose their preferences; if they are later reactivated, the preference
		// would be gone. This way, the preference persists.
		$html .= '<input type="hidden"   id="' . $args[ 'id' ] . '" name="' . $args[ 'section' ] . '[' . $args[ 'id' ] . ']" value=""/>';
		$html .= '<input type="checkbox" id="' . $args[ 'id' ] . '" name="' . $args[ 'section' ] . '[' . $args[ 'id' ] . ']" value="' . ( $args[ 'id' ] ) . '" ' . $checked . '/>';

		// Here, we will take the first argument of the array and add it to a label next to the input
		$html .= '<label for="' . $args[ 'id' ] . '"> ' . $args[ 'label' ] . '</label>';
		echo $html;
	}

}
new admin_only_login();
