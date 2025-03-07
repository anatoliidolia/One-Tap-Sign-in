define(['jquery'], function ($) {
    'use strict';

    return function (config) {
        function googleLoginEndpoint(googleUser) {
            const ajax = new XMLHttpRequest();

            console.log(config.ajaxUrl);
            ajax.open("POST", config.ajaxUrl, true);
            ajax.onreadystatechange = function () {
                if (this.readyState === 4 && this.status === 200) {
                    window.location.reload();
                }
            };
            let formData = new FormData();
            formData.append("id_token", googleUser.credential);
            ajax.send(formData);
        }

        $(document).ready(function () {
            let myInterval = setInterval(function () {
                let container = $('#credential_picker_container');
                if (container.length) {
                    container.addClass(config.position || 'default-position');
                    clearInterval(myInterval);
                }
            }, 100);
        });

        window.googleLoginEndpoint = googleLoginEndpoint;
    };
});
