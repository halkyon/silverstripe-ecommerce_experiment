<?php
/**
 * Provides additional functionality and database columns
 * to {@link Member} specifically for the ecommerce package.
 * 
 * One example of this is extending the default Member
 * database columns with additional ones specific to 
 * a shop, e.g. address, zip code, phone number, country.
 * 
 * @package ecommerce
 * @subpackage model
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class EcommerceMemberExtension extends DataObjectDecorator {
	
	public function extraStatics() {
		return array(
			'db' => array(
				'FirstName' => 'Varchar',
				'Surname' => 'Varchar',
				'Phone' => 'Varchar',
				'Email' => 'Varchar',
				'Address' => 'Text',
				'City' => 'Varchar',
				'Country' => 'Varchar'
			),
			'has_many' => array(
				'Orders' => 'Order'
			)
		);
	}

	/**
	 * Fields for shown in the billing details part
	 * of {@link OrderForm}
	 * 
	 * @return array
	 */
	public function billingDetailsFields() {
		return new CompositeField(array(
			new HeaderField('ContactHeader', 'Contact details', 3),
			new TextField('FirstName', 'First name'),
			new TextField('Surname'),
			new TextField('Phone'),
			new EmailField('Email'),
			new TextareaField('Address', 'Address', 2),
			new TextField('City'),
			new TextField('Country')
		));
	}
	
	/**
	 * Attempt to find out the country from a user who
	 * is not logged into the website using GeoIP.
	 * 
	 * @return string
	 */
	public function findCountry() {
		return Geoip::visitor_country();
	}
	
	/**
	 * Check that no existing members have the same value
	 * for their unique field. This is useful for checking
	 * if a member already exists with a certain email address.
	 * 
	 * If the member is logged in, and the existing member found
	 * has the same ID (it's them), return TRUE because this is
	 * their own member account.
	 * 
	 * @param array $data Raw data to check from a form request
	 * @return boolean TRUE is unique | FALSE not unique
	 */
	public function checkUniqueFieldValue($data) {
		$field = Member::get_unique_identifier_field();
		$value = isset($data[$field]) ? $data[$field] : null;
		if(!$value) return true;
		$SQL_value = Convert::raw2sql($value);
		$existingMember = DataObject::get_one('Member', "$field = '{$SQL_value}'");
		if($existingMember && $existingMember->exists()) {
			if($this->owner->ID != $existingMember->ID) {
				return false;
			}
		}
		return true;
	}
	
}