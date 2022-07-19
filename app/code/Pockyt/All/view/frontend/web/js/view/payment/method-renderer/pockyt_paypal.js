define([
    'ko',
    'jquery',
    'Magento_Checkout/js/view/payment/default',
    'Pockyt_All/js/action/set-payment-method-action'
], function (ko, $, Component, setPaymentMethodAction) {
    'use strict';

    return Component.extend({
        defaults: {
            redirectAfterPlaceOrder: false,
            template: 'Pockyt_All/payment/form'
        },
        afterPlaceOrder: function () {
            setPaymentMethodAction(this.messageContainer);
            return false;
        },
        getCode: function() {
            return 'pockyt_paypal';
        },
        getTitle: function() {
            var config = window.checkoutConfig.payment;
            return config['pockyt_paypal'].title;
        },
        getImageUrl: function() {
            var config = window.checkoutConfig.payment;
            return config['pockyt_paypal'].imageUrl;
        },
        getImageWidth: function() {
            var config = window.checkoutConfig.payment;
            return config['pockyt_paypal'].imageWidth;
        }
    });
});
