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
$installer = $this;

$installer->startSetup();

$installer->run("
    ALTER TABLE {$this->getTable('promotionalgift_catalog_rule')}
        ADD COLUMN `discount_product_fixed` int(11) default '0',
        ADD COLUMN `price_type` int(1) default '1';  

    ALTER TABLE {$this->getTable('promotionalgift_shopping_cart_rule')}
        ADD COLUMN `discount_product_fixed` int(11) default '0',
        ADD COLUMN `price_type` int(1) default '1';    
  
");

$installer->endSetup();
