<?php
/**
 * Just as the name suggests, this modifier supports a
 * very simple shipping scheme. Shipping is charged by
 * the country the items are being shipped to at a flat
 * rate, falling back to a default rate if the customer's
 * country isn't in the list.
 * 
 * To apply shipping charges to a very specific set of
 * countries only, just leave out the default charge.
 * 
 * Here is an example of it configured in your
 * project _config.php file:
 * 
 * <code>
 * SimpleShippingModifier::$default_charge = 20;
 * SimpleShippingModifier::$charges_for_countries = array(
 * 	'NZ' => 5,
 * 	'US' => 10,
 * 	'UK' => 15
 *	);
 * </code>
 * 
 * @package ecommerce
 * @subpackage model
 */
class SimpleShippingModifier extends OrderModifier {

	public static $db = array(
		'Country' => 'Varchar'
	);
	
	/**
	 * The default shipping charge amount.
	 * @var int|double
	 */
	public static $default_charge = 0;
	
	/**
	 * Shipping charges by country.
	 * @var array
	 */
	public static $charges_for_countries = array();
	
	/**
	 * When SimpleShippingModifier is written for the
	 * first time, set the field to the return values
	 * of the overloaded functions.
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		
		if(!$this->ID) {
			$this->setField('Country', $this->Country());
		}
	}
	
	/**
	 * Calculate the shipping costs based on the country
	 * code for the member. If a country isn't found, then
	 * return the default charge.
	 * 
	 * @return int|double
	 */
	public function calculateAmount() {
		if(isset(self::$charges_for_countries[$this->Country()])) {
			return self::$charges_for_countries[$this->Country()];
		} else {
			return self::$default_charge;
		}
	}
	
	/**
	 * Overload the getter for the Country field to return the
	 * "live" country (SimpleShippingModifier is not in the DB yet).
	 * 
	 * @return string|null string type found | NULL not found
	 */
	public function Country() {
		$country = $this->Order()->findShippingCountry();
		return ($this->ID) ? $this->getField('Country') : $country;
	}
	
}