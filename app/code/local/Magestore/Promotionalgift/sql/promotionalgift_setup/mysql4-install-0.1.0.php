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

DROP TABLE IF EXISTS {$this->getTable('promotionalgift_catalog_rule')};

CREATE TABLE {$this->getTable('promotionalgift_catalog_rule')} (
  `rule_id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) default '',
  `description` text default '',
  `status` smallint(6) NOT NULL default '0',
  `website_ids` text default '',
  `customer_group_ids` text default '',
  `uses_limit` int(11) NULL,
  `time_used` int(11) NULL,
  `from_date` date default NULL,
  `to_date` date default NULL,
  `priority` int(11) unsigned default '0',
  `conditions_serialized` mediumtext NOT NULL,
  PRIMARY KEY (`rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('promotionalgift_shopping_cart_rule')};

CREATE TABLE {$this->getTable('promotionalgift_shopping_cart_rule')} (
  `rule_id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) default '',
  `description` text default '',
  `status` smallint(6) NOT NULL default '0',
  `website_ids` text default '',
  `customer_group_ids` text default '',
  `coupon_type` smallint(6) NOT NULL default '0',
  `coupon_code` varchar(255) DEFAULT NULL,
  `uses_per_coupon` int(11) NULL,  
  `from_date` date default NULL,
  `to_date` date default NULL,
  `priority` int(11) unsigned default '0',
  `conditions_serialized` mediumtext NOT NULL,
  `number_item_free` int(11) default '1',
  PRIMARY KEY (`rule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('promotionalgift_sale')};

CREATE TABLE {$this->getTable('promotionalgift_sale')} (
  `sale_id` int(11) unsigned NOT NULL auto_increment,
  `order_id` int(11) NOT NULL default '0',
  `order_increment_id` varchar(50) default NULL,
  `order_total` decimal(12,4) default NULL,
  `product_ids` text default '',
  `product_names` text default '',
  `gift_total` decimal(12,4) default NULL,
  `created_at` datetime NULL,
  `order_status` varchar(255) default NULL,
  `catalogrule_id` varchar(255),
  `shoppingcartrule_id` varchar(255),
  `coupon_code` varchar(255),
  PRIMARY KEY (`sale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('promotionalgift_catalog_item')};

CREATE TABLE {$this->getTable('promotionalgift_catalog_item')} (
  `item_id` int(11) unsigned NOT NULL auto_increment,
  `product_ids` varchar(255) default '',
  `rule_id` int(11) unsigned NOT NULL,
  `gift_qty` varchar(255) default '',
  PRIMARY KEY (`item_id`),
  INDEX (`rule_id`),
  FOREIGN KEY (`rule_id`) REFERENCES {$this->getTable('promotionalgift_catalog_rule')} (`rule_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('promotionalgift_shopping_cart_item')};

CREATE TABLE {$this->getTable('promotionalgift_shopping_cart_item')} (
  `item_id` int(11) unsigned NOT NULL auto_increment,
  `product_ids` varchar(255) default '',
  `rule_id` int(11) unsigned NOT NULL,
  `gift_qty` varchar(255) default '',
  PRIMARY KEY (`item_id`),
  INDEX (`rule_id`),
  FOREIGN KEY (`rule_id`) REFERENCES {$this->getTable('promotionalgift_shopping_cart_rule')} (`rule_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('promotionalgift_quote')};

CREATE TABLE {$this->getTable('promotionalgift_quote')} (
  `promotionalgift_quote_id` int(11) unsigned NOT NULL auto_increment,
  `quote_id` int(11) NOT NULL default '0',
  `item_id` int(11) NOT NULL default '0',
  `item_parent_id` int(11) NOT NULL default '0',
  `catalog_rule_id` int(11) NOT NULL default '0',
  `shopping_cart_rule_id` int(11) NOT NULL default '0',
  `number_item_free` int(11) default '1',
  `message` text default '',
  PRIMARY KEY (`promotionalgift_quote_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('promotionalgift_shopping_cart_quote')};

CREATE TABLE {$this->getTable('promotionalgift_shopping_cart_quote')} (
  `shoppingcart_quote_id` int(11) unsigned NOT NULL auto_increment,
  `quote_id` int(11) NOT NULL default '0',
  `item_id` int(11) NOT NULL default '0',  
  `coupon_code` varchar(255),
  `shoppingcartrule_id` int(11) NOT NULL default '0',  
  `message` text default '',
  PRIMARY KEY (`shoppingcart_quote_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

$installer->endSetup();

