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
class Magestore_Promotionalgift_Block_Adminhtml_Banner_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('bannerGrid');
        $this->setDefaultSort('banner_id');
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
        $collection = Mage::getModel('promotionalgift/banner')->getCollection();
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
        $this->addColumn('banner_id', array(
            'header' => Mage::helper('promotionalgift')->__('ID'),
            'align' => 'right',
            'width' => '50px',
            'index' => 'banner_id',
        ));

        $this->addColumn('name', array(
            'header' => Mage::helper('promotionalgift')->__('Ad Name'),
            'align' => 'left',
            'index' => 'name',
        ));

        $this->addColumn('cmsblock', array(
            'header' => Mage::helper('promotionalgift')->__('CMS Block'),
            'align' => 'left',
            'index' => 'cmsblock',
            'renderer' => 'Magestore_Promotionalgift_Block_Adminhtml_Banner_Edit_Tab_Renderer_Staticblock',
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

        $this->addColumn('action',
            array(
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

        return parent::_prepareColumns();
    }
//    protected function _prepareMassaction()
//     {
//         $this->setMassactionIdField('banner_id');
//         $this->getMassactionBlock()->setFormFieldName('banner');
//
//         $this->getMassactionBlock()->addItem('delete', array(
//             'label'        => Mage::helper('promotionalgift')->__('Delete'),
//             'url'        => $this->getUrl('*/*/massDelete'),
//             'confirm'    => Mage::helper('promotionalgift')->__('Are you sure?')
//         ));
//         $statuses = Mage::getSingleton('promotionalgift/status')->getOptionArray();
//         $this->getMassactionBlock()->addItem('status', array(
//             'label'=> Mage::helper('promotionalgift')->__('Change status'),
//             'url'    => $this->getUrl('*/*/massStatus', array('_current'=>true)),
//             'additional' => array(
//                 'visibility' => array(
//                     'name'    => 'status',
//                     'type'    => 'select',
//                     'class'    => 'required-entry',
//                     'label'    => Mage::helper('promotionalgift')->__('Status'),
//                     'values'=> $statuses
//                 ))
//         ));
//         return $this;
//     }
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}