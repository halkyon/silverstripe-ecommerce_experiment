<?php
/**
 * Simple implementation of {@link DataObjectDecorator}
 * that should be applied to a page type (default = "Page")
 * to add the ability to use a shopping cart template
 * across an entire site.
 * 
 * @author Sean Harvey <sean at silverstripe dot com>
 * @package ecommerce
 * @subpackage cart
 */
class ShoppingCartPageExtension extends DataObjectDecorator {
	
	/**
	 * Return an instance of {@link ShoppingCart_Controller}
	 * so that we can show a cart template across the site.
	 * 
	 * @return object ShoppingCart object
	 */
	public function Cart() {
		$cart = new ShoppingCart_Controller();
		$cart->init();
		
		return $cart;
	}
	
}