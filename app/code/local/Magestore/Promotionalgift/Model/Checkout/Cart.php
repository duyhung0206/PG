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
class Magestore_Promotionalgift_Model_Checkout_Cart extends Mage_Checkout_Model_Cart
{

    /**
     * Update cart items information
     *
     * @param   array $data
     * @return  Mage_Checkout_Model_Cart
     */
    public function updateItems($data)
    {

        //var_dump($data);die();
        $autoUpdateQty = Mage::getStoreConfig('promotionalgift/catalog_rule_configuration/autoupdateqty');
        $itemChange = array();
        if ($autoUpdateQty) {
            foreach ($data as $itemId => $itemData) {
                $newQty = $itemData['qty'];
                $itemModel = Mage::getModel('sales/quote_item')->load($itemId);
                $oldQty = $itemModel->getQty();
                if ($newQty!=$oldQty) {
                    $itemChange[]=$itemId;
                }

            }
        }

        if (Mage::helper('promotionalgift')->enablePromotionalgift()) {
            $sRuleGifts = array();
            $cRuleGifts = array();
            $nRuleGifts = array();
            $ruleTotalGift = array();
            $nGiftItemIds = array();
            $totalCatalogItemGiftQty = array();
            $totalShoppingcartItemGiftQty = array();
            $giftCatalogRuleProductQty = array();
            $giftShoppingcartRuleProductQty = array();
            $super_attribute = array();
            $module = Mage::app()->getRequest()->getModuleName();
            $changeQtyConfig = Mage::getStoreConfig('promotionalgift/general/changegiftqty');
            if ($module == 'onestepcheckout') {
                $quote = $this->getQuote();
                foreach ($quote->getAllItems() as $item) {
                    $isGift = false;
                    $itemId = $item->getId();
                    $itemInfo = null;
                    if ($data[$itemId]) {
                        $itemInfo = $data[$itemId];
                    }
                    $productId = $item->getProductId();
                    $catalogQuote = Mage::getModel('promotionalgift/quote')
                        ->getCollection()
                        ->addFieldToFilter('item_id', $itemId)
                        ->getFirstItem();
                    $shoppingQuote = Mage::getModel('promotionalgift/shoppingquote')
                        ->getCollection()
                        ->addFieldToFilter('item_id', $itemId)
                        ->getFirstItem();
                    if ($catalogQuote->getId()) {
                        $ruleId = $catalogQuote->getCatalogRuleId();
                        $isGift = true;
                        $itemOptions = $item->getOptions();
                        foreach ($itemOptions as $option) {
                            $oData = $option->getData();
                            if ($oData['code'] == 'attributes') {
                                $attributes = unserialize($oData['value']);
                                $super_attribute[$itemId] = $attributes;
                            }
                        }
                        if ($totalCatalogItemGiftQty[$ruleId][$productId]) {
                            if ($totalCatalogItemGiftQty[$ruleId][$productId]['qty']) {
                                if ($itemInfo) {
                                    $totalCatalogItemGiftQty[$ruleId][$productId]['qty'] += $itemInfo['qty'];
                                } else {
                                    $totalCatalogItemGiftQty[$ruleId][$productId]['qty'] += $item->getQty();
                                }
                            }
                            if ($totalCatalogItemGiftQty[$ruleId][$productId]['item']) {
                                $totalCatalogItemGiftQty[$ruleId][$productId]['item'] = $totalCatalogItemGiftQty[$ruleId][$productId]['item'] . ',' . $item->getId();
                            }
                        } else {
                            if ($itemInfo) {
                                $totalCatalogItemGiftQty[$ruleId][$productId]['qty'] += $itemInfo['qty'];
                            } else {
                                $totalCatalogItemGiftQty[$ruleId][$productId]['qty'] += $item->getQty();
                            }
                            $totalCatalogItemGiftQty[$ruleId][$productId]['item'] = $item->getId();
                        }
                        $giftCatalogRuleProductQty = Mage::helper('promotionalgift/cart')->getGiftCatalogRuleProductQty($ruleId, $giftCatalogRuleProductQty);
                        if (empty($cRuleGifts[$ruleId][$item->getId()])) {
                            $cRuleGifts[$ruleId][$item->getId()] = '';
                            $cRuleGifts[$ruleId][$item->getId()] = array('item' => $item->getId(), 'product' => $productId, 'product_name' => $item->getName(), 'in_cart' => $itemInfo['qty'], 'super_attribute' => $super_attribute[$itemId]);
                        }
                    }
                    if ($shoppingQuote->getId()) {
                        $ruleId = $shoppingQuote->getShoppingcartruleId();
                        $isGift = true;
                        $itemOptions = $item->getOptions();
                        foreach ($itemOptions as $option) {
                            $oData = $option->getData();
                            if ($oData['code'] == 'attributes') {
                                $attributes = unserialize($oData['value']);
                                $super_attribute[$itemId] = $attributes;
                            }
                        }
                        if ($totalShoppingcartItemGiftQty[$ruleId][$productId]) {
                            if ($totalShoppingcartItemGiftQty[$ruleId][$productId]['qty']) {
                                if ($itemInfo) {
                                    $totalShoppingcartItemGiftQty[$ruleId][$productId]['qty'] += $itemInfo['qty'];
                                } else {
                                    $totalShoppingcartItemGiftQty[$ruleId][$productId]['qty'] += $item->getQty();
                                }
                            }
                            if ($totalShoppingcartItemGiftQty[$ruleId][$productId]['item']) {
                                $totalShoppingcartItemGiftQty[$ruleId][$productId]['item'] = $totalShoppingcartItemGiftQty[$ruleId][$productId]['item'] . ',' . $item->getId();
                            }
                        } else {
                            if ($itemInfo) {
                                $totalShoppingcartItemGiftQty[$ruleId][$productId]['qty'] += $itemInfo['qty'];
                            } else {
                                $totalShoppingcartItemGiftQty[$ruleId][$productId]['qty'] += $item->getQty();
                            }
                            $totalShoppingcartItemGiftQty[$ruleId][$productId]['item'] = $item->getId();
                        }
                        $giftShoppingcartRuleProductQty = Mage::helper('promotionalgift/cart')->getGiftShoppingcartRuleProductQty($ruleId, $giftShoppingcartRuleProductQty);
                        if (empty($sRuleGifts[$ruleId][$item->getId()])) {
                            $sRuleGifts[$ruleId][$item->getId()] = '';
                            $sRuleGifts[$ruleId][$item->getId()] = array('item' => $item->getId(), 'product' => $productId, 'product_name' => $item->getName(), 'in_cart' => $itemInfo['qty'], 'super_attribute' => $super_attribute[$itemId]);
                        }
                    }

                    if (!$isGift) {
                        $nRuleGifts[] = array('item' => $item->getId(), 'product' => $productId, 'in_cart' => $itemInfo['qty']);
                    }
                }
            } else {
                foreach ($data as $itemId => $itemInfo) {
                    $isGift = false;
                    $item = $this->getQuote()->getItemById($itemId);
                    $productId = $item->getProductId();
                    $catalogQuote = Mage::getModel('promotionalgift/quote')
                        ->getCollection()
                        ->addFieldToFilter('item_id', $itemId)
                        ->getFirstItem();
                    $shoppingQuote = Mage::getModel('promotionalgift/shoppingquote')
                        ->getCollection()
                        ->addFieldToFilter('item_id', $itemId)
                        ->getFirstItem();
                    if ($catalogQuote->getId()) {
                        $ruleId = $catalogQuote->getCatalogRuleId();
                        $isGift = true;
                        $itemOptions = $item->getOptions();
                        foreach ($itemOptions as $option) {
                            $oData = $option->getData();
                            if ($oData['code'] == 'attributes') {
                                $attributes = unserialize($oData['value']);
                                $super_attribute[$itemId] = $attributes;
                            }
                        }
                        if ($totalCatalogItemGiftQty[$ruleId][$productId]) {
                            if ($totalCatalogItemGiftQty[$ruleId][$productId]['qty']) {
                                $totalCatalogItemGiftQty[$ruleId][$productId]['qty'] += $itemInfo['qty'];
                            }
                            if ($totalCatalogItemGiftQty[$ruleId][$productId]['item']) {
                                $totalCatalogItemGiftQty[$ruleId][$productId]['item'] = $totalCatalogItemGiftQty[$ruleId][$productId]['item'] . ',' . $item->getId();
                            }
                        } else {
                            $totalCatalogItemGiftQty[$ruleId][$productId]['qty'] = $itemInfo['qty'];
                            $totalCatalogItemGiftQty[$ruleId][$productId]['item'] = $item->getId();
                        }
                        $giftCatalogRuleProductQty = Mage::helper('promotionalgift/cart')->getGiftCatalogRuleProductQty($ruleId, $giftCatalogRuleProductQty);
                        if (empty($cRuleGifts[$ruleId][$item->getId()])) {
                            $cRuleGifts[$ruleId][$item->getId()] = '';
                            $cRuleGifts[$ruleId][$item->getId()] = array('item' => $item->getId(), 'product' => $productId, 'product_name' => $item->getName(), 'in_cart' => $itemInfo['qty'], 'super_attribute' => $super_attribute[$itemId]);
                        }
                    }
                    if ($shoppingQuote->getId()) {
                        $ruleId = $shoppingQuote->getShoppingcartruleId();
                        $isGift = true;
                        $itemOptions = $item->getOptions();
                        foreach ($itemOptions as $option) {
                            $oData = $option->getData();
                            if ($oData['code'] == 'attributes') {
                                $attributes = unserialize($oData['value']);
                                $super_attribute[$itemId] = $attributes;
                            }
                        }
                        if ($totalShoppingcartItemGiftQty[$ruleId][$productId]) {
                            if ($totalShoppingcartItemGiftQty[$ruleId][$productId]['qty']) {
                                $totalShoppingcartItemGiftQty[$ruleId][$productId]['qty'] += $itemInfo['qty'];
                            }
                            if ($totalShoppingcartItemGiftQty[$ruleId][$productId]['item']) {
                                $totalShoppingcartItemGiftQty[$ruleId][$productId]['item'] = $totalShoppingcartItemGiftQty[$ruleId][$productId]['item'] . ',' . $item->getId();
                            }
                        } else {
                            $totalShoppingcartItemGiftQty[$ruleId][$productId]['qty'] = $itemInfo['qty'];
                            $totalShoppingcartItemGiftQty[$ruleId][$productId]['item'] = $item->getId();
                        }
                        $giftShoppingcartRuleProductQty = Mage::helper('promotionalgift/cart')->getGiftShoppingcartRuleProductQty($ruleId, $giftShoppingcartRuleProductQty);
                        if (empty($sRuleGifts[$ruleId][$item->getId()])) {
                            $sRuleGifts[$ruleId][$item->getId()] = '';
                            $sRuleGifts[$ruleId][$item->getId()] = array('item' => $item->getId(), 'product' => $productId, 'product_name' => $item->getName(), 'in_cart' => $itemInfo['qty'], 'super_attribute' => $super_attribute[$itemId]);
                        }
                    }

                    if (!$isGift) {
                        $nRuleGifts[] = array('item' => $item->getId(), 'product' => $productId, 'in_cart' => $itemInfo['qty']);
                    }

                    /* Thinhnd */
                    $quoteOptions = Mage::getModel('sales/quote_item_option')->getCollection()
                        ->addFieldToFilter('item_id', $item->getId())
                        ->addFieldToFilter('code', 'product_type')
                        ->addFieldToFilter('value', 'grouped')
                        ->getFirstItem();
                    if ($quoteOptions && $quoteOptions->getId()) {
                        $quoteItemOptions = Mage::getModel('sales/quote_item_option')->getCollection()
                            ->addFieldToFilter('product_id', $quoteOptions->getProductId())
                            ->addFieldToFilter('code', 'product_type')
                            ->addFieldToFilter('value', 'grouped');
                        if (count($quoteItemOptions) > 1)
                            continue;
                    }
                }
            }
            //check max gift catalog rule
            if (!$autoUpdateQty) {
                if ($giftCatalogRuleProductQty) {
                    foreach ($giftCatalogRuleProductQty as $ruleId => $cdata) {
                        foreach ($cdata as $productId => $maxRuleQty) {
                            $product = Mage::getModel('catalog/product')->load($productId);
                            if ($totalCatalogItemGiftQty[$ruleId][$productId]['qty'] > $maxRuleQty) {
                                $itemIds = explode(',', $totalCatalogItemGiftQty[$ruleId][$productId]['item']);
                                foreach ($itemIds as $itemId) {
                                    unset($cRuleGifts[$ruleId][$itemId]);
                                    unset($data[$itemId]);
                                    if (count($cRuleGifts[$ruleId]) == 0) {
                                        unset($cRuleGifts[$ruleId]);
                                    }
                                }
                                $message = Mage::helper('promotionalgift')->__('The maximum quantity offered of gift %s is %s', $product->getName(), $maxRuleQty);
                                Mage::getSingleton('checkout/session')->addNotice($message);
                            }
                        }
                    }
                }
            }
            //chek max gift shoppingcart rule
            if ($giftShoppingcartRuleProductQty) {
                foreach ($giftShoppingcartRuleProductQty as $sRuleId => $sdata) {
                    foreach ($sdata as $sproductId => $maxRuleQty) {
                        $sproduct = Mage::getModel('catalog/product')->load($sproductId);
                        if ($totalShoppingcartItemGiftQty[$sRuleId][$sproductId]['qty'] > $maxRuleQty) {
                            $itemIds = explode(',', $totalShoppingcartItemGiftQty[$sRuleId][$sproductId]['item']);
                            foreach ($itemIds as $itemId) {
                                unset($sRuleGifts[$sRuleId][$itemId]);
                                unset($data[$itemId]);
                                if (count($sRuleGifts[$sRuleId]) == 0) {
                                    unset($sRuleGifts[$sRuleId]);
                                }
                            }
                            $message = Mage::helper('promotionalgift')->__('The maximum quantity offered of gift %s is %s', $sproduct->getName(), $maxRuleQty);
                            Mage::getSingleton('checkout/session')->addNotice($message);
                        }
                    }
                }
            }
            foreach ($nRuleGifts as $nRuleGift) {
                $nGiftItemIds[] = $nRuleGift['item'];
                $nAvaiRule = Mage::getModel('promotionalgift/catalogrule')->validateItem($nRuleGift['product']);
                if ($nAvaiRule) {
                    $ruleId = $nAvaiRule->getId();
                } else {
                    continue;
                }
                if (isset($ruleId) && empty($ruleTotalGift[$ruleId])) {
                    $ruleTotalGift[$ruleId] = '';
                }
                if (isset($ruleId)) {
                    $ruleTotalGift[$ruleId] += $nRuleGift['in_cart'];
                }
                $data[$nRuleGift['item']] = array(
                    'qty' => $nRuleGift['in_cart'],
                    'before_suggest_qty' => $nRuleGift['in_cart']
                );
            }
            if (!empty($cRuleGifts)) {
                foreach ($cRuleGifts as $cRuleId => $cRuleGift) {
                    $catalogItem = Mage::getModel('promotionalgift/catalogitem')
                        ->getCollection()
                        ->addFieldToFilter('rule_id', $cRuleId)
                        ->getFirstItem();
                    $productIds = explode(',', $catalogItem->getProductIds());
                    foreach ($cRuleGift as $giftInfo) {
                        foreach ($productIds as $key => $productId) {
                            if ($productId == $giftInfo['product']) {
                                //defind product
                                $product = new Mage_Catalog_Model_Product();
                                $product->load($productId);
                                //get maximum qty rule gift
                                $cQtyGift = $giftCatalogRuleProductQty[$cRuleId][$giftInfo['product']];
                                //eden
                                if ($autoUpdateQty) {
                                    $parentQty = 0;
                                    $numberParent = 0;
                                    $newQty = 0;
                                    $items = Mage::getSingleton('checkout/session')->getQuote()->getAllItems();
                                    $increateQty=false;
                                    foreach ($items as $item) {
                                        $productId = $item->getProduct()->getId();
                                        $availableRule = Mage::getModel('promotionalgift/catalogrule')->validateItem($productId);
                                        $itemId = $item->getId();
                                        $itemIsGift = false;
                                        $itemOptions = $item->getOptions();
                                        foreach ($itemOptions as $option) {
                                            $oData = $option->getData();
                                            if (!$item->getParentItemId()) {
                                                if ($oData['code'] == 'option_promotionalgift_catalogrule' || $oData['code'] == 'option_promotionalgift_shoppingcartrule') {
                                                    $itemIsGift = true;
                                                }
                                            }
                                        }

                                        if ($availableRule && ($itemIsGift == false)) {
                                            if ($availableRule->getRuleId() == $cRuleId) {
                                                //$parentQty = $parentQty + $item->getQty();
                                                $parentQty = $parentQty + $data[$itemId]['qty'];
                                                $newQty = $newQty + $data[$itemId]['qty'];
                                                $numberParent = $numberParent + 1;
                                            }
                                            if (in_array($itemId,$itemChange)) {
                                                $increateQty=true;

                                            }
                                        }
                                    }

                                    if ($numberParent > 0) {
                                        $newQtyGift=$cQtyGift;
                                        $cQtyGift = $cQtyGift * $parentQty;
                                        $newQty= $newQtyGift * $newQty;
                                    }
                                }

                                //end eden
                                //check stock qty
                                if ($product->getTypeId() == 'configurable') {
                                    $childProduct = Mage::getModel('catalog/product_type_configurable')
                                        ->getProductByAttributes($giftInfo['super_attribute'], $product);
                                    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($childProduct);
                                } else {
                                    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                                }
                                if ($stockItem->getManageStock()) {
                                    $qtyStock = $stockItem->getQty() - $cQtyGift;
                                    if ($qtyStock && $qtyStock <= 0) {
                                        $cQtyGift = (int)$stockItem->getQty();
                                    }
                                    if ($giftInfo['in_cart'] > $stockItem->getQty()) {
                                        $giftNotice = Mage::helper('promotionalgift')->__('Qty. in stock of gift %s is not enough to add to cart.', $giftInfo['product_name']);
                                        Mage::getSingleton('checkout/session')->addNotice($giftNotice);
                                    }
                                }
                            }
                        }
                        if ($giftInfo['in_cart'] > $cQtyGift) {
                            if (!$autoUpdateQty) {
                                $message = Mage::helper('promotionalgift')->__('The maximum quantity offered of gift %s is %s ', $giftInfo['product_name'], $cQtyGift);
                                Mage::getSingleton('checkout/session')->addNotice($message);
                            }
                            $giftInfo['in_cart'] = $cQtyGift;
                        }
                        $giftCatalogRuleProductQty[$cRuleId][$giftInfo['product']] -= $giftInfo['in_cart'];
                        if ($changeQtyConfig == 0) {
                            $giftInfo['in_cart'] = null;
                        }
                        $data[$giftInfo['item']] = array(
                            'qty' => $giftInfo['in_cart'],
                            'before_suggest_qty' => $giftInfo['in_cart']
                        );
                        if ($autoUpdateQty) {
                            if ($increateQty == true && !in_array($giftInfo['item'],$itemChange)) {

                                $data[$giftInfo['item']] = array(
                                    'qty' => $newQty,
                                    'before_suggest_qty' => $newQty
                                );
                            }
                        }
                    }
                }
            }
            if (!empty($sRuleGifts)) {
                foreach ($sRuleGifts as $sRuleId => $sRuleGift) {
                    $sItem = Mage::getModel('promotionalgift/shoppingcartitem')
                        ->getCollection()
                        ->addFieldToFilter('rule_id', $sRuleId)
                        ->getFirstItem();
                    $productIds = explode(',', $sItem->getProductIds());
                    foreach ($sRuleGift as $sgiftInfo) {
                        foreach ($productIds as $key => $productId) {
                            if ($productId == $sgiftInfo['product']) {
                                //define product
                                $product = new Mage_Catalog_Model_Product();
                                $product->load($productId);
                                $sQtyGift = $giftShoppingcartRuleProductQty[$sRuleId][$productId];
                                //check stock qty
                                if ($product->getTypeId() == 'configurable') {
                                    $childProduct = Mage::getModel('catalog/product_type_configurable')
                                        ->getProductByAttributes($sgiftInfo['super_attribute'], $product);
                                    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($childProduct);
                                } else {
                                    $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                                }
                                if ($stockItem->getManageStock()) {
                                    $qtyStock = $stockItem->getQty() - $sQtyGift;
                                    if ($qtyStock && $qtyStock <= 0) {
                                        $cQtyGift = (int)$stockItem->getQty();
                                    }
                                    if ($giftInfo['in_cart'] > $stockItem->getQty()) {
                                        $giftNotice = Mage::helper('promotionalgift')->__('Qty. in stock of gift %s is not enough to add to cart.', $giftInfo['product_name']);
                                        Mage::getSingleton('checkout/session')->addNotice($giftNotice);
                                    }
                                }
                            }
                        }
                        if ($sgiftInfo['in_cart'] > $sQtyGift) {
                            $message = Mage::helper('promotionalgift')->__('The maximum quantity offered of gift %s is %s ', $sgiftInfo['product_name'], $sQtyGift);
                            Mage::getSingleton('checkout/session')->addNotice($message);
                            $sgiftInfo['in_cart'] = $sQtyGift;
                        }
                        $giftShoppingcartRuleProductQty[$sRuleId][$sgiftInfo['product']] -= $sgiftInfo['in_cart'];
                        if ($changeQtyConfig == 0) {
                            $sgiftInfo['in_cart'] = null;
                        }
                        $data[$sgiftInfo['item']] = array(
                            'qty' => $sgiftInfo['in_cart'],
                            'before_suggest_qty' => $sgiftInfo['in_cart']
                        );
                    }
                }
            }
        }
        Mage::dispatchEvent('checkout_cart_update_items_before', array('cart' => $this, 'info' => $data));

        /* @var $messageFactory Mage_Core_Model_Message */
        $messageFactory = Mage::getSingleton('core/message');
        $session = $this->getCheckoutSession();
        $qtyRecalculatedFlag = false;

        foreach ($data as $itemId => $itemInfo) {
            $item = $this->getQuote()->getItemById($itemId);
            if (!$item) {
                continue;
            }

            if (!empty($itemInfo['remove']) || (isset($itemInfo['qty']) && floatval($itemInfo['qty']) <= 0)) {
                $this->getQuote()->removeItem($itemId);
                continue;
            }

            $qty = isset($itemInfo['qty']) ? (float)$itemInfo['qty'] : false;
            if ($qty > 0) {
                $oldQty = $item->getQty();
                $item->setQty($qty);
                if ($item->getHasError()) {
                    if (in_array($itemId, $nGiftItemIds)) {
                        Mage::throwException($item->getMessage());
                    } else {
                        /* set Qty of Gifts back to OldQty */
                        Mage::getSingleton('checkout/session')->addError($item->getMessage());
                        $item->setQty($oldQty);
                    }
                }

                if (isset($itemInfo['before_suggest_qty']) && ($itemInfo['before_suggest_qty'] != $qty)) {
                    $qtyRecalculatedFlag = true;
                    $message = $messageFactory->notice(Mage::helper('checkout')->__('Quantity was recalculated from %d to %d', $itemInfo['before_suggest_qty'], $qty));
                    $session->addQuoteItemMessage($item->getId(), $message);
                }
            }
        }

        if ($qtyRecalculatedFlag) {
            $session->addNotice(
                Mage::helper('checkout')->__('Some products quantities were recalculated because of quantity increment mismatch')
            );
        }

        Mage::dispatchEvent('checkout_cart_update_items_after', array('cart' => $this, 'info' => $data));
        return $this;

    }

    public function removeItem($itemId)
    {
        if (!Mage::getStoreConfig('promotionalgift/catalog_rule_configuration/autoupdateqty')) {
            if (!Mage::helper('promotionalgift')->enablePromotionalgift()) {
                $this->getQuote()->removeItem($itemId);
                return $this;
            }
            $quoteId = Mage::getModel('checkout/cart')->getQuote()->getId();
            $catalogQuotes = Mage::getModel('promotionalgift/quote')
                ->getCollection()
                ->addFieldToFilter('item_parent_id', $itemId)
                ->addFieldToFilter('quote_id', $quoteId);
            if (count($catalogQuotes) > 0) {
                $quote = Mage::getSingleton('sales/quote')->load($quoteId);
                foreach ($catalogQuotes as $catalogQuote) {
                    $quote->removeItem($catalogQuote->getItemId())->save();
                    $catalogQuote->delete();
                }
                Mage::getModel('checkout/session')->setData('free_gift_item', null);
                $quote->collectTotals();
            }
            $shoppingQuotes = Mage::getModel('promotionalgift/shoppingquote')
                ->getCollection()
                ->addFieldToFilter('item_id', $itemId)
                ->addFieldToFilter('quote_id', $quoteId);
            if (count($shoppingQuotes) > 0) {
                foreach ($shoppingQuotes as $shoppingQuote) {
                    $shoppingQuote->delete();
                }
                $this->getQuote()->removeItem($itemId);
                return $this;
            }
            $item = $this->getQuote()->getItemById($itemId);
            if ($item) {
                $productId = $item->getProductId();
                $availableRule = Mage::getModel('promotionalgift/catalogrule')->validateItem($productId);
                if ($availableRule) {
                    $cartItems = Mage::getModel('checkout/cart')->getItems();
                    $data = array();
                    foreach ($cartItems as $cartItem) {
                        if ($cartItem->getData('parent_item_id'))
                            continue;
                        $qty = $cartItem->getQty();
                        if ($cartItem->getId() == $itemId)
                            $qty = 0;
                        $data[$cartItem->getId()] = array(
                            "qty" => $qty,
                            "before_suggest_qty" => $qty,
                        );
                    }
                    if ($data) {
                        $this->updateItems($data);
                    }
                } else {
                    $this->getQuote()->removeItem($itemId); //fix bug ko xoa duoc gift
                    return $this;
                }

            }
            $this->getQuote()->removeItem($itemId);  //fix bug ko xoa duoc gift
            return $this;
        } else {
            if (!Mage::helper('promotionalgift')->enablePromotionalgift()) {
                $this->getQuote()->removeItem($itemId);
                return $this;
            }
            $quoteId = Mage::getModel('checkout/cart')->getQuote()->getId();
            $catalogQuotes = Mage::getModel('promotionalgift/quote')
                ->getCollection()
                ->addFieldToFilter('item_id', $itemId)
                ->addFieldToFilter('quote_id', $quoteId);
            if (count($catalogQuotes) > 0) {
                foreach ($catalogQuotes as $catalogQuote) {
                    $catalogQuote->delete();
                }
                $this->getQuote()->removeItem($itemId);
                return $this;
            }
            $shoppingQuotes = Mage::getModel('promotionalgift/shoppingquote')
                ->getCollection()
                ->addFieldToFilter('item_id', $itemId)
                ->addFieldToFilter('quote_id', $quoteId);
            if (count($shoppingQuotes) > 0) {
                foreach ($shoppingQuotes as $shoppingQuote) {
                    $shoppingQuote->delete();
                }
                $this->getQuote()->removeItem($itemId);
                return $this;
            }
            $item = $this->getQuote()->getItemById($itemId);
            if ($item) {
                $productId = $item->getProductId();
                $availableRule = Mage::getModel('promotionalgift/catalogrule')->validateItem($productId);
                if ($availableRule) {
                    $cartItems = Mage::getModel('checkout/cart')->getItems();
                    $data = array();
                    foreach ($cartItems as $cartItem) {
                        if ($cartItem->getData('parent_item_id')) continue;
                        $qty = $cartItem->getQty();
                        if ($cartItem->getId() == $itemId) $qty = 0;
                        $data[$cartItem->getId()] = array(
                            "qty" => $qty,
                            "before_suggest_qty" => $qty,
                        );
                    }
                    if ($data) {
                        $this->updateItems($data);
                    }
                } else {
                    $this->getQuote()->removeItem($itemId);  //fix bug ko xoa duoc gift
                    return $this;
                }
            }
            $this->getQuote()->removeItem($itemId);  //fix bug ko xoa duoc gift
            return $this;
        }
    }

    public function updateItem($itemId, $requestInfo = null, $updatingParams = null)
    {
        if (Mage::helper('promotionalgift')->enablePromotionalgift()) {
            $quoteId = Mage::getModel('checkout/cart')->getQuote()->getId();
            $productId = Mage::getModel('sales/quote_item')->load($itemId, 'item_id')->getProductId();
            $catalogQuotes = Mage::getModel('promotionalgift/quote')
                ->getCollection()
                ->addFieldToFilter('item_id', $itemId)
                ->addFieldToFilter('quote_id', $quoteId)
                ->getFirstItem();
            $shoppingQuotes = Mage::getModel('promotionalgift/shoppingquote')
                ->getCollection()
                ->addFieldToFilter('item_id', $itemId)
                ->addFieldToFilter('quote_id', $quoteId)
                ->getFirstItem();
            if ($catalogQuotes->getId() || $shoppingQuotes->getId()) {
                if ($catalogQuotes->getId()) {
                    $catalogRuleId = $catalogQuotes->getCatalogRuleId();
                    $freeGiftItemQty = $catalogQuotes->getNumberItemFree();
                    Mage::getModel('checkout/session')->setData('catalog_rule_id', $catalogRuleId);
                    Mage::getModel('checkout/session')->setData('free_gift_item_qty', $freeGiftItemQty);
                    if ($productId) {
                        Mage::getModel('checkout/session')->setData('free_gift_item', $productId);
                    }
                }
                if ($shoppingQuotes->getId()) {
                    $shoppingCartRuleId = $shoppingQuotes->getShoppingcartruleId();
                    Mage::getModel('checkout/session')->setData('shoppingcart_rule_id', $shoppingCartRuleId);
                    if ($productId)
                        Mage::getModel('checkout/session')->setData('shoppingcart_gift_item', $productId);
                }
                return $this->updateGiftItem($itemId, $requestInfo, $updatingParams);
            }
        }
        try {
            $item = $this->getQuote()->getItemById($itemId);
            if (!$item) {
                Mage::throwException(Mage::helper('checkout')->__('Quote item does not exist.'));
            }
            $productId = $item->getProduct()->getId();
            $product = $this->_getProduct($productId);
            $request = $this->_getProductRequest($requestInfo);
            if ($product->getStockItem()) {
                $minimumQty = $product->getStockItem()->getMinSaleQty();
                // If product was not found in cart and there is set minimal qty for it
                if ($minimumQty && ($minimumQty > 0) && ($request->getQty() < $minimumQty) && !$this->getQuote()->hasProductId($productId)
                ) {
                    $request->setQty($minimumQty);
                }
            }
            if (Mage::helper('promotionalgift')->enablePromotionalgift()) {
                if ($item->getQty() != $request['qty']) {
                    $upgrades = array();
                    $productId = $item->getProductId();
                    $availableRule = Mage::getModel('promotionalgift/catalogrule')->validateItem($productId);
                    if ($availableRule) {
                        $cartItems = Mage::getModel('checkout/cart')->getItems();
                        $data = array();
                        foreach ($cartItems as $cartItem) {
                            $qty = $cartItem->getQty();
                            if ($cartItem->getId() == $itemId)
                                $qty = $request['qty'];
                            $data[$cartItem->getId()] = array(
                                "qty" => $qty,
                                "before_suggest_qty" => $qty,
                            );
                        }
                        if ($data) {
                            $this->updateItems($data);
                        }
                    }
                }
            }
            $result = $this->getQuote()->updateItem($itemId, $request, $updatingParams);
        } catch (Mage_Core_Exception $e) {
            $this->getCheckoutSession()->setUseNotice(false);
            $result = $e->getMessage();
        }
        /**
         * We can get string if updating process had some errors
         */
        if (is_string($result)) {
            if ($this->getCheckoutSession()->getUseNotice() === null) {
                $this->getCheckoutSession()->setUseNotice(true);
            }
            Mage::throwException($result);
        }

        Mage::dispatchEvent('checkout_cart_product_update_after', array(
            'quote_item' => $result,
            'product' => $product
        ));
        $this->getCheckoutSession()->setLastAddedProductId($productId);
        return $result;
    }

    public function updateGiftItem($itemId, $requestInfo = null, $updatingParams = null)
    {
        try {
            $item = $this->getQuote()->getItemById($itemId);
            if (!$item) {
                Mage::throwException(Mage::helper('checkout')->__('Quote item does not exist.'));
            }
            $productId = $item->getProduct()->getId();
            $product = $this->_getProduct($productId);
            $request = $this->_getProductRequest($requestInfo);
            if ($product->getStockItem()) {
                $minimumQty = $product->getStockItem()->getMinSaleQty();
                // If product was not found in cart and there is set minimal qty for it
                if ($minimumQty && ($minimumQty > 0) && ($request->getQty() < $minimumQty) && !$this->getQuote()->hasProductId($productId)
                ) {
                    $request->setQty($minimumQty);
                }
            }
            if (Mage::helper('promotionalgift')->enablePromotionalgift()) {
                if ($item->getQty() != $request['qty']) {
                    $next = '';
                    $itemOptions = $item->getOptions();
                    $quotes = Mage::getModel('promotionalgift/quote')
                        ->getCollection()
                        ->addFieldToFilter('quote_id', $this->getQuote()->getId())
                        ->addFieldToFilter('item_id', $itemId);
                    $shoppingQuotes = Mage::getModel('promotionalgift/shoppingquote')
                        ->getCollection()
                        ->addFieldToFilter('quote_id', $this->getQuote()->getId())
                        ->addFieldToFilter('item_id', $itemId);
                    if ((count($quotes) > 0) || (count($shoppingQuotes) > 0))
                        $next = 1;
                    if ($next == 1) {
                        $request['qty'] = $item->getQty();
                    } else {
                        $upgrades = array();
                        $productId = $item->getProductId();
                        $availableRule = Mage::getModel('promotionalgift/catalogrule')->validateItem($productId);
                        if ($availableRule) {
                            $cartItems = Mage::getModel('checkout/cart')->getItems();
                            $data = array();
                            foreach ($cartItems as $cartItem) {
                                $qty = $cartItem->getQty();
                                if ($cartItem->getId() == $itemId)
                                    $qty = $request['qty'];
                                $data[$cartItem->getId()] = array(
                                    "qty" => $qty,
                                    "before_suggest_qty" => $qty,
                                );
                            }
                            if ($data) {
                                $this->updateItems($data);
                            }
                        }
                    }
                }
            }
            $result = $this->getQuote()->updateItem($itemId, $request, $updatingParams);
        } catch (Mage_Core_Exception $e) {
            $this->getCheckoutSession()->setUseNotice(false);
            $result = $e->getMessage();
        }

        /**
         * We can get string if updating process had some errors
         */
        if (is_string($result)) {
            if ($this->getCheckoutSession()->getUseNotice() === null) {
                $this->getCheckoutSession()->setUseNotice(true);
            }
            Mage::throwException($result);
        }

        Mage::dispatchEvent('checkout_cart_product_update_after', array(
            'quote_item' => $result,
            'product' => $product
        ));
        $this->getCheckoutSession()->setLastAddedProductId($productId);
        return $result;
    }

}
