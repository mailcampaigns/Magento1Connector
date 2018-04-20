<?php
/**
 * MailCampaigns Connector
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Magento extension
 * @package    MailCampaigns
 * @copyright  Copyright (c) 2016 MailCampaigns. (http://www.mailcampaigns.nl)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
?>
<?php

class MailCampaigns_SynchronizeContacts_Block_Success extends Mage_Core_Block_Template {

	public function getTrackingCode() {

		$store_id = Mage::app()->getStore()->getId();
		
		$mailcampaigns_tracking_code_order_success = Mage::getStoreConfig('mailcampaigns/mailcampaigns_tracking_code/mailcampaigns_tracking_code_order_success',$store_id);
		if ($mailcampaigns_tracking_code_order_success == 1)
		{
			$visitor_data	= Mage::getModel('core/session')->getVisitorData();
			
			if ($visitor_data["visitor_id"] > 0) $visitor_id 	= $visitor_data["visitor_id"]; 	else $visitor_id 		= 0;
			if ($visitor_data["quote_id"] > 0) 	$quote_id 	= $visitor_data["quote_id"]; 	else $quote_id 		= 0;
			
			$customer_id 	= Mage::getSingleton('customer/session')->getCustomerId();
			$tmp_order_id 	= Mage::getSingleton('checkout/session')->getLastRealOrderId();
			$order			= Mage::getModel('sales/order')->loadByIncrementId($tmp_order_id);
			$order_data		= $order->getData();
			$order_id 		= $order->getId();
			if ($order_data["quote_id"] > 0) 	$quote_id 	= $order_data["quote_id"]; 	else $quote_id 		= 0;

			$api_key 		= Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_key', $store_id); 
		
			// build post data
			$postdata = array(
				'store_id' => $store_id,
				'customer_id' => $customer_id,
				'visitor_id' => $visitor_id,
				'quote_id' => $quote_id,
				'order_id' => $order_id,
			);
	
			// call tracking javascript code
			return '<script type="text/javascript" src="//interface.mailcampaigns.nl/w/'.$api_key.'/'.base64_encode(json_encode($postdata)).'"></script>';
		}
	}
	
}