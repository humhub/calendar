/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

humhub.module('calendar.reminder.Form', function (module, require, $) {
    var Widget = require('ui.widget').Widget;
    var client = require('client');

    var SELECTOR_ITEMS = '.calendar-reminder-items';
    var SELECTOR_USE_DEFAULT_CHECKBOX = '#remindersettings-usedefaults';
    var SELECTOR_ADD_BUTTON = '.btn-primary[data-action-click="add"]';

    var Form = Widget.extend();

    Form.prototype.init = function(evt) {
        this.checkMaxReminder();
        this.checkUseDefaults();
    };

    Form.prototype.checkMaxReminder = function() {
        var count = this.$.find(SELECTOR_ITEMS).find('[data-reminder-index]').length;
        if(count >= this.options.maxReminder) {
            this.$.find(SELECTOR_ADD_BUTTON).hide();
        } else {
            this.$.find(SELECTOR_ADD_BUTTON).show();
        }
    };

    Form.prototype.checkUseDefaults = function() {
        var $checkbox = this.$.find(SELECTOR_USE_DEFAULT_CHECKBOX);

        if(!$checkbox.length) {
            return;
        }

        if(this.$.find(SELECTOR_USE_DEFAULT_CHECKBOX).is(':checked')) {
            this.$.find(SELECTOR_ITEMS).hide();
        } else {
            this.$.find(SELECTOR_ITEMS).show();
        }
    };

    Form.prototype.delete = function(evt) {
        var that = this;
        evt.$trigger.closest('.row').fadeOut('fast', function() {
            $(this).remove();
            that.checkMaxReminder();
        });

    };

    Form.prototype.add = function(evt) {
        var $triggerRow = evt.$trigger.closest('.row');
        var $lastIndex = parseInt($triggerRow.attr('data-reminder-index'));
        var $newRow = $triggerRow.clone().attr('data-reminder-index', ++$lastIndex);

        $newRow.find('[name]').each(function() {
            var name = $(this).attr('name').replace(/CalendarReminder\[[0-9]]/, 'CalendarReminder['+$lastIndex+']');
            $(this).attr('name', name);
        });

        $newRow.insertAfter($triggerRow);

        evt.$trigger.data('action-click', 'delete')
            .removeClass('btn-primary')
            .addClass('btn-danger')
            .find('i')
            .removeClass('fa-plus')
            .addClass('fa-times');

        this.checkMaxReminder();
    };

    Form.prototype.reset = function(evt) {
        var $settings = this.$.find('.calendar-reminder-items');
        client.post(evt).then(function(response) {
            $settings.replaceWith($(response.html).find('.calendar-reminder-items'));
            module.log.success('saved');
        }).catch(function(e) {
            module.log.error(e, true);
        });
    };


    module.export = Form;
});