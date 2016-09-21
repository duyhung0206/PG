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
 * Promotionalgift Model
 *
 * @category    Magestore
 * @package     Magestore_Promotionalgift
 * @author      Magestore Developer
 */
class Magestore_Promotionalgift_Model_Shoppingcartrule extends Mage_Rule_Model_Rule
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('promotionalgift/shoppingcartrule');
    }

    public function getConditionsInstance()
    {
        return Mage::getModel('salesrule/rule_condition_combine');
    }

    public function getActionsInstance()
    {
        return Mage::getModel('salesrule/rule_condition_product_combine');
    }

    /**
     * Fix error when load and save with collection
     */
    protected function _afterLoad()
    {
        $this->setConditions(null);
        $this->setActions(null);
        return parent::_afterLoad();
    }

    protected function _beforeSave()
    {
        parent::_beforeSave();
        if ($this->hasWebsiteIds()) {
            $websiteIds = $this->getWebsiteIds();
            if (is_array($websiteIds) && !empty($websiteIds)) {
                $this->setWebsiteIds(implode(',', $websiteIds));
            }
        }
        if ($this->hasCustomerGroupIds()) {
            $groupIds = $this->getCustomerGroupIds();
            if (is_array($groupIds) && !empty($groupIds)) {
                $this->setCustomerGroupIds(implode(',', $groupIds));
            }
        }
        if ($this->hasDaily()) {
            $daily = $this->getDaily();
            if (is_array($daily) && !empty($daily)) {
                $this->setDaily(implode(',', $daily));
            }
        }
        if ($this->hasMonthly()) {
            $monthly = $this->getMonthly();
            if (is_array($monthly) && !empty($monthly)) {
                $this->setMonthly(implode(',', $monthly));
            }
        }
        if ($this->hasWeekly()) {
            $weekly = $this->getWeekly();
            if (is_array($weekly) && !empty($weekly)) {
                $this->setWeekly(implode(',', $weekly));
            }
        }
        if ($this->hasYearly()) {
            $yearly = $this->getYearly();
            if (is_array($yearly) && !empty($yearly)) {
                $this->setYearly(implode(',', $yearly));
            }
        }
        return $this;
    }

    /*
      List available shoppingcart rules
     */

    public function getAvailableRule($ruleIds = array())
    {
        // if(!Mage::helper('promotionalgift')->checkModuleEnable()) return null;
        $availableRules = $this->getCollection();
        if (isset($ruleIds) && count($ruleIds) > 0) {
            $availableRules = $availableRules->addFieldToFilter('rule_id', array('nin' => array($ruleIds)));
        }
        $availableRules->addFieldToFilter('website_ids', array('finset' => Mage::app()->getStore()->getWebsiteId()));
        $availableRules->addFieldToFilter('status', '1');
        $availableRules->addFieldToFilter('number_item_free', array('gt' => 0));
        $availableRules->addFieldToFilter('coupon_type', '1');
        // $availableRules->addFieldToFilter('coupon_code', array('null'=>1));
        // $availableRules->addFieldToFilter('uses_per_coupon', array('gt'=>0));
        if (Mage::getModel('customer/session')->isLoggedIn()) {
            $customer = Mage::getModel('customer/customer')->load(Mage::getModel('customer/session')->getCustomerId());
            $availableRules->addFieldToFilter('customer_group_ids', array('finset' => $customer->getGroupId()));
        } else {
            $availableRules->addFieldToFilter('customer_group_ids', array('finset' => Mage_Customer_Model_Group::NOT_LOGGED_IN_ID));
        }
        $availableRules->getSelect()->where('(uses_per_coupon IS NULL) OR (uses_per_coupon > 0)');
        $availableRules->getSelect()->where('(from_date IS NULL) OR (date(from_date) <= date(?))', Mage::getModel('core/date')->date('Y-m-d'));
        $availableRules->getSelect()->where('(to_date IS NULL) OR (date(to_date) >= date(?))', Mage::getModel('core/date')->date('Y-m-d'));
        $availableRules->setOrder('priority', 'ASC')->setOrder('rule_id', 'DESC');
        if (count($availableRules))
            return $availableRules;
        return null;
    }

    public function getAvailableCouponRule($ruleIds = array())
    {
        // if(!Mage::helper('promotionalgift')->checkModuleEnable()) return null;
        $availableRules = $this->getCollection();
        if (isset($ruleIds) && count($ruleIds) > 0) {
            $availableRules = $availableRules->addFieldToFilter('rule_id', array('nin' => array($ruleIds)));
        }
        $availableRules->addFieldToFilter('website_ids', array('finset' => Mage::app()->getStore()->getWebsiteId()));
        $availableRules->addFieldToFilter('status', '1');
        $availableRules->addFieldToFilter('number_item_free', array('gt' => 0));
        if (Mage::getModel('customer/session')->isLoggedIn()) {
            $customer = Mage::getModel('customer/customer')->load(Mage::getModel('customer/session')->getCustomerId());
            $availableRules->addFieldToFilter('customer_group_ids', array('finset' => $customer->getGroupId()));
        } else {
            $availableRules->addFieldToFilter('customer_group_ids', array('finset' => Mage_Customer_Model_Group::NOT_LOGGED_IN_ID));
        }
        $availableRules->getSelect()->where('(uses_per_coupon IS NULL) OR (uses_per_coupon > 0)');
        $availableRules->getSelect()->where('(from_date IS NULL) OR (date(from_date) <= date(?))', date("Y-m-d", strtotime(now())));
        $availableRules->getSelect()->where('(to_date IS NULL) OR (date(to_date) >= date(?))', date("Y-m-d", strtotime(now())));
        $availableRules->setOrder('priority', 'ASC')->setOrder('rule_id', 'DESC');
        if (count($availableRules))
            return $availableRules;
        return null;
    }


    public function validateQuote($quote)
    {
        $oldsubtotal = Mage::getModel('checkout/cart')->getQuote()->getSubtotal();
        $newsubtotal = Mage::getModel('checkout/cart')->getQuote()->getSubtotal();
        /* calculate subtotal include tax if subtotal is displayed include tax on store */
        $taxConfig = Mage::getStoreConfig('tax/cart_display/subtotal', Mage::app()->getStore()->getStoreId());
        if ($taxConfig == 2) {
            if (Mage::getModel('checkout/cart')->getQuote()->isVirtual()) {
                $oldsubtotal = Mage::getModel('checkout/cart')->getQuote()->getBillingAddress()->getSubtotalInclTax();
                $newsubtotal = Mage::getModel('checkout/cart')->getQuote()->getBillingAddress()->getSubtotalInclTax();
            } else {
                $oldsubtotal = Mage::getModel('checkout/cart')->getQuote()->getShippingAddress()->getSubtotalInclTax();
                $newsubtotal = Mage::getModel('checkout/cart')->getQuote()->getShippingAddress()->getSubtotalInclTax();
            }
        }

        if ($quote->isVirtual()) {
            $address = $quote->getBillingAddress();
        } else {
            $address = $quote->getShippingAddress();
        }

        //clone object quote and address
        $validatedQuote = clone $quote;
        $validatedAddress = clone $address;

        //delete item gift from clone quote
        foreach (Mage::getModel('checkout/cart')->getItems() as $item) {
            $itemId = $item->getId();
            $itemGift = $quote->getItemById($itemId);
            if ($itemGift) {
                $itemOptions = $itemGift->getOptions();
                foreach ($itemOptions as $option) {
                    $oData = $option->getData();
                    if (!$itemGift->getParentItemId()) {
                        if ($oData['code'] == 'option_promotionalgift_catalogrule' || $oData['code'] == 'option_promotionalgift_shoppingcartrule') {
                            //$validatedQuote->removeItem($itemGift->getId());
                            $itemGift->isDeleted(true);
                        }
                    }
                }
            } else {
                continue;
            }
        }


        //recollect totals
        $validatedQuote->collectTotals();
        $validatedAddress->collectTotals();
        $availableRules = $this->getAvailableRule();

        //validate rule
        if (count($availableRules)) {
            foreach ($availableRules as $availableRule) {
                $availableRule->afterLoad();
                if ($availableRule->validate($validatedAddress)) {
                    $newAvailableRules[] = $availableRule;
                    $ruleIds[] = $availableRule->getId();
                }
            }
        }
        foreach (Mage::getModel('checkout/cart')->getItems() as $item) {
            $itemId = $item->getId();
            $itemGift = $quote->getItemById($itemId);
            if ($itemGift) {
                $itemOptions = $itemGift->getOptions();
                foreach ($itemOptions as $option) {
                    $oData = $option->getData();
                    if (!$itemGift->getParentItemId()) {
                        if ($oData['code'] == 'option_promotionalgift_catalogrule' || $oData['code'] == 'option_promotionalgift_shoppingcartrule') {
                            //$validatedQuote->removeItem($itemGift->getId());
                            $itemGift->isDeleted(false);
                        }
                    }
                }
            } else {
                continue;
            }
        }

        //return value
        if (count($newAvailableRules)) {
            Mage::helper('promotionalgift/rule')->deleteGiftItemOfRule($ruleIds);
            return $newAvailableRules;
        } else {
            Mage::helper('promotionalgift/rule')->deleteGiftItemOfRule($ruleIds);
            return false;
        }
    }

    public function checkRule($order)
    {
        if ($this->getIsActive()) {
            $this->afterLoad();
            return $this->validate($order);
        }
        return false;
    }

    public function checkCouponCodeExist($couponCode, $ruleId)
    {
        $rules = $this->getCollection()->addFieldToFilter('coupon_code', $couponCode)
            ->addFieldToFilter('rule_id', array('nin' => array($ruleId)));
        if (isset($rules) and $rules->getSize() > 0) {
            return true;
        }
        return false;
    }

}
