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
            return 'yuansfer_creditcard';
        },
        getTitle: function() {
            var config = window.checkoutConfig.payment;
            return config['yuansfer_creditcard'].title;
        },
        getImageUrl: function() {
            var config = window.checkoutConfig.payment;
            return config['yuansfer_creditcard'].imageUrl;
        },
        getImageWidth: function() {
            var config = window.checkoutConfig.payment;
            return config['yuansfer_creditcard'].imageWidth;
        }
    });
});
