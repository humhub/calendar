<?php $this->beginContent('application.modules_core.activity.views.activityLayout', array('activity' => $activity)); ?>                    
<?php
echo Yii::t('CalendarModule.views_activities_EntryResponse', '%displayName% not attends to %contentTitle%.', array(
    '%displayName%' => '<strong>' . $user->displayName . '</strong>',
    '%contentTitle%' => $activity->getUnderlyingObject()->getContentTitle()
));
?>
<?php $this->endContent(); ?>
