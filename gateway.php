<?php

class WC_Gateway_Woocommerce_CBgateway extends WC_Payment_Gateway {
    /**
     * Main Construct() for the plugin
     * Setup Commercial Bank Gateway's id, description and other values
     */
    function __construct() {
        // The global ID for this Payment method
        $this->id = "wc_combank_gateway";
        // The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
        $this->method_title = __( "Commercial Bank Payment Gateway", 'wc_combank_gateway' );
        // The description for this Payment Gateway, shown on the actual Payment options page on the backend
        $this->method_description = __( "Commercial Bank Payment Gateway Plug-in for WooCommerce", 'wc_combank_gateway' );
        // The title to be used for the vertical tabs that can be ordered top to bottom
        $this->title = __( "Commercial Bank Payment Gateway", 'wc_combank_gateway' );
        // If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
        $this->icon = null;
        // Bool. Can be set to true if you want payment fields to show on the checkout
        // if doing a direct integration, which we are doing in this case
        //$this->has_fields = true;
        $this->has_fields = false;
        // Supports the default credit card form
        //$this->supports = array( 'default_credit_card_form' );
        // This basically defines your settings which are then loaded with init_settings()
        $this->init_form_fields();
        // After init_settings() is called, you can get the settings and load them into variables, e.g:
        // $this->title = $this->get_option( 'title' );
        $this->init_settings();
        // Turn these settings into variables we can use
        foreach ( $this->settings as $setting_key => $value ) {
            $this->$setting_key = $value;
        }
        // Lets check for SSL
        add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );
        // Save settings
        if ( is_admin() ) {
            // Versions over 2.0
            // Save our administration options. Since we are not going to be doing anything special
            // we have not defined 'process_admin_options' in this class so the method in the parent
            // class will be used instead
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        }
    } // End __construct()
    // Build the administration fields for this specific Gateway
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'		=> __( 'Enable / Disable', 'wc_combank_gateway' ),
                'label'		=> __( 'Enable this payment gateway', 'wc_combank_gateway' ),
                'type'		=> 'checkbox',
                'default'	=> 'no',
            ),
            'title' => array(
                'title'		=> __( 'Title', 'wc_combank_gateway' ),
                'type'		=> 'text',
                'desc_tip'	=> __( 'Payment title the customer will see during the checkout process', 'wc_combank_gateway' ),
                'default'	=> __( 'Commercial Bank', 'wc_combank_gateway' ),
            ),
            'description' => array(
                'title'		=> __( 'Description', 'wc_combank_gateway' ),
                'type'		=> 'textarea',
                'desc_tip'	=> __( 'Payment description the customer will see during the checkout process', 'wc_combank_gateway' ),
                'default'	=> __( 'Pay securely using your credit card with Commercial Bank.', 'wc_combank_gateway' ),
                'css'		=> 'max-width:400px;'
            ),
            'virtualPaymentClientURL' => array(
                'title' 	=> __('Virtual Payment Client URL:', 'wc_combank_gateway'),
                'type'		=> 'text',
                'desc_tip' => __('Virtual payment client URL given by bank', 'wc_combank_gateway'),
                'default' 	=> __('https://migs.mastercard.com.au/vpcpay', 'wc_combank_gateway')
            ),
            'vpcVersion' => array(
                'title' 	=> __('VPC Version:', 'wc_combank_gateway'),
                'type'		=> 'text',
                'desc_tip' => __('Virtual payment client version given by bank', 'wc_combank_gateway'),
                'default' 	=> __('1', 'wc_combank_gateway')
            ),
            'commandType' => array(
                'title' 	=> __('Command Type:', 'wc_combank_gateway'),
                'type'		=> 'text',
                'desc_tip' => __('Command type given by bank', 'wc_combank_gateway'),
                'default' 	=> __('pay', 'wc_combank_gateway')
            ),
            'merchantAccessCode' => array(
                'title' 	=> __('Merchant Access Code:', 'wc_combank_gateway'),
                'type'		=> 'text',
                'desc_tip' => __('Merchant access code given by bank', 'wc_combank_gateway'),
                'default' 	=> __('', 'wc_combank_gateway')
            ),
            /*
            'merchantTransactionReference' => array(
                'title' 	=> __('Merchant Transaction Reference:', 'wc_combank_gateway'),
                'type'		=> 'text',
                'desc_tip' => __('Merchant transaction reference given by bank', 'wc_combank_gateway'),
                'default' 	=> __('', 'wc_combank_gateway')
            ),
            */
            'merchantID' => array(
                'title' 	=> __('Merchant ID:', 'wc_combank_gateway'),
                'type'		=> 'text',
                'desc_tip' => __('Unique Merchant ID given by bank', 'wc_combank_gateway'),
                'default' 	=> __('', 'wc_combank_gateway')
            ),
            'receiptReturnURL' => array(
                'title' 	=> __('Receipt Return URL:', 'wc_combank_gateway'),
                'type'		=> 'text',
                'desc_tip' => __('Return URL after transaction', 'wc_combank_gateway'),
                'default' 	=> __('', 'wc_combank_gateway')
            ),
            'displayLanguage' => array(
                'title' 	=> __('Display Language', 'wc_combank_gateway'),
                'type'		=> 'text',
                'desc_tip' => __('Payment server display language locale', 'wc_combank_gateway'),
                'default' 	=> __('en_US', 'wc_combank_gateway')
            ),
            'currency' => array(
                'title' 	=> __('Currency', 'wc_combank_gateway'),
                'type'		=> 'text',
                'desc_tip' => __('Currency', 'wc_combank_gateway'),
                'default' 	=> __('USD', 'wc_combank_gateway')
            ),
            'secureHashSecret' => array(
                'title' 	=> __('Merchant’s Secure Hash Secret:', 'wc_combank_gateway'),
                'type'		=> 'text',
                'desc_tip' => __('Merchant’s secure hash secret given by bank', 'wc_combank_gateway'),
                'default' 	=> __('', 'wc_combank_gateway')
            )
        );
    }
    // Submit payment and handle response
    public function process_payment( $order_id ) {
        global $woocommerce;
        // Get this Order's information so that we know
        // who to charge and how much
        $customer_order = new WC_Order( $order_id );
        // Are we testing right now or is it a real transaction
        $environment = ( $this->environment == "yes" ) ? 'TRUE' : 'FALSE';
        // Decide which URL to post to
        $environment_url = ( "FALSE" == $environment ) ? $this->active_payment_url : $this->test_payment_url;
        if($customer_order->user_id){
            $inv_account = $customer_order->user_id;
        }else{
            $inv_account = "guest";
        }
		$totalamount = str_replace(".","",$customer_order->order_total);
        // This is where the fun stuff begins
        $payload = array(
            "fullname=" . $customer_order->billing_first_name . " " . $customer_order->billing_last_name,
            "telephone=" . $customer_order->billing_phone,
            "email=" . $customer_order->billing_email,
            "invoice[1][account]=" . $inv_account,
            "invoice[1][reference]=" . str_replace( "#", "", $customer_order->get_order_number() ),
            "invoice[1][amount]=" . $totalamount
        );
        // build url with & in middle
        $fw_value_array = implode('&', $payload);
        // combine with payment_url
        
        $fw_url = "https://migs.mastercard.com.au/vpcpay?" . $fw_value_array;
        // return array(
        //     'result'   => 'success',
        //     'redirect' => $fw_url,
        // );

        //blah blah code

        include('VPCPaymentConnection.php');
        $conn = new VPCPaymentConnection();
        // This is secret for encoding the SHA256 hash
        // This secret will vary from merchant to merchant

        $secureSecret = $this->secureHashSecret;
        // Set the Secure Hash Secret used by the VPC connection object
        $conn->setSecureSecret($secureSecret);
        $vpcURL = $this->virtualPaymentClientURL;
        $title = $this->title;

        //add all fields 
        $conn->addDigitalOrderField('vpc_AccessCode', $this->merchantAccessCode);
        $conn->addDigitalOrderField('vpc_Amount', $totalamount);
        $conn->addDigitalOrderField('vpc_Command', $this->commandType);
        $conn->addDigitalOrderField('vpc_Currency', $this->currency);
        $conn->addDigitalOrderField('vpc_Locale', $this->displayLanguage);
        $conn->addDigitalOrderField('vpc_MerchTxnRef', 'ps');
        $conn->addDigitalOrderField('vpc_Merchant', $this->merchantID);
        $conn->addDigitalOrderField('vpc_OrderInfo', $payload);
        $conn->addDigitalOrderField('vpc_ReturnURL', $this->receiptReturnURL);
        $conn->addDigitalOrderField('vpc_Version', $this->vpcVersion);
        // Obtain a one-way hash of the Digital Order data and add this to the Digital Order
        $secureHash = $conn->hashAllFields();
        $conn->addDigitalOrderField("vpc_SecureHash", $secureHash);
        $conn->addDigitalOrderField("vpc_SecureHashType", "SHA256");

        // Obtain the redirection URL and redirect the web browser
        $vpcURL = $conn->getDigitalOrder($vpcURL);
       // header("Location: ".$vpcURL);
           
        return array(
         'result'   => 'success',
        'redirect' => $vpcURL,
     );

    }
    // Validate fields
    public function validate_fields() {
        return true;
    }
    // Check if we are forcing SSL on checkout pages
    // Custom function not required by the Gateway
    public function do_ssl_check() {
        if( $this->enabled == "yes" ) {
            if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
                echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";
            }
        }
    }
}