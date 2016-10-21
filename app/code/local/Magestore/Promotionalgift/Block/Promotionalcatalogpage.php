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

/**
 * Promotionalgift Block
 *
 * @category    Magestore
 * @package     Magestore_Promotionalgift
 * @author      Magestore Developer
 */
class Magestore_Promotionalgift_Block_Promotionalcatalogpage extends Mage_Catalog_Block_Product_List
{

    /**
     * prepare block's layout
     *
     * @return Magestore_Promotionalgift_Block_Promotionalgiftcatalog
     */
    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    protected function getProductIds()
    {
        $products = Mage::getResourceModel('catalog/product_collection');
        $productIds = array();
        foreach ($products as $product) {
            $availableRule = Mage::getModel('promotionalgift/catalogrule')->validateItem($product->getId());
            if ($availableRule) {
                $productIds[] = $product->getId();
            }
        }
        return $productIds;
    }

    public function getListShoppingcartRule()
    {
        $action = Mage::app()->getRequest()->getActionName();
        $shoppingcartRules = Mage::getModel('promotionalgift/shoppingcartrule')->getCollection()
            ->addFieldToFilter('website_ids', array('finset' => Mage::app()->getStore()->getWebsiteId()))
            ->addFieldToFilter('status', '1')
            ->addFieldToFilter('number_item_free', array('gt' => 0))
            ->addFieldToFilter('coupon_type', '1');
        if (Mage::getModel('customer/session')->isLoggedIn()) {
            $customer = Mage::getModel('customer/customer')->load(Mage::getModel('customer/session')->getCustomerId());
            $shoppingcartRules->addFieldToFilter('customer_group_ids', array('finset' => $customer->getGroupId()));
        } else {
            $shoppingcartRules->addFieldToFilter('customer_group_ids', array('finset' => Mage_Customer_Model_Group::NOT_LOGGED_IN_ID));
        }

        $shoppingcartRules->getSelect()->where('(uses_per_coupon IS NULL) OR (uses_per_coupon > 0)');
        $fromdate = strtotime($this->getRequest()->getParam('fromdate'));
        $todate = strtotime($this->getRequest()->getParam('todate'));
        if ($shoppingcartRules) {
            if ($action == 'index') {
                $shoppingcartRules->getSelect()->where('(from_date IS NULL) OR (date(from_date) <= date(?))', date("Y-m-d", strtotime(now())));
                $shoppingcartRules->getSelect()->where('(to_date IS NULL) OR (date(to_date) >= date(?))', date("Y-m-d", strtotime(now())));
                $shoppingcartRules->setOrder('priority', 'ASC');
            } else {
                if ($fromdate && !$todate) {
                    $shoppingcartRules->getSelect()->where("(to_date IS NULL) OR date(to_date) >= date(?)", date("Y-m-d", $fromdate));
                }
                if ($todate && !$fromdate) {
                    $shoppingcartRules->getSelect()->where("(from_date IS NULL) OR date(from_date) <= date(?)", date("Y-m-d", $todate));
                }
                if ($todate && $fromdate) {
                    if ($todate > $fromdate) {
                        $shoppingcartRules->getSelect()->where("(to_date IS NULL) OR date(to_date) >= date(?)", date("Y-m-d", $fromdate));
                        $shoppingcartRules->getSelect()->where("(from_date IS NULL) OR date(from_date) <= date(?)", date("Y-m-d", $todate));
                    }
                }
                $shoppingcartRules->setOrder('priority', 'ASC');

            }
        }
        return $shoppingcartRules;
    }

    public function getListCatalogRule()
    {
        $catalogRules = Mage::getModel('promotionalgift/catalogrule')
            ->getCollection()
            ->addFieldToFilter('website_ids', array('finset' => Mage::app()->getStore()->getWebsiteId()))
            ->addFieldToFilter('status', '1');
        if (Mage::getModel('customer/session')->isLoggedIn()) {
            $customer = Mage::getModel('customer/customer')->load(Mage::getModel('customer/session')->getCustomerId());
            $catalogRules->addFieldToFilter('customer_group_ids', array('finset' => $customer->getGroupId()));
        } else {
            $catalogRules->addFieldToFilter('customer_group_ids', array('finset' => Mage_Customer_Model_Group::NOT_LOGGED_IN_ID));
        }

        $catalogRules->getSelect()->where('(uses_limit IS NULL) OR (uses_limit > 0)');

        $fromdate = strtotime($this->getRequest()->getParam('fromdate'));
        $todate = strtotime($this->getRequest()->getParam('todate'));
        if ($catalogRules) {
            if ($fromdate && !$todate) {
                $catalogRules->getSelect()->where("(to_date IS NULL) OR date(to_date) >= date(?)", date("Y-m-d", $fromdate));
            }
            if ($todate && !$fromdate) {
                $catalogRules->getSelect()->where("(from_date IS NULL) OR date(from_date) <= date(?)", date("Y-m-d", $todate));
            }
            if ($todate && $fromdate) {
                if ($todate > $fromdate) {
                    $catalogRules->getSelect()->where("(to_date IS NULL) OR date(to_date) >= date(?)", date("Y-m-d", $fromdate));
                    $catalogRules->getSelect()->where("(from_date IS NULL) OR date(from_date) <= date(?)", date("Y-m-d", $todate));
                }
            }
            $catalogRules->setOrder('priority', 'ASC');
        }
        return $catalogRules;
    }

    public function getRuleByPruduct($productId)
    {
        if (Mage::app()->getRequest()->getParam('catalogrule')) {
            $catalogRule = Mage::getModel('promotionalgift/catalogrule')->load(Mage::app()->getRequest()->getParam('catalogrule'));
        } else {
            $catalogRule = Mage::getModel('promotionalgift/catalogrule')->validateItem($productId);
        }
        return $catalogRule;
    }

    public function getFreeGifts($productId, $ruleId)
    {
        // $ruleId = $this->getRuleByPruduct($productId)->getId();
        $freeGrift = Mage::getModel('promotionalgift/catalogitem')->load($ruleId, 'rule_id')->getProductIds();
        $freeGrift = explode(',', $freeGrift);
        return $freeGrift;
    }


}