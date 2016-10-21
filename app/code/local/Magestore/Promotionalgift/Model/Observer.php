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
 * Promotionalgift Observer Model
 *
 * @category    Magestore
 * @package     Magestore_Promotionalgift
 * @author      Magestore Developer
 */
class Magestore_Promotionalgift_Model_Observer
{

    /**
     * process controller_action_predispatch event
     *
     * @return Magestore_Promotionalgift_Model_Observer
     */
    public function checkoutCartProductAddAfter($observer)
    {
        if (Mage::getStoreConfig('promotionalgift/catalog_rule_configuration/autoupdateqty') == 1) {


            if (!Mage::helper('promotionalgift')->enablePromotionalgift())
                return $this;
            //Add Promotional gift by shopping cart rule
            if (Mage::getModel('checkout/session')->getData('shoppingcart_gift_item') != null) {
                return $this;
            }

            //Add Promotional gift by category cart rule
            if (Mage::getModel('checkout/session')->getData('free_gift_item')) {
                return $this;
            }

            $item = $observer['quote_item'];
            if (Mage::getModel('checkout/session')->getData('free_gift_item') || Mage::getModel('checkout/session')->getData('promotionalgift_bundle')) {
                if (Mage::getModel('checkout/session')->getData('sameproduct') == 1) {
                    Mage::getModel('checkout/session')->setData('sameproduct', Mage::getModel('checkout/session')->getData('sameproduct') + 1);
                } else {
                    if (Mage::getModel('checkout/session')->getData('sameproduct'))
                        Mage::getModel('checkout/session')->setData('sameproduct', Mage::getModel('checkout/session')->getData('sameproduct') + 1);
                    $item = $observer['quote_item'];
                    $item->setCustomPrice(0);
                    $item->setOriginalCustomPrice(0);
                    $item->getProduct()->setIsSuperMode(true);
                    if ($item->getId()) {
                        $catalogRuleId = Mage::getModel('checkout/session')->getData('catalog_rule_id');
                        $itemParentId = Mage::getModel('checkout/session')->getData('product_parent');
                        $freeGiftItemQty = Mage::getModel('checkout/session')->getData('free_gift_item_qty');
                        $quoteId = Mage::getModel('checkout/cart')->getQuote()->getId();
                        $quote = Mage::getModel('promotionalgift/quote')
                            ->getCollection()
                            ->addFieldToFilter('quote_id', $quoteId)
                            ->addFieldToFilter('item_id', $item->getId())
                            ->addFieldToFilter('item_parent_id', $itemParentId)
                            ->addFieldToFilter('catalog_rule_id', $catalogRuleId)
                            ->getFirstItem()
                        ;
                        Mage::getModel('checkout/session')->setData('catalog_rule_id', null);
                        Mage::getModel('checkout/session')->setData('product_parent', null);
                        Mage::getModel('checkout/session')->setData('free_gift_item', null);
                        Mage::getModel('checkout/session')->setData('free_gift_item_qty', null);
                        Mage::getModel('checkout/session')->setData('promotionalgift_bundle', null);
                        if (!$quote->getId()) {
                            try {
                                $message = Mage::getModel('promotionalgift/catalogrule')->load($catalogRuleId)->getName();
                                $quote = Mage::getModel('promotionalgift/quote');
                                $quote->setData('quote_id', $quoteId)
                                    ->setData('item_id', $item->getId())
                                    ->setData('item_parent_id', $itemParentId)
                                    ->setData('catalog_rule_id', $catalogRuleId)
                                    ->setData('number_item_free', $freeGiftItemQty)
                                    ->setData('message', $message)
                                    ->save();
                            } catch (Exception $e) {

                            }
                        }
                    }
                }
            } else {
                $product = $observer['product'];
                $item = $observer['quote_item'];
                $cart_qty = $product->getData('cart_qty');
                $productId = $product->getId();
                $availableRule = Mage::getModel('promotionalgift/catalogrule')->validateItem($productId);
                $autoAddGift = Mage::getStoreConfig('promotionalgift/catalog_rule_configuration/autoaddgift') == 1;
                $websiteId = Mage::app()->getStore()->getWebsiteId();
                if($availableRule)
                {
                    Mage::getModel('checkout/session')->setData('has_product_parent', true);
                }
                //Auto add free gift of Catalog Rule
                if ($availableRule && $autoAddGift) {
                    if(!$this->isAllowAutoAddGift()){
                        return $this;
                    }
                    $catalogItem = Mage::getModel('promotionalgift/catalogitem')
                        ->getCollection()
                        ->addFieldToFilter('rule_id',$availableRule->getId())
                        ->getFirstItem();
                    if($catalogItem->getId()){
                        $productIds = $catalogItem->getProductIds();
                        $productIds = explode(',',$productIds);
                        $qtys = $catalogItem->getGiftQty();
                        $qtys = explode(',',$qtys);
                        $count = 0;
                        $limitNumberItem = 0;
                        $numberItemFree = $availableRule->getNumberItemFree();
                        foreach($qtys as $qty){
                            if($qty <= 0){
                                $count++;
                                continue;
                            }
                            if ($limitNumberItem == $numberItemFree) break;
                            $productGiftId = $productIds[$count];
                            $count++;
                            $requestInfo = array();
                            if((count($qtys)<= 1) && ($productGiftId == $productId)){
                                Mage::getModel('checkout/session')->setData('sameproduct',1);
                            }else{
                                Mage::getModel('checkout/session')->setData('sameproduct',null);
                            }
                            $giftItem = new Mage_Catalog_Model_Product();
                            $giftItem->load($productGiftId);
                            /* check if gift is not avaiable */
                            if (!in_array($websiteId, $giftItem->getWebsiteIds()) || !$giftItem->isAvailable()) {
                                continue;
                            }
//                            if(!$giftItem->isAvailable()){
//                                $message = Mage::helper('promotionalgift')->__($giftItem->getName().' is out of stock !');
//                                Mage::getSingleton('checkout/session')->addError($message);
//                                continue;
//                            }
                            /* check if gift is Out of Stock */
                            if (!in_array($giftItem->getTypeId(), array('downloadable', 'virtual'))) {
                                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($giftItem);
                                $qtyStock = $stockItem->getQty();
                                if ($qtyStock <= 0 && !$stockItem->getData('is_in_stock')){
                                    $message = Mage::helper('promotionalgift')->__($giftItem->getName().' is out of stock !');
                                    Mage::getSingleton('checkout/session')->addError($message);
                                    continue;
                                }
                            }

                            /*item has options*/
                            $options=array();
                            if($giftItem->getOptions()){
                                $year = date('Y');
                                $month = date('m');
                                $day = date('j');
                                $hour = date('g');
                                $minute = date('i');
                                $day_part = date('a');
                                $options = Mage::helper('core')->decorateArray($giftItem->getOptions());
                                $optionAdds = array();
                                foreach($options as $option){
                                    if(in_array($option->getType(),array('area','field')))
                                        $optionAdds[$option->getOptionId()] = Mage::helper('promotionalgift')->__('Promotional Gift');
                                    if($option->getType()=='date_time')
                                        $optionAdds[$option->getOptionId()] = array(
                                            'month'=> $month,
                                            'day'=> $day,
                                            'year'=> $year,
                                            'hour'=> $hour,
                                            'minute'=> $minute,
                                            'day_part'=> $day_part
                                        );
                                    if($option->getType()=='date')
                                        $optionAdds[$option->getOptionId()] = array(
                                            'month'=> $month,
                                            'day'=> $day,
                                            'year'=> $year
                                        );
                                    if($option->getType()=='time')
                                        $optionAdds[$option->getOptionId()] = array(
                                            'hour'=> $hour,
                                            'minute'=> $minute,
                                            'day_part'=> $day_part
                                        );
                                    if(in_array($option->getType(),array('drop_down','checkbox','multiple','radio'))){
                                        foreach($option->getValues() as $value){
                                            $optionAdds[$option->getOptionId()] = $value->getData('option_type_id');
                                            break;
                                        }
                                    }
                                }
                                $requestInfo['options'] = $optionAdds;
                            }
                            $requestInfo['product'] = $giftItem->getId();
                            $requestInfo['related_product'] = '';
                            Mage::getModel('checkout/session')->setData('free_gift_item',$productGiftId);
                            Mage::getModel('checkout/session')->setData('free_gift_item_qty',$qty);
                            Mage::getModel('checkout/session')->setData('catalog_rule_id',$availableRule->getId());
                            Mage::getModel('checkout/session')->setData('catalog_rule_product_id',$availableRule->getId());
                            Mage::getModel('checkout/session')->setData('product_parent',$item->getId());

                            if($giftItem->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE){
                                $requestInfo['qty'] = $cart_qty * $qty;
                                $typeInstance = $giftItem->getTypeInstance(true);
                                $typeInstance->setStoreFilter($giftItem->getStoreId(), $giftItem);

                                $optionCollection = $typeInstance->getOptionsCollection($giftItem);

                                $selectionCollection = $typeInstance->getSelectionsCollection(
                                    $typeInstance->getOptionsIds($giftItem),
                                    $giftItem
                                );
                                $options = $optionCollection->appendSelections($selectionCollection, false, false);
                                $bundleOptions = array();
                                foreach ($options as $option){
                                    if(!$option->getSelections()) continue;
                                    $option_id = $option->getData('option_id');
                                    $selections = $option->getData('selections');
                                    if($option->getType()!='checkbox' && $option->getType()!='multi'){
                                        foreach($selections as $selection){
                                            $bundleOptions[$option_id] = $selection->getData('selection_id');
                                            break;
                                        }
                                    }else{
                                        foreach($selections as $selection){
                                            $bundleOptions[$option_id] = array($selection->getData('selection_id'));
                                            break;
                                        }
                                    }
                                }
                                $requestInfo['product'] = $giftItem->getId();
                                $requestInfo['related_product'] = '';
                                $requestInfo['bundle_option'] = $bundleOptions;
                                $cart = Mage::getModel('checkout/cart');
                                $product = new Mage_Catalog_Model_Product();
                                $product->load($productGiftId);
                                try{
                                    $cart->addProduct($product, $requestInfo);
                                } catch (Exception $ex) {
                                    // Mage::getSingleton('checkout/session')->addError($ex->getMessage());
                                }
                                $result = $cart->save();
                            }elseif($giftItem->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE){
                                $requestInfo['qty'] = $cart_qty * $qty;
                                $attributes = $giftItem->getTypeInstance(true)->getConfigurableAttributes($giftItem);
                                $allProducts = $giftItem->getTypeInstance(true)->getUsedProducts(null, $giftItem);
                                foreach ($allProducts as $product) {
                                    $productId  = $product->getId();
                                    // Fix add configurable product gift_King130701
                                    $productItem = new Mage_Catalog_Model_Product();
                                    $productItem->load($productId);
                                    $qtyItem = 0;
                                    if(!in_array($productItem->getTypeId(),array('downloadable','virtual','configurable'))){
                                        $stockItem = $productItem->getStockItem();
                                        if(!$stockItem->getIsInStock()){
                                            continue;
                                        }else{
                                            $qtyStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productItem)->getQty();
                                            if(!$productItem->isInStock()) continue;
                                        }
                                        $qtyItem = 1;
                                    }
                                    $allowAttributes = $giftItem->getTypeInstance(true)
                                        ->getConfigurableAttributes($giftItem);
                                    foreach ($allowAttributes as $attribute) {
                                        $productAttribute   = $attribute->getProductAttribute();
                                        $productAttributeId = $productAttribute->getId();
                                        $attributeValue     = $product->getData($productAttribute->getAttributeCode());
                                        if (!isset($options[$productAttributeId])) {
                                            $options[$productAttributeId] = array();
                                        }

                                        if (!isset($options[$productAttributeId][$attributeValue])) {
                                            $options[$productAttributeId][$attributeValue] = array();
                                        }
                                        $options[$productAttributeId][$attributeValue][] = $productId;
                                    }
                                }
                                if($options){
                                    foreach($options as $optionId => $keys){
                                        if(count($options)=='1'){
                                            foreach($keys as $k1=>$key){
                                                $requestInfo['super_attribute'] = array($optionId=>$k1);
                                                break;
                                            }
                                            break;
                                        }else{
                                            $check = '';
                                            foreach($keys as $k1=>$key){
                                                $check = 1;
                                                break;
                                            }
                                            if($check=='') continue;
                                            if(count($keys)<1) continue;
                                            $id1 = $optionId;
                                            foreach($keys as $k1=>$key){
                                                foreach($key as $k){
                                                    foreach($options as $optionId2 => $key2s){
                                                        if($optionId2 == $id1) continue;
                                                        foreach($key2s as $k2=>$key2){
                                                            foreach($key2 as $_k2){
                                                                if($_k2==$k){
                                                                    $key1 = $k1;
                                                                    $key2 = $k2;
                                                                    $id2 = $optionId2;
                                                                    $next = 1;  
                                                                    break;
                                                                }
                                                                if($next == '1') break;
                                                            }
                                                            if($next == '1') break;
                                                        }
                                                        if($next == '1') break;
                                                    }
                                                    if($next == '1') break;
                                                }
                                                if($next == '1') break;
                                            }
                                            $requestInfo['super_attribute'] = array($id1=>$key1,$id2=>$key2);
                                            break;
                                        }
                                    }
                                    $requestInfo['product'] = $giftItem->getId();
                                    $requestInfo['related_product'] = '';
                                    $cart = Mage::getModel('checkout/cart');
                                    $product = new Mage_Catalog_Model_Product();
                                    $product->load($productGiftId);
                                    /* check if gift is not enough to add to cart
                                   $childProduct = Mage::getModel('catalog/product_type_configurable')
                                             ->getProductByAttributes($requestInfo['super_attribute'], $product);
                                   $stockConfigItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($childProduct);
                                   $qtyConfigStock = $stockConfigItem->getQty();
                                   if($qtyConfigStock && $requestInfo['qty'] > $qtyConfigStock){
                                       $requestInfo['qty'] = $qtyConfigStock;
                                       $giftNotice = Mage::helper('promotionalgift')->__('Gift is not enough to add to cart !');
                                       Mage::getSingleton('checkout/session')->addNotice($giftNotice);
                                   }
                                     *
                                     */
                                    try{
                                        $cart->addProduct($product, $requestInfo);
                                    } catch (Exception $ex) {
                                        // Mage::getSingleton('checkout/session')->addError($ex->getMessage());
                                    }

                                    $result = $cart->save();
                                }
                            }elseif($giftItem->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED){
                                $productGiftId = '';
                                $associatedProducts = $giftItem->getTypeInstance(true)->getAssociatedProducts($giftItem);
                                $hasAssociatedProducts = count($associatedProducts);
                                if ($giftItem->isAvailable() && $hasAssociatedProducts){
                                    foreach($associatedProducts as $associatedProduct){
                                        $productGrouped = Mage::getModel('catalog/product')->load($associatedProduct->getId());
                                        if(!in_array($productGrouped->getTypeId(),array('downloadable','virtual'))){
                                            $qtyStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productGrouped)->getQty();
                                            if($qtyStock <= 0) continue;
                                        }
                                        $productGiftId = $associatedProduct->getId();
                                        break;
                                    }
                                }
                                if(!$productGiftId){
                                    Mage::getModel('checkout/session')->setData('free_gift_item',null);
                                    continue;
                                }
                                Mage::getModel('checkout/session')->setData('free_gift_item',$productGiftId);
                                $requestInfo['qty'] = $cart_qty * $qty;
                                $cart = Mage::getModel('checkout/cart');
                                $product = new Mage_Catalog_Model_Product();
                                $product->load($productGiftId);
                                try{
                                    $cart->addProduct($product, $requestInfo);
                                } catch (Exception $ex) {
                                    // Mage::getSingleton('checkout/session')->addError($ex->getMessage());
                                }

                                $result = $cart->save();
                            }else{
                                $requestInfo['qty'] = $cart_qty * $qty;
                                $cart = Mage::getModel('checkout/cart');
                                $product = new Mage_Catalog_Model_Product();
                                $product->load($productGiftId);
                                /* check if qtyGift greater than qtyInStock
                                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                                $qtyStock = $stockItem->getQty();
                                if($qtyStock && $requestInfo['qty'] > $qtyStock){
                                    $requestInfo['qty'] = $qtyStock;
                                    $giftNotice = Mage::helper('promotionalgift')->__('Gift is not enough to add to cart !');
                                    Mage::getSingleton('checkout/session')->addNotice($giftNotice);
                                }
                                */
                                try{
                                    $cart->addProduct($product, $requestInfo);
                                } catch (Exception $ex) {
                                    // Mage::getSingleton('checkout/session')->addError($ex->getMessage());
                                }

                                $result = $cart->save();
                            }
                            Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                            if(!$result->hasError()){
                                $message = Mage::helper('promotionalgift')->__($giftItem->getName().
                                    ' has been automatically added to your shopping cart as a gift!');
                                Mage::getSingleton('checkout/session')->addSuccess($message);
                            }
                            $limitNumberItem++;
                            Mage::getModel('checkout/session')->setData('free_gift_item', null);
                            Mage::getModel('checkout/session')->setData('catalog_rule_id', null);
                        }
                    }

                    Mage::getModel('checkout/session')->setData('free_gift_item', null);
                    Mage::getModel('checkout/session')->setData('catalog_rule_id', null);

                }
            }

        } else {

            if (!Mage::helper('promotionalgift')->enablePromotionalgift()) {
                return $this;
            }
            if (Mage::getModel('checkout/session')->getData('free_gift_item') || Mage::getModel('checkout/session')->getData('promotionalgift_bundle') || Mage::getModel('checkout/session')->getData('shoppingcart_gift_item')) {
                return $this;
            } else {
                $product = $observer['product'];
                $productId = $product->getId();
                $availableRule = Mage::getModel('promotionalgift/catalogrule')->validateItem($productId);
                if ($availableRule) {
                    Mage::getModel('checkout/session')->setData('is_product_parent', true);
                }
            }
        }
    }

    public function isAllowAutoAddGift(){
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $orders = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('customer_id', $customer->getId());
        $config_number_order = Mage::getStoreConfig('promotionalgift/general/numberorder');
        $config_number_order?$config_number_order:0;
        if(count($orders) != 0 && count($orders) < $config_number_order){
            return false;
        }
        return true;
    }

    public function salesQuoteItemSaveAfter($observer) {
        if (!Mage::helper('promotionalgift')->enablePromotionalgift()) {
            return $this;
        }

        //variable define item
        $item = $observer['item'];
        $itemId = $item->getId();


        //variable define quote
        $quote = Mage::getModel('checkout/cart')->getQuote();
        $quoteId = $quote->getId();


        $itemIsGift = false;
        $item = $observer['item'];
        $itemId = $item->getId();
        $itemOptions = $item->getOptions();
        foreach ($itemOptions as $option) {
            $oData = $option->getData();
            if (!$item->getParentItemId()) {
                if ($oData['code'] == 'option_promotionalgift_catalogrule' || $oData['code'] == 'option_promotionalgift_shoppingcartrule') {
                    $itemIsGift = true;
                }
            }
        }


        /*thinhnd*/

        if (Mage::getStoreConfig('promotionalgift/catalog_rule_configuration/autoupdateqty') == 1) {

            $hasProductParent = Mage::getModel('checkout/session')->getData('has_product_parent');
            $quoteId = Mage::getModel('checkout/cart')->getQuote()->getId();
            $catalogQuote = Mage::getModel('promotionalgift/quote')
                ->getCollection()
                ->addFieldToFilter('item_id', $item->getId())
                ->addFieldToFilter('quote_id', $quoteId);
            if ($hasProductParent && !count($catalogQuote)) {
                Mage::getModel('checkout/session')->setData('has_product_parent', false);
                $productId = $item->getProductId();
                $availableRule = Mage::getModel('promotionalgift/catalogrule')->validateItem($productId);
                if ($availableRule) {
                    $catalogItem = Mage::getModel('promotionalgift/catalogitem')
                        ->getCollection()
                        ->addFieldToFilter('rule_id', $availableRule->getId())
                        ->getFirstItem();
                    $productParent = unserialize(Mage::getModel('checkout/session')->getData('product_parent_id'));
                    if (!$productParent) {
                        $productParent1 = array();
                        $productParent1[$item->getId()] = $catalogItem->getProductIds();
                        Mage::getModel('checkout/session')->setData('product_parent_id', serialize($productParent1));
                    } else {
                        $productParent[$item->getId()] = $catalogItem->getProductIds();
                        Mage::getModel('checkout/session')->setData('product_parent_id', serialize($productParent));
                    }//zend_debug::dump($productParent1);zend_debug::dump($productParent);die();
                }
                return $this;
            }
        } else {
            //add main product and gift product of a catalogrule to Product Parent array
            $isProductParent = Mage::getModel('checkout/session')->getData('is_product_parent');
            $catalogQuote = Mage::getModel('promotionalgift/quote')
                ->getCollection()
                ->addFieldToFilter('item_id', $itemId)
                ->addFieldToFilter('quote_id', $quoteId);
            if ($isProductParent && !count($catalogQuote)) {
                Mage::getModel('checkout/session')->setData('is_product_parent', false);
                $productId = $item->getProductId();
                $availableRule = Mage::getModel('promotionalgift/catalogrule')->validateItem($productId);
                if ($availableRule) {
                    $catalogItem = Mage::getModel('promotionalgift/catalogitem')
                        ->getCollection()
                        ->addFieldToFilter('rule_id', $availableRule->getId())
                        ->getFirstItem();
                    $productParent = unserialize(Mage::getModel('checkout/session')->getData('product_parent_id'));
                    if (!$productParent) {
                        $productParent1 = array();
                        $productParent1[$itemId] = $catalogItem->getProductIds();
                        Mage::getModel('checkout/session')->setData('product_parent_id', serialize($productParent1));
                    } else {
                        $productParent[$itemId] = $catalogItem->getProductIds();
                        Mage::getModel('checkout/session')->setData('product_parent_id', serialize($productParent));
                    }

                    $catalogQuoteRule = $catalogQuote = Mage::getModel('promotionalgift/quote')
                        ->getCollection()
                        ->addFieldToFilter('catalog_rule_id', $availableRule->getId())
                        ->addFieldToFilter('quote_id', $quoteId);
                    $autoAddGift = Mage::getStoreConfig('promotionalgift/catalog_rule_configuration/autoaddgift', Mage::app()->getStore()->getId());
                    if ($autoAddGift && count($catalogQuoteRule) == 0) {
                        if(!$this->isAllowAutoAddGift()){
                            return $this;
                        }
                        if ($catalogItem->getId()) {
                            $productId = $item->getProductId();
                            Mage::helper('promotionalgift/cart')->autoAddCatalogGift($catalogItem, $productId, $availableRule, $itemId);
                        }
                    }
                }
                return $this;
            }
            /* end */
        }
        /*end*/

        //add shopping quote data
        if ($itemIsGift && Mage::getModel('checkout/session')->getData('shoppingcart_gift_item') && ($item->getProduct()->getId() == Mage::getModel('checkout/session')->getData('shoppingcart_gift_item'))) {
            if ($itemId) {
                //get shopping cart rule
                $shoppingcartRuleId = Mage::getModel('checkout/session')->getData('shoppingcart_rule_id');
                $shoppingcartRule = Mage::getModel('promotionalgift/shoppingcartrule')->load($shoppingcartRuleId);
                //get shopping quote
                $shoppingQuote = Mage::helper('promotionalgift/rule')->checkShoppingQuote($quoteId, $itemId, $shoppingcartRuleId);
                $catalogQuote = Mage::getModel('promotionalgift/quote')
                    ->getCollection()
                    ->addFieldToFilter('quote_id', $quoteId)
                    ->addFieldToFilter('item_id', $item->getId())
                    ->getFirstItem();
                //get coupon code
                $couponCode = '';
                $couponCode = Mage::getModel('promotionalgift/shoppingcartrule')->load($shoppingcartRuleId)
                    ->getCouponCode();

                //check shopping quote
                if (!$shoppingQuote && !$catalogQuote->getId()) {
                    try {
                        $message = $shoppingcartRule->getName();
                        Mage::getModel('promotionalgift/shoppingquote')
                            ->setData('quote_id', $quoteId)
                            ->setData('item_id', $itemId)
                            ->setData('shoppingcartrule_id', $shoppingcartRuleId)
                            ->setData('message', $message)
                            ->setData('coupon_code', $couponCode)
                            ->save();
                    } catch (Exception $e) {

                    }
                    Mage::getModel('checkout/session')->setData('shoppingcart_gift_item', null);
                    Mage::getModel('checkout/session')->setData('shoppingcart_rule_id', null);
                    Mage::getModel('checkout/session')->setData('shoppingcart_gift_item_qty', null);
                }
            }
            return $this;
        } elseif ($itemIsGift && Mage::getModel('checkout/session')->getData('promotionalgift_shoppingcart_grouped')) {
            $productGrouped = unserialize(Mage::getModel('checkout/session')->getData('promotionalgift_shoppingcart_grouped'));
            //get shopping cart rule
            $shoppingcartRuleId = Mage::getModel('checkout/session')->getData('shoppingcart_rule_id');
            $shoppingcartRule = Mage::getModel('promotionalgift/shoppingcartrule')->load($shoppingcartRuleId);
            //get shopping quote
            $shoppingQuote = Mage::helper('promotionalgift/rule')->checkShoppingQuote($quoteId, $itemId, $shoppingcartRuleId);
            $catalogQuote = Mage::getModel('promotionalgift/quote')
                ->getCollection()
                ->addFieldToFilter('quote_id', $quoteId)
                ->addFieldToFilter('item_id', $item->getId())
                ->getFirstItem();
            //get coupon code
            $couponCode = '';
            $couponCode = Mage::getModel('promotionalgift/shoppingcartrule')->load($shoppingcartRuleId)
                ->getCouponCode();
            //check shopping quote
            if (!$shoppingQuote && !$catalogQuote->getId()) {
                try {
                    $message = Mage::getModel('promotionalgift/shoppingcartrule')->load($shoppingcartRuleId)->getName();
                    $quote = Mage::getModel('promotionalgift/shoppingquote');
                    $quote->setData('quote_id', $quoteId)
                        ->setData('item_id', $item->getId())
                        ->setData('shoppingcartrule_id', $shoppingcartRuleId)
                        ->setData('message', $message)
                        ->setData('coupon_code', $couponCode)
                        ->setData('grouped_id', $productGrouped[0])
                        ->save();
                    foreach ($productGrouped[1] as $label => $value) {
                        if ($item->getProductId() == $label) {
                            unset($productGrouped[1][$label]);
                            break;
                        }
                    }
                    if (count($productGrouped[1])) {
                        Mage::getModel('checkout/session')->setData('promotionalgift_shoppingcart_grouped', serialize($productGrouped));
                    } else {
                        Mage::getModel('checkout/session')->setData('shoppingcart_rule_id', null);
                        Mage::getModel('checkout/session')->setData('product_parent', null);
                        Mage::getModel('checkout/session')->setData('promotionalgift_shoppingcart_grouped', null);
                    }
                } catch (Exception $e) {

                }
                return $this;
            }
        } else {
            Mage::getModel('checkout/session')->setData('product_parent', $item->getId());
        }

        //add data for catalogrule
        if (Mage::getStoreConfig('promotionalgift/catalog_rule_configuration/autoupdateqty') == 1) {
            if ($itemIsGift && (Mage::getModel('checkout/session')->getData('free_gift_item'))
                && ($item->getProduct()->getId() == Mage::getModel('checkout/session')->getData('free_gift_item'))) {
                if (Mage::getModel('checkout/session')->getData('sameproduct') &&
                    (Mage::getModel('checkout/session')->getData('sameproduct') == 2)) {
                    Mage::getModel('checkout/session')->setData('product_parent', $item->getId());
                    Mage::getModel('checkout/session')->setData('sameproduct', Mage::getModel('checkout/session')->getData('sameproduct') + 1);
                } else {
                    $catalogRuleId = Mage::getModel('checkout/session')->getData('catalog_rule_id');
                    $itemParentId = 0;//Mage::getModel('checkout/session')->getData('product_parent');
                    //$freeGiftItemQty = Mage::getModel('checkout/session')->getData('free_gift_item_qty');
                    $quoteId = Mage::getModel('checkout/cart')->getQuote()->getId();
                    /*thinhnd*/
                    $productParent = unserialize(Mage::getModel('checkout/session')->getData('product_parent_id'));
                    $catalogItem = Mage::getModel('promotionalgift/catalogitem')
                        ->getCollection()
                        ->addFieldToFilter('rule_id', $catalogRuleId)
                        ->getFirstItem();
                    $qtys = explode(',',$catalogItem->getGiftQty());
                    $productarray = explode(',',$catalogItem->getProductIds());
                    $count = array_search($item->getProductId(),$productarray);
                    $bb=$item->getQty()-$qtys[$count];
                    $freeGiftItemQty =$qtys[$count];
                    foreach($productParent as $itemParId =>$productId){
                        $productId = explode(',',$productId);
                        $key = array_search($item->getProductId(),$productId);
                        if($item->getProductId() == $productId[$key])
                        {
                            unset($productId[$key]);
                            $itemParentId = $itemParId;
                        }
                        if($itemParentId !=0)
                        {
                            $quote = Mage::getModel('promotionalgift/quote')
                                ->getCollection()
                                ->addFieldToFilter('quote_id', $quoteId)
                                ->addFieldToFilter('item_id', $item->getId())
                                ->addFieldToFilter('item_parent_id', $itemParentId)
                                ->addFieldToFilter('catalog_rule_id', $catalogRuleId)
                                ->getFirstItem()
                            ;
                            Mage::getModel('checkout/session')->setData('catalog_rule_id', null);
                            Mage::getModel('checkout/session')->setData('product_parent', null);
                            Mage::getModel('checkout/session')->setData('free_gift_item', null);
                            Mage::getModel('checkout/session')->setData('free_gift_item_qty', null);
                            Mage::getModel('checkout/session')->setData('promotionalgift_bundle', null);
                            if (!$quote->getId()) {
                                try {
                                    $message = Mage::getModel('promotionalgift/catalogrule')->load($catalogRuleId)->getName();
                                    $quote = Mage::getModel('promotionalgift/quote');
                                    $quote->setData('quote_id', $quoteId)
                                        ->setData('item_id', $item->getId())
                                        ->setData('item_parent_id', $itemParentId)
                                        ->setData('catalog_rule_id', $catalogRuleId)
                                        ->setData('number_item_free', $freeGiftItemQty)
                                        ->setData('message', $message)
                                        ->save();
                                } catch (Exception $e) {

                                }
                            }
                            // if(!count($productId))
                            // unset($productParent[$itemParId]);
                            // else
                            $productParent[$itemParentId] = implode(',',$productId);
                            if(count($productParent))
                                Mage::getModel('checkout/session')->setData('product_parent_id',serialize($productParent));
                            else
                                Mage::getModel('checkout/session')->setData('product_parent_id',null);
                            if($bb<=0)
                                break;
                            $itemParentId =0;
                        }
                    }
                    /*end*/

                }
            } else {
                Mage::getModel('checkout/session')->setData('product_parent', $item->getId());
            }
        } else {
            //add data for catalogrule
            if ($itemIsGift&& (Mage::getModel('checkout/session')->getData('free_gift_item')) && ($item->getProduct()->getId() == Mage::getModel('checkout/session')->getData('free_gift_item'))) {
                if (Mage::getModel('checkout/session')->getData('sameproduct') &&
                    (Mage::getModel('checkout/session')->getData('sameproduct') == 2)) {
                    Mage::getModel('checkout/session')->setData('product_parent', $item->getId());
                    Mage::getModel('checkout/session')->setData('sameproduct', Mage::getModel('checkout/session')->getData('sameproduct') + 1);
                } else {
                    $catalogRuleId = Mage::getModel('checkout/session')->getData('catalog_rule_id');
                    $itemParentId = 0;

                    /* thinhnd */
                    $productParent = unserialize(Mage::getModel('checkout/session')->getData('product_parent_id'));
                    $catalogItem = Mage::getModel('promotionalgift/catalogitem')
                        ->getCollection()
                        ->addFieldToFilter('rule_id', $catalogRuleId)
                        ->getFirstItem();
                    $qtys = explode(',', $catalogItem->getGiftQty());
                    $productarray = explode(',', $catalogItem->getProductIds());
                    $count = array_search($item->getProductId(), $productarray);
                    $bb = $qtys[$count];
                    $freeGiftItemQty = $qtys[$count];
                    foreach ($productParent as $itemParId => $productId) {
                        if ($itemParId != $item->getId()) {
                            $productId = explode(',', $productId);
                            $key = array_search($item->getProductId(), $productId);
                            if ($item->getProductId() == $productId[$key]) {
                                unset($productId[$key]);
                                $itemParentId = $itemParId;
                            }
                            if ($itemParentId != 0) {
                                $catalogquote = Mage::getModel('promotionalgift/quote')
                                    ->getCollection()
                                    ->addFieldToFilter('quote_id', $quoteId)
                                    ->addFieldToFilter('item_id', $item->getId())
                                    ->addFieldToFilter('item_parent_id', $itemParentId)
                                    ->addFieldToFilter('catalog_rule_id', $catalogRuleId)
                                    ->getFirstItem();
                                $shoppingquote = Mage::getModel('promotionalgift/shoppingquote')
                                    ->getCollection()
                                    ->addFieldToFilter('quote_id', $quoteId)
                                    ->addFieldToFilter('item_id', $item->getId())
                                    ->getFirstItem();
                                if (!$shoppingquote->getId() && !$catalogquote->getId()) {
                                    try {
                                        $bb -= $item->getQty();
                                        $message = Mage::getModel('promotionalgift/catalogrule')->load($catalogRuleId)->getName();
                                        Mage::getModel('promotionalgift/quote')
                                            ->setData('quote_id', $quoteId)
                                            ->setData('item_id', $item->getId())
                                            ->setData('item_parent_id', $itemParentId)
                                            ->setData('catalog_rule_id', $catalogRuleId)
                                            ->setData('number_item_free', $freeGiftItemQty)
                                            ->setData('message', $message)
                                            ->save();
                                    } catch (Exception $e) {

                                    }
                                    $productParent[$itemParentId] = implode(',', $productId);
                                    if (count($productParent)) {
                                        Mage::getModel('checkout/session')->setData('product_parent_id', serialize($productParent));
                                    } else {
                                        Mage::getModel('checkout/session')->setData('product_parent_id', null);
                                    }
                                    if ($bb <= 0) {
                                        break;
                                    }
                                    $itemParentId = 0;
                                }
                            }
                        }
                    }
                }
            } else {
                Mage::getModel('checkout/session')->setData('product_parent', $item->getId());
            }
        }


    }

    //ADD PROMOTIONAL GIFT OPTION TO PRODUCT ADD TO CART
    public function catalogProductTypePrepareFullOptions($observer)
    {
        if (!Mage::helper('promotionalgift')->enablePromotionalgift()) {
            return $this;
        }
        if (Mage::getModel('checkout/session')->getData('catalog_rule_product_id')) {
            $transport = $observer->getTransport();
            $transport->options['promotionalgift_catalogrule'] = Mage::getModel('checkout/session')
                ->getData('catalog_rule_product_id');
            Mage::getModel('checkout/session')->setData('catalog_rule_product_id', null);
        }
        if (Mage::getModel('checkout/session')->getData('shoppingcart_gift_item')) {
            $sRuleTransport = $observer->getTransport();
            $sRuleTransport->options['promotionalgift_shoppingcartrule'] = Mage::getModel('checkout/session')
                ->getData('shoppingcart_rule_id');
        }
    }

    //ADD PROMOTIONAL GIFT OPTION TO PRODUCT ADD TO CART
    public function catalogProductTypePrepareLiteOptions($observer)
    {
        if (!Mage::helper('promotionalgift')->enablePromotionalgift()) {
            return $this;
        }

        if (Mage::getModel('checkout/session')->getData('catalog_rule_product_id')) {
            $transport = $observer->getTransport();
            $transport->options['promotionalgift_catalogrule'] = Mage::getModel('checkout/session')
                ->getData('catalog_rule_product_id');
            Mage::getModel('checkout/session')->setData('catalog_rule_product_id', null);
        }
        if (Mage::getModel('checkout/session')->getData('shoppingcart_gift_item')) {
            $sRuleTransport = $observer->getTransport();
            $sRuleTransport->options['promotionalgift_shoppingcartrule'] = Mage::getModel('checkout/session')
                ->getData('shoppingcart_rule_id');
        }
    }

    public function salesOrderPlaceAfter($observer)
    {
        if (!Mage::helper('promotionalgift')->enablePromotionalgift())
            return $this;
        $order = $observer['order'];
        $quoteId = $order->getQuoteId();
        $catalogRuleQuote = Mage::getModel('promotionalgift/quote')
            ->getCollection()
            ->addFieldToFilter('quote_id', $quoteId);
        $shoppingcartRuleQuote = Mage::getModel('promotionalgift/shoppingquote')
            ->getCollection()
            ->addFieldToFilter('quote_id', $quoteId);
        $freeGiftIds = array();
        $shoppingcartRuleIds = array();
        if (count($shoppingcartRuleQuote) > 0) {
            foreach ($shoppingcartRuleQuote as $rule) {
                if (!in_array($rule->getId(), $freeGiftIds)) {
                    $freeGiftIds[] = $rule->getItemId();
                    if (!in_array($rule->getData('shoppingcartrule_id'), $shoppingcartRuleIds)) {
                        $shoppingcartRuleIds[] = $rule->getData('shoppingcartrule_id');
                    }
                }
            }
        }
        $catalogRuleQuoteIds = array();
        if (count($catalogRuleQuote) > 0) {
            foreach ($catalogRuleQuote as $catalogRule) {
                if (!in_array($catalogRule->getId(), $freeGiftIds)) {
                    $freeGiftIds[] = $catalogRule->getItemId();
                    if (!in_array($catalogRule->getData('catalog_rule_id'), $catalogRuleQuoteIds))
                        $catalogRuleQuoteIds[$catalogRule->getItemId()] = $catalogRule->getData('catalog_rule_id');
                }
            }
        }
        if (count($freeGiftIds)) {
            $itemsCollection = $order->getItemsCollection();
            $productIds = '';
            $productNames = '';
            $giftTotal = 0;
            $count = 0;
            $catalogRuleIds = array();
            foreach ($itemsCollection as $item) {
                $parentItemId = 0;
                if ($item->getParentItemId()) {
                    $itemParent = Mage::getModel('sales/order_item')->load($item->getParentItemId());
                    $parentItemId = $itemParent->getQuoteItemId();
                }
                if ((in_array($item->getQuoteItemId(), $freeGiftIds)) || (in_array($parentItemId, $freeGiftIds))) {
                    if (in_array($item->getQuoteItemId(), $freeGiftIds)) {
                        if ($catalogRuleQuoteIds[$item->getQuoteItemId()] && !in_array($catalogRuleQuoteIds[$item->getQuoteItemId()], $catalogRuleIds))
                            $catalogRuleIds[] = $catalogRuleQuoteIds[$item->getQuoteItemId()];
                    } elseif (in_array($parentItemId, $freeGiftIds)) {
                        if ($catalogRuleQuoteIds[$parentItemId] && !in_array($catalogRuleQuoteIds[$parentItemId], $catalogRuleIds))
                            $catalogRuleIds[] = $catalogRuleQuoteIds[$parentItemId];
                    }
                    $price = $item->getProduct()->getPrice();
                    $giftTotal += $price * $item->getQtyOrdered();
                    if ($parentItemId > 0)
                        continue;
                    if ($count == 0) {
                        $productIds .= $item->getProductId();
                        $productNames .= $item->getName();
                    } else {
                        $productIds .= ',' . $item->getProductId();
                        $productNames .= ';' . $item->getName();
                    }
                    $count++;
                }
            }
            $catalogRules = '';
            if ($catalogRuleIds) {
                $catalogRuleCheck = Mage::getModel('promotionalgift/catalogrule')
                    ->getCollection()
                    ->addFieldToFilter('rule_id', array('in' => $catalogRuleIds));
                foreach ($catalogRuleCheck as $catalogRuleCh) {
                    if (intval($catalogRuleCh->getData('uses_limit')) > 0) {
                        try {
                            $catalogRuleCh->setUsesLimit(intval($catalogRuleCh->getUsesLimit()) - 1)
                                ->save();
                        } catch (Exception $e) {

                        }
                    }
                }
                $catalogRules = implode(',', $catalogRuleIds);
            }
            $shoppingcartRules = '';

            if ($shoppingcartRuleIds) {
                $shoppingcartRuleCheck = Mage::getModel('promotionalgift/shoppingcartrule')
                    ->getCollection()
                    ->addFieldToFilter('rule_id', array('in' => $shoppingcartRuleIds));
                foreach ($shoppingcartRuleCheck as $shoppingRule) {
                    if (intval($shoppingRule->getData('uses_per_coupon')) > 0) {
                        try {
                            $shoppingRule->setUsesPerCoupon(intval($shoppingRule->getUsesPerCoupon()) - 1)
                                ->save();

                            $customer_id = $order->getCustomerId();
                            $promotionalgiftCustomer = Mage::getModel('promotionalgift/limitcustomer');
                            $data = array(
                                'customer_id' => $customer_id,
                                'catalogrule_id' => '0',
                                'shoppingcartrule_id' => $shoppingRule->getId()
                            );
                            $promotionalgiftCustomer->setData($data)->save();
                        } catch (Exception $e) {

                        }
                    }
                }
                $shoppingcartRules = implode(',', $shoppingcartRuleIds);
            }

            if ($productIds) {
                $promotionalgiftSale = Mage::getModel('promotionalgift/sale');
                $promotionalgiftSale->setData('order_id', $order->getId())
                    ->setData('order_increment_id', $order->getIncrementId())
                    ->setData('order_total', $order->getGrandTotal())
                    ->setData('product_ids', $productIds)
                    ->setData('product_names', $productNames)
                    ->setData('gift_total', $giftTotal)
                    ->setData('created_at', now())
                    ->setData('order_status', $order->getStatus())
                    ->setData('catalog_rule_id', $catalogRules)
                    ->setData('shoppingcartrule_id', $shoppingcartRules);
                try {
                    $promotionalgiftSale->save();
                } catch (Exception $e) {

                }
            }
        }
    }

    public function salesOrderSaveAfter($observer)
    {
        if (!Mage::helper('promotionalgift')->enablePromotionalgift())
            return $this;
        $order = $observer['order'];
        $promotionalgiftSale = Mage::getModel('promotionalgift/sale')->load($order->getId(), 'order_id');
        if ($promotionalgiftSale->getId())
            $promotionalgiftSale->setData('order_status', $order->getStatus())->save();
    }

    public function deleteSession()
    {
        $session = Mage::getSingleton('checkout/session');
        $itemIds = array();
        foreach ($session->getQuote()->getAllItems() as $item) {
            $itemIds[] = $item->getId();
        }
        //zend_debug::dump($itemIds);
        $productParent = unserialize(Mage::getModel('checkout/session')->getData('product_parent_id'));
        if (is_array($productParent)) {
            foreach ($productParent as $itemParId => $productId) {
                if (!in_array($itemParId, $itemIds)) {
                    $itemParentId = $itemParId;
                    unset($productParent[$itemParentId]);
                }
            }
        }
        if (count($productParent))
            Mage::getModel('checkout/session')->setData('product_parent_id', serialize($productParent));
        else
            Mage::getModel('checkout/session')->setData('product_parent_id', null);
    }

    public function controllerActionPostdispatch($observer)
    {
        if (!Mage::helper('promotionalgift')->enablePromotionalgift())
            return $this;
        if (Mage::app()->getRequest()->getActionName() == 'saveOrder')
            return $this;
        $quoteId = Mage::getModel('checkout/cart')->getQuote()->getId();
        $quotes = Mage::getModel('promotionalgift/quote')
            ->getCollection()
            ->addFieldToFilter('quote_id', $quoteId);
        $shoppingQuotes = Mage::getModel('promotionalgift/shoppingquote')
            ->getCollection()
            ->addFieldToFilter('quote_id', $quoteId);
        $itemIds = array();
        foreach ($quotes as $quote) {
            if (!in_array($quote->getItemId(), $itemIds)) {
                $itemIds[] = $quote->getItemId();
            }
        }
        foreach ($shoppingQuotes as $shoppingQuote) {
            if (!in_array($shoppingQuote->getItemId(), $itemIds)) {
                $itemIds[] = $shoppingQuote->getItemId();
            }
        }
        if ($itemIds) {
            $session = Mage::getSingleton('checkout/session');
            $ruleId = $session->getData('catalog_rule_id');
            if (!empty($ruleId)) {
                //$percentDiscount = Mage::getModel('promotionalgift/catalogrule')->load($ruleId)->getDiscountProduct();
                $catalogrule = Mage::getModel('promotionalgift/catalogrule')->load($ruleId);
                if ($catalogrule->getPriceType() == 1)
                    $percentDiscount = $catalogrule->getDiscountProduct();
                else
                    $fixedDiscount = $catalogrule->getDiscountProductFixed();
            } else {
                $ruleId = $session->getData('shoppingcart_rule_id');
                if (!empty($ruleId)) {
                    //$percentDiscount = Mage::getModel('promotionalgift/shoppingcartrule')->load($ruleId)->getDiscountProduct();
                    $shoppingcartrule = Mage::getModel('promotionalgift/shoppingcartrule')->load($ruleId);
                    if ($shoppingcartrule->getPriceType() == 1)
                        $percentDiscount = $shoppingcartrule->getDiscountProduct();
                    else
                        $fixedDiscount = $shoppingcartrule->getDiscountProductFixed();
                }
            }
            $i=0;
            foreach ($session->getQuote()->getAllItems() as $item) {
                if (empty($ruleId)) {
                    $quotes = Mage::getModel('promotionalgift/quote')->getCollection()
                        ->addFieldToFilter('item_id', $item->getId())
                        ->getFirstItem();
                    if ($quotes->getId()) {
                        //$percentDiscount = Mage::getModel('promotionalgift/catalogrule')->load($quotes->getCatalogRuleId())->getDiscountProduct();
                        $catalogrule = Mage::getModel('promotionalgift/catalogrule')->load($quotes->getCatalogRuleId());
                        if ($catalogrule->getPriceType() == 1)
                            $percentDiscount = $catalogrule->getDiscountProduct();
                        else
                            $fixedDiscount = $catalogrule->getDiscountProductFixed();
                    } else {
                        $shoppingCartQuotes = Mage::getModel('promotionalgift/shoppingquote')->getCollection()
                            ->addFieldToFilter('item_id', $item->getId())
                            ->getFirstItem();
                        if ($shoppingCartQuotes->getId()) {
                            // $percentDiscount = Mage::getModel('promotionalgift/shoppingcartrule')->load($shoppingCartQuotes->getShoppingcartruleId())->getDiscountProduct();
                            $shoppingcartrule = Mage::getModel('promotionalgift/shoppingcartrule')->load($shoppingCartQuotes->getShoppingcartruleId());
                            if ($shoppingcartrule->getPriceType() == 1)
                                $percentDiscount = $shoppingcartrule->getDiscountProduct();
                            else
                                $fixedDiscount = $shoppingcartrule->getDiscountProductFixed();
                        }
                    }
                }
                if ((in_array($item->getId(), $itemIds) || in_array($item->getParentItemId(), $itemIds)) && (!$item->getData('custom_price') || floatval($item->getData('custom_price')) > 0)) {
                    $oldPrice = Mage::helper('core')->currency($item->getProduct()->getFinalPrice(), false, false);
                    if (Mage::getModel('checkout/session')->getData('product_bundle') && $item->getProductId() != Mage::getModel('checkout/session')->getData('product_bundle')) {
                        $productBundle = new Mage_Catalog_Model_Product();
                        $productBundle->load(Mage::getModel('checkout/session')->getData('product_bundle'));
                        $specialPrice = Mage::helper('core')->currency($productBundle->getFinalPrice(), false, false);
                        if($fixedDiscount !== null)
                        {
                            ++$i;
                            if($i>=2) continue;
                        }
                        if ($specialPrice)
                            $oldPrice = $oldPrice * $specialPrice / 100;
                    }
                    if ($percentDiscount)
                        $newPrice = $oldPrice - ($oldPrice * $percentDiscount) / 100;
                    if ($fixedDiscount) {
                        $fixedDiscount = Mage::helper('core')->currency($fixedDiscount, false, false); //eden fix currency
                        $newPrice = $oldPrice - (($fixedDiscount >= $oldPrice) ? $oldPrice : $fixedDiscount);
                    }
                    if (!Mage::registry('autoshopping')) {  //ingore when automatic add shopping cart rule
                        $item->setCustomPrice($newPrice);
                        $item->setOriginalCustomPrice($newPrice);
                        //$item->setFreeShipping(true);
                        $item->getProduct()->setIsSuperMode(true);
                        $item->save();
                    }
                }
            }
        }
        $this->deleteSession();
    }

    public function controllerActionPredispatch($observer)
    {
        if (Mage::getModel('checkout/session')->getData('reset_cart')) {
            $cart = Mage::getSingleton('checkout/cart');
            if ($cart->getQuote()->getItemsCount()) {
                $cart->init();
                $cart->save();
            }
            Mage::getModel('checkout/session')->setData('reset_cart', false);
        }
    }

    public function checkoutOnepageControllerSuccessAction($observer)
    {
        Mage::getModel('checkout/session')->setData('catalog_rule_id', null);
        Mage::getModel('checkout/session')->setData('product_parent', null);
        Mage::getModel('checkout/session')->setData('free_gift_item', null);
        Mage::getModel('checkout/session')->setData('free_gift_item_qty', null);
        Mage::getModel('checkout/session')->setData('catalog_rule_ids', null);
        Mage::getModel('checkout/session')->setData('promotionalgift_shoppingcart_rule_id', null);
        Mage::getModel('checkout/session')->setData('promptionalgift_coupon_code', null);
        Mage::getModel('checkout/session')->setData('promotionalgift_shoppingcart_rule_used', null);
        Mage::getModel('checkout/session')->setData('shoppingcart_couponcode_rule_id', null);
        Mage::getModel('checkout/session')->setData('product_bundle', null);
    }

    /*
      Use shopping cart rule
     */

    public function couponPostAction($observer)
    {
        if (!Mage::helper('promotionalgift')->enablePromotionalgift())
            return $this;

        $action = $observer->getEvent()->getControllerAction();
        $code = trim($action->getRequest()->getParam('coupon_code'));
        if (!$code)
            return $this;
        $session = Mage::getSingleton('checkout/session');
        $cart = Mage::getSingleton('checkout/cart');
        $ruleId = '';
        $salesRules = Mage::getModel('promotionalgift/shoppingcartrule')->getAvailableCouponRule();
        foreach ($salesRules as $salesRule) {
            if ($salesRule->getCouponCode() == $code) {
                if (Mage::helper('promotionalgift/rule')->validateRuleQuote($salesRule)) {
                    $ruleId = $salesRule->getId();
                    break;
                }
            }
        }

        if ($action->getRequest()->getParam('remove') == 1) {
            if ($session->getData('promptionalgift_coupon_code')) {
                $session->addSuccess(Mage::helper('promotionalgift')->__('Coupon code "%s" was canceled.', $session->getData('promptionalgift_coupon_code')));
                $session->setData('promptionalgift_coupon_code', null);
                $session->setData('shoppingcart_couponcode_rule_id', null);
                $session->setData('promotionalgift_shoppingcart_rule_id', null);
                $session->setData('promotionalgift_shoppingcart_rule_used', null);
                if ($ruleId) {
                    $shoppingQuote = Mage::getModel('sales/quote_item');
                    $giftItems = Mage::getModel('promotionalgift/shoppingquote')->getCollection()
                        ->addFieldToFilter('shoppingcartrule_id', $ruleId);
                    foreach ($giftItems as $item) {
                        try {
                            $item->delete();
                            $cart->removeItem($item->getItemId())->save();
                        } catch (Exception $e) {

                        }
                    }
                }
                $action->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
                return $this;

            }
        } else {
            if ($ruleId) {
                if (!$session->getData('promptionalgift_coupon_code')) {
                    $session->setData('promptionalgift_coupon_code', $code);
                    $session->setData('promotionalgift_shoppingcart_rule_id', $ruleId);

                    /*Check limit customer - Hades 20/10/2016*/

                    if(!Mage::getSingleton('customer/session')->isLoggedIn()){
                        $session->setData('promptionalgift_coupon_code', null);
                        $session->setData('shoppingcart_couponcode_rule_id', null);
                        $session->setData('promotionalgift_shoppingcart_rule_id', null);
                        $session->setData('promotionalgift_shoppingcart_rule_used', null);
                        $session->addError(Mage::helper('promotionalgift')
                            ->__("You're out of rule to use"));
                        $action->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
                        return;
                    }

                    $customerId = Mage::getSingleton('customer/session')->getId();
                    $promotionalgiftCustomer = Mage::getModel('promotionalgift/limitcustomer')->getCollection()
                        ->addFieldToFilter('customer_id', $customerId)
                        ->addFieldToFilter('shoppingcartrule_id', $ruleId);
                    $shoppingCartRule = Mage::getModel('promotionalgift/shoppingcartrule')->load($ruleId);

                    if(count($promotionalgiftCustomer) >= (int)$shoppingCartRule->getLimitCustomer()){
                        $session->setData('promptionalgift_coupon_code', null);
                        $session->setData('shoppingcart_couponcode_rule_id', null);
                        $session->setData('promotionalgift_shoppingcart_rule_id', null);
                        $session->setData('promotionalgift_shoppingcart_rule_used', null);
                        $session->addError(Mage::helper('promotionalgift')
                            ->__("Over limit uses per customer !"));
                        if ($ruleId) {
                            $shoppingQuote = Mage::getModel('sales/quote_item');
                            $giftItems = Mage::getModel('promotionalgift/shoppingquote')->getCollection()
                                ->addFieldToFilter('shoppingcartrule_id', $ruleId);
                            foreach ($giftItems as $item) {
                                try {
                                    $item->delete();
                                    $cart->removeItem($item->getItemId())->save();
                                } catch (Exception $e) {

                                }
                            }
                        }
                        $action->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));

                        return;
                    }

                    $quote = Mage::getSingleton('checkout/cart')->getQuote();
                    $quote->setCouponCode('');
                    $quote->collectTotals()->save();
                    $available = false;
                    foreach ($quote->getAddressesCollection() as $address) {
                        if (!$address->isDeleted() && $session->getData('promptionalgift_coupon_code') == $code) {
                            $available = true;
                            break;
                        }
                    }
                    if ($session->getData('promotionalgift_shoppingcart_use_full_rule')) {
                        $session->addError(Mage::helper('promotionalgift')
                            ->__("You're out of rule to use. Please remove gift(s) of other rules from cart and try again"));
                    }
                }
            }
            $action->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
        }
        $autoAddGift = Mage::getStoreConfig('promotionalgift/shoppingcart_rule_configuration/automaticadd', Mage::app()->getStore()->getId());
        if (!Mage::registry('autoaddcart') && $autoAddGift) {
            if(!$this->isAllowAutoAddGift()){
                return $this;
            }
            $availableCart = $this->getShoppingcartRule();
            $numberShoppingCart = Mage::getStoreConfig(
                'promotionalgift/shoppingcart_rule_configuration/numberofshoppingcartrule'
            );
            if (isset($numberShoppingCart) && ($numberShoppingCart != null)
                && ($numberShoppingCart > 0)
            ) {
                if (count($availableCart) > $numberShoppingCart) {
                    for (
                        $count = $numberShoppingCart;
                        $count <= count($availableCart); $count++
                    ) {
                        unset($availableCart[$count]);
                    }
                }
            }
            if ($availableCart) {
                Mage::register('autoaddcart', 1);
                $this->autoAddGiftShoppingCart($availableCart);
            }
        }
    }

    public function checkoutCartDelete($observer)
    {
        if (!Mage::helper('promotionalgift')->enablePromotionalgift())
            return $this;
        $action = $observer->getEvent()->getControllerAction();
        $itemId = $action->getRequest()->getParam('id');
        $session = Mage::getModel('checkout/session');
        if ($itemId) {
            $giftQuote = Mage::getModel('promotionalgift/shoppingquote')->load($itemId, 'item_id');
            if ($giftQuote->getId()) {
                $ruleId = $giftQuote->getShoppingcartruleId();
                $numberItems = Mage::getModel('promotionalgift/shoppingquote')->getCollection()
                    ->addFieldToFilter('shoppingcartrule_id', $ruleId);
                if (count($numberItems) == 1) {
                    if ($giftQuote->getCouponCode()) {
                        $session->setData('promotionalgift_shoppingcart_rule_used', null);
                    }
                }
            }
            /* Thinhnd */
            $quoteId = $session->getQuote()->getId();
            $catalogQuotes = Mage::getModel('promotionalgift/quote')->getCollection()
                ->addFieldToFilter('quote_id', $quoteId)
                ->addFieldToFilter('item_id', $itemId);
            $item = Mage::getModel('sales/quote_item')->load($itemId);
            $productParent = unserialize(Mage::getModel('checkout/session')->getData('product_parent_id'));
            foreach ($catalogQuotes as $catalogQuote) {
                if (array_key_exists($catalogQuote->getItemParentId(), $productParent)) {
                    $productId = explode(',', $productParent[$catalogQuote->getItemParentId()]);
                    if (!in_array($item->getProductId(), $productId)) {
                        $productId = implode(',', $productId) . ',' . $item->getProductId();
                        $productParent[$catalogQuote->getItemParentId()] = $productId;
                    }
                }
            }
            Mage::getModel('checkout/session')->setData('product_parent_id', serialize($productParent));
            /* end */
        }
    }

    protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getQuote()
    {
        return $this->_getCart()->getQuote();
    }

    public function catalogProductView($observer)
    {
        if (!Mage::helper('promotionalgift')->enablePromotionalgift())
            return $this;
        $action = $observer->getEvent()->getControllerAction();
        if ($action->getRequest()->getParam('isajaxfreecart') == 'true') {
            $categoryId = (int)$action->getRequest()->getParam('category', false);
            $productId = (int)$action->getRequest()->getParam('id');
            $specifyOptions = $action->getRequest()->getParam('options');
            $items = $action->getRequest()->getParam('items');
            if (empty($items)) {
                $session = Mage::getModel('checkout/session');
                $abc = $session->getData('abc');
                $abc = 1;
                $session->setData('abc', $abc);
                $ruleId = $action->getRequest()->getParam('ruleId');
                $checkout = $action->getRequest()->getParam('checkout');
                $productChechoutId = $action->getRequest()->getParam('product_id');
                if (empty($checkout)) {
                    $url = 'promotionalgift/index/addPromotionalGifts/product_id/' . $productId . '/ruleId/' . $ruleId;
                } else {
                    $url = 'promotionalgift/index/addPromotionalGiftsCheckout/product_id/' . $productChechoutId . '/ruleId/' . $ruleId;
                }
            } else {
                $session = Mage::getModel('checkout/session');
                $abc = $session->getData('def');
                $abc = 1;
                $session->setData('def', $abc);
                $categoryruleid = $action->getRequest()->getParam('categoryruleid');
                $qty = $action->getRequest()->getParam('qty');
                $url = 'promotionalgift/index/addPromotionalGiftsCategoryRule/items/' . $items . '/categoryruleid/' . $categoryruleid . '/qty/' . $qty;
            }
            $result = array();
            $viewHelper = Mage::helper('promotionalgift/product_view');

            $params = new Varien_Object();
            $params->setCategoryId($categoryId);

            $productHelper = Mage::helper('promotionalgift/product');

            try {
                $product = $productHelper->initProduct($productId, $action, $params);
                if (!$product) {
                    $this->_getSession()->addError($viewHelper->__('Product is not loaded'));
                } else {
                    // if ($buyRequest = $params->getBuyRequest())
                    // $productHelper->prepareProductOptions($product, $buyRequest);
                    // $product->setConfigureMode(true);
                    //Mage::dispatchEvent('catalog_controller_product_view', array('product' => $product));
                    $viewHelper->initProductLayout($product, $action);
                    $result['hasOptions'] = true;
                }
            } catch (Exception $e) {
                $this->_getSession()->addError(Mage::helper('checkout')->__('Cannot configure product.'));
            }
            $action->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
            $params = $action->getRequest()->getParams();
            if (isset($params['groupmessage']) && $params['groupmessage'] && $action->getLayout()->getMessagesBlock()) {
                $action->getLayout()->getMessagesBlock()->addMessages($this->_getSession()->getMessages(true));
                $action->getLayout()->getMessagesBlock()->setEscapeMessageFlag($this->_getSession()->getEscapeMessages(true));
                $result['message'] = $action->getLayout()->getMessagesBlock()->getGroupedHtml();
            } else {
                $this->_getSession()->getMessages(true);
                $this->_getSession()->getEscapeMessages(true);
            }
            if (isset($result['hasOptions'])) {
                $productType = $product->getTypeId();
                if ($productType == 'bundle' || $productType == 'giftvoucher' || $productType == 'grouped' || $productType == 'configurable') {
                    $productBlock = $action->getLayout()->createBlock('promotionalgift/product_' . $product->getTypeId(), 'promotionalgift_product_view');
                } else {
                    $productBlock = $action->getLayout()->createBlock('promotionalgift/product_view', 'promotionalgift_product_view');
                }
                $id = 1;
                $productBlock->setData('submit_route_data', array(
                    'route' => $url,
                    'params' => array('id' => $id),
                ));
                if ($productType == 'giftvoucher') {
                    $giftvoucherBlock = $action->getLayout()->getBlock('product.info.media');
                    $result['giftvoucher'] = $giftvoucherBlock->toHtml();
                }
                $result['optionhtml'] = $productBlock->toHtml();
            }
            $action->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    public function checkoutCartConfigure($observer)
    {
        if (!Mage::helper('promotionalgift')->enablePromotionalgift())
            return $this;
        $action = $observer->getEvent()->getControllerAction();
        if ($action->getRequest()->getParam('isajaxfreecart') == 'true') {
            $id = (int)$action->getRequest()->getParam('id');
            $quoteItem = null;
            $cart = $this->_getCart();
            if ($id)
                $quoteItem = $cart->getQuote()->getItemById($id);

            $result = array();
            if (!$quoteItem) {
                $action->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
                return $action->getResponse()->setBody('');
            }
            $viewHelper = Mage::helper('promotionalgift/product_view');

            $params = new Varien_Object();
            $params->setCategoryId(false);
            $params->setConfigureMode(true);
            $params->setBuyRequest($quoteItem->getBuyRequest());

            $productHelper = Mage::helper('promotionalgift/product');
            $productId = $quoteItem->getProduct()->getId();

            try {
                $product = $productHelper->initProduct($productId, $action, $params);
                if (!$product) {
                    $this->_getSession()->addError($viewHelper->__('Product is not loaded'));
                } else {
                    if ($buyRequest = $params->getBuyRequest())
                        $productHelper->prepareProductOptions($product, $buyRequest);
                    $product->setConfigureMode(true);
                    Mage::dispatchEvent('catalog_controller_product_view', array('product' => $product));
                    $viewHelper->initProductLayout($product, $action);
                    $result['hasOptions'] = true;
                }
            } catch (Exception $e) {
                $this->_getSession()->addError(Mage::helper('checkout')->__('Cannot configure product.'));
            }
            $action->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
            $params = $action->getRequest()->getParams();
            if (isset($params['groupmessage']) && $params['groupmessage'] && $action->getLayout()->getMessagesBlock()) {
                $action->getLayout()->getMessagesBlock()->addMessages($this->_getSession()->getMessages(true));
                $action->getLayout()->getMessagesBlock()->setEscapeMessageFlag($this->_getSession()->getEscapeMessages(true));
                $result['message'] = $action->getLayout()->getMessagesBlock()->getGroupedHtml();
            } else {
                $this->_getSession()->getMessages(true);
                $this->_getSession()->getEscapeMessages(true);
            }
            if (isset($result['hasOptions'])) {
                $productType = $product->getTypeId();
                if ($productType == 'bundle' || $productType == 'giftvoucher' || $productType == 'grouped' || $productType == 'configurable') {
                    $productBlock = $action->getLayout()->createBlock('promotionalgift/product_' . $product->getTypeId(), 'promotionalgift_product_view');
                } else {
                    $productBlock = $action->getLayout()->createBlock('promotionalgift/product_view', 'promotionalgift_product_view');
                }
                $productBlock->setData('submit_route_data', array(
                    'route' => 'checkout/cart/updateItemOptions',
                    'params' => array('id' => $id),
                ));
                $result['optionhtml'] = $productBlock->toHtml();
            }
            $action->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    public function checkoutCartUpdateItemOptions($observer)
    {
        if (!Mage::helper('promotionalgift')->enablePromotionalgift())
            return $this;
        $action = $observer->getEvent()->getControllerAction();
        $id = (int)$action->getRequest()->getParam('id');
        $productId = Mage::getModel('sales/quote_item')->load($id, 'item_id')->getProductId();
        $quoteId = Mage::getModel('checkout/session')->getQuote()->getId();
        $catalogItems = Mage::getModel('promotionalgift/quote')->getCollection()
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('item_id', $id)
            ->getFirstItem();
        if ($catalogItems->getId()) {
            $catalogRuleId = $catalogItems->getCatalogRuleId();
            $freeGiftItemQty = $catalogItems->getNumberItemFree();
            Mage::getModel('checkout/session')->setData('catalog_rule_id', $catalogRuleId);
            Mage::getModel('checkout/session')->setData('free_gift_item_qty', $freeGiftItemQty);
            if ($productId) {
                Mage::getModel('checkout/session')->setData('free_gift_item', $productId);
                $productBundle = new Mage_Catalog_Model_Product();
                $productBundle->load($productId);
                if ($productBundle->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE)
                    Mage::getModel('checkout/session')->setData('product_bundle', $productId);
            }
            /* ThinhND */
            $catalogQuotes = Mage::getModel('promotionalgift/quote')->getCollection()
                ->addFieldToFilter('quote_id', $quoteId)
                ->addFieldToFilter('item_id', $id);
            $item = Mage::getModel('sales/quote_item')->load($id);
            $productParent = unserialize(Mage::getModel('checkout/session')->getData('product_parent_id'));
            foreach ($catalogQuotes as $catalogQuote) {
                if (array_key_exists($catalogQuote->getItemParentId(), $productParent)) {
                    $productId = explode(',', $productParent[$catalogQuote->getItemParentId()]);
                    if (!in_array($item->getProductId(), $productId)) {
                        $productId = implode(',', $productId) . ',' . $item->getProductId();
                        $productParent[$catalogQuote->getItemParentId()] = $productId;
                    }
                }
            }
            Mage::getModel('checkout/session')->setData('product_parent_id', serialize($productParent));
            /* end */
        }
        $shoppingcartItems = Mage::getModel('promotionalgift/shoppingquote')->getCollection()
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('item_id', $id)
            ->getFirstItem();
        if ($shoppingcartItems->getId()) {
            $shoppingCartRuleId = $shoppingcartItems->getShoppingcartruleId();
            Mage::getModel('checkout/session')->setData('shoppingcart_rule_id', $shoppingCartRuleId);
            if ($productId) {
                Mage::getModel('checkout/session')->setData('shoppingcart_gift_item', $productId);
                $productBundle = new Mage_Catalog_Model_Product();
                $productBundle->load($productId);
                if ($productBundle->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE)
                    Mage::getModel('checkout/session')->setData('product_bundle', $productId);
            }
        }

        if ($action->getRequest()->getParam('isajaxfreecart') == 'true') {
            $cart = $this->_getCart();
            $params = $action->getRequest()->getParams();
            $result = array();
            if (!isset($params['options']))
                $params['options'] = array();
            try {
                if (isset($params['qty'])) {
                    $filter = new Zend_Filter_LocalizedToNormalized(array('locale' => Mage::app()->getLocale()->getLocaleCode()));
                    $params['qty'] = $filter->filter($params['qty']);
                }
                $quoteItem = $cart->getQuote()->getItemById($id);
                if ($quoteItem) {
                    $item = $cart->updateGiftItem($id, new Varien_Object($params));
                    if (is_string($item)) {
                        $this->_getSession()->addError($item);
                    } elseif ($item->getHasError()) {
                        $this->_getSession()->addError($item->getMessage());
                    } else {
                        $related = $action->getRequest()->getParam('related_product');
                        if (!empty($related))
                            $cart->addProductsByIds(explode(',', $related));
                        $cart->save();
                        $this->_getSession()->setCartWasUpdated(true);
                        Mage::dispatchEvent('checkout_cart_update_item_complete', array('item' => $item, 'request' => $action->getRequest(), 'response' => $action->getResponse()));
                        if (!$cart->getQuote()->getHasError()) {
                            $message = Mage::helper('checkout')->__('%s was updated in your shopping cart.', Mage::helper('core')->htmlEscape($item->getProduct()->getName()));
                            $this->_getSession()->addSuccess($message);
                        }
                    }
                } else {
                    $this->_getSession()->addError(Mage::helper('checkout')->__('Quote item is not found.'));
                }
            } catch (Mage_Core_Exception $e) {
                if ($this->_getSession()->getUseNotice(true)) {
                    $this->_getSession()->addNotice($e->getMessage());
                } else {
                    $messages = array_unique(explode("\n", $e->getMessage()));
                    foreach ($messages as $message)
                        $this->_getSession()->addError($message);
                }
            } catch (Exception $e) {
                $this->_getSession()->addError(Mage::helper('checkout')->__('Cannot update the item.'));
            }
            $action->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
            if (isset($params['groupmessage']) || isset($params['minicart']) || isset($params['ajaxfreelinks']) || isset($params['isajaxfreecartpage'])) {
                $action->loadLayout();
                if (isset($item) && $item) {
                    $relatedBlock = $action->getLayout()->createBlock('promotionalgift/display')->setProduct($item->getProduct());
                    $result['relatedhtml'] = $relatedBlock->toHtml();
                }
                if (isset($params['minicart']) && $params['minicart'] && $action->getLayout()->getBlock('cart_sidebar')) {
                    $result['miniCart'] = $action->getLayout()->getBlock('cart_sidebar')->toHtml();
                }
                if (isset($params['groupmessage']) && $params['groupmessage'] && $action->getLayout()->getMessagesBlock()) {
                    $action->getLayout()->getMessagesBlock()->addMessages($this->_getSession()->getMessages(true));
                    $action->getLayout()->getMessagesBlock()->setEscapeMessageFlag($this->_getSession()->getEscapeMessages(true));
                    $result['message'] = $action->getLayout()->getMessagesBlock()->getGroupedHtml();
                } else {

                }
                if (isset($params['ajaxfreelinks']) && $params['ajaxfreelinks'] && $action->getLayout()->getBlock('top.links')) {
                    $result['ajaxfreelinks'] = $action->getLayout()->getBlock('top.links')->toHtml();
                }
                if (isset($params['isajaxfreecartpage']) && $params['isajaxfreecartpage']) {
                    $result['hasOptions'] = true;
                    $result['redirectUrl'] = Mage::getUrl('checkout/cart/index');
                }
            }

            $action->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
        }
    }

    public function salesQuoteProductAddAfter($observer)
    {
        //Add Promotional gift by shopping cart rule
        $items = $observer['items'];
        $ruleId = Mage::getModel('checkout/session')->getData('catalog_rule_id');
        if (!empty($ruleId)) {
            $catalogrule = Mage::getModel('promotionalgift/catalogrule')->load($ruleId);
            if ($catalogrule->getPriceType() == 1)
                $percentDiscount = $catalogrule->getDiscountProduct();
            else
                $fixedDiscount = $catalogrule->getDiscountProductFixed();
        } else {
            $ruleId = Mage::getModel('checkout/session')->getData('shoppingcart_rule_id');
            $shoppingcartrule = Mage::getModel('promotionalgift/shoppingcartrule')->load($ruleId);
            if ($shoppingcartrule->getPriceType() == 1)
                $percentDiscount = $shoppingcartrule->getDiscountProduct();
            else
                $fixedDiscount = $shoppingcartrule->getDiscountProductFixed();
        }

        if (Mage::getModel('checkout/session')->getData('shoppingcart_gift_item') || Mage::getModel('checkout/session')->getData('free_gift_item') || Mage::getModel('checkout/session')->getData('promotionalgift_bundle')) {
            $i=0;
            foreach ($items as $item) {
                $oldPrice = Mage::helper('core')->currency($item->getProduct()->getFinalPrice(), false, false);
                if (Mage::getModel('checkout/session')->getData('product_bundle') && $item->getProductId() != Mage::getModel('checkout/session')->getData('product_bundle')) {
                    $productBundle = new Mage_Catalog_Model_Product();
                    $productBundle->load(Mage::getModel('checkout/session')->getData('product_bundle'));
                    $specialPrice = $productBundle->getSpecialPrice();
                    if($fixedDiscount !== null)
                    {
                        ++$i;
                        if($i>=2) continue;
                    }
                    if ($specialPrice) {
                        $oldPrice = $oldPrice * $specialPrice / 100;
                    }
                }

                if ($percentDiscount !== null)
                    $newPrice = $oldPrice - ($oldPrice * $percentDiscount) / 100;
                if ($fixedDiscount !== null) {
                    $fixedDiscount = Mage::helper('core')->currency($fixedDiscount, false, false); // eden fix currency
                    $newPrice = $oldPrice - (($fixedDiscount >= $oldPrice) ? $oldPrice : $fixedDiscount);
                }
                $item->setCustomPrice($newPrice);
                $item->setOriginalCustomPrice($newPrice);
                $item->getProduct()->setIsSuperMode(true);
                if (Mage::registry('autoshopping')) {
                    $item->save();
                }
                //$item->save();


            }
            //Mage::getModel('checkout/session')->setData('catalog_rule_id',null);
            return $this;
        }
        //Mage::getModel('checkout/session')->setData('catalog_rule_id',null);

    }

    public function checkoutCartUpdatePost($observer)
    {
        $action = $observer->getEvent()->getControllerAction();
        $updateAction = (string)$action->getRequest()->getParam('update_cart_action');
        if ($updateAction == 'empty_cart') {
            $quoteId = Mage::getModel('checkout/cart')->getQuote()->getId();
            $items = Mage::getModel('checkout/cart')->getQuote()->getAllItems();
            foreach ($items as $item) {
                $model = Mage::getModel('promotionalgift/shoppingquote');
                $giftItemId = $model
                    ->getCollection()
                    ->addFieldToFilter('item_id', $item->getItemId())
                    ->addFieldToFilter('quote_id', $quoteId)
                    ->getFirstItem()
                    ->getShoppingcartQuoteId();
                if ($giftItemId) {
                    $model->setId($giftItemId)->delete();
                }
            }
            Mage::getModel('checkout/session')->setData('catalog_rule_id', null);
            Mage::getModel('checkout/session')->setData('product_parent', null);
            Mage::getModel('checkout/session')->setData('free_gift_item', null);
            Mage::getModel('checkout/session')->setData('free_gift_item_qty', null);
            Mage::getModel('checkout/session')->setData('catalog_rule_ids', null);
            Mage::getModel('checkout/session')->setData('shoppingcart_gift_item', null);
            Mage::getModel('checkout/session')->setData('product_bundle', null);
            Mage::getModel('checkout/session')->setData('promotionalgift_shoppingcart_rule_id', null);
            Mage::getModel('checkout/session')->setData('promptionalgift_coupon_code', null);
            Mage::getModel('checkout/session')->setData('promotionalgift_shoppingcart_rule_used', null);
            Mage::getModel('checkout/session')->setData('shoppingcart_couponcode_rule_id', null);
            Mage::getModel('checkout/session')->setData('promotionalgift_shoppingcart_use_full_rule', null);
            Mage::getModel('checkout/session')->setData('has_product_parent', null);
            Mage::getSingleton('checkout/session')->setData('autoaddcart', null);


        }
    }

    public function customerLogin($observer)
    {
        $items = Mage::getModel('checkout/cart')->getQuote()->getAllItems();
        $shoppingGiftItems = array();
        $catalogGiftItems = array();
        $customer = $observer['customer'];
        foreach ($items as $item) {
            $itemOptions = $item->getOptions();
            foreach ($itemOptions as $option) {
                $oData = $option->getData();
                if ($oData['code'] == 'option_promotionalgift_shoppingcartrule') {
                    $shoppingGiftItems[] = $item->getId();
                }
                if ($oData['code'] == 'option_promotionalgift_catalogrule') {
                    $catalogGiftItems[] = $item->getId();
                }
            }
            if (!in_array($item->getId(), $shoppingGiftItems) && !in_array($item->getId(), $catalogGiftItems))
                $parentItems[$item->getId()] = $item->getProductId();
        }
        $shoppingData = array();
        if (count($shoppingGiftItems) > 0) {
            foreach ($shoppingGiftItems as $shoppingGiftItem) {
                $ruleId = Mage::getModel('sales/quote_item_option')->getCollection()
                    ->addFieldToFilter('item_id', $shoppingGiftItem)
                    ->addFieldToFilter('code', 'option_promotionalgift_shoppingcartrule')
                    ->getFirstItem()
                    ->getValue();
                $quote=Mage::getSingleton('checkout/session')->getQuote();
                $checkrule=Mage::getModel('promotionalgift/shoppingcartrule')->load($ruleId)->validateQuote($quote);
                if(!$checkrule)
                {
                    $item->delete();
                    Mage::getModel('checkout/session')->setData('reset_cart', true);
                }
                if ($ruleId) {
                    $rule = Mage::getModel('promotionalgift/shoppingcartrule')->load($ruleId);
                    $item = Mage::getModel('sales/quote_item')->load($shoppingGiftItem);
                    if (in_array($customer->getGroupId(), explode(",", $rule->getCustomerGroupIds()))) {
                        $shoppingData['quote_id'] = $item->getQuoteId();
                        $shoppingData['item_id'] = $shoppingGiftItem;
                        $shoppingData['shoppingcartrule_id'] = $ruleId;
                        $shoppingData['coupon_code'] = '';
                        if ($rule->getCouponType() == 2) {
                            $shoppingData['coupon_code'] = $rule->getCouponCode();
                        }
                        $shoppingData['message'] = $rule->getName();
                        $promotionalGiftQuote = Mage::getModel('promotionalgift/shoppingquote');
                        try {
                            $promotionalGiftQuote->setData($shoppingData)->save();
                        } catch (exception $e) {

                        }
                    } else {
                        $item->delete();
                        Mage::getModel('checkout/session')->setData('reset_cart', true);
                    }
                }
            }
        }
        $catalogData = array();
        if (count($catalogGiftItems) > 0) {
            foreach ($catalogGiftItems as $catalogGiftItem) {
                $ruleId = Mage::getModel('sales/quote_item_option')->getCollection()
                    ->addFieldToFilter('item_id', $catalogGiftItem)
                    ->addFieldToFilter('code', 'option_promotionalgift_catalogrule')
                    ->getFirstItem()
                    ->getValue();
                $groupedId = Mage::getModel('sales/quote_item_option')->getCollection()
                    ->addFieldToFilter('item_id', $catalogGiftItem)
                    ->addFieldToFilter('code', 'product_type')
                    ->addFieldToFilter('value', 'grouped')
                    //->addFieldToFilter('value','promotionalgift_catalogrule');
                    ->getFirstItem()
                    ->getProductId();
                $rule = Mage::getModel('promotionalgift/catalogrule')->load($ruleId);
                $item = Mage::getModel('sales/quote_item')->load($catalogGiftItem);
                if (in_array($customer->getGroupId(), explode(",", $rule->getCustomerGroupIds()))) {
                    // $item->setCustomPrice(null);
                    // $item->setOriginalCustomPrice(null);
                    // //$item->setFreeShipping(true);
                    // $item->getProduct()->setIsSuperMode(true);
                    // $item->save();
                    if (!$groupedId)
                        $groupedId = 0;
                    $catalogData['quote_id'] = $item->getQuoteId();
                    $catalogData['item_id'] = $catalogGiftItem;
                    $catalogData['catalog_rule_id'] = $ruleId;
                    $catalogData['number_item_free'] = $item->getQty();
                    $catalogData['message'] = $rule->getName();
                    $catalogData['grouped_id'] = $groupedId;
                    $promotionalGiftQuote = Mage::getModel('promotionalgift/quote');
                    try {
                        $promotionalGiftQuote->setData($catalogData)->save();
                    } catch (exception $e) {

                    }
                } else {
                    $item->delete();
                    Mage::getModel('checkout/session')->setData('reset_cart', true);
                }
            }
        }
        if (count($parentItems) > 0) {
            foreach ($parentItems as $parentItem => $productId) {
                $productParent = unserialize(Mage::getModel('checkout/session')->getData('product_parent_id'));
                if (!$productParent) {
                    $productParent1 = array();
                    $productParent1[$parentItem] = Mage::helper('promotionalgift')->getProductChild($parentItem, $productId);
                    Mage::getModel('checkout/session')->setData('product_parent_id', serialize($productParent1));
                } else {
                    $productParent[$parentItem] = Mage::helper('promotionalgift')->getProductChild($parentItem, $productId);
                    Mage::getModel('checkout/session')->setData('product_parent_id', serialize($productParent));
                }
            }
        }
    }

    public function salesOrderCancelAfter($observer)
    {
        $order = $observer['order'];
        $quoteId = $order->getQuoteId();
        $catalogRuleQuote = Mage::getModel('promotionalgift/quote')
            ->getCollection()
            ->addFieldToFilter('quote_id', $quoteId);
        $shoppingcartRuleQuote = Mage::getModel('promotionalgift/shoppingquote')
            ->getCollection()
            ->addFieldToFilter('quote_id', $quoteId);
        $shoppingcartRuleIds = array();
        if (count($shoppingcartRuleQuote) > 0) {
            foreach ($shoppingcartRuleQuote as $rule) {
                if (!in_array($rule->getData('shoppingcartrule_id'), $shoppingcartRuleIds)) {
                    $shoppingcartRuleIds[] = $rule->getData('shoppingcartrule_id');
                }
            }
        }
        if (count($shoppingcartRuleIds) > 0) {
            foreach ($shoppingcartRuleIds as $shoppingcartRuleId) {
                $newLimit = null;
                $shoppingCartRule = Mage::getModel('promotionalgift/shoppingcartrule')->load($shoppingcartRuleId);
                $limit = $shoppingCartRule->getUsesPerCoupon();
                if ($limit != null) {
                    $newLimit = $limit + 1;
                    $shoppingCartRule->setUsesPerCoupon($newLimit)->save();
                }
            }
        }

        $catalogRuleQuoteIds = array();
        if (count($catalogRuleQuote) > 0) {
            foreach ($catalogRuleQuote as $catalogRule) {
                if (!in_array($catalogRule->getData('catalog_rule_id'), $catalogRuleQuoteIds)) {
                    $catalogRuleQuoteIds[] = $catalogRule->getData('catalog_rule_id');
                }
            }
        }

        if (count($catalogRuleQuoteIds) > 0) {
            foreach ($catalogRuleQuoteIds as $catalogRuleQuoteId) {
                $catalogNewLimit = null;
                $catalogRule = Mage::getModel('promotionalgift/catalogrule')->load($catalogRuleQuoteId);
                $limit = $catalogRule->getUsesLimit();
                if ($limit != null) {
                    $catalogNewLimit = $limit + 1;
                    $catalogRule->setUsesLimit($catalogNewLimit)->save();
                }
            }
        }
    }

    public function salesOrderRefundAfter($observer)
    {
        $order = $observer['creditmemo']->getOrder();
        $quoteId = $order->getQuoteId();
        $catalogRuleQuote = Mage::getModel('promotionalgift/quote')
            ->getCollection()
            ->addFieldToFilter('quote_id', $quoteId);
        $shoppingcartRuleQuote = Mage::getModel('promotionalgift/shoppingquote')
            ->getCollection()
            ->addFieldToFilter('quote_id', $quoteId);
        $shoppingcartRuleIds = array();
        if (count($shoppingcartRuleQuote) > 0) {
            foreach ($shoppingcartRuleQuote as $rule) {
                if (!in_array($rule->getData('shoppingcartrule_id'), $shoppingcartRuleIds)) {
                    $shoppingcartRuleIds[] = $rule->getData('shoppingcartrule_id');
                }
            }
        }
        if (count($shoppingcartRuleIds) > 0) {
            foreach ($shoppingcartRuleIds as $shoppingcartRuleId) {
                $newLimit = null;
                $shoppingCartRule = Mage::getModel('promotionalgift/shoppingcartrule')->load($shoppingcartRuleId);
                $limit = $shoppingCartRule->getUsesPerCoupon();
                if ($limit != null) {
                    $newLimit = $limit + 1;
                    $shoppingCartRule->setUsesPerCoupon($newLimit)->save();
                }
            }
        }

        $catalogRuleQuoteIds = array();
        if (count($catalogRuleQuote) > 0) {
            foreach ($catalogRuleQuote as $catalogRule) {
                if (!in_array($catalogRule->getData('catalog_rule_id'), $catalogRuleQuoteIds)) {
                    $catalogRuleQuoteIds[] = $catalogRule->getData('catalog_rule_id');
                }
            }
        }

        if (count($catalogRuleQuoteIds) > 0) {
            foreach ($catalogRuleQuoteIds as $catalogRuleQuoteId) {
                $catalogNewLimit = null;
                $catalogRule = Mage::getModel('promotionalgift/catalogrule')->load($catalogRuleQuoteId);
                $limit = $catalogRule->getUsesLimit();
                if ($limit != null) {
                    $catalogNewLimit = $limit + 1;
                    $catalogRule->setUsesLimit($catalogNewLimit)->save();
                }
            }
        }
    }

    //action in onepage check out when customer save
    public function addAjaxToCheckoutForm($observer)
    {
        $request = Mage::app()->getRequest();
        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();
        $block = $observer['block'];
        $is_checkout = Mage::getModel('core/session')
            ->getData('promotionalgift_is_checkout_ajax');
        if ($module == 'onestepcheckout') {
            if ($is_checkout) {
                if ($block instanceof Mage_Checkout_Block_Onepage_Review_Info) {
                    $transport = $observer['transport'];
                    $html_addajax = $block->getLayout()->createBlock('promotionalgift/checkout_ajaxload')->renderView();
                    $html = $transport->getHtml();
                    $html = $html_addajax . $html;
                    $transport->setHtml($html);
                    Mage::getModel('core/session')->setData('promotionalgift_is_checkout_ajax', null);
                }
            }
        } else if ($module == 'checkout') {
            if ($is_checkout) {
                if (!Mage::app()->getRequest()->getParam('review')) {
                    if ($block instanceof Mage_Checkout_Block_Onepage_Billing || $block instanceof Mage_Checkout_Block_Onepage_Shipping || $block instanceof Mage_Checkout_Block_Onepage_Shipping_Method_Additional || $block instanceof Mage_Checkout_Block_Onepage_Shipping_Method_Available || $block instanceof Mage_Checkout_Block_Onepage_Review_Info || $block instanceof Mage_Checkout_Block_Onepage_Payment_Methods) {
                        $transport = $observer['transport'];
                        $html_addajax = $block->getLayout()->createBlock('promotionalgift/checkout_ajaxload')->renderView();
                        $html = $transport->getHtml();
                        $html = $html_addajax . $html;
                        $transport->setHtml($html);
                        Mage::getModel('core/session')->setData('promotionalgift_is_checkout_ajax', null);
                    }
                }
            } else {
                if ($action == 'saveShippingMethod' && $block instanceof Mage_Checkout_Block_Onepage_Payment_Methods) {
                    $transport = $observer['transport'];
                    $html_addajax = $block->getLayout()->createBlock('promotionalgift/checkout_ajaxload')->renderView();
                    $html = $transport->getHtml();
                    $html = $html_addajax . $html;
                    $transport->setHtml($html);
                    Mage::getModel('core/session')->setData('promotionalgift_is_checkout_ajax', null);
                }
            }
        }
    }
    public function checkoutCartSaveAfter($observer) {
        $request = Mage::app()->getRequest();
        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();
        $updateAction = (string)Mage::app()->getRequest()->getParam('update_cart_action');
        $removeParam = Mage::app()->getRequest()->getParam('remove');
        $autoAddGift = Mage::getStoreConfig('promotionalgift/shoppingcart_rule_configuration/automaticadd', Mage::app()->getStore()->getId());
        if (($action=='add'|| $action=='updatePost')&& $updateAction != 'empty_cart' && $removeParam != 1 && $autoAddGift) {
            if(!$this->isAllowAutoAddGift()){
                return $this;
            }
            if (!Mage::registry('autoaddcart') && (!Mage::getModel('checkout/session')->getData('catalog_rule_id'))) {
                if (!Mage::getModel('checkout/session')->getData('catalog_rule_id')) {
                    $availableCart = $this->getShoppingcartRule();
                    $numberShoppingCart = Mage::getStoreConfig(
                        'promotionalgift/shoppingcart_rule_configuration/numberofshoppingcartrule'
                    );
                    if (isset($numberShoppingCart) && ($numberShoppingCart != null)
                        && ($numberShoppingCart > 0)
                    ) {
                        if (count($availableCart) > $numberShoppingCart) {
                            for (
                                $count = $numberShoppingCart;
                                $count <= count($availableCart); $count++
                            ) {
                                unset($availableCart[$count]);
                            }
                        }
                    }
                    //$catalogRuleSession=Mage::getModel('checkout/session')->getData('catalog_rule_id');
                    if ($availableCart) {
                        if ($action=='add') {
                            Mage::register('autoshopping',1);
                        }
                        //Mage::log('catalogRuleSession: '.$catalogRuleSession, null, 'promo.txt');
                        Mage::register('autoaddcart', 1);
                        $this->autoAddGiftShoppingCart($availableCart);
                    }
                }
            }
        }
    }
    public function salesQuoteSaveAfter($observer)
    {
        $request = Mage::app()->getRequest();
        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();
        $nullAction = Mage::helper('promotionalgift')->getNullSessionActionName();
        if ($module == 'checkout') {
            if ($controller == 'onepage') {
                if (in_array($action, $nullAction)) {
                    Mage::getModel('core/session')->setData('promotionalgift_is_checkout_ajax', null);
                } else {
                    Mage::getModel('core/session')->setData('promotionalgift_is_checkout_ajax', 1);
                }
            }
        } else if ($module == 'onestepcheckout') {
            if (in_array($action, $nullAction)) {
                Mage::getModel('core/session')->setData('promotionalgift_is_checkout_ajax', null);
            } else {
                Mage::getModel('core/session')->setData('promotionalgift_is_checkout_ajax', 1);
            }
        }
    }



    public function getShoppingcartRule()
    {
        $quote = Mage::getModel('checkout/session')->getQuote();
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
                    $maxItems = $rule->getNumberItemFree();
                    if (count($ruleUsed)) {
                        $checkRulesUsed[] = $rule->getId();
                    }
                    if (count($ruleUsed) == 0 || (int)(count($ruleUsed)) < (int)$maxItems) {
                        if ($checkCalendar == true) {
                            $ruleIds[] = $rule->getId();
                        }
                    }
                    if (count($checkRulesUsed) == $number)
                        break;
                }
            }
            if (count($checkRulesUsed) == $number) {
                $ruleIds = array();
                foreach ($checkRulesUsed as $checkRuleUsed) {
                    $numberItemsAdded = Mage::getModel('promotionalgift/shoppingquote')
                        ->getCollection()
                        ->addFieldToFilter('shoppingcartrule_id', $checkRuleUsed)
                        ->addFieldToFilter('quote_id', $quoteId);
                    $numberItemFree = Mage::getModel('promotionalgift/shoppingcartrule')
                        ->load($checkRuleUsed)
                        ->getNumberItemFree();
                    if ((int)(count($numberItemsAdded)) < (int)$numberItemFree) {
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

    public function getCouponCodeRule()
    {
        $session = Mage::getModel('checkout/session');
        $ruleId = $session->getData('promotionalgift_shoppingcart_rule_id');
        $ruleUsed = $session->getData('promotionalgift_shoppingcart_rule_used');
        $ruleIds = array();
        // Zend_debug::dump($ruleUsed);die();
        if ($ruleUsed)
            return false;
        if ($ruleId)
            $rule = Mage::getModel('promotionalgift/shoppingcartrule')->load($ruleId);
        if (isset($rule) && $rule != false) {
            $ruleIds[] = $ruleId;
            return $ruleIds;
        } else
            return false;
    }

    public function autoAddGiftShoppingCart($availableCart)
    {


        foreach ($availableCart as $shId) {

            $availableShoppingCart = Mage::getModel('promotionalgift/shoppingcartrule')->load($shId);

            if ($availableShoppingCart) {
                $shoppingCartItem = Mage::getModel('promotionalgift/shoppingcartitem')
                    ->getCollection()
                    ->addFieldToFilter('rule_id', $availableShoppingCart->getRuleId())
                    ->getFirstItem();
                if ($shoppingCartItem->getRuleId()) {
                    $quoteId = Mage::getModel('checkout/session')->getQuote()->getId();
                    $ruleId = $shoppingCartItem->getRuleId();
                    $number_item_free = $availableShoppingCart->getNumberItemFree(); //lay ra so item dc chon
                    $itemIds = Mage::getModel('promotionalgift/shoppingquote')
                        ->getCollection()
                        ->addFieldToFilter('quote_id', $quoteId)
                        ->addFieldToFilter('shoppingcartrule_id', $ruleId);
                    $productIds = array();
                    $qtys = $shoppingCartItem->getGiftQty();
                    $qtys = explode(',', $qtys);
                    $productIdString = $shoppingCartItem->getProductIds();
                    $productIds = explode(',', $productIdString);
                    if ($itemIds) {
                        foreach ($itemIds as $itemId) {
                            $itemId = $itemId->getItemId();
                            $cartitems = Mage::getModel('checkout/cart')->getQuote()->getAllItems();
                            foreach ($cartitems as $cartitem) {
                                if ($cartitem->getItemId() == $itemId) {
                                    $productId = $cartitem->getProductId();
                                    if (in_array($productId, $productIds)) {
                                        $key = array_search($productId, $productIds);
                                        unset($productIds[$key]);
                                        unset($qtys[$key]);
                                        $number_item_free = $number_item_free - 1;
                                    }
                                }
                            }
                        }
                    }

                    $newProductIds = array();
                    $newQtys = array();
                    foreach ($productIds as $productId) {
                        $newProductIds[] = $productId;
                    }
                    foreach ($qtys as $qty) {
                        $newQtys[] = $qty;
                    }
                    $qtys = $newQtys;
                    $productIds = $newProductIds;

                    $newProductIds = array();
                    $newQtys = array();
                    if (isset($number_item_free) && ($number_item_free != NULL) && ($number_item_free > 0)) {
                        for ($count = 0; $count < count($productIds); $count++) {
                            $newProductIds[] = $productIds[$count];
                            $newQtys[] = $qtys[$count];
                        }
                        $productIds = $newProductIds;
                        $qtys = $newQtys;
                    }


                    if (count($productIds) == 0) {
                        $productIds = $shoppingCartItem->getProductIds();
                        $productIds = explode(',', $productIds);
                    }
                    if (count($qtys) == 0) {
                        $qtys = $shoppingCartItem->getGiftQty();
                        $qtys = explode(',', $qtys);
                    }
                    $newProductIds = array();
                    $newQtys = array();
                    $websiteId = Mage::app()->getStore()->getWebsiteId();


                    $newProductId = array();
                    $newQtys = array();

                    foreach ($productIds as $key => $productId) {
                        $giftItem = new Mage_Catalog_Model_Product();
                        $giftItem->load($productId);
                        $productWebsiteIds = $giftItem->getWebsiteIds();
                        if ($giftItem->isAvailable() && in_array($websiteId, $productWebsiteIds)) {
                            $newProductId[] = $productId;
                            $newQtys[] = $qtys[$key];
                        }
                    }
                    $productIds = $newProductId;
                    $qtys = $newQtys;
                    $newProductIds = array();
                    $newQtys = array();
                    if (isset($number_item_free) && ($number_item_free != NULL) && ($number_item_free > 0)) {
                        if (count($productIds) > $number_item_free) {

                            for ($count = 0; $count < $number_item_free; $count++) {
                                $newProductIds[] = $productIds[$count];
                                $newQtys[] = $qtys[$count];
                            }
                            $productIds = $newProductIds;
                            $qtys = $newQtys;

                        }
                    }


                    $count = 0;
                    foreach ($qtys as $qty) {
                        if ($qty <= 0) {
                            $count++;
                            continue;
                        }

                        $productGiftId = $productIds[$count];
                        $count++;
                        $requestInfo = array();
//                            if ((count($qtys) <= 1) && ($productGiftId == $productId)) {
//                                Mage::getModel('checkout/session')->setData('sameproduct', 1);
//                            } else {
//                                Mage::getModel('checkout/session')->setData('sameproduct', null);
//                            }

                        $giftItem = new Mage_Catalog_Model_Product();
                        $giftItem->load($productGiftId);
                        /* check if gift is not avaiable */
                        if (!$giftItem->isAvailable()) {
                            $message = Mage::helper('promotionalgift')->__($giftItem->getName() . ' is out of stock !');
                            Mage::getSingleton('checkout/session')->addError($message);
                            continue;
                        }
                        /* check if gift is Out of Stock */
                        if (!in_array($giftItem->getTypeId(), array('downloadable', 'virtual'))) {
                            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($giftItem);
                            $qtyStock = $stockItem->getQty();
                            if ($qtyStock <= 0 && !$stockItem->getData('is_in_stock')) {
                                $message = Mage::helper('promotionalgift')->__($giftItem->getName() . ' is out of stock !');
                                Mage::getSingleton('checkout/session')->addError($message);
                                continue;
                            }
                        }

                        /*item has options*/
                        $options=array();
                        if ($giftItem->getOptions()) {
                            $year = date('Y');
                            $month = date('m');
                            $day = date('j');
                            $hour = date('g');
                            $minute = date('i');
                            $day_part = date('a');
                            $options = Mage::helper('core')->decorateArray($giftItem->getOptions());
                            $optionAdds = array();
                            foreach ($options as $option) {
                                if (in_array($option->getType(), array('area', 'field')))
                                    $optionAdds[$option->getOptionId()] = Mage::helper('promotionalgift')->__('Promotional Gift');
                                if ($option->getType() == 'date_time')
                                    $optionAdds[$option->getOptionId()] = array(
                                        'month' => $month,
                                        'day' => $day,
                                        'year' => $year,
                                        'hour' => $hour,
                                        'minute' => $minute,
                                        'day_part' => $day_part
                                    );
                                if ($option->getType() == 'date')
                                    $optionAdds[$option->getOptionId()] = array(
                                        'month' => $month,
                                        'day' => $day,
                                        'year' => $year
                                    );
                                if ($option->getType() == 'time')
                                    $optionAdds[$option->getOptionId()] = array(
                                        'hour' => $hour,
                                        'minute' => $minute,
                                        'day_part' => $day_part
                                    );
                                if (in_array($option->getType(), array('drop_down', 'checkbox', 'multiple', 'radio'))) {
                                    foreach ($option->getValues() as $value) {
                                        $optionAdds[$option->getOptionId()] = $value->getData('option_type_id');
                                        break;
                                    }
                                }
                            }
                            $requestInfo['options'] = $optionAdds;
                        }
                        $requestInfo['product'] = $giftItem->getId();
                        $requestInfo['related_product'] = '';
                        Mage::getModel('checkout/session')->setData('shoppingcart_gift_item', $productGiftId);
                        Mage::getModel('checkout/session')->setData('shoppingcart_gift_item_qty', $qty);
                        //Mage::getModel('checkout/session')->setData('catalog_rule_id',null);
                        Mage::getModel('checkout/session')->setData('shoppingcart_rule_id', $availableShoppingCart->getId());

                        if ($giftItem->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                            $requestInfo['qty'] = $qty;
                            $typeInstance = $giftItem->getTypeInstance(true);
                            $typeInstance->setStoreFilter($giftItem->getStoreId(), $giftItem);

                            $optionCollection = $typeInstance->getOptionsCollection($giftItem);

                            $selectionCollection = $typeInstance->getSelectionsCollection(
                                $typeInstance->getOptionsIds($giftItem),
                                $giftItem
                            );
                            $options = $optionCollection->appendSelections($selectionCollection, false, false);
                            $bundleOptions = array();
                            foreach ($options as $option) {
                                if (!$option->getSelections()) continue;
                                $option_id = $option->getData('option_id');
                                $selections = $option->getData('selections');
                                if ($option->getType() != 'checkbox' && $option->getType() != 'multi') {
                                    foreach ($selections as $selection) {
                                        $bundleOptions[$option_id] = $selection->getData('selection_id');
                                        break;
                                    }
                                } else {
                                    foreach ($selections as $selection) {
                                        $bundleOptions[$option_id] = array($selection->getData('selection_id'));
                                        break;
                                    }
                                }
                            }
                            $requestInfo['product'] = $giftItem->getId();
                            $requestInfo['related_product'] = '';
                            $requestInfo['bundle_option'] = $bundleOptions;
                            $cart = Mage::getModel('checkout/cart');
                            $product = new Mage_Catalog_Model_Product();
                            $product->load($productGiftId);
                            try {
                                $cart->addProduct($product, $requestInfo);
                            } catch (Exception $ex) {
                                // Mage::getSingleton('checkout/session')->addError($ex->getMessage());
                            }
                            $result = $cart->save();
                        } elseif ($giftItem->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
                            $requestInfo['qty'] = $qty;
                            $attributes = $giftItem->getTypeInstance(true)->getConfigurableAttributes($giftItem);
                            $allProducts = $giftItem->getTypeInstance(true)->getUsedProducts(null, $giftItem);
                            foreach ($allProducts as $product) {
                                $productId = $product->getId();
                                // Fix add configurable product gift_King130701
                                $productItem = new Mage_Catalog_Model_Product();
                                $productItem->load($productId);
                                $qtyItem = 0;
                                if (!in_array($productItem->getTypeId(), array('downloadable', 'virtual', 'configurable'))) {
                                    $stockItem = $productItem->getStockItem();
                                    if (!$stockItem->getIsInStock()) {
                                        continue;
                                    } else {
                                        $qtyStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productItem)->getQty();
                                        if (!$productItem->isInStock()) continue;
                                    }
                                    $qtyItem = 1;
                                }
                                $allowAttributes = $giftItem->getTypeInstance(true)
                                    ->getConfigurableAttributes($giftItem);
                                foreach ($allowAttributes as $attribute) {
                                    $productAttribute = $attribute->getProductAttribute();
                                    $productAttributeId = $productAttribute->getId();
                                    $attributeValue = $product->getData($productAttribute->getAttributeCode());
                                    if (!isset($options[$productAttributeId])) {
                                        $options[$productAttributeId] = array();
                                    }

                                    if (!isset($options[$productAttributeId][$attributeValue])) {
                                        $options[$productAttributeId][$attributeValue] = array();
                                    }
                                    $options[$productAttributeId][$attributeValue][] = $productId;
                                }
                            }
                            if ($options) {
                                foreach ($options as $optionId => $keys) {
                                    if (count($options) == '1') {
                                        foreach ($keys as $k1 => $key) {
                                            $requestInfo['super_attribute'] = array($optionId => $k1);
                                            break;
                                        }
                                        break;
                                    } else {
                                        $check = '';
                                        foreach ($keys as $k1 => $key) {
                                            $check = 1;
                                            break;
                                        }
                                        if ($check == '') continue;
                                        if (count($keys) < 1) continue;
                                        $id1 = $optionId;
                                        foreach ($keys as $k1 => $key) {
                                            foreach ($key as $k) {
                                                foreach ($options as $optionId2 => $key2s) {
                                                    if ($optionId2 == $id1) continue;
                                                    foreach ($key2s as $k2 => $key2) {
                                                        foreach ($key2 as $_k2) {
                                                            if ($_k2 == $k) {
                                                                $key1 = $k1;
                                                                $key2 = $k2;
                                                                $id2 = $optionId2;
                                                                $next = 1;
                                                                break;
                                                            }
                                                            if ($next == '1') break;
                                                        }
                                                        if ($next == '1') break;
                                                    }
                                                    if ($next == '1') break;
                                                }
                                                if ($next == '1') break;
                                            }
                                            if ($next == '1') break;
                                        }
                                        $requestInfo['super_attribute'] = array($id1 => $key1, $id2 => $key2);
                                        break;
                                    }
                                }
                                $requestInfo['product'] = $giftItem->getId();
                                $requestInfo['related_product'] = '';
                                $cart = Mage::getModel('checkout/cart');
                                $product = new Mage_Catalog_Model_Product();
                                $product->load($productGiftId);
                                /* check if gift is not enough to add to cart
                               $childProduct = Mage::getModel('catalog/product_type_configurable')
                                         ->getProductByAttributes($requestInfo['super_attribute'], $product);
                               $stockConfigItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($childProduct);
                               $qtyConfigStock = $stockConfigItem->getQty();
                               if($qtyConfigStock && $requestInfo['qty'] > $qtyConfigStock){
                                   $requestInfo['qty'] = $qtyConfigStock;
                                   $giftNotice = Mage::helper('promotionalgift')->__('Gift is not enough to add to cart !');
                                   Mage::getSingleton('checkout/session')->addNotice($giftNotice);
                               }
                                 *
                                 */
                                try {
                                    $cart->addProduct($product, $requestInfo);
                                } catch (Exception $ex) {
                                    // Mage::getSingleton('checkout/session')->addError($ex->getMessage());
                                }

                                $result = $cart->save();
                            }
                        } elseif ($giftItem->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
                            $productGiftId = '';
                            $associatedProducts = $giftItem->getTypeInstance(true)->getAssociatedProducts($giftItem);
                            $hasAssociatedProducts = count($associatedProducts);
                            if ($giftItem->isAvailable() && $hasAssociatedProducts) {
                                foreach ($associatedProducts as $associatedProduct) {
                                    $productGrouped = Mage::getModel('catalog/product')->load($associatedProduct->getId());
                                    if (!in_array($productGrouped->getTypeId(), array('downloadable', 'virtual'))) {
                                        $qtyStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productGrouped)->getQty();
                                        if ($qtyStock <= 0) continue;
                                    }
                                    $productGiftId = $associatedProduct->getId();
                                    break;
                                }
                            }
                            if (!$productGiftId) {
                                Mage::getModel('checkout/session')->setData('shoppingcart_gift_item', null);
                                continue;
                            }
                            Mage::getModel('checkout/session')->setData('shoppingcart_gift_item', $productGiftId);
                            $requestInfo['qty'] = $qty;
                            $cart = Mage::getModel('checkout/cart');
                            $product = new Mage_Catalog_Model_Product();
                            $product->load($productGiftId);
                            try {
                                $cart->addProduct($product, $requestInfo);
                            } catch (Exception $ex) {
                                // Mage::getSingleton('checkout/session')->addError($ex->getMessage());
                            }

                            $result = $cart->save();
                        } else {

                            $requestInfo['qty'] = $qty;
                            $cart = Mage::getModel('checkout/cart');
                            $product = new Mage_Catalog_Model_Product();
                            $product->load($productGiftId);
                            /* check if qtyGift greater than qtyInStock
                            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                            $qtyStock = $stockItem->getQty();
                            if($qtyStock && $requestInfo['qty'] > $qtyStock){
                                $requestInfo['qty'] = $qtyStock;
                                $giftNotice = Mage::helper('promotionalgift')->__('Gift is not enough to add to cart !');
                                Mage::getSingleton('checkout/session')->addNotice($giftNotice);
                            }
                            */
                            try {
                                $cart->addProduct($product, $requestInfo);
                            } catch (Exception $ex) {
                                // Mage::getSingleton('checkout/session')->addError($ex->getMessage());
                            }

                            $result = $cart->save();
                            Mage::getModel('checkout/session')->setData('shoppingcart_gift_item', null);
                        }
                        Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
                        if (!$result->hasError()) {
                            $message = Mage::helper('promotionalgift')->__($giftItem->getName() .
                                ' has been automatically added to your shopping cart as a gift!');
                            Mage::getSingleton('checkout/session')->addSuccess($message);
                        }
                        Mage::getModel('checkout/session')->setData('shoppingcart_gift_item', null);
                    }

                }


            }
        }


    }

    public function customerLogout($observer) {
        //logout xoa gift eden
        $customer = $observer['customer'];
        $quote = Mage::getModel('sales/quote')->loadByCustomer($customer);
        $quoteId = $quote->getId();
        $items = $quote->getAllItems();
        $checkHasGift = 0;
        $qtyRemoved=0;
        foreach ($items as $item) {
            $itemOptions = $item->getOptions();

            foreach ($itemOptions as $option) {
                $oData = $option->getData();
                if ($oData['code'] == 'option_promotionalgift_shoppingcartrule') {
                    $qtyRemoved=$qtyRemoved+$item->getQty();
                    $item->delete();

                    $checkHasGift=1;
                }
                if ($oData['code'] == 'option_promotionalgift_catalogrule') {
                    $qtyRemoved=$qtyRemoved+$item->getQty();
                    $item->delete();
                    $checkHasGift=1;
                }
            }
        }
        if ($checkHasGift==1) {
            $giftCatalogCollection = Mage::getModel('promotionalgift/quote')->getCollection()
                ->addFieldToFilter('quote_id', $quoteId);
            $giftShoppingCollection = Mage::getModel('promotionalgift/shoppingquote')->getCollection()
                ->addFieldToFilter('quote_id', $quoteId);

            foreach ($giftCatalogCollection as $gift) {
                $gift->delete();
            }
            foreach ($giftShoppingCollection as $gift) {
                $gift->delete();
            }
            $oldItemQty= $quote->getItemsQty();
            $quote->setItemsQty($oldItemQty-$qtyRemoved);
            $quote->save();
        }


    }


}
