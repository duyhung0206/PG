<?php

class Magestore_Promotionalgift_Block_Cart_Item_Renderer extends Mage_Checkout_Block_Cart_Item_Renderer
{

    public function isOnCheckoutPage()
    {
        $module = $this->getRequest()->getModuleName();
        $controller = $this->getRequest()->getControllerName();
        if((Mage::getSingleton('checkout/session')->getData('isCheckout'))
            && ($module=='promotionalgift')) {
            return true;
        }
        return $module == 'checkout' && ($controller == 'onepage' || $controller == 'multishipping');
    }
}
