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
 * GlobalController provides a global view.
 *
 * @package humhub.modules_core.calendar.controllers
 * @author luke
 */
class GlobalController extends Controller
{

    public function beforeAction($action)
    {
        if (!Yii::app()->user->getModel()->isModuleEnabled('calendar')) {
            throw new CHttpException('500', 'Calendar module is not enabled for your user!');
        }

        return parent::beforeAction($action);
    }

    public function actionIndex()
    {

        // Restore users last selectors
        $lastSelectorsJson = Yii::app()->user->getModel()->getSetting('lastSelectors', 'calendar');
        if ($lastSelectorsJson != "") {
            $selectors = CJSON::decode($lastSelectorsJson);
        } else {
            $selectors = array(
                CalendarEntry::SELECTOR_MINE,
                CalendarEntry::SELECTOR_SPACES,
            );
        }

        // Restore users last used filter
        $lastFilterJson = Yii::app()->user->getModel()->getSetting('lastFilters', 'calendar');
        if ($lastFilterJson != "") {
            $filters = CJSON::decode($lastFilterJson);
        } else {
            $filters = array();
        }

        $this->render('index', array(
            'selectors' => $selectors,
            'filters' => $filters
        ));
    }

    public function actionLoadAjax()
    {

        $output = array();

        $startDate = new DateTime(Yii::app()->request->getParam('start'));
        $endDate = new DateTime(Yii::app()->request->getParam('end'));
        $selectors = explode(",", Yii::app()->request->getParam('selectors'));
        $filters = explode(",", Yii::app()->request->getParam('filters'));

        Yii::app()->user->getModel()->setSetting('lastSelectors', CJSON::encode($selectors), 'calendar');
        Yii::app()->user->getModel()->setSetting('lastFilters', CJSON::encode($filters), 'calendar');

        $entries = CalendarEntry::getEntriesByRange($startDate, $endDate, $selectors, $filters);

        foreach ($entries as $entry) {
            $output[] = $entry->getFullCalendarArray();
        }

        echo CJSON::encode($output);
    }

}
