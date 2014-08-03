<?php

/**
 * HumHub
 * Copyright © 2014 The HumHub Project
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 */

/**
 * Description of FullCalendarWidget
 *
 * @author luke
 */
class FullCalendarWidget extends HWidget
{

    public function init()
    {

        $calendarModule = Yii::app()->getModule('calendar');

        Yii::app()->clientScript->registerCssFile($calendarModule->getAssetsUrl() . '/fullcalendar/fullcalendar.css');
        Yii::app()->clientScript->registerCssFile($calendarModule->getAssetsUrl() . '/fullcalendar/fullcalendar.print.css', 'print');

        Yii::app()->clientScript->registerScriptFile($calendarModule->getAssetsUrl() . '/fullcalendar/lib/moment.min.js');
        Yii::app()->clientScript->registerScriptFile($calendarModule->getAssetsUrl() . '/fullcalendar/lib/jquery-ui.custom.min.js');
        Yii::app()->clientScript->registerScriptFile($calendarModule->getAssetsUrl() . '/fullcalendar/fullcalendar.min.js');
        Yii::app()->clientScript->registerScriptFile($calendarModule->getAssetsUrl() . '/fullcalendar/lang-all.js');

        Yii::app()->clientScript->registerScriptFile($calendarModule->getAssetsUrl() . '/fullcalendar.js');
        
        Yii::app()->clientScript->setJavascriptVariable('fullCalendarCanWrite', Yii::app()->getController()->contentContainer->canWrite() ? 'true' : 'false');
        Yii::app()->clientScript->setJavascriptVariable('fullCalendarTimezone', date_default_timezone_get());
        Yii::app()->clientScript->setJavascriptVariable('fullCalendarLanguage', Yii::app()->language);
        Yii::app()->clientScript->setJavascriptVariable('fullCalendarLoadUrl', Yii::app()->getController()->createContainerUrl('view/loadAjax'));
        Yii::app()->clientScript->setJavascriptVariable('fullCalendarCreateUrl', Yii::app()->getController()->createContainerUrl('entry/edit', array('start_time' => '-start-', 'end_time' => '-end-', 'fullCalendar' => '1')));
        
    }

    public function run()
    {

        $this->render('fullCalendar', array(
        ));
    }

}

?>
