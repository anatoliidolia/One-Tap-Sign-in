define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';

    return function (config) {
        window.googleLoginEndpoint = function (googleUser) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", window.BASE_URL + "onetaplogin/checkout/response", true);
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Refresh customer + cart sections
                            customerData.reload(['customer', 'cart'], true);
                        } else {
                            console.error('Google login failed:', response.message);
                        }
                    } catch (e) {
                        console.error('Invalid JSON response', e);
                    }
                }
            };
            const formData = new FormData();
            formData.append("id_token", googleUser.credential);
            xhr.send(formData);
        };

        $(function () {
            const interval = setInterval(function () {
                const container = $('#credential_picker_container');
                if (container.length) {
                    container.addClass(config.positionClass);
                    clearInterval(interval);
                }
            }, 100);
        });
    };
});
