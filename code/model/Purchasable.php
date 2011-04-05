<?php
/**
 * Interface class for {@link DataObject} classes
 * that should be considered "purchasable" by the
 * ecommerce system. Provides hooks for {@link OrderItem}
 * to retrieve required data like the product price,
 * before it can be added to the {@link ShoppingCart}
 *
 * @package ecommerce
 * @subpackage model
 * @author Sean Harvey <sean at silverstripe dot com>
 */
interface Purchasable {
	
	/**
	 * Factory method to create an {@link OrderItem}
	 * to be stored in {@link ShoppingCart} and used by
	 * {@link Order} to know what product is being
	 * purchased by the customer.
	 * 
	 * @param int $quantity The default quantity for the item
	 * @return object OrderItem instance
	 */
	public function createOrderItem($quantity = 1);
	
	/**
	 * Return the price of the product. This is used by
	 * {@link OrderItem::calculateTotal()}
	 * 
	 * @return string
	 */
	public function getUnitPrice();
	
	/**
	 * Check if this item is in the {@link ShoppingCart}
	 * @return boolean
	 */
	public function IsInCart();
	
}