/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

humhub.module('calendar.participants.List', function (module, require, $) {
    const Widget = require('ui.widget').Widget;
    const loader = require('ui.loader');
    const client = require('client');
    const status = require('ui.status');

    var List = Widget.extend();

    List.prototype.update = function (evt) {
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

    List.prototype.remove = function (evt) {
        const participation = evt.$trigger.closest('li');
        const data = {
            entryId: this.data('entry-id'),
            userId: participation.data('user-id'),
        };

        client.post(this.data('remove-url'), {data}).then(function(response) {
            if (response.success) {
                status.success(response.message);
                participation.remove();
            } else {
                status.error(response.message);
            }
        }).catch(function(e) {
            module.log.error(e, true);
        }).finally(function () {
            evt.finish();
        });
    };

    List.prototype.displayAddForm = function (evt) {
        const list = this.$.find('ul.media-list');

        client.get(evt).then(function(response) {
            list.append(response.html);
            Widget.closest(list.find('[data-ui-init]'));
            evt.$trigger.remove();
        }).catch(function(e) {
            module.log.error(e, true);
        }).finally(function () {
            evt.finish();
        });
    }

    List.prototype.add = function (evt) {
        const form = evt.$trigger.closest('li');
        const data = {
            entryId: this.data('entry-id'),
            status: form.find('select[name=status]').val(),
            guids: form.find('select[name="newParticipants[]"]').val(),
        };

        client.post(this.data('add-url'), {data}).then(function(response) {
            if (response.success) {
                status.success(response.success);
                form.before(response.html);
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

    module.export = List;
});