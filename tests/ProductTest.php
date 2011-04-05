<?php
/**
 * Tests the {@link Product}, {@link ProductVariation}
 * and {@link ProductVariation_OrderItem} classes.
 * 
 * @package ecommerce
 * @subpackage tests
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class ProductTest extends SapphireTest {
	
	public static $fixture_file = 'ecommerce/tests/ProductTest.yml';

	/**
	 * Test URLSegment field generation that occurs
	 * on {@link Product::onBeforeWrite()}
	 */
	public function testUrlSegmentGeneration() {
		$product = $this->objFromFixture('Product', 'prod1');
		$product2 = $this->objFromFixture('Product', 'prod2');
		
		$product->write();
		$this->assertEquals(
			't-shirt-with-a-lovely-flower-on-it-special-',
			$product->getField('URLSegment'),
			'Correct URL segment is generated'
		);
		
		$product2->setField('Title', 'T-Shirt with a lovely flower on it (SPECIAL)');
		$product2->write();
		$this->assertEquals(
			't-shirt-with-a-lovely-flower-on-it-special--1',
			$product2->getField('URLSegment'),
			'The title is the same as another product, so -1 is appended to the URL segment'
		);
		
		$product2->write();
		$this->assertEquals(
			't-shirt-with-a-lovely-flower-on-it-special--1',
			$product2->getField('URLSegment'),
			'Even writing it again, it persists to be the same'
		);
		
		$product->write();
		$this->assertEquals(
			't-shirt-with-a-lovely-flower-on-it-special-',
			$product->getField('URLSegment'),
			'Even writing it again, it persists to be the same'
		);
	}

	/**
	 * Test that {@link ProductVariation} implements
	 * the "Purchasable" interface properly.
	 */
	public function testVariationImplementsPurchasable() {
		$classes = ClassInfo::implementorsOf('Purchasable');
		$this->assertType('array', $classes);
		$this->assertTrue(in_array('ProductVariation', $classes));
	}
	
	/**
	 * Test getting the related ProductVariation components,
	 * and asserting the number of variations we have.
	 */
	public function testVariationsCount() {
		$product = $this->objFromFixture('Product', 'prod1');
		$this->assertEquals(2, $product->getComponents('Variations')->Count(), 'The product has 2 variations');
	}

	/**
	 * Test that the variation can correctly find it's
	 * related {@link Product} record by peeking into
	 * the has one relation to it.
	 */
	public function testVariationHasCorrectProduct() {
		$variation = $this->objFromFixture('ProductVariation', 'variation1');
		$productID = $this->idFromFixture('Product', 'prod1');
		$this->assertEquals($productID, $variation->getComponent('Product')->ID, 'The parent product is correctly linked');
	}
	
	/**
	 * Test {@link ProductVariation::getUnitPrice()}
	 * and {@link ProductVariation_OrderItem::getUnitPrice()}
	 */
	public function testVariationUnitPrice() {
		$variation = $this->objFromFixture('ProductVariation', 'variation1');
		$orderItem = $variation->createOrderItem();
		$this->assertEquals('25.50', $variation->getUnitPrice(), 'The unit price is correctly returned');
		$this->assertEquals('25.50', $orderItem->getUnitPrice(), 'The order item refers to the variation for the unit price');
	}
	
	/**
	 * Test {@link ProductVariation::canPurchase()}
	 * which checks the quantity level of the variation.
	 */
	public function testVariationCanPurchase() {
		$variation = $this->objFromFixture('ProductVariation', 'variation1');
		$this->assertTrue($variation->canPurchase(1), 'The variation can be purchased');
		$variation->setField('Quantity', 0);
		$this->assertFalse($variation->canPurchase(1), 'The quantity is at zero - can\'t purchase');
	}
	
	/**
	 * Test {@link ProductVariation_OrderItem::getInternalProduct()}
	 */
	public function testOrderItemInternalProductID() {
		$variation = $this->objFromFixture('ProductVariation', 'variation2');
		$orderItem = $variation->createOrderItem();
		$this->assertEquals($variation->ID, $orderItem->getInternalProduct()->ID, 'The internal product ID is the same');
	}
	
}