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
class Magestore_Promotionalgift_Helper_Cart extends Mage_Checkout_Helper_Cart
{

    /**
     * Retrieve url for add product to cart
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  string
     */
    public function getGiftCatalogRuleProductQty($ruleId, $giftRuleProductQty)
    {
        $catalogItem = Mage::getModel('promotionalgift/catalogitem')
            ->getCollection()
            ->addFieldToFilter('rule_id', $ruleId)
            ->getFirstItem();
        if (empty($giftRuleProductQty[$ruleId])) {
            $productIds = explode(',', $catalogItem->getProductIds());
            $cQtyArray = explode(',', $catalogItem->getGiftQty());
            foreach ($productIds as $key => $productId) {
                $cQtyGift = $cQtyArray[$key];
                $giftRuleProductQty[$ruleId][$productId] = $cQtyGift;
            }
        }
        return $giftRuleProductQty;
    }

    public function getGiftShoppingcartRuleProductQty($ruleId, $giftRuleProductQty)
    {
        $shoppingcartItem = Mage::getModel('promotionalgift/shoppingcartitem')
            ->getCollection()
            ->addFieldToFilter('rule_id', $ruleId)
            ->getFirstItem();
        if (empty($giftRuleProductQty[$ruleId])) {
            $productIds = explode(',', $shoppingcartItem->getProductIds());
            $sQtyArray = explode(',', $shoppingcartItem->getGiftQty());
            foreach ($productIds as $key => $productId) {
                $sQtyGift = $sQtyArray[$key];
                $giftRuleProductQty[$ruleId][$productId] = $sQtyGift;
            }
        }
        return $giftRuleProductQty;
    }

    public function getAddUrl($product, $additional = array())
    {
        $continueUrl = Mage::helper('core')->urlEncode($this->getCurrentUrl());
        $urlParamName = Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED;

        $routeParams = array(
            $urlParamName => $continueUrl,
            'product' => $product->getEntityId()
        );

        if (!empty($additional)) {
            $routeParams = array_merge($routeParams, $additional);
        }

        if ($product->hasUrlDataObject()) {
            $routeParams['_store'] = $product->getUrlDataObject()->getStoreId();
            $routeParams['_store_to_url'] = true;
        }

        if ($this->_getRequest()->getRouteName() == 'checkout' && $this->_getRequest()->getControllerName() == 'cart') {
            $routeParams['in_cart'] = 1;
        }

        if (in_array($product->getTypeId(), array('grouped', 'configurable')) && $this->getFullActionName() != 'catalog_product_view'
        ) {
            if (!isset($routeParams['_query'])) {
                $routeParams['_query'] = array();
            }
            $routeParams['_query']['options'] = 'cart';
        }

        return $this->_getUrl('checkout/cart/add', $routeParams);
    }

    public function getFullActionName($delimiter = '_')
    {
        $request = Mage::app()->getRequest();
        return $request->getRequestedRouteName() . $delimiter .
        $request->getRequestedControllerName() . $delimiter .
        $request->getRequestedActionName();
    }

    public function checkOutstock($product)
    {
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        $qtyStock = $stockItem->getQty();
        if ($stockItem->getManageStock()) {
            if ($qtyStock <= 0 && !$stockItem->getData('is_in_stock')) {
                return true;
            }
        }
        return false;
    }

    public function autoAddCatalogGift($catalogItem, $productId, $availableRule, $itemId)
    {
        //Mage::register('autoaddcatalog',1);
        $websiteId = Mage::app()->getStore()->getWebsiteId();
        $productIds = $catalogItem->getProductIds();
        $productIds = explode(',', $productIds);
        $qtys = $catalogItem->getGiftQty();
        $qtys = explode(',', $qtys);
        $count = 0;
        $limitNumberItem = 0;
        $numberItemFree = $availableRule->getNumberItemFree();
        foreach ($qtys as $qty) {
            if ($qty <= 0) {
                $count++;
                continue;
            }
            if ($limitNumberItem == $numberItemFree) break;
            $productGiftId = $productIds[$count];
            $count++;
            $requestInfo = array();

            $requestInfo['qty'] = $qty;
            if ((count($qtys) <= 1) && ($productGiftId == $productId)) {
                Mage::getModel('checkout/session')->setData('sameproduct', 1);
            } else {
                Mage::getModel('checkout/session')->setData('sameproduct', null);
            }
            $giftItem = new Mage_Catalog_Model_Product();
            $giftItem->load($productGiftId);
            /* check if gift is not in current Website */
            if (!in_array($websiteId, $giftItem->getWebsiteIds()) || !$giftItem->isAvailable()) {
                continue;
            }
            //vietdq fix bug getBackEnd
            /* check if gift is Out of Stock */
            if (!in_array($giftItem->getTypeId(), array('downloadable', 'virtual'))) {
                $is_outstock = Mage::helper('promotionalgift/cart')->checkOutstock($giftItem);
                if ($is_outstock) {
                    $message = Mage::helper('promotionalgift')->__($giftItem->getName() . ' is out of stock !');
                    Mage::getSingleton('checkout/session')->addError($message);
                    continue;
                }
            }

            Mage::getModel('checkout/session')->setData('free_gift_item', $productGiftId);
            Mage::getModel('checkout/session')->setData('free_gift_item_qty', $qty);
            Mage::getModel('checkout/session')->setData('catalog_rule_id', $availableRule->getId());
            Mage::getModel('checkout/session')->setData('catalog_rule_product_id', $availableRule->getId());
            Mage::getModel('checkout/session')->setData('product_parent', $itemId);
            Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
            $options=array();
            if ($giftItem->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                $typeInstance = $giftItem->getTypeInstance(true);
                $typeInstance->setStoreFilter($giftItem->getStoreId(), $giftItem);

                $optionCollection = $typeInstance->getOptionsCollection($giftItem);

                $selectionCollection = $typeInstance->getSelectionsCollection(
                    $typeInstance->getOptionsIds($giftItem), $giftItem
                );
                $options = $optionCollection->appendSelections($selectionCollection, false, false);
                $bundleOptions = array();
                foreach ($options as $option) {
                    if (!$option->getSelections())
                        continue;
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
                            if (!$productItem->isInStock())
                                continue;
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
                            if ($check == '')
                                continue;
                            if (count($keys) < 1)
                                continue;
                            $id1 = $optionId;
                            foreach ($keys as $k1 => $key) {
                                foreach ($key as $k) {
                                    foreach ($options as $optionId2 => $key2s) {
                                        if ($optionId2 == $id1)
                                            continue;
                                        foreach ($key2s as $k2 => $key2) {
                                            foreach ($key2 as $_k2) {
                                                if ($_k2 == $k) {
                                                    $key1 = $k1;
                                                    $key2 = $k2;
                                                    $id2 = $optionId2;
                                                    $next = 1;
                                                    break;
                                                }
                                                if ($next == '1')
                                                    break;
                                            }
                                            if ($next == '1')
                                                break;
                                        }
                                        if ($next == '1')
                                            break;
                                    }
                                    if ($next == '1')
                                        break;
                                }
                                if ($next == '1')
                                    break;
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
                    $childProduct = Mage::getModel('catalog/product_type_configurable')
                        ->getProductByAttributes($requestInfo['super_attribute'], $product);
                    $stockConfigItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($childProduct);
                    $currentQtyInCart = Mage::helper('promotionalgift')->checkItemInCart($childProduct);
                    if ($stockConfigItem->getManageStock()) {
                        $qtyConfigStock = $stockConfigItem->getQty() - $currentQtyInCart;
                        if (isset($qtyConfigStock) && $requestInfo['qty'] > $qtyConfigStock) {
                            $requestInfo['qty'] = $qtyConfigStock;
                            $giftNotice = Mage::helper('promotionalgift')->__('Qty. in stock of gift %s is not enough to add to cart.', $product->getName());
                            Mage::getSingleton('checkout/session')->addNotice($giftNotice);
                        }
                    }
                    try {
                        $cart->addProduct($product, $requestInfo);
                    } catch (Exception $ex) {

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
                            if ($qtyStock <= 0)
                                continue;
                        }
                        $productGiftId = $associatedProduct->getId();
                        break;
                    }
                }
                if (!$productGiftId) {
                    Mage::getModel('checkout/session')->setData('free_gift_item', null);
                    continue;
                }
                Mage::getModel('checkout/session')->setData('free_gift_item', $productGiftId);
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
                $cart = Mage::getModel('checkout/cart');
                $product = new Mage_Catalog_Model_Product();
                $product->load($productGiftId);
                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
                $currentQtyInCart = Mage::helper('promotionalgift')->checkItemInCart($product);
                if ($stockItem->getManageStock()) {
                    $qtyStock = $stockItem->getQty() - $currentQtyInCart;
                    if (isset($qtyStock) && $requestInfo['qty'] > $qtyStock) {
                        $requestInfo['qty'] = $qtyStock;
                        $giftNotice = Mage::helper('promotionalgift')->__('Qty. in stock of gift %s is not enough to add to cart.', $product->getName());
                        Mage::getSingleton('checkout/session')->addNotice($giftNotice);
                    }
                }
                try {
                    $cart->addProduct($product, $requestInfo);
                } catch (Exception $ex) {
                    // Mage::getSingleton('checkout/session')->addError($ex->getMessage());
                }

                $result = $cart->save();
            }
            Mage::getSingleton('checkout/session')->setCartWasUpdated(true);
            if (!$result->hasError()) {
                $message = Mage::helper('promotionalgift')->__('%s has been automatically added to your shopping cart as a gift!', $giftItem->getName());
                Mage::getSingleton('checkout/session')->addSuccess($message);
            }
            $limitNumberItem++;
            Mage::getModel('checkout/session')->setData('free_gift_item', null);
            Mage::getModel('checkout/session')->setData('catalog_rule_id', null);
        }
        Mage::getModel('checkout/session')->setData('free_gift_item', null);
        Mage::getModel('checkout/session')->setData('catalog_rule_id', null);
    }

    public function addProduct($product, $requestInfo)
    {
        //variable $j to check add gift success or not
        $j = 0;
        $productId = $product->getId();
        /* item has options */
        if ($product->getOptions()) {
            $year = date('Y');
            $month = date('m');
            $day = date('j');
            $hour = date('g');
            $minute = date('i');
            $day_part = date('a');
            $options = Mage::helper('core')->decorateArray($product->getOptions());
            $optionAdds = array();
            foreach ($options as $option) {
                if ($option->getData('is_require') != '1')
                    continue;
                if (in_array($option->getType(), array('area', 'field'))) {
                    $optionAdds[$option->getOptionId()] = Mage::helper('promotionalgift')->__('Promotional Gift');
                }
                if ($option->getType() == 'date_time') {
                    $optionAdds[$option->getOptionId()] = array(
                        'month' => $month,
                        'day' => $day,
                        'year' => $year,
                        'hour' => $hour,
                        'minute' => $minute,
                        'day_part' => $day_part
                    );
                }
                if ($option->getType() == 'date') {
                    $optionAdds[$option->getOptionId()] = array(
                        'month' => $month,
                        'day' => $day,
                        'year' => $year
                    );
                }
                if ($option->getType() == 'time') {
                    $optionAdds[$option->getOptionId()] = array(
                        'hour' => $hour,
                        'minute' => $minute,
                        'day_part' => $day_part
                    );
                }
                if (in_array($option->getType(), array('drop_down', 'checkbox', 'multiple', 'radio'))) {
                    foreach ($option->getValues() as $value) {
                        $optionAdds[$option->getOptionId()] = $value->getData('option_type_id');
                        break;
                    }
                }
            }
            $requestInfo['options'] = $optionAdds;
        }

        /* Add product special type */
        //BUNDLE TYPE
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $typeInstance = $product->getTypeInstance(true);
            $typeInstance->setStoreFilter($product->getStoreId(), $product);

            $optionCollection = $typeInstance->getOptionsCollection($product);

            $selectionCollection = $typeInstance->getSelectionsCollection(
                $typeInstance->getOptionsIds($product), $product
            );
            Mage::getModel('checkout/session')->setData('product_bundle', $productId);
            $requestInfo['related_product'] = '';
            $cart = Mage::getModel('checkout/cart');
            $productBundle = new Mage_Catalog_Model_Product();
            $productBundle->load($productId);
            try {
                $cart->addProduct($productBundle, $requestInfo);
            } catch (Exception $ex) {
                Mage::getSingleton('core/session')->addError($ex->getMessage());
            }
            $result = $cart->save();
            $j++;
        } //CONFIGURABLE TYPE
        elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $productGiftId = $productId;
            $attributes = $product->getTypeInstance(true)->getConfigurableAttributes($product);
            $allProducts = $product->getTypeInstance(true)
                ->getUsedProducts(null, $product);
            foreach ($allProducts as $productConfig) {
                $productId = $productConfig->getId();
                $allowAttributes = $product->getTypeInstance(true)
                    ->getConfigurableAttributes($product);
                foreach ($allowAttributes as $attribute) {
                    $productAttribute = $attribute->getProductAttribute();
                    $productAttributeId = $productAttribute->getId();
                    $attributeValue = $productConfig->getData($productAttribute->getAttributeCode());
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
                            //$requestInfo['super_attribute'] = array($optionId=>$k1);
                            break;
                        }
                        break;
                    } else {
                        $check = '';
                        foreach ($keys as $k1 => $key) {
                            $check = 1;
                            break;
                        }
                        if ($check == '')
                            continue;
                        if (count($keys) < 1)
                            continue;
                        $id1 = $optionId;
                        foreach ($keys as $k1 => $key) {
                            foreach ($key as $k) {
                                foreach ($options as $optionId2 => $key2s) {
                                    if ($optionId2 == $id1)
                                        continue;
                                    foreach ($key2s as $k2 => $key2) {
                                        foreach ($key2 as $_k2) {
                                            if ($_k2 == $k) {
                                                $key1 = $k1;
                                                $key2 = $k2;
                                                $id2 = $optionId2;
                                                $next = 1;
                                                break;
                                            }
                                            if ($next == '1')
                                                break;
                                        }
                                        if ($next == '1')
                                            break;
                                    }
                                    if ($next == '1')
                                        break;
                                }
                                if ($next == '1')
                                    break;
                            }
                            if ($next == '1')
                                break;
                        }
                        //$requestInfo['super_attribute'] = array($id1=>$key1,$id2=>$key2);
                        break;
                    }
                }
                $requestInfo['related_product'] = '';
                $cart = Mage::getModel('checkout/cart');
                $productConfig = new Mage_Catalog_Model_Product();
                $productConfig->load($productGiftId);
                /* check if gift is not enough to add to cart */
                $childProduct = Mage::getModel('catalog/product_type_configurable')
                    ->getProductByAttributes($requestInfo['super_attribute'], $productConfig);
                $stockConfigItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($childProduct);
                $currentQtyInCart = Mage::helper('promotionalgift')->checkItemInCart($productConfig);
                if ($stockConfigItem->getManageStock()) {
                    $qtyConfigStock = $stockConfigItem->getQty() - $currentQtyInCart;
                    if (isset($qtyConfigStock) && $requestInfo['qty'] > $qtyConfigStock) {
                        $requestInfo['qty'] = $qtyConfigStock;
                        $giftNotice = Mage::helper('promotionalgift')->__('Qty. in stock of gift %s is not enough to add to cart.', $product->getName());
                        Mage::getSingleton('core/session')->addNotice($giftNotice);
                    }
                }
                try {
                    $cart->addProduct($productConfig, $requestInfo);
                } catch (Exception $ex) {
                    Mage::getSingleton('core/session')->addError($ex->getMessage());
                }
                $result = $cart->save();
            }
            $j++;
        } //GROUP TYPE
        elseif ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
            $associatedProducts = $product->getTypeInstance(true)
                ->getAssociatedProducts($product);
            $hasAssociatedProducts = count($associatedProducts);
            if ($product->isAvailable() && $hasAssociatedProducts) {
                $productGroup = array();
                $productGroup[] = $product->getId();
                $productGroup[] = $requestInfo['super_group'];
                Mage::getModel('checkout/session')->setData('promotionalgift_shoppingcart_grouped', serialize($productGroup));
                $requestInfo['product'] = $productId;
                $requestInfo["related_product"] = "";
                $cart = Mage::getModel('checkout/cart');
                $productGroup = new Mage_Catalog_Model_Product();
                $productGroup->load($productId);
                try {
                    $cart->addProduct($productGroup, $requestInfo);
                } catch (Exception $ex) {
                    Mage::getSingleton('core/session')->addError($ex->getMessage());
                }
                $result = $cart->save();
            }
            $j++;
        }
        /* End add product special type */
        //SIMPLE TYPE
        else {
//            if($product->getOptions()){
//                
//            }
            /* check if gift is not enough to add to cart */
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            $currentQtyInCart = Mage::helper('promotionalgift')->checkItemInCart($product);
            if ($stockItem->getManageStock()) {
                $qtyStock = $stockItem->getQty() - $currentQtyInCart;
                if (isset($qtyStock) && $requestInfo['qty'] > $qtyStock) {
                    $requestInfo['qty'] = $qtyStock;
                    $giftNotice = Mage::helper('promotionalgift')->__('Qty. in stock of gift %s is not enough to add to cart.', $product->getName());
                    Mage::getSingleton('core/session')->addNotice($giftNotice);
                }
            }
            try {
                $cart = Mage::getModel('checkout/cart');
                $cart->addProduct($product, $requestInfo);
            } catch (Exception $ex) {
                Mage::getSingleton('core/session')->addError($ex->getMessage());
            }
            $result = $cart->save();
            $j++;
        }
        if ($j > 0) {
            if (Mage::getModel('checkout/session')->getData('promotionalgift_shoppingcart_rule_id') &&
                !Mage::getModel('checkout/session')->getData('promotionalgift_shoppingcart_rule_used')
            ) {
                Mage::getModel('checkout/session')->setData('promotionalgift_shoppingcart_rule_used', true);
            }
        }

        if (!$result->hasError()) {
            $message = Mage::helper('promotionalgift')->__('%s was added to your shopping cart as a gift', $product->getName());
            Mage::getModel('core/session')->addSuccess($message);
            return 'success';
        } else {
            $message = Mage::helper('promotionalgift')->__('Cannot add the item to shopping cart.');
            Mage::getModel('core/session')->addSuccess($message);
            return 'failed';
        }
    }

}
