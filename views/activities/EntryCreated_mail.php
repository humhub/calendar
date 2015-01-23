<?php $this->beginContent('application.modules_core.activity.views.activityLayoutMail', array('activity' => $activity, 'showSpace' => true)); ?>
<?php

echo Yii::t('CalendarModule.views_activities_EntryCreated', '%displayName% created a new %contentTitle%.', array(
    '%displayName%' => '<strong>' . CHtml::encode($user->displayName) . '</strong>',
    '%contentTitle%' => ActivityModule::formatOutput($activity->getUnderlyingObject()->getContentTitle())
));
?>
<?php $this->endContent(); ?>    