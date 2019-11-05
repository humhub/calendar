<?php

namespace humhub\modules\calendar\helpers;

use humhub\modules\calendar\interfaces\CalendarItemType;
use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\calendar\models\CalendarEntryParticipant;
use humhub\modules\calendar\models\CalendarEntryType;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\user\models\User;
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

        return URL::to(['/calendar/config/delete-type', 'id' => $model->id]);
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

    public static function toEditItemType(CalendarItemType $type, ContentContainerActiveRecord $container = null)
    {
        if($container) {
            return $container->createUrl('/calendar/container-config/edit-calendars', ['key' => $type->key]);
        }

        return static::to(['/calendar/config/edit-calendars', 'key' => $type->key]);
    }

    public static function toSettingsReset(ContentContainerActiveRecord $container = null)
    {
        if($container) {
            return $container->createUrl('/calendar/container-config/reset-config');
        }

        return static::to(['/calendar/config/reset-config']);
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

    public static function toFullCalendarDrop(ContentContainerActiveRecord $container)
    {
        return $container->createUrl('/calendar/entry/edit-ajax');
    }

    public static function toEditEntryAjax(CalendarEntry $entry, $cal = null, ContentContainerActiveRecord $container = null)
    {
        if(!$container) {
            $container = $entry->content->container;
        }

        return $container->createUrl('/calendar/entry/edit-ajax', ['id' => $entry->id]);
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

        return $container->createUrl('/calendar/entry/view', $params);
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

    public static function toEntryDownloadICS(CalendarEntry $entry, ContentContainerActiveRecord $container = null)
    {
        if(!$container) {
            $container = $entry->content->container;
        }

        return $container->createUrl('/calendar/entry/generateics', ['id' => $entry->id]);
    }

    public static function toUserLevelReminderConfig(CalendarEntry $entry, ContentContainerActiveRecord $container = null)
    {
        if(!$container) {
            $container = $entry->content->container;
        }

        return $container->createUrl('/calendar/reminder/set', ['id' => $entry->content->id]);
    }

    public static function toEntryRespond(CalendarEntry $entry, $state, ContentContainerActiveRecord $container = null)
    {
        if(!$container) {
            $container = $entry->content->container;
        }

        $participantSate = $entry->getParticipationState();

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

}