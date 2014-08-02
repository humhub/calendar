<?php

class FullCalendarWidget extends HWidget {

    public $calendarEntry;

    
    public function init()
    {
        
        $calendarModule = Yii::app()->getModule('calendar');
        
        Yii::app()->clientScript->registerCssFile($calendarModule->getAssetsUrl() . '/fullcalendar/fullcalendar.css');
        Yii::app()->clientScript->registerCssFile($calendarModule->getAssetsUrl() . '/fullcalendar/fullcalendar.print.css', 'print');
        
        Yii::app()->clientScript->registerScriptFile($calendarModule->getAssetsUrl() . '/fullcalendar/lib/moment.min.js');
        Yii::app()->clientScript->registerScriptFile($calendarModule->getAssetsUrl() . '/fullcalendar/lib/jquery-ui.custom.min.js');
        Yii::app()->clientScript->registerScriptFile($calendarModule->getAssetsUrl() . '/fullcalendar/fullcalendar.min.js');
        Yii::app()->clientScript->registerScriptFile($calendarModule->getAssetsUrl() . '/fullcalendar/lang-all.js');
        
    }
    
    
    public function run() {

        $this->render('fullCalendar', array(
        ));
    }

}

?>