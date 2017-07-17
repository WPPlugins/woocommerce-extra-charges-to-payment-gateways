<?php
/*
Plugin Name: WooCommerce Extra Charges To Payment Gateway (Standard)
Plugin URI: http://www.mydealstm.com
Description: You can add extra fee for any payment gateways
Version: 1.0.12
Author: hemsingh1
Author URI: http://www.mydealstm.com
Text Domain: woocommerce-extra-charges-to-payment-gateways
Domain Path: /languages/
*/

/**
 * Copyright (c) `date "+%Y"` hemsingh1. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * **********************************************************************
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


class WC_PaymentGateway_Add_extra_std_Charges{
    public function __construct(){
        $this -> current_gateway_title = '';
        $this -> current_gateway_extra_charges = '';
        add_action('admin_head', array($this, 'add_form_fields'));
        
        add_action( 'woocommerce_cart_calculate_fees', array( $this, 'calculate_totals' ), 10, 1 );

        //add_action( 'woocommerce_calculate_totals', array( $this, 'calculate_totals' ), 10, 1 );
   add_action( 'wp_enqueue_scripts',array($this,'load_my_script'));
   add_action('plugins_loaded', array($this, 'load_textdomain'));
    }


function load_textdomain() {
 
     load_plugin_textdomain('woocommerce-extra-charges-to-payment-gateways', false, dirname(plugin_basename(__FILE__)) . '/languages/');
 
 }

    function load_my_script(){

        wp_enqueue_script( 'wc-add-extra-charges', $this->plugin_url() . '/assets/app.js', array('wc-checkout'), false, true );
    }
//tttty

    function add_form_fields(){
        global $woocommerce;
         // Get current tab/section
        $current_tab        = ( empty( $_GET['tab'] ) ) ? '' : sanitize_text_field( urldecode( $_GET['tab'] ) );
        $current_section    = ( empty( $_REQUEST['section'] ) ) ? '' : sanitize_text_field( urldecode( $_REQUEST['section'] ) );

        if($current_tab == 'checkout' && $current_section!='' && ($current_section=='bacs'||$current_section=='cod'||$current_section=='cheque')){
            $gateways = $woocommerce->payment_gateways->payment_gateways();
            foreach($gateways as $gateway){
                if( (strtolower(get_class($gateway))=='wc_gateway_bacs' || strtolower(get_class($gateway))=='wc_gateway_cheque' || strtolower(get_class($gateway))=='wc_gateway_cod') && strtolower(get_class($gateway))=='wc_gateway_'.$current_section){
                    $current_gateway = $gateway -> id;
                    $extra_charges_id = 'woocommerce_'.$current_gateway.'_extra_charges';
                    $extra_charges_type = $extra_charges_id.'_type';
                    if(isset($_REQUEST['save'])){
                        update_option( $extra_charges_id, $_REQUEST[$extra_charges_id] );
                        update_option( $extra_charges_type, $_REQUEST[$extra_charges_type] );
                    }
                    $extra_charges = get_option( $extra_charges_id);
                    $extra_charges_type_value = get_option($extra_charges_type);
                }
            }

            ?>
            <script>
            jQuery(document).ready(function($){
                $data = '<h4>"<?php __('Add Extra Charges', 'woocommerce-extra-charges-to-payment-gateways');?>"</h4><table class="form-table">';
                $data += '<tr valign="top">';
                $data += '<th scope="row" class="titledesc">"<?php __('Extra Charges', 'woocommerce-extra-charges-to-payment-gateways');?>"</th>';
                $data += '<td class="forminp">';
                $data += '<fieldset>';
                $data += '<input style="" name="<?php echo $extra_charges_id?>" id="<?php echo $extra_charges_id?>" type="text" value="<?php echo $extra_charges?>"/>';
                $data += '<br /></fieldset></td></tr>';
                $data += '<tr valign="top">';
                $data += '<th scope="row" class="titledesc">"<?php __('Extra Charges Type', 'woocommerce-extra-charges-to-payment-gateways');?>"</th>';
                $data += '<td class="forminp">';
                $data += '<fieldset>';
                $data += '<select name="<?php echo $extra_charges_type?>"><option <?php if($extra_charges_type_value=="add") echo "selected=selected"?> value="add">"<?php __('Total Add', 'woocommerce-extra-charges-to-payment-gateways');?>"</option>';
                $data += '<option <?php if($extra_charges_type_value=="percentage") echo "selected=selected"?> value="percentage">"<?php __('Total % Add', 'woocommerce-extra-charges-to-payment-gateways');?>"</option>';
                $data += '<br /></fieldset></td></tr></table>';
                $('.form-table:last').after($data);

            });
</script>
<?php
}
}

//Modified functions to include fee in email
public function calculate_totals( $totals ) {
    global $woocommerce;
    $available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
    $current_gateway = '';
    if ( ! empty( $available_gateways ) ) {
           // Chosen Method
        if ( isset( $woocommerce->session->chosen_payment_method ) && isset( $available_gateways[ $woocommerce->session->chosen_payment_method ] ) ) {
            $current_gateway = $available_gateways[ $woocommerce->session->chosen_payment_method ];
        } elseif ( isset( $available_gateways[ get_option( 'woocommerce_default_gateway' ) ] ) ) {
            $current_gateway = $available_gateways[ get_option( 'woocommerce_default_gateway' ) ];
        } else {
            $current_gateway =  current( $available_gateways );

        }
    }
    if($current_gateway!=''){
        $current_gateway_id = $current_gateway -> id;
        $extra_charges_id = 'woocommerce_'.$current_gateway_id.'_extra_charges';
        $extra_charges_type = $extra_charges_id.'_type';
        $extra_charges = (float)get_option( $extra_charges_id);
        $extra_charges_type_value = get_option( $extra_charges_type);
        if($extra_charges){
            if($extra_charges_type_value=="percentage"){
           $decimal_sep = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ), ENT_QUOTES );
     $thousands_sep = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ), ENT_QUOTES );

       $t1 = ($totals -> cart_contents_total*$extra_charges)/100;
                //$totals -> cart_contents_total = $totals -> cart_contents_total + round(($totals -> cart_contents_total*$extra_charges)/100,2);
$t3 = ($totals -> cart_contents_total*0.1)/100;

            }else{
//$totals -> cart_contents_total = $totals -> cart_contents_total + $extra_charges;
$t1 =  $extra_charges;
            }

            $this -> current_gateway_title = $current_gateway -> title;
            $this -> current_gateway_extra_charges = $extra_charges;
            $this -> current_gateway_extra_charges_type_value = $extra_charges_type_value;


   $t5 = ($extra_charges_type_value=="percentage"? $extra_charges.'%':'Fixed');

// $woocommerce->cart->
//$totals->add_fee( __( $this -> current_gateway_title.'  Extra Charges -  '.$t5),$t1);

$woocommerce->cart->add_fee( __( $this -> current_gateway_title.'  Extra Charges -  '.$t5),$t1);


        }

    }
    return $totals;
}


function add_payment_gateway_extra_charges_row(){
    ?>
    <tr class="payment-extra-charge">
        <th><?php echo $this->current_gateway_title . ' ' . __('Extra Charges', 'woocommerce-extra-charges-to-payment-gateways');?></th>
        <td><?php if($this->current_gateway_extra_charges_type_value=="percentage"){
            echo $this -> current_gateway_extra_charges.'%';
        }else{
         echo woocommerce_price($this -> current_gateway_extra_charges);
     }?></td>
 </tr>
 <?php
}

/**
     * Get the plugin url.
     *
     * @access public
     * @return string
     */
    public function plugin_url() {

        return $this->plugin_url = untrailingslashit( plugins_url( '/', __FILE__ ) );
    }


    /**
     * Get the plugin path.
     *
     * @access public
     * @return string
     */
    public function plugin_path() {
        if ( $this->plugin_path ) return $this->plugin_path;

        return $this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
    }

}
new WC_PaymentGateway_Add_extra_std_Charges();