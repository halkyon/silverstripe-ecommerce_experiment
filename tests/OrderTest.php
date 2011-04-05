<?php
/**
 * Tests for the {@link Order} class.
 * 
 * @package ecommerce
 * @subpackage tests
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class OrderTest extends FunctionalTest {

	public static $fixture_file = 'ecommerce/tests/OrderTest.yml';
	
	/**
	 * Test {@link Order::Subtotal()}
	 */
	function testSubtotal() {
		$variation = $this->objFromFixture('ProductVariation', 'variation1');
		$this->get("cart/add/{$variation->ID}/{$variation->class}?quantity=3");
		$this->assertEquals(76.50, singleton('Order')->Subtotal(), 'The order subtotal is 76.50');
	}
	
	/**
	 * Test {@link Order::Total()}
	 */
	function testTotal() {
		$variation = $this->objFromFixture('ProductVariation', 'variation1');
		$this->get("cart/add/{$variation->ID}/{$variation->class}?quantity=3");
		$this->assertEquals(76.50, singleton('Order')->Subtotal(), 'The order subtotal is 76.50');
	}
	
	/**
	 * Test {@link Order::Items()}
	 */
	function testItems() {
		$var1 = $this->objFromFixture('ProductVariation', 'variation1');
		$var2 = $this->objFromFixture('ProductVariation', 'variation2');
		$this->get("cart/add/{$var1->ID}/{$var1->class}?quantity=3");
		$this->get("cart/add/{$var2->ID}/{$var2->class}");
		$items = singleton('Order')->Items();
		$itemsArr = $items->toArray();
		$this->assertEquals(2, $items->Count(), '2 items are in the items DataObjectSet');
		$this->assertEquals(3, $itemsArr[$var1->ID]->getField('Quantity'), 'variation1 item has a quantity of 3');
		$this->assertEquals(1, $itemsArr[$var2->ID]->getField('Quantity'), 'variation2 item has a quantity of 1');
	}
	
	/**
	 * Test {@link Order::Modifiers()}
	 */
	function testModifiers() {
		Order::add_modifier('TaxModifier');
		Order::add_modifier('SimpleShippingModifier');
		$modifiers = singleton('Order')->Modifiers();
		$modifiersArr = $modifiers->toArray();
		$this->assertEquals(2, $modifiers->Count(), '2 modifiers are in the modifiers DataObjectSet');
	}
	
	/**
	 * Test {@link Order::process()}
	 */
	function testProcess() {
		$var1 = $this->objFromFixture('ProductVariation', 'variation1');
		$var2 = $this->objFromFixture('ProductVariation', 'variation2');
		$member = $this->objFromFixture('Member', 'member1');
		$this->get("cart/add/{$var1->ID}/{$var1->class}?quantity=3");
		$this->get("cart/add/{$var2->ID}/{$var2->class}");
		$order = new Order();
		$this->assertType('DataObjectSet', $order->Items(), 'Items are in the cart, returned as a DataObjectSet');
		$this->assertType('DataObjectSet', $order->Modifiers(), 'Modifiers are in the cart, returned as a DataObjectSet');
		$order->process($member->ID);
		$this->assertType('ComponentSet', $order->Items(), 'Items are now a component set because they are in the DB');
		$this->assertType('ComponentSet', $order->Modifiers(), 'Modifiers are now a component set because they are in the DB');
		$this->assertEquals(104, $order->Subtotal());
		$this->assertEquals(104, $order->Total());
		$this->assertFalse($order->process($member->ID), 'The order in the DB cannot be processed again');
	}
	
}