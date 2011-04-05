<?php
/**
 * Storage backend for {@link ShoppingCart} that stores
 * cart data in PHP session using the
 * {@link Session} class.
 * 
 * @uses Session
 * @author Sean Harvey <sean at silverstripe dot com>
 * @package ecommerce
 * @subpackage cart
 */
class ShoppingCartSessionBackend {
	
	/**
	 * @see ShoppingCart::getItems()
	 * @return array
	 */
	public function getItems() {
		return Session::get('ShoppingCart.Items');
	}
	
	/**
	 * @see ShoppingCart::getModifiers()
	 * @return array
	 */
	public function getModifiers() {
		return Session::get('ShoppingCart.Modifiers');
	}
	
	/**
	 * @see ShoppingCart::addItem()
	 * @param string|int $itemID The ID of the item
	 * @param string $item Serialized OrderItem object to add
	 */
	public function addItem($itemID, $item) {
		Session::set("ShoppingCart.Items.{$itemID}", $item);
	}
	
	/**
	 * @see ShoppingCart::removeItem()
	 * @param string|int $itemID The ID of the item
	 */
	public function removeItem($itemID) {
		Session::clear("ShoppingCart.Items.{$itemID}");
	}
	
	/**
	 * @see ShoppingCart::emptyCart()
	 */
	public function emptyCart() {
		Session::clear('ShoppingCart');
	}
	
}