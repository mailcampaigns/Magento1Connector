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

class MailCampaigns_SynchronizeContacts_Block_Footer extends Mage_Core_Block_Template {

	public function getTrackingCode() {
		$store_id = Mage::app()->getStore()->getId();
		$customer_id = 0;
		$visitor_id = 0;
		$product_id = 0;
		
		$mailcampaigns_tracking_code = Mage::getStoreConfig('mailcampaigns/mailcampaigns_tracking_code/mailcampaigns_tracking_code',$store_id);
		if ($mailcampaigns_tracking_code == 1)
		{
			$api_key 		= Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_key', $store_id); 
			$visitor_data	= Mage::getModel('core/session')->getVisitorData();
			
			if(isset($visitor_data) && is_array($visitor_data)){
				if ($visitor_data["visitor_id"] > 0){
					$visitor_id = $visitor_data["visitor_id"];
				} else{
					$visitor_id = 0;
				}
				$customer_id = Mage::getSingleton('customer/session')->getCustomerId();
			}
			
			$product_id	= 0; 
			if(Mage::registry('current_product')) 
			{ 
				$product_id = Mage::app()->getRequest()->getParam('id'); 
			} 
				
			// build post data
			$postdata = array(
				'store_id' => (int)$store_id,
				'customer_id' => (int)$customer_id,
				'visitor_id' => (int)$visitor_id,
				'product_id' => (int)$product_id
			);
	
			// call tracking javascript code
			return '<script type="text/javascript" src="//interface.mailcampaigns.nl/w/'.$api_key.'/'.base64_encode(json_encode($postdata)).'"></script>';
		}
	}
	
}