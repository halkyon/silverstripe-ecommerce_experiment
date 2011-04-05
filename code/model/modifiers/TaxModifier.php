<?php
/**
 * Implements sales tax on a per-country basis.
 * 
 * Here is an example of it configured in your
 * project _config.php file:
 * 
 * <code>
 * TaxModifier::set_by_country('NZ', 0.125, 'GST', 'inclusive');
 * TaxModifier::set_by_country('UK', 0.175, 'VAT', 'exclusive');
 * </code>
 * 
 * @package ecommerce
 * @subpackage model
 */
class TaxModifier extends OrderModifier {

	public static $db = array(
		'Country' => 'Varchar',
		'Rate' => 'Double',
		'Name' => 'Varchar',
		'TaxType' => "Enum('Exclusive,Inclusive')"
	);
	
	/**
	 * Map of tax names by country.
	 * e.g "NZ" => "GST". Set by
	 * {@link TaxModifier::set_by_country()}
	 * 
	 * @var array
	 */
	protected static $names_by_country = array();

	/**
	 * Map of rates by country. e.g. "NZ" => 0.125.
	 * Set by {@link TaxModifier::set_by_country()}
	 * 
	 * @var array
	 */
	protected static $rates_by_country = array();
	
	/**
	 * Map of tax types by country.
	 * e.g. "NZ" => "exclusive", "UK" => "inclusive".
	 * Set by {@link TaxModifier::set_by_country()}
	 * 
	 * @var array
	 */
	protected static $taxtypes_by_country = array();
	
	/**
	 * Set the tax scheme for a particular country.
	 * 
	 * @param string $country The country code. e.g. "NZ" or "UK"
	 * @param double $rate The tax rate. e.g. "0.125" (12.5%)
	 * @param string $name The name of the tax. e.g. "GST" or "VAT"
	 * @param string $type The tax type. Possible values: "inclusive" (products are inclusive of tax)
	 * 											or "exclusive" (tax should be added to the order total)
	 */
	public static function set_by_country($country, $rate, $name, $type) {
		self::$names_by_country[$country] = $name;
		self::$rates_by_country[$country] = $rate;

		if(!in_array($type, $this->obj('TaxType')->enumValues())) {
			user_error(
				"TaxModifier::set_by_country(): Bad argument '$type' for the \$type argument",
				E_USER_ERROR
			);
		}
		
		self::$taxtypes_by_country[$country] = $type;
	}
	
	/**
	 * When TaxModifer is written for the first time,
	 * set the field to the return values of the
	 * overloaded functions.
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		
		if(!$this->ID) {
			$this->setField('Country', $this->Country());
			$this->setField('Rate', $this->Rate());
			$this->setField('Name', $this->Name());
			$this->setField('TaxType', $this->TaxType());
		}
	}
	
	/**
	 * Calculate the amount of tax is applicable on the items
	 * in the customers {@link ShoppingCart} - this is the
	 * amount that will eventually get saved into the Amount field
	 * when TaxModifier is written to the DB.
	 * 
	 * @return double|int
	 */
	public function calculateAmount() {
		return ($this->TaxType() == 'exclusive') ? $this->charge() : 0;
	}
	
	/**
	 * Calculate the charge to the customer (whether it be the
	 * exclusive amount or the amount that is already included
	 * in the price).
	 * 
	 * @return double
	 */
	protected function charge() {
		$rate = ($this->TaxType() == 'exclusive'
			? $this->Rate()
			: (1 - (1 / (1 + $this->Rate())))
		);
		
		return $this->taxableAmount() * $rate;
	}
	
	/**
	 * FIXME: Other modifiers (not this one) need to have their
	 * amount added to the subtotal to make up the taxable amount.
	 * 
	 * @return double
	 */
	protected function taxableAmount() {
		return $this->Order()->Subtotal();
	}

	/**
	 * Find out what country the customer is from.
	 * @return string|null string country found | NULL not found
	 */
	protected function findCustomerCountry() {
		return Member::currentUser() ? Member::currentUser()->Country : singleton('Member')->findCountry();
	}
	
	/**
	 * Overload the getter for the Country field to return the
	 * "live" country (TaxModifier is not in the DB yet).
	 * 
	 * @return string|null string country found | NULL not found
	 */
	public function Country() {
		if($this->ID) return $this->getField('Country');
		return $this->findCustomerCountry();
	}
	
	/**
	 * Overload the getter for the Rate field to return the
	 * "live" rate (TaxModifier is not in the DB yet).
	 * 
	 * @return double|null double found rate | NULL not found
	 */
	public function Rate() {
		if($this->ID) return $this->getField('Rate');
		$country = $this->findCustomerCountry();
		$rate = null;
		if($country && isset(self::$rates_by_country[$country])) {
			$rate = self::$rates_by_country[$country];
		}
		return $rate;
	}

	/**
	 * Overload the getter for the Name field to return the
	 * "live" name (TaxModifier is not in the DB yet).
	 * 
	 * @return string|null string name found | NULL not found
	 */
	public function Name() {
		if($this->ID) return $this->getField('Name');
		$country = $this->findCustomerCountry();
		$name = null;
		if($country && isset(self::$names_by_country[$country])) {
			$name = self::$names_by_country[$country];
		}
		return $country;
	}
	
	/**
	 * Overload the getter for the TaxType field to return the
	 * "live" tax type (TaxModifier is not in the DB yet).
	 * 
	 * @return string|null string type found | NULL not found
	 */
	public function TaxType() {
		if($this->ID) return $this->getField('TaxType');
		$country = $this->findCustomerCountry();
		$type = null;
		if($country && isset(self::$taxtypes_by_country[$country])) {
			$type = self::$taxtypes_by_country[$country];
		}
		return $type;
	}
	
}