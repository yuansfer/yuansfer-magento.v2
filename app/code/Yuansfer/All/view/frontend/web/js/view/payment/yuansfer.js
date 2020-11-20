define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';
        var config = window.checkoutConfig.payment,
            alipayType = 'yuansfer_alipay',
            unionpayType = 'yuansfer_unionpay',
            wechatpayType = 'yuansfer_wechatpay',
            creditcardType = 'yuansfer_creditcard',
            paypalType = 'yuansfer_paypal',
            venmoType = 'yuansfer_venmo';

            if(config[alipayType].isActive) {
                rendererList.push(
                    {
                        type: alipayType,
                        component: 'Yuansfer_All/js/view/payment/method-renderer/yuansfer_alipay'
                    }
                );
            }
            if(config[unionpayType].isActive) {
                rendererList.push(
                    {
                        type: unionpayType,
                        component: 'Yuansfer_All/js/view/payment/method-renderer/yuansfer_unionpay'
                    }
                );
            }
            if(config[wechatpayType].isActive) {
                rendererList.push(
                    {
                        type: wechatpayType,
                        component: 'Yuansfer_All/js/view/payment/method-renderer/yuansfer_wechatpay'
                    }
                );
            }
            if(config[creditcardType].isActive) {
                rendererList.push(
                    {
                        type: creditcardType,
                        component: 'Yuansfer_All/js/view/payment/method-renderer/yuansfer_creditcard'
                    }
                );
            }
            if(config[paypalType].isActive) {
                rendererList.push(
                    {
                        type: paypalType,
                        component: 'Yuansfer_All/js/view/payment/method-renderer/yuansfer_paypal'
                    }
                );
            }
            if(config[venmoType].isActive) {
                rendererList.push(
                    {
                      type: venmoType,
                      component: 'Yuansfer_All/js/view/payment/method-renderer/yuansfer_venmo'
                    }
                );
            }

        /** Add view logic here if needed */
        return Component.extend({});
    });
