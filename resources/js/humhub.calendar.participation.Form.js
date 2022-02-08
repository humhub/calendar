/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

humhub.module('calendar.participation.Form', function (module, require, $) {
    const Widget = require('ui.widget').Widget;
    const loader = require('ui.loader');
    const client = require('client');
    const status = require('ui.status');

    var Form = Widget.extend();

    Form.prototype.update = function (evt) {
        const updater = evt.$trigger.parent();
        const updaterHtml = evt.$trigger.parent().html();
        const data = {
            entryId: this.data('entry-id'),
            userId: evt.$trigger.closest('li').data('user-id'),
            status: evt.$trigger.val(),
        };

        loader.set(updater, {size: '10px', css: {padding: '0px'}});
        client.post(this.data('update-url'), {data}).then(function(response) {
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
        const participation = evt.$trigger.closest('li');
        const data = {
            entryId: this.data('entry-id'),
            userId: participation.data('user-id'),
        };

        client.post(this.data('remove-url'), {data}).then(function(response) {
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
        const form = evt.$trigger.closest('.calendar-entry-new-participants-form');
        const data = {
            entryId: this.data('entry-id'),
            guids: form.find('select[name="newParticipants[]"]').val(),
        };
        const entryStatus = form.find('select[name=status]');
        if (entryStatus.length) {
            data.status = entryStatus.val();
        }

        client.post(evt, {data}).then(function(response) {
            if (response.success) {
                status.success(response.success);
                const list = form.closest('.calendar-entry-participants').find('#calendar-entry-participants-list ul.media-list');
                const count = list.find('li').length;
                list.append(response.html);
                updateParticipantsCount(list.find('li').length - count);
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
        const filter = evt.$trigger;
        const filters = filter.parent();
        const modal = $('#globalModal');
        const data = {
            id: this.data('entry-id'),
            state: filter.data('state'),
        };

        filter.attr('data-active', '');
        loader.set(filters, {size: '10px', css: {padding: '0px'}});
        client.get(this.data('filter-url'), {data}).then(function(response) {
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
        if (evt.$trigger.val() == 0) {
            this.$.find('.participationOnly').fadeOut('fast');
            this.$.find('#calendar-entry-participation-tabs').hide();
            this.$.find('#calendar-entry-participation-settings-title').show();
        } else {
            this.$.find('.participationOnly').fadeIn('fast');
            this.$.find('#calendar-entry-participation-tabs').show();
            this.$.find('#calendar-entry-participation-settings-title').hide();
        }
    };

    const updateParticipantsCount = function(value, shift) {
        const counter = $('#globalModal').find('.calendar-entry-participants-count span');
        counter.html(shift || typeof(shift) === 'undefined' ? parseInt(counter.html()) + value : value);
    }

    module.export = Form;
});