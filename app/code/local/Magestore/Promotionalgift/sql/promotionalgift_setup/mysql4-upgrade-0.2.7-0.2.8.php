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
        ADD COLUMN `time_used` int(11) NULL,
        ADD COLUMN `from_date` date default NULL,
        ADD COLUMN `to_date` date default NULL,
        ADD COLUMN `banner_calendar` varchar(255) NOT NULL default 'all',
        ADD COLUMN `daily` text default '',
        ADD COLUMN `weekly` text default '',
        ADD COLUMN `monthly` text default '',
        ADD COLUMN `yearly` text default '';  

   
");

$installer->endSetup();
