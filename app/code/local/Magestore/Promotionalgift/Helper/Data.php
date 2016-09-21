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
 * Promotionalgift Helper
 *
 * @category    Magestore
 * @package     Magestore_Promotionalgift
 * @author      Magestore Developer
 */
class Magestore_Promotionalgift_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function getShoppingcartFreeGifts($rule) 
    {
        $ruleId = $rule->getId();
        $freeGifts = Mage::getModel('promotionalgift/shoppingcartitem')
            ->load($ruleId, 'rule_id');
        $maxItemsFree = $rule->getNumberItemFree();
        $productIds = explode(',', $freeGifts->getProductIds());
        $qtyItems = explode(',', $freeGifts->getGiftQty());
        $websiteId = Mage::app()->getStore()->getWebsiteId();
        $i = 0;
        $j = 0;
        $giftitems = array();
        if ($freeGifts->getProductIds()) {
            foreach ($productIds as $productId) {
                $product = new Mage_Catalog_Model_Product();
                $product->load($productId);
                $productWebsiteIds = $product->getWebsiteIds();
                if ($product->getStatus() != 2 && in_array($websiteId, $productWebsiteIds)) {
                    $qtyGifts = $this->checkItemOfShoppingCartRule($productId, $ruleId);
                    if ($j >= $maxItemsFree) {
                        foreach ($giftitems as $key => $gitem) {
                            if ($gitem['qty_in_cart'] == 0) {
                                unset($giftitems[$key]);
                            }
                        }
                        break;
                    }
                    if ($qtyGifts > 0) {
                        $j++;
                    }
                    $qtyItem = $qtyItems[$i] - $qtyGifts;
                    if ($qtyItem > 0) {
                        $giftitems[] = array(
                            'rule' => $ruleId,
                            'gift_qty' => $qtyItem,
                            'product_id' => $productId,
                            'qty_in_cart' => $qtyGifts
                        );
                    }
                    $i++;
                }
            }
        }
        return $giftitems;
    }

    public function getQtyProductRule($product, $rule)
    {
        if ($rule != false) {
            $giftitems = Mage::helper('promotionalgift')->getShoppingcartFreeGifts($rule);
            // Zend_debug::dump($giftitems);die('32');
            foreach ($giftitems as $giftitem) {
                if ($giftitem['product_id'] == $product->getId())
                    return $giftitem['gift_qty'];
            }
        }
        return false;
    }

    //CHECK AND RETURN THE ARRAY OF GIFT_QTY, PRODUCT_ID, RULE_ID
    public function getCategoryRuleFreeGifts($ruleId)
    {

        //get gifts of rule
        $freeGifts = Mage::getModel('promotionalgift/catalogitem')
            ->load($ruleId, 'rule_id');
        $productIds = explode(',', $freeGifts->getProductIds());
        //get qty product and check validate of main product
        $qtyProduct = $this->qtyProduct($ruleId);
        //get qty of gifts
        $qtyItems = explode(',', $freeGifts->getGiftQty());
        $websiteId = Mage::app()->getStore()->getWebsiteId();
        //variable $i using for foreach loop
        $i = 0;
        //define gift itemss
        $giftitems = array();
        if ($freeGifts->getProductIds()) {
            foreach ($productIds as $productId) {
                $product = new Mage_Catalog_Model_Product();
                $product->load($productId);
                $productWebsiteIds = $product->getWebsiteIds();
                if ($product->getStatus() != 2 && in_array($websiteId, $productWebsiteIds)) {
                    /* gift has not been added to cart, need to show it on catalog rule gift slider */
                    if ($qtyProduct > 0) {
                        //check if gift does not reach the maximum qty
                        if (Mage::getStoreConfig('promotionalgift/catalog_rule_configuration/autoupdateqty')) {
                            $qtyItem = $qtyProduct * $qtyItems[$i] - ($this->checkItem($productId, $ruleId));
                        } else {
                            $qtyItem = $qtyItems[$i] - ($this->checkItem($productId, $ruleId));
                        }
                        //if not add it to array
                        if ($qtyItem > 0) {
                            $giftitems[] = array(
                                'rule' => $ruleId,
                                'gift_qty' => $qtyItem,
                                'product_id' => $productId,
                            );
                            //update product_parent_id session data
                            $this->updateSessionProductParentArray($ruleId, $productId);
                        }
                        $i++;
                    }
                }
            }
        }
        return $giftitems;
    }

    public function updateSessionProductParentArray($ruleId, $productId)
    {
        //retrieve quote
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $quoteId = $quote->getId();

        //get resources
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $installer = Mage::getModel('core/resource_setup');

        //get item_parent_id
        $item_parent_id = array();
        $query = "SELECT `item_parent_id` FROM " . $installer->getTable('promotionalgift_quote') .
            " WHERE `catalog_rule_id` = " . $ruleId . " AND `quote_id` = " . $quoteId;
        $fetchData = $readConnection->fetchAll($query);
        foreach ($fetchData as $data) {
            if (!in_array($data['item_parent_id'])) {
                $item_parent_id[] = $data['item_parent_id'];
            }
        }
        //update session data
        //get session data
        $productParent = unserialize(Mage::getModel('checkout/session')->getData('product_parent_id'));
        if (count($item_parent_id)) {
            foreach ($item_parent_id as $itemId) {
                if (isset($productParent[$itemId]) && $productParent[$itemId] != '') {
                    $giftItems = explode(',', $productParent[$itemId]);
                    if (!in_array($productId, $giftItems)) {
                        $giftItems[] = $productId;
                    }
                } else {
                    $giftItems = array();
                    $giftItems[] = $productId;
                }
            }
            $giftItems = array_unique($giftItems);
            $productParent[$itemId] = implode(',', $giftItems);
            Mage::getModel('checkout/session')->setData('product_parent_id', serialize($productParent));
        }
    }

    //return total qty of main product
    public function qtyProduct($ruleId)
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $i = 0;
        $productGroup = 0;
        //get all item in cart
        foreach ($quote->getAllItems() as $item) {
            //check if it does not have parent id
            if (!$item->getParentItemId()) {
                //validate and return available rule
                $availableRule = Mage::getModel('promotionalgift/catalogrule')->validateItem($item->getProductId());
                //check if available rule is exist
                if ($availableRule) {
                    if ($availableRule->getId() == $ruleId) {
                        //get gift quote if the validated item is gift
                        $freeGifts = Mage::getModel('promotionalgift/quote')->getCollection();
                        $freeGifts->addFieldToFilter('item_id', $item->getId());
                        $shoppingcartQuote = Mage::getModel('promotionalgift/shoppingquote')->getCollection()
                            ->addFieldToFilter('item_id', $item->getId());
                        //if it is not a gift
                        if (!count($freeGifts) && !count($shoppingcartQuote)) {
                            $quoteOptions = Mage::getModel('sales/quote_item_option')->getCollection()
                                ->addFieldToFilter('item_id', $item->getId())
                                ->addFieldToFilter('code', 'product_type')
                                ->addFieldToFilter('value', 'grouped')
                                ->getFirstItem();
                            //if the item is grouped product
                            if ($quoteOptions && $quoteOptions->getId()) {
                                if ($productGroup != $quoteOptions->getProductId()) {
                                    $i++;
                                    $productGroup = $quoteOptions->getProductId();
                                }
                            } else {
                                $i += $item->getQty();
                            }
                        }
                    }
                }
            }
        }
        //return the total qty in cart of the item
        return $i;
    }

    //check if gift was added to cart
    public function checkItem($productId, $ruleId)
    {
        $product = new Mage_Catalog_Model_Product();
        $product->load($productId);
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if ($product->getTypeId() == 'grouped') {
            foreach ($quote->getAllItems() as $item) {
                $freeGifts = Mage::getModel('promotionalgift/quote')->getCollection();
                $freeGifts->addFieldToFilter('item_id', $item->getId())
                    ->addFieldToFilter('grouped_id', $productId)
                    ->addFieldToFilter('catalog_rule_id', $ruleId);
                if (count($freeGifts)) {
                    $qtyGift = $item->getQty();
                    return $qtyGift;
                }
            }
        } else {
            $qtyGift = 0;
            foreach ($quote->getAllItems() as $item) {
                if ($item->getProductId() == $productId) {
                    $freeGifts = Mage::getModel('promotionalgift/quote')->getCollection()
                        ->addFieldToFilter('item_id', $item->getId())
                        ->addFieldToFilter('catalog_rule_id', $ruleId);
                    if (count($freeGifts)) {
                        $qtyGift += $item->getQty();
                    }
                }
            }
            return $qtyGift;
        }
        return 0;
    }

    public function checkItemInCart($product)
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $productId = $product->getId();
        $qtyGift = 0;
        foreach ($quote->getAllItems() as $item) {
            if ($item->getProductId() == $productId) {
                if ($item->getParentItemId()) {
                    $qty = Mage::getModel('sales/quote_item')->load($item->getParentItemId())->getQty();
                    $qtyGift += $qty;
                } else {
                    $qtyGift += $item->getQty();
                }
            }
        }
        return $qtyGift;
    }

    public function checkItemOfShoppingCartRule($productId, $ruleId)
    {
        $product = new Mage_Catalog_Model_Product();
        $product->load($productId);
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $qtyGift = 0;
        if ($product->getTypeId() == 'grouped') {
            foreach ($quote->getAllItems() as $item) {
                $freeGifts = Mage::getModel('promotionalgift/shoppingquote')->getCollection();
                $freeGifts->addFieldToFilter('item_id', $item->getId())
                    ->addFieldToFilter('grouped_id', $productId)
                    ->addFieldToFilter('shoppingcartrule_id', $ruleId);
                if (count($freeGifts)) {
                    $qtyGift = $item->getQty();
                    return $qtyGift;
                }
            }
        } else {
            foreach ($quote->getAllItems() as $item) {
                if ($item->getProductId() == $productId) {
                    $freeGifts = Mage::getModel('promotionalgift/shoppingquote')->getCollection()
                        ->addFieldToFilter('item_id', $item->getId())
                        ->addFieldToFilter('shoppingcartrule_id', $ruleId);
                    if (count($freeGifts)) {
                        $qtyGift += $item->getQty();
                    }
                }
            }
            return $qtyGift;
        }
        return 0;
    }

    public function getProductChild($parentItem, $productId)
    {
        $availableRule = Mage::getModel('promotionalgift/catalogrule')->validateItem($productId);
        if ($availableRule) {
            $totalItems = Mage::getModel('promotionalgift/catalogitem')
                ->getCollection()
                ->addFieldToFilter('rule_id', $availableRule->getId())
                ->getFirstItem()
                ->getProductIds();

            return $totalItems;
        }
    }

    protected $_childProductIds;

    public function getProductChildId()
    {
        if (is_null($this->_childProductIds)) {
            $this->_childProductIds = array();
        }
        $productChild = Mage::getModel('catalog/product')->getCollection()
            ->addFieldToFilter('visibility', Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
        Mage::getSingleton('core/resource_iterator')->walk(
            $productChild->getSelect(), array(array($this, 'childProductIdCallback')), array(
                'product' => Mage::getModel('catalog/product'),
            )
        );
        return $this->_childProductIds;
    }

    public function childProductIdCallback($args)
    {
        $proCh = clone $args['product'];
        $proCh->setData($args['row']);
        $parents = Mage::getModel('catalog/product_type_configurable')
            ->getParentIdsByChild($proCh->getId());
        if (count($parents)) {
            $this->_childProductIds[] = $proCh->getId();
        } else {
            $parents = Mage::getModel('catalog/product_type_grouped')
                ->getParentIdsByChild($proCh->getId());
            if (count($parents)) {
                $this->_childProductIds[] = $proCh->getId();
            } else {
                $parents = Mage::getModel('bundle/product_type')
                    ->getParentIdsByChild($proCh->getId());
                if (count($parents)) {
                    $this->_childProductIds[] = $proCh->getId();
                }
            }
        }
    }

    public function getPromotionalgiftUrl()
    {
        $url = $this->_getUrl('promotionalgift/index/index', array());
        return $url;
    }

    public function getStoreId()
    {
        return Mage::app()->getStore()->getId();
    }

    public function enablePromotionalgift()
    {
        if (!Mage::helper('magenotification')->checkLicenseKey('Promotionalgift')) {
            return false;
        }
        return Mage::getStoreConfig('promotionalgift/general/enable', $this->getStoreId());
    }

    public function getPromotionalIcon()
    {
        $showGiftLabel = Mage::getStoreConfig('promotionalgift/general/showgiftlabel', $this->getStoreId());
        if ($showGiftLabel)
            return Mage::getStoreConfig('promotionalgift/general/giftlabel', $this->getStoreId());
        return null;
    }

    public function showFreeGift()
    {
        return Mage::getStoreConfig('promotionalgift/general/showfreegift', $this->getStoreId());
    }

    public function getReportConfig($code, $store = null)
    {
        return Mage::getStoreConfig('promotionalgift/report/' . $code, $store);
    }

    public function getShoppingcartRule()
    {
        $session = Mage::getModel('checkout/session');
        $quote = Mage::getModel('checkout/session')->getQuote();
        $rule = Mage::getModel('promotionalgift/shoppingcartrule')->validateQuote($quote);
        if ($rule) {
            return $rule;
        } else {
            return false;
        }
    }

    /**
     * get Mini cart block class
     *
     * @return string
     */
    public function getMiniCartClass()
    {
        if (!isset($this->_cache['mini_cart_class'])) {
            $minicartSelect = '';
            if ($minicartBlock = Mage::app()->getLayout()->getBlock('cart_sidebar')) {
                $xmlMinicart = simplexml_load_string($this->toXMLElement($minicartBlock->toHtml()));
                $attributes = $xmlMinicart->attributes();
                if ($id = (string)$attributes->id) {
                    $minicartSelect = "#$id";
                } elseif ($class = (string)$attributes->class) {
                    $minicartSelect = '[class="' . $class . '"]';
                }
            }
            $this->_cache['mini_cart_class'] = $minicartSelect;
        }
        return $this->_cache['mini_cart_class'];
    }

    public function toXMLElement($html)
    {
        $open = trim(substr($html, 0, strpos($html, '>') + 1));
        $close = '</' . substr($open, 1, strpos($open, ' ') - 1) . '>';
        if ($xml = $open . $close) {
            return $xml;
        }
        return '<div></div>';
    }
    public function checkBannerCalendar($rule)
    {
        $checkCalendar = false;
        $gift_calendar = $rule->getBannerCalendar();
        $now = getdate();
        if ($gift_calendar == 'all') {
            $checkCalendar = true;
        }
        if ($gift_calendar == 'daily') {
            $daily = $rule->getDaily();
            $mday = $now['mday'];
            $daily = explode(',', $daily);
            if (in_array($mday, $daily)) {
                $checkCalendar = true;
            }
        }
        if ($gift_calendar == 'weekly') {
            $weekly = $rule->getWeekly();
            $wday = $now['weekday'];
            $weekly = explode(',', $weekly);
            if (in_array(strtolower($wday), $weekly)) {
                $checkCalendar = true;
            }
        }
        if ($gift_calendar == 'yearly') {
            $yearly = $rule->getYearly();
            $mon = $now['month'];
            $yearly = explode(',', $yearly);
            if (in_array(strtolower($mon), $yearly)) {
                $checkCalendar = true;
            }
        }
        if ($gift_calendar == 'monthly') {
            $date = date("Y-m-d", Mage::getModel('core/date')->timestamp(now()));
            $week_num = $this->getWeeks($date);
            $monthly = $rule->getMonthly();
            $monthly = explode(',', $monthly);
            if (in_array($week_num, $monthly)) {
                $checkCalendar = true;
            }
        }
        return $checkCalendar;
    }
    public function checkCalendar($rule)
    {
        $checkCalendar = false;
        $gift_calendar = $rule->getGiftCalendar();
        $now = getdate();
        if ($gift_calendar == 'all') {
            $checkCalendar = true;
        }
        if ($gift_calendar == 'daily') {
            $daily = $rule->getDaily();
            $mday = $now['mday'];
            $daily = explode(',', $daily);
            if (in_array($mday, $daily)) {
                $checkCalendar = true;
            }
        }
        if ($gift_calendar == 'weekly') {
            $weekly = $rule->getWeekly();
            $wday = $now['weekday'];
            $weekly = explode(',', $weekly);
            if (in_array(strtolower($wday), $weekly)) {
                $checkCalendar = true;
            }
        }
        if ($gift_calendar == 'yearly') {
            $yearly = $rule->getYearly();
            $mon = $now['month'];
            $yearly = explode(',', $yearly);
            if (in_array(strtolower($mon), $yearly)) {
                $checkCalendar = true;
            }
        }
        if ($gift_calendar == 'monthly') {
            $date = date("Y-m-d", Mage::getModel('core/date')->timestamp(now()));
            $week_num = $this->getWeeks($date);
            $monthly = $rule->getMonthly();
            $monthly = explode(',', $monthly);
            if (in_array($week_num, $monthly)) {
                $checkCalendar = true;
            }
        }
        return $checkCalendar;
    }

    public function getWeeks($date)
    {
        $date_parts = explode('-', $date);
        $date_parts[2] = '01';
        $first_of_month = implode('-', $date_parts);
        $day_of_first = date('N', strtotime($first_of_month));
        $day_of_month = date('j', strtotime($date));
        return floor(($day_of_first + $day_of_month - 1) / 7) + 1;
    }

    public function getAllRuleIds()
    {
        $ruleIds = array();
        $shoppingcartRules = Mage::getModel('promotionalgift/shoppingcartrule')->getAvailableRule();
        foreach ($shoppingcartRules as $rule) {
            $ruleIds[] = $rule->getId();
        }
        return $ruleIds;
    }

    public function getModuleStatus()
    {
        $status = Mage::getStoreConfig('promotionalgift/general/enable');
        if ($status == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function getCalendarInformation($rule)
    {
        $gift_calendar = $rule->getGiftCalendar();
        $stringCalendarInfo = '';
        $i = 0;
        if ($gift_calendar == 'all') {
            $checkCalendar = true;
            $stringCalendarInfo = 'All days';
        }
        //daily
        if ($gift_calendar == 'daily') {
            $daily = $rule->getDaily();
            $daily = explode(',', $daily);
            $stringCalendarInfo = 'On the';
            foreach ($daily as $d) {
                $i++;
                if ($i == count($daily)) {
                    if ($d == 1 || $d == 11 || $d == 21 || $d == 31) {
                        $stringCalendarInfo .= ' ' . $d . 'st';
                    } elseif ($d == 2 || $d == 12 || $d == 22) {
                        $stringCalendarInfo .= ' ' . $d . 'nd';
                    } elseif ($d == 3 || $d == 13 || $d == 23) {
                        $stringCalendarInfo .= ' ' . $d . 'rd';
                    } else {
                        $stringCalendarInfo .= ' ' . $d . 'th';
                    }
                } else {
                    if ($d == 1 || $d == 11 || $d == 21 || $d == 31) {
                        $stringCalendarInfo .= ' ' . $d . 'st, ';
                    } elseif ($d == 2 || $d == 12 || $d == 22) {
                        $stringCalendarInfo .= ' ' . $d . 'nd, ';
                    } elseif ($d == 3 || $d == 13 || $d == 23) {
                        $stringCalendarInfo .= ' ' . $d . 'rd, ';
                    } else {
                        $stringCalendarInfo .= ' ' . $d . 'th, ';
                    }
                }
            }
            $stringCalendarInfo .= ' of month';
        }
        //weekly
        if ($gift_calendar == 'weekly') {
            $weekly = $rule->getWeekly();
            $stringCalendarInfo = 'Every ';
            $weekly = explode(',', $weekly);
            foreach ($weekly as $w) {
                $i++;
                if ($i == count($weekly)) {
                    $stringCalendarInfo .= ucwords($w);
                } else {
                    $stringCalendarInfo .= ucwords($w) . ', ';
                }
            }
            $stringCalendarInfo .= ' of week';
        }
        //yearly
        if ($gift_calendar == 'yearly') {
            $yearly = $rule->getYearly();
            $stringCalendarInfo = 'In ';
            $yearly = explode(',', $yearly);
            foreach ($yearly as $y) {
                $i++;
                if ($i == count($yearly)) {
                    $stringCalendarInfo .= ucwords($y);
                } else {
                    $stringCalendarInfo .= ucwords($y) . ', ';
                }
            }
        }
        //monthly
        if ($gift_calendar == 'monthly') {
            $monthly = $rule->getMonthly();
            $monthly = explode(',', $monthly);
            $stringCalendarInfo = 'In ';
            foreach ($monthly as $m) {
                $i++;
                if ($i == count($monthly)) {
                    if ($m == 1) {
                        $stringCalendarInfo .= $m . 'st week ';
                    } elseif ($m == 2) {
                        $stringCalendarInfo .= $m . 'nd week ';
                    } elseif ($m == 3) {
                        $stringCalendarInfo .= $m . 'rd week ';
                    } else {
                        $stringCalendarInfo .= $m . 'th week ';
                    }
                } else {
                    if ($m == 1) {
                        $stringCalendarInfo .= $m . 'st week, ';
                    } elseif ($m == 2) {
                        $stringCalendarInfo .= $m . 'nd week, ';
                    } elseif ($m == 3) {
                        $stringCalendarInfo .= $m . 'rd week, ';
                    } else {
                        $stringCalendarInfo .= $m . 'th week, ';
                    }
                }
            }
            $monthly .= 'of month';
        }
        return $stringCalendarInfo;
    }


    //get config of promotional gift
    public function getConfig($config)
    {
        $storeId = Mage::app()->getStore()->getId();
        return Mage::getStoreConfig('promotionalgift/general/' . $config, $storeId);
    }

    //check remaining qty of gift of shopping cart rules
    public function checkRemainQtyRule($rule, $quoteId)
    {
        $ruleId = $rule->getId();
        $sItem = Mage::getModel('promotionalgift/shoppingcartitem')
            ->getCollection()
            ->addFieldToFilter('rule_id', $ruleId)
            ->getFirstItem();
        $productIds = explode(',', $sItem->getProductIds());
        $qtys = explode(',', $sItem->getGiftQty());
        $maxItems = $rule->getNumberItemFree();
        if ($maxItems > count($productIds)) {
            $maxItems = count($productIds);
        }
        $itemUsed = array();
        $ruleQuotes = Mage::getModel('promotionalgift/shoppingquote')
            ->getCollection()
            ->addFieldToFilter('shoppingcartrule_id', $rule->getId())
            ->addFieldToFilter('quote_id', $quoteId);
        if (count($ruleQuotes) == 0) {
            return true;
        }
        if (count($ruleQuotes) > 0) {
            $totalAllowQty = 0;
            $totalQtyInCart = 0;
            foreach ($ruleQuotes as $ruleQuote) {
                $itemId = $ruleQuote->getItemId();
                $item = Mage::getModel('sales/quote_item')->load($itemId);
                $key = array_search($item->getProductId(), $productIds);
                if (isset($key)) {
                    if (!in_array($item->getProductId(), $itemUsed)) {
                        $itemUsed[] = $item->getProductId();
                    }
                    $totalAllowQty += $qtys[$key];
                    $totalQtyInCart += $item->getQty();
                }
            }
            if (count($itemUsed) < $maxItems) {
                return true;
            }
            if (count($itemUsed) == $maxItems) {
                if ($totalAllowQty > $totalQtyInCart) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getAjaxloaderImage()
    {
        $image = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'promotionalgift/promotionalgift_loader.gif';
        return $image;
    }

    public function getNullSessionActionName()
    {
        return array(
            0 => 'index',
            1 => 'addPromotionalGiftsCheckout',
            2 => 'saveOrder'
        );
    }
    public function getValidateRules(){
        $helper = Mage::helper('promotionalgift');
        $rules = Mage::getModel('promotionalgift/catalogrule')->getAvailableRule();
        if ($rules != false) {
            foreach ($rules as $rule) {
                $ruleId = $rule->getId();
                if (isset($ruleId) && $ruleId > 0) {
                    $items[] = array(
                        'rule_id' => $ruleId,
                        'items' => $helper->getCategoryRuleFreeGifts($ruleId),
                        'number_item_select' => $rule->getNumberItemFree()
                    );
                }
            }
        }
        $quoteId = Mage::getModel('checkout/session')->getQuote()->getId();
        $productIds= array();
        $ruleRemain =array();
        foreach ($items as $itemRule) {

            $maxitems = Mage::getModel('promotionalgift/catalogitem')->load($itemRule['rule_id'])->getProductIds();
            $totalItems = count(explode(',', $maxitems));

//            $totalItems= count($itemRule['items']);
            $numberItemSelect = $itemRule['number_item_select'];
            $ruleId = $itemRule['rule_id'];
            $itemIds = Mage::getModel('promotionalgift/quote')
                ->getCollection()
                ->addFieldToFilter('quote_id', $quoteId)
                ->addFieldToFilter('catalog_rule_id', $ruleId);

            if ($itemIds) {
                if ($numberItemSelect >= $totalItems) {
                    $numberFreeItems[$ruleId] = $totalItems;
                } else {
                    $numberFreeItems[$ruleId] = $numberItemSelect;
                }
                $productIds = array();
                foreach ($itemIds as $itemId) {
                    $itemId = $itemId->getItemId();
                    $cartitems = Mage::getModel('checkout/cart')->getQuote()->getAllItems();
                    foreach ($cartitems as $cartitem) {
                        if ($cartitem->getItemId() == $itemId) {
                            $giftQtyRule = array();
                            $giftQtyRules = Mage::helper('promotionalgift/cart')->getGiftCatalogRuleProductQty($ruleId, $giftQtyRule);
                            $qtyProductRule = $giftQtyRules[$ruleId][$cartitem->getProductId()];
                            if ($qtyProductRule == $cartitem->getQty()) {
                                $productIds[] = $cartitem->getProductId();
                                $numberFreeItems[$ruleId]--;
                            }
                        }
                    }
                }
                //$numberFreeItems[$ruleId] = $numberFreeItems[$ruleId] - (count($productIds));
            }
            if ($numberFreeItems[$ruleId] > 0 && count($itemRule['items'])) {
                $rulecurrent = Mage::getModel('promotionalgift/catalogrule')->load($ruleId);
                $ruleRemain[]=$ruleId;
            }


        }
        return $ruleRemain;
    }
    public function checkHasCatalogRule()
    {
        $ruleRemain = $this->getCurrentAvailableRule();
        if (count($ruleRemain)==0) {
            return false;
        } else {
            return true;
        }
    }
    public function returnlayout() {
        return '&nbsp;&lt;block name="promotionalgift.cms.banner" type="promotionalgift/cmsbanner" /&gt<br/>';
    }

    public function returnblock() {
        return '&nbsp;&nbsp{{block type="promotionalgift/cmsbanner"}}<br>';
    }

    public function returntext() {
        return 'Besides the Banner Listing page, you can show the Banner box on other places by using the following options (recommended for developers).';
    }

    public function returntemplate() {
        return "&nbsp;\$this->getLayout()->createBlock('promotionalgift/cmsbanner')->tohtml();";
    }
	public function getActionsList(){
		$shoppingCartRuleIds = Mage::helper('promotionalgift/rule')->getShoppingcartRule();
		$isCategoryRule = $this->checkHasCatalogRule();
		$actions = array();
		$i = 0;
		if($isCategoryRule && $numberOfRules = count($this->getCurrentAvailableRule())){
			foreach ($this->getCurrentAvailableRule() as $ruleId) {
				$items =  Mage::helper('promotionalgift')->getCategoryRuleFreeGifts($ruleId);
				if(count($items) > 0){
					$i++;
					$actions[] = "loadRule(this,".$i.",'catalog')";
				}
			}
		}
// 		if($shoppingCartRuleIds && count($shoppingCartRuleIds) > 0){
// 			for($i = 1; $i <= count($shoppingCartRuleIds);$i++){
// 			    $rule = Mage::getModel('promotionalgift/shoppingcartrule')->load($shoppingCartRuleIds[$i]);
// //				$items = $this->getShoppingcartFreeGifts($rule);
// //				if(count($items) > 0)
// 					$actions[] = "loadRule(this,".$i.",'shop')";
// 			}
// 		}
        if($shoppingCartRuleIds && count($shoppingCartRuleIds) > 0){
            $i=0;
            foreach ($shoppingCartRuleIds as $id) {
                $rule = Mage::getModel('promotionalgift/shoppingcartrule')->load($id);
                if(count($rule) > 0){   
                    ++$i;
                    $actions[]="loadRule(this,".$i.",'shop')";
                }
            }
        }
		return $actions;
	}
    public function getCurrentAvailableRule() {  //eden
        $rules = Mage::getModel('promotionalgift/catalogrule')->getAvailableRule();
        $autoUpdateQty = Mage::getStoreConfig('promotionalgift/catalog_rule_configuration/autoupdateqty');
        $ruleAvailable = array();
        foreach ($rules as $rule) {
            $ruleId= $rule->getRuleId();
            $catalogItem=Mage::getModel('promotionalgift/catalogitem')->load($ruleId,'rule_id');
            $productIds= $catalogItem->getProductIds();
            $giftQty = $catalogItem->getGiftQty();
            $productIdsArray = explode(',',$productIds);
            $qtyIdsArray = explode(',',$giftQty);
            $count=0;
            $productQty = array();
            foreach ($productIdsArray as $productId) {
                $productQty[$productId] = $qtyIdsArray[$count];
                $count++;
            }
            $quoteId = Mage::getModel('checkout/cart')->getQuote()->getId();
            $cartitems = Mage::getModel('checkout/cart')->getQuote()->getAllVisibleItems();
            $giftItemInCartIds= array();
            $giftItemInCarts  = Mage::getModel('promotionalgift/quote')
                ->getCollection()
                ->addFieldToFilter('quote_id', $quoteId)
                ->addFieldToFilter('catalog_rule_id', $ruleId);
            foreach ($giftItemInCarts as $giftItemInCart) {
                $giftItemInCartIds[] = $giftItemInCart->getItemId();
            }
            $numberGiftAdded=0;
            $parentQty = 0;
            foreach ($cartitems as $item) {
                $productId = $item->getProduct()->getId();
                $availableRule = Mage::getModel('promotionalgift/catalogrule')->validateItem($productId);
                $itemId = $item->getId();
                $itemIsGift = false;
                $itemOptions = $item->getOptions();
                foreach ($itemOptions as $option) {
                    $oData = $option->getData();
                    if (!$item->getParentItemId()) {
                        if ($oData['code'] == 'option_promotionalgift_catalogrule' || $oData['code'] == 'option_promotionalgift_shoppingcartrule') {
                            $itemIsGift = true;
                        }
                    }
                }
                if ($availableRule) {
                    if ($itemIsGift == false) {
                        if ($availableRule->getRuleId() == $ruleId) {
                            $parentQty = $parentQty + $item->getQty();
                        }
                    }
                }
            }



            foreach ($cartitems as $cartitem) {
                $cartItemId = $cartitem->getItemId();

                if (in_array($cartItemId,$giftItemInCartIds)) {
                    $productId = $cartitem->getProduct()->getId();
                    $cartItemQty = $cartitem->getQty();
                    if (!$autoUpdateQty) {
                        if ($cartItemQty >= $productQty[$productId]) {
                            $numberGiftAdded = $numberGiftAdded + 1;
                        }
                    } else {

                        if ($cartItemQty >= $productQty[$productId]*$parentQty) {
                            $numberGiftAdded = $numberGiftAdded + 1;
                        }
                    }
                }
            }
            if ($numberGiftAdded<$rule->getNumberItemFree()) {
                if ($parentQty!=0)
                    $ruleAvailable[] = $ruleId;
            }

        }

        return $ruleAvailable;
    }
}
