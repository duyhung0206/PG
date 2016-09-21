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
class Magestore_Promotionalgift_Adminhtml_ReportcartruleController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyAdminController($this)) {
            return;
        }
        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('promotionalgift/adminhtml_reportcartrule'));
        $this->renderLayout();
    }

    public function dashboardAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyAdminController($this)) {
            return;
        }

        $this->loadLayout();
        $this->_addContent($this->getLayout()->createBlock('promotionalgift/adminhtml_reportcartrule_dashboard'));
        $this->renderLayout();
    }


    public function ajaxBlockAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyAdminController($this)) {
            return;
        }
        $output = '';
        $output = $this->getLayout()->createBlock("promotionalgift/adminhtml_reportcartrule_giftorder")->toHtml();
        $this->getResponse()->setBody($output);
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('promo/promotionalgift/report');
    }
}
