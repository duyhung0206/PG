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

class Magestore_Promotionalgift_Helper_Rule extends Mage_Checkout_Helper_Cart
{

    /**
     * Get and check existing of shopping quote
     * Return boolean
     */
    public function checkShoppingQuote($quoteId, $itemId, $shoppingcartRuleId)
    {
        //get shopping quote
        $shoppingQuote = Mage::getModel('promotionalgift/shoppingquote')
            ->getCollection()
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('item_id', $itemId)
            ->getFirstItem();
        //check shopping quote
        if (isset($shoppingQuote) && $shoppingQuote->getId() != null) {
            return true;
        }
        return false;
    }
    public function getCatagoryRuleCurrent()
    {
        $catalogRule = Mage::getModel('promotionalgift/catalogrule')->getAvailableRule();
        $catalogids=array();
        foreach($catalogRule as $rule)
        {
            $catalogids[]=$rule->getId();
        }
        return $catalogids; //->getFirstItem();
    }
    public function getCatagoryRuleCurrentAjax()
    {
        $catalogRule = Mage::getModel('promotionalgift/catalogrule')->getCurrentRule();
        $catalogids=array();
        foreach($catalogRule as $rule)
        {
            $catalogids[]=$rule->getId();
        }
        return $catalogids; //->getFirstItem();
    }
    public function getCouponCodeRule()
    {
        $session = Mage::getModel('checkout/session');
        $quoteId = Mage::getModel('checkout/session')->getQuote()->getId();
        $ruleId = $session->getData('promotionalgift_shoppingcart_rule_id');
        $ruleIds = array();
        if ($ruleId) {
            $rule = Mage::getModel('promotionalgift/shoppingcartrule')->load($ruleId);
            $customer=Mage::getSingleton('customer/session')->getCustomer();
            //check customer with coupon code
            $isLogin=Mage::getSingleton('customer/session')->isLoggedIn();
            if($isLogin)
            {
                if (!in_array($customer->getGroupId(), explode(",", $rule->getCustomerGroupIds())))
                {
                    return false;
                }
            }
            if (!$this->validateRuleQuote($rule)) {
                
                $rule = false;
            }
        }
        if (isset($rule) && $rule != false) {
            $checkCalendar = Mage::helper('promotionalgift')->checkCalendar($rule);
            $checkRemainQtyRule = Mage::helper('promotionalgift')->checkRemainQtyRule($rule, $quoteId);
            if ($checkRemainQtyRule) {
                if ($checkCalendar == true) {
                    $ruleIds[] = $rule->getId();
                }
            }
            return $ruleIds;
        } else
            return false;
    }

    public function getShoppingcartRule()
    {
        $quote = Mage::getModel('checkout/session')->getQuote();
        //$rules = Mage::getModel('promotionalgift/shoppingcartrule')->validateQuote1($quote);
        $rules = Mage::getModel('promotionalgift/shoppingcartrule')->validateQuote($quote);
        $session = Mage::getModel('checkout/session');
        $quoteId = Mage::getModel('checkout/session')->getQuote()->getId();
        $ruleIds = array();
        $isMultiple = Mage::getStoreConfig('promotionalgift/shoppingcart_rule_configuration/multipleshoppingcartrule');
        if ($isMultiple != 1) {
            if ($this->getCouponCodeRule()) {
                return $this->getCouponCodeRule();
            }
            if ($rules != false) {
                $rule = $rules[0];
                $maxItems = $rule->getNumberItemFree();
                $checkCalendar = Mage::helper('promotionalgift')->checkCalendar($rule);
                $checkRemainQtyRule = Mage::helper('promotionalgift')->checkRemainQtyRule($rule, $quoteId);
                if ($checkRemainQtyRule) {
                    if ($checkCalendar == true) {
                        $ruleIds[] = $rule->getId();
                    }
                }
                return $ruleIds;
            } else {
                return false;
            }
        } else {
            $number = Mage::getStoreConfig('promotionalgift/shoppingcart_rule_configuration/numberofshoppingcartrule');
            $checkRulesUsed = array();
            if ($this->getCouponCodeRule()) {
                $couponRules = $this->getCouponCodeRule();
                foreach ($couponRules as $couponRule) {
                    $rules[] = Mage::getModel('promotionalgift/shoppingcartrule')->load($couponRule);
                }
            }
            if (is_array($rules)) {
                foreach ($rules as $rule) {
                    $checkCalendar = Mage::helper('promotionalgift')->checkCalendar($rule);
                    $ruleUsed = Mage::getModel('promotionalgift/shoppingquote')
                        ->getCollection()
                        ->addFieldToFilter('shoppingcartrule_id', $rule->getId())
                        ->addFieldToFilter('quote_id', $quoteId);
                    $checkRemainQtyRule = Mage::helper('promotionalgift')->checkRemainQtyRule($rule, $quoteId);
                    $maxItems = $rule->getNumberItemFree();
                    if (count($ruleUsed)) {
                        $checkRulesUsed[] = $rule->getId();
                    }
                    if ($checkRemainQtyRule) {
                        if ($checkCalendar == true) {
                            $ruleIds[] = $rule->getId();
                        }
                    }

                    if (count($checkRulesUsed) == $number) {
                        break;
                    }
                }
            }

            if (count($checkRulesUsed) == $number) {
                $ruleIds = array();
                foreach ($checkRulesUsed as $checkRuleUsed) {
                    $checkRemainQtyRule = Mage::helper('promotionalgift')->checkRemainQtyRule($rule, $quoteId);
                    if ($checkRemainQtyRule) {
                        $ruleIds[] = $checkRuleUsed;
                    }
                }
            }
            if (count($ruleIds) > 0) {
                $session->setData('promotionalgift_shoppingcart_use_full_rule', null);
                return $ruleIds;
            } else {
                $session->setData('promotionalgift_shoppingcart_use_full_rule', null);
                if (count($checkRulesUsed) == $number) {
                    $session->setData('promotionalgift_shoppingcart_use_full_rule', true);
                }
                return false;
            }
        }
    }

    public function clearRuleSession()
    {
        Mage::getModel('checkout/session')->setData('catalog_rule_id', null);
        Mage::getModel('checkout/session')->setData('product_parent', null);
        Mage::getModel('checkout/session')->setData('free_gift_item', null);
        Mage::getModel('checkout/session')->setData('free_gift_item_qty', null);
        Mage::getModel('checkout/session')->setData('promotionalgift_bundle', null);

        Mage::getModel('checkout/session')->setData('shoppingcart_rule_id', null);
        Mage::getModel('checkout/session')->setData('product_parent', null);
        Mage::getModel('checkout/session')->setData('promotionalgift_shoppingcart_grouped', null);
    }

    public function validateRuleQuote($availableRule)
    {
        $quote = Mage::getModel('checkout/session')->getQuote();
        if ($quote->isVirtual()) {
            $address = $quote->getBillingAddress();
        } else {
            $address = $quote->getShippingAddress();
        }
        $taxConfig = Mage::getStoreConfig('tax/cart_display/subtotal', Mage::app()->getStore()->getStoreId());
        $availableRule->afterLoad();
        /* validate subtotal include tax if subtotal is displayed include tax on store */
        if ($taxConfig == 2) {
            $address->setSubtotal($address->getSubtotalInclTax());
            $address->setBaseSubtotal($address->getSubtotalInclTax());
        }
        $address->collectTotals();
        if ($availableRule->validate($address))
            return $availableRule;
        return false;
    }

    public function checkCartProductNotGift()
    {
        foreach (Mage::getModel('checkout/cart')->getQuote()->getAllItems() as $item) {
            $isGift = false;
            $itemOptions = $item->getOptions();
            $productId = $item->getProductId();
            foreach ($itemOptions as $option) {
                $oData = $option->getData();
                if (!$item->getParentItemId()) {
                    if ($oData['code'] == 'option_promotionalgift_shoppingcartrule' || $oData['code'] == 'option_promotionalgift_catalogrule') {
                        $isGift = true;
                    }
                }
            }
            if (!$isGift && !$item->getParentItemId()) {
                $notGifts[$item->getId()] = $productId;
            }
        }

        return $notGifts;
    }

    public function checkRules()
    {
        $quote = Mage::getModel('checkout/session')->getQuote();
        //catalogrule
        $quotes = Mage::getModel('promotionalgift/quote')->getCollection()
            ->addFieldToFilter('quote_id', $quote->getId());
        if (count($quotes) > 0) {
            $this->checkCatalogRule($quotes);
        }

        //shoppingcartrule
        $shoppingQuotes = Mage::getModel('promotionalgift/shoppingquote')->getCollection()
            ->addFieldToFilter('quote_id', $quote->getId());
        if (count($shoppingQuotes)) {
            $this->checkCartRule($shoppingQuotes);
        }
        //clear rule session
        $this->clearRuleSession();

        //redirect if no items is in cart
        $notGifts = $this->checkCartProductNotGift();
        if (count($notGifts) <= 0) {
            $url = Mage::getUrl('checkout/cart');
            return $url;
        }
    }

    public function checkCartRule($shoppingQuotes)
    {
        $ruleIds = array();
        $quote = Mage::getModel('checkout/session')->getQuote();
        $rules = Mage::getModel('promotionalgift/shoppingcartrule')->validateQuote($quote);
        if ($rules) {
            foreach ($rules as $rule)
                $ruleIds[] = $rule->getId();
        }
        if (count($ruleIds) == 0) {
            $this->deleteGiftItemOfRule();
        } elseif (count($ruleIds) > 0) {
            $this->deleteGiftItemOfRule($ruleIds);
        }
    }

    public function deleteGiftItemOfRule($ruleId = null)
    {
        $session = Mage::getModel('checkout/session');
        $shoppingcartRuleId = $session->getData('promotionalgift_shoppingcart_rule_id');
        $quote = $session->getQuote();
        $quoteId = $quote->getId();
        $cart = Mage::getModel('checkout/cart');
        $shoppingQuotes = Mage::getModel('promotionalgift/shoppingquote')->getCollection()
            ->addFieldToFilter('quote_id', $quoteId);
        $notGifts = $this->checkCartProductNotGift();
//        Zend_Debug::Dump($ruleId);die();
        if (count($notGifts) <= 0) {
            foreach ($shoppingQuotes as $shoppingQuote) {
                try {
                    $item = $cart->getQuote()->getItemById($shoppingQuote->getItemId());
                    if ($item) {
                        $cart->getQuote()->removeItem($shoppingQuote->getItemId())->save();
                        $shoppingQuote->delete();
                    }
                } catch (Exception $e) {

                }
            }
        } else {
            if ($ruleId) {
                $shoppingQuotes = $shoppingQuotes->addFieldToFilter('shoppingcartrule_id', array('nin' => $ruleId));
            }
            if ($shoppingcartRuleId) {
                $rule = Mage::getModel('promotionalgift/shoppingcartrule')->load($shoppingcartRuleId);
                if (!$this->validateRuleQuote($rule)) {
                    $session->setData('promotionalgift_shoppingcart_rule_id', null);
                    $session->setData('shoppingcart_couponcode_rule_id', null);
                    $session->setData('promotionalgift_shoppingcart_rule_used', null);
                    $session->setData('promptionalgift_coupon_code', null);
                } else {
                    $shoppingQuotes = $shoppingQuotes->addFieldToFilter('shoppingcartrule_id', array('nin' => $shoppingcartRuleId));
                }
            }
            $change = 0;
            if (count($shoppingQuotes)) {
                foreach ($shoppingQuotes as $shoppingQuote) {
                    try {
                        $item = $cart->getQuote()->getItemById($shoppingQuote->getItemId());
                        if ($item) {
                            $cart->getQuote()->removeItem($shoppingQuote->getItemId())->save();
                            $shoppingQuote->delete();
                        }
                    } catch (Exception $e) {

                    }
                }
            }
        }
    }

    public function checkCatalogRule($quotes)
    {
        $catRuleGifts = array();
        $cart = Mage::getModel('checkout/cart');
        foreach ($quotes as $quote) {
            if (!in_array($quote->getItemId(), $catRuleGifts)) {
                $catRuleGifts[] = array('quote_id' => $quote->getQuoteId(), 'item_id' => $quote->getItemId(), 'catalog_rule_id' => $quote->getCatalogRuleId());
            }
        }
        $notGifts = $this->checkCartProductNotGift();
        if (count($notGifts) > 0) {
            $availableRuleIds = array();
            foreach ($notGifts as $itemId => $productId) {
                $cRule = Mage::getModel('promotionalgift/catalogrule')->validateItem($productId);
                if ($cRule) {
                    $availableRuleIds[] = $cRule->getRuleId();
                }
            }
            if (count($availableRuleIds) > 0) {
                foreach ($catRuleGifts as $catRuleGift) {
                    if (!in_array($catRuleGift['catalog_rule_id'], $availableRuleIds)) {
                        Mage::getModel('promotionalgift/quote')->getCollection()
                            ->addFieldToFilter('item_id', $catRuleGift['item_id'])
                            ->addFieldToFilter('quote_id', $catRuleGift['quote_id'])
                            ->getFirstItem()
                            ->delete();
                        Mage::getModel('checkout/cart')->getQuote()->removeItem($catRuleGift['item_id'])->save();
                    } else {
                        $freeGifts = Mage::getModel('promotionalgift/catalogitem')
                            ->load($catRuleGift['catalog_rule_id'], 'rule_id');
                        $productIds = explode(',', $freeGifts->getProductIds());
                        $cGift = $cart->getQuote()->getItemById($catRuleGift['item_id']);
                        if ($cGift) {
                            $cGiftProduct = $cGift->getProductId();
                        }
                        if (!in_array($cGiftProduct, $productIds)) {
                            Mage::getModel('promotionalgift/quote')->getCollection()
                                ->addFieldToFilter('item_id', $catRuleGift['item_id'])
                                ->addFieldToFilter('quote_id', $catRuleGift['quote_id'])
                                ->getFirstItem()
                                ->delete();
                            Mage::getModel('checkout/cart')->getQuote()->removeItem($catRuleGift['item_id'])->save();
                        }
                    }
                }
            }
        } else {
            foreach ($catRuleGifts as $gItem) {
                Mage::getModel('promotionalgift/quote')->getCollection()
                    ->addFieldToFilter('item_id', $gItem['item_id'])
                    ->addFieldToFilter('quote_id', $gItem['quote_id'])
                    ->getFirstItem()
                    ->delete();
                Mage::getModel('checkout/cart')->getQuote()->removeItem($gItem['item_id'])->save();
            }
        }
    }

    public function getItemIds()
    {
        $quoteId = Mage::getModel('checkout/session')->getQuote()->getId();
        $itemIds = array();
        if ($quoteId) {
            $giftItems = Mage::getModel('promotionalgift/shoppingquote')->getCollection()
                ->addFieldToFilter('quote_id', $quoteId);
            foreach ($giftItems as $item) {
                $itemIds[] = $item->getItemId();
            }

            $productGifts = Mage::getModel('promotionalgift/quote')->getCollection()
                ->addFieldToFilter('quote_id', $quoteId);
            foreach ($productGifts as $product) {
                $itemIds[] = $product->getItemId();
            }
            return implode(',', $itemIds);
        }
    }

    public function getEidtItemIds()
    {
        $quote = Mage::getModel('checkout/session')->getQuote();
        $quoteId = $quote->getId();
        $itemIds = array();
        if ($quoteId) {
            $giftItems = Mage::getModel('promotionalgift/shoppingquote')->getCollection()
                ->addFieldToFilter('quote_id', $quoteId);
            foreach ($giftItems as $item) {
                if (!$this->checkItemOption($item->getItemId())) {
                    $itemIds[] = $item->getItemId();
                }
            }

            $productGifts = Mage::getModel('promotionalgift/quote')->getCollection()
                ->addFieldToFilter('quote_id', $quoteId);
            foreach ($productGifts as $product) {
                if (!$this->checkItemOption($product->getItemId())) {
                    $itemIds[] = $product->getItemId();
                }
            }
            return implode(',', $itemIds);
        }
    }

    public function checkItemOption($itemId)
    {
        $item = Mage::getModel('sales/quote_item')->load($itemId);
        $productId = $item->getProductId();
        $product = Mage::getModel('catalog/product')->load($productId);
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE || $product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE || $product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED || $product->getOptions()
        ) {
            return true;
        }
        return false;
    }

    public function getEidtItemOptionIds()
    {
        $quote = Mage::getModel('checkout/session')->getQuote();
        $quoteId = $quote->getId();
        $itemIds = array();
        if ($quoteId) {
            $giftItems = Mage::getModel('promotionalgift/shoppingquote')->getCollection()
                ->addFieldToFilter('quote_id', $quoteId);
            foreach ($giftItems as $item) {
                $itemIds[] = $item->getItemId();
            }

            $productGifts = Mage::getModel('promotionalgift/quote')->getCollection()
                ->addFieldToFilter('quote_id', $quoteId);
            foreach ($productGifts as $product) {
                $itemIds[] = $product->getItemId();
            }
            return implode(',', $itemIds);
        }
    }
}
