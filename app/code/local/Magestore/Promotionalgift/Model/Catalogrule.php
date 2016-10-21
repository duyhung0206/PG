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
class Magestore_Promotionalgift_Model_Catalogrule extends Mage_Rule_Model_Rule
{

    protected $_productIds;

    public function _construct()
    {
        parent::_construct();
        $this->_init('promotionalgift/catalogrule');
        $this->setIdFieldName('rule_id');
    }

    public function getConditionsInstance()
    {
        return Mage::getModel('promotionalgift/catalogrule_condition_combine');
    }

    public function validateItem($productId)
    {
   /*      if(Mage::app()->getRequest()->getParam('page'))
            $availableRules = $this->getCurrentRule();
        else */
		$availableRules = $this->getAvailableRule();
        if (!$availableRules) {
            return false;
        }
        foreach ($availableRules as $availableRule) {
            $catalogProduct = Mage::getModel('promotionalgift/catalogproduct')
                ->getCollection()
                ->addFieldToFilter('rule_id', $availableRule->getId())
                ->addFieldToFilter('product_id', $productId)
                ->getFirstItem();
            $checkCalendar = Mage::helper('promotionalgift')->checkCalendar($availableRule);
            if ($catalogProduct->getId() && $checkCalendar == true) {
                return $availableRule;
            }
        }

        return false;
    }
    public function getAllRules(){
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
        $availableRules->getSelect()->where('(uses_limit IS NULL) OR (uses_limit > 0)');

        $availableRules->setOrder('priority', 'ASC');
        $availableRules->setOrder('rule_id', 'DESC');
        return $availableRules;
    }
    public function getCurrentRule(){
        $page = Mage::app()->getRequest()->getParam('page');
        $availableRules = $this->getAllRules();
		$catalogRuleIds = Mage::helper('promotionalgift')->getCurrentAvailableRule();
		$availableRules->addFieldToFilter('rule_id',array('in' => $catalogRuleIds));
        $availableRules->setPageSize(1)->setCurPage($page);
        if (count($availableRules->getData())) {
            return $availableRules;
        }
        return null;
    }
    public function getAvailableRuleIds()
    {
        // if(!Mage::helper('promotionalgift')->checkModuleEnable()) return null;
        $availableRules = $this->getAllRules();
		$catalogRuleIds = Mage::helper('promotionalgift')->getCurrentAvailableRule();
		$availableRules->addFieldToFilter('rule_id',array('in' => $catalogRuleIds));
        $availableRules->setPageSize(1)->setCurPage(1);
        if (count($availableRules->getData())) {
            return $availableRules;
        }
        return null;
    }
    public function getAvailableRule()
    {
        // if(!Mage::helper('promotionalgift')->checkModuleEnable()) return null;
        $availableRules = $this->getAllRules();
        if (count($availableRules->getData())) {
            return $availableRules;
        }
        return null;
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

    /**
     * Fix bug when save website ids and customer group id in magento v1.7
     *
     * */
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

    public function updateRuleProductData($rule)
    {
        $ruleId = $rule->getId();
        $resource = Mage::getSingleton('core/resource');
        $writeConnection = $resource->getConnection('core_write');
        // $installer = Mage::getModel('core/resource_setup');

        if (is_null($this->_productIds)) {
            $this->_productIds = array();
        }
        if (is_null($this->_notPassProducts)) {
            $this->_productIds = array();
        }


        /** @var $productCollection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */
        $productCollection = Mage::getResourceModel('catalog/product_collection');

        Mage::getSingleton('core/resource_iterator')->walk(
            $productCollection->getSelect(), array(array($this, 'ruleProductDataCallback')), array(
                'product' => Mage::getModel('catalog/product'),
                'rule' => $rule
            )
        );
        $writeConnection->beginTransaction();
        if (count($this->_productIds) > 0) {
            //delete invalid product id and rule id to promotionalgift_catalog_table
            $writeConnection->delete($resource->getTableName('promotionalgift/catalogproduct'), $writeConnection->quoteInto('rule_id=?', $ruleId));
            //insert valid product id and rule id to promotionalgift_catalog_table
            $rows = array();
            foreach ($this->_productIds as $productId) {
                $rows[] = array(
                    'rule_id' => $ruleId,
                    'product_id' => $productId
                );
                if (count($rows) == 900) {
                    $writeConnection->insertMultiple($resource->getTableName('promotionalgift/catalogproduct'), $rows);
                    $rows = array();
                }
            }
        } else {
            $writeConnection->delete($resource->getTableName('promotionalgift/catalogproduct'), $writeConnection->quoteInto('rule_id=?', $ruleId));
        }
        if (count($rows) > 0) {
            $writeConnection->insertMultiple($resource->getTableName('promotionalgift/catalogproduct'), $rows);
        }

        $writeConnection->commit();
    }

    public function ruleProductDataCallback($args)
    {
        $rule = $args['rule'];
        $product = clone $args['product'];
        $product->setData($args['row']);
        $productId = $product->getId();
        $product = Mage::getModel('catalog/product')->load($productId);
        if ($rule->validate($product)) {
            $this->_productIds[] = $productId;
        }
    }

}
