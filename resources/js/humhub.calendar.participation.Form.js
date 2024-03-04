/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

humhub.module('calendar.participation.Form', function (module, require, $) {
    var Widget = require('ui.widget').Widget;
    var loader = require('ui.loader');
    var client = require('client');
    var status = require('ui.status');
    var calendar = require('calendar');
    var modal = require('ui.modal');

    var Form = Widget.extend();

    Form.prototype.init = function () {
        var that = this;
        this.saveButton = $('#calendar-entry-participation-button-save');
        this.nextButton = $('#calendar-entry-participation-button-next');
        this.backButton = $('#calendar-entry-participation-button-back');

        this.tabSettings = $('#calendar-entry-participation-tabs li:first');
        this.tabParticipants = $('#calendar-entry-participation-tabs li:last');

        this.isNewRecord = this.backButton.length;
        if (this.isNewRecord) {
            $('#calendar-entry-participation-tabs li a').click(function () {
                if ($('#calendar-entry-participation-tabs li:visible').length > 1) {
                    var isTabSettingsActive = $(this).closest('li').index() === 0;
                    that.saveButton.toggle(!isTabSettingsActive);
                    that.nextButton.toggle(isTabSettingsActive);
                }
            });
        }
    }

    Form.prototype.changeParticipantsListPage = function (evt) {
        var that = this;
        evt.preventDefault();
        modal.footerLoader();

        client.get(evt).then(function(response) {
            var $participantsList = $(response.html).filter('#calendar-entry-participants-list');
            that.$.find('#calendar-entry-participants-list').replaceWith($participantsList);
        }).catch(function(e) {
            module.log.error(e, true);
        }).finally(function () {
            loader.reset(modal.global.getFooter());
            evt.finish();
        });
    }

    Form.prototype.update = function (evt) {
        var updater = evt.$trigger.parent();
        var updaterHtml = evt.$trigger.parent().html();
        var data = {
            entryId: this.data('entry-id'),
            userId: evt.$trigger.closest('li').data('user-id'),
            status: evt.$trigger.val(),
        };

        loader.set(updater, {size: '10px', css: {padding: '0px'}});
        client.post(this.data('update-url'), {data: data}).then(function(response) {
            if (response.success) {
                status.success(response.message);
                loader.remove(updater);
                updater.html(updaterHtml).find('select').val(data.status);
            }
        }).catch(function(e) {
            module.log.error(e, true);
        }).finally(function () {
            evt.finish();
        });
    };

    Form.prototype.remove = function (evt) {
        var participation = evt.$trigger.closest('li');
        var data = {
            entryId: this.data('entry-id'),
            userId: participation.data('user-id'),
        };

        client.post(this.data('remove-url'), {data: data}).then(function(response) {
            if (response.success) {
                status.success(response.message);
                if (participation.closest('ul').find('li[data-user-id]').length === 1) {
                    participation.closest('ul').prev('p').show();
                }
                participation.remove();
                updateParticipantsCount(-1);
            } else {
                status.error(response.message);
            }
        }).catch(function(e) {
            module.log.error(e, true);
        }).finally(function () {
            evt.finish();
        });
    };

    Form.prototype.add = function (evt) {
        var form = evt.$trigger.closest('.calendar-entry-new-participants-form');
        var data = {
            entryId: this.data('entry-id'),
            guids: form.find('select[name="CalendarEntryParticipationForm[newParticipants][]"]').val(),
        };
        var entryStatus = form.find('select[name="CalendarEntryParticipationForm[newParticipantStatus]"]');
        if (entryStatus.length) {
            data.status = entryStatus.val();
        }

        client.post(evt, {data: data}).then(function(response) {
            if (response.html) {
                var list = form.closest('.calendar-entry-participants').find('#calendar-entry-participants-list ul.media-list');
                var count = list.find('li').length;
                list.append(response.html);
                updateParticipantsCount(list.find('li').length - count);
            }
            if (response.success) {
                status.success(response.success);
            } else if (response.warning) {
                status.warn(response.warning);
            } else if (response.error) {
                status.error(response.error);
            }
        }).catch(function(e) {
            module.log.error(e, true);
        }).finally(function () {
            evt.finish();
        });
    }

    Form.prototype.filterState = function (evt) {
        var filter = evt.$trigger;
        var filters = filter.parent();
        var modal = $('#globalModal');
        var data = {
            id: this.data('entry-id'),
            state: filter.data('state'),
        };

        filter.attr('data-active', '');
        loader.set(filters, {size: '10px', css: {padding: '0px'}});
        client.get(this.data('filter-url'), {data: data}).then(function(response) {
            modal.find('#calendar-entry-participants-list').after(response.html).remove();
            updateParticipantsCount(modal.find('[name=calendar-entry-participants-count]').val(), false);
            loader.reset(filters);
            filters.find('.btn.active').removeClass('active');
            filters.find('[data-active]').addClass('active').removeAttr('data-active');
        }).catch(function(e) {
            module.log.error(e, true);
        }).finally(function () {
            evt.finish();
        });
    };

    Form.prototype.changeParticipationMode = function (evt) {
        var noParticipants = evt.$trigger.val() == 0;

        if (noParticipants) {
            this.$.find('.participationOnly').fadeOut('fast')
        } else {
            this.$.find('.participationOnly').fadeIn('fast');
        }
        this.tabParticipants.toggle(!noParticipants);
        if (this.isNewRecord) {
            this.saveButton.toggle(noParticipants);
            this.nextButton.toggle(!noParticipants);
        }
    };

    var updateParticipantsCount = function(value, shift) {
        var counter = $('#globalModal').find('.calendar-entry-participants-count span');
        counter.html(shift || typeof(shift) === 'undefined' ? parseInt(counter.html()) + value : value);
    }

    Form.prototype.next = function (evt) {
        this.tabParticipants.find('a').click();
        this.nextButton.hide();
        this.saveButton.show();
        evt.finish();
    }

    Form.prototype.back = function (evt) {
        if (this.tabParticipants.hasClass('active')) {
            this.tabSettings.find('a').click();
            this.saveButton.hide();
            this.nextButton.show();
        } else {
            loader.set(evt.$trigger, {size: '10px', css: {padding: '0px'}});
            calendar.editModal(evt);
        }
        evt.finish();
    }

    module.export = Form;
});
