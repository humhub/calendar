/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

humhub.module('calendar.Calendar', function (module, require, $) {
    var Widget = require('ui.widget').Widget;
    var client = require('client');
    var util = require('util');
    var string = util.string;
    var loader = require('ui.loader');
    var view = require('ui.view');
    var modal = require('ui.modal');

    var Calendar = Widget.extend();

    Calendar.prototype.init = function () {
        var that = this;
        // Initial events

        this.options.events = function (info, successCallback, failureCallback) {
            $.ajax({
                url: that.options.loadUrl,
                type: 'GET',
                data: {
                    start: moment(info.start.valueOf()).format('YYYY-MM-DD'),
                    end:  moment(info.end.valueOf()).format('YYYY-MM-DD'),
                    viewMode: that.options.viewMode,
                    calendars: that.options.calendars,
                    show: that.options.show,
                    types: that.options.types
                },
                success: function (response) {
                    successCallback(response);
                }
            });
        };

        module.log.debug('Init calendar: ', this.options);

        this.initCalendarFilter();
        this.updateCalendarFilters();
    };

    Calendar.prototype.initCalendarFilter = function () {
        var that = this;
        var updateTypeFilterTimeout;
        var updateTypeFilter = function () {
            clearTimeout(updateTypeFilterTimeout);
            updateTypeFilterTimeout = setTimeout(function () {
                that.updateCalendarFilters(true);
            }, 0);
        };

        $('.calendar-select-view-mode').on('change', function () {
            if (that._resetting) {
                return;
            }
            that.toggleCalendarsSelector();
            that.updateCalendarFilters(true);
        });

        $('.calendar-select-calendars').on('change', function () {
            if (that._resetting) {
                return;
            }
            that.updateCalendarFilters(true);
        });

        $('.calendar-select-show').on('change', function () {
            if (that._resetting) {
                return;
            }
            that.updateCalendarFilters(true);
        });

        $('.calendar-filter-reset').on('click', function (evt) {
            evt.preventDefault();
            that.resetFilters();
        });

        $('select[name="filterType[]"]').on('change select2:select select2:unselect select2:clear', function () {
            if (that._resetting) {
                return;
            }
            updateTypeFilter();
        });

        this.toggleCalendarsSelector();
    };

    /**
     * The "Calendars" selector is only meaningful in "My Calendars" view mode, since
     * "Entire network" always includes everything regardless of its value.
     */
    Calendar.prototype.toggleCalendarsSelector = function () {
        var isNetwork = $('.calendar-select-view-mode').val() === 'network';
        $('.calendar-filter-calendars').toggleClass('d-none', isNetwork);
    };

    /**
     * Resets all filters back to their defaults (My Calendars / All / no "show only" / no types)
     * and reloads the calendar, without navigating away from the current calendar view/date.
     *
     * Values are set via val().trigger('change') (not just val()) since these selects are
     * enhanced with Select2 (see humhub\helpers\Html::dropDownList()), which only re-renders
     * its visible display in reaction to a native "change" event on the underlying <select>.
     * The individual change handlers are temporarily suppressed via the _resetting flag so the
     * calendar is only reloaded once, at the end, instead of once per reset field.
     */
    Calendar.prototype.resetFilters = function () {
        this._resetting = true;

        $('.calendar-select-view-mode').val('mycalendars').trigger('change');
        $('.calendar-select-calendars').val('all').trigger('change');
        $('.calendar-select-show').val('').trigger('change');

        var $types = $('select[name="filterType[]"]');
        if ($types.length) {
            $types.val(null).trigger('change');
        }

        this._resetting = false;

        this.toggleCalendarsSelector();
        this.updateCalendarFilters(true);
    };

    Calendar.prototype.updateCalendarFilters = function (reload) {
        this.options.viewMode = $('.calendar-select-view-mode').val() || 'mycalendars';
        this.options.calendars = $('.calendar-select-calendars').val() || 'all';
        this.options.show = $('.calendar-select-show').val() || '';
        this.options.types = $('select[name="filterType[]"]').val() || [];

        this.updateResetButtonVisibility();

        this.initFullCalendar(reload);
    };

    /**
     * The reset ("x") action is only shown once the current selection deviates from the
     * defaults, mirroring the People/Space directory filter bars.
     */
    Calendar.prototype.updateResetButtonVisibility = function () {
        var isFiltered = this.options.viewMode !== 'mycalendars'
            || this.options.calendars !== 'all'
            || this.options.show !== ''
            || (this.options.types && this.options.types.length > 0);

        $('.form-search-action-reset').toggleClass('d-none', !isFiltered);
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

        if (module.text('button.day') !== 'day') {
            buttonText.day = module.text('button.day');
        }

        if (module.text('button.list') !== 'list') {
            buttonText.list = module.text('button.list');
        }

        var that = this;
        
        var options = {
			customButtons: {
                create: {
                    click: function() {
                        // Start: next full hour
                        var start = new Date();
                        start.setHours(start.getHours() + 1);
                        start.setMinutes(0, 0, 0);
                        
                        // End: next full hour +1
                        var end = new Date();
                        end.setHours(end.getHours() + 2);
                        end.setMinutes(0, 0, 0);
                        
                        var options = {
                            data: {
                                start: that.toJsonDateFormat(start, false),
                                end: that.toJsonDateFormat(end, false),
                                cal: 1
                            }
                        }
                        var createUrl = that.options.global ? that.options.globalCreateUrl : that.options.editUrl;
                        
                        modal.global.load(createUrl, options).then(function () {
                            modal.global.$.one('hidden.bs.modal submitted', function () {
                                that.fetch();
                            });
                        }).catch(function (e) {
                            modal.global.close();
                            module.log.error(e, true);
                        });

                        that.fullCalendar.unselect();
                    },
                    bootstrapFontAwesome: 'fa-plus',
                }
            },
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'create dayGridMonth,timeGridWeek,timeGridDay,' + module.config['listViewType']
            },
            buttonText: buttonText,
            plugins: ['dayGrid', 'timeGrid', 'list', 'interaction', 'bootstrap', 'moment', 'momentTimezone'],
            defaultView: 'dayGridMonth',
            canCreate: true,
            selectable: true,
            select: $.proxy(this.select, this),
            eventAllow: function () {
                return true;
            },
            themeSystem: 'bootstrap',
            loading: $.proxy(this.loader, this),
            eventResize: $.proxy(this.updateEvent, this),
            eventDrop: $.proxy(this.updateEvent, this),
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
                center: 'dayGridMonth,timeGridWeek,timeGridDay,' + module.config['listViewType'],
                right: 'create'
            };
        }

        return options;
    };

    Calendar.prototype.renderEvent = function (event, element) {
        var $element = $(element);
        $element.attr({title: $element.text()});
        if (event.icon) {
            if (string.startsWith(event.icon, 'fa-')) {
                $element.find('.fc-content').prepend($('<i class="fa ' + event.icon + '"></i>'));
            }
        }
    };

    Calendar.prototype.toJsonDateFormat = function (date, allDay) {
        if(allDay) {
            return FullCalendarMoment.toMoment(date, this.fullCalendar).format('YYYY-MM-DD');
        }

        return FullCalendarMoment.toMoment(date, this.fullCalendar).format();
    };

    Calendar.prototype.select = function (info) {
        var that = this;
        var options = {
            data: {
                start: this.toJsonDateFormat(info.start, false),
                end: this.toJsonDateFormat(info.end, false),
                cal: 1
            }
        };
        if (info.view.type === 'dayGridMonth') {
            options.data.view = 'month';
        }

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

    Calendar.prototype.updateEvent = function (info) {
        var that = this;
        var event = info.event;
        var eventProps = event.extendedProps;
        var options = {
            data: {
                id: event.id,
                start: this.toJsonDateFormat(event.start, event.allDay),
                end: this.toJsonDateFormat(event.end, event.allDay)
            }
        };

        this.loader();

        client.post(eventProps.updateUrl, options).then(function (response) {
            if (response.success) {
                module.log.success('saved');
            } else {
                module.log.error(response, true);
            }

            if(eventProps.refreshAfterUpdate) {
                that.fetch();
            }

        }).catch(function (e) {
            module.log.error(e, true);
            info.revert();
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

        if (eventProps.viewMode === 'modal') {
            modal.global.load(eventProps.viewUrl, {'viewContext' : 'fullCalendar'}).then(function () {
                modal.global.set({backdrop: true});
                modal.global.$.one('hidden.bs.modal', function () {
                    that.fetch();
                });
            }).catch(function (e) {
                module.log.error(e, true);
                modal.global.close();
            });
        } else {
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

    module.export = Calendar;
});
