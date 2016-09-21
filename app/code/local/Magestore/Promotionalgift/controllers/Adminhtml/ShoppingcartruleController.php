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
class Magestore_Promotionalgift_Adminhtml_ShoppingcartruleController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('promotionalgift/shoppingcartrule')
            ->_addBreadcrumb(Mage::helper('promotionalgift')->__('Shoppingcart Rules'),
                Mage::helper('promotionalgift')->__('Shoppingcart Rules'));
        return $this;
    }

    public function indexAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyAdminController($this)) {
            return;
        }
        $this->_title($this->__('Shoppingcart Rule'))->_title($this->__('Shoppingcart Rule'));
        $this->_initAction()
            ->renderLayout();
    }

    public function editAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyAdminController($this)) {
            return;
        }
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('promotionalgift/shoppingcartrule')->load($id);

        if ($model->getId() || $id == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data);
            }

            $model->getConditions()->setJsFormObject('rule_conditions_fieldset');
            Mage::register('shoppingcartrule_data', $model);

            $this->_title($this->__('Promotional Gift'))
                ->_title($this->__('Manage rule'));
            if ($model->getId()) {
                $this->_title($model->getName());
            } else {
                $this->_title($this->__('New rule'));
            }

            $this->loadLayout();
            $this->_setActiveMenu('promotionalgift/shoppingcartrule');

            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Rule Manager'), Mage::helper('adminhtml')->__('Rule Manager'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Rule News'), Mage::helper('adminhtml')->__('Rule News'));

            $this->getLayout()->getBlock('head')
                ->setCanLoadExtJs(true)
                ->setCanLoadRulesJs(true);

            $this->_addContent($this->getLayout()->createBlock('promotionalgift/adminhtml_shoppingcartrule_edit'))
                ->_addLeft($this->getLayout()->createBlock('promotionalgift/adminhtml_shoppingcartrule_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('promotionalgift')->__('Rule does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function giftitemAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyAdminController($this)) {
            return;
        }
        $this->loadLayout();
        $this->getLayout()->getBlock('promotionalgift.shoppingcartrule.edit.tab.giftitem')
            ->setGiftitems($this->getRequest()->getPost('psgiftitem', null));
        $this->renderLayout();
    }

    public function giftitemGridAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyAdminController($this)) {
            return;
        }
        $this->loadLayout();
        $this->getLayout()->getBlock('promotionalgift.shoppingcartrule.edit.tab.giftitem')
            ->setGiftitems($this->getRequest()->getPost('psgiftitem', null));
        $this->renderLayout();
    }

    public function saveAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyAdminController($this)) {
            return;
        }
        if ($data = $this->getRequest()->getPost()) {
            $data = $this->_filterDates($data, array('from_date', 'to_date'));

            if (isset($data['from_date']) && $data['from_date'] == '') $data['from_date'] = null;
            if (isset($data['to_date']) && $data['to_date'] == '') $data['to_date'] = null;
            $model = Mage::getModel('promotionalgift/shoppingcartrule');
            if (isset($data['rule'])) {
                $rules = $data['rule'];
                if (isset($rules['conditions']))
                    $data['conditions'] = $rules['conditions'];
                if (isset($rules['actions']))
                    $data['actions'] = $rules['actions'];
                unset($data['rule']);
            }
            if (!$data['number_item_free'] && $data['number_item_free'] != '0') {
                $data['number_item_free'] = 1;
            }
            if ($data['coupon_type'] == '1') {
                $data['coupon_code'] = '';
                // $data['uses_per_coupon'] = '';
            }
            //choose price type
            if (!empty($data['price_type'])) {
                if ($data['price_type'] == 1) {
                    if ($data['discount_product'] > 100)
                        $data['discount_product'] = 100;
                    if ($data['discount_product'] < 0)
                        $data['discount_product'] = 0;
                } elseif ($data['price_type'] == 2) {
                    $data['discount_product_fixed'] = ($data['discount_product_fixed'] < 0) ? 0 : $data['discount_product_fixed'];
                }
            }
            if (!$data['uses_per_coupon'] && $data['uses_per_coupon'] != '0') $data['uses_per_coupon'] = null;
            if (!$data['time_used'] && $data['time_used'] != '0') $data['time_used'] = null;
            // add data to model
            $model->addData($data)
                ->setId($this->getRequest()->getParam('id'));
            if ($data['coupon_code'] != '') {
                $checkCouponCode = $model->checkCouponCodeExist($data['coupon_code'], $this->getRequest()->getParam('id'));
                if ($checkCouponCode) {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('promotionalgift')
                        ->__('This coupon code has been created in the other rule already!'));
                    $this->_redirect('*/*/edit', array(
                        'id' => $this->getRequest()->getParam('id'),
                    ));
                    return;
                }
            }
            try {
                $model->loadPost($data);
                //save date
                $model->setData('from_date', $data['from_date']);
                $model->setData('to_date', $data['to_date']);

                $model->save();
                //save list of gift items
                if (isset($data['shoppingcartrule_giftitem']) && is_array($data['shoppingcartrule_giftitem']))
                    $giftItems = array();
                parse_str($data['shoppingcartrule_giftitem'], $giftItems);
                if (count($giftItems)) {
                    $productIds = '';
                    $qtys = '';
                    $count = 0;
                    foreach ($giftItems as $pId => $enCoded) {
                        $codeArr = array();
                        parse_str(base64_decode($enCoded), $codeArr);
                        if (!$codeArr['gift_qty']) $codeArr['gift_qty'] = 1;
                        if ($count == 0) {
                            $productIds .= $pId;
                            $qtys .= $codeArr['gift_qty'];
                        } else {
                            $productIds .= ',' . $pId;
                            $qtys .= ',' . $codeArr['gift_qty'];
                        }
                        $count++;
                    }
                    $shoppingcartItem = Mage::getModel('promotionalgift/shoppingcartitem')
                        ->getCollection()
                        ->addFieldToFilter('rule_id', $model->getId())
                        ->getFirstItem();
                    if ($shoppingcartItem->getId()) {
                        $shoppingcartItem->setRuleId($model->getId())
                            ->setProductIds($productIds)
                            ->setGiftQty($qtys)
                            ->save();
                    } else {
                        Mage::getModel('promotionalgift/shoppingcartitem')
                            ->setRuleId($model->getId())
                            ->setProductIds($productIds)
                            ->setGiftQty($qtys)
                            ->save();
                    }
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('promotionalgift')
                    ->__('Shopping cart rule has been successfully saved!'));
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
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('promotionalgift')
            ->__('Saving failed! Unable to find shoppingcart rule.'));
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyAdminController($this)) {
            return;
        }
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $model = Mage::getModel('promotionalgift/shoppingcartrule');
                $model->setId($this->getRequest()->getParam('id'))
                    ->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')
                    ->__('Shoppingcart rule was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array(
                    'id' => $this->getRequest()->getParam('id'),
                    // 'store' => $this->getRequest()->getParam('store'),
                ));
            }
        }
        $this->_redirect('*/*/', array('store' => $this->getRequest()->getParam('store')));
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('promo/promotionalgift/shoppingcartrule');
    }

    //Code By Eden
    public function massDeleteAction()
    {
        $ruleIds = $this->getRequest()->getParam('rule');
        if (!is_array($ruleIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($ruleIds as $ruleId) {
                    $shoppingCartRule = Mage::getModel('promotionalgift/shoppingcartrule')->load($ruleId);
                    $shoppingCartRule->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were successfully deleted',
                        count($ruleIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function massStatusAction()
    {
        $ruleIds = $this->getRequest()->getParam('rule');
        $status = $this->getRequest()->getParam('status');
        if (!is_array($ruleIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select item(s)'));
        } else {
            try {
                foreach ($ruleIds as $ruleId) {
                    $shoppingCartRule = Mage::getModel('promotionalgift/shoppingcartrule')->load($ruleId);
                    $shoppingCartRule->setStatus($status);
                    $shoppingCartRule->save();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were successfully updated',
                        count($ruleIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
    //end
}
