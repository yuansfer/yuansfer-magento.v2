define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';
        var config = window.checkoutConfig.payment,
            alipayType = 'pockyt_alipay',
            unionpayType = 'pockyt_unionpay',
            wechatpayType = 'pockyt_wechatpay',
            creditcardType = 'pockyt_creditcard',
            paypalType = 'pockyt_paypal',
            venmoType = 'pockyt_venmo';

            if(config[alipayType].isActive == "1") {
                rendererList.push(
                    {
                        type: alipayType,
                        component: 'Pockyt_All/js/view/payment/method-renderer/pockyt_alipay'
                    }
                );
            }
            if(config[unionpayType].isActive == "1") {
                rendererList.push(
                    {
                        type: unionpayType,
                        component: 'Pockyt_All/js/view/payment/method-renderer/pockyt_unionpay'
                    }
                );
            }
            if(config[wechatpayType].isActive == "1") {
                rendererList.push(
                    {
                        type: wechatpayType,
                        component: 'Pockyt_All/js/view/payment/method-renderer/pockyt_wechatpay'
                    }
                );
            }
            if(config[creditcardType].isActive == "1") {
                rendererList.push(
                    {
                        type: creditcardType,
                        component: 'Pockyt_All/js/view/payment/method-renderer/pockyt_creditcard'
                    }
                );
            }
            if(config[paypalType].isActive == "1") {
                rendererList.push(
                    {
                        type: paypalType,
                        component: 'Pockyt_All/js/view/payment/method-renderer/pockyt_paypal'
                    }
                );
            }
            if(config[venmoType].isActive == "1") {
                rendererList.push(
                    {
                      type: venmoType,
                      component: 'Pockyt_All/js/view/payment/method-renderer/pockyt_venmo'
                    }
                );
            }

        /** Add view logic here if needed */
        return Component.extend({});
    });
