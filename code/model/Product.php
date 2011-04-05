<?php
/**
 * A product is made up of {@link ProductVariation} which
 * are essentially the product but with different attributes.
 * For example, if you were selling t-shirts, a single t-shirt
 * could have many variations - colours and sizes.
 *
 * Each product belongs to one or more {@link ProductCategoryPage},
 * this is how they are shown on pages to the site user.
 * 
 * @package ecommerce
 * @subpackage model
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class Product extends DataObject implements PermissionProvider {
	
	public static $db = array(
		'Title' => 'Varchar(100)',
		'MenuTitle' => 'Varchar(100)',
		'URLSegment' => 'Varchar(100)',
		'Content' => 'HTMLText'
	);
	
	public static $has_many = array(
		'Variations' => 'ProductVariation'
	);
	
	public static $many_many = array(
		'Tags' => 'ProductTag'
	);
	
	public static $belongs_many_many = array(
		'Categories' => 'ProductCategoryPage'
	);
	
	public static $searchable_fields = array(
		'Title',
		'Content'
	);
	
	/**
	 * Return a single {@link Product} record by it's
	 * URLSegment field value. This will also ensure the
	 * value is SQL safe by adding slashes.
	 *
	 * @param string $segment The URLSegment value to get one by
	 * @return object|boolean Product object or boolean FALSE
	 */
	public static function get_by_urlsegment($segment) {
		if($segment && is_string($segment)) {
			$SQL_segment = Convert::raw2sql($segment);
			return DataObject::get_one(__CLASS__, "URLSegment = '$SQL_segment'");
		}
		return false;
	}
	
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		
		$t = strtolower($this->getField('Title'));
		$t = str_replace('&amp;', '-and-', $t);
		$t = str_replace('&', '-and-', $t);
		$t = ereg_replace('[^A-Za-z0-9]+', '-', $t);
		$t = ereg_replace('-+', '-', $t);
		if(!$t) {
			$t = "product-$this->ID";
		}
		
		$count = 1;
		while(DataObject::get_one('Product', "URLSegment = '$t' AND Product.ID != '$this->ID'")) {
			$t = ereg_replace('-[0-9]+$', '', $t) . "-$count";
			$count++;
		}
		
		$this->setField('URLSegment', $t);
	}
	
	/**
	 * Set up permission codes for use with the {@link Permission}
	 * class. This means that we can have fine grain permissions
	 * set up for member groups adding, editing and deleting a product.
	 * 
	 * @return array
	 */
	public function providePermissions() {
		return array(
			'PRODUCT_ADD' => _t('Product.ADD', 'Add a product'),
			'PRODUCT_EDIT' => _t('Product.EDIT', 'Edit a product'),
			'PRODUCT_DELETE' => _t('Product.DELETE', 'Delete a product')
		);
	}
	
	/**
	 * Usually anyone can view a product. However, if for
	 * some reason you require security on viewing products,
	 * such as a member being logged in and having a particular
	 * permission code, then this could be overloaded on
	 * a {@link DataObjectDecorator} applied to this class.
	 * 
	 * @return boolean
	 */
	public function canView() {
		return true;
	}
	
	/**
	 * Does the logged in member have permission to
	 * add a product?
	 * 
	 * @param object $member Member to check for add permission
	 * @return boolean
	 */
	public function canAdd($member = null) {
		return Permission::check('PRODUCT_ADD', 'any', $member);
	}
	
	/**
	 * Does the logged in member have permission to
	 * edit a product?
	 * 
	 * @param object $member Member to check for edit permission
	 * @return boolean
	 */
	public function canEdit($member = null) {
		return Permission::check('PRODUCT_EDIT', 'any', $member);
	}
	
	/**
	 * Does the logged in member have permission to
	 * delete a product?
	 * 
	 * @param object $member Member to check for delete permission
	 * @return boolean
	 */
	public function canDelete($member = null) {
		return Permission::check('PRODUCT_DELETE', 'any', $member);
	}
	
	/**
	 * Return a relative link to the product.
	 * @param string $action Action method (default is "show")
	 * @return string URL of product
	 */
	public function Link($action = 'show') {
		return "product/$action/{$this->URLSegment}";
	}
	
	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->replaceField('URLSegment', new ReadonlyField('URLSegment', _t('Product.URLSEGMENT', 'URL segment for product')));

		$fields->removeByName('Categories');
		$fields->addFieldToTab('Root.Main', new TreeMultiselectField('Categories', _t('Product.CATEGORIES', 'Categories this product belongs to'), 'ProductCategoryPage'), 'Content');

		$fields->removeByName('Variations');

		if($this->ID) {
			$summaryFields = ProductVariation::$summary_fields;
			$fieldTypes = ProductVariation::$field_types;
			$variationsTable = new TableField(
				'Variations',
				'ProductVariation',
				$summaryFields,
				$fieldTypes,
				'ProductID',
				$this->ID
			);
			
			$variationsTable->setExtraData(array(
				'ProductID' => $this->ID
			));
			
			$fields->addFieldToTab('Root.Main', new HeaderField(_t('Product.PRODVARIATIONS', 'Product variations'), 3), 'Content');
			$fields->addFieldToTab('Root.Main', $variationsTable, 'Content');
		}
		
		return $fields;
	}
	
}
class Product_Controller extends ContentController {
	
	public static $url_handlers = array(
		'$Action//$ID/$OtherID' => 'handleAction',
	);
	
	public function show($request) {
		$product = Product::get_by_urlsegment($request->param('ID'));
		if(!($product && $product->exists())) {
			return $this->httpError(404, 'Product not found!');
		}
		
		if(!$product->canView()) {
			return Security::permissionFailure($this, _t('Product.CANTVIEW', 'You do not have permission view that product.'));
		}
		
		return $this->customise($product)->renderWith(array(
			$product->class,
			'Page'
		));
	}
	
}