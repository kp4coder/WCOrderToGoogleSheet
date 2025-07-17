<?php 

if( !class_exists ( 'WCGS_Order' ) ) {
    class WCGS_Order {

        function __construct(){

            add_action( 'init', array( $this, 'authenticate_and_get_token' ), 1, 1 );

            // for get access token every 24 hours
            add_action( 'wcgs_refresh_token', array( $this, 'wcgs_refresh_token_func' ) );

            add_action( 'woocommerce_new_order', array( $this, 'new_order_custom_email_notification'), 10, 2 );

            // for order update woocommerce_process_shop_order_meta action

        }

        function add_details_to_credentials() {
            $wc_spreadsheet_client_id = get_option('wc_spreadsheet_client_id'); 
            $wc_spreadsheet_project_id = get_option('wc_spreadsheet_project_id'); 
            $wc_spreadsheet_client_secret = get_option('wc_spreadsheet_client_secret'); 

            $file_content = array(
                    "web" => array(
                        "client_id" => $wc_spreadsheet_client_id,
                        "project_id" => $wc_spreadsheet_project_id,
                        "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
                        "token_uri" => "https://oauth2.googleapis.com/token",
                        "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
                        "client_secret" => $wc_spreadsheet_client_secret,
                        "redirect_uris" => array( 
                            get_site_url() . "/?wcgs=google_auth",
                        )
                    )
                );

            update_option( 'wc_spreadsheet_credentials_json', $file_content );
        }

        function authenticate_and_get_token() {
            if( isset($_REQUEST['wcgs']) && $_REQUEST['wcgs'] == 'google_auth' ) {

                if( isset($_REQUEST['close']) && $_REQUEST['close'] == 'no' ) {
                    if( file_exists(WCGS_PLUGIN_DIR . 'token.json') ) {
                        unlink(WCGS_PLUGIN_DIR . 'token.json');
                    }
                    $this->add_details_to_credentials();
                    $client = $this->getClient();
                } else {
                    $client = $this->getClient();
                    ?>
                    <script>
                        window.close();
                    </script>    
                    <?php 
                }
                die;
            }
        }

        function getClient() {
            require WCGS_GOOGLE_API . 'SpreadsheetSnippets.php';
            require WCGS_GOOGLE_API . '/vendor/autoload.php';

            $credentials_json = get_option( 'wc_spreadsheet_credentials_json', true );
            $token_json = get_option( 'wc_spreadsheet_token_json', true );

            $client = new Google_Client();
            $client->setApplicationName('Google Sheets API PHP Quickstart');
            $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
            $client->setAuthConfig($credentials_json);
            $client->setAccessType('offline');
            $client->setPrompt('select_account consent');

            if( isset($_REQUEST['code']) && !empty($_REQUEST['code']) ) {
                $accessToken = $client->fetchAccessTokenWithAuthCode($_REQUEST['code']);
                $client->setAccessToken($accessToken);
                
                $file_content = $client->getAccessToken();
                update_option( 'wc_spreadsheet_token_json', $file_content );
            }

            // Load previously authorized token from a file, if it exists.
            if( is_array($token_json) && !empty($token_json) ) {
                $client->setAccessToken($token_json);
            }

            // If there is no previous token or it's expired.
            if ($client->isAccessTokenExpired()) {
                if ($client->getRefreshToken()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());

                    $file_content = $client->getAccessToken();
                    update_option( 'wc_spreadsheet_token_json', $file_content );
                } else {
                    $authUrl = $client->createAuthUrl();
                    header('Location: ' . $authUrl);
                }
            } else if( isset($_REQUEST['wcgs']) ) {
                ?><script> window.close(); </script> <?php 
            }
            return $client;
        }

        function wcgs_refresh_token_func() {
            $credentials_json = get_option( 'wc_spreadsheet_credentials_json', true );
            if( is_array($credentials_json) && !empty($credentials_json) ) {
                $this->getClient();
            }
        }

        function new_order_custom_email_notification( $order_id, $order ) {
            if ( ! $order_id ) return; 

            $dataArr = array();
            // $order = wc_get_order( $order_id ); // WC functions https://www.codegrepper.com/code-examples/php/how+to+get+all+order+details+in+woocommerce
            
            $billing_fullname = $order->get_formatted_billing_full_name();
            $billing_address = $order->get_formatted_billing_address();
            $billing_address = str_replace( '<br/>', ', ', $billing_address );
            $billing_address = str_replace( $billing_fullname.', ', '', $billing_address );

            $shipping_fullname = $order->get_formatted_shipping_full_name();
            $shipping_address = $order->get_formatted_shipping_address();
            $shipping_address = str_replace( '<br/>', ', ', $shipping_address );
            $shipping_address = str_replace( $shipping_fullname.', ', '', $shipping_address );

            // Date, Name, Last Name, Phone, Email, Country, City, Street, Postal Code, Purchase Note

            $order_info = array(
                'order_date'        => $order->get_date_created()->date('Y-m-d H:i:s'),
                'billing_first'     => $order->get_billing_first_name(),
                'billing_last'      => $order->get_billing_last_name(),
                'billing_phone'     => $order->get_billing_phone(),
                'billing_email'     => $order->get_billing_email(),
                'billing_country'   => WC()->countries->countries[ $order->get_billing_country() ],
                'billing_city'      => $order->get_billing_city(),
                'billing_address1'  => $order->get_billing_address_1(),
                'billing_address2'   => $order->get_billing_address_2(),
                'billing_postcode'  => $order->get_billing_postcode(),
                'billing_company'    => $order->get_billing_company(),


                // 'order_id'       => $order->get_id(), 
                // 'order_status'   => $order->get_status(),
                // 'order_total'    =>  strip_tags( html_entity_decode( $order->get_formatted_order_total() ) ), 
                // 'payment_method' => $order->get_payment_method_title(),

                // 'customer_id'        => $order->get_customer_id(),
                // 'billing_fullname'   => $billing_fullname,
                // 'billing_address'    => $billing_address,
                // 'shipping_fullname'  => $shipping_fullname,
                // 'shipping_address'   => $shipping_address,

                // 'billing_state'      => $order->get_billing_state(),
                // 'shipping_first'     => $order->get_shipping_first_name(),
                // 'shipping_last'      => $order->get_shipping_last_name(),
                // 'shipping_company'   => $order->get_shipping_company(),
                // 'shipping_address1'  => $order->get_shipping_address_1(),
                // 'shipping_address2'  => $order->get_shipping_address_2(),
                // 'shipping_city'      => $order->get_shipping_city(),
                // 'shipping_state'     => $order->get_shipping_state(),
                // 'shipping_postcode'  => $order->get_shipping_postcode(),
                // 'shipping_country'   => $order->get_shipping_country(),
            );

            $purchase_notes = array();
            $attributes = '';
            foreach ( $order->get_items() as $item_id => $item ) {
                $product_id = $item->get_product_id();
                $purchase_notes[] = get_post_meta( $product_id, '_purchase_note', true);

                $product_id   = $item->get_product_id(); //Get the product ID
                $quantity     = $item->get_quantity(); //Get the product QTY
                $product_name = $item->get_name(); //Get the product NAME

                // Get an instance of the WC_Product object (can be a product variation  too)
                $product      = $item->get_product();

                // Get the product description (works for product variation too)
                $description  = $product->get_description();

                // Only for product variation
                if( $product->is_type('variation') ){
                     // Get the variation attributes
                    $variation_attributes = $product->get_variation_attributes();
                    // Loop through each selected attributes
                    foreach($variation_attributes as $attribute_taxonomy => $term_slug ){
                        $attribute_value = '';
                        // Get product attribute name or taxonomy
                        $taxonomy = str_replace('attribute_', '', $attribute_taxonomy );
                        // The label name from the product attribute
                        $attribute_name = wc_attribute_label( $taxonomy, $product );
                        $attribute_name = str_replace('pa_', '', $attribute_name );
                        // The term name (or value) from this attribute
                        if( taxonomy_exists($taxonomy) ) {
                            $attribute_value = get_term_by( 'slug', $term_slug, $taxonomy )->name;
                        } else {
                            $attribute_value = $term_slug; // For custom product attributes
                        }

                        if( !empty($attributes) ) {
                            $attributes.= ',  ' . $attribute_name . ':' . $attribute_value;
                        } else {
                            $attributes.= $attribute_name . ':' . $attribute_value;
                        }
                    }
                }
            }

            $order_info[] = $attributes;
            $order_info[] = implode(' , ', $purchase_notes);

            foreach ( $order->get_items() as $item_id => $item ) {
                $order_info[] = $item->get_name();
                $order_info[] = $item->get_product_id();
            }

            $dataArr[] = array_values($order_info);

            // send order details to google sheet
            $spreadsheetId = get_option('wc_spreadsheet_title'); 
            $client = $this->getClient();
            $service = new Google_Service_Sheets($client);
            $SpreadsheetSnippets = New SpreadsheetSnippets($service);

            // fetch all excel sheet
            $sheetInfo = $service->spreadsheets->get($spreadsheetId);
            $allsheet_info = $sheetInfo['sheets'];
            $all_sheetinfo = array_column($allsheet_info, 'properties');
            $sheetName = $all_sheetinfo[0]->title; // fetch first sheet name to add data

            // $dataArr[] = array_values($order_info);
            $data = $SpreadsheetSnippets->appendValues( $spreadsheetId, $sheetName, 'USER_ENTERED', $dataArr );

            // print_r($data);
            // die;
        }
    }

    global $wcgs_order;
    $wcgs_order = new WCGS_Order();
}