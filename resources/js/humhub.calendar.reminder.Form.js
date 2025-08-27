/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

humhub.module('calendar.reminder.Form', function (module, require, $) {
    var Widget = require('ui.widget').Widget;
    var client = require('client');
    var additions = require('ui.additions');

    var SELECTOR_ITEMS = '.calendar-reminder-items';
    var SELECTOR_ITEM = '[data-reminder-index]';
    var SELECTOR_DEFAULT_ITEMS = '.calendar-reminder-item-defaults';
    var SELECTOR_REMINDER_TYPE_DROPDOWN = '#remindersettings-remindertype';
    var SELECTOR_BUTTON = '[data-action-click]';

    var REMINDER_TYPE_NONE = 0;
    var REMINDER_TYPE_DEFAULTS = 1;
    var REMINDER_TYPE_CUSTOM = 2;

    var Form = Widget.extend();

    Form.prototype.init = function() {
        this.checkMaxReminder();
        this.checkRemidnerType();
    };

    Form.prototype.checkMaxReminder = function() {
        var rows = this.$.find(SELECTOR_ITEM);

        this.$.find(SELECTOR_BUTTON).data('action-click', 'delete')
            .removeClass('btn-primary').addClass('btn-danger')
            .find('i')
            .removeClass('fa-plus').addClass('fa-times');

        if (rows.length < this.options.maxReminder) {
            rows.last().find(SELECTOR_BUTTON).data('action-click', 'add')
                .removeClass('btn-danger').addClass('btn-primary')
                .find('i')
                .removeClass('fa-times').addClass('fa-plus');
        }
    };

    Form.prototype.checkRemidnerType = function() {
        var $reminderType = $(SELECTOR_REMINDER_TYPE_DROPDOWN).val();

        if($reminderType == REMINDER_TYPE_NONE) {
            $(SELECTOR_ITEMS).hide();
            $(SELECTOR_DEFAULT_ITEMS).hide();
        } else if($reminderType == REMINDER_TYPE_CUSTOM) {
            $(SELECTOR_ITEMS).show();
            $(SELECTOR_DEFAULT_ITEMS).hide();
        } else if($reminderType == REMINDER_TYPE_DEFAULTS) {
            $(SELECTOR_ITEMS).hide();
            $(SELECTOR_DEFAULT_ITEMS).show();
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
            $(this).attr('name', $(this).attr('name').replace(/^CalendarReminder\[\d]/, 'CalendarReminder[' + $lastIndex + ']'));
        });
        $newRow.find('[id]').each(function() {
            $(this).attr('id', $(this).attr('id').replace(/^calendarreminder-\d/, 'calendarreminder-' + $lastIndex));
        });
        $newRow.find('.select2-container').remove();
        additions.applyTo($newRow);

        $newRow.insertAfter($triggerRow);

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
