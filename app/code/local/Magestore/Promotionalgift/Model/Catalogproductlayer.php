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
class Magestore_Promotionalgift_Model_Catalogproductlayer extends Mage_Catalog_Model_Layer
{

    public function getProductCollection()
    {
        if (isset($this->_productCollections[$this->getCurrentCategory()->getId()])) {
            $collection = $this->_productCollections[$this->getCurrentCategory()->getId()];
        } else {
            $collection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('entity_id', array('in' => $this->getProductIds()));
            $this->prepareProductCollection($collection);
            $this->_productCollections[$this->getCurrentCategory()->getId()] = $collection;
        }

        // Zend_Debug::dump($collection->getSelect()->__toString());die();           
        return $collection;
    }

    public function getProductIds()
    {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        // $installer = Mage::getModel('core/resource_setup');
        $catalogId = Mage::app()->getRequest()->getParam('catalogrule');
        $productIds = array();
        if ($catalogId) {
            $collection_query = '';
            $collection_query = 'SELECT `product_id` FROM '
                . $resource->getTableName("promotionalgift/catalogproduct") .
                ' WHERE `rule_id` = ' . $catalogId . ';';
            $catalogProductsCollection = $readConnection->fetchAll($collection_query);
            foreach ($catalogProductsCollection as $catalogProduct) {
                if (!in_array($catalogProduct['product_id'], $productIds)) {
                    $productIds[] = $catalogProduct['product_id'];
                }
            }
        } else {
            $availableRules = Mage::getModel('promotionalgift/catalogrule')->getAvailableRule();
            if ($availableRules) {
                foreach ($availableRules as $availableRule) {
                    $collection_query = '';
                    $collection_query = 'SELECT `product_id` FROM '
                        . $resource->getTableName("promotionalgift/catalogproduct") .
                        ' WHERE `rule_id` = ' . $availableRule->getRuleId() . ';';
                    $catalogProductsCollection = $readConnection->fetchAll($collection_query);
                    foreach ($catalogProductsCollection as $catalogProduct) {
                        if (!in_array($catalogProduct['product_id'], $productIds)) {
                            $productIds[] = $catalogProduct['product_id'];
                        }
                    }
                }
            }
        }
        return $productIds;
    }

}