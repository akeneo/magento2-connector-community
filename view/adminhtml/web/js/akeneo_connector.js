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
            runProductUrl: null,
            console: null,
            identifier: null,
            currentFamily: null,
            familyCount: 0,
            families: null
        },

        init: function (url, urlProduct, console) {
            this.options.runUrl = url;
            this.options.runProductUrl = urlProduct;
            this.console = $(console);
        },

        type: function (type, object) {
            this.options.type = type;
            this.step('type', $(object));
            this.options.currentFamily = null;
            this.options.familyCount = null;
            this.options.families = null;
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

        runProduct: function () {
            var akeneoConnector = this;
            $.ajax({
                url: akeneoConnector.options.runProductUrl,
                type: 'post',
                context: this,
                data: {
                    'identifier': akeneoConnector.options.identifier
                },
                success: function (response) {
                    if (response.message) {
                        akeneoConnector.disabledImport(true);
                        akeneoConnector.listElement(response.message, 'error');
                    } else {
                        akeneoConnector.options.families = response;
                        akeneoConnector.run(akeneoConnector);
                    }
                }
            });
        },

        run: function (context = null) {
            var akeneoConnector = this;

            if (context != null) {
                var akeneoConnector = context;
            }

            if (akeneoConnector.options.currentFamily == null && akeneoConnector.options.families != null && akeneoConnector.options.families.length >= 1) {
                akeneoConnector.options.currentFamily = akeneoConnector.options.families[0];
            }

            akeneoConnector.disabledImport(true);

            if (akeneoConnector.options.type && akeneoConnector.options.runUrl) {
                $.ajax({
                    url: akeneoConnector.options.runUrl,
                    type: 'post',
                    context: this,
                    data: {
                        'code': akeneoConnector.options.type,
                        'step': akeneoConnector.options.step,
                        'identifier': akeneoConnector.options.identifier,
                        'family': akeneoConnector.options.currentFamily
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

                        if (!response.continue && akeneoConnector.options.type == "product") {
                            akeneoConnector.options.identifier = null;
                            akeneoConnector.options.step = 0;
                            akeneoConnector.options.familyCount++;

                            if (akeneoConnector.options.families != null && akeneoConnector.options.families.hasOwnProperty(akeneoConnector.options.familyCount)) {
                                akeneoConnector.options.currentFamily = akeneoConnector.options.families[akeneoConnector.options.familyCount];
                                akeneoConnector.run();
                            }
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
