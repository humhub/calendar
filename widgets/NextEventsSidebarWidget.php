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
 * NextEventsSidebarWidget shows next events in sidebar.
 *
 * @package humhub.modules_core.calendar.widgets
 * @author luke
 */
class NextEventsSidebarWidget extends StackWidget
{

    /**
     * ContentContainer to limit events to. (Optional)
     * 
     * @var HActiveRecordContentContainer
     */
    public $contentContainer;

    /**
     * How many days in future events should be shown?
     *
     * @var int
     */
    public $daysInFuture = 7;

    /**
     * Maximum Events to display
     * 
     * @var int
     */
    public $maxEvents = 3;

    public function run()
    {
        $calendarEntries = CalendarEntry::getUpcomingEntries($this->contentContainer, $this->daysInFuture, $this->maxEvents);

        if (count($calendarEntries) == 0) {
            return;
        }

        $this->render('nextEvents', array('calendarEntries' => $calendarEntries));
    }

}
