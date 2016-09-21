/**********Js For Ajaxcart In Cart Page**********/


if (typeof Element.clone == 'undefined') {
    Element.clone = function (element, deep) {
        if (!(element = $(element)))
            return;
        var clone = element.cloneNode(deep);
        clone._prototypeUID = void 0;
        if (deep) {
            var descendants = Element.select(clone, '*'),
                i = descendants.length;
            while (i--) {
                descendants[i]._prototypeUID = void 0;
            }
        }
        return Element.extend(clone);
    };
}

var Ajaxfreecartpage = Class.create();
Ajaxfreecartpage.prototype = {
    allowFinish: true,
    initialize: function (ajaxMask, ajaxPopup, popupContent, cartPage, links, preLoadAjax) {
        this.ajaxMask = ajaxMask;
        this.ajaxPopup = ajaxPopup;
        this.popupContent = popupContent;
        this.cartPage = cartPage;
        this.links = links;

        this.preLoadAjax = preLoadAjax;

        this.jsSource = [];
        this.jsCache = [];
        this.jsCount = 0;
        this.intervalCache = 0;

        this.ajaxOnComplete = this.ajaxOnComplete.bindAsEventListener(this);
        this.addJsSource = this.addJsSource.bindAsEventListener(this);
        this.updateJscartEvent = this.updateJscartEvent.bindAsEventListener(this);
    },
    getCartPage: function () {
        if (!this.objCartPage) {
            if ($$(this.cartPage).first()) {
                this.objCartPage = $$(this.cartPage).first();
            }
        }
        return this.objCartPage;
    },
    addToCartHandle: function (requestUrl, params) {
        this.url = requestUrl;
        if (window.location.href.match('https://') && !requestUrl.match('https://'))
            requestUrl = requestUrl.replace('http://', 'https://');
        if (!window.location.href.match('https://') && requestUrl.match('https://'))
            requestUrl = requestUrl.replace('https://', 'http://');
        if (requestUrl.indexOf('?') != -1)
            requestUrl += '&isajaxfreecart=true';
        else
            requestUrl += '?isajaxfreecart=true';
        if (this.getCartPage())
            requestUrl += '&isajaxfreecartpage=1';
        if (this.links)
            requestUrl += '&ajaxfreelinks=1';

        // $(this.ajaxMask).show();
        this.responseCache = '';
        this.requestAjax = new Ajax.Request(requestUrl, {
            method: 'post',
            postBody: params,
            parameters: params,
            onException: function (xhr, e) {
				console.log(e);
                $(this.ajaxMask).hide();
                $(this.ajaxPopup).hide();
                window.location.href = this.url;
            },
            onComplete: this.ajaxOnComplete
        });
    },
    cancelRequest: function () {
        if (typeof this.requestAjax == 'object') {
            this.requestAjax.transport.abort();
        }
    },
    ajaxOnComplete: function (xhr) {
        if (xhr.responseText.isJSON()) {
            var response = xhr.responseText.evalJSON();
            if (response.hasOptions) {
                if (response.redirectUrl)
                    this.addToCartHandle(response.redirectUrl, '');
                else
                    this.popupContentWindow(response);
            } else {
                if (this.allowFinish) {
                    this.addToCartFinish(response);
                } else {
                    this.responseCache = response;
                }
            }
        } else {
            $(this.ajaxMask).hide();
            $(this.ajaxPopup).hide();
            window.location.href = this.url;
        }
    },
    addToCartFinish: function (response) {
        if (response.qtycart_html) {
            reloadQty(response);
        }
        if (response.minicart_html) {
            reloadCartMinicart(response);
        }

        if (response.is_checkout && response.is_checkout==1) {
            if ($D('#cart-sidebar .remove')) {
                $D('#cart-sidebar .remove').replaceWith( "<span>Remove Item</span>" );
            }
        }

        if (this.getCartPage() && response.cartPage) {
            if (response.emptyCart) {
                this.getCartPage().update(response.cartPage);
            } else {
                $(this.popupContent).innerHTML = response.cartPage;
                ajaxcartUpdateCartHtml(this.getCartPage(), $(this.popupContent));
                $(this.popupContent).innerHTML = '';
                this.updateJscartEvent();
            }
        }
        if (this.links && response.ajaxlinks) {
            this.links.update(response.ajaxlinks);
            this.links.innerHTML = this.links.firstChild.innerHTML;
        }
        if (response.grand_html) {
            reloadTotalPG(response);
        }
        if (response.cart_html) {
            reloadCartPG(response);
        }

        $(this.ajaxMask).hide();
        $(this.ajaxPopup).hide();
        if (response.catalog_gift_html) {
            reloadCatalogRule(response);
        }
        if (response.shopping_gift_html) {
            reloadShoppingCartRule(response);
        }
        if (response.review_html) {
            reloadReviewPG(response);
        }
        if (response.message_html) {
            addMessagePG(response);
        }
        if(!response.has_catalog && !response.has_shopping_cart)
            $D('#promotional-gift-items-add').hide();
        else{
			if(response.actions){
				var actionArray = response.actions;
				if(actionArray && parseInt(actionArray.length) == 0){
					if($D('#promotional-gift-items-add'))
						$D('#promotional-gift-items-add').hide();
				}
				if($('number_of_rules')){
					$('number_of_rules').value = parseInt(actionArray.length);
					if(!response.has_catalog){
						$('number_of_rules').setAttribute('has_catalog','false');
					}
				}
			} 
			else
				$D('#promotional-gift-items-add').show();
		}
    },
    popupContentWindow: function (response) {
        if (response.optionjs && !this.preLoadAjax) {
            for (var i = 0; i < response.optionjs.length; i++) {
                var pattern = 'script[src="' + response.optionjs[i] + '"]';
                if ($$(pattern).first())
                    continue;
                this.jsSource[this.jsSource.length] = response.optionjs[i];
            }
        }
        if (response.optionhtml) {
//            $(this.popupContent).innerHTML += response.optionhtml;
//            this.jsCache = response.optionhtml.extractScripts();
            pContent = $(this.popupContent);
            if (pContent.down('form')) {
                pContent.removeChild(pContent.down('form'));
            }
            pContent.innerHTML += response.optionhtml;
            if (typeof ajaxcartTemplateJs != 'undefined')
                ajaxcartTemplateJs();
            this.jsCache = response.optionhtml.extractScripts();
        }
        if (response.giftvoucher) {
            pContent1 = $$('.ajaxcart-preload')[0];//alert(pContent1);
            var pContent2 = $$('.product-image')[0];
            var pContent3 = document.createElement('div');
            pContent3.className = 'product-image-box';
            pContent3.setStyle("float: left; width:150px; height:150px");
            pContent3.innerHTML = response.giftvoucher;

            pContent1.replaceChild(pContent3, pContent2);
            var pContent5 = $$('.giftcard-change-image')[0];
            pContent5.setStyle("width:300px; height:300px");
            var pContent6 = $('giftcard-template-left');
            pContent6.setStyle("width:300px; height:300px");
            var pContent7 = $('giftcard-template-top');
            pContent7.setStyle("width:300px; height:300px");
            $('giftcard-template-back').hide();
            $('giftcard-template-top').hide();
            $('giftcard-template-left').hide();
            $('ajaxcart-content').setStyle("width:400px;");
        }
        if (this.preLoadAjax) {
            this.addJsSource();
        } else {
            this.intervalCache = setInterval(this.addJsSource, 500);
            this.addJsSource();
        }
    },
    addJsSource: function () {
        if (this.jsCount == this.jsSource.length) {
            this.jsSource = [];
            this.jsCount = 0;
            clearInterval(this.intervalCache);
            this.addJsScript();
        } else {
            var headDoc = $$('head').first();
            var jsElement = new Element('script');
            jsElement.src = this.jsSource[this.jsCount];
            headDoc.appendChild(jsElement);
            this.jsCount++;
        }
    },
    addJsScript: function () {
        if (this.jsCache.length == 0)
            return false;
        try {
            for (var i = 0; i < this.jsCache.length; i++) {
                var script = this.jsCache[i];
                var headDoc = $$('head').first();
                var jsElement = new Element('script');
                jsElement.type = 'text/javascript';
                jsElement.text = script;
                headDoc.appendChild(jsElement);
            }
            this.jsCache = [];
            $(this.ajaxMask).hide();
            $(this.ajaxPopup).show();
            var content = $(this.popupContent);
            this.updatePopupBox(content);
            ajaxMoreTemplateJs();
        } catch (e) {
        }
    },
    updateJscartEvent: function () {
        ajaxUpdateFormAction();
    },
    updatePopupBox: function (content) {
        content.style.removeProperty ? content.style.removeProperty('top') : content.style.removeAttribute('top');
        if (content.offsetHeight + content.offsetTop > document.viewport.getHeight() - 30) {
            content.style.position = 'absolute';
            content.style.top = document.viewport.getScrollOffsets()[1] + 10 + 'px';
        } else {
            content.style.position = 'fixed';
        }
        var ajaxcartContent = $('giftcard-template-back');
        if (ajaxcartContent != null) {
            content.style.position = 'absolute';
            changeTemplate($('giftcard_template_select'));
            $('giftcard-preview-button-add').hide();
        }
        if (content.up('.ajaxcart')) {
            content.up('.ajaxcart').style.width = content.getWidth() + 'px';
        }
    }
}