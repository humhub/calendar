<div class="panel panel-default">
    <div class="panel-body">

        <?php $this->beginContent('application.modules_core.wall.views.wallLayout', array('object' => $calendarEntry)); ?>


        <div class="pull-right">


            <?php if ($calendarEntry->canRespond() && !$calendarEntry->hasResponded()): ?>
                <?php echo CHtml::link(Yii::t('CalendarModule.views_entry_view', "Attend"), $calendarEntry->createContainerUrlTemp('/calendar/entry/respond', array('type' => CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED, 'id' => $calendarEntry->id)), array('class' => 'btn btn-success')); ?>
                <?php echo CHtml::link(Yii::t('CalendarModule.views_entry_view', "Maybe"), $calendarEntry->createContainerUrlTemp('/calendar/entry/respond', array('type' => CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE, 'id' => $calendarEntry->id)), array('class' => 'btn btn-default')); ?>
                <?php echo CHtml::link(Yii::t('CalendarModule.views_entry_view', "Decline"), $calendarEntry->createContainerUrlTemp('/calendar/entry/respond', array('type' => CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED, 'id' => $calendarEntry->id)), array('class' => 'btn btn-default')); ?>
            <?php endif; ?>

            <?php if ($calendarEntry->hasResponded()): ?>
                <?php
                $participationModes = array();
                $participationModes[CalendarEntryParticipant::PARTICIPATION_STATE_ACCEPTED] = Yii::t('CalendarModule.views_entry_view', "I´m attending");
                $participationModes[CalendarEntryParticipant::PARTICIPATION_STATE_MAYBE] = Yii::t('CalendarModule.views_entry_view', "I´m maybe attending");
                $participationModes[CalendarEntryParticipant::PARTICIPATION_STATE_DECLINED] = Yii::t('CalendarModule.views_entry_view', "I´m not attending");
                ?>

                <div class="btn-group">
                    <button type="button" class="btn btn-success"><?php echo $participationModes[$calendarEntryParticipant->participation_state]; ?></button>
                    <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                        <span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        <?php
                        unset($participationModes[$calendarEntryParticipant->participation_state]);
                        ?>

                        <?php foreach ($participationModes as $participationMode => $title): ?>
                            <li><?php echo CHtml::link($title, $calendarEntry->createContainerUrlTemp('/calendar/entry/respond', array('type' => $participationMode, 'id' => $calendarEntry->id)), array('class' => '')); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <br />            
        </div>


        <strong>Event: <?php echo $calendarEntry->title; ?></strong><br />
        <?php $this->widget('application.modules.calendar.widgets.CalendarEntryDateWidget', array('calendarEntry' => $calendarEntry)); ?><br />
        <br />
        <?php $this->widget('application.modules.calendar.widgets.CalendarEntryParticipantsWidget', array('calendarEntry' => $calendarEntry)); ?><br />

        <?php if ($calendarEntry->description != ""): ?>
            <?php $this->beginWidget('CMarkdown'); ?><?php echo nl2br($calendarEntry->description); ?><?php $this->endWidget(); ?>
        <?php endif; ?>

        <?php $this->endContent(); ?>

    </div>
</div>