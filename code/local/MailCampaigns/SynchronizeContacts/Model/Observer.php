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

class MailCampaigns_SynchronizeContacts_Model_Observer
{
	public $version = '1.4.1';
	
	public function ProcessCrons()
	{
		$this->ProcessAPIQueue();
		$this->ImportAPIQueue();
	}
	
	public function SaveSettings(Varien_Event_Observer $observer)
    {
		// Raise memory and execution time temporarily
		// ini_set('memory_limit','128M');
		// ini_set('max_execution_time','18000');
		
		// Create MailCampaigns API Class Object
		$mcAPI 				= new MailCampaigns_API();
		$connection_write	= Mage::getSingleton('core/resource')->getConnection('core_write');
		$connection_read  	= Mage::getSingleton('core/resource')->getConnection('core_read');
	
		/* Create "mc_api_queue" table if not exists */
		$sql        = "CREATE TABLE IF NOT EXISTS `".Mage::getConfig()->getTablePrefix()."mc_api_queue` (
					  `id` int(11) NOT NULL AUTO_INCREMENT,
					  `datetime` int(11) NOT NULL DEFAULT '0',
					  `stream_data` longtext NOT NULL,
					  `error` tinyint(1) NOT NULL DEFAULT '0',
					  PRIMARY KEY (`id`)
					) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
		$connection_write->query($sql);
		
		/* Create "mc_api_pages" table if not exists */
		$sql        = "CREATE TABLE IF NOT EXISTS `".Mage::getConfig()->getTablePrefix()."mc_api_pages` (
					  `id` int(11) NOT NULL AUTO_INCREMENT,
					  `collection` varchar(50) NOT NULL,
					  `store_id` int(11) NOT NULL,
					  `page` int(11) NOT NULL,
					  `total` int(11) NOT NULL,
					  `datetime` int(11) NOT NULL,
					  PRIMARY KEY (`id`)
					) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
		$connection_write->query($sql);
		
		$tn__mc_api_queue = Mage::getSingleton('core/resource')->getTableName('mc_api_queue');
		$tn__mc_api_pages = Mage::getSingleton('core/resource')->getTableName('mc_api_pages');
		
		$mcAPI->APIStoreID = Mage::app()->getStore(Mage::app()->getRequest()->getParam('store'))->getId();
		$mcAPI->APIWebsiteID  = Mage::getModel('core/store')->load($mcAPI->APIStoreID)->getWebsiteId();
		$mcAPI->APIKey = Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_key',$mcAPI->APIStoreID);
		$mcAPI->APIToken = Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_usertoken',$mcAPI->APIStoreID);	
		
		$mcAPI->ImportOrdersHistory = Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_order_history',$mcAPI->APIStoreID);
		$mcAPI->ImportProductsHistory = Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_products_history',$mcAPI->APIStoreID);	
		$mcAPI->ImportMailinglistHistory = Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_mailing_list_history',$mcAPI->APIStoreID);	
		$mcAPI->ImportCustomersHistory = Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_customers_history',$mcAPI->APIStoreID);
		$mcAPI->ImportOrderProductsHistory = Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_order_product_history',$mcAPI->APIStoreID);
				
		$mcAPI->ImportProducts = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_products',$mcAPI->APIStoreID);	
		$mcAPI->ImportMailinglist = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_mailing_list',$mcAPI->APIStoreID);	
		$mcAPI->ImportCustomers = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_customers',$mcAPI->APIStoreID);	
		$mcAPI->ImportQuotes = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_quotes',$mcAPI->APIStoreID);
		$mcAPI->ImportOrders = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_orders',$mcAPI->APIStoreID);
		
		$mcAPI->ImportOrdersHistoryAmount = (int)Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_order_amount',$mcAPI->APIStoreID);
		$mcAPI->ImportProductsHistoryAmount = (int)Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_products_history_amount',$mcAPI->APIStoreID);
		$mcAPI->ImportMailinglistHistoryAmount = (int)Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_mailing_list_amount',$mcAPI->APIStoreID);
		$mcAPI->ImportCustomersHistoryAmount = (int)Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_customers_amount',$mcAPI->APIStoreID);
		$mcAPI->ImportOrderProductsHistoryAmount = (int)Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_order_product_amount',$mcAPI->APIStoreID);
		
		if ($mcAPI->ImportOrdersHistoryAmount == 0) $mcAPI->ImportOrdersHistoryAmount = 50;
		if ($mcAPI->ImportProductsHistoryAmount == 0) $mcAPI->ImportProductsHistoryAmount = 10;
		if ($mcAPI->ImportMailinglistHistoryAmount == 0) $mcAPI->ImportMailinglistHistoryAmount = 100;
		if ($mcAPI->ImportCustomersHistoryAmount == 0) $mcAPI->ImportCustomersHistoryAmount = 100;
		if ($mcAPI->ImportOrderProductsHistoryAmount == 0) $mcAPI->ImportOrderProductsHistoryAmount = 50;		

		if ($mcAPI->APIKey != "" && $mcAPI->APIToken != "" && $mcAPI->APIStoreID > 0)
		{
			// save multistore settings
			$config_data 				= array();
			$config_data 				= Mage::app()->getStore(Mage::app()->getRequest()->getParam('store'))->getData();
			$config_data["website_id"]	= $mcAPI->APIWebsiteID;
			$config_data["version"] 		= $this->version;
			$config_data["url"] 			= $_SERVER['SERVER_NAME'];
			
			$mcAPI->Call("save_magento_settings", $config_data, 0);
			
			// Init settings for import with cronjob
			$sql        = "DELETE FROM `".$tn__mc_api_pages."` WHERE collection = 'customer/customer' AND store_id = ".$mcAPI->APIStoreID."";
			$connection_write->query($sql);
			
			if ($mcAPI->ImportCustomersHistory == 1)
			{		
				$customersCollection = Mage::getModel('customer/customer')
							  ->getCollection()
							  ->addAttributeToSelect('*');
							  //->addAttributeToFilter('store_id', $mcAPI->APIStoreID);  /* Changed 06/05/2015 WST */ 
				$customersCollection->setPageSize($mcAPI->ImportCustomersHistoryAmount);
				$pages = $customersCollection->getLastPageNumber();
					
				$connection_write->insert(Mage::getSingleton('core/resource')->getTableName('mc_api_pages'), array(
					'collection'   => 'customer/customer',
					'datetime'     => time(),
					'page'     	   => 1,
					'total'		   => $pages,
					'store_id'     => $mcAPI->APIStoreID
				));
				
				$mc_import_data = array("store_id" => $mcAPI->APIStoreID, "collection" => 'customer/customer', "page" => 1, "total" => (int)$pages, "datetime" => time(), "finished" => 0);
				$mcAPI->Call("update_magento_progress", $mc_import_data);
			}

			/* Init settings for import with cronjob */
			$sql        = "DELETE FROM `".$tn__mc_api_pages."` WHERE collection = 'newsletter/subscriber_collection' AND store_id = ".$mcAPI->APIStoreID."";
			$connection_write->query($sql);
			
			if ($mcAPI->ImportMailinglistHistory == 1)
			{			
				$mailinglistCollection = Mage::getResourceModel('newsletter/subscriber_collection')->load();
				$mailinglistCollection->setPageSize($mcAPI->ImportMailinglistHistoryAmount);
				$pages = $mailinglistCollection->getLastPageNumber();
				
				$connection_write->insert(Mage::getSingleton('core/resource')->getTableName('mc_api_pages'), array(
					'collection'   => 'newsletter/subscriber_collection',
					'datetime'     => time(),
					'page'     	   => 1,
					'total'		   => $pages,
					'store_id'     => $mcAPI->APIStoreID
				));
				
				$mc_import_data = array("store_id" => $mcAPI->APIStoreID, "collection" => 'newsletter/subscriber_collection', "page" => 1, "total" => (int)$pages, "datetime" => time(), "finished" => 0);
				$mcAPI->Call("update_magento_progress", $mc_import_data);
			}
			
			/* Init settings for import with cronjob */
			$sql        = "DELETE FROM `".$tn__mc_api_pages."` WHERE collection = 'catalog/product' AND store_id = ".$mcAPI->APIStoreID."";
			$connection_write->query($sql);
			
			if ($mcAPI->ImportProductsHistory == 1)
			{
				$productsCollection = Mage::getModel('catalog/product')->setStoreId( $mcAPI->APIStoreID )->setOrder('entity_id', 'ASC')->getCollection();//->addStoreFilter($mcAPI->APIStoreID);
				$productsCollection->setPageSize($mcAPI->ImportProductsHistoryAmount);
				$pages = $productsCollection->getLastPageNumber();
				
				$connection_write->insert(Mage::getSingleton('core/resource')->getTableName('mc_api_pages'), array(
					'collection'   => 'catalog/product',
					'datetime'     => time(),
					'page'     	   => 1,
					'total'		   => $pages,
					'store_id'     => $mcAPI->APIStoreID
				));
				
				$mc_import_data = array("store_id" => $mcAPI->APIStoreID, "collection" => 'catalog/product', "page" => 1, "total" => (int)$pages, "datetime" => time(), "finished" => 0);
				$mcAPI->Call("update_magento_progress", $mc_import_data);
			}
			
			/* Init settings for import with cronjob */
			$sql        = "DELETE FROM `".$tn__mc_api_pages."` WHERE collection = 'sales/order' AND store_id = ".$mcAPI->APIStoreID."";
			$connection_write->query($sql);
			
			if ($mcAPI->ImportOrdersHistory == 1)
			{
				$ordersCollection = Mage::getModel('sales/order')->getCollection(); // ->setStoreId( $mcAPI->APIStoreID )
				$ordersCollection->setPageSize($mcAPI->ImportOrdersHistoryAmount);
				$pages = $ordersCollection->getLastPageNumber();
				
				$connection_write->insert(Mage::getSingleton('core/resource')->getTableName('mc_api_pages'), array(
					'collection'   => 'sales/order',
					'datetime'     => time(),
					'page'     	   => 1,
					'total'		   => $pages,
					'store_id'     => $mcAPI->APIStoreID
				));
				
				$mc_import_data = array("store_id" => $mcAPI->APIStoreID, "collection" => 'sales/order', "page" => 1, "total" => (int)$pages, "datetime" => time(), "finished" => 0);
				$mcAPI->Call("update_magento_progress", $mc_import_data);
			}
			
			/* Init settings for import with cronjob */
			$sql        = "DELETE FROM `".$tn__mc_api_pages."` WHERE collection = 'sales/order/products' AND store_id = ".$mcAPI->APIStoreID."";
			$connection_write->query($sql);

			if ($mcAPI->ImportOrderProductsHistory == 1)
			{
				$tn__sales_flat_order 					= Mage::getSingleton('core/resource')->getTableName('sales_flat_order');
				$tn__sales_flat_order_item 				= Mage::getSingleton('core/resource')->getTableName('sales_flat_order_item');
				
				$pagesize 								= $mcAPI->ImportOrderProductsHistoryAmount;
				
				// order items
				$sql        = "
				SELECT COUNT(*) AS pages 
				FROM `".$tn__sales_flat_order."` AS o 
				INNER JOIN ".$tn__sales_flat_order_item." AS oi ON oi.order_id = o.entity_id 
				WHERE o.store_id = ".$mcAPI->APIStoreID." OR o.store_id = 0";
				$pages 		= ceil($connection_read->fetchOne($sql) / $pagesize);
								
				$connection_write->insert(Mage::getSingleton('core/resource')->getTableName('mc_api_pages'), array(
					'collection'   => 'sales/order/products',
					'datetime'     => time(),
					'page'     	   => 1,
					'total'		   => $pages,
					'store_id'     => $mcAPI->APIStoreID
				));
				
				$mc_import_data = array("store_id" => $mcAPI->APIStoreID, "collection" => 'sales/order/products', "page" => 1, "total" => (int)$pages, "datetime" => time(), "finished" => 0);
				$mcAPI->Call("update_magento_progress", $mc_import_data);
			}
		}
    }
	
	
	// one transaction
    public function SynchronizeContact(Varien_Event_Observer $observer)
    {
		try 
		{
			// Retrieve the customer being updated from the event observer
			$customer = $observer->getEvent()->getCustomer();
				
			// Create MailCampaigns API Class Object
			$mcAPI 				= new MailCampaigns_API();
			$mcAPI->APIStoreID 	= $customer->getStoreId();
			$mcAPI->APIKey 		= Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_key',$mcAPI->APIStoreID);
			$mcAPI->APIToken 	= Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_usertoken',$mcAPI->APIStoreID);	
			$mcAPI->ImportCustomers = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_customers',$mcAPI->APIStoreID);	
			
			if ($mcAPI->ImportCustomers == 1 && $mcAPI->APIKey != "" && $mcAPI->APIToken != "" && $mcAPI->APIStoreID > 0)
			{
				$customer_data = array();
				
				$address_data = array();
				$customerAddressId = $customer->getDefaultBilling();
				if ($customerAddressId)
				{
					$address = Mage::getModel('customer/address')->load($customerAddressId);
					$address_data = $address->getData();
					
					if (isset($address_data["country_id"]))
					{
						try 
						{
							$country_id = $address_data["country_id"];
							$country_name = Mage::getModel('directory/country')->load($country_id)->getName();
							$address_data["country_name"] = $country_name;
						} 
						catch (Exception $e) 
						{ 
							$mcAPI->DebugCall($e->getMessage());
						}
					}
				}
				
				unset($address_data["entity_id"]);
				unset($address_data["parent_id"]);
				unset($address_data["is_active"]);
				unset($address_data["created_at"]);
				unset($address_data["updated_at"]);
							
				$customer_data[0] = array_filter(array_merge($address_data, $customer->getData()), 'is_scalar');	// ommit sub array levels
				$response = $mcAPI->QueueAPICall("update_magento_customers", $customer_data);
			}
		} 
		catch (Exception $e) 
		{ 
			$mcAPI->DebugCall($e->getMessage());
		}
    }

	// one transaction
	public function SynchronizeMailingEntry(Varien_Event_Observer $observer)
    {
		try
		{
			$event = $observer->getEvent();
			$subscriber = $event->getDataObject();
			$subscriber_tmp = (array)$subscriber->getData();
				
			// Create MailCampaigns API Class Object
			$mcAPI 				= new MailCampaigns_API();
			$mcAPI->APIStoreID 	= $subscriber_tmp["store_id"];
			$mcAPI->APIKey 		= Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_key',$mcAPI->APIStoreID);
			$mcAPI->APIToken 	= Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_usertoken',$mcAPI->APIStoreID);	
			$mcAPI->ImportMailinglist = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_mailing_list',$mcAPI->APIStoreID);	
			
			if ($mcAPI->ImportMailinglist == 1 && $mcAPI->APIKey != "" && $mcAPI->APIToken != "" && $mcAPI->APIStoreID > 0)
			{
				$subscriber_data = array();
				$subscriber_data[0] = $subscriber_tmp;			
				$response = $mcAPI->QueueAPICall("update_magento_mailing_list", $subscriber_data);
			}
		} 
		catch (Exception $e) 
		{ 
			$mcAPI->DebugCall($e->getMessage());
		}
	}
	
	public function SynchronizeOrder(Varien_Event_Observer $observer)
    {
		try
		{
			// Retrieve the order being updated from the event observer
			$order = $observer->getEvent()->getOrder();
			$mc_order_data = $order->getData();
			
			// Create MailCampaigns API Class Object
			$mcAPI 				= new MailCampaigns_API();
			$mcAPI->APIStoreID 	= $mc_order_data["store_id"];
			$mcAPI->APIKey 		= Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_key',$mcAPI->APIStoreID);
			$mcAPI->APIToken 	  = Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_usertoken',$mcAPI->APIStoreID);
			$mcAPI->ImportOrders = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_orders',$mcAPI->APIStoreID);			
			
			if ($mcAPI->ImportOrders == 1 && $mcAPI->APIKey != "" && $mcAPI->APIToken != "" && $mcAPI->APIStoreID > 0)
			{
				// Get table names
				$tn__sales_flat_quote 					= Mage::getSingleton('core/resource')->getTableName('sales_flat_quote');
				$tn__sales_flat_order 					= Mage::getSingleton('core/resource')->getTableName('sales_flat_order');
				$tn__sales_flat_order_item 				= Mage::getSingleton('core/resource')->getTableName('sales_flat_order_item');
				$tn__sales_flat_quote_item 				= Mage::getSingleton('core/resource')->getTableName('sales_flat_quote_item');
				$tn__catalog_category_product 			= Mage::getSingleton('core/resource')->getTableName('catalog_category_product');
				$tn__catalog_category_entity_varchar 	= Mage::getSingleton('core/resource')->getTableName('catalog_category_entity_varchar');
				$tn__eav_entity_type 					= Mage::getSingleton('core/resource')->getTableName('eav_entity_type');
				$tn__catalog_category_entity 			= Mage::getSingleton('core/resource')->getTableName('catalog_category_entity');
				
				$mc_data = array(
						"store_id" => $mc_order_data["store_id"],
						"order_id" => $mc_order_data["entity_id"],
						"order_name" => $mc_order_data["increment_id"],
						"order_status" => $mc_order_data["status"],
						"order_total" => $mc_order_data["grand_total"],
						"customer_id" => $mc_order_data["customer_id"],
						//"visitor_id" => $mc_order_data["visitor_id"],
						"quote_id" => $mc_order_data["quote_id"],
						"customer_email" => $mc_order_data["customer_email"],
						"created_at" => $mc_order_data["created_at"],
						"updated_at" => $mc_order_data["updated_at"]
						);
	
				$response = $mcAPI->QueueAPICall("update_magento_orders", $mc_data);
				
				// order items
				$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
				$sql        = "SELECT o.entity_id as order_id, o.store_id, oi.product_id as product_id, oi.qty_ordered, oi.price, oi.name, oi.sku, o.customer_id
				FROM `".$tn__sales_flat_order."` AS o
				INNER JOIN `".$tn__sales_flat_order_item."` AS oi ON oi.order_id = o.entity_id
				WHERE o.entity_id = ".$mc_order_data["entity_id"]." 
				ORDER BY  `o`.`updated_at` DESC";
				$rows       = $connection->fetchAll($sql);
				$mc_import_data = array(); $i = 0;
				foreach ($rows as $row)
				{
					foreach ($row as $key => $value)
					{
						if (!is_numeric($key)) $mc_import_data[$i][$key] = $value;
					}
					
					// get categories			
					$categories = array();
					$sql_cat        = "select cc.value from ".$tn__catalog_category_product." as pi
					JOIN ".$tn__catalog_category_entity_varchar." cc ON cc.entity_id = pi.category_id
					JOIN ".$tn__eav_entity_type." ee ON cc.entity_type_id = ee.entity_type_id
					JOIN ".$tn__catalog_category_entity." cce ON cc.entity_id = cce.entity_id
					WHERE cc.attribute_id =  '41' AND ee.entity_model =  'catalog/category' AND pi.product_id = ".$row["product_id"]."";
					$rows_cat       = $connection->fetchAll($sql_cat);
					foreach ($rows_cat as $row_cat)
					{
						$categories[] = $row_cat["value"];
					}
					$mc_import_data[$i]["categories"] = implode("|", $categories);
					
					$i++;
				}	
				if ($i > 0)
				{
					$response = $mcAPI->QueueAPICall("update_magento_order_products", $mc_import_data);	
				}
			}
		} 
		catch (Exception $e) 
		{ 
			$mcAPI->DebugCall($e->getMessage());
		}
    }
	
	// one transaction
	public function SynchronizeProduct(Varien_Event_Observer $observer)
    {
		try
		{
			// Retrieve the product being updated from the event observer
			$product = $observer->getEvent()->getProduct();
		
			$product_data = array();
			$related_products = array();
			$i = 0;	
			
			$allStores = Mage::app()->getStores();
			foreach ($allStores as $_eachStoreId => $val) 
			{
				$_storeId = Mage::app()->getStore($_eachStoreId)->getId();
					
				// Create MailCampaigns API Class Object
				$mcAPI 				= new MailCampaigns_API();
				$mcAPI->APIStoreID 	= $_storeId;
				$mcAPI->APIKey 		= Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_key',$mcAPI->APIStoreID);
				$mcAPI->APIToken 	= Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_usertoken',$mcAPI->APIStoreID);	
				$mcAPI->ImportProducts = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_products',$mcAPI->APIStoreID);	
				
				if ($mcAPI->ImportProducts == 1 && $mcAPI->APIKey != "" && $mcAPI->APIToken != "" && $mcAPI->APIStoreID > 0)
				{
					$product = Mage::getModel('catalog/product')->setStoreId( $_storeId )->load($product->getId());
					$attributes = $product->getAttributes();
					foreach ($attributes as $attribute)
					{
						$data = $attribute->getFrontend()->getValue($product);
						if (!is_array($data)) 
						{
							$product_data[$i][$attribute->getAttributeCode()] = $data;
						}
					}
					
					// get lowest tier price / staffel
					$lowestTierPrice = $product->getResource()->getAttribute('tier_price')->getFrontend()->getValue($product); 
					$product_data[$i]["lowest_tier_price"] = $lowestTierPrice;
	
					// images
					$product_data[$i]["mc:image_url_main"] = $product->getMediaConfig()->getMediaUrl($product->getData('image'));
					$image_id = 1;
					foreach ($product->getMediaGalleryImages() as $image)
					{
						$product_data[$i]["mc:image_url_".$image_id++.""] = $image->getUrl();
					} 
									
					// link
					$product_data[$i]["mc:product_url"] = $product->getProductUrl();
					
					// store id
					$product_data[$i]["store_id"] = $_storeId; //implode(",",$product->getStoreIds());
					
					// get related products
					/*$related_product_collection = $product->getRelatedProductIds();
					$related_products[$product->getId()]["store_id"] = $_storeId;
					foreach($related_product_collection as $pdtid)
					{
						$related_products[$product->getId()]["products"][] = $pdtid;
					}*/
		
					$i++;
		
					$response = $mcAPI->QueueAPICall("update_magento_products", $product_data);
					//$response = $mcAPI->QueueAPICall("update_magento_related_products", $related_products);
				}
			}
		} 
		catch (Exception $e) 
		{ 
			$mcAPI->DebugCall($e->getMessage());
		}
	}
	
	// one transaction
	public function SynchronizeProducts(Varien_Event_Observer $observer)
    {	
		try
		{
			// Retrieve the product being updated from the event observer
			$i = 0;	
			$product_data = array();
			$related_products = array();
			
			$products = $observer->getEvent()->product_ids;
			
			$allStores = Mage::app()->getStores();
			foreach ($allStores as $_eachStoreId => $val) 
			{
				$_storeId = Mage::app()->getStore($_eachStoreId)->getId();
				
				// Create MailCampaigns API Class Object
				$mcAPI 				= new MailCampaigns_API();
				$mcAPI->APIStoreID 	= $_storeId;
				$mcAPI->APIKey 		= Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_key',$mcAPI->APIStoreID);
				$mcAPI->APIToken 	= Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_usertoken',$mcAPI->APIStoreID);	
				$mcAPI->ImportProducts = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_products',$mcAPI->APIStoreID);	
				
				if ($mcAPI->ImportProducts == 1 && $mcAPI->APIKey != "" && $mcAPI->APIToken != "" && $mcAPI->APIStoreID > 0)
				{
					foreach ($products as $product_id)
					{
						$product = Mage::getModel('catalog/product')->setStoreId( $_storeId )->load($product_id);
						$attributes = $product->getAttributes();
						foreach ($attributes as $attribute)
						{
							$data = $attribute->getFrontend()->getValue($product);
							if (!is_array($data)) $product_data[$i][$attribute->getAttributeCode()] = $data;
						}
						
						// get lowest tier price / staffel
						$lowestTierPrice = $product->getResource()->getAttribute('tier_price')->getFrontend()->getValue($product); 
						$product_data[$i]["lowest_tier_price"] = $lowestTierPrice;
		
						// images
						$product_data[$i]["mc:image_url_main"] = $product->getMediaConfig()->getMediaUrl($product->getData('image'));
						$image_id = 1;
						foreach ($product->getMediaGalleryImages() as $image)
						{
							$product_data[$i]["mc:image_url_".$image_id++.""] = $image->getUrl();
						} 
										
						// link
						$product_data[$i]["mc:product_url"] = $product->getProductUrl();
						
						// store id
						$product_data[$i]["store_id"] = $_storeId; //implode(",",$product->getStoreIds());
						
						// get related products
						/*$related_product_collection = $product->getRelatedProductIds();
						$related_products[$product->getId()]["store_id"] = $_storeId;
						foreach($related_product_collection as $pdtid)
						{
							$related_products[$product->getId()]["products"][] = $pdtid;
						}*/
		
						$i++;			
					}
					
					$response = $mcAPI->QueueAPICall("update_magento_products", $product_data);
					//$response = $mcAPI->QueueAPICall("update_magento_related_products", $related_products);			
				}
			}
		} 
		catch (Exception $e) 
		{ 
			$mcAPI->DebugCall($e->getMessage());
		}
	}
		
	public function DeleteProduct(Varien_Event_Observer $observer)
    {
		try
		{
			// Retrieve the product being updated from the event observer
			$product = $observer->getEvent()->getProduct();
			
			$allStores = Mage::app()->getStores();
			foreach ($allStores as $_eachStoreId => $val) 
			{
				$_storeId = Mage::app()->getStore($_eachStoreId)->getId();
				
				// Create MailCampaigns API Class Object
				$mcAPI 				= new MailCampaigns_API();
				$mcAPI->APIStoreID 	= $_storeId;
				$mcAPI->APIKey 		= Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_key',$mcAPI->APIStoreID);
				$mcAPI->APIToken 	= Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_usertoken',$mcAPI->APIStoreID);	
				$mcAPI->ImportProducts = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_products',$mcAPI->APIStoreID);	
				
				if ($mcAPI->ImportProducts == 1 && $mcAPI->APIKey != "" && $mcAPI->APIToken != "" && $mcAPI->APIStoreID > 0)
				{
					$product_data = array();
					$product_data["entity_id"] = $product->getId();			
				
					$response = $mcAPI->QueueAPICall("delete_magento_product", $product_data);
				}
			}
		} 
		catch (Exception $e) 
		{ 
			$mcAPI->DebugCall($e->getMessage());
		}
	}
	
	public function SynchronizeQuote(Varien_Event_Observer $observer)
	{
		try
		{
			// Retrieve the quote being updated from the event observer
			$quote_data = $observer->getEvent()->getQuote()->getData();
			$quote_id = $quote_data["entity_id"];
			$store_id = $quote_data["store_id"];
			
			// Create MailCampaigns API Class Object
			$mcAPI 				= new MailCampaigns_API();
			$mcAPI->APIStoreID 	= $store_id;
			$mcAPI->APIKey 		= Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_key',$mcAPI->APIStoreID);
			$mcAPI->APIToken 	= Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_usertoken',$mcAPI->APIStoreID);	
			$mcAPI->ImportQuotes = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_quotes',$mcAPI->APIStoreID);	
				
			if ($mcAPI->ImportQuotes == 1 && $mcAPI->APIKey != "" && $mcAPI->APIToken != "" && $mcAPI->APIStoreID > 0)
			{
				// Get table names
				$tn__sales_flat_quote 		= Mage::getSingleton('core/resource')->getTableName('sales_flat_quote');
				$tn__sales_flat_order 		= Mage::getSingleton('core/resource')->getTableName('sales_flat_order');
				$tn__sales_flat_quote_item 	= Mage::getSingleton('core/resource')->getTableName('sales_flat_quote_item');
				
				// abandonded carts quotes
				$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
	
				$sql        = "SELECT q.*
				FROM `".$tn__sales_flat_quote."` AS q
				WHERE
				q.entity_id = ".$quote_id."
				ORDER BY  `q`.`updated_at` DESC";
				$data = array(); $i = 0;
				$rows       = $connection->fetchAll($sql);
				foreach ($rows as $row)
				{
					foreach ($row as $key => $value)
					{
						if (!is_numeric($key)) $data[$i][$key] = $value;
					}	
					
					// retrieve session_id from mailcampaigns interface server
					/*
					$data[$i]["session_id"] = file_get_contents('https://interface.mailcampaigns.nl/session_id',null,stream_context_create(array(
						'http' => array(
							'protocol_version' => 1.1,
							'method'           => 'POST',
							'header'           => "Content-type: application/json\r\n".
												  "Connection: close\r\n",
							'timeout'		   => 1
						),
					)));
					*/
					
					$i++;
				}
				if ($i > 0)
				{
					$response = $mcAPI->QueueAPICall("update_magento_abandonded_cart_quotes", $data);
				}
				
				// abandonded carts quote items
				$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
				$sql        = "SELECT q.entity_id as quote_id, p.product_id, p.store_id, p.item_id, p.qty, p.price
				FROM `".$tn__sales_flat_quote."` AS q
				LEFT JOIN `".$tn__sales_flat_order."` AS o ON o.quote_id = q.entity_id
				INNER JOIN ".$tn__sales_flat_quote_item." AS p ON p.quote_id = q.entity_id
				WHERE
				q.entity_id = ".$quote_id."
				ORDER BY  `q`.`updated_at` DESC";
				$rows       = $connection->fetchAll($sql);
				$data = array(); $i = 0;
				foreach ($rows as $row)
				{
					foreach ($row as $key => $value)
					{
						if (!is_numeric($key)) $data[$i][$key] = $value;
					}
					$i++;
				}	
				if ($i > 0)
				{
					$response = $mcAPI->QueueAPICall("update_magento_abandonded_cart_products", $data);	
				}
			}
		} 
		catch (Exception $e) 
		{ 
			$mcAPI->DebugCall($e->getMessage());
		}
	}
	
	public function SynchronizeQuoteDeleteItem(Varien_Event_Observer $observer)
	{
		try
		{
			$quote_data = $observer->getEvent()->getQuoteItem()->getData();
			$quote_id   = $quote_data["quote_id"];
			$item_id   = $quote_data["item_id"];
			$store_id   = $quote_data["store_id"];
			
			// Create MailCampaigns API Class Object
			$mcAPI 				= new MailCampaigns_API();
			$mcAPI->APIStoreID 	= $store_id;
			$mcAPI->APIKey 		= Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_key',$mcAPI->APIStoreID);
			$mcAPI->APIToken 	= Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_usertoken',$mcAPI->APIStoreID);	
			$mcAPI->ImportQuotes = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_quotes',$mcAPI->APIStoreID);	
		
			if ($mcAPI->ImportQuotes == 1 && $mcAPI->APIKey != "" && $mcAPI->APIToken != "" && $mcAPI->APIStoreID > 0)
			{
				// delete abandonded carts quote items
				$data = array("item_id" => $item_id, "store_id" => $store_id, "quote_id" => $quote_id);
				$response = $mcAPI->QueueAPICall("delete_magento_abandonded_cart_product", $data);	
			}
		} 
		catch (Exception $e) 
		{ 
			$mcAPI->DebugCall($e->getMessage());
		}
	}
	
	public function ProcessAPIQueue()
	{
		// Create MailCampaigns API Class Object
		$mcAPI 	= new MailCampaigns_API();
		
		$starttime 	= time();
		$connection_read  = Mage::getSingleton('core/resource')->getConnection('core_read');
		$connection_write = Mage::getSingleton('core/resource')->getConnection('core_write');
		
		$tn__mc_api_queue = Mage::getSingleton('core/resource')->getTableName('mc_api_queue');
		$tn__mc_api_pages = Mage::getSingleton('core/resource')->getTableName('mc_api_pages');
			
		// Process 250 items each cron
		$sql        = "SELECT * FROM `".$tn__mc_api_queue."` ORDER BY id ASC LIMIT 250";
		$rows       = $connection_read->fetchAll($sql);
		
		// Loop through queue list
		foreach ($rows as $row)
		{
			try
			{
				// Send it to MailCampaigns API
				$mcAPI->PostCall($row["stream_data"]);
				
				// Delete queued call
				$sql        = "DELETE FROM `".$tn__mc_api_queue."` WHERE id = '".$row["id"]."'";
				$connection_write->query($sql);
			} 
			catch (Exception $e) 
			{ 
				$mcAPI->DebugCall($e->getMessage());
			}
			
			/*
			// Timeout detection / 24 hour
			if (($row["datetime"] + (3600 * 4)) < time())
			{				
				// Delete queued call
				$sql        = "DELETE FROM `mc_api_queue` WHERE id = '".$row["id"]."'";
				$connection_write->query($sql);
			}
			else
			if (($row["datetime"] + (3600 * 1)) < time())
			{				
				// Mark queue call as error
				$sql        = "UPDATE `mc_api_queue` SET error = 1 WHERE id = '".$row["id"]."'";
				$connection_write->query($sql);
			}
			else
			{
				// Send it to MailCampaigns API
				$response 			= $mcAPI->PostCall($row["stream_data"]);
								
				if (isset($response["Success"]) || isset($response["success"]))
				{
					// Delete queued call
					$sql        = "DELETE FROM `mc_api_queue` WHERE id = '".$row["id"]."'";
					$connection_write->query($sql);
				}
				else
				{
					// Mark queue call as error
					$sql        = "UPDATE `mc_api_queue` SET error = 1 WHERE id = '".$row["id"]."'";
					$connection_write->query($sql);
				}
			}
			*/
			
			// Detect timeout
			if ((time() - $starttime) > 55)
			{
				return;	
			}
		}
	}
	
	public function ImportAPIQueue()
	{
		// Create MailCampaigns API Class Object
		$mcAPI 	= new MailCampaigns_API();
		
		$starttime 	= time();
		$connection_read  = Mage::getSingleton('core/resource')->getConnection('core_read');
		$connection_write = Mage::getSingleton('core/resource')->getConnection('core_write');
		
		$tn__mc_api_queue = Mage::getSingleton('core/resource')->getTableName('mc_api_queue');
		$tn__mc_api_pages = Mage::getSingleton('core/resource')->getTableName('mc_api_pages');
		
		// Process one page per each cron
		$sql        = "SELECT * FROM `".$tn__mc_api_pages."`";
		$rows       = $connection_read->fetchAll($sql);
		
		// Loop through queue list
		foreach ($rows as $row)
		{
			$currentPage 	= $row["page"];
			$currentTotal 	= $row["total"];
			
			$mcAPI->APIStoreID = $row["store_id"];
			$mcAPI->APIKey = Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_key',$mcAPI->APIStoreID);
			$mcAPI->APIToken = Mage::getStoreConfig('mailcampaigns/mailcampaigns_group/mailcampaigns_api_usertoken',$mcAPI->APIStoreID);	
			
			$mcAPI->ImportOrdersHistory = Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_order_history',$mcAPI->APIStoreID);
			$mcAPI->ImportProductsHistory = Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_products_history',$mcAPI->APIStoreID);	
			$mcAPI->ImportMailinglistHistory = Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_mailing_list_history',$mcAPI->APIStoreID);	
			$mcAPI->ImportCustomersHistory = Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_customers_history',$mcAPI->APIStoreID);
			$mcAPI->ImportOrderProductsHistory = Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_order_product_history',$mcAPI->APIStoreID);
			
			$mcAPI->ImportProducts = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_products',$mcAPI->APIStoreID);	
			$mcAPI->ImportMailinglist = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_mailing_list',$mcAPI->APIStoreID);	
			$mcAPI->ImportCustomers = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_customers',$mcAPI->APIStoreID);	
			$mcAPI->ImportQuotes = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_quotes',$mcAPI->APIStoreID);
			$mcAPI->ImportOrders = Mage::getStoreConfig('mailcampaigns/mailcampaigns__syncoptions_group/mailcampaigns_import_orders',$mcAPI->APIStoreID);
			
			$mcAPI->ImportOrdersHistoryAmount = (int)Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_order_amount',$mcAPI->APIStoreID);
			$mcAPI->ImportProductsHistoryAmount = (int)Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_products_history_amount',$mcAPI->APIStoreID);
			$mcAPI->ImportMailinglistHistoryAmount = (int)Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_mailing_list_amount',$mcAPI->APIStoreID);
			$mcAPI->ImportCustomersHistoryAmount = (int)Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_customers_amount',$mcAPI->APIStoreID);
			$mcAPI->ImportOrderProductsHistoryAmount = (int)Mage::getStoreConfig('mailcampaigns/mailcampaigns_history_group/mailcampaigns_import_order_product_amount',$mcAPI->APIStoreID);
			
			if ($mcAPI->ImportOrdersHistoryAmount == 0) $mcAPI->ImportOrdersHistoryAmount = 50;
			if ($mcAPI->ImportProductsHistoryAmount == 0) $mcAPI->ImportProductsHistoryAmount = 10;
			if ($mcAPI->ImportMailinglistHistoryAmount == 0) $mcAPI->ImportMailinglistHistoryAmount = 100;
			if ($mcAPI->ImportCustomersHistoryAmount == 0) $mcAPI->ImportCustomersHistoryAmount = 100;
			if ($mcAPI->ImportOrderProductsHistoryAmount == 0) $mcAPI->ImportOrderProductsHistoryAmount = 50;
			
			if ($row["collection"] == "customer/customer")
			{
				// one transaction
				// get all customers
				$customer_data = array();
				$customersCollection = Mage::getModel('customer/customer')
							  ->getCollection()
							  ->addAttributeToSelect('*');
							  //->addAttributeToFilter('store_id', $mcAPI->APIStoreID);  /* Changed 06/05/2015 WST */ 

				$customersCollection->setPageSize($mcAPI->ImportCustomersHistoryAmount);
				$pages = $currentTotal; //$customersCollection->getLastPageNumber();

				$customersCollection->setCurPage($currentPage);
				$customersCollection->load();
				
				foreach ($customersCollection as $customer)
				{
					try
					{
						$tmpdata = $customer->getData();
						
						$address_data = array();
						$customerAddressId = $customer->getDefaultBilling();
						if ($customerAddressId)
						{
							$address = Mage::getModel('customer/address')->load($customerAddressId);
							$address_data = $address->getData();
							$country_id = $address_data["country_id"];
							$country_name = Mage::getModel('directory/country')->load($country_id)->getName();
							$address_data["country_name"] = $country_name;
						}
						
						unset($address_data["entity_id"]);
						unset($address_data["parent_id"]);
						unset($address_data["is_active"]);
						unset($address_data["created_at"]);
						unset($address_data["updated_at"]);
						
						$tmpdata = array_merge($tmpdata, $address_data);
						
						$tmp_customer_data = array_filter($tmpdata, 'is_scalar');	// ommit sub array levels		
						if ($tmp_customer_data["store_id"] == 0 || $tmp_customer_data["store_id"] == $mcAPI->APIStoreID) $customer_data[] = $tmp_customer_data; /* Changed 06/05/2015 WST */ 		
					} 
					catch (Exception $e) 
					{ 
						$mcAPI->DebugCall($e->getMessage());
					}
				}
				$mcAPI->QueueAPICall("update_magento_customers", $customer_data, 0);
				
				// Clear collection and free memory
				$customersCollection->clear();	
				unset($customer_data);
			}

			if ($row["collection"] == "newsletter/subscriber_collection")
			{
				// one transaction
				// get mailing list for this store
				$subscriber_data = array();
				$mailinglistCollection = Mage::getResourceModel('newsletter/subscriber_collection')->load();
				$mailinglistCollection->setPageSize($mcAPI->ImportMailinglistHistoryAmount);
				$pages = $currentTotal; //$mailinglistCollection->getLastPageNumber();
				
				$mailinglistCollection->setCurPage($currentPage);
				$mailinglistCollection->load();
			
				foreach($mailinglistCollection->getItems() as $subscriber)
				{
					try
					{
						$tmp = $subscriber->getData();
						if ($tmp["store_id"] == $mcAPI->APIStoreID || $tmp["store_id"] == 0 /* Added 06/05/2015 WST */ )
						{
							$subscriber_data[] = $subscriber->getData();
						}
					} 
					catch (Exception $e) 
					{ 
						$mcAPI->DebugCall($e->getMessage());
					}
				}
				
				$mcAPI->QueueAPICall("update_magento_mailing_list", $subscriber_data, 0);
				$subscriber_data = array();
				
				//clear collection and free memory
				$mailinglistCollection->clear();
			}	

			if ($row["collection"] == "catalog/product")
			{
				// one transaction
				// loop trough all products for this store
				$product_data = array(); 
				$related_products = array();
				$i = 0;
				$productsCollection = Mage::getModel('catalog/product')->setStoreId( $mcAPI->APIStoreID )->setOrder('entity_id', 'ASC')->getCollection();//->addStoreFilter($mcAPI->APIStoreID);
				$productsCollection->setPageSize($mcAPI->ImportProductsHistoryAmount);
				$pages = $currentTotal; //$productsCollection->getLastPageNumber();
				
				$productsCollection->setCurPage($currentPage);
				$productsCollection->load();
		 
				foreach ($productsCollection as $product)
				{
					try
					{
						//if ($product->getId() > (int)$mcAPI->ImportProductsHistoryOffset) /* Added 07/05/2015 WST */
						//{
							$product = Mage::getModel('catalog/product')->setStoreId( $mcAPI->APIStoreID )->load($product->getId());
							$attributes = $product->getAttributes();
							foreach ($attributes as $attribute)
							{
								$data = $attribute->getFrontend()->getValue($product);
								if (!is_array($data)) $product_data[$i][$attribute->getAttributeCode()] = $data;
								unset($data);
							}
							
							// get lowest tier price / staffel
							$lowestTierPrice = $product->getResource()->getAttribute('tier_price')->getFrontend()->getValue($product); 
							$product_data[$i]["lowest_tier_price"] = $lowestTierPrice;
			
							// images
							$image_id = 1;
							$product_data[$i]["mc:image_url_main"] = $product->getMediaConfig()->getMediaUrl($product->getData('image'));
							foreach ($product->getMediaGalleryImages() as $image)
							{
								$product_data[$i]["mc:image_url_".$image_id++.""] = $image->getUrl();
							} 
			
							// link
							$product_data[$i]["mc:product_url"] = $product->getProductUrl();
							
							// store id
							$product_data[$i]["store_id"] = $mcAPI->APIStoreID;
							
							// get related products
							/*
							$related_product_collection = $product->getRelatedProductIds();
							$related_products[$product->getId()]["store_id"] = $_storeId;
							foreach($related_product_collection as $pdtid)
							{
								$related_products[$product->getId()]["products"][] = $pdtid;
							}
							*/
		
							$i++;
						//}
					} 
					catch (Exception $e) 
					{ 
						$mcAPI->DebugCall($e->getMessage());
					}
				}	
				
				$response = $mcAPI->QueueAPICall("update_magento_products", $product_data, 0);			
				unset($product_data);	
				
				//$response = $mcAPI->QueueAPICall("update_magento_related_products", $related_products, 0);			
				//unset($related_products);			
	
				//clear collection and free memory
				$productsCollection->clear();
			}

			if ($row["collection"] == "sales/order")
			{
				// get all orders
				$mc_import_data = array();
				$ordersCollection = Mage::getModel('sales/order')->getCollection(); // ->setStoreId( $mcAPI->APIStoreID )
				$ordersCollection->setPageSize($mcAPI->ImportOrdersHistoryAmount);
				$pages = $currentTotal; //$ordersCollection->getLastPageNumber();
				
				$ordersCollection->setCurPage($currentPage);
				$ordersCollection->load();
				foreach ($ordersCollection as $order)
				{
					try
					{
						$mc_order_data = (array)$order->getData();
						
						if ($mc_order_data["store_id"] == $mcAPI->APIStoreID || $mc_order_data["store_id"] == 0) /* Added 06/05/2015 WST */
						{
							$mc_import_data[] = array(
								"store_id" => $mc_order_data["store_id"],
								"order_id" => $mc_order_data["entity_id"],
								"order_name" => $mc_order_data["increment_id"],
								"order_status" => $mc_order_data["status"],
								"order_total" => $mc_order_data["grand_total"],
								"customer_id" => $mc_order_data["customer_id"],
								//"visitor_id" => $mc_order_data["visitor_id"],
								"quote_id" => $mc_order_data["quote_id"],
								"customer_email" => $mc_order_data["customer_email"],
								"created_at" => $mc_order_data["created_at"],
								"updated_at" => $mc_order_data["updated_at"]
								);
						}
					} 
					catch (Exception $e) 
					{ 
						$mcAPI->DebugCall($e->getMessage());
					}
				}
				
				$response = $mcAPI->QueueAPICall("update_magento_multiple_orders", $mc_import_data);
				unset($mc_import_data);
				
				//clear collection and free memory
				$ordersCollection->clear();
			}

			if ($row["collection"] == "sales/order/products")
			{
				// Get table names
				$tn__sales_flat_quote 					= Mage::getSingleton('core/resource')->getTableName('sales_flat_quote');
				$tn__sales_flat_order 					= Mage::getSingleton('core/resource')->getTableName('sales_flat_order');
				$tn__sales_flat_order_item 				= Mage::getSingleton('core/resource')->getTableName('sales_flat_order_item');
				$tn__sales_flat_quote_item 				= Mage::getSingleton('core/resource')->getTableName('sales_flat_quote_item');
				$tn__catalog_category_product 			= Mage::getSingleton('core/resource')->getTableName('catalog_category_product');
				$tn__catalog_category_entity_varchar 	= Mage::getSingleton('core/resource')->getTableName('catalog_category_entity_varchar');
				$tn__eav_entity_type 					= Mage::getSingleton('core/resource')->getTableName('eav_entity_type');
				$tn__catalog_category_entity 			= Mage::getSingleton('core/resource')->getTableName('catalog_category_entity');
				
				$pagesize 								= $mcAPI->ImportOrderProductsHistoryAmount;
				
				try
				{
					// order items
					/*$sql        = "
					SELECT COUNT(*) AS pages 
					FROM `".$tn__sales_flat_order."` AS o 
					INNER JOIN ".$tn__sales_flat_order_item." AS oi ON oi.order_id = o.entity_id 
					WHERE o.store_id = ".$mcAPI->APIStoreID." OR o.store_id = 0";*/
					$pages 		= $currentTotal; //ceil($connection_read->fetchOne($sql) / $pagesize);
					
					$sql        = "SELECT o.entity_id as order_id, o.store_id, oi.product_id as product_id, oi.qty_ordered, oi.price, oi.name, oi.sku, o.customer_id
					FROM `".$tn__sales_flat_order."` AS o
					INNER JOIN ".$tn__sales_flat_order_item." AS oi ON oi.order_id = o.entity_id
					WHERE o.store_id = ".$mcAPI->APIStoreID." OR o.store_id = 0
					ORDER BY  `o`.`created_at` ASC
					LIMIT ".$pagesize." OFFSET ".(($currentPage-1) * $pagesize)."
					";
					$tmp_rows       = $connection_read->fetchAll($sql);
					
					$mc_import_data = array(); $i = 0;
					foreach ($tmp_rows as $tmp_row)
					{
						foreach ($tmp_row as $key => $value)
						{
							if (!is_numeric($key)) $mc_import_data[$i][$key] = $value;
						}
		
						// get categories			
						$categories = array();
						if ($tmp_row["product_id"] > 0)
						{
							try
							{
								$sql_cat        = "select cc.value from ".$tn__catalog_category_product." as pi
								JOIN ".$tn__catalog_category_entity_varchar." cc ON cc.entity_id = pi.category_id
								JOIN ".$tn__eav_entity_type." ee ON cc.entity_type_id = ee.entity_type_id
								JOIN ".$tn__catalog_category_entity." cce ON cc.entity_id = cce.entity_id
								WHERE cc.attribute_id =  '41' AND ee.entity_model =  'catalog/category' AND pi.product_id = ".$tmp_row["product_id"]."";
								$rows_cat       = $connection_read->fetchAll($sql_cat);
								foreach ($rows_cat as $row_cat)
								{
									$categories[] = $row_cat["value"];
								}
							}
							catch (Exception $e) 
							{ 
							
							}
						}
						
						$mc_import_data[$i]["categories"] = implode("|", $categories);
						$i++;
					}	
				} 
				catch (Exception $e) 
				{ 
					$mcAPI->DebugCall($e->getMessage());
				}

				// post items
				$response = $mcAPI->QueueAPICall("update_magento_order_products", $mc_import_data);		
				
				// clear
				$mc_import_data = array();				
			}
			
			// Remove job if finished
			if (($currentPage + 1) > $pages)
			{
				$sql = "DELETE FROM `".$tn__mc_api_pages."` WHERE id = ".$row["id"]."";
				$connection_write->query($sql);
				
				$mc_import_data = array("store_id" => $row["store_id"], "collection" => $row["collection"], "page" => ($currentPage + 1), "total" => (int)$pages, "datetime" => time(), "finished" => 1);
				$mcAPI->Call("update_magento_progress", $mc_import_data);	
			}
			else
			// Update job if not finished
			{
				$sql = "UPDATE `".$tn__mc_api_pages."` SET page = ".($currentPage+1).", total = ".(int)$pages.", datetime = ".time()." WHERE id = ".$row["id"]."";
				$connection_write->query($sql);
				
				$mc_import_data = array("store_id" => $row["store_id"], "collection" => $row["collection"], "page" => ($currentPage+1), "total" => (int)$pages, "datetime" => time(), "finished" => 0);
				$mcAPI->Call("update_magento_progress", $mc_import_data);	
			}	
		}
	}
}

/* API Library v1.20 */
/* Added a queuing system */
class MailCampaigns_API
{	
	public $APIKey;
	public $APIToken;
	public $APIStoreID;
	public $APIWebsiteID;
	public $ImportOrdersHistory;
	public $ImportProductsHistory;
	public $ImportMailinglistHistory;
	public $ImportCustomersHistory;
	public $ImportOrderProductsHistory;
	public $ImportProductsHistoryOffset;
	public $ImportProducts;
	public $ImportMailinglist;
	public $ImportCustomers;
	public $ImportQuotes;
	public $ImportOrders;
	public $ImportOrdersHistoryAmount;
	public $ImportProductsHistoryAmount;
	public $ImportMailinglistHistoryAmount;
	public $ImportCustomersHistoryAmount;
	public $ImportOrderProductsHistoryAmount;
	
	function QueueAPICall($api_function, $api_filters, $timeout = 0 /* not used */)
	{
		$tn__mc_api_queue = Mage::getSingleton('core/resource')->getTableName('mc_api_queue');
		$tn__mc_api_pages = Mage::getSingleton('core/resource')->getTableName('mc_api_pages');
		
		$body = array();
		$body["api_key"] 	= $this->APIKey;
		$body["api_token"] 	= $this->APIToken;
		$body["method"] 	    = $api_function;
		$body["filters"]  	= $api_filters;
		$body_json 			= json_encode($body);
		
		if ($this->APIKey == "" || $this->APIToken == "" || $this->APIKey == NULL || $this->APIToken == NULL) 
			return false;
		
		$connection_write   = Mage::getSingleton('core/resource')->getConnection('core_write');
		return $connection_write->insert($tn__mc_api_queue, array(
			'stream_data'   => $body_json,
			'datetime'      => time()
		));	
	}
	
	function Call($api_function, $api_filters, $timeout = 5)
	{		
		$body = array();
		$body["api_key"] 	= $this->APIKey;
		$body["api_token"] 	= $this->APIToken;
		$body["method"] 	    = $api_function;
		$body["filters"]  	= $api_filters;
		$body_json 			= json_encode($body);
		
		if ($this->APIKey == "" || $this->APIToken == "") 
			return false;
	 
	 	if ($timeout == 0)
		{
			try
			{
				$response = file_get_contents('https://api.mailcampaigns.nl/api/v1.1/rest',null,stream_context_create(array(
					'http' => array(
						'protocol_version' => 1.1,
						'method'           => 'POST',
						'header'           => "Content-type: application/json\r\n".
											  "Connection: close\r\n" .
											  "Content-length: " . strlen($body_json) . "\r\n",
						'content'          => $body_json,
					),
				)));
			} 
			catch (Exception $e) 
			{ 
			
			}
		}
		else
		if ($timeout > 0)
		{
			try
			{
				$response = file_get_contents('https://api.mailcampaigns.nl/api/v1.1/rest',null,stream_context_create(array(
					'http' => array(
						'protocol_version' => 1.1,
						'method'           => 'POST',
						'header'           => "Content-type: application/json\r\n".
											  "Connection: close\r\n" .
											  "Content-length: " . strlen($body_json) . "\r\n",
						'content'          => $body_json,
						'timeout'		   => $timeout
					),
				)));
			} 
			catch (Exception $e) 
			{ 
			
			}
		}
								 
		return json_decode($response, true);
	}
	
	function PostCall($json_data)
	{		
		try
		{
			$response = file_get_contents('https://api.mailcampaigns.nl/api/v1.1/rest',null,stream_context_create(array(
				'http' => array(
					'protocol_version' => 1.1,
					'method'           => 'POST',
					'header'           => "Content-type: application/json\r\n".
										  "Connection: close\r\n" .
										  "Content-length: " . strlen($json_data) . "\r\n",
					'content'          => $json_data,
					'timeout'		   => 5
				),
			)));
		} 
		catch (Exception $e) 
		{ 
		
		}
								 
		return json_decode($response, true);
	}
	
	function DebugCall($debug_string)
	{	
		$debug_string = "".$this->APIKey." - ".date("d/m/Y H:i:s", time())." - " . $debug_string;
			
		try
		{
			$response = file_get_contents('https://api.mailcampaigns.nl/api/v1.1/debug',null,stream_context_create(array(
				'http' => array(
					'protocol_version' => 1.1,
					'method'           => 'POST',
					'header'           => "Content-type: application/json\r\n".
										  "Connection: close\r\n" .
										  "Content-length: " . strlen($debug_string) . "\r\n",
					'content'          => $debug_string,
					'timeout'		   => 5
				),
			)));
		} 
		catch (Exception $e) 
		{ 
		
		}
								 
		return json_decode($response, true);
	}
}