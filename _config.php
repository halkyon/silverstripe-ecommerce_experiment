<?php
define('ECOMMERCE_DIR', 'ecommerce');

Director::addRules(50, array(
	'product' => 'Product_Controller',
	'cart' => 'ShoppingCart_Controller',
	'order' => 'Order_Controller'
));

Object::add_extension('Page', 'ShoppingCartPageExtension');
