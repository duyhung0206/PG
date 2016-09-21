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
class Magestore_Promotionalgift_Model_Quote_Freeshipping extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    public function __construct()
    {
        $this->setCode('discount');
        $this->_calculator = Mage::getSingleton('salesrule/validator');
    }

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        parent::collect($address);
        $quote = $address->getQuote();
        $store = Mage::app()->getStore($quote->getStoreId());
        $address->setFreeShipping(0);
        $items = $this->_getAddressItems($address);
        if (!count($items)) {
            return $this;
        }
        $this->_calculator->init($store->getWebsiteId(), $quote->getCustomerGroupId(), $quote->getCouponCode());
        foreach ($items as $item) {
            $quotes = Mage::getModel('promotionalgift/quote')->getCollection()->addFieldToFilter('item_id', $item->getId())->getFirstItem();

            if ($quotes->getId()) {
                $isfreeShipping = Mage::getModel('promotionalgift/catalogrule')->load($quotes->getCatalogRuleId())->getFreeShipping();
                if ($isfreeShipping) {
                    $item->setFreeShipping(true);
                    // $item->save();
                } else {
                    $item->setFreeShipping(false);
                }
            } else {
                $shoppingCartQuotes = Mage::getModel('promotionalgift/shoppingquote')->getCollection()
                    ->addFieldToFilter('item_id', $item->getId())
                    ->getFirstItem();
                if ($shoppingCartQuotes->getId()) {
                    $isfreeShipping = Mage::getModel('promotionalgift/shoppingcartrule')->load($shoppingCartQuotes->getShoppingcartruleId())->getFreeShipping();
                    if ($isfreeShipping) {
                        $item->setFreeShipping(true);
                        // $item->save();
                    } else {
                        $item->setFreeShipping(false);
                    }
                } else {
                    $this->_calculator->processFreeShipping($item);
                }
            }
        }

        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        return $this;
    }
}
