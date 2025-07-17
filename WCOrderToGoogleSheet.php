<?php
/*
Plugin Name: Woocommerce Order To Google Sheet
Plugin URI: https://wordpress.org/plugins/
Description: Allows user to store woocommerce order to google sheet
Version: 1.0.0
Author: kp dev
Author URI: https://wordpress.org/plugins/
Domain Path: /languages
Text Domain: wcgs_text_domain
*/

// plugin definitions
define( 'WCGS_PLUGIN', '/WCOrderToGoogleSheet/');
 define("WCGS_PLUGIN_PATH", plugin_dir_path(__FILE__));

// directory define
define( 'WCGS_PLUGIN_DIR', WP_PLUGIN_DIR.WCGS_PLUGIN);
define( 'WCGS_GOOGLE_API', WCGS_PLUGIN_DIR.'google-api-php-client/' );
define( 'WCGS_INCLUDES_DIR', WCGS_PLUGIN_DIR.'includes/' );

define( 'WCGS_ASSETS_DIR', WCGS_PLUGIN_DIR.'assets/' );
define( 'WCGS_CSS_DIR', WCGS_ASSETS_DIR.'css/' );
define( 'WCGS_JS_DIR', WCGS_ASSETS_DIR.'js/' );
define( 'WCGS_IMAGES_DIR', WCGS_ASSETS_DIR.'images/' );

// URL define
define( 'WCGS_PLUGIN_URL', WP_PLUGIN_URL.WCGS_PLUGIN);

define( 'WCGS_ASSETS_URL', WCGS_PLUGIN_URL.'assets/');
define( 'WCGS_IMAGES_URL', WCGS_ASSETS_URL.'images/');
define( 'WCGS_CSS_URL', WCGS_ASSETS_URL.'css/');
define( 'WCGS_JS_URL', WCGS_ASSETS_URL.'js/');

// define text domain
define( 'WCGS_txt_domain', 'wcgs_text_domain' );

global $wcgs_version;
$wcgs_version = '1.1';

class WCOrderToGoogleSheet {

    var $wcgs_setting = '';

	function __construct() {
        global $wpdb;

        $this->wcgs_setting = 'wcgs_setting';

		register_activation_hook( __FILE__,  array( &$this, 'wcgs_install' ) );

        register_deactivation_hook( __FILE__, array( &$this, 'wcgs_deactivation' ) );

		add_action( 'admin_menu', array( $this, 'wcgs_add_menu' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'wcgs_enqueue_scripts' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'wcgs_front_enqueue_scripts' ) );

        add_action( 'plugins_loaded', array( $this, 'wcgs_load_textdomain' ) );
        
	}

    function wcgs_load_textdomain() {
        load_plugin_textdomain( WCGS_txt_domain, false, basename(dirname(__FILE__)) . '/languages' ); //Loawcgs plugin text domain for the translation
        do_action('WCGS_txt_domain');
    }

	static function wcgs_install() {

		global $wpdb, $wcgs, $wcgs_version;

        $charset_collate = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        if ( ! wp_next_scheduled( 'wcgs_refresh_token' ) ) {
        	wp_schedule_event( time(), 'daily', 'wcgs_refresh_token' );
        }

        update_option( "wcgs_plugin", true );
        update_option( "wcgs_version", $wcgs_version );
	}

    static function wcgs_deactivation() {
        // deactivation process here
    }

	function wcgs_get_sub_menu() {
		$wcgs_admin_menu = array(
			array(
				'name' => __('Setting', WCGS_txt_domain),
				'cap'  => 'manage_options',
				'slug' => $this->wcgs_setting,
			),
		);
		return $wcgs_admin_menu;
	}

	function wcgs_add_menu() {

		$wcgs_main_page_name = __('Data Scraper', WCGS_txt_domain);
		$wcgs_main_page_capa = 'manage_options';
		$wcgs_main_page_slug = $this->wcgs_setting; 

		$wcgs_get_sub_menu   = $this->wcgs_get_sub_menu();
		/* set capablity here.... Right now manage_options capability given to all page and sub pages. <span class="dashicons dashicons-money"></span>*/	 
		// add_menu_page($wcgs_main_page_name, $wcgs_main_page_name, $wcgs_main_page_capa, $wcgs_main_page_slug, array( &$this, 'wcgs_route' ), 'dashicons-star-half', 11 );

		foreach ($wcgs_get_sub_menu as $wcgs_menu_key => $wcgs_menu_value) {
			add_submenu_page(
				$wcgs_main_page_slug, 
				$wcgs_menu_value['name'], 
				$wcgs_menu_value['name'], 
				$wcgs_menu_value['cap'], 
				$wcgs_menu_value['slug'], 
				array( $this, 'wcgs_route') 
			);	
		}
	}

	function wcgs_is_activate(){
		if(get_option("wcgs_plugin")) {
			return true;
		} else {
			return false;
		}
	}

	function wcgs_admin_slugs() {
		$wcgs_pages_slug = array(
			$this->wcgs_setting,
		);
		return $wcgs_pages_slug;
	}

	function wcgs_is_page() {
		if( ( isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], $this->wcgs_admin_slugs() ) ) 
		 || ( isset( $_REQUEST['post_type'] ) && in_array( $_REQUEST['post_type'], $this->wcgs_admin_slugs() ) ) 
		 || ( in_array( get_post_type()	, $this->wcgs_admin_slugs() ) )
		) {
			return true;
		} else {
			return false;
		}
	} 

    function wcgs_admin_msg( $key ) { 
        $admin_msg = array(
            "no_tax" => __("No matching tax rates found.", WCGS_txt_domain)
        );

        if( $key == 'script' ){
            $script = '<script type="text/javascript">';
            $script.= 'var __wcgs_msg = '.json_encode($admin_msg);
            $script.= '</script>';
            return $script;
        } else {
            return isset($admin_msg[$key]) ? $admin_msg[$key] : false;
        }
    }

	function wcgs_enqueue_scripts( $hook_suffix ) {
		global $wcgs_version;
		// Only in single product pages and a specific url (using GET method) 
        if( isset( $_GET['page'] ) && $_GET['page'] == "wc-settings" && isset( $_GET['tab'] ) && $_GET['tab'] = "spreadsheet" ) :
            wp_register_script( 'wcgs_script', WCGS_JS_URL.'wcgs_admin_js.js?rand='.rand(1,9), 'jQuery', '1.0', true );
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'wcgs_script' );
        endif;
        
		/* must register style and than enqueue */
		if( $this->wcgs_is_page() ) {

			/*********** register and enqueue scripts ***************/
            wp_register_style( 'wcgs_admin_style_css', WCGS_CSS_URL.'wcgs_admin_style.css', false, $wcgs_version );
            wp_enqueue_style( 'wcgs_admin_style_css' );


			/*********** register and enqueue scripts ***************/
            echo $this->wcgs_admin_msg( 'script' );
            wp_register_script( 'wcgs_admin_js', WCGS_JS_URL.'wcgs_admin_js.js', 'jQuery', $wcgs_version, true );
			wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'wcgs_admin_js' );
            
		}
    }

    function ncm_is_front_page() {
        return false;
    }

    function wcgs_front_enqueue_scripts() {
        global $wcgs_version;
        if( $this->ncm_is_front_page() ) {

            /*********** register and enqueue styles ***************/
            wp_register_style( 'wcgs_front_css',  WCGS_CSS_URL.'wcgs_front.css', false, $wcgs_version );
            wp_enqueue_style( 'wcgs_front_css' );

            /*********** register and enqueue scripts ***************/
            echo "<script> var ajaxurl = '".admin_url( 'admin-ajax.php' )."'; </script>";
            wp_register_script( 'wcgs_front_js', WCGS_JS_URL.'wcgs_front.js', 'jQuery', $wcgs_version, true );
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( 'wcgs_front_js' );
        }
        
	}

	function wcgs_route() {
		global $wcgs_settings;
		if( isset($_REQUEST['page']) && $_REQUEST['page'] != '' ){
			switch ( $_REQUEST['page'] ) {
				case $this->wcgs_setting:
					$wcgs_settings->wcgs_display_settings();
					break;
			}
		}
	}

    function wcgs_write_log( $content = '', $file_name = 'wcgs_log.txt' ) {
        $file = __DIR__ . '/log/' . $file_name;    
        $file_content = "=============== Write At => " . date( "y-m-d H:i:s" ) . " =============== \r\n";
        $file_content .= json_encode( $content ) . "\r\n\r\n";
        file_put_contents( $file, $file_content, FILE_APPEND | LOCK_EX );
    }
    
}


// begin!
global $wcgs;
$wcgs = new WCOrderToGoogleSheet();

if( $wcgs->wcgs_is_activate() && file_exists( WCGS_INCLUDES_DIR . "wcgs_settings.class.php" ) ) {
    include_once( WCGS_INCLUDES_DIR . "wcgs_settings.class.php" );
}

if( $wcgs->wcgs_is_activate() && file_exists( WCGS_INCLUDES_DIR . "wcgs_order.class.php" ) ) {
    include_once( WCGS_INCLUDES_DIR . "wcgs_order.class.php" );
}
