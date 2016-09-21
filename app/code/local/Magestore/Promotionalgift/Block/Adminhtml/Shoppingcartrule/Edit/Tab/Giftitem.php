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
class Magestore_Promotionalgift_Block_Adminhtml_Shoppingcartrule_Edit_Tab_Giftitem extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('giftitemGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        if ($this->getShoppingcartrule() && $this->getShoppingcartrule()->getId()) {
            $this->setDefaultFilter(array('in_giftitems' => 1));
        }
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('*');
        $collection->addFieldToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $collection->addAttributeToFilter('type_id', array('in' => array('simple', 'configurable', 'bundle', 'virtual', 'downloadable')));
        $store = Mage::app()->getStore();
        $allow = Mage::getStoreConfig('promotionalgift/general/allowproductnoinvisible', $store->getId()); //zend_debug::dump($allow);die();
        if (!$allow) {
            $collection->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH);
        } else {
            $productChild = Mage::helper('promotionalgift')->getProductChildId();
            if (count($productChild) > 0) {
                $collection->addAttributeToFilter('entity_id', array('nin' => $productChild));
            }
        }
        $storeId = $this->getRequest()->getParam('store', 0);
        if ($storeId && $storeId != 0) {
            $collection->addStoreFilter($storeId);
        }
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId() == 'in_giftitems') {
            $giftitemIds = $this->_getSelectedGiftitems();
            if (empty($giftitemIds))
                $giftitemIds = 0;
            if ($column->getFilter()->getValue())
                $this->getCollection()->addFieldToFilter('entity_id', array('in' => $giftitemIds));
            elseif ($giftitemIds)
                $this->getCollection()->addFieldToFilter('entity_id', array('nin' => $giftitemIds));
            return $this;
        }
        $field = ($column->getFilterIndex()) ? $column->getFilterIndex() : $column->getIndex();
        if ($field == 'gift_qty') {
            $gifts = $this->getSelectedRelatedGiftitems();
            $cond = $column->getFilter()->getCondition();
            $giftIds = array();
            foreach ($gifts as $giftId => $gift) {
                if ($cond['from'] && $cond['to']) {
                    if ($gift['gift_qty'] >= $cond['from'] && $gift['gift_qty'] <= $cond['to'])
                        $giftIds[] = $giftId;
                } elseif ($cond['from']) {
                    if ($gift['gift_qty'] >= $cond['from'])
                        $giftIds[] = $giftId;
                } elseif ($cond['to']) {
                    if ($gift['gift_qty'] <= $cond['to'])
                        $giftIds[] = $giftId;
                }
            }
            $this->getCollection()->addFieldToFilter('entity_id', array('in' => $giftIds));
            return $this;
        }
        return parent::_addColumnFilterToCollection($column);
    }

    protected function _prepareColumns()
    {
        $currencyCode = Mage::app()->getStore()->getBaseCurrency()->getCode();

        $this->addColumn('in_giftitems', array(
            'header_css_class' => 'a-center',
            'type' => 'checkbox',
            'name' => 'in_giftitems',
            'values' => $this->_getSelectedGiftitems(),
            'align' => 'center',
            'index' => 'entity_id',
            'use_index' => true,
        ));

        $this->addColumn('entity_id', array(
            'header' => Mage::helper('catalog')->__('ID'),
            'sortable' => true,
            'width' => '60',
            'index' => 'entity_id'
        ));

        $this->addColumn('product_name', array(
            'header' => Mage::helper('catalog')->__('Name'),
            'align' => 'left',
            'index' => 'name',
        ));

        $sets = Mage::getResourceModel('eav/entity_attribute_set_collection')
            ->setEntityTypeFilter(Mage::getModel('catalog/product')->getResource()->getTypeId())
            ->load()
            ->toOptionHash();
        $this->addColumn('product_set_name', array(
            'header' => Mage::helper('catalog')->__('Attrib. Set Name'),
            'align' => 'left',
            'index' => 'attribute_set_id',
            'type' => 'options',
            'options' => $sets,
        ));

        $this->addColumn('product_status', array(
            'header' => Mage::helper('catalog')->__('Status'),
            'width' => '90px',
            'index' => 'status',
            'type' => 'options',
            'options' => Mage::getSingleton('catalog/product_status')->getOptionArray(),
        ));

        $this->addColumn('product_visibility', array(
            'header' => Mage::helper('catalog')->__('Visibility'),
            'width' => '90px',
            'index' => 'visibility',
            'type' => 'options',
            'options' => Mage::getSingleton('catalog/product_visibility')->getOptionArray(),
        ));

        $this->addColumn('product_sku', array(
            'header' => Mage::helper('catalog')->__('SKU'),
            'width' => '80px',
            'index' => 'sku'
        ));

        $this->addColumn('product_price', array(
            'header' => Mage::helper('catalog')->__('Price'),
            'type' => 'currency',
            'currency_code' => (string)Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE),
            'index' => 'price'
        ));

        $this->addColumn('gift_qty', array(
            'header' => Mage::helper('promotionalgift')->__('Max Gift Qty'),
            'name' => 'gift_qty',
            'type' => 'number',
            'index' => 'gift_qty',
            'editable' => true,
            'edit_only' => true,
        ));
    }

    // public function getRowUrl($row){
    // return $this->getUrl('affiliateplusadmin/adminhtml_account/edit', array(
    // 'id' 	=> $row->getId(),
    // 'store'	=>$this->getRequest()->getParam('store')
    // ));
    // }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/giftitemGrid', array(
            '_current' => true,
            'id' => $this->getRequest()->getParam('id'),
            'store' => $this->getRequest()->getParam('store')
        ));
    }

    protected function _getSelectedGiftitems()
    {
        $giftItems = $this->getGiftitems();
        if (!is_array($giftItems))
            $giftItems = array_keys($this->getSelectedRelatedGiftitems());
        return $giftItems;
    }

    public function getSelectedRelatedGiftitems()
    {
        $giftItems = array();
        $shoppingcartrule = $this->getShoppingcartrule();
        $giftItemProduct = Mage::getModel('promotionalgift/shoppingcartitem')
            ->load($shoppingcartrule->getId(), 'rule_id');
        $productIds = explode(',', $giftItemProduct->getProductIds());
        $qtyItems = explode(',', $giftItemProduct->getGiftQty());
        $i = 0;
        foreach ($productIds as $productId) {
            $giftitems[$productId] = array('gift_qty' => $qtyItems[$i]);
            $i++;
        }
        return $giftitems;
    }

    /**
     * get Current Shoppingcart Rule
     *
     * @return Magestore_Promotionalgift_Model_Shoppingcartrule
     */
    public function getShoppingcartrule()
    {
        return Mage::getModel('promotionalgift/shoppingcartrule')->load($this->getRequest()->getParam('id'));
    }

    /**
     * get currrent store
     *
     * @return Mage_Core_Model_Store
     */
    public function getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }

}
