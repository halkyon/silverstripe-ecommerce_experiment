<?php
/**
 * Allow the customer to place their {@link Order} by
 * filling out the fields on this form and submitting.
 * 
 * @todo Use multiform module to display contact and
 * shipping details on first step, order details (inc
 * shipping and taxes, totals etc) on the second step,
 * as well as fields allowing the customer to pay.
 * 
 * @package ecommerce
 * @subpackage forms
 * @author Sean Harvey <sean at silverstripe dot com>
 */
class OrderForm extends Form {
	
	/**
	 * @param object $controller Controller instance
	 * @param string $name The form name
	 */
	public function __construct($controller, $name) {
		$member = Member::currentUserID() ? Member::currentUser() : new Member();
		$order = singleton('Order');
		
		$fields = new FieldSet(
			$billingFields = $member->billingDetailsFields(),
			$shippingFields = $order->shippingDetailsFields()
		);
		
		$fields->merge(Payment::combined_form_fields($order->Total()));
		
		$actions = new FieldSet(
			new FormAction('doProcess', _t('OrderForm.PLACEORDER', 'Place order and make payment'))
		);
		
		parent::__construct($controller, $name, $fields, $actions);
		
		if($member->exists()) {
			$this->loadDataFrom($member);
		}
	}
	
	public function doProcess($data, $form, $request) {
		$order = new Order();
		$items = $order->Items();
		$member = Member::currentUserID() ? Member::currentUser() : new Member();
		$paymentClass = isset($data['PaymentMethod']) ? $data['PaymentMethod'] : null;
		$payment = class_exists($paymentClass) ? new $paymentClass() : null;
		$requirePayment = ($order->Subtotal() > 0) ? true : false;
		
		if(!($items && $items->Count() > 0)) {
			$form->sessionMessage(
				_t('OrderForm.NOITEMS', 'Error placing order: You have no items in your cart.'),
				'bad'
			);
			return Director::redirectBack();
		}

		if($requirePayment) {
			if(!($payment && $payment instanceof Payment)) {
				user_error(
					"OrderForm::doProcess(): '$paymentClass' is not a valid payment class!",
					E_USER_ERROR
				);
			}
		}

		// Ensure existing members don't get their record hijacked (IMPORTANT!)
		if(!$member->checkUniqueFieldValue($data)) {
			$uniqueField = Member::get_unique_identifier_field();
			$uniqueValue = $data[$uniqueField];
			$uniqueError = "Error placing order: The %s \"%d\" is
				already taken by another member. If this belongs to you, please
				log in first before placing your order.";
			$form->sessionMessage(
				_t(
					'EcommerceMemberExtension.ALREADYEXISTS',
					printf($uniqueError, strtolower($uniqueField), $uniqueValue),
					PR_MEDIUM,
					'Let the user know that member already exists (e.g. %s could be "Email", %d could be "joe@somewhere.com)'
				),
				'bad'
			);
			return Director::redirectBack();
		}
		
		$form->saveInto($member);
		if(!$member->Password) {
			$member->setField('Password', Member::create_new_password());
		}
		$member->write();
		
		$form->saveInto($order);
		try {
			$result = $order->process($member->ID);
		} catch(Exception $e) {
			$form->sessionMessage(
				_t(
					'OrderForm.PROCESSERROR',
					"An error occurred while placing your order: {$e->getMessage()}.<br>
					Please contact the website administrator."
				),
				'bad'
			);
			
			// Send an email to site admin with $e->getMessage() error
			
			return Director::redirectBack();
		}
		
		if($requirePayment) {
			$form->saveInto($payment);
			$payment->write();
			$result = $payment->processPayment($data, $form);
			
			if($result->isSuccess()) {
				$order->sendReceipt();
			}
			
			// Long payment process. e.g. user goes to external site to pay (PayPal, WorldPay)
			if($result->isProcessing()) {
				return $result->getValue();
			}
		}
		
		Director::redirect($order->Link());
	}
	
}