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
 * Promotionalgift Adminhtml Block
 *
 * @category    Magestore
 * @package     Magestore_Promotionalgift
 * @author      Magestore Developer
 */
class Magestore_Promotionalgift_Block_Adminhtml_Reportcartrule_Dashboard extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('promotionalgift/report/dashboard.phtml');
    }

    protected function _prepareLayout()
    {

        //	$this->setChild('store_switcher',$this->getLayout()->createBlock('adminhtml/store_switcher')->setTemplate('store/switcher.phtml'));
        $this->setChild('gift-order', $this->getLayout()->createBlock('promotionalgift/adminhtml_reportcartrule_giftorder'));
        parent::_prepareLayout();
    }
}