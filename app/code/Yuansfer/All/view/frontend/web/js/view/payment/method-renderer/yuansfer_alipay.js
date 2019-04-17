define([
    'ko',
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Yuansfer_All/js/action/set-payment-method-action'
], function (ko, $, Component, setPaymentMethodAction) {
    'use strict';

    return Component.extend({
        defaults: {
            redirectAfterPlaceOrder: false,
            template: 'Yuansfer_All/payment/form'
        },
        afterPlaceOrder: function () {
            setPaymentMethodAction(this.messageContainer);
            return false;
        },
        getCode: function() {
            return 'yuansfer_alipay';
        },
        getTitle: function() {
            var config = window.checkoutConfig.payment;
            return config['yuansfer_alipay'].title;
        },
        getImageUrl: function() {
            var config = window.checkoutConfig.payment;
            return config['yuansfer_alipay'].imageUrl;
        },
        getImageWidth: function() {
            var config = window.checkoutConfig.payment;
            return config['yuansfer_alipay'].imageWidth;
        }
    });
});