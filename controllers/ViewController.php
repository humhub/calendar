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
 * Description of ViewController
 *
 * @author luke
 */
class ViewController extends ContentContainerController
{

    public function actionIndex()
    {
        $this->checkContainerAccess();

        $entryId = (int) Yii::app()->request->getParam('entryId', '');
        $entries = CalendarEntry::model()->contentContainer($this->contentContainer)->findAll();

        $this->render('index', array('calendarEntries' => $entries, 'entryId' => $entryId));
    }

    public function actionLoadAjax()
    {

        $output = array();

        $startDate = new DateTime(Yii::app()->request->getParam('start'));
        $endDate = new DateTime(Yii::app()->request->getParam('end'));

        $entries = CalendarEntry::getEntriesByRange($startDate, $endDate, $this->contentContainer);

        foreach ($entries as $entry) {
            $output[] = $entry->getFullCalendarArray();
        }

        echo CJSON::encode($output);
    }


}
