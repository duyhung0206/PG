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
    ALTER TABLE {$this->getTable('promotionalgift_banner')}
        ADD COLUMN `is_cart` int(1) default '1',
        ADD COLUMN `is_onepage` int(1) default '1';  

   
");

$installer->endSetup();
