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
class Magestore_Promotionalgift_Block_Category_Productgift_Freegift extends Mage_Catalog_Block_Product_View
{

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('promotionalgift/category/productgift/freegift.phtml');
    }

    public function getCatalogruleCollection()
    {
        return Mage::getModel('promotionalgift/catalogrule')->getAvailableRule();
    }

    public function validateProductHasFreeGift($product_id)
    {
        return Mage::getModel('promotionalgift/catalogrule')->validateItem($product_id);
    }

    public function getProductCollection()
    {
        $collection = Mage::getResourceModel('catalog/product_collection');
        return $collection;
    }

    public function getCurrentProduct()
    {
        return Mage::registry('current_product');
    }

    public function getGiftItemCollection()
    {
        $collection = Mage::getModel('promotionalgift/catalogitem')->getCollection();
        return $collection;
    }

    public function getRule()
    {
        return Mage::getModel('promotionalgift/catalogrule')->getAvailableRule();
    }

    public function getRuleByPruduct($productId)
    {
        $catalogRule = Mage::getModel('promotionalgift/catalogrule')->validateItem($productId);
        return $catalogRule;
    }

    public function getIdFreeGifts($productId)
    {
        $freeGrift = false;
        $rule = $this->getRuleByPruduct($productId);
        if ($rule) {
            $ruleId = $this->getRuleByPruduct($productId)->getId();
            $freeGrift = Mage::getModel('promotionalgift/catalogitem')->load($ruleId, 'rule_id')->getProductIds();
            $freeGrift = explode(',', $freeGrift);
        }
        return $freeGrift;
    }
}

