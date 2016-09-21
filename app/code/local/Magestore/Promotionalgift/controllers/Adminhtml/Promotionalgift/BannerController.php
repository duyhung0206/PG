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
class Magestore_Promotionalgift_Adminhtml_Promotionalgift_BannerController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('promotionalgift/banner')
            ->_addBreadcrumb(Mage::helper('promotionalgift')->__('Manager Banner'), Mage::helper('promotionalgift')->__('Manager Banner'));
        return $this;
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('promo/promotionalgift/banner');
    }

    public function indexAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyAdminController($this)) {
            return;
        }
        $this->_title($this->__('Manager Banner'))->_title($this->__('Manager Banner'));
        $this->_initAction()
            ->renderLayout();
    }

    public function editAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyAdminController($this)) {
            return;
        }
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('promotionalgift/banner')->load($id);

        if ($model->getId() || $id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }

            $model->getConditions()->setJsFormObject('rule_conditions_fieldset');
            Mage::register('banner_data', $model);

            $this->_title($this->__('Promotional Gift'))
                ->_title($this->__('Manage rule'));
            if ($model->getId()) {
                $this->_title($model->getName());
            } else {
                $this->_title($this->__('New Banner'));
            }

            $this->loadLayout();
            $this->_setActiveMenu('promotionalgift/banner');

            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Manager Banner'), Mage::helper('adminhtml')->__('Manager Banner'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Rule News'), Mage::helper('adminhtml')->__('Rule News'));

            $this->getLayout()->getBlock('head')
                ->setCanLoadExtJs(true)
                ->setCanLoadRulesJs(true);

            $this->_addContent($this->getLayout()->createBlock('promotionalgift/adminhtml_banner_edit'))
                ->_addLeft($this->getLayout()->createBlock('promotionalgift/adminhtml_banner_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('promotionalgift')->__('Banner does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function saveAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyAdminController($this)) {
            return;
        }
        if (!Mage::getStoreConfig('promotionalgift/general/enable')) {
            return;
        }
        if ($data = $this->getRequest()->getPost()) {
            //check date
            $data = $this->_filterDates($data, array('from_date', 'to_date'));
            if (isset($data['from_date']) && $data['from_date'] == '')
                $data['from_date'] = null;
            if (isset($data['to_date']) && $data['to_date'] == '')
                $data['to_date'] = null;
            $model = Mage::getModel('promotionalgift/banner');
            //check rule condition
            if (isset($data['rule'])) {
                $rules = $data['rule'];
                if (isset($rules['conditions']))
                    $data['conditions'] = $rules['conditions'];
                if (isset($rules['actions']))
                    $data['actions'] = $rules['actions'];
                unset($data['rule']);
            }
            //check time 
            if (isset($data['time_used']) && !$data['time_used'] && $data['time_used'] != '0')
                $data['time_used'] = null;
            // add data to model
            $model->addData($data)
                ->setId($this->getRequest()->getParam('id'));
            try {
                $model->loadPost($data);
                //save date
                $model->setData('from_date', $data['from_date']);
                $model->setData('to_date', $data['to_date']);
                $model->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('promotionalgift')->__('Banner was successfully saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array(
                        'id' => $model->getId(),
                    ));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array(
                    'id' => $this->getRequest()->getParam('id'),
                ));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('promotionalgift')->__('Unable to find Banner to save'));
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                Mage::getModel('promotionalgift/banner')->load($id)->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Delete banner success'));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($this->__('Unable to find Banner to delete'));
            }
            $this->_redirect('*/*');
        }
    }
}