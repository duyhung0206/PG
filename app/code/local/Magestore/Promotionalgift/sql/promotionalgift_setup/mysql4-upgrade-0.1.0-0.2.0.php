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
  ALTER TABLE {$this->getTable('promotionalgift_catalog_rule')}
    ADD COLUMN `discount_product` int(11) NOT NULL default '100',
    ADD COLUMN `free_shipping` smallint(6) NOT NULL default '0',
    ADD COLUMN `gift_calendar` varchar(255) NOT NULL default 'all',
    ADD COLUMN `daily` text default '',
    ADD COLUMN `weekly` text default '',
    ADD COLUMN `monthly` text default '',
    ADD COLUMN `yearly` text default '',
    ADD COLUMN `show_before_date` tinyint(1) NOT NULL default '1',
    ADD INDEX (`discount_product`),
    ADD INDEX (`free_shipping`);
  ALTER TABLE {$this->getTable('promotionalgift_shopping_cart_rule')}
    ADD COLUMN `discount_product` int(11) NOT NULL default '100',
    ADD COLUMN `free_shipping` smallint(6) NOT NULL default '0',
    ADD COLUMN `gift_calendar` varchar(255) NOT NULL default 'all',
    ADD COLUMN `daily` text default '',
    ADD COLUMN `weekly` text default '',
    ADD COLUMN `monthly` text default '',
    ADD COLUMN `yearly` text default '',
    ADD COLUMN `show_before_date` tinyint(1) NOT NULL default '1',
    ADD INDEX (`discount_product`),
    ADD INDEX (`free_shipping`);
  ALTER TABLE {$this->getTable('promotionalgift_quote')}
    ADD COLUMN `grouped_id` int(11) NOT NULL default '0',
    ADD INDEX (`grouped_id`);
  ALTER TABLE {$this->getTable('promotionalgift_shopping_cart_quote')}
    ADD COLUMN `grouped_id` int(11) NOT NULL default '0',
    ADD INDEX (`grouped_id`);
");

$installer->endSetup();

