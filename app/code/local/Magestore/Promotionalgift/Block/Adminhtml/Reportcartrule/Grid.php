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
 * Promotionalgift Grid Block
 *
 * @category    Magestore
 * @package     Magestore_Promotionalgift
 * @author      Magestore Developer
 */
class Magestore_Promotionalgift_Block_Adminhtml_Reportcartrule_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('promotionalgiftGrid');
        $this->setDefaultSort('sale_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * prepare collection for block to display
     *
     * @return Magestore_Promotionalgift_Block_Adminhtml_Promotionalgift_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('promotionalgift/sale')->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * prepare columns for this grid
     *
     * @return Magestore_Promotionalgift_Block_Adminhtml_Promotionalgift_Grid
     */
    protected function _prepareColumns()
    {
        $currencyCode = Mage::app()->getStore()->getBaseCurrency()->getCode();
        $this->addColumn('sale_id', array(
            'header' => Mage::helper('promotionalgift')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'type' => 'text',
            'index' => 'sale_id',
        ));

        $this->addColumn('order_increment_id', array(
            'header' => Mage::helper('promotionalgift')->__('Order ID'),
            'align' => 'left',
            'width' => '50px',
            'type' => 'text',
            'index' => 'order_increment_id',
            'renderer' => 'promotionalgift/adminhtml_reportcartrule_renderer_order',
        ));
        //$store = $this->_getStore();
        $this->addColumn('order_total', array(
            'header' => Mage::helper('promotionalgift')->__('Order Value'),
            'width' => '50px',
            'type' => 'price',
            'index' => 'order_total',
            'currency_code' => $currencyCode,
        ));
        $this->addColumn('product_names', array(
            'header' => Mage::helper('promotionalgift')->__('Promotional Gifts Attached'),
            //'width'     => '100px',
            'index' => 'product_names',
            'renderer' => 'promotionalgift/adminhtml_reportcartrule_renderer_product',
        ));
        $this->addColumn('gift_total', array(
            'header' => Mage::helper('promotionalgift')->__('Gift Value'),
            'width' => '80px',
            'type' => 'price',
            'index' => 'gift_total',
            'currency_code' => $currencyCode,
        ));

        $this->addColumn('created_at', array(
            'header' => Mage::helper('promotionalgift')->__('Created Time'),
            'width' => '100px',
            'type' => 'datetime',
            'index' => 'created_at',
        ));
        $this->addColumn('order_status', array(
            'header' => Mage::helper('promotionalgift')->__('Status'),
            'align' => 'left',
            'width' => '50px',
            'index' => 'order_status',
            'type' => 'options',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
        ));


        // $this->addExportType('*/*/exportCsv', Mage::helper('promotionalgift')->__('CSV'));
        // $this->addExportType('*/*/exportXml', Mage::helper('promotionalgift')->__('XML'));

        return parent::_prepareColumns();
    }

}