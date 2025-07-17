<?php
global $wcgs, $wcgs_settings;
if( isset( $_REQUEST['wcgs_setting_save'] ) && isset( $_REQUEST['wcgs_setting'] ) && $_REQUEST['wcgs_setting'] != '' ) {
    do_action( 'wcgs_save_settings', $_POST );
}

echo '<div class="wrap wcgs_content">';

if( isset($_SESSION['wcgs_msg_status']) && $_SESSION['wcgs_msg_status'] ) { 
    echo '<div id="message" class="updated notice notice-success is-dismissible">';
    echo '<p>';
    echo (isset($_SESSION['wcgs_msg']) && $_SESSION['wcgs_msg']!='') ? $_SESSION['wcgs_msg'] : 'Something went wrong.';
    echo '</p>';
    echo '<button type="button" class="notice-dismiss"><span class="screen-reader-text">'.__('Dismiss this notice.',WCGS_txt_domain).'</span></button>';
    echo '</div>';
	unset($_SESSION['wcgs_msg_status']);
	unset($_SESSION['wcgs_msg']);
} 

echo '<form name="wcgs_settings" id="wcgs_settings" method="post" >';
    
    global $wcgs, $wcgs_settings;

    $general_option = $wcgs_settings->wcgs_get_settings_func( );
    extract($general_option);
    ?>
    <div class="cmrc-table">

        <?php /********************* General Options Section Start ********************/ ?>
        <div class="setting-general" >
            <h2><?php _e('General options', WCGS_txt_domain); ?></h2>
            <table class="form-table wcgs-setting-form">
                <tbody>
                    <tr>
                        <th><label for="wc_spreadsheet_client_id"><?php _e('Client ID', WCGS_txt_domain); ?></label></th>
                        <td>
                            <input type="text" name="wc_spreadsheet_client_id" id="wc_spreadsheet_client_id" class="" value="<?php echo $wc_spreadsheet_client_id; ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wc_spreadsheet_project_id"><?php _e('Project ID', WCGS_txt_domain); ?></label></th>
                        <td>
                            <input type="text" name="wc_spreadsheet_project_id" id="wc_spreadsheet_project_id" class="" value="<?php echo $wc_spreadsheet_project_id; ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wc_spreadsheet_client_secret"><?php _e('Client Secret', WCGS_txt_domain); ?></label></th>
                        <td>
                            <input type="text" name="wc_spreadsheet_client_secret" id="wc_spreadsheet_client_secret" class="" value="<?php echo $wc_spreadsheet_client_secret; ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wc_spreadsheet_title"><?php _e('SpreadSheet ID', WCGS_txt_domain); ?></label></th>
                        <td>
                            <input type="text" name="wc_spreadsheet_title" id="wc_spreadsheet_title" class="" value="<?php echo $wc_spreadsheet_title; ?>" />
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
        <?php /********************* General Options Section end ********************/ ?>

    </div>
    <?php
    echo '<p class="submit">';
    echo '<input type="hidden" name="wcgs_setting" id="wcgs_setting" value="wcgs_setting" />';
    echo '<input name="wcgs_setting_save" class="button-primary wcgs_setting_save" type="submit" value="Save changes" />';
    echo '</p>';

echo '</form>';
echo '</div>';