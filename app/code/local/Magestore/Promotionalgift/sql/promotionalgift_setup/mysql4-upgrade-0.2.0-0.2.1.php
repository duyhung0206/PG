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
    DROP TABLE IF EXISTS {$this->getTable('promotionalgift_catalog_product')};
    CREATE TABLE {$this->getTable('promotionalgift_catalog_product')}(
        `rule_product_id` int(10) unsigned NOT NULL auto_increment,
        `rule_id` int(10) unsigned NOT NULL default '0',
        `product_id` int(10) unsigned NOT NULL default '0',
        PRIMARY KEY  (`rule_product_id`),
        FOREIGN KEY (`rule_id`) REFERENCES {$this->getTable('promotionalgift_catalog_rule')} (`rule_id`) ON DELETE CASCADE ON UPDATE CASCADE
    )ENGINE=InnoDB DEFAULT CHARSET=utf8;"
);

$resource = Mage::getSingleton('core/resource');
$readConnection = $resource->getConnection('core/read');
$query = 'SELECT `rule_id` FROM ' . $this->getTable('promotionalgift_catalog_rule') . ';';
$rules = $readConnection->fetchAll($query);

foreach ($rules as $rule) {
    $ruleId = $rule['rule_id'];
    $rule = Mage::getModel('promotionalgift/catalogrule')->load($ruleId);
    Mage::getModel('promotionalgift/catalogrule')->updateRuleProductData($rule);
}

$installer->endSetup();
