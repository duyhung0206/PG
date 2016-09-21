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
class Magestore_Promotionalgift_Block_Adminhtml_Shoppingcartrule_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('shoppingcartruleGrid');
        $this->setDefaultSort('rule_id');
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
        $collection = Mage::getModel('promotionalgift/shoppingcartrule')->getCollection();
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
        $this->addColumn('rule_id', array(
            'header' => Mage::helper('promotionalgift')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'rule_id',
        ));

        $this->addColumn('name', array(
            'header' => Mage::helper('promotionalgift')->__('Rule Name'),
            'align' => 'left',
            'index' => 'name',
        ));

        $this->addColumn('coupon_code', array(
            'header' => Mage::helper('promotionalgift')->__('Coupon Code'),
            'align' => 'left',
            'index' => 'coupon_code',
        ));

        $this->addColumn('from_date', array(
            'header' => Mage::helper('promotionalgift')->__('Start Date'),
            'align' => 'left',
            'width' => '120px',
            'type' => 'date',
            'index' => 'from_date',
            'filter_condition_callback' => array($this, 'filterFromDate')
        ));

        $this->addColumn('to_date', array(
            'header' => Mage::helper('promotionalgift')->__('End Date'),
            'align' => 'left',
            'width' => '120px',
            'type' => 'date',
            'default' => '--',
            'index' => 'to_date',
            'filter_condition_callback' => array($this, 'filterToDate')
        ));

        $this->addColumn('status', array(
            'header' => Mage::helper('promotionalgift')->__('Status'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'status',
            'type' => 'options',
            'options' => array(
                1 => 'Enabled',
                2 => 'Disabled',
            ),
        ));

        $this->addColumn('priority', array(
            'header' => Mage::helper('promotionalgift')->__('Priority'),
            'align' => 'left',
            'width' => '80px',
            'index' => 'priority',
        ));

        $this->addColumn('action', array(
            'header' => Mage::helper('promotionalgift')->__('Action'),
            'width' => '100',
            'type' => 'action',
            'getter' => 'getId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('promotionalgift')->__('Edit'),
                    'url' => array('base' => '*/*/edit'),
                    'field' => 'id'
                )),
            'filter' => false,
            'sortable' => false,
            'index' => 'stores',
            'is_system' => true,
        ));

        // $this->addExportType('*/*/exportCsv', Mage::helper('promotionalgift')->__('CSV'));
        // $this->addExportType('*/*/exportXml', Mage::helper('promotionalgift')->__('XML'));

        return parent::_prepareColumns();
    }

    /**
     * prepare mass action for this grid
     *
     * @return Magestore_Promotionalgift_Block_Adminhtml_Promotionalgift_Grid
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('rule_id');
        $this->getMassactionBlock()->setFormFieldName('rule');

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => Mage::helper('promotionalgift')->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => Mage::helper('promotionalgift')->__('Are you sure?')
        ));
        $statuses = Mage::getSingleton('promotionalgift/status')->getOptionArray();
        $this->getMassactionBlock()->addItem('status', array(
            'label' => Mage::helper('promotionalgift')->__('Change status'),
            'url' => $this->getUrl('*/*/massStatus', array('_current' => true)),
            'additional' => array(
                'visibility' => array(
                    'name' => 'status',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => Mage::helper('promotionalgift')->__('Status'),
                    'values' => $statuses
                ))
        ));
        return $this;
    }

    /**
     * get url for each row in grid
     *
     * @return string
     */
    public function filterFromDate($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }
        if ($value['from']) {
            $from = date('Y-m-d', strtotime($value['orig_from']));
            $collection->addFieldToFilter('from_date', array('gteq' => $from));
        }
        if ($value['to']) {
            $to = date('Y-m-d', strtotime($value['orig_to']));
            $to .= ' 23:59:59';
            $collection->addFieldToFilter('from_date', array('lteq' => $to));
        }
    }

    public function filterToDate($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }
        if ($value['from']) {
            $from = date('Y-m-d', strtotime($value['orig_from']));
            $collection->addFieldToFilter('to_date', array('gteq' => $from));
        }
        if ($value['to']) {
            $to = date('Y-m-d', strtotime($value['orig_to']));
            $to .= ' 23:59:59';
            $collection->addFieldToFilter('to_date', array('lteq' => $to));
        }
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

}