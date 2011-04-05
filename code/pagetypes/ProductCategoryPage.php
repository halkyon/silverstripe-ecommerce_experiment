<?php
/**
 * CMS page-type that displays a number of related
 * {@link Product} items on a page allowing the
 * site user to browse for products.
 *
 * @package ecommerce
 * @subpackage pagetypes
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class ProductCategoryPage extends Page {
	
	public static $many_many = array(
		'Products' => 'Product'
	);

	public static $default_child = 'ProductCategoryPage';
	
	public static $icon = array();
	
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		$filterWhere = $this->getManyManyFilter('Products', 'Product');
		$filterJoin = $this->getManyManyJoin('Products', 'Product');
		$fields->addFieldToTab(
			'Root.Content.Products',
			new ComplexTableField(
				$this,
				'Products',
				'Product',
				Product::$summary_fields,
				'getCMSFields',
				$filterWhere,
				'',
				$filterJoin
			)
		);
		
		return $fields;
	}
	
}
class ProductCategoryPage_Controller extends Page_Controller {
	
}