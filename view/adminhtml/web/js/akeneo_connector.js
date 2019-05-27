/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */

/*global define*/
define(['jquery'], function ($) {
    'use strict';

    return {
        options: {
            type: null,
            step: 0,
            runUrl: null,
            console: null,
            identifier: null
        },

        init: function (url, console) {
            this.options.runUrl = url;
            this.console = $(console);
        },

        type: function (type, object) {
            this.options.type = type;
            this.step('type', $(object));
        },

        step: function (type, object) {
            /* Reset step */
            this.options.step = 0;

            /* Reset identifier */
            this.options.identifier = null;

            /* Enable button */
            this.disabledImport(false);

            /* Reset Console */
            this.cleanConsole();

            /* Reset active element */
            $('.import-' + type).each(function () {
                $(this).removeClass('active');
            });

            /* Active element */
            object.addClass('active');
        },

        run: function () {
            var akeneoConnector = this;

            akeneoConnector.disabledImport(true);

            if (akeneoConnector.options.type && akeneoConnector.options.runUrl) {
                $.ajax({
                    url: akeneoConnector.options.runUrl,
                    type: 'post',
                    context: this,
                    data: {
                        'code': akeneoConnector.options.type,
                        'step': akeneoConnector.options.step,
                        'identifier': akeneoConnector.options.identifier
                    },
                    success: function (response) {
                        akeneoConnector.removeWaiting();

                        if (response.identifier) {
                            akeneoConnector.options.identifier = response.identifier;
                        }

                        if (akeneoConnector.options.step === 0) {
                            akeneoConnector.listElement(response.comment, false);
                        }

                        if (response.message) {
                            if (response.status === false) {
                                akeneoConnector.listElement(response.message, 'error');
                            } else {
                                akeneoConnector.listElement(response.message, 'success');
                            }
                        }

                        if (response.continue) {
                            akeneoConnector.listElement(response.next, 'waiting');
                            akeneoConnector.options.step = akeneoConnector.options.step + 1;
                            akeneoConnector.run();
                        }

                        akeneoConnector.console.scrollTop(100000);
                    }
                });
            }
        },

        removeWaiting: function () {
            this.console.find('li').removeClass('waiting');
        },

        listElement: function (content, elementClass) {
            this.console.append(
                '<li' + (elementClass ? ' class="' + elementClass + '"' : '') + '>' + content + '</li>'
            );
        },

        cleanConsole: function () {
            this.console.html(
                '<li class="selected">' +
                (this.options.type ? this.options.type + ' ' : '') +
                '</li>'
            );
        },

        disabledImport: function (enable) {
            $('.akeneo-connector-uploader').find('button').prop("disabled", enable);
        }
    }
});
