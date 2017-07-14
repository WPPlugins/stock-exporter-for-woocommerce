<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Class.
 *
 * These are our Products, which extend the regular WooCommerce Products,in order to abstract properties access after the 3.0 changes
 *
 */
class WC_Product_Stock_Exporter extends WC_Product {

	/**
	 * Returns the unique ID for this product.
	 * @return int
	 */
	public function se_get_id() {
		return version_compare( WC_VERSION, '3.0', '>=' ) ? $this->get_id() : $this->id;
	}

	/**
	 * Gets product meta
	 */
	public function se_get_meta( $key ) {
		if ( version_compare( WC_VERSION, '3.0', '>=' ) ) {
			return $this->get_meta($key);
		} else {
			return get_post_meta($this->se_get_id(), $key, true);
		}
	}

}

/**
 * Variable Product Class.
 *
 * These are our Products, which extend the regular WooCommerce Products,in order to abstract properties access after the 3.0 changes
 *
 */
class WC_Product_Variation_Stock_Exporter extends WC_Product_Variation {

	/**
	 * Returns the unique ID for this product variation
	 * @return int
	 */
	public function se_get_id() {
		return version_compare( WC_VERSION, '3.0', '>=' ) ? $this->get_id() : $this->id;
	}

	/**
	 * Gets variation meta
	 */
	public function se_get_meta( $key ) {
		if ( version_compare( WC_VERSION, '3.0', '>=' ) ) {
			return $this->get_meta($key);
		} else {
			return get_post_meta($this->se_get_id(), $key, true);
		}
	}

}