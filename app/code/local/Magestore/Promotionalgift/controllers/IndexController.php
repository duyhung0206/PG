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
class Magestore_Promotionalgift_IndexController extends Mage_Core_Controller_Front_Action
{

    public function indexAction()
    {

        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if (!Mage::helper('promotionalgift')->enablePromotionalgift())
            return $this;
        $this->_title($this->__('Promotional Gift'))->_title($this->__('Promotional Gift'));
        $this->loadLayout()
            ->renderLayout();
    }

    public function beforeSearchAction()
    {
        $data = $this->getRequest()->getPost();
        $url = Mage::getUrl('promotionalgift/index/search');
        if ($data['from_date']) {
            $url .= 'fromdate/' . $data['from_date'] . '/';
        }

        if ($data['to_date']) {
            $url .= 'todate/' . $data['to_date'] . '/';
        }
        $this->getResponse()->setRedirect($url);
    }

    public function searchAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if (!Mage::helper('promotionalgift')->enablePromotionalgift())
            return $this;
        $this->_title($this->__('Promotional Campaigns'))->_title($this->__('Promotional Campaigns'));
        $this->loadLayout()
            ->renderLayout();
    }

    public function showCatalogRuleAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        $catalogRuleId = $this->getRequest()->getParam('catalogrule');
        $rule = Mage::getModel('promotionalgift/catalogrule')->load($catalogRuleId);
        if (!Mage::helper('promotionalgift')->enablePromotionalgift())
            return $this;
        $this->_title($this->__($rule->getName()))->_title($this->__($rule->getName()));
        $this->loadLayout()
            ->renderLayout();
    }

    public function clearSessionAction()
    {
        Mage::getModel('checkout/session')->setData('catalog_rule_id', null);
        Mage::getModel('checkout/session')->setData('product_parent', null);
        Mage::getModel('checkout/session')->setData('free_gift_item', null);
        Mage::getModel('checkout/session')->setData('free_gift_item_qty', null);
        Mage::getModel('checkout/session')->setData('promotionalgift_bundle', null);
    }

    public function checkSessionAction()
    {
    }

    //ADD CATALOG RULE GIFT IN CART PAGE
    public function addPromotionalGiftsCategoryRuleAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        $session = Mage::getModel('checkout/session');

        //variable define
        $productId = $this->getRequest()->getParam('items');
        $product = Mage::getModel('catalog/product')->load($productId);
        //result is used for Ajax
        $result = array();
        //get rule id and qty
        $ruleId = $this->getRequest()->getParam('categoryruleid');
        $qty = $this->getRequest()->getParam('qty');

        //load rule from rule id
        $rule = Mage::getModel('promotionalgift/catalogrule')->load($ruleId);

        //get catalog rule item
        $ruleItem = Mage::getModel('promotionalgift/catalogitem')->load($ruleId, 'rule_id');
        $ruleItemIds = $ruleItem->getProductIds();
        $ruleItemIds = explode(',', $ruleItemIds);

        //get request info
        $requestInfo = $this->getRequest()->getPost();
        $requestInfo['qty'] = $qty;

        //check if items is exist and then add them to cart
        if (in_array($productId, $ruleItemIds)) {
            $product = new Mage_Catalog_Model_Product();
            $product->load($productId);
            /* check if gift is Out of Stock */
            if (!in_array($product->getTypeId(), array('downloadable', 'virtual'))) {
                $is_outstock = Mage::helper('promotionalgift/cart')->checkOutstock($product);
                if ($is_outstock) {
                    $message = Mage::helper('promotionalgift')->__($product->getName() . ' is out of stock !');
                    Mage::getSingleton('core/session')->addError($message);
                    return;
                }
            }

            //save data of catalog rule to session
            $session->setData('free_gift_item', $productId);
            $session->setData('free_gift_item_qty', $qty);
            $session->setData('catalog_rule_id', $ruleId);
            $session->setData('catalog_rule_product_id', $ruleId);

            //add product to cart
            $return = Mage::helper('promotionalgift/cart')->addProduct($product, $requestInfo);
        }
        if ($return == 'success') {

            if(Mage::getSingleton('checkout/session')->getData('checkrouter')=='checkout' && Mage::getSingleton('checkout/session')->getData('checkcontroll')=='onepage')
            {
                if ($this->getRequest()->getParam('is_review'))
                {
                    $review_html = $this->getReviewHtml(); //tam comment vi gay loi vietdq
                    $result['review_html'] = $review_html;
                }
                //INFORMATION FOR CATALOGRULE SLIDER
                //catalog rule slider html
                $catalogGiftBlock = $this->getLayout()->createBlock('promotionalgift/checkout_cart_catalogruleslider');
                $catalogGiftHtml = $catalogGiftBlock->toHtml();
                $result['catalog_gift_html'] = $catalogGiftHtml;
                $result['is_checkout']=1;
                //catalog rule gift item
                $rules = Mage::getModel('promotionalgift/catalogrule')->getAvailableRule();
                $stringItemRule = '';
                if ($rules != false) {
                    foreach ($rules as $rule) {
                        $rId = $rule->getId();
                        $items = Mage::helper('promotionalgift')->getCategoryRuleFreeGifts($rId);
                        if (count($items) > 0) {
                            foreach ($items as $item) {
                                if ($stringItemRule == '') {
                                    $stringItemRule = $rId . '_' . $item['product_id'];
                                } else {
                                    $stringItemRule .= ',' . $rId . '_' . $item['product_id'];
                                }
                            }
                        }
                    }
                }
                $result['catalog_rule_product'] = $stringItemRule;
                //INFORMATION FOR CATALOGRULE SLIDER
                $result["minicart_html"] = $this->getLayout()
                    ->createBlock('checkout/cart_sidebar')
                    ->setTemplate('checkout/cart/minicart/items.phtml')
                    ->addItemRender("default", "checkout/cart_item_renderer", "checkout/cart/minicart/default.phtml")
                    ->addItemRender("simple", "checkout/cart_item_renderer", "checkout/cart/minicart/default.phtml")
                    ->addItemRender("grouped", "checkout/cart_item_renderer_grouped", "checkout/cart/minicart/default.phtml")
                    ->addItemRender("configurable", "checkout/cart_item_renderer_configurable", "checkout/cart/minicart/default.phtml")
                    ->toHtml();
                $result['qtycart_html'] = Mage::helper('checkout/cart')->getSummaryCount();

                //get message notification
                $messageHtml = $this->getLayout()->getMessagesBlock()->getGroupedHtml();
                $result['message_html'] = $messageHtml;
                /*block choose gift*/
                $hasShoppingCart = Mage::helper('promotionalgift/rule')->getShoppingcartRule();
                $result['has_shopping_cart'] = $hasShoppingCart;
            } else {
                $result = $this->getRulesInformation();
            }

        } else {
            $url = Mage::getUrl('checkout/cart');
            $result['cart_url'] = $url;
        }
        $actions = Mage::helper('promotionalgift')->getActionsList();
        if(strpos($actions[0],'catalog') !== false)
            $result['has_catalog'] = true;
        else
            $result['has_catalog'] = false;
        $result['actions'] = $actions;
        $this->getResponse()->setBody(json_encode($result));
    }

    //ADD SHOPPING CART RULE GIFT IN CART PAGE
    public function addPromotionalGiftsAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        $session = Mage::getModel('checkout/session');

        //validate shopping cart quote and coupon code
        $codeRuleId = $session->getData('shoppingcart_rule_id');
        $quote = $session->getQuote();
        if (!$quote->getId()) {
            $this->_redirect('checkout/cart/index');
        } else {
            $shoppingRule = Mage::getModel('promotionalgift/shoppingcartrule')->validateQuote($quote);
            $shoppingCodeRule = Mage::getModel('promotionalgift/shoppingcartrule')->load($codeRuleId, 'coupon_code');
            if (!$shoppingRule && !$shoppingCodeRule) {
                $this->_redirect('checkout/cart/index');
            }
        }

        /* Variable define */
        $productId = $this->getRequest()->getParam('product_id');
        $product = Mage::getModel('catalog/product')->load($productId);
        //result is used for Ajax
        $result = array();

        //load rule
        $ruleId = $this->getRequest()->getParam('ruleId');
        $rule = Mage::getModel('promotionalgift/shoppingcartrule')->load($ruleId);

        //get valid qty of rule item
        $ruleItem = Mage::getModel('promotionalgift/shoppingcartitem')->load($ruleId, 'rule_id');
        $ruleItemIds = $ruleItem->getProductIds();
        $ruleItemIds = explode(',', $ruleItemIds);
        $qty = Mage::helper('promotionalgift')->getQtyProductRule($product, $rule);

        //set qty to request info
        $requestInfo = $this->getRequest()->getPost();
        $requestInfo['qty'] = $qty;

        //check if product is gift and then add to cart
        if (in_array($productId, $ruleItemIds)) {
            $product = new Mage_Catalog_Model_Product();
            $product->load($productId);
            /* check if gift is Out of Stock */
            if (!in_array($product->getTypeId(), array('downloadable', 'virtual'))) {
                $is_outstock = Mage::helper('promotionalgift/cart')->checkOutstock($product);
                if ($is_outstock) {
                    $message = Mage::helper('promotionalgift')->__($product->getName() . ' is out of stock !');
                    Mage::getSingleton('core/session')->addError($message);
                    return;
                }
            }

            //save data of shopping cart rule to session
            $session->setData('shoppingcart_gift_item', $productId);
            $session->setData('shoppingcart_gift_item_qty', $qty);
            $session->setData('shoppingcart_rule_id', $ruleId);
            if ($session->getData('promotionalgift_shoppingcart_rule_id')) {
                $session->setData('shoppingcart_couponcode_rule_id', $ruleId);
            }

            //add product to cart
            $return = Mage::helper('promotionalgift/cart')->addProduct($product, $requestInfo);
        }


        if ($return == 'success') {
            //start call reload review on checkout/one page -LOKI
            if ($this->getRequest()->getParam('is_review'))
            {
                $review_html = $this->getReviewHtml();
                $result['review_html'] = $review_html;
            }
            // end LOKI                
            $result = $this->getRulesInformation();
        } else {
            $url = Mage::getUrl('checkout/cart');
            $result['return_url'] = $url;
        }
        $actions = Mage::helper('promotionalgift')->getActionsList();
        $result['actions'] = $actions;
        $this->getResponse()->setBody(json_encode($result));
    }

    //ADD SHOPPING CART RULE GIFT IN CHECKOUT PAGE
    public function addPromotionalGiftsCheckoutAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }

        $session = Mage::getModel('checkout/session');


        //validate shopping cart quote and coupon code
        $codeRuleId = $session->getData('shoppingcart_rule_id');
        $quote = $session->getQuote();
        if (!$quote->getId()) {
            $this->_redirect('checkout/cart/index');
        } else {
            $shoppingRule = Mage::getModel('promotionalgift/shoppingcartrule')->validateQuote($quote);
            $shoppingCodeRule = Mage::getModel('promotionalgift/shoppingcartrule')->load($codeRuleId, 'coupon_code');
            if (!$shoppingRule && !$shoppingCodeRule) {
                $this->_redirectUrl($backUrl);
                return;
            }
        }

        //variable define
        $productId = $this->getRequest()->getParam('product_id');
        $product = Mage::getModel('catalog/product')->load($productId);

        //load rule
        $ruleId = $this->getRequest()->getParam('ruleId');
        $rule = Mage::getModel('promotionalgift/shoppingcartrule')->load($ruleId);

        //get valid qty of rule item
        $ruleItem = Mage::getModel('promotionalgift/shoppingcartitem')->load($ruleId, 'rule_id');
        $ruleItemIds = $ruleItem->getProductIds();
        $ruleItemIds = explode(',', $ruleItemIds);
        $qty = Mage::helper('promotionalgift')->getQtyProductRule($product, $rule);

        //define request info
        $requestInfo = $this->getRequest()->getPost();
        $requestInfo['qty'] = $qty;

        //check if product is gift and then add to cart
        if (in_array($productId, $ruleItemIds)) {
            $product = new Mage_Catalog_Model_Product();
            $product->load($productId);
            /* check if gift is Out of Stock */
            if (!in_array($product->getTypeId(), array('downloadable', 'virtual'))) {
                $is_outstock = Mage::helper('promotionalgift/cart')->checkOutstock($product);
                if ($is_outstock) {
                    $message = Mage::helper('promotionalgift')->__($product->getName() . ' is out of stock !');
                    Mage::getSingleton('core/session')->addError($message);
                    return;
                }
            }

            //save data of shopping cart rule to session
            $session->setData('shoppingcart_gift_item', $productId);
            $session->setData('shoppingcart_gift_item_qty', $qty);
            $session->setData('shoppingcart_rule_id', $ruleId);
            if ($session->getData('promotionalgift_shoppingcart_rule_id')) {
                $session->setData('shoppingcart_couponcode_rule_id', $ruleId);
            }

            //add product to cart
            $return = Mage::helper('promotionalgift/cart')->addProduct($product, $requestInfo);
        }
        $result["minicart_html"] = $this->getLayout()
            ->createBlock('checkout/cart_sidebar')
            ->setTemplate('checkout/cart/minicart/items.phtml')
            ->addItemRender("default", "checkout/cart_item_renderer", "checkout/cart/minicart/default.phtml")
            ->addItemRender("simple", "checkout/cart_item_renderer", "checkout/cart/minicart/default.phtml")
            ->addItemRender("grouped", "checkout/cart_item_renderer_grouped", "checkout/cart/minicart/default.phtml")
            ->addItemRender("configurable", "checkout/cart_item_renderer_configurable", "checkout/cart/minicart/default.phtml")
            ->toHtml();
        $result['qtycart_html'] = Mage::helper('checkout/cart')->getSummaryCount();
        $result['is_checkout']=1;
        if ($return == 'success') {
            if ($this->getRequest()->getParam('is_review')) {
                $review_html = $this->getReviewHtml();
                $result['review_html'] = $review_html;
            }
            if ($this->getRequest()->getParam('is_onestep')) {
                $review_html = $this->getReviewHtml();
                $result['is_onestep'] = 1;
            }
            //get shopping cart gift html
            $shoppingGiftBlock = $this->getLayout()->createBlock('promotionalgift/checkout_cart_shoppingcartruleslider');
            $shoppingGiftHtml = $shoppingGiftBlock->toHtml();
            $result['shopping_gift_html'] = $shoppingGiftHtml;
            //get message notification
            $messageHtml = $this->getLayout()->getMessagesBlock()->getGroupedHtml();
            $result['message_html'] = $messageHtml;
            $ruleIds = Mage::helper('promotionalgift/rule')->getShoppingcartRule();
            $stringItemRule = '';
            if (count($ruleIds) > 0) {
                foreach ($ruleIds as $ruleId) {
                    $rule = Mage::getModel('promotionalgift/shoppingcartrule')->load($ruleId);
                    $items = Mage::helper('promotionalgift')->getShoppingcartFreeGifts($rule);
                    if (count($items) > 0) {
                        foreach ($items as $item) {
                            if ($stringItemRule == '') {
                                $stringItemRule = $ruleId . '_' . $item['product_id'];
                            } else {
                                $stringItemRule .= ',' . $ruleId . '_' . $item['product_id'];
                            }
                        }
                    } else {
                        $key = array_search($ruleId, $ruleIds);
                        unset($ruleIds[$key]);
                    }
                }
            }
            $result['rule_ids'] = implode(',', $ruleIds);
            $result['shopping_rule_product'] = $stringItemRule;
        } else {
            $url = Mage::getUrl('checkout/onepage');
            $result['return_url'] = $url;
        }
        $actions = Mage::helper('promotionalgift')->getActionsList();
        $result['actions'] = $actions;
        $this->getResponse()->setBody(json_encode($result));
    }

    public function getdataforcartAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if (!Mage::helper('promotionalgift')->enablePromotionalgift())
            return $this;
        $result = array();
        $block = Mage::getBlockSingleton('promotionalgift/shoppingcart');
        $itemEditIds = $block->getEidtItemIds();
        $itemEditOptionIds = $block->getEidtItemOptionIds();
        $itemIds = $block->getItemIds();
        $result['itemEditIds'] = $itemEditIds;
        $result['itemEditOptionIds'] = $itemEditOptionIds;
        $result['itemIds'] = $itemIds;
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function updatepromotionalposAction()
    {
        if (!Mage::helper('magenotification')->checkLicenseKeyFrontController($this)) {
            return;
        }
        if (!Mage::helper('promotionalgift')->enablePromotionalgift())
            return $this;
        $this->loadLayout(false);
        $this->renderLayout();
    }

    public function checkoutAjaxLoadPromotionalgiftAction()
    {
        if (!Mage::helper('promotionalgift')->enablePromotionalgift()) {
            return $this;
        }
        $result = array();
        $result['is_reload'] = 1;
        $redirect = Mage::helper('promotionalgift/rule')->checkRules();
        if ($redirect) {
            $result['return_url'] = $redirect;
        }
        $promotionalgiftBlock = $this->getLayout()->createBlock('promotionalgift/checkout_cart_shoppingcartruleslider');
        $result['shopping_gift_html'] = $promotionalgiftBlock->toHtml();
        $ruleIds = Mage::helper('promotionalgift/rule')->getShoppingcartRule();
        $stringItemRule = '';
        if (count($ruleIds) > 0) {
            foreach ($ruleIds as $ruleId) {
                $rule = Mage::getModel('promotionalgift/shoppingcartrule')->load($ruleId);
                $items = Mage::helper('promotionalgift')->getShoppingcartFreeGifts($rule);
                if (count($items) > 0) {
                    foreach ($items as $item) {
                        if ($stringItemRule == '') {
                            $stringItemRule = $ruleId . '_' . $item['product_id'];
                        } else {
                            $stringItemRule .= ',' . $ruleId . '_' . $item['product_id'];
                        }
                    }
                } else {
                    $key = array_search($ruleId, $ruleIds);
                    unset($ruleIds[$key]);
                }
            }
        }
        $result['rule_ids'] = implode(',', $ruleIds);
        $result['shopping_rule_product'] = $stringItemRule;
        $this->getResponse()->setBody(json_encode($result));
    }

    //GET CONFIGURABLE PRODUCT STOCK STATUS
    public function getConfigProductAction()
    {
        $result = array();
        $productOptions = array();
        $params = $this->getRequest()->getParams();
        $productId = $params['product_id'];
        $optionArray = explode(',', $params['options']);
        foreach ($optionArray as $options) {
            $options = explode('_', $options);
            $productOptions[$options[0]] = $options[1];
        }
        $productConfig = new Mage_Catalog_Model_Product();
        $productConfig->load($productId);
        /* check if gift is not enough to add to cart */
        $childProduct = Mage::getModel('catalog/product_type_configurable')
            ->getProductByAttributes($productOptions, $productConfig);
        $stockConfigItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($childProduct);
        $currentQtyInCart = Mage::helper('promotionalgift')->checkItemInCart($childProduct);
        if ($stockConfigItem->getManageStock()) {
            $qtyConfigStock = $stockConfigItem->getQty() - $currentQtyInCart;
            if ($qtyConfigStock <= 0) {
                $result['status'] = 'outstock';
            } else {
                $result['status'] = 'instock';
            }
        }

        $this->getResponse()->setBody(json_encode($result));
    }

    //GET REVIEW HTML ACTION
    public function getReviewHtmlAction()
    {
        $result = array();
        $output = $this->getReviewHtml();
        $is_onestep = $this->getRequest()->getParam('is_onestep');
        if ($is_onestep) {
            $result['is_onestep'] = 1;
        }
        $result['review_html'] = $output;

        $this->getResponse()->setBody(json_encode($result));
    }

    //GET REVIEW HTML
    public function getReviewHtml()
    {
        $is_onestep = $this->getRequest()->getParam('is_onestep');
        $layout = $this->getLayout();
        $update = $layout->getUpdate();
        if ($is_onestep) {
            $update->load('onestepcheckout_onestepcheckout_review');
            $layout->unsetBlock('shippingmethod');
        } else {
            $update->load('checkout_onepage_review');
        }
        $layout->generateXml();
        $layout->generateBlocks();
        $output = $layout->getOutput();
        return $output;
    }

    //GET RULES INFORMATION FOR SLIDER
    public function getRulesInformation()
    {
        $cartBlock = $this->getLayout()->createBlock('promotionalgift/checkout_cart');
        $cartBlock->addItemRender('simple', 'promotionalgift/cart_item', 'checkout/cart/item/default.phtml');
        $cartBlock->addItemRender('virtual', 'promotionalgift/cart_item', 'checkout/cart/item/default.phtml');
        $cartBlock->addItemRender('downloadable', 'promotionalgift/cart_item', 'checkout/cart/item/default.phtml');
        $cartBlock->addItemRender('grouped', 'promotionalgift/cart_item', 'checkout/cart/item/default.phtml');
        $cartBlock->addItemRender('configurable', 'promotionalgift/cart_configurable_item', 'checkout/cart/item/default.phtml');
        $cartBlock->addItemRender('bundle', 'promotionalgift/cart_bundle_item', 'checkout/cart/item/default.phtml');
        $cartBlock->addItemRender('giftvoucher', 'promotionalgift/cart_giftvoucher_item', 'checkout/cart/item/default.phtml');
        $cart_html = $cartBlock->toHtml();
        $result['cart_html'] = $cart_html;


        $grandBlock = $this->getLayout()->createBlock('checkout/cart_totals')->setTemplate('checkout/cart/totals.phtml');
        $grand_html = $grandBlock->toHtml();
        $result['grand_html'] = $grand_html;

        //INFORMATION FOR CATALOGRULE SLIDER
        //catalog rule slider html
        $catalogGiftBlock = $this->getLayout()->createBlock('promotionalgift/checkout_cart_catalogruleslider');
        $catalogGiftHtml = $catalogGiftBlock->toHtml();
        $result['catalog_gift_html'] = $catalogGiftHtml;
        //catalog rule gift item
        $rules = Mage::getModel('promotionalgift/catalogrule')->getAvailableRule();
        $stringItemRule = '';
        if ($rules != false) {
            foreach ($rules as $rule) {
                $rId = $rule->getId();
                $items = Mage::helper('promotionalgift')->getCategoryRuleFreeGifts($rId);
                if (count($items) > 0) {
                    foreach ($items as $item) {
                        if ($stringItemRule == '') {
                            $stringItemRule = $rId . '_' . $item['product_id'];
                        } else {
                            $stringItemRule .= ',' . $rId . '_' . $item['product_id'];
                        }
                    }
                }
            }
        }
        $result['catalog_rule_product'] = $stringItemRule;

        //INFORMATION FOR CATALOGRULE SLIDER
        //shopping cart rule slider html
        $shoppingGiftBlock = $this->getLayout()->createBlock('promotionalgift/checkout_cart_shoppingcartruleslider');
        $shoppingGiftHtml = $shoppingGiftBlock->toHtml();
        $result['shopping_gift_html'] = $shoppingGiftHtml;
        //shopping cart rule gift item

        $result["minicart_html"] = $this->getLayout()
            ->createBlock('checkout/cart_sidebar')
            ->setTemplate('checkout/cart/minicart/items.phtml')
            ->addItemRender("default", "checkout/cart_item_renderer", "checkout/cart/minicart/default.phtml")
            ->addItemRender("simple", "checkout/cart_item_renderer", "checkout/cart/minicart/default.phtml")
            ->addItemRender("grouped", "checkout/cart_item_renderer_grouped", "checkout/cart/minicart/default.phtml")
            ->addItemRender("configurable", "checkout/cart_item_renderer_configurable", "checkout/cart/minicart/default.phtml")
            ->toHtml();
        $result['qtycart_html'] = Mage::helper('checkout/cart')->getSummaryCount();
        $ruleIds = Mage::helper('promotionalgift/rule')->getShoppingcartRule();
        $stringItemRuleShopping = '';
        if (count($ruleIds) > 0) {
            foreach ($ruleIds as $ruleId) {
                $rule = Mage::getModel('promotionalgift/shoppingcartrule')->load($ruleId);
                $items = Mage::helper('promotionalgift')->getShoppingcartFreeGifts($rule);
                if (count($items) > 0) {
                    foreach ($items as $item) {
                        if ($stringItemRuleShopping == '') {
                            $stringItemRuleShopping = $ruleId . '_' . $item['product_id'];
                        } else {
                            $stringItemRuleShopping .= ',' . $ruleId . '_' . $item['product_id'];
                        }
                    }
                } else {
                    $key = array_search($ruleId, $ruleIds);
                    unset($ruleIds[$key]);
                }
            }
        }
        $result['rule_ids'] = implode(',', $ruleIds);
        $result['shopping_rule_product'] = $stringItemRuleShopping;

        //get information to disable qty field in cart
        $allowchangegiftqty = Mage::helper('promotionalgift')->getConfig('changegiftqty');
        if (!$allowchangegiftqty) {
            $result['edit_item_ids'] = Mage::helper('promotionalgift/rule')->getEidtItemIds();
            $result['edit_item_option_ids'] = Mage::helper('promotionalgift/rule')->getEidtItemOptionIds();
            $result['item_ids'] = Mage::helper('promotionalgift/rule')->getItemIds();
        }

        //get message notification
        $messageHtml = $this->getLayout()->getMessagesBlock()->getGroupedHtml();
        $result['message_html'] = $messageHtml;
        /*block choose gift*/
        $actions =  Mage::helper('promotionalgift')->getActionsList();
        if(strpos($actions[0],'catalog') !== false)
            $result['has_catalog'] = true;
        else
            $result['has_catalog'] = false;
        $hasShoppingCart = Mage::helper('promotionalgift/rule')->getShoppingcartRule();
        $result['has_shopping_cart'] = $hasShoppingCart;
        return $result;
    }
    public function nextRuleAction(){
        $typeRule = $this->getRequest()->getParam('type_rule');
        $page = $this->getRequest()->getParam('page');
        $action = $this->getRequest()->getParam('action');
        $blockCatalogRule = Mage::getBlockSingleton('promotionalgift/shoppingcartcategory')
            ->setTemplate('promotionalgift/shoppingcartcategory.phtml');
        $currentCatalogRule = $blockCatalogRule->getCatalogRules()->getFirstItem()->getRuleId();
        $blockShoppingCartRule = Mage::getBlockSingleton('promotionalgift/shoppingcart')
            ->setTemplate('promotionalgift/multipleshoppingcartrules.phtml');
        $currentShoppingCart = $blockShoppingCartRule->getShoppingCartRules();
        $actions = Mage::helper('promotionalgift')->getActionsList();
        if($typeRule == 'catalog'){
            $result = array(
                'html' => $blockCatalogRule->toHtml(),
                'rule_id' => $currentCatalogRule,
                'actions' => $actions
            );
        }
        if($typeRule == 'shop'){
            $result = array(
                'html' => $blockShoppingCartRule->toHtml(),
                'shop_rule_id' => $currentShoppingCart[0],
                'actions' => $actions
            );
        }
        $this->getResponse()->setBody(json_encode($result));
    }
}
