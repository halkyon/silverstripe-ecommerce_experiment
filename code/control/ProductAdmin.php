<?php
/**
 * CMS admin interface {@link ModelAdmin} for
 * managing {@link Product} records.
 *
 * @package ecommerce
 * @subpackage control
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class ProductAdmin extends ModelAdmin {

	public static $url_segment = 'products';
	
	public static $menu_title = 'Products';
	
	public static $managed_models = array(
		'Product'
	);
	
}