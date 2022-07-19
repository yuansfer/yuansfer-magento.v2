define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'mage/url',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, quote, urlBuilder, storage, errorProcessor, customer, fullScreenLoader) {
        'use strict';
        return function (messageContainer) {
            $.mage.redirect(urlBuilder.build('pockyt/securepay/redirect'));
            //window.location.href = '/pockyt/securePay/redirect';
        };
    }
);