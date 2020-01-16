/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

humhub.module('calendar', function (module, require, $) {
        var Widget = require('ui.widget').Widget;
        var client = require('client');
        var util = require('util');
        var object = util.object;
        var modal = require('ui.modal');
        var action = require('action');
        var Content = require('content').Content;

        var Calendar = Widget.extend();


        var Form = Widget.extend();

        Form.RECUR_EDIT_MODE_CREATE = 0;
        Form.RECUR_EDIT_MODE_THIS = 1;
        Form.RECUR_EDIT_MODE_FOLLOWING = 2;
        Form.RECUR_EDIT_MODE_ALL = 3;

        Form.prototype.init = function () {
            modal.global.$.find('.tab-basic').on('shown.bs.tab', function (e) {
                $('#calendarentry-title').focus();
            });

            modal.global.$.find('.tab-participation').on('shown.bs.tab', function (e) {
                $('#calendarentry-participation_mode').focus();
            });

            this.initTimeInput();
        };

        Form.prototype.setEditMode = function (evt) {
            var mode = evt.$trigger.data('editMode');

            if (mode == Form.RECUR_EDIT_MODE_THIS) {
                $('.tab-recurrence').hide();
                $('.field-calendarentryform-is_public').hide();
            } else {
                $('.tab-recurrence').show();
                $('.field-calendarentryform-is_public').show();
            }

            this.$.find('.calendar-edit-mode-back').show();
            this.$.find('.recurrence-edit-type').hide();
            this.$.find('.calendar-entry-form-tabs').show();
            this.$.find('#recurrenceEditMode').val(mode);
        };

        Form.prototype.showEditModes = function (evt) {
            this.$.find('.calendar-edit-mode-back').hide();
            this.$.find('.recurrence-edit-type').show();
            this.$.find('.calendar-entry-form-tabs').hide();
        };

        Form.prototype.initTimeInput = function (evt) {
            var $timeFields = modal.global.$.find('.timeField');
            var $timeInputs = $timeFields.find('.form-control');
            $timeInputs.each(function () {
                var $this = $(this);
                if ($this.prop('disabled')) {
                    $this.data('oldVal', $this.val()).val('');
                }
            });
        };

        Form.prototype.toggleDateTime = function (evt) {
            var $timeFields = modal.global.$.find('.timeField');
            var $timeInputs = $timeFields.find('.form-control');
            var $timeZoneInput = modal.global.$.find('.timeZoneField');
            if (evt.$trigger.prop('checked')) {
                $timeInputs.prop('disabled', true);
                $timeInputs.each(function () {
                    $(this).data('oldVal', $(this).val()).val('');
                });
                $timeFields.css('opacity', '0.2');
                $timeZoneInput.hide();

            } else {
                $timeInputs.each(function () {
                    var $this = $(this);
                    if ($this.data('oldVal')) {
                        $this.val($this.data('oldVal'));
                    }
                });
                $timeInputs.prop('disabled', false);
                $timeFields.css('opacity', '1.0');
                $timeZoneInput.show();
            }
        };

        Form.prototype.changeTimezone = function (evt) {
            var $dropDown = this.$.find('.timeZoneInput');
            this.$.find('.calendar-timezone').text($dropDown.find('option:selected').text());
            $dropDown.hide();
        };

        Form.prototype.toggleTimezoneInput = function (evt) {
            this.$.find('.timeZoneInput').fadeToggle();
        };

        Form.prototype.changeParticipationMode = function (evt) {
            if (evt.$trigger.val() == 0) {
                this.$.find('.participationOnly').fadeOut('fast');
            } else {
                this.$.find('.participationOnly').fadeIn('fast');
            }
        };

        Form.prototype.changeEventType = function (evt) {
            var $selected = evt.$trigger.find(':selected');
            if ($selected.data('type-color')) {
                $('.colorpicker-element').data('colorpicker').color.setColor($selected.data('type-color'));
                $('.colorpicker-element').data('colorpicker').update();
            }
        };

        var CalendarEntry = function (id) {
            Content.call(this, id);
        };

        object.inherits(CalendarEntry, Content);

        CalendarEntry.prototype.toggleClose = function (event) {
            this.update(client.post(event));
        };

        CalendarEntry.prototype.reload = function (event) {
            return this.parent().reload();
        };

        CalendarEntry.prototype.update = function (update) {
            this.loader();
            update.then($.proxy(this.handleUpdateSuccess, this))
                .catch(CalendarEntry.handleUpdateError)
                .finally($.proxy(this.loader, this, false));
        };

        CalendarEntry.prototype.loader = function ($loader) {
            this.streamEntry().loader($loader);
        };

        CalendarEntry.prototype.handleUpdateSuccess = function (response) {
            var streamEntry = this.streamEntry();
            return streamEntry.replace(response.output).catch(function (e) {
                module.log.error(e, true);
            });
        };

        CalendarEntry.handleUpdateError = function (e) {
            module.log.error(e, true);
        };

        CalendarEntry.prototype.streamEntry = function () {
            return this.parent();
        };

        /**
         * Action respond to calendar entry (participation)
         * @param evt
         */
        var respond = function (evt) {
            evt.block = action.BLOCK_MANUAL;
            client.post(evt).then(function (response) {
                if (response.success) {
                    var entry = Widget.closest(evt.$trigger);
                    entry.reload().then(function () {
                        module.log.success('saved');
                    });
                } else {
                    module.log.error(e, true);
                    evt.finish();
                }
            }).catch(function (e) {
                module.log.error(e, true);
                evt.finish();
            });
        };

        var editModal = function (evt) {
            var streamEntry = Widget.closest(evt.$trigger);
            streamEntry.loader();
            modal.load(evt).then(function (response) {
                modal.global.$.one('submitted', function () {
                    getCalendar().fetch();
                });
            }).catch(function (e) {
                module.log.error(e, true);
            });
        };

        var getCalendar = function () {
            return Widget.instance('#calendar');
        };

        var deleteEvent = function (evt) {
            var streamEntry = Widget.closest(evt.$trigger);
            streamEntry.loader();
            modal.confirm().then(function (confirm) {
                if (confirm) {
                    client.post(evt).then(function () {
                        modal.global.close();
                    }).catch(function (e) {
                        module.log.error(e, true);
                    });
                } else {
                    var streamEntry = Widget.closest(evt.$trigger);
                    streamEntry.loader(false);
                }
            }).finally(function () {
                evt.finish();
            });
        };

        module.export({
            Calendar: Calendar,
            respond: respond,
            editModal: editModal,
            deleteEvent: deleteEvent,
            CalendarEntry: CalendarEntry,
            Form: Form
        });
    }
);