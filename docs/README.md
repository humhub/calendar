[![Build Status](https://travis-ci.org/humhub/humhub-modules-calendar.svg?branch=master)](https://travis-ci.org/humhub/humhub-modules-calendar)

# Calendar

Create events on profile or space level and allow other modules add calendar events to your calendar. This module
serves as basic calendar module and can be used in combination with the following modules:

 - [Task](https://www.humhub.com/marketplace/tasks/)
 - [Meetings](https://www.humhub.com/marketplace/meeting/)
 - [External Calendar](https://www.humhub.com/marketplace/meeting/)
 - and others..
 
## Overview

 - Global event overview
 - Event participation management
 - Recurrent events
 - Event reminder
 - Event information as description and files
 - Global and Space level default settings
 - ICS export
 
## Installation

A git installation requires calling `composer install` within the module root. 
This is not required when installing by marketplace.

## Build

In order to build `resources/fullcalendar.bundle.min.js` run:

```
npm install
```

and

```
grunt build
```
 
## Further Information

Please refer to the [Developer Section](DEVELOPER.md) for more information.
