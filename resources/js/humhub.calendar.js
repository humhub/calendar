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
        var string = util.string;
        var loader = require('ui.loader');
        var modal = require('ui.modal');
        var action = require('action');
        var Content = require('content').Content;

        var Calendar = Widget.extend();
        var view = require('ui.view');

        Calendar.prototype.init = function () {
            // Initial events
            this.options.events = {
                url: this.options.loadUrl,
                data: {
                    selectors: this.options.selectors,
                    filters: this.options.filters
                },
                error: function (err) {
                    module.log.error(err, true);
                }
            };

            module.log.debug('Init calendar: ', this.options);

            this.initCalendarFilter();
            this.updateCalendarFilters();
        };

        Calendar.prototype.initCalendarFilter = function () {
            var that = this;

            $(".selectorCheckbox").click(function () {
                that.updateCalendarFilters(true);
            });

            $(".filterCheckbox").click(function () {

                // Make sure responded / not resondend  filters are not checked at the same time
                if ($(this).val() == '3') {
                    $(":checkbox[value=4][name='filter']").attr("checked", false);
                }

                if ($(this).val() == '4') {
                    $(":checkbox[value=3][name='filter']").attr("checked", false);
                }

                that.updateCalendarFilters(true);
            });
        };

        Calendar.prototype.updateCalendarFilters = function (reload) {
            var selectors = [];
            var filters = [];

            $(".selectorCheckbox").each(function () {
                if ($(this).prop("checked")) {
                    selectors.push($(this).val());
                }
            });

            $(".filterCheckbox").each(function () {
                if ($(this).prop("checked")) {
                    filters.push($(this).val());
                }
            });

            this.options.events = {
                url: this.options.loadUrl + '&' + $.param({selectors: selectors, filters: filters}),
                error: function (err) {
                    module.log.error(err, true);
                }
            };


            this.initFullCalendar(reload);
        };

        Calendar.prototype.initFullCalendar = function (reload) {
            if (this.fullCalendar && reload) {
                this.fullCalendar.removeAllEventSources();
                this.fullCalendar.addEventSource(this.options.events);
                //this.fullCalendar.refetchEvents();
            } else {
                this.fullCalendar = new FullCalendar.Calendar(this.$[0], this.options);
                this.fullCalendar.render();
            }
        };

        Calendar.prototype.getDefaultOptions = function () {

            /*
             * We only want to overwrite the default button texts if already
             * translated otherwise we use default fullcalendar translation.
             */
            var buttonText = {};
            if (module.text('button.today') !== 'today') {
                buttonText.today = module.text('button.today');
            }

            if (module.text('button.month') !== 'month') {
                buttonText.month = module.text('button.month');
            }

            if (module.text('button.week') !== 'week') {
                buttonText.week = module.text('button.week');
            }

            if (module.text('button.list') !== 'list') {
                buttonText.list = module.text('button.list');
            }

            var options = {
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                buttonText: buttonText,
                plugins: ['dayGrid', 'timeGrid', 'list', 'interaction', 'bootstrap'],
                defaultView: 'dayGridMonth',
                aspectRatio: 1.5,
                canCreate: true,
                selectable: true,
                select: $.proxy(this.select, this),
                eventAllow: function () {
                    return true;
                },
                themeSystem: 'bootstrap',
                loading: $.proxy(this.loader, this),
                eventResize: $.proxy(this.resizeEvent, this),
                eventDrop: $.proxy(this.dropEvent, this),
                eventClick: $.proxy(this.clickEvent, this),
                eventRender: $.proxy(this.renderEvent, this)
            };

            if (view.isSmall()) {
                options.header = {
                    left: 'prev,next',
                    center: 'title',
                    right: 'today'
                };
                options.footer = {
                    center: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                };
            }

            return options;
        }

        Calendar.prototype.renderEvent = function (event, element) {
            var $element = $(element);
            $element.attr({title: $element.text()});
            if (event.icon) {
                if (string.startsWith(event.icon, 'fa-')) {
                    $element.find('.fc-content').prepend($('<i class="fa ' + event.icon + '"></i>'));
                }
            }
        };

        var toJsonDateFormat = function (date) {
            // YYYY-MM-DD HH:mm:ss
            var year = date.getFullYear();
            var month = leadingZero(date.getMonth() + 1);
            var day = leadingZero(date.getDate());

            var hour = leadingZero(date.getHours());
            var minutes = leadingZero(date.getMinutes());
            var seconds = leadingZero(date.getSeconds());
            return year + '-' + month + '-' + day + ' ' + hour + ':' + minutes + ':' + seconds;
        };

        var leadingZero = function (value) {
            return ('0' + value).slice(-2);
        };

        Calendar.prototype.select = function (info) {

            var start = info.start;
            var end = info.end;
            var options = {
                data: {
                    start: toJsonDateFormat(start),
                    end: toJsonDateFormat(end),
                    cal: 1
                }
            };

            var that = this;

            this.lastStart = start;
            this.lastEnd = end;

            var selectUrl = this.options.global ? this.options.globalCreateUrl : this.options.editUrl;

            modal.global.load(selectUrl, options).then(function () {
                modal.global.$.one('hidden.bs.modal submitted', function () {
                    that.fetch();
                });
            }).catch(function (e) {
                modal.global.close();
                module.log.error(e, true);
            });

            this.fullCalendar.unselect();
        };

        Calendar.prototype.fetch = function () {
            this.fullCalendar.refetchEvents();
        };

        Calendar.prototype.resizeEvent = function (entry, delta, revertFunc) {
            var options = {
                data: {
                    id: entry.id,
                    start: entry.start.format(this.options.jsonFormat),
                    end: entry.end.format(this.options.jsonFormat)
                }
            };

            var that = this;
            this.loader();
            client.post(entry.updateUrl, options).then(function (response) {
                if (response.success) {
                    module.log.success('saved');
                } else {
                    module.log.error(e, true);
                    that.fetch();
                }
            }).catch(function (e) {
                module.log.error(e, true);
                that.fetch();
            }).finally(function () {
                that.loader(false);
            });
        };

        Calendar.prototype.dropEvent = function (event, delta, revertFunc) {
            var options = {
                data: {
                    id: encodeURIComponent(event.id),
                    start: event.start.format(this.options.jsonFormat),
                    end: event.end.format(this.options.jsonFormat),
                    cal: true
                }
            };

            this.loader();
            var that = this;

            var dropUrl = (event.updateUrl) ? event.updateUrl : this.options.dropUrl;

            client.post(dropUrl, options).then(function (response) {
                if (response.success) {
                    module.log.success('saved');
                } else {
                    module.log.error(null, true);
                    revertFunc();
                }
            }).catch(function (e) {
                module.log.error(e, true);
                revertFunc();
            }).finally(function () {
                that.loader(false);
            });
        };

        Calendar.prototype.clickEvent = function (info) {

            var eventProps = info.event.extendedProps;

            if (!eventProps.viewUrl) {
                return;
            }

            var that = this;
            if (!eventProps.viewMode || eventProps.viewMode === 'modal') {
                modal.global.load(eventProps.viewUrl).then(function () {
                    modal.global.set({backdrop: true});
                    modal.global.$.one('hidden.bs.modal', function () {
                        that.fetch();
                    });
                }).catch(function (e) {
                    module.log.error(e, true);
                    modal.global.close();
                });
            } else if (eventProps.viewMode === 'redirect') {
                client.pjax.redirect(eventProps.viewUrl);
            }
        };

        Calendar.prototype.loader = function (show) {
            if (show === false) {
                loader.reset($('#calendar-overview-loader'));
            } else {
                loader.set($('#calendar-overview-loader'), {
                    'size': '8px',
                    'css': {
                        padding: '2px ',
                        width: '60px'

                    }
                });
            }
        };

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
            var that = this;
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

        /**
         * Callback after module was enabled.
         * @param evt
         */
        var enabled = function (evt) {
            var calendar = getCalendar();
            calendar.options.enabled = true;
            calendar.select(calendar.lastStart, calendar.lastEnd);
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
            enabled: enabled,
            CalendarEntry: CalendarEntry,
            Form: Form
        });
    }
);