<?php
class OrderStatusLog extends DataObject {
	
	public static $db = array(
		'Name' => 'Varchar',
		'Note' => 'Text',
		'SentToCustomer' => 'Boolean'
	);
	
	public static $has_one = array(
		'Author' => 'Member',
		'Order' => 'Order'
	);
	
	public function onBeforeSave() {
		parent::onBeforeSave();
		if(!$this->ID) {
			$this->AuthorID = Member::currentUserID();
		}
	}
	
}