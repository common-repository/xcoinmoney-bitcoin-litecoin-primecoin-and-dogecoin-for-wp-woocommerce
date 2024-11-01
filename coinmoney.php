<?php
/*
Plugin Name: xCoinMoney bitcoin, litecoin, primecoin and dogecoin for WP WooCommerce
Plugin URI: http://wordpress.org/plugins/xcoinmoney-bitcoin-litecoin-primecoin-and-dogecoin-for-wp-woocommerce/
Description: Bitcoin, Litecoin, Primecoin and Dogecoin Payments with WP WooCommerce plugin for WordPress.
Version: 1.00
Author: xcoinmoney
*/

add_action('plugins_loaded', 'init_WC_Coinmoney_Payment_Gateway', 0);


function init_WC_Coinmoney_Payment_Gateway() {

  if(!class_exists('WC_Payment_Gateway')) return;

  class WC_Coinmoney_Payment_Gateway extends WC_Payment_Gateway{

    public function __construct(){

      $this->id = 'coinmoney';
      $this->has_fields = false;

      // Load the form fields.
      $this->init_form_fields();

      // Load the settings.
      $this->init_settings();

      // Define user set variables
      $this->title = $this->settings['title'];
      $this->description = $this->settings['description'];
      $this->icon = apply_filters('woocommerce_coinmoney_icon', $this->settings['coinmoney_icon_url']);

      // Actions
      add_action('woocommerce_update_options_payment_gateways_'.$this->id, array(&$this, 'process_admin_options'));
      add_action('woocommerce_thankyou_cheque', array(&$this, 'thankyou_page'));
      add_action('woocommerce_receipt_'. $this->id, array( $this, 'receipt_page' ) );
      add_action('woocommerce_email_before_order_table', array(&$this, 'email_instructions'), 10, 2);
    }

    function init_form_fields()
    {
      $this->form_fields = array(
        'enabled' => array(
          'title' => __( 'Enable/Disable', 'woothemes' ),
          'type' => 'checkbox',
          'label' => __( 'Enable xCoinMoney Payment', 'woothemes' ),
          'default' => 'yes'
        ),
        'title' => array(
          'title' => __( 'Title', 'woothemes' ),
          'type' => 'text',
          'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ),
          'default' => __( 'xCoinMoney Payment', 'woothemes' )
        ),
        'callback' => array(
          'title' => __('Your callback URL', 'woothemes'),
          'type' => 'text',
          'disabled' => TRUE,
          'description' => __('Your callback URL'),
          'default' => get_option('siteurl') . '/?page_id=' . get_option('woocommerce_checkout_page_id')
        ),
        'coinmoney_url' => array(
          'title' => __('xCoinMoney url', 'woothemes'),
          'type' => 'text',
          'description' => __('Enter xCoinmoney url'),
          'default' => 'https://www.xcoinmoney.com/api'
        ),
        'merchant_id' => array(
          'title' => __('Merchant Id', 'woothemes'),
          'type' => 'text',
          'description' => __('Enter the Merchant Id you created at xCoinmoney.com'),
        ),
        'api_key' => array(
          'title' => __('api key', 'woothemes'),
          'type' => 'text',
          'description' => __('Enter the apiKey you created at xCoinmoney.com'),
        ),
        'dxx_account' => array(
          'title' => __( 'DXX account', 'woothemes' ),
          'type' => 'checkbox',
          'label' => __( 'Enable xCoinMoney DXX account', 'woothemes' ),
          'default' => 'yes'
        ),
        'btc_account' => array(
          'title' => __( 'BTC account', 'woothemes' ),
          'type' => 'checkbox',
          'label' => __( 'Enable xCoinMoney BTC account', 'woothemes' ),
          'default' => 'yes'
        ),
        'ltc_account' => array(
          'title' => __( 'LTC account', 'woothemes' ),
          'type' => 'checkbox',
          'label' => __( 'Enable xCoinMoney LTC account', 'woothemes' ),
          'default' => 'yes'
        ),
        'xpm_account' => array(
          'title' => __( 'XPM account', 'woothemes' ),
          'type' => 'checkbox',
          'label' => __( 'Enable xCoinMoney XPM account', 'woothemes' ),
          'default' => 'yes'
        ),
        'doge_account' => array(
          'title' => __( 'DOGE account', 'woothemes' ),
          'type' => 'checkbox',
          'label' => __( 'Enable xCoinMoney DOGE account', 'woothemes' ),
          'default' => 'yes'
        ),
      );
    }

    public function admin_options() {
      ?>
      <h3><?php _e('Bitcoin/Litecoin Payment', 'woothemes'); ?></h3>
      <p><?php _e('Allows bitcoin/litecoin payments via xcoinmoney.com ', 'woothemes'); ?></p>
      <table class="form-table">
        <?php

        $this->generate_settings_html();
        ?>
      </table>
    <?php
    }

    public function email_instructions( $order, $sent_to_admin ) {
      return;
    }

    function payment_fields() {
      if ($this->description) echo wpautop(wptexturize($this->description));
    }

    function thankyou_page() {
      if ($this->description) echo wpautop(wptexturize($this->description));
    }


    function process_payment($order_id){
      $order = new WC_Order( $order_id );

      return array(
        'result' => 'success',
        'redirect'  => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
      );
    }

    public function receipt_page($order){
      echo $this->generate_form($order);
    }

    public function generate_form($order_id){
      $order = new WC_Order( $order_id );


      $prefix = 'billing_';

      if (get_woocommerce_currency() == 'USD') {
        $currency = 'DXX';
      } else {
        $currency = get_woocommerce_currency();
      }

      $allowed_currencies = array();

      if ($this->settings['dxx_account'] == 'yes') {
        $allowed_currencies[] = 'DXX';
      }
      if ($this->settings['btc_account'] == 'yes') {
        $allowed_currencies[] = 'BTC';
      }
      if ($this->settings['ltc_account'] == 'yes') {
        $allowed_currencies[] = 'LTC';
      }
      if ($this->settings['xpm_account'] == 'yes') {
        $allowed_currencies[] = 'XPM';
      }
      if ($this->settings['doge_account'] == 'yes') {
        $allowed_currencies[] = 'DOGE';
      }


      $options = array();
      $options['cmd'] = 'order';
      $options['user_id'] = $this->settings['merchant_id'];
      $options['amount'] = $order->order_total;
      $options['currency'] = $currency;
      $options['allowed_currencies'] = implode(', ', $allowed_currencies);
      $options['payer_pays_fee'] = 0;
      $options['item_name'] = "Payment for order - $order_id";
      $options['item_number'] = $order_id;
      $options['quantity'] = 1;
      $options['first_name'] = $order->{$prefix.first_name};
      $options['last_name'] = $order->{$prefix.last_name};
      $options['address1'] = $order->{$prefix.address_1};
      $options['address2'] = $order->{$prefix.address_2};
      $options['city'] = $order->{$prefix.city};
      $options['state'] = $order->{$prefix.state};
      $options['zip'] = $order->{$prefix.postcode};
      $options['country'] = $order->{$prefix.country};
      $options['email'] = $order->{$prefix.email};

      $options['callback_url'] = $this->settings['callback'];

      $str = '';
      $keys = array_keys($options);
      sort($keys);
      for ($i=0; $i < count($keys); $i++) {
        $str .= $options[$keys[$i]];
      }
      $str .=  $this->settings['api_key'];
      $options['hash'] = md5($str);

      $result = $this->send_api_call($options);

      if($result->result == 'success') {
        echo'<script> window.location="'.$result->url.'"; </script> ';
      }

      die();
    }

    private function send_api_call($options) {
      $result = FALSE;

      try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->settings['coinmoney_url']);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $options);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $json = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($json);
        if (isset($result->data)) {
          $result = json_decode($result->data);
        }
      }
      catch (Exception $e) {  }
      return $result;
    }

    public function admin_options1() {
      ?>
      <h3><?php _e('Bitcoin Payment', 'woothemes'); ?></h3>
      <p><?php _e('Allows bitcoin payments via xCoinMoney ', 'woothemes'); ?></p>
      <table class="form-table">
        <?php
        // Generate the HTML For the settings form.
        $this->generate_settings_html();
        ?>
      </table>
    <?php
    } // End admin_options()

  }
}


function wc_coinmoney_callback(){
  global $woocommerce;

  $gateways = $woocommerce->payment_gateways->payment_gateways();

  if (!isset($gateways['coinmoney']))
  {
    return;
  }

  if (isset($_POST['data']) && isset($_POST['hash'])) {
    $hash = md5(stripcslashes($_POST['data']) . $gateways['coinmoney']->settings['api_key']);

    if ($_POST['hash'] == $hash) {
      $data = json_decode(stripcslashes($_POST['data']));
      $sessionid = $data->item_number;

      if (is_numeric($sessionid)) {

        $order = new WC_Order($sessionid);
        $order->payment_complete();

        echo "OK";
      } else {
        header("HTTP/1.0 404 Not Found");
        die();
      }
    } else {
      header("HTTP/1.0 404 Not Found");
      die();
    }
  }
}

add_action('init', 'wc_coinmoney_callback');
add_filter( 'woocommerce_payment_gateways', 'add_WC_Coinmoney_Payment_Gateway' );

function add_WC_Coinmoney_Payment_Gateway($methods){
  $methods[] = 'WC_Coinmoney_Payment_Gateway';
  return $methods;
}
?>
