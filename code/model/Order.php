<?php
/**
 * Order is an aggregate of {@link OrderItem}
 * and {@link OrderModifier} objects. OrderItem
 * objects are derived from {@link ShoppingCart}
 * and are written to the DB once the customer
 * goes to checkout, with the {@link ShoppingCart}
 * being emptied.
 * 
 * Once the data is processed (written to the DB) the
 * order is then set in stone, and
 * on-the-fly calculations are no longer performed, as
 * seen in {@link OrderModifier::Amount()} and
 * {@link OrderItem::Amount()}.
 * 
 * Order does a few important calculations of its own:
 * 
 * - Subtotal: The total of all OrderItem amounts
 *   @see Order::Subtotal()
 * 
 * - Total: Subtotal with additions or deductions
 *   from the OrderModifier objects (e.g. taxes, shipping)
 *   @see Order::Total()
 * 
 * - TotalOutstanding: Total with {@link Payment} deductions
 *   from the customer to produce an outstanding amount
 *   owed to the seller.
 *   @see Order::TotalOutstanding()
 * 
 * @package ecommerce
 * @subpackage model
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class Order extends DataObject implements PermissionProvider {
	
	public static $db = array(
		'Status' => 'Varchar',
		'ShippingName' => 'Varchar',
		'ShippingAddress' => 'Text',
		'ShippingCity' => 'Varchar',
		'ShippingCountry' => 'Varchar'
	);
	
	public static $has_one = array(
		'Member' => 'Member'
	);
	
	public static $has_many = array(
		'Items' => 'OrderItem',
		'Modifiers' => 'OrderModifier',
		'Payments' => 'Payment',
		'StatusLogs' => 'OrderStatusLog'
	);
	
	public static $casting = array(
		'Subtotal' => 'Currency',
		'Total' => 'Currency',
		'TotalOutstanding' => 'Currency'
	);
	
	public static $defaults = array(
		'Status' => 'Unpaid'
	);
	
	/**
	 * A map of possible Order states and human
	 * readable text that describes them.
	 * 
	 * @return array
	 */
	public static $statuses = array(
		'Unpaid' => 'Order is unpaid, no payment received from customer yet',
		'Query' => 'Order is currently pending, awaiting query',
		'Paid' => 'Order is paid for by customer',
		'Processing' => 'Order is being processed',
		'Sent' => 'Order is sent to the customer',
		'Complete' => 'Order is completed',
		'AdminCancelled' => 'Order cancelled by the administrator',
		'CustomerCancelled' => 'Order cancelled by the customer'
	);

	/**
	 * Class that handles order processing. You can completely
	 * swap out the processor by changing this to a class of
	 * your own.
	 * 
	 * @var string
	 */
	public static $processor_class = 'OrderProcessor';
	
	/**
	 * An array of {@link OrderModifier} class names that
	 * are currently set up to be used on the site.
	 * {@link Order::add_modifier()} and
	 * {@link Order::remove_modifier()} are used to
	 * manipulate this array.
	 * 
	 * @var array
	 */
	protected static $modifiers = array();
	
	/**
	 * Add a {@link OrderModifier} class name to the array
	 * of enabled modifiers for this site.
	 * 
	 * @param string $class Class name of an OrderModifier to add
	 */
	public static function add_modifier($class) {
		if(!is_subclass_of($class, 'OrderModifier')) {
			user_error("Order::add_modifier(): '$class' is not a valid subclass of OrderModifier", E_USER_ERROR);
		}
		if(!in_array($class, self::$modifiers)) {
			self::$modifiers[] = $class;
		}
	}
	
	/**
	 * Remove an {@link OrderModifier} class name from the
	 * array of enabled modifiers for this site.
	 * 
	 * @param string $class Class name of an OrderModifier to remove
	 */
	public static function remove_modifier($class) {
		foreach(self::$modifiers as $index => $modifierClass) {
			if($modifierClass == $class) {
				unset(self::$modifiers[$index]);
			}
		}
	}
	
	/**
	 * Fields that {@link OrderForm} uses to determine what
	 * shipping form fields should map to the Order DB.
	 * 
	 * @return object CompositeField with FormField objects
	 */
	public function shippingDetailsFields() {
		return new CompositeField(array(
			new HeaderField('ShippingHeading', 'Shipping details', 3),
			new TextField('ShippingName', 'Name'),
			new TextareaField('ShippingAddress', 'Address', 2),
			new TextField('ShippingCity', 'City'),
			new TextField('ShippingCountry', 'Country')
		));
	}
	
	/**
	 * Set up permission codes for use with the {@link Permission}
	 * class. This means that we can have fine grain permissions
	 * set up for member groups adding, editing and deleting an order.
	 * 
	 * @return array
	 */
	public function providePermissions() {
		return array(
			'ORDER_VIEW' => _t('Order.VIEW', 'View an order'),
			'ORDER_ADD' => _t('Order.ADD', 'Add an order'),
			'ORDER_EDIT' => _t('Order.EDIT', 'Edit an order'),
			'ORDER_DELETE' => _t('Order.DELETE', 'Delete an order')
		);
	}

	/**
	 * Check if the logged in member has permission
	 * to view this order. Let members logged in view their
	 * own orders. Otherwise, fall back to checking for the
	 * ORDER_VIEW permission code.
	 * 
	 * @param object $member Member to check for view permission
	 * @return boolean
	 */
	public function canView($member = null) {
		$memberID = ($member && is_object($member)) ? $member->ID : Member::currentUserID();
		if($this->MemberID == $memberID) {
			return true;
		}
		return Permission::check('ORDER_VIEW', 'any', $member);
	}
	
	/**
	 * Does the logged in member have permission to
	 * add an order?
	 * 
	 * @param object $member Member to check for add permission
	 * @return boolean
	 */
	public function canAdd($member = null) {
		return Permission::check('ORDER_ADD', 'any', $member);
	}
	
	/**
	 * Does the logged in member have permission to
	 * edit an order?
	 * 
	 * @param object $member Member to check for edit permission
	 * @return boolean
	 */
	public function canEdit($member = null) {
		return Permission::check('ORDER_EDIT', 'any', $member);
	}

	/**
	 * Does the logged in member have permission to
	 * delete an order?
	 * 
	 * @param object $member Member to check for delete permission
	 * @return boolean
	 */
	public function canDelete($member = null) {
		return Permission::check('ORDER_DELETE', 'any', $member);
	}
	
	/**
	 * Can the customer cancel their order?
	 * If they haven't paid yet, we assume they can.
	 *
	 * @return boolean
	 */
	public function canCancel() {
		return ($this->isPaid()) ? false : true;
	}
	
	/**
	 * Is the order paid for?
	 * @return boolean
	 */
	public function isPaid() {
		$statuses = array('Paid', 'Processing', 'Sent', 'Complete');
		return (in_array($this->Status, $statuses)) ? true : false;
	}
	
	/**
	 * Is the order being processed?
	 * @return boolean
	 */
	public function isProcessing() {
		return ($this->Status == 'Processing') ? true : false;
	}
	
	/**
	 * Is the order sent yet?
	 * @return boolean
	 */
 	public function isSent() {
		$statuses = array('Sent', 'Complete');
		return (in_array($this->Status, $statuses)) ? true : false;
	}
	
	/**
	 * Is the order complete?
	 * @return boolean
	 */
	public function isComplete() {
		return ($this->Status == 'Complete') ? true : false;
	}
	
	/**
	 * Find the country that this order is being shipped to.
	 * @return string|null string country found | NULL not found
	 */
	public function findShippingCountry() {
		user_error('Order::findShippingCountry() is not implemented yet', E_USER_ERROR);
	}
	
	/**
	 * Process the current {@link OrderItem} and
	 * {@link OrderModifier} objects in the customers
	 * {@link ShoppingCart} writing them to the DB.
	 * 
	 * @param string|int $memberID Member ID who placed the order
	 * @return object OrderProcess_Result object encapsulating results
	 */
	public function process($memberID) {
		if($this->ID) return false;
		$processor = new self::$processor_class($this, $memberID);
		return $processor->process();
	}
	
	/**
	 * Overload the Items relation getter to retrieve
	 * {@link OrderItem} objects stored in
	 * {@link ShoppingCart} if this order has not been
	 * written to the DB yet.
	 * 
	 * @return object DataObjectSet|ComponentSet
	 */
	public function Items() {
		if($this->ID) return $this->getComponents('Items');
		return new DataObjectSet(singleton('ShoppingCart')->getItems());
	}
	
	/**
	 * Overload the Modifiers relation getter to retrieve
	 * {@link OrderModifier} objects configured to be used
	 * if this order has not been written to the DB yet.
	 *
	 * @return object DataObjectSet|ComponentSet
	 */
	public function Modifiers() {
		if($this->ID) return $this->getComponents('Modifiers');
		$modifiers = array();
		foreach(self::$modifiers as $modifier) {
			$modifiers[] = new $modifier();
		}
		return new DataObjectSet($modifiers);
	}
	
	/**
	 * Overload the Subtotal field getter to return the
	 * total price for all {@link OrderItem} objects
	 * related to this order.
	 * 
	 * @return double
	 */
	public function Subtotal() {
		$amount = 0.00;
		$items = $this->Items();
		if($items) foreach($items as $item) {
			$amount = $item->updateAmount($amount);
		}
		return $amount;
	}
	
	/**
	 * Overload the total field getter to return the
	 * subtotal price of the order, with {@link OrderModifier}
	 * objects related added into the mix.
	 * 
	 * @return double
	 */
	public function Total() {
		$amount = $this->Subtotal();
		$modifiers = $this->Modifiers();
		if($modifiers) foreach($modifiers as $modifier) {
			$amount = $modifier->updateAmount($amount);
		}
		return $amount;
	}
	
	/**
	 * Return the total amount after all successful
	 * payments have been deducted.
	 * 
	 * @return double
	 */
	public function TotalOutstanding() {
		$total = $this->Total();
		$payments = $this->getComponents('Payments');
		if($payments) foreach($payments as $payment) {
			// updateAmount does all the work for us, inc. checking if payment was successful
			$total = $payment->updateAmount($total);
		}
		return $total;
	}
	
	/**
	 * Return a relative link to the order.
	 * @param string $action Action method (default is "show")
	 * @return string URL of order
	 */
	public function Link($action = 'show') {
		return "order/$action/{$this->ID}";
	}

	/**
	 * Return a title for this order. This would be
	 * used in templates, such as the order summary
	 * the user sees after they have placed their order.
	 * 
	 * @return string
	 */
	public function Title() {
		return _t('Order.TITLE', 'Order');
	}
	
}
/**
 * Handles user interaction with {@link Order} objects.
 * 
 * @package ecommerce
 * @package control
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class Order_Controller extends ContentController {
	
	public static $url_handlers = array(
		'$Action//$ID/$OtherID' => 'handleAction',
	);
	
	/**
	 * The {@link Form} subclass that handles an order form.
	 * You can completely swap out the default OrderForm
	 * by setting this to a different class name of your own.
	 * 
	 * @var string
	 */
	public static $order_form_class = 'OrderForm';
	
	/**
	 * The default view, showing a form allowing the
	 * customer to enter their details and place their
	 * order through {@link Order_Controller::Form()}
	 */
	public function index($request) {
		return $this->customise(singleton('Order'))->renderWith(array('Order', 'Page'));
	}
	
	/**
	 * Show a single {@link Order}. This is what the customer
	 * would see after they have placed their order.
	 */
	public function show($request) {
		$order = DataObject::get_by_id('Order', $request->param('ID'));
		if(!($order && $order->exists())) {
			return $this->httpError(404, 'Order not found!');
		}
		
		if(!$order->canView()) {
			return Security::permissionFailure($this, _t('Order.CANTVIEW', 'You cannot view that order.'));
		}
		
		return $this->customise($order)->renderWith(array('Order', 'Page'));
	}
	
	/**
	 * Construct and return the {@link Form} class set
	 * in {@link Order_Controller::$order_form_class}
	 * 
	 * @return object Form object
	 */
	public function Form() {
		$form = new self::$order_form_class($this, 'Form');
		$form->setHTMLID('OrderForm');
		return $form;
	}
	
}