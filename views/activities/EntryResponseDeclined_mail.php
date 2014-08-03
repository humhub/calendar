<?php $this->beginContent('application.modules_core.activity.views.activityLayoutMail', array('activity' => $activity, 'showSpace' => true)); ?>
<?php
echo Yii::t('CalendarModule.views_activities_EntryResponse', '%displayName% not attends to %contentTitle%.', array(
    '%displayName%' => '<strong>' . $user->displayName . '</strong>',
    '%contentTitle%' => $activity->getUnderlyingObject()->getContentTitle()
));
?>
<?php $this->endContent(); ?>    