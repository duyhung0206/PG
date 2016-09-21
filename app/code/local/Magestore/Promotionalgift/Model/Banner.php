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
class Magestore_Promotionalgift_Model_Banner extends Mage_Rule_Model_Rule
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('promotionalgift/banner');
        $this->setIdFieldName('banner_id');
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

    public function checkRule($order)
    {
        if ($this->getIsActive()) {
            $this->afterLoad();
            return $this->validate($order);
        }
        return false;
    }

    public function getAvailableRule()
    {
        $availableRules = $this->getCollection();
        $availableRules->addFieldToFilter('website_ids', array('finset' => Mage::app()->getStore()->getWebsiteId()));
        $availableRules->addFieldToFilter('status', '1');
        if (Mage::getModel('customer/session')->isLoggedIn()) {
            $customer = Mage::getModel('customer/customer')->load(Mage::getModel('customer/session')->getCustomerId());
            $availableRules->addFieldToFilter('customer_group_ids', array('finset' => $customer->getGroupId()));
        } else {
            $availableRules->addFieldToFilter('customer_group_ids', array('finset' => Mage_Customer_Model_Group::NOT_LOGGED_IN_ID));
        }
        $availableRules->getSelect()->where('(from_date IS NULL) OR (date(from_date) <= date(?))', Mage::getModel('core/date')->date('Y-m-d'));
        $availableRules->getSelect()->where('(to_date IS NULL) OR (date(to_date) >= date(?))', Mage::getModel('core/date')->date('Y-m-d'));
        $availableRules->setOrder('priority', 'ASC');
        $availableRules->setOrder('banner_id', 'DESC');
        if (count($availableRules)) {
            return $availableRules;
        }
        return null;
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
    
    public function validateQuote($quote)
    {
        if ($quote->isVirtual()) {
            $address = $quote->getBillingAddress();
        } else {
            $address = $quote->getShippingAddress();
        }
        $availableRules = $this->getAvailableRule();
        //validate rule
        if (count($availableRules)) {
            foreach ($availableRules as $availableRule) {
                $availableRule->afterLoad();
                $checkCalendar = Mage::helper('promotionalgift')->checkBannerCalendar($availableRule);
                if ($availableRule->validate($address) && $checkCalendar== true) {
                    $newAvailableRules[] = $availableRule;
                }
            }
        }
        if (count($newAvailableRules)) {
            return $newAvailableRules;
        } else {
            return false;
        }

    }
}
