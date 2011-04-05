<?php
/**
 * An OrderModifier alters an order by charging or discounting
 * the total price of it.
 * 
 * This is especially useful for discounts or a shipping
 * calculator. Based on what items are in the users cart, you
 * could charge an additional amount to the order for
 * shipping costs.
 *
 * There are three types of OrderModifiers (denoted by the
 * "Type" enum field below):
 * 
 * - 'None': This is for a state when you need to indicate
 *   to the user an amount, but it doesn't modify the total.
 *   (very useful for indicating tax on an order where tax
 *   is inclusive on the products being purchased)
 * 
 * - 'Chargable': Charges an amount to the order. A good example
 *   of this would be shipping costs.
 *
 * - 'Deductable': Discounts an amount from the order. A good
 *   example is a discount being offered to the customer; a
 *   rewards scheme, or offering a 10% discount if the customer
 *   is a VIP or frequent customer.
 *
 * OrderModifiers can be as simple or as complex as needed.
 * {@link OrderModifier::Amount()} returns the amount to be
 * charged or discounted. The amount is calculated in
 * {@link OrderModifier::calculateAmount()} (this should be
 * implemented on your modifier classes).
 *
 * If the OrderModifier is saved to the DB (user has checked
 * out), then {@link OrderModifier::Amount()} will return the
 * amount from the DB instead of performing a calculation.
 *
 * @todo Document how the user can interact with an OrderModifier
 * on the OrderForm step. For example, the user enters a coupon
 * code to receive a discount.
 * 
 * @package ecommerce
 * @subpackage model
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class OrderModifier extends DataObject {

	public static $db = array(
		'Amount' => 'Currency',
		'Type' => "Enum('None,Chargable,Deductable')"
	);
	
	public static $has_one = array(
		'Order' => 'Order'
	);
	
	public static $defaults = array(
		'Type' => 'Chargable'
	);
	
	/**
	 * When this OrderModifier is written for the
	 * first time, set the Amount field with the
	 * calculated amount.
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		
		if(!$this->ID) {
			$this->setField('Amount', $this->calculateAmount());
		}
	}
	
	/**
	 * Is this a modifier that adds to the order?
	 * @return boolean
	 */
	public function isChargable() {
		return ($this->Type == 'Chargable') ? true : false;
	}
	
	/**
	 * Is this a modifier that deducts from the order?
	 * @return boolean
	 */
	public function isDeductable() {
		return ($this->Type == 'Deductable') ? true : false;
	}
	
	/**
	 * Add or subtract this modifier amount to
	 * an existing amount given.
	 * 
	 * @param decimal $amount The amount to update
	 * @return string Updated amount
	 */
	public function updateAmount($amount) {
		if($this->isChargable()) return $amount += $this->Amount();
		if($this->isDeductable()) return $amount -= $this->Amount();
	}
	
	/**
	 * Overload the Order relation getter to return a
	 * singleton of Order if this modifier is not
	 * written to the DB yet - calculations will be
	 * done based on items in {@link ShoppingCart}.
	 * 
	 * @return object Order
	 */
	public function Order() {
		return ($this->ID) ? $this->getComponent('Order') : singleton('Order');
	}
	
	/**
	 * Return the amount that is chargable or
	 * deductable on this OrderModifier.
	 *
	 * @return string
	 */
	public function Amount() {
		return ($this->ID) ? $this->Amount : $this->calculateAmount();
	}

	/**
	 * Calculate the amount chargable, or deductable
	 * on the items total (subtotal) from the
	 * related {@link Order}.
	 * 
	 * Example (order subtotal multiplied by 10%):
	 * <code>
	 * return $this->Order()->Subtotal() * 0.1;
	 * </code>
	 * 
	 * @return string
	 */
	public function calculateAmount() {
		user_error("Please implement calculateAmount() on $this->class", E_USER_ERROR);
	}
	
}