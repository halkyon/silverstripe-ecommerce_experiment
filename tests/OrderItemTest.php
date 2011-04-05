<?php
/**
 * Tests for the {@link OrderItem} class.
 *
 * @package ecommerce
 * @subpackage tests
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class OrderItemTest extends SapphireTest {
	
	/**
	 * Test {@link OrderItem::__construct()} has set
	 * the correct default quantity.
	 */
	function testDefaultQuantity() {
		$item = new ProductVariation_OrderItem(array());
		$this->assertEquals(1, $item->getField('Quantity'), 'Item quantity is 1 (implicitly set)');
		
		$item = new ProductVariation_OrderItem(array(), 5);
		$this->assertEquals(5, $item->getField('Quantity'), 'Item quantity is 5');
	}
	
}