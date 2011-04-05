<?php
/**
 * A ProductVariation is a product, but with different attributes.
 * For example, a t-shirt could be in many different sizes,
 * so each size would be a single {@link ProductVariation} which
 * makes up the {@link Product} through a one-to-many relation.
 * 
 * ProductVariation is versioned, so that when the user purchases a
 * product, the price is always consistent throughout the
 * purchase process, even if the site admin changes the price.
 *
 * @package ecommerce
 * @subpackage model
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class ProductVariation extends DataObject implements Purchasable {
	
	public static $db = array(
		'Price' => 'Currency',
		'Quantity' => 'Int',
		'Weight' => 'Decimal(9,2)'
	);

	public static $has_one = array(
		'Product' => 'Product'
	);
	
	public static $extensions = array(
		"Versioned('Live')"
	);
	
	public static $summary_fields = array(
		'Price' => 'Price',
		'Quantity' => 'Quantity',
		'Weight' => 'Weight (kg)'
	);
	
	public static $field_types = array(
		'Price' => 'CurrencyField',
		'Quantity' => 'NumericField',
		'Weight' => 'NumericField'
	);
	
	/**
	 * Inherit the permissions to check if the
	 * logged in member can add a product.
	 * 
	 * @return boolean
	 */
	public function canAdd($member = null) {
		return $this->getComponent('Product')->canAdd($member);
	}
	
	/**
	 * Inherit the permissions to check if the
	 * logged in member can edit a product. This
	 * is for adding an admin adding a product to
	 * the database, not a customer adding it to
	 * their cart.
	 * 
	 * @return boolean
	 */
	public function canEdit($member = null) {
		return $this->getComponent('Product')->canEdit($member);
	}
	
	/**
	 * Inherit the permissions to check if the
	 * logged in member can delete a product.
	 * 
	 * @return boolean
	 */
	public function canDelete($member = null) {
		return $this->getComponent('Product')->canDelete($member);
	}
	
	/**
	 * Can this variation be purchased?
	 * @param int $quantity The quantity to check against
	 * @return boolean
	 */
	public function canPurchase($quantity) {
		$modifiedQuantity = $this->Quantity - $quantity;
		return ($modifiedQuantity >= 0) ? true : false;
	}
	
	/**
	 * Deduct quantity from this product's existing quantity.
	 * This assumes that {OrderItem::canPurchase()} has been
	 * checked first before this method is called.
	 * 
	 * @param int $quantity Quantity amount to deduct
	 */
	public function deductQuantity($quantity) {
		$this->setField('Quantity', $this->Quantity - $quantity);
	}
	
	/**
	 * Check if this item is in the {@link ShoppingCart}
	 * @return boolean
	 */
	public function isInCart() {
		return (singleton('ShoppingCart')->getItem($this->ID)) ? true : false;
	}
	
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$fields->removeByName('Versions');
		$fields->renameField('Weight', 'Weight (kg)');
		return $fields;
	}
	
	/**
	 * Return fields that should be visible to the user
	 * on the front end. These are scaffolded fields
	 * with some inappropriate fields removed.
	 * 
	 * @return object FieldSet object
	 */
	public function getFrontEndFields() {
		$fields = parent::getFrontEndFields();
		$fields->removeByName('Weight');
		$fields->removeByName('Version');
		$fields->removeByName('ProductID');
		
		foreach($fields as $field) {
			$field->setValue($this->getField($field->Name()));
		}
		
		return $fields;
	}
	
	/**
	 * Return the parent {@link Product} record
	 * Title field. NOTE: This may change in the
	 * future (e.g. this variation may need a title
	 *	of it's own).
	 * 
	 * @return string
	 */
	public function Title() {
		return $this->getComponent('Product')->Title;
	}
	
	/**
	 * Create an {@link OrderItem} instance that tells the
	 * {@link ShoppingCart} and {@link Order} about this
	 * product variation that the site user is purchasing.
	 *
	 * @param int $quantity The default quantity for the item
	 * @return object OrderItem object
	 */
	public function createOrderItem($quantity = 1) {
		$record = array(
			'ProductVariationID' => $this->ID,
			'Version' => $this->Version
		);
		
		return new ProductVariation_OrderItem($record, $quantity);
	}
	
	/**
	 * Return the price of the product. This is used by
	 * {@link OrderItem::calculateTotal()}
	 * 
	 * @return string
	 */
	public function getUnitPrice() {
		return $this->getField('Price');
	}
	
	/**
	 * ProductVariation doesn't have a link, so just
	 * return the link to the product.
	 * 
	 * @return string URL of product
	 */
	public function Link() {
		return $this->getComponent('Product')->Link();
	}
	
}
class ProductVariation_OrderItem extends OrderItem {
	
	public static $db = array(
		'Version' => 'Int'
	);
	
	public static $has_one = array(
		'ProductVariation' => 'ProductVariation'
	);
	
	public function getInternalProduct() {
		return $this->getComponent('ProductVariation');
	}
	
	public function getUnitPrice() {
		$version = (int) $this->Version;
		$variationID = (int) $this->ProductVariationID;
		if(!($version && $variationID)) return false;
		
		$variation = $this->getComponent('ProductVariation', "Version = '$version'");
		return $variation->getUnitPrice();
	}
	
}