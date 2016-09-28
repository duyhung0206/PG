<?php
/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Promotionalgift
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$this->getTable('promotionalgift_customer')};

CREATE TABLE {$this->getTable('promotionalgift_customer')} (
  `item_id` int(11) unsigned NOT NULL auto_increment,
  `customer_id` int(11) NOT NULL,
  `catalogrule_id` varchar(255),
  `shoppingcartrule_id` varchar(255),
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

 ALTER TABLE {$this->getTable('promotionalgift_catalog_rule')}
        ADD COLUMN `limit_customer` int(11) NULL;
        
 ALTER TABLE {$this->getTable('promotionalgift_shopping_cart_rule')}
        ADD COLUMN `limit_customer` int(11) NULL;


");



$installer->endSetup();

