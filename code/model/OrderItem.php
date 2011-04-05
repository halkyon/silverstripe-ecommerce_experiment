<?php
/**
 * OrderItem stores a price and link to a
 * {@link DataObject} that implements {@link Purchasable}
 * 
 * OrderItem must be subclassed for each class that
 * implements {@link Purchasable}. A good example
 * of an OrderItem implementation can be seen with
 * {@link ProductVariation_OrderItem}
 * 
 * OrderItem tells {@link Order} how much quantity of
 * an item the user wants, and calculates the
 * total of that item (quantity * price).
 * 
 * @see OrderItem::calculateTotal()
 * @see Order::Subtotal()
 * 
 * When a user adds an item to their cart, they
 * are adding a new OrderItem object which is created
 * every time the "add" action is called on
 * {@link ShoppingCart_Controller}.
 * {@link ShoppingCart} stores the OrderItem objects
 * in session, to be retrieved later when the
 * customer goes to checkout.
 * 
 * @package ecommerce
 * @subpackage model
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class OrderItem extends DataObject {
	
	public static $db = array(
		'Amount' => 'Currency',
		'Quantity' => 'Int'
	);
	
	public static $has_one = array(
		'Order' => 'Order'
	);
	
	public function __construct($record = null, $quantity = 1, $isSingleton = false) {
		parent::__construct($record, $isSingleton);
		$this->setField('Quantity', $quantity);
	}
	
	/**
	 * When this OrderItem is written for the
	 * first time, set the Amount field with the
	 * calculated amount.
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		
		if(!$this->ID) {
			$this->setField('Amount', $this->calculateAmount());
			$this->deductProductQuantity();
		}
	}
	
	/**
	 * Returns the internal product data record that
	 * this OrderItem is for.
	 * 
	 * @see ProductVariation_OrderItem::getInternalProduct()
	 * @return object
	 */
	public function getInternalProduct() {
		user_error("Please implement getInternalProduct() on $this->class", E_USER_ERROR);
	}
	
	/**
	 * This method should return the price
	 * of the internal product that is being
	 * purchased.
	 * 
	 * @see ProductVariation_OrderItem::getInternalProduct()
	 * @return string
	 */
	public function getUnitPrice() {
		user_error("Please implement getPrice() on $this->class", E_USER_ERROR);
	}
	
	/**
	 * Check if we can purchase by passing on this item
	 * quantity to the product so it can see if there is
	 * enough to purchase.
	 * 
	 * @return boolean
	 */
	public function canPurchase() {
		return $this->getInternalProduct()->canPurchase($this->Quantity);
	}
	
	/**
	 * Deduct this OrderItem quantity on the product data.
	 * This should be called at the time the OrderItem gets
	 * written to the DB {@link Order::process()}
	 */
	public function deductProductQuantity() {
		$this->getInternalProduct()->deductQuantity($this->Quantity);
	}
	
	/**
	 * Add this item amount to an existing amount.
	 * @param decimal $amount The amount to update
	 * @return double Updated amount
	 */
	public function updateAmount($amount) {
		return $amount += $this->Amount();
	}
	
	/**
	 * Return the amount of this item.
	 * @return string
	 */
	public function Amount() {
		return ($this->ID) ? $this->Amount : $this->calculateAmount();
	}
	
	/**
	 * Calculate the amount of this item.
	 * @return string
	 */
	public function calculateAmount() {
		return $this->getUnitPrice() * $this->Quantity;
	}
	
}