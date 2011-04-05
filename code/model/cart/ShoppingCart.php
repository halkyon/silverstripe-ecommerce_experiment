<?php
/**
 * ShoppingCart manages {@link OrderItem} objects with
 * a backend - each OrderItem represents a link to
 * a product, e.g. {@link ProductVariation} that the user would
 * like to purchase.
 * 
 * By default, the backend stores cart items and data
 * in PHP session using {@link ShoppingCartSessionBackend}
 * Should you ever require a different storage solution,
 * {@link ShoppingCart::$backend_class} can be set to a
 * different class. For example, you could implement a
 * DataObject to hold the cart items and data instead.
 * 
 * @see Order class documentation for more information
 * on how the rest of the ecommerce core package interacts
 * with eachother.
 *	
 * @author Sean Harvey <sean at silverstripe dot com>
 * @package ecommerce
 * @subpackage cart
 */
class ShoppingCart {
	
	/**
	 * The class that performs storage of ShoppingCart data.
	 * @var string
	 */
	public static $backend_class = 'ShoppingCartSessionBackend';
	
	/**
	 * An instance of the backend class that performs
	 * data storage for ShoppingCart.
	 * 
	 * @var object
	 */
	protected $backend;
	
	public function __construct() {
		$backendClass = self::$backend_class;
		if(!class_exists($backendClass)) {
			user_error("ShoppingCart::__construct(): '{$backendClass}' does not exist", E_USER_ERROR);
		}
		
		$this->backend = new $backendClass();
	}
	
	/**
	 * Get all the {@link OrderItem} objects
	 * serialized in the cart.
	 *
	 * @return array
	 */
	public function getItems() {
		$items = $this->backend->getItems();
		if($items && is_array($items)) {
			foreach($items as $itemID => $serialObj) {
				if($serialObj == null) {
					unset($items[$itemID]);
					continue;
				}
				$items[$itemID] = unserialize($serialObj);
			}
		}
		return $items ? $items : array();
	}
	
	/**
	 * Add an {@link OrderItem} object into the cart.
	 * @param string|int The ID of the item
	 * @param object $orderItem OrderItem object to add
	 */
	public function addItem($itemID, OrderItem $item) {
		$this->backend->addItem($itemID, serialize($item));
	}
	
	/**
	 * Get a {@link OrderItem} object from the cart.
	 * @param int $itemID The ID of the item
	 * @return object|boolean OrderItem object or boolean FALSE not found
	 */
	public function getItem($itemID) {
		$items = $this->getItems();
		if(!$items) return false;
		return isset($items[$itemID]) ? $items[$itemID] : false;
	}
	
	/**
	 * Remove an {@link OrderItem} object from the cart.
	 * @param object|int $itemID The ID of the item
	 */
	public function removeItem($itemID) {
		$this->backend->removeItem($itemID);
	}
	
	/**
	 * Increment the quantity of an {@link OrderItem}
	 * object that already exists in the cart, replacing
	 * the existing object with an updated one.
	 *
	 * @param object|int $itemID The product ID or OrderItem object
	 * @param int $quantity The quantity to increment by
	 * @return boolean TRUE successfully incremented | FALSE item not found
	 */
	public function addItemQuantity($itemID, $quantity = 1) {
		$item = $this->getItem($itemID);
		if(!$item) return false;
		$currentQuantity = $item->getField('Quantity');
		$item->setField('Quantity', $currentQuantity + $quantity);
		$this->addItem($itemID, $item);
		return true;
	}
	
	/**
	 * Decrement the quantity of an {@link OrderItem}
	 * object that exists in the cart, replacing the
	 * existing object with an updated one. If the quantity
	 * falls below 1, the item is removed completely.
	 * 
	 * @param object|int $itemID The product ID or OrderItem object
	 * @param int $quantity The quantity to decrement by
	 * @return boolean TRUE successfully deducted | FALSE item not found
	 */
	public function deductItemQuantity($itemID, $quantity = 1) {
		$item = $this->getItem($itemID);
		if(!$item) return false;
		$currentQuantity = $item->getField('Quantity');
		$newQuantity = $currentQuantity - $quantity;
		if($newQuantity > 0) {
			$item->setField('Quantity', $newQuantity);
			$this->addItem($itemID, $item);
		} else {
			$this->removeItem($itemID);
		}
		return true;
	}
	
	/**
	 * Completely empties the cart of all information.
	 */
	public function emptyCart() {
		$this->backend->emptyCart();
	}
	
}
/**
 * Handles user interaction with the OrderItem
 * objects in {@link ShoppingCart} when the user
 * is browsing the site. For example, the user can
 * add or remove items that exist in their cart.
 * 
 * @author Sean Harvey <sean at silverstripe dot com>
 * @package ecommerce
 * @package cart
 */
class ShoppingCart_Controller extends Controller {
	
	public static $url_handlers = array(
		'$Action//$ID/$ClassName' => 'handleAction',
	);
	
	/**
	 * An instance of {@link ShoppingCart}
	 * that this controller will use.
	 * 
	 * @var object
	 */
	protected $cart;
	
	public function init() {
		parent::init();
		$this->cart = singleton('ShoppingCart');
	}
	
	/**
	 * Return the OrderItem objects from
	 * {@link ShoppingCart::getItems()} as
	 * a DataObjectSet.
	 * 
	 * @return object DataObjectSet
	 */
	public function Items() {
		return new DataObjectSet($this->cart->getItems());
	}
	
	/**
	 * Return the total price for all items
	 * in the cart. This is called Subtotal
	 * in the context of an Order, since Total
	 * would include shipping, taxes etc.
	 * 
	 * @return double
	 */
	public function Subtotal() {
		return singleton('Order')->Subtotal();
	}
	
	/**
	 * Add quantity to an item that exists in {@link ShoppingCart}.
	 * If the item doesn't exist, try to add a new item of the particular
	 * class given the URL parameters available.
	 */
	public function add($request) {
		$quantity = (isset($_GET['quantity'])) ? (int) $_GET['quantity'] : 1;
		$className = $request->param('ClassName');
		$productId = $request->param('ID');
		
		if($this->cart->getItem($productId)) {
			$this->cart->addItemQuantity($productId, $quantity);
		} else {
			if(!class_exists($className)) {
				return $this->redirectBack();
			}
			$record = DataObject::get_by_id($className, $productId);
			if(!($record && $record->exists())) {
				return $this->httpError(404, 'Item not found!');
			}
			
			if($record->canPurchase($quantity)) {
				$this->cart->addItem($record->ID, $record->createOrderItem($quantity));
			}
		}
		
		$this->redirectBack();
	}

	/**
	 * Deduct quantity from an item that exists in {@link ShoppingCart}
	 */
	public function deduct($request) {
		$quantity = (isset($_GET['quantity'])) ? (int) $_GET['quantity'] : 1;
		$this->cart->deductItemQuantity($request->param('ID'), $quantity);
		$this->redirectBack();
	}

	/**
	 * Completely remove an item that exists in {@link ShoppingCart}
	 */
	public function remove($request) {
		$this->cart->removeItem($request->param('ID'));
		$this->redirectBack();
	}

}