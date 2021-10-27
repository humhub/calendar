Changelog
=========

1.1.11 (Unreleased)
---------------------
- Fix #114: PHP8 - Deprecate required parameters after optional parameters
- Fix #244: CLI error when no REST module is installed
- Fix #246: Fix error displaying of empty date fields
- Enh #187: Possibility to disable participation activities in the mail summary
- Enh #5274: Deprecate CompatModuleManager
- Fix #254: Fix wall stream entry icons color
- Fix: In the calendar view, "Day" button is not translated with Humhub's messages

1.1.10  (April 14, 2021)
----------------------
- Fix: Todays Birthday not shown in Dashboard Snippet when Interval is one year

1.1.8  (April 8, 2021)
----------------------
- Fix #221: Fix call of console commands when REST API module doesn't exist

1.1.7 (April 7, 2021)
---------------------
- Enh: Added module snippet configuration "Include birthdays to dashboard snippet"
- Enh #218: Change snippet default sort
- Enh #235: Added REST API Module Support 

1.1.6 (February 22, 2021)
---------------------
- Fix: Birthdays can not be exported
- Fix: Prevent vevent duplicates in export

1.1.5 (February 18, 2021)
---------------------
- Fix #226: Calendar Reminder Query for space entry `PARTICIPATION_MODE_ALL` should only include participants
- Fix #226: Calendar Reminder Query for user entry `PARTICIPATION_MODE_ALL` did not include user itself

1.1.4 (February 5, 2021)
---------------------
- Fix #217: Monthly recurrence rule text on fifth week of the month not working
- Enh: Added monthly recurrence setting "Monthly on last <day>"
- Fix: Date picker udpate does not update monthly rule selection text
- Enh: Updating start date input will update end date value if end < start (and vice versa)
- Enh: Updated translations
- Fix #224: Hide space with disabled module on chooser modal window

1.1.3 (November 3, 2020)
---------------------
- Fix: Remember Global calendar filter in user settings not working

1.1.2 (November 3, 2020)
---------------------
- Fix: Only render file preview section of recurrent root event if files exists
- Chng: Remove default profile/space filter from global calendar
- Fix: Reminder process fails due to missing join in AbstractCalendarQuery

1.1.1 (November 2, 2020)
---------------------
- Fix #210: Birthday calendar entry does not link to user profile
- Fix: FullCalendar calls `getIcon()` on entry instead of event type
- Enh: Mark canceled events in "Upcoming events" snippet
- Enh: Reworked calendar wall entry view
- Fix #213: Calendar entry editing in stream not working
- Fix #211: Unable to move event to another space
- Fix #188: Topic label not translatable
- Fix #166: Archived events are not excluded from the calendar
- Fix #99: Users with participation state maybe can attend to events with exceeded max participation count 

1.1.0 (November 2, 2020)
---------------------
- Enh #208: Wall Stream Layout Migration (HumHub 1.7+)

1.0.13 (October 16, 2020)
---------------------
- Enh: New attribute “Location” for calendar entry
- Fix #198: Recurrence & max. Participants not checked #198


1.0.12 (July 31, 2020)
---------------------
- Fix #186: Make ICS Organizer field optional
- Fix #189: Missing public content visibility permission check on calendar entry form
- Chg: Removed `RecurrenceQueryIF::getExistingRecurrences()` since it is not in use anymore
- Fix: `AbstractRecurrenceQuery::findRecurrenceInstances()` did not include recurrences overlapping the search interval
- Fix #193: Recurrence instance duplicates created by reminder process
- Fix: Block `CalendarEntryParticipation::canRespond()` on recurrent root events
- Chg: Switched edit/delete order in wallentry context menu
- Chg: Prevent stream entry generation for recurrent root event
- Enh: Added integrity check for duplicated recurrence instances

1.0.11 (May 06, 2020)
---------------------
- Fix #176: Files are not attached on calendar entry creation

1.0.10 (May 05, 2020)
---------------------
- Fix #178: Overflow issue in ee theme
- Enh: Use of different aspect ratio for fluid themes
- Enh: Added `aspectRatio` option to `FullCalendar` widget
- Chng: Update HumHub min version to v1.4
- Fix: Calendar buttons and loader render issue on mobile
- Enh: Minified calendar.css stylesheet

1.0.9 (April 20, 2020)
---------------------
- Fix #178: Calendar settings button only visible for system admin

1.0.8 (April 09, 2020)
---------------------
- Enh: Added 1.5 defer compatibility
- Fix: load-ajax does not use `X-Requested-With	XMLHttpRequest` header
- Fix: Global calendar does not use strict access for guest users

1.0.7 (March 26, 2020)
---------------------
- Chng: Updated grunt version to 1.1.0
- Chng: More stable event error handling 
- Fix #167: Can not remove calendar type, once a calendar type is set
- Fix #173: Event cannot be moved to another Space
- Fix #175: Implemented fallback for failed `calendar_entry_participant` user foreign key
- Fix: Calendar permissions displayed on container without calendar module installed (https://github.com/humhub/humhub/issues/3828)

1.0.6 (March 4, 2020)
---------------------
- Fix: Forbidden create entry attempts should result in 403 instead of Internal server error
- Fix #174: Spelling in calendar settings of german translation
- Enh: Updated translations

1.0.5 (February 17, 2020)
------------------------
- Fix: recurrence_id unique index too long

1.0.4 (February 5, 2020)
------------------------
- Enh: Updated translations

1.0.3 (January, 17, 2020)
-----------------------
- Fix: container based calendar type updates overwrite global defaults
- Fix: Default weekly recurrence day not based on initial start date
- Enh #42: Use space color as default entry color
- Enh #95: Make default color of calendar events configurable
- Chng: Renamed `Other calendars` section to `Calendars` section, since default events are configurable now
- Enh: Use of grunt to minify and concat assets
- Chng: `CalendarService::getCalendarItemTypes()` now includes `CalendarEntryType`

1.0.2 (January, 16, 2020)
-----------------------
- Fix: fullcalendar load url fails when using url rewrite

1.0.1 (January, 16, 2020)
-----------------------
- Fix: Change default viewMode of legacy interface to `redirect`

1.0.0 (January, 16, 2020)
-----------------------
- Chng: Switched from array based interfaces to real interfaces
- Enh: Reminder support with reminder interface `CalendarEventReminderIF`
- Enh: Introduction of new `CalendarEventParticipationIF`
- Enh: Support of recurring events with recurring `RecurrentEventIF`
- Chng: Introduced `FullCalendarEventIF` for additional event view settings
- Fix #161: Error when accessing global configuration, in case the module is not enabled on profile level
- Chng: Use of `helpers/Url` class
- Fix #106: Issues with time validation in swedish and greek locale
- Chng: Omitted timezone translation on all day events
- Enh: Omit timezone information on all day events
- Enh: Enhanced ICal export format
- Fix #18: Incorrect Phrasing of Strings
- Chng: Updated to fullcalendar v4
- Enh: Added fullcalendar list view
- Enh: translatable calendar view buttons
- Enh: Enhanced mobile calendar view
- Chng: Switch from inclusive to exclusive end date (23:59:59) on all day events
- Fix #117: Date selection accepts invalid user input
- Fix #155: AbstractCalendarQuery does not include events spanning over the search interval
- Fix #78: Add date information to response activity
- Enh #165: Improved mail html and text views


0.7.5 (October 16, 2019)
-----------------------
- Fix: ExternalCalendarEntryQuery with either only start or only end not fails


0.7.4 (October 16, 2019)
-----------------------
- Enh: Translation update


0.7.3 (August 28, 2019)
-----------------------
- Fix: My events filter not working


0.7.2 (August 22, 2019)
-----------------------
- Fix: `VCalendar::withEvents()` broken


0.7.1 (August 22, 2019)
-----------------------
- Fix: Issue with PHP < 7 use of incompatible sabre/xml version


0.7 (August 22, 2019)
-----------------------
- Enh: Added `humhub\modules\calendar\widgets\CalendarControls` stacked menu in full calendar view
- Chng: Added VObject dependency for ICS handling (dev-master see https://github.com/sabre-io/vobject/issues/448)
- Enh: Added `uid` field and auto UID creation in AbstractCalendarQuery
- Fix: Fixed encoding issue in space selection dropdown
- Fix: Space selection dropdown does not respect default module installation
- Fix #156: Upcoming events includes events already finished today
- Enh: Added `CalendarUtils::getUserTimeZone()`
- Enh: Added `AbstractCalendarQuery::expand` flag and  `AbstractCalendarQuery::expand()` for calendars with event expansion possibility
- Enh: Added `CalendarItemsEvent::expand` flag in order to query unexpanded or expanded event results
- Chng: Added `getRRule()`, `getExdate()`, 'getLocation()', 'getDescription()' to `interface\CalendarItem` interface
- Enh: Added rounded top border to global calendar view
- Chng: Updated min version to 1.3.14


0.6.23 (November 27, 2018)
-----------------------
- Enh: Use of new richtext
- Fix: Participation info label not translatable

0.6.22 (November 27, 2018)
-----------------------
- Fix: Hide permissions for space guest role


0.6.21 (November 14, 2018)
-----------------------
- Fix: Same day events displaying wrong output date format


0.6.20 (September 17, 2018)
-----------------------
- Enh: Added time information to force participation mail
- Enh: Updated Translations


0.6.19 (September 04, 2018)
-----------------------
- Fix: Other calendar configuration view not working


0.6.18 (August 23, 2018)
-----------------------
- Enh: Added topic picker to edit form
- Enh: Added "Add all space members to event" feature
- Enh: Added calendar event title
- Fix: encoding issue in calendar view
- Chg: Min HumHub version 1.3
- Fix: Canceled event missing event title
- Enh: Added move feature for HumHub >= 1.3.2
- Fix: Global calendar types in space in space edit view


0.6.17  (July 4, 2018)
-----------------------
- Chg: Added HumHub 1.3 compatibility (new space module handling)


0.6.16  (July 2, 2018)
-----------------------
- Chg: PHP 7.2 compatibility fixes


0.6.15  (May 29, 2018)
-----------------------
- Fix: Added missing "readable" filter in calendar view


0.6.14  (May 11, 2018)
-----------------------
- Fix: Wrong translation text category for "Delete" link


0.6.13  (May 08, 2018)
-----------------------
- Fix: Wrong translation text category for "Cancel event"
- Enh: Updated translations


0.6.12  (April 27, 2018)
-----------------------
- Fix: Removed FilterNotSupported error log


0.6.11  (April 18, 2018)
-----------------------
- Fix: Calendar ics export timezone issue


0.6.10  (April 18, 2018)
-----------------------
- Fix: Birthday calendar shows non visible users


0.6.9  (April 17, 2018)
-----------------------
- Fix: Birthday calendar fixes
- Enh: Added BirthdayQuery tests


0.6.8  (April 17, 2018)
-----------------------
- Fix: Birthday calendar query


0.6.6  (April 13, 2018)
-----------------------
- Enh: Updated translations


0.6.5 - (April 13, 2018)
------------------------
- Fix: Calendar Interface `allDay` events not working (staxDB)
- Fix: Show upcoming event snippet setting ignored (staxDB)
- Fix: Upcoming event snippet sorting (staxDB)
- Enh: Updated fullcalendar.js to v3.9.0
- Enh: Added event icons
- Enh: Added birthdays to calendar
- Enh: Guest support for Upcoming events widget
- Enh: Added `AbstractCalendarQuery::filterGuests()` to support a guest view of the upcoming events snippet
- Enh: Added `AbstractCalendarQuery::filterDashboard()` to enable a custom filter for the upcoming events dashboard snippet
- Enh: Added ICS export of single events (ASBaj)


0.6.4 
-----
- Fix: Minor Grammar fixes (@Felli)
- Enh: Cleanup, remove optional part of parameter (@kristianlm)
- Fix: Fixed typo (@acs-ferreira) 
- Fix: Cancel event deletion not working
- Fix #114: Markdown file upload not attaching files


0.6.3 - 27.10.2017
------------------
- Fix: Delete of global calendar types not working
- Fix #38: Added `calendar_entry_participant` user foreign key
- Fix #66: Only show Participation Badge if participation is allowed


0.6.2 - 20.10.2017
------------------
- Fix #84: Files are not attached to new calendar entries
- Fix #91: Participants can't change state if max participant number is reached


0.6.1 - 21.09.2017
------------------
- Fix: Timezone display issue for all day events
- Enh: Added Calendar Interface for module interoperability
- Enh: Added Calendar Picker if creating events in global calendar
- Fix: Invalid translation keys


0.5.6 - 01.09.2017
------------------
- Fix: ICU 57.1 compatibility for time format HH.mm with whole day setting
- Fix: Drag updates in day/week view are not working
- Fix: Locale mapping between humhub and moment.js


0.5.4 - 08.08.2017
------------------
- Fix: ICU 57.1 compatibility for time format HH.mm


0.5.2 - 08.08.2017
------------------
- Enh: Added global Event Type setting
- Enh: Toggle participation state
- Fix: Rename close to canceled
- Enh: Send update participant info mail
- Fix: Change event type js error
- Fix: TimeZone edit not working correctly
- Fix: word break issue in wall entry


0.5 
----
- Enh: Added timeZone setting in edit form
- Enh: Added TimePicker with locale based time format
- Enh: Seperation of CalendarEntryForm and CalendarEntry model
- Enh: Use user locale in fullCalendar
- Enh: Added richtext description
- Enh: Added global/space module config with default participation settings
- Enh: Enhanced usability
- Enh: Added file upload
- Enh: Added participation state restriction and participation info richtext
- Enh: Close event
- Enh: Max attandee setting

0.4.5 - 30.06.2017
------------------
- Enh: Enable global calendar for guests
- Fix: Hide follow filter if user following is disabled


0.4.4 - 06.06.2017
------------------
- Enh: Major refactoring
- Enh: Usability enhancements
- Enh: Modal based editing
- Enh: Added CalendarEntryQuery class to facilitate calendar entry queries
- Enh: Added Calendar colors
- Enh: Enhanced dashboard/space snippet
- Enh: Guest calendar access
- Enh: Added filter to space calendar
- Enh: Added calendar configuration
- Fix: Added is public translation
- Enh #17: Added ManageEntry permission for space members
