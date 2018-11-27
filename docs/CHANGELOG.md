Changelog
=========

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
