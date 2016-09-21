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
class Magestore_Promotionalgift_Block_Js extends Mage_Core_Block_Template
{

    public function getItemIds()
    {
        return Mage::helper('promotionalgift/rule')->getItemIds();
    }

    public function getEidtItemIds()
    {
        return Mage::helper('promotionalgift/rule')->getEidtItemIds();
    }

    public function getEidtItemOptionIds()
    {
        return Mage::helper('promotionalgift/rule')->getEidtItemOptionIds();
    }

}
