<?php $this->beginContent('application.modules_core.activity.views.activityLayout', array('activity' => $activity)); ?>                    
<?php
echo Yii::t('CalendarModule.views_activities_EntryCreated', '%displayName% created a new %contentTitle%.', array(
    '%displayName%' => '<strong>' . $user->displayName . '</strong>',
    '%contentTitle%' => $activity->getUnderlyingObject()->getContentTitle()
));
?>
<?php $this->endContent(); ?>
