<?php
/**
 * CMS page-type that holds {@link ProductCategoryPage}
 * items as child pages.
 * 
 * @package ecommerce
 * @subpackage pagetypes
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class ProductHolderPage extends Page {
	
	public static $icon = array();
	
	public static $default_child = 'ProductCategoryPage';
	
}
class ProductHolderPage_Controller extends Page_Controller {
	
}