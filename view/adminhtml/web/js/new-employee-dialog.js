/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global FORM_KEY*/
define([
    'jquery',
    'jquery/ui',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'mage/backend/validation'
], function ($) {
    'use strict';

    $.widget('mage.newEmployeeDialog', {
        _create: function () {
            var widget = this;

            var newEmployeeForm = $('#new_employee_form');
            newEmployeeForm.mage('validation', {
                errorPlacement: function (error, element) {
                    error.insertAfter(element);
                }
            });
            this.element.modal({
                type: 'slide',
                modalClass: 'mage-new-employee-dialog form-inline',
                title: $.mage.__('Add Employee'),
                opened: function () {
                    $('#new_employee_form input').val('');

                    $('#new_employee_messages').html('');
                    $('.field-new_employee_messages').hide(); 
                },
                closed: function () {
                    var validationOptions = newEmployeeForm.validation('option');

                    $('#new_employee_form input').val('');

                    newEmployeeForm.validation('clearError');
                },
                buttons: [{
                    text: $.mage.__('Save'),
                    class: 'action-primary',
                    click: function (e) {
                        if (!newEmployeeForm.valid()) {
                            return;
                        }
                        
                        window.IWDB2B.Employee.save(widget, e, 1);                        
                    }
                },{
                    text: $.mage.__('Cancel'),
                    class: 'action',
                    click: function (e) {
                        $(widget.element).modal('closeModal');
                    }                 
                },{
                    text: $.mage.__('Save and Continue Edit'),
                    class: 'action',
                    click: function (e) {
                        if (!newEmployeeForm.valid()) {
                            return;
                        }
                        
                        window.IWDB2B.Employee.save(widget, e, 2);                        
                    }                    
                }]
            });
        }
    });
    
    window.IWDB2B = window.IWDB2B||{};
    
    window.IWDB2B.Employee = {

            save: function(widget, e, btn){
                
                var newEmployeeForm = $('#new_employee_form');
                
                $('#new_category_messages').html('');
                $('.field-new_employee_messages').hide();
                
                var thisButton = $(e.currentTarget);

                var formData = newEmployeeForm.serializeArray();

                formData.push({"name":"form_key", "value":FORM_KEY});
                formData.push({"name":"return_session_messages_only", "value":1});
                
                if(btn == 2)
                    formData.push({"name":"continue_edit", "value":1});

                thisButton.parents('.page-actions-buttons').find('button').prop('disabled', true);
                
                $.ajax({
                    type: 'POST',
                    url: widget.options.saveEmployeeUrl,
                    data: formData,
                    dataType: 'json',
                    context: $('body')
                }).success(function (data) {
                    if (!data.error) {
                        $(widget.element).modal('closeModal');                                
                        if (data.location) {
                            window.location.href = data.location;
                        }
                        else
                            location.reload();
                    } else {
                        var err_html = '<div class="message message-error error"><div>'+data.messages+'</div></div>';
                        $('#new_employee_messages').html(err_html);
                        $('.field-new_employee_messages').show();
                    }
                }).complete(
                    function () {
                        thisButton.parents('.page-actions-buttons').find('button').prop('disabled', false);
                    }
                );
                
            }

    };
    

    return $.mage.newEmployeeDialog;
});
