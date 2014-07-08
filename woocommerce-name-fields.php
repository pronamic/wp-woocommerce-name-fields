<?php
/*
Plugin Name: WooCommerce Name Fields
Plugin URI: http://www.pronamic.eu/
Description: Add support for house number checkout fields to WooCommerce.

Version: 1.0.0
Requires at least: 3.0

Author: Pronamic
Author URI: http://www.pronamic.eu/

License: GPL
*/

class WooCommerceNameFieldsPlugin {
	/**
	 * Constructs and initialize WooCommerce name fields plugin
	 */
	public function __construct( $file ) {
		$this->file = $file;

		// Actions
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'checkout_update_order_meta' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Filters
		add_filter( 'woocommerce_checkout_fields', array( $this, 'checkout_fields' ) );
	}

	/**
	 * Plugins loaded
	 */
	public function plugins_loaded() {
		load_plugin_textdomain( 'woocommerce_name_fields', false, dirname( plugin_basename( $this->file ) ) . '/languages/' );
	}

	/**
	 * WooCommerce house number checkout fields
	 * 
	 * @param array $fields
	 * @return array
	 */
	public function checkout_fields( $fields ) {
		// Fields
		$field_name_initials = array(
			'label'       => __( 'Initials', 'wc_hn' ),
			'required'    => true,
			'class'       => array( 'form-row-first' ),
			'clear'       => false,
		);

		$field_last_name_prefix = array(
			'label'       => __( 'Prefix', 'wc_hn' ),
			'required'    => false,
			'class'       => array( 'form-row-first' ),
			'clear'       => false,
		);

		$field_last_name_clean = array(
			'label'       => __( 'Last Name', 'wc_hn' ),
			'required'    => true,
			'class'       => array( 'form-row-last' ),
			'clear'       => true,
		);

		// Position
		$position = 3;

		// Billing fields
		if ( isset( $fields['billing'] ) ) {
			$fields_billing = $fields['billing'];
			$fields_billing['billing_name_initials']    = $field_name_initials;
			$fields_billing['billing_last_name_prefix'] = $field_last_name_prefix;
			$fields_billing['billing_last_name_clean']  = $field_last_name_clean;

			$fields_billing_new = array();

			foreach ( $fields_billing as $name => $field ) {
				if ( 'billing_first_name' == $name ) {
					$fields_billing_new['billing_name_initials'] = $fields_billing['billing_name_initials'];

					continue;
				}

				if ( 'billing_last_name' == $name ) {
					$fields_billing_new['billing_last_name_prefix'] = $fields_billing['billing_last_name_prefix'];
					$fields_billing_new['billing_last_name_clean'] = $fields_billing['billing_last_name_clean'];

					continue;
				}

				$fields_billing_new[ $name ] = $field;
			}

			$fields['billing'] = $fields_billing_new;
		}

		// Shipping fields 
		if ( isset( $fields['shipping'] ) ) {
			$fields_shipping = $fields['shipping'];
			$fields_shipping['shipping_name_initials']    = $field_name_initials;
			$fields_shipping['shipping_last_name_prefix'] = $field_last_name_prefix;
			$fields_shipping['shipping_last_name_clean']  = $field_last_name_clean;

			$fields_shipping_new = array();

			foreach ( $fields_shipping as $name => $field ) {
				if ( 'shipping_first_name' == $name ) {
					$fields_shipping_new['shipping_name_initials'] = $fields_shipping['shipping_name_initials'];

					continue;
				}

				if ( 'shipping_last_name' == $name ) {
					$fields_shipping_new['shipping_last_name_prefix'] = $fields_shipping['shipping_last_name_prefix'];
					$fields_shipping_new['shipping_last_name_clean']  = $fields_shipping['shipping_last_name_clean'];

					continue;
				}

				$fields_shipping_new[ $name ] = $field;
			}

			$fields['shipping'] = $fields_shipping_new;
		}

		return $fields;
	}

	/**
	 * Update order meta
	 * @see https://github.com/woothemes/woocommerce/blob/v2.0.12/classes/class-wc-checkout.php#L359
	 * @see https://github.com/woothemes/woocommerce/blob/v2.0.12/classes/class-wc-checkout.php#L15
	 * 
	 * @param string $order_id
	 * @param array $posted array of posted form data
	 */
	function checkout_update_order_meta( $order_id, $posted ) {
		/*
		 * Billing
		 */

		// First Name
		$initials         = isset( $posted['billing_name_initials'] ) ? woocommerce_clean( $posted['billing_name_initials'] ) : '';

		$billing_first_name = $initials;

		// Last Name
		$last_name_prefix = isset( $posted['billing_last_name_prefix'] ) ? woocommerce_clean( $posted['billing_last_name_prefix'] ) : '';
		$last_name_clean  = isset( $posted['billing_last_name_clean'] ) ? woocommerce_clean( $posted['billing_last_name_clean'] ) : '';

		$billing_last_name = trim( sprintf( 
			'%s %s', 
			$last_name_prefix, 
			$last_name_clean
		) );

		// @see https://github.com/woothemes/woocommerce/blob/v2.0.12/admin/post-types/writepanels/writepanel-order_data.php#L721
		update_post_meta( $order_id, '_billing_first_name', $billing_first_name );
		update_post_meta( $order_id, '_billing_last_name', $billing_last_name );

		/*
		 * Shipping
		 */

		// First Name
		$initials         = isset( $posted['shipping_name_initials'] ) ? woocommerce_clean( $posted['shipping_name_initials'] ) : '';

		$shipping_first_name = $initials;

		// Last Name
		$last_name_prefix = isset( $posted['shipping_last_name_prefix'] ) ? woocommerce_clean( $posted['shipping_last_name_prefix'] ) : '';
		$last_name_clean  = isset( $posted['shipping_last_name_clean'] ) ? woocommerce_clean( $posted['shipping_last_name_clean'] ) : '';

		$shipping_last_name = trim( sprintf( 
			'%s %s', 
			$last_name_prefix, 
			$last_name_clean
		) );

		if ( empty( $shipping_first_name ) ) {
			// Use billing address as shipping adres 1
			$shipping_first_name = $billing_first_name;
		}

		if ( empty( $shipping_last_name ) ) {
			// Use billing address as shipping adres 1
			$shipping_last_name = $billing_last_name;
		}

		// @see https://github.com/woothemes/woocommerce/blob/v2.0.12/admin/post-types/writepanels/writepanel-order_data.php#L721
		update_post_meta( $order_id, '_shipping_first_name', $shipping_first_name );
		update_post_meta( $order_id, '_shipping_last_name', $shipping_last_name );
	}

	/**
	 * Enqueue plugin style-file
	 */
	function enqueue_scripts() {
		wp_enqueue_style( 'woocommerce-name-fields', plugins_url( 'style.css', $this->file ) );
	}
}

new WooCommerceNameFieldsPlugin( __FILE__ );
