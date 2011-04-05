<?php
/**
 * Tests for the {@link ShoppingCart} class.
 * @todo Write functional tests for {@link ShoppingCart_Controller}
 *
 * @package ecommerce
 * @subpackage tests
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class ShoppingCartTest extends FunctionalTest {
	
	/**
	 * Create a {@link ShoppingCart} object and give
	 * it some test {@link OrderItem} objects.
	 *
	 * @return object ShoppingCart
	 */
	public static function createCartWithItems() {
		$item1 = new ProductVariation_OrderItem(array(
			'ProductVariationID' => 2,
			'Version' => 1
		));
		
		$item2 = new ProductVariation_OrderItem(array(
			'ProductVariationID' => 1,
			'Version' => 1
		));
		
		$cart = new ShoppingCart();
		$cart->addItem(2, $item1);
		$cart->addItem(1, $item2);
		
		return $cart;
	}
	
	/**
	 * Test {@link ShoppingCart::getItems()}
	 */
	public function testGetItems() {
		$cart = $this->createCartWithItems();
		
		$this->assertType('array', $items = $cart->getItems(), 'The items are an array');
		$this->assertType('object', $items[2], 'Product 2 in the array is an object');
		$this->assertEquals(2, count($cart->getItems()), '2 items are in the cart');
		$this->assertType('object', $cart->getItem(2), 'The item is an object');
		$this->assertType('object', $cart->getItem(1), 'The item is an object');
		$this->assertFalse($cart->getItem(3243), 'Item doesn\'t exist - FALSE returned');
	}

	/**
	 * Test {@link ShoppingCart::removeItem()}
	 */
	public function testRemoveItems() {
		$cart = $this->createCartWithItems();
		
		$this->assertEquals(2, count($cart->getItems()), '2 items are in the cart');
		$cart->removeItem(2);
		$this->assertEquals(1, count($cart->getItems()), '1 item is in the cart');
		$this->assertType('object', $cart->getItem(1), 'The item is an object');
		$this->assertType('array', $cart->getItems(), 'The items are an array');
		$this->assertEquals(1, count($cart->getItems()), 'There is 1 item in the cart');
		$cart->removeItem(1);
		$this->assertEquals(0, count($cart->getItems()), 'No items in the cart');
		$this->assertFalse($cart->getItem(1), 'The item can\'t be found, it has been removed');
	}

	/**
	 * Test {@link ShoppingCart::addItemQuantity()}
	 */
	public function testAddItemQuantity() {
		$cart = $this->createCartWithItems();
		
		$this->assertTrue($cart->addItemQuantity(2, 5), 'Addition of quantity using product ID');
		$this->assertType('object', $item = $cart->getItem(2), 'The item is an object');
		$this->assertEquals(6, $item->getField('Quantity'), 'The item has a quantity of 6');
		$this->assertTrue($cart->addItemQuantity(2, 2), 'Addition of quantity using OrderItem object');
		$this->assertType('object', $item = $cart->getItem(2), 'The item is an object');
		$this->assertEquals(8, $item->getField('Quantity'), 'The item has a quantity of 8');
	}

	/**
	 * Test {@link ShoppingCart::deductItemQuantity()}
	 */
	public function testDeductItemQuantity() {
		$cart = $this->createCartWithItems();
		
		$this->assertTrue($cart->addItemQuantity(2, 5), 'Addition of more quantity successful');
		$this->assertTrue($cart->deductItemQuantity(2, 3), 'Deduction of quantity successful');
		$this->assertFalse($cart->deductItemQuantity(9, 2), 'The item doesn\'t exist, FALSE returned');
		$this->assertType('object', $item = $cart->getItem(2), 'The item is an object');
		$this->assertEquals(3, $item->getField('Quantity'), 'The item has a quantity of 3');
		$this->assertTrue($cart->deductItemQuantity(2, 5), 'Deduction of quantity successful');
		$this->assertFalse($cart->getItem(2), 'An item can\'t have a quantity less than 1, so the item is removed');
	}
	
	/**
	 * Test {@link ShoppingCart::emptyCart()}
	 */
	public function testEmptyCart() {
		$cart = $this->createCartWithItems();
		
		$cart->emptyCart();
		$this->assertEquals(0, count($cart->getItems()), 'No items in the cart');
	}
	
}