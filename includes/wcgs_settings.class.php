<?php
if( !class_exists ( 'WCGS_Settings' ) ) {

    class WCGS_Settings {

        function __construct(){

            add_action( "wcgs_save_settings", array( $this, "wcgs_save_settings_func" ), 10 , 1 );

        } 
         
        function wcgs_display_settings( ) {
            if( file_exists( WCGS_INCLUDES_DIR . "wcgs_settings.view.php" ) ) {
                include_once( WCGS_INCLUDES_DIR . "wcgs_settings.view.php" );
            }
        }

        function wcgs_default_setting_option() {
            return array(
                'wc_spreadsheet_section_title' => '',
                'wc_spreadsheet_client_id' => '',
                'wc_spreadsheet_project_id' => '',
                'wc_spreadsheet_client_secret' => '',
                'wc_spreadsheet_title' => ''
            );
        }

        function wcgs_save_settings_func( $params = array() ) {
            if( isset( $params['wcgs_setting'] ) && $params['wcgs_setting'] != '') {
                $wcgs_setting = $params['wcgs_setting'];
                unset( $params['wcgs_setting'] );
                unset( $params['wcgs_setting_save'] );
                
                update_option('wcgs_setting', $params);

                $_SESSION['wcgs_msg_status'] = true;
                $_SESSION['wcgs_msg'] = 'Settings updated successfully.';
            }
        }

        function wcgs_get_settings_func( ) {
            $wcgs_default_general_option = $this->wcgs_default_setting_option();
            $wcgs_setting_option = get_option( 'wcgs_setting' );
            return shortcode_atts( $wcgs_default_general_option, $wcgs_setting_option );
        }
       
    }

    global $wcgs_settings;
    $wcgs_settings = new WCGS_Settings();

}

if( !class_exists ( 'WCGS_Settings_SpreadSheet' ) ) {
    class WCGS_Settings_SpreadSheet {

        function __construct() {
            // Three hooks for create new tab.
            // add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
            // add_action( 'woocommerce_settings_tabs_spreadsheet', array( $this, 'settings_tab' ) );
            // add_action( 'woocommerce_update_options_spreadsheet', array( $this, 'update_settings' ) );

            // two hooks for add section in already exists tab this will add section in advanced.
            add_filter( 'woocommerce_get_sections_advanced', array( $this, 'add_settings_tab' ) );
            add_filter( 'woocommerce_get_settings_advanced', array( $this, 'get_settings' ), 10, 2 );
        }
        
        
        function add_settings_tab( $settings_tabs ) {
            $settings_tabs['spreadsheet'] = __( 'SpreadSheet', WCGS_txt_domain );
            return $settings_tabs;
        }

        function settings_tab() {
            woocommerce_admin_fields( self::get_settings() );
        }

        function update_settings() {
            woocommerce_update_options( self::get_settings() );
        }

        function get_settings( $settings, $current_section ) {
            if ( $current_section == 'spreadsheet' ) {
                $settings = array(
                    'section_title' => array(
                        'name'     => __( 'SpreadSheet', WCGS_txt_domain ),
                        'type'     => 'title',
                        'desc'     => '',
                        'id'       => 'wc_spreadsheet_section_title'
                    ),
                    'client_id' => array(
                        'name' => __( 'Client ID', WCGS_txt_domain ),
                        'type' => 'text',
                        'desc' => '',
                        'id'   => 'wc_spreadsheet_client_id'
                    ),
                    'project_id' => array(
                        'name' => __( 'Project ID', WCGS_txt_domain ),
                        'type' => 'text',
                        'desc' => '',
                        'id'   => 'wc_spreadsheet_project_id'
                    ),
                    'wc_spreadsheet_client_secret' => array(
                        'name' => __( 'Client Secret', WCGS_txt_domain ),
                        'type' => 'text',
                        'desc' => __( 'Add redirect URIs -', WCGS_txt_domain ) . get_site_url() . '/?wcgs=google_auth 
                                <br/><br/><button id="authenticate" data-url="'.get_site_url() . '/?wcgs=google_auth&close=no">'.__( 'Google Authenticate', WCGS_txt_domain ).'</button>',
                        'id'   => 'wc_spreadsheet_client_secret'
                    ),
                    'title' => array(
                        'name' => __( 'SpreadSheet ID', WCGS_txt_domain ),
                        'type' => 'text',
                        'desc' => __( 'You will find spreadsheet ID in your spreadsheet url.<br/>
                        Like : https://docs.google.com/spreadsheets/d/<b>1LswLxasIekcSuf12qy8FVh3sdfWhSj5SDFWER4_vM</b>/edit?ts=603d2d18<br/>
                        On above URL <b>1LswLxasIekcSuf12qy8FVh3sdfWhSj5SDFWER4_vM</b> is Spreadsheet ID.', WCGS_txt_domain ),
                        'id'   => 'wc_spreadsheet_title'
                    ),
                    'section_end' => array(
                         'type' => 'sectionend',
                         'id' => 'wc_spreadsheet_section_end'
                    )
                );

                return apply_filters( 'wc_spreadsheet_settings', $settings );
            } else {
                return $settings;
            }
        }

    }

    $WCGS_Settings_SpreadSheet = new WCGS_Settings_SpreadSheet();
}

?>