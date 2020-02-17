<?php

namespace humhub\modules\calendar\helpers;

use humhub\modules\calendar\interfaces\reminder\CalendarEventReminderIF;
use humhub\modules\calendar\interfaces\event\CalendarTypeIF;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\user\models\User;
use Yii;
use yii\helpers\Url as BaseUrl;

class Url extends BaseUrl
{
    public static function toConfig(ContentContainerActiveRecord $container = null)
    {
        if($container) {
            return $container->createUrl('/calendar/container-config');
        }

        return  BaseUrl::to(['/calendar/config']);
    }

    public static function toConfigTypes(ContentContainerActiveRecord $container = null)
    {
        if($container) {
            return $container->createUrl('/calendar/container-config/types');
        }

        return static::to(['/calendar/config/types']);
    }

    public static function toEditType(CalendarEntryType $model, ContentContainerActiveRecord $container = null)
    {
        if($container) {
            return $container->createUrl('/calendar/container-config/edit-type',  ['id' => $model->id] );
        }

        return static::to(['/calendar/config/edit-type', 'id' => $model->id]) ;
    }

    public static function toCreateType(ContentContainerActiveRecord $container = null)
    {
        if($container) {
            return $container->createUrl('/calendar/container-config/edit-type' );
        }

        return static::to(['/calendar/config/edit-type']) ;
    }

    public static function toDeleteType(CalendarEntryType $model, ContentContainerActiveRecord $container = null)
    {
        if($container) {
            return $container->createUrl('/calendar/container-config/delete-type', ['id' => $model->id]);
        }

        return static::to(['/calendar/config/delete-type', 'id' => $model->id]);
    }

    public static function toConfigCalendars(ContentContainerActiveRecord $container = null)
    {
        if($container) {
            return $container->createUrl('/calendar/container-config/calendars');
        }

        return static::to(['/calendar/config/calendars']);
    }

    public static function toConfigSnippets()
    {
        return static::toRoute(['/calendar/config/snippet']);
    }

    public static function toCalendar(ContentContainerActiveRecord $container = null)
    {
        if($container) {
            return $container->createUrl('/calendar/view/index');
        }

        return static::toGlobalCalendar();
    }

    public static function toGlobalCalendar()
    {
        return static::to(['/calendar/global/index']);
    }

    public static function toEditItemType(CalendarTypeIF $type, ContentContainerActiveRecord $container = null)
    {
        if($container) {
            return $container->createUrl('/calendar/container-config/edit-calendars', ['key' => $type->getKey()]);
        }

        return static::to(['/calendar/config/edit-calendars', 'key' => $type->getKey()]);
    }

    public static function toParticipationSettingsReset(ContentContainerActiveRecord $container = null)
    {
        if($container) {
            return $container->createUrl('/calendar/container-config/reset-participation-config');
        }

        return static::to(['/calendar/config/reset-participation-config']);
    }

    public static function toAjaxLoad(ContentContainerActiveRecord $container = null)
    {
        if($container) {
            return $container->createUrl('/calendar/view/load-ajax');
        }

        return static::to(['/calendar/global/load-ajax']);
    }

    public static function toGlobalCreate()
    {
        return static::to(['/calendar/global/select']);
    }

    public static function toEditEntry(CalendarEntry $entry, $cal = null, ContentContainerActiveRecord $container = null)
    {
        if(!$container) {
            $container = $entry->content->container;
        }

        return $container->createUrl('/calendar/entry/edit', ['id' => $entry->id, 'cal' => $cal]);
    }

    public static function toFullCalendarEdit(ContentContainerActiveRecord $container)
    {
        return $container->createUrl('/calendar/entry/edit', ['cal' => 1]);
    }

    public static function toCreateEntry(ContentContainerActiveRecord $container, $start = null, $end = null)
    {
        return $container->createUrl('/calendar/entry/edit', ['start' => $start, 'end' => $end]);
    }

    public static function toEnableProfileModule(User $user)
    {
        return $user->createUrl('/user/account/enable-module', ['moduleId' => 'calendar']);
    }

    public static function toEntry(CalendarEntry $entry, $cal = 0, ContentContainerActiveRecord $container = null)
    {
        if(!$container) {
            $container = $entry->content->container;
        }

        $params =  ['id' => $entry->id];
        if($cal) {
            $params['cal'] = 1;
        }

        if(RecurrenceHelper::isRecurrentInstance($entry)) {
            $params['parent_id'] = $entry->parent_event_id;
            $params['recurrence_id'] = $entry->recurrence_id;
            return $container->createUrl('/calendar/entry/view-recurrence', $params);
        }

        // Container should always be present but, in order to prevent null pointer (https://community.humhub.com/s/general-discussion/?contentId=209345)
        return $container ? $container->createUrl('/calendar/entry/view', $params) : '';
    }

    public static function toEntryDelete(CalendarEntry $entry, $cal = 0, ContentContainerActiveRecord $container = null)
    {
        if(!$container) {
            $container = $entry->content->container;
        }

        $params =  ['id' => $entry->id];
        if($cal) {
            $params['cal'] = 1;
        }

        return $container->createUrl('/calendar/entry/delete', $params);
    }

    public static function toEntryToggleClose(CalendarEntry $entry, ContentContainerActiveRecord $container = null)
    {
        if(!$container) {
            $container = $entry->content->container;
        }

        return $container->createUrl('/calendar/entry/toggle-close', ['id' => $entry->id]);
    }

    public static function toEntryDownloadICS(ContentActiveRecord $entry, ContentContainerActiveRecord $container = null)
    {
        if(!$container) {
            $container = $entry->content->container;
        }

        return $container->createUrl('/calendar/ical/export', ['id' => $entry->content->id]);
    }

    public static function toUserLevelReminderConfig(CalendarEventReminderIF $entry, ContentContainerActiveRecord $container = null)
    {
        if(!$container) {
            $container = $entry->getContentRecord()->container;
        }

        return $container->createUrl('/calendar/reminder/set', ['id' => $entry->getContentRecord()->id]);
    }

    public static function toEntryRespond(CalendarEntry $entry, $state, ContentContainerActiveRecord $container = null)
    {
        if(!$container) {
            $container = $entry->content->container;
        }

        $participantSate = $entry->participation->getParticipationStatus(Yii::$app->user->getidentity());

        return $container->createUrl('/calendar/entry/respond', [
            'type' => $participantSate == $state ? CalendarEntryParticipant::PARTICIPATION_STATE_NONE : $state,
            'id' => $entry->id]);
    }

    public static function toParticipationUserList(CalendarEntry $entry, $state, ContentContainerActiveRecord $container = null)
    {
        if(!$container) {
            $container = $entry->content->container;
        }

        return $container->createUrl('/calendar/entry/user-list', ['id' => $entry->id, 'state' => $state]);
    }

    public static function toEnableModuleOnProfileConfig()
    {
        if(Yii::$app->user->isGuest) {
            return null;
        }

        return Yii::$app->user->identity->createUrl('/calendar/global/enable-config');
    }

    public static function toUpdateEntry(ContentActiveRecord $entry)
    {
        return static::to(['/calendar/full-calendar/update', 'id' => $entry->content->id]);
    }
}