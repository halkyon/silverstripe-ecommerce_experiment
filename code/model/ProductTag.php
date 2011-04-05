<?php
/**
 * Represents a single tag which has a number of
 * {@link Product} records related to it.
 *
 * @package ecommerce
 * @subpackage model
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class ProductTag extends DataObject {
	
	public static $db = array(
		'Name' => 'Varchar(100)'
	);
	
	public static $belongs_many_many = array(
		'Products' => 'Product'
	);
	
}
?>