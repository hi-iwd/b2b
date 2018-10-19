/*
 * Copyright © 2018 IWD Agency - All rights reserved.
 * See LICENSE.txt bundled with this module for license details.
 */
/*jshint browser:true jquery:true*/
/*global FORM_KEY*/
require([
    'jquery',
    'jquery/ui',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($) {
    'use strict';

    window.IWDB2B = window.IWDB2B||{};
    window.IWDB2B.menuSelectors = [
        '.item-b2b-files',
        '.item-b2b-roles',
        '.item-b2b-productgrid-index',
        '.item-b2b-salesrep-index',
        '.item-b2b-salesrep-report-order',
        '.item-b2b-shared-catalog-index'
    ];
    window.IWDB2B.additionalSelectors = [
        '.b2b-pro-form-tab'
    ];
    window.IWDB2B.configSelector = '.section-config.iwd-b2b-pro-section';
    window.IWDB2B.configImageSelector = '.section-config.iwd-b2b-pro-section .entry-edit-head-link';
    window.IWDB2B.freeVersionContent = $.mage.__(
        'Take your business to the next level with B2B Suite Pro, a robust set of tools that makes it ' +
        'easier than ever to integrate your existing Magento store into a full-fledged wholesale ' +
        'experience that will delight your customers. And the best part is – you can try it without ' +
        'worry or hassle, with flexible subscription plans that you can cancel at any time.\n' +
        '\n' +
        '• B2B Dashboard, a hub for your individual wholesellers.\n' +
        '• Bulk order faster with our industry-leading product matrix.\n' +
        '• Upload orders via CSV.\n' +
        '• A single source for all of your downloadable marketing materials.\n' +
        '• Account credit limits and pricing per accounts.\n' +
        '• And more...'
    );
    window.IWDB2B.freeVersionPayUrl = 'https://www.iwdagency.com/extensions/b2b-ecommerce-suite-m1-m2.html';

    // Dialog window
    window.IWDB2B.dialog = function (elem) {
        $(elem).append( "<div class='free-b2b-dialog-content'></div>");
        var widget = $(elem).children('div').text(window.IWDB2B.freeVersionContent);
        widget.modal({
            type: 'popup',
            modalClass: 'mage-free-version-dialog form-inline',
            title: $.mage.__('B2B Suite Pro'),
            buttons: [{
                text: $.mage.__('Cancel'),
                class: 'action',
                click: function (e) {
                    widget.modal('closeModal');
                }
            }, {
                text: $.mage.__('Try It Now'),
                class: 'action-primary',
                click: function (e) {
                    window.open(window.IWDB2B.freeVersionPayUrl,'_blank');
                }
            }]
        }).trigger('openModal');
    };

    // Disable all free input fields in configs
    $(window.IWDB2B.configSelector).find(':input').prop('disabled', true);
    $(document).on('click', window.IWDB2B.configSelector, function () {
        $(this).find(':input').prop('disabled', true);
    });

    // dialog click event
    $(document).on('click', window.IWDB2B.configImageSelector, function (e) {
        e.preventDefault();
        e.stopPropagation();
        window.IWDB2B.dialog(this);
    });

    $(document).on('click', '.b2b-pay-button', function () {
        window.open(window.IWDB2B.freeVersionPayUrl,'_blank');
    });

    $.each(window.IWDB2B.menuSelectors, function (i, selector) {
        $(selector).addClass('section-config iwd-b2b-pro-section').append(
            // Add config icons for menu selectors
            '<span class="entry-edit-head-link"></span>'
        );
        $(document).on('click', selector, function (e) {
            e.preventDefault();
            e.stopPropagation();
            window.IWDB2B.dialog(this);
        });
    });

    $.each(window.IWDB2B.additionalSelectors, function (i, selector) {
        $(selector).addClass('section-config iwd-b2b-pro-section').append(
            '<span class="entry-edit-head-link"></span>'
        );
    });

});
