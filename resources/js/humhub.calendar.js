/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

humhub.module('calendar', function (module, require, $) {
    var Widget = require('ui.widget').Widget;
    var client = require('client');
    var object = require('util').object;
    var loader = require('ui.loader');
    var modal = require('ui.modal');
    var action = require('action');
    var Content = require('content').Content;

    var Calendar = function (node, options) {
        Widget.call(this, node, options);
    };

    object.inherits(Calendar, Widget);

    Calendar.prototype.init = function () {
        // Initial events
        this.options.events = {
            url: this.options.loadUrl,
            data: {selectors: this.options.selectors, filters: this.options.filters},
            error: function (err) {
                module.log.error(err, true);
            }
        };

        module.log.debug('Init calendar: ',this.options);

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
            url: this.options.loadUrl,
            data: {selectors: selectors, filters: filters},
            error: function (err) {
                module.log.error(err, true);
            }
        };


        this.initFullCalendar(reload);
    };

    Calendar.prototype.initFullCalendar = function (reload) {
        if(reload) {
            this.$.fullCalendar('removeEventSource', this.options.events);
            this.$.fullCalendar('addEventSource', this.options.events);
        } else {
            this.$.fullCalendar(this.options);
        }
    };

    Calendar.prototype.getDefaultOptions = function () {
        return {
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            },
            defaultView: 'month',
            aspectRatio: 1.5,
            canCreate: true,
            selectable: true,
            select: $.proxy(this.select, this),
            eventResize: $.proxy(this.resizeEvent, this),
            eventDrop: $.proxy(this.dropEvent, this),
            eventClick: $.proxy(this.clickEvent, this),
            eventAllow: function() {
                return true;
            },
            jsonFormat: "YYYY-MM-DD HH:mm:ss",
            loading: $.proxy(this.loader, this),
        };
    };

    Calendar.prototype.select = function (start, end) {
        var options = {
            data: {
                start: start.format(this.options.jsonFormat),
                end: end.format(this.options.jsonFormat),
                cal: 1
            }
        };

        var that = this;

        if(!this.options.enabled) {
            this.lastStart = start;
            this.lastEnd = end;
            modal.global.load(this.options.enableUrl);
        } else if(this.options.canCreate) {
            modal.global.load(this.options.editUrl, options).then(function() {
                modal.global.$.on('submitted', function() {
                    that.fetch();
                });
            });
        }

        this.$.fullCalendar('unselect');
    };
    
    Calendar.prototype.fetch = function () {
        this.$.fullCalendar("refetchEvents");
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
        client.post(entry.updateUrl, options).then(function(response) {
           if(response.success) {
               module.log.success('saved');
           } else {
               module.log.error(e,true);
               that.fetch();
           }
        }).catch(function(e) {
            module.log.error(e,true);
            that.fetch();
        }).finally(function() {
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
        client.post(this.options.dropUrl, options).then(function(response) {
            if(response.success) {
                module.log.success('saved');
            } else {
                module.log.error(null, true);
                revertFunc();
            }
        }).catch(function(e) {
            module.log.error(e, true);
            revertFunc();
        }).finally(function() {
            that.loader(false);
        })
    };

    Calendar.prototype.clickEvent = function (event, delta, revertFunc) {
        modal.global.load(event.viewUrl).then(function() {
            modal.global.set({backdrop: true});
        });
    };

    Calendar.prototype.loader = function (show) {
        if (show === false) {
            loader.reset($('#calendar-overview-loader'));
        } else {
            loader.set($('#calendar-overview-loader'),{
                'size': '8px',
                'css': {
                    padding: '2px ',
                    width: '60px'

                }});
        }
    };

    var Form = function (node, options) {
        Widget.call(this, node, options);
    };

    object.inherits(Form, Widget);

    Form.prototype.init = function() {
        modal.global.$.find('.tab-basic').on('shown.bs.tab', function (e) {
            $('#calendarentry-title').focus();
        });

        modal.global.$.find('.tab-participation').on('shown.bs.tab', function (e) {
            $('#calendarentry-participation_mode').focus();
        });

        this.initTimeInput();
    };

    Form.prototype.initTimeInput = function(evt) {
        $timeFields = modal.global.$.find('.timeField');
        $timeInputs =  $timeFields.find('.form-control');
        $timeInputs.each(function() {
            var $this = $(this);
            if($this.prop('disabled')) {
                $this.data('oldVal', $this.val()).val('');
            }
        });
    };

    Form.prototype.toggleDateTime = function(evt) {
        $timeFields = modal.global.$.find('.timeField');
        $timeInputs =  $timeFields.find('.form-control');
        if (evt.$trigger.prop('checked')) {
            $timeInputs.prop('disabled', true);
            $timeInputs.each(function() {
                $(this).data('oldVal', $(this).val()).val('');
            });
            $timeFields.css('opacity', '0.2');
        } else {
            $timeInputs.each(function() {
                $this = $(this);
                if($this.data('oldVal')) {
                    $this.val($this.data('oldVal'));
                }
            });
            $timeInputs.prop('disabled', false);
            $timeFields.css('opacity', '1.0');
        }
    };

    Form.prototype.changeTimezone = function(evt) {
        $dropDown = this.$.find('.timeZoneInput');
        this.$.find('.calendar-timezone').text($dropDown.find('option:selected').text());
        $dropDown.hide();
    };

    Form.prototype.toggleTimezoneInput = function(evt) {
        this.$.find('.timeZoneInput').fadeToggle();
    };

    Form.prototype.changeParticipationMode = function(evt) {
        if(evt.$trigger.val() == 0) {
            this.$.find('.participationOnly').fadeOut('fast');
        } else {
            this.$.find('.participationOnly').fadeIn('fast');
        }
    };

    Form.prototype.changeEventType = function(evt) {
        $selected = evt.$trigger.find(':selected');
        if($selected.data('type-color')) {
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
        return streamEntry.replace(response.output).catch(function(e) {
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
    var respond = function(evt) {
        evt.block = action.BLOCK_MANUAL;
        client.post(evt).then(function(response) {
            if(response.success) {
                var entry = Widget.closest(evt.$trigger);
                entry.reload().then(function() {
                    module.log.success('saved');
                });
            } else {
                module.log.error(e, true);
                evt.finish();
            }
        }).catch(function(e) {
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

    var getCalendar = function() {
        return Widget.instance('#calendar');
    };

    /**
     * Callback after module was enabled.
     * @param evt
     */
    var enabled = function(evt) {
        var calendar = getCalendar();
        calendar.options.enabled = true;
        calendar.select(calendar.lastStart, calendar.lastEnd);
    };

    var deleteEvent = function(evt) {
        var streamEntry = Widget.closest(evt.$trigger);
        streamEntry.loader();
        client.post(evt).then(function() {
            modal.global.close();
            getCalendar().fetch();
        }).catch(function(e) {
            module.log.error(e, true);
        });
    };

    module.export({
        Calendar: Calendar,
        respond:respond,
        editModal: editModal,
        deleteEvent: deleteEvent,
        enabled: enabled,
        CalendarEntry: CalendarEntry,
        Form: Form
    });
});