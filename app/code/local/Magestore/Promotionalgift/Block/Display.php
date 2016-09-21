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
class Magestore_Promotionalgift_Block_Display extends Mage_Catalog_Block_Product_List
{

    const RELATED = 0;
    const UP_SELL = 1;
    const CROSS_SELL = 2;

    public function _prepareLayout()
    {
        $this->setTemplate('promotionalgift/display.phtml');
        // $this->getLayout()->getBlock('head')->addCss('css/magestore/ajaxcart/style.css');
        parent::_prepareLayout();
    }

    /**
     *
     * @return max number of product list
     */
    public function getNumberProductDisplay()
    {
        $number = 3; //Mage::getStoreConfig('ajaxcart/confirmation/maximum');
        return $number;
    }

    /**
     * @return product data
     */
    public function getProduct()
    {
        if (!$this->hasData('product')) {
            $product = Mage::getModel('catalog/product')->load(18);
            $this->setData('product', $product);
            return $product;
        }
        return $this->getData('product');
    }

    /**
     * set product for block
     *
     * @param mixed $product
     * @return Magestore_Ajaxcart_Block_Display
     */
    public function setProduct($product)
    {
        if (is_object($product)) {
            $this->setData('product', $product);
        } else {
            $product = Mage::getModel('catalog/product')->load($product);
            $this->setData('product', $product);
        }
        return $this;
    }

    /**
     *
     * @return related product collection
     */
    public function relatedProduct()
    {
        $product = $this->getData('product');
        $itemCollection = $product->getRelatedProductCollection()
            ->addAttributeToSelect('required_options')
            ->addAttributeToSort('position', Varien_Db_Select::SQL_ASC)
            ->addStoreFilter();

        if (Mage::helper('catalog')->isModuleEnabled('Mage_Checkout')) {
            Mage::getResourceSingleton('checkout/cart')->addExcludeProductFilter($itemCollection, Mage::getSingleton('checkout/session')->getQuoteId()
            );
            $this->_addProductAttributesAndPrices($itemCollection);
        }
//        Mage::getSingleton('catalog/product_status')->addSaleableFilterToCollection($this->_itemCollection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($itemCollection);

        $itemCollection->setPageSize(intval($this->getNumberProductDisplay()));

        $itemCollection->load();

        foreach ($itemCollection as $products) {
            $products->setDoNotUseCategoryId(true);
        }
        return $itemCollection->getItems();
    }

    /**
     *
     * @return up-sell product collection
     */
    public function upsellProduct()
    {
        $product = $this->getData('product');
        $itemCollection = $product->getUpSellProductCollection()
            ->addAttributeToSort('position', Varien_Db_Select::SQL_ASC)
            ->addStoreFilter();
        if (Mage::helper('catalog')->isModuleEnabled('Mage_Checkout')) {
            Mage::getResourceSingleton('checkout/cart')->addExcludeProductFilter($itemCollection, Mage::getSingleton('checkout/session')->getQuoteId()
            );

            $this->_addProductAttributesAndPrices($itemCollection);
        }
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($itemCollection);

        $itemCollection->setPageSize(intval($this->getNumberProductDisplay()));

        $itemCollection->load();

        foreach ($itemCollection as $item) {
            $item->setDoNotUseCategoryId(true);
        }

        return $itemCollection->getItems();
    }

    /**
     *
     * @return cross-sell product collection
     */
    public function crosssellProduct()
    {
        $product = $this->getData('product');
        $itemCollection = $product->getCrossSellProductCollection()
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addAttributeToSort('position', 'asc')
            ->addStoreFilter();
        Mage::getSingleton('catalog/product_visibility')->addVisibleInCatalogFilterToCollection($itemCollection);

        $itemCollection->setPageSize(intval($this->getNumberProductDisplay()));

        $itemCollection->load();

        foreach ($itemCollection as $products) {
            $products->setDoNotUseCategoryId(true);
        }

        return $itemCollection->getItems();
    }

    /**
     *
     * @return type product collection
     */
    public function getListProduct()
    {
        $product = $this->getData('product');

        $releatedProductIds = array();
        $upsellProductIds = array();
        $crosssellProductIds = array();
        $allProductIds = array();

        // if (Mage::getStoreConfig('ajaxcart/confirmation/related')) {
        // $releatedProductIds = $product->getRelatedProductIds();
        // $allProductIds = array_merge($allProductIds, $releatedProductIds);
        // }
        // if (Mage::getStoreConfig('ajaxcart/confirmation/up_sell')) {
        // $upsellProductIds = $product->getUpSellProductIds();
        // $allProductIds = array_merge($allProductIds, $upsellProductIds);
        // }
        // if (Mage::getStoreConfig('ajaxcart/confirmation/cross_sell')) {
        // $crosssellProductIds = $product->getCrossSellProductIds();
        // $allProductIds = array_merge($allProductIds, $crosssellProductIds);
        // }

        $allProductIds = array_unique($allProductIds);
        $limit = (int)$this->getNumberProductDisplay();
        if (!$limit) {
            $limit = count($allProductIds);
        }
        $productCollection = Mage::getResourceModel('catalog/product_collection');
        $productCollection->getSelect()
            ->limit($limit)
            ->where('e.entity_id IN (?)', $allProductIds)
            ->group('e.entity_id');
        $productCollection->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents();
        Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($productCollection);
        Mage::getSingleton('catalog/product_visibility')->addVisibleInSearchFilterToCollection($productCollection);
        $productCollection->addUrlRewrite(0);
        return $productCollection;
    }

    public function stripTags($data, $allowableTags = null, $allowHtmlEntities = false)
    {
        $result = strip_tags($data, $allowableTags);
        return $allowHtmlEntities ? $this->escapeHtml($result, $allowableTags) : $result;
    }

    public function escapeHtml($data, $allowedTags = null)
    {
        if (is_array($data)) {
            $result = array();
            foreach ($data as $item) {
                $result[] = $this->escapeHtml($item);
            }
        } else {
            // process single item
            if (strlen($data)) {
                if (is_array($allowedTags) and !empty($allowedTags)) {
                    $allowed = implode('|', $allowedTags);
                    $result = preg_replace('/<([\/\s\r\n]*)(' . $allowed . ')([\/\s\r\n]*)>/si', '##$1$2$3##', $data);
                    $result = htmlspecialchars($result);
                    $result = preg_replace('/##([\/\s\r\n]*)(' . $allowed . ')([\/\s\r\n]*)##/si', '<$1$2$3>', $result);
                } else {
                    $result = htmlspecialchars($data);
                }
            } else {
                $result = $data;
            }
        }
        return $result;
    }

}
