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
class Magestore_Promotionalgift_Block_Shoppingcart extends Mage_Core_Block_Template
{

    /**
     * prepare block's layout
     *
     * @return Magestore_Promotionalgift_Block_Shoppingcart
     */
    public function _prepareLayout()
    {
        Mage::helper('promotionalgift/rule')->checkRules();
        $this->setTemplate('promotionalgift/multipleshoppingcartrules.phtml');
        return parent::_prepareLayout();
    }

    public function getCouponCodeRule()
    {
        $session = Mage::getModel('checkout/session');
        $quoteId = Mage::getModel('checkout/session')->getQuote()->getId();
        $ruleId = $session->getData('promotionalgift_shoppingcart_rule_id');
        $ruleIds = array();
        if ($ruleId) {
            $rule = Mage::getModel('promotionalgift/shoppingcartrule')->load($ruleId);
            if (!Mage::helper('promotionalgift/rule')->validateRuleQuote($rule)) {
                $rule = false;
            }
        }
        if (isset($rule) && $rule != false) {
            $maxItems = $rule->getNumberItemFree();
            $checkCalendar = Mage::helper('promotionalgift')->checkCalendar($rule);
            $ruleUsed = Mage::getModel('promotionalgift/shoppingquote')
                ->getCollection()
                ->addFieldToFilter('shoppingcartrule_id', $rule->getId())
                ->addFieldToFilter('quote_id', $quoteId);
            if (count($ruleUsed) == 0 || count($ruleUsed) < $maxItems) {
                if ($checkCalendar == true) {
                    $ruleIds[] = $rule->getId();
                }
            }
            return $ruleIds;
        } else
            return false;
    }

    public function getTotalItem($rule)
    {
        $item = array();
        if ($rule != false) {
            $ruleItems = Mage::getModel('promotionalgift/shoppingcartitem')->load($rule->getId(), 'rule_id')
                ->getProductIds();
            $items = explode(',', $ruleItems);
            $totalItems = count($items);
            return $totalItems;
        }
        return false;
    }

    public function getListShoppingcartRule()
    {
        $ruleActived = Mage::helper('promotionalgift/rule')->getShoppingcartRule();
        if ($ruleActived) {
            $ruleId = $ruleActived->getId();
            $priority = $ruleActived->getPriority();
        }
        $shoppingcartRules = Mage::getModel('promotionalgift/shoppingcartrule')
            ->getAvailableRule();
        if (isset($ruleId) && $ruleId > 0) {
            $rulePriorityLess = $this->getRulePriorityLess($ruleActived);
            $shoppingcartRules = Mage::getModel('promotionalgift/shoppingcartrule')
                ->getAvailableRule($rulePriorityLess);
        }
        return $shoppingcartRules;
    }

    public function getRuleName($name)
    {
        if (strlen($name) >= 85) {
            $name = substr($name, 0, 84);
            $name = $name . '...';
        }
        return $name;
    }

    public function getQtyProductRule($product, $rule)
    {
        if ($rule != false) {
            $giftitems = Mage::helper('promotionalgift')->getShoppingcartFreeGifts($rule);
            foreach ($giftitems as $giftitem) {
                if ($giftitem['product_id'] == $product->getId())
                    return $giftitem['gift_qty'];
            }
        }
        return false;
    }
    public function getShoppingCartRules(){
        $ruleIds = Mage::helper('promotionalgift/rule')->getShoppingcartRule();
        $page = Mage::app()->getRequest()->getParam('page');
        if($page)
            $currentRule = array($ruleIds[$page-1]);
        else
             $currentRule = array($ruleIds[0]);
        return $currentRule;
    }
}
