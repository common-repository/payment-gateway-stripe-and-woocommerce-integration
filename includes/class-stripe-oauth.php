<?php



if (!defined('ABSPATH')) {
    exit;
}  

/**
 * EH_Stripe_Oauth class.
 *
 * To received the tokens send by Stripe and save to the databse
 */
#[\AllowDynamicProperties]
class EH_Stripe_Oauth {
    
    
    public function __construct() { 
        //handle the redirection after installing Stripe app
        add_action( 'woocommerce_api_wt_stripe_oauth_update', array( $this, 'wt_stripe_oauth_update' ) );
        
    }


    /**
     * 
     * Function to retrieve tokens send from Stripe to WebToffee server and save tokens to db
     * @since 4.0.0
     */ 
    public function wt_stripe_oauth_update()
    {  
        $raw_post = file_get_contents( 'php://input' );
        if (!empty($raw_post)) {
            $decoded  = json_decode($raw_post, true);

            if(isset($decoded['access_token'])){
                $access_token = sanitize_text_field($decoded['access_token']);
                $refresh_token = (isset($decoded['refresh_token']) ? sanitize_text_field($decoded['refresh_token'])  : '');
                $account_id = (isset($decoded['account_id']) ? sanitize_text_field($decoded['account_id'])  : '');
                $stripe_publishable_key = (isset($decoded['stripe_publishable_key']) ? sanitize_text_field($decoded['stripe_publishable_key'])  : '');

                $arr_oauth_tokens = array(
                    'access_token' => $access_token,
                    'refresh_token' => $refresh_token,
                    'account_id' => $account_id,
                    'stripe_publishable_key' => $stripe_publishable_key,
                );
                 wc_get_logger()->log('debug', print_r($arr_oauth_tokens, true), array( 'source' => 'wt_stripe_oauth' )  );

                $stripe_settings = get_option("woocommerce_eh_stripe_pay_settings");
                $mode = (isset($stripe_settings["eh_stripe_mode"]) ?  $stripe_settings["eh_stripe_mode"] : 'live');
                if('test' === $mode){
                    update_option("wt_stripe_account_id_test", $account_id);
                    update_option("wt_stripe_access_token_test", $access_token);
                    update_option("wt_stripe_refresh_token_test", $refresh_token);
                    update_option("wt_stripe_test_publishable_key", $stripe_publishable_key);
                    set_transient( 'wtst_oauth_expriy_test', 'wtst_oauth_expriy', (MINUTE_IN_SECONDS*50) );
                    update_option("wt_stripe_oauth_connected_test", "yes");

                    $stripe_settings['eh_stripe_mode'] = 'test';
                    update_option("woocommerce_eh_stripe_pay_settings", $stripe_settings);

                }
                else{
                    update_option("wt_stripe_account_id_live", $account_id);
                    update_option("wt_stripe_access_token_live", $access_token);
                    update_option("wt_stripe_refresh_token_live", $refresh_token);
                    update_option("wt_stripe_live_publishable_key", $stripe_publishable_key);
                    set_transient( 'wtst_oauth_expriy_live', 'wtst_oauth_expriy', (MINUTE_IN_SECONDS*50) );
                    update_option("wt_stripe_oauth_connected_live", "yes");

                    $stripe_settings['eh_stripe_mode'] = 'live';
                    update_option("woocommerce_eh_stripe_pay_settings", $stripe_settings);

                }

                $oauth_status = 'success';
            }


        }
        else{
            wc_get_logger()->log('debug', 'empty response' , array( 'source' => 'wt_stripe_oauth' ) );
            $oauth_status = 'failed';
        }

        //Redirect back to settings page
        $setting_link = admin_url(sprintf('admin.php?page=wt_stripe_menu&oauth_status=%s', $oauth_status));

        $response = array(
            'oauth_status' => $oauth_status,
            'redirect_url' => $setting_link,
        );
        wc_get_logger()->log('debug', print_r($response, true), array( 'source' => 'wt_stripe_oauth' )  );
       
        echo json_encode( $response);
        exit;
    }
  
}

new EH_Stripe_Oauth();

