<?php
/**
 * Order processing handler which handles processing
 * an order by writing the {@link OrderItem}, {@link OrderModifier}
 * objects, linking them to the newly written {@link Order} object.
 * 
 * @package ecommerce
 * @subpackage model
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class OrderProcessor {

	/**
	 * {@link Order} object to be processed.
	 * @var object
	 */
	protected $order;
	
	/**
	 * {@link DataObjectSet} containing
	 * {@link OrderItem} objects that make up
	 * the items to process with the order.
	 * 
	 * @var object
	 */
	protected $items;

	/**
	 * {@link DataObjectSet} containing
	 * {@link OrderModifier} objects to process
	 * with the order.
	 * 
	 * @var object
	 */
	protected $modifiers;
	
	/**
	 * {@link Member} object of the member who is
	 * processing the order.
	 * 
	 * @var object
	 */
	protected $member;
	
	/**
	 * A map of {@link OrderItem} objects that couldn't
	 * be purchased, indexed by the product ID.
	 * 
	 * @var array
	 */
	protected $itemsCantPurchase = array();
	
	public function __construct(Order $order, $memberID) {
		$this->order = $order;
		$this->items = $this->order->Items();
		$this->modifiers = $this->order->Modifiers();
		$this->member = $this->getMember($memberID);
	}
	
	public function process() {
		if(!$this->hasItems()) {
			throw new Exception('No items to process!');
		}
		if(!$this->canPurchaseAtLeastOneItem()) {
			throw new Exception('No items to process are allowed to be purchased!');
		}
		
		$this->order->setField('MemberID', $this->member->ID);
		$this->order->write();
		
		$this->writeItems();
		$this->writeModifiers();
		
		$result = new OrderProcessor_Result($this->order);
		$result->setItemsCantPurchase($this->itemsCantPurchase);
		return $result;
	}
	
	protected function getMember($memberID) {
		$member = DataObject::get_by_id('Member', (int) $memberID);
		if(!($member && $member->exists())) {
			throw new Exception("Member ID '{$memberID}' does not exist!");
		}
		return $member;
	}
	
	protected function hasItems() {
		$items = $this->items;
		return ($items && $items->Count() > 0) ? true : false;
	}
	
	protected function canPurchaseAtLeastOneItem() {
		foreach($this->items as $item) {
			if($item->canPurchase()) {
				return true;
			}
		}
		return false;
	}
	
	protected function writeItems() {
		foreach($this->items as $item) {
			if(!$item->canPurchase()) {
				$this->cantPurchaseItems[$item->getInternalProduct()->ID] = $item;
				continue;
			}
			$item->setField('OrderID', $this->order->ID);
			$item->write();
		}
	}
	
	protected function writeModifiers() {
		$modifiers = $this->modifiers;
		if($modifiers) foreach($modifiers as $modifier) {
			$modifier->setField('OrderID', $this->order->ID);
			$modifier->write();
		}
	}
	
}
/**
 * Encapsulation of {@link OrderProcessor} results after
 * {@link OrderProcessor::process()} is called. This is
 * useful for interrogating how many items were purchased,
 * or if there were some that couldn't be processed during
 * the process operation - for example, stock may have not
 * been available at the time of ordering, but was when the
 * item was added to the cart.
 * 
 * @package ecommerce
 * @subpackage model
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class OrderProcessor_Result {
	
	/**
	 * {@link Order} object that was processed.
	 * @var object
	 */
	protected $order;
	
	/**
	 * A map of {@link OrderItem} objects that couldn't
	 * be purchased, indexed by the product ID.
	 * 
	 * @var array
	 */
	protected $itemsCantPurchase = array();
	
	public function __construct(Order $order) {
		$this->order = $order;
	}
	
	public function setItemsCantPurchase(array $items) {
		$this->itemsCantPurchase = $items;
	}
	
	public function getItemsCantPurchase() {
		return $this->itemsCantPurchase;
	}
	
	public function getItemCount() {
		return $this->order->Items()->Count();
	}
	
}