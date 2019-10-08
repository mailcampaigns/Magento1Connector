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

$installer = $this;

$installer->startSetup();

$installer->run("DROP TABLE IF EXISTS `".Mage::getConfig()->getTablePrefix()."mc_api_queue`; 
CREATE TABLE IF NOT EXISTS `".Mage::getConfig()->getTablePrefix()."mc_api_queue` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`datetime` int(11) NOT NULL DEFAULT '0',
`stream_data` longtext NOT NULL,
`error` tinyint(1) NOT NULL DEFAULT '0',
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;");

$installer->run("DROP TABLE IF EXISTS `".Mage::getConfig()->getTablePrefix()."mc_api_pages`; 
CREATE TABLE IF NOT EXISTS `".Mage::getConfig()->getTablePrefix()."mc_api_pages` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`collection` varchar(50) NOT NULL,
`store_id` int(11) NOT NULL,
`page` int(11) NOT NULL,
`total` int(11) NOT NULL,
`datetime` int(11) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;");

$installer->run("DROP TABLE IF EXISTS `".Mage::getConfig()->getTablePrefix()."mc_api_status`; 
CREATE TABLE IF NOT EXISTS `".Mage::getConfig()->getTablePrefix()."mc_api_status` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`type` varchar(50) NOT NULL,
`datetime` int(11) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;");

$installer->endSetup();