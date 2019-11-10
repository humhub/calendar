/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2017 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

humhub.module('calendar.recurrence.Form', function (module, require, $) {
    var Widget = require('ui.widget').Widget;

    var Form = Widget.extend();

    var WEEKDAY_SELECT = '#recurrenceformmodel-weekdays';
    var INTERVAL_TYPE_SELECT = '#recurrenceformmodel-frequency';
    var INTERVAL_VALUE_INPUT = '#recurrenceformmodel-interval';

    Form.prototype.init = function() {
        this.updatedValue();
        this.updatedType();

        if(this.options.pickerSelector) {
            $(this.options.pickerSelector).on('change', function() {
                var date = $(this).datepicker('getDate');
                $(WEEKDAY_SELECT).val(date.getDay() + 1).trigger('change');
            });
        }
    };

    Form.prototype.updatedValue = function () {
        var $typeOption = this.$.find(INTERVAL_TYPE_SELECT);
        var value = parseInt(this.$.find(INTERVAL_VALUE_INPUT).val(), 10);

        $typeOption.find('option').each(function() {
            var $this = $(this);
            var text = value > 1 ? $this.data('plural') : $this.data('singular');
            $this.text(text);
        });
    };

    Form.prototype.updatedType = function () {
        var value = parseInt(this.$.find(INTERVAL_TYPE_SELECT).val(), 10);

        if(value === 0) {
            this.$.find('.hideIfNoRecurrence').hide();
        } else {
            this.$.find('.hideIfNoRecurrence').show();
            this.$.find('[data-recurrence-type]').hide();
            this.$.find('[data-recurrence-type="'+value+'"]').show();
        }

    };


    module.export = Form;
});