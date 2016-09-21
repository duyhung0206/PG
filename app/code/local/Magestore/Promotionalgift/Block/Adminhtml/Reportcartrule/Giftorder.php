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
class Magestore_Promotionalgift_Block_Adminhtml_Reportcartrule_Giftorder extends Magestore_Promotionalgift_Block_Adminhtml_Reportcartrule_Graph
{
    public function __construct()
    {
        $this->_google_chart_params = array(
            'cht' => 'lc',
            'chf' => 'bg,s,f4f4f4|c,lg,90,ffffff,0.1,ededed,0',
            'chdl' => $this->__('Gift value') . '|' . $this->__('Order total'),
            'chco' => '2424ff,db4814',
            'chxt' => 'x,r,r',
            'chxlexpend' => '|2:|||' . $this->__('# Money ($)')
        );

        $this->setHtmlId('giftorder');
        parent::__construct();
    }

    protected function _prepareData()
    {
        $this->setDataHelperName('promotionalgift/report_giftorder');
        $this->getDataHelper()->setParam('store', $this->getRequest()->getParam('store'));
        $data = $this->setDataRows(array('gift_order', 'order_value'));
        $this->_axisMaps = array(
            'x' => 'range',
            'y' => 'order_value'
        );
        parent::_prepareData();
    }

    public function getCommentContent()
    {
        return $this->__('This graph shows the gift value and order total of all sales over time.');
    }
}