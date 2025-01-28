/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

humhub.module('calendar', function (module, require, $) {
        var Widget = require('ui.widget').Widget;
        var client = require('client');
        var modal = require('ui.modal');
        var action = require('action');
        var Content = require('content').Content;
        var event = require('event');
        var StreamEntry = require('stream').StreamEntry;
        var status = require('ui.status');

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

            this.initDateTimeCorrector();
            this.initTimeInput();
            this.initSubmitAction();
        };

        Form.prototype.initDateTimeCorrector = function () {
            var startPrefix = '#calendarentryform-start_';
            var endPrefix = '#calendarentryform-end_';

            var convertTimeToMinutes = function(time) {
                time = time.split(':');
                var hours = parseInt(time[0]);
                if (hours === 12 && time[1].includes('AM')) {
                    hours = 0;
                } else {
                    hours += time[1].includes('PM') && hours < 12 ? 12 : 0;
                }
                return hours * 60 + parseInt(time[1]);
            }

            var convertMinutesToTime = function(time, mode) {
                var minutes = time % 60;
                var hours = (time - minutes) / 60;
                var modeSuffix = '';
                if (mode === '12h') {
                    modeSuffix = hours < 12 ? ' AM' : ' PM';
                    if (hours === 0) {
                        hours = 12;
                    } else if (hours > 12) {
                        hours -= 12;
                    }
                }
                if (hours < 10) {
                    hours = '0' + hours;
                }
                if (minutes < 10) {
                    minutes = '0' + minutes;
                }
                return hours + ':' + minutes + modeSuffix;
            }

            var getDateData = function() {
                return {
                    start: $(startPrefix + 'date').datepicker('getDate').getTime(),
                    end: $(endPrefix + 'date').datepicker('getDate').getTime(),
                }
            }

            var getTimeData = function() {
                return {
                    start: convertTimeToMinutes($(startPrefix + 'time').val()),
                    end: convertTimeToMinutes($(endPrefix + 'time').val()),
                    mode: $(startPrefix + 'time').val().includes('M') ? '12h' : '24h',
                }
            }

            var fixDateTime = function(prefix, time, mode) {
                var dayMinutes = 24 * 60;
                if (time < 0 || time >= dayMinutes) {
                    // Shift date to +1/-1 day if time is out of day
                    var date = new Date($(prefix + 'date').datepicker('getDate'));
                    date.setDate(date.getDate() + (time < 0 ? -1 : 1));
                    $(prefix + 'date').datepicker('setDate', date);
                    time = time + (time < 0 ? dayMinutes : -dayMinutes);
                }
                $(prefix + 'time').val(convertMinutesToTime(time, mode));
            }

            var validateTime = function () {
                var date = getDateData();
                if (date.start !== date.end) {
                    return true;
                }
                var time = getTimeData();
                return time.start >= time.end ? time : true;
            }

            this.$.find(startPrefix + 'time').on('change', function () {
                var time = validateTime();
                if (time !== true) {
                    fixDateTime(endPrefix, time.start + 60, time.mode);
                }
            });

            this.$.find(endPrefix + 'time').on('change', function () {
                var time = validateTime();
                if (time !== true) {
                    fixDateTime(startPrefix, time.end - 60, time.mode);
                }
            });

            var validateDate = function () {
                var date = getDateData();
                return date.start <= date.end;
            }

            this.$.find(startPrefix + 'date').on('change', function () {
                if (!validateDate()) {
                    $(endPrefix + 'date').val($(startPrefix + 'date').val());
                }
            });

            this.$.find(endPrefix + 'date').on('change', function () {
                if (!validateDate()) {
                    $(startPrefix + 'date').val($(endPrefix + 'date').val())
                }
            });
        };

        Form.prototype.setEditMode = function (evt) {
            var mode = evt.$trigger.data('editMode');

            if (mode == Form.RECUR_EDIT_MODE_THIS) {
                $('.field-calendarentryform-is_public').hide();
            } else {
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

        Form.prototype.initTimeInput = function () {
            var $timeFields = modal.global.$.find('.timeField');
            var $timeInputs = $timeFields.find('.form-control');
            $timeInputs.each(function () {
                var $this = $(this);
                if ($this.prop('disabled')) {
                    $this.data('oldVal', $this.val()).val('');
                }
            });
        };

        Form.prototype.initSubmitAction = function () {
            modal.global.$.one('submitted', onCalEntryFormSubmitted);
        }

        var onCalEntryFormSubmitted = function (evt, response) {
            if (response.id) {
                modal.global.$.one('hidden.bs.modal', function () {
                    var entry = StreamEntry.getNodeByKey(response.id);
                    if (entry.length) {
                        entry = new StreamEntry(entry);
                        entry.reload();
                    }
                });
            }

            if (response.reloadWall) {
                event.trigger('humhub:content:newEntry', response.content, this);
                event.trigger('humhub:content:afterSubmit', response.content, this);
            } else {
                modal.global.$.one('submitted', onCalEntryFormSubmitted);
            }
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

        Form.prototype.changeEventType = function (evt) {
            var $selected = evt.$trigger.find(':selected');
            if ($selected.data('color')) {
                $('.colorpicker-element').data('colorpicker').color.setColor($selected.data('color'));
                $('.colorpicker-element').data('colorpicker').update();
            } else if (module.config['defaultEventColor']) {
                $('.colorpicker-element').data('colorpicker').color.setColor(module.config['defaultEventColor']);
                $('.colorpicker-element').data('colorpicker').update();
            }
        };

        Form.prototype.toggleRecurring = function (evt) {
            $('.calendar-entry-form-tabs .tab-recurrence').parent().toggle(evt.$trigger.is(':checked'));
        };

        Form.prototype.toggleReminder = function (evt) {
            $('.calendar-entry-form-tabs .tab-reminder').parent().toggle(evt.$trigger.is(':checked'));
        };

        var CalendarEntry = Content.extend();

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
            modal.load(evt).then(function (response) {
                modal.global.$.one('submitted', function () {
                    var calendar = getCalendar();
                    if (calendar) {
                        calendar.fetch();
                    }
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
                    client.post(evt).then(function (response) {
                        if (response.success) {
                            status.success(response.message);
                            modal.global.close();
                        } else if (response.message) {
                            status.error(response.message);
                        }
                    }).catch(function (e) {
                        module.log.error(e, true);
                        if (e.message) {
                            status.error(e.message);
                        }
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
