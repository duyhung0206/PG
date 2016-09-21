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
class Magestore_Promotionalgift_Block_Toplink extends Mage_Core_Block_Template
{

    public function __construct()
    {
        parent::__construct();
    }

    public function _prepareLayout()
    {
        $store = Mage::app()->getStore()->getId();
        parent::_prepareLayout();
        if (!Mage::getStoreConfig('promotionalgift/general/enable', $store)) {
            return $this;
        }
        if (!Mage::getStoreConfig('promotionalgift/general/show_toplink', $store)) {
            return $this;
        }
        $this->addPromotionalGiftToplink();
    }

    public function addPromotionalGiftToplink()
    {
        $block = $this->getLayout()->getBlock('top.links');
        if ($block) {
            $block->addLink(Mage::helper('promotionalgift')->__('Promotional Gift'), Mage::helper('promotionalgift')->getPromotionalgiftUrl(), Mage::helper('promotionalgift')->__('Promotional Gift'), '', '', 10);
        }
    }
}