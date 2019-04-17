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
            return 'yuansfer_unionpay';
        },
        getTitle: function() {
            var config = window.checkoutConfig.payment;
            return config['yuansfer_unionpay'].title;
        },
        getImageUrl: function() {
            var config = window.checkoutConfig.payment;
            return config['yuansfer_unionpay'].imageUrl;
        },
        getImageWidth: function() {
            var config = window.checkoutConfig.payment;
            return config['yuansfer_unionpay'].imageWidth;
        }
    });
});