<?php

use yii\db\Schema;
use humhub\components\Migration;
use module\calendar\models\CalendarEntry;
use module\calendar\models\CalendarEntryParticipant;

class m150707_134615_update extends Migration
{

    public function up()
    {
        // Namespace Classes
        $this->renameClass('CalendarEntry', CalendarEntry::className());
        $this->renameClass('CalendarEntryParticipant', CalendarEntryParticipant::className());

        // Merge EntryCreated to ContentCreated Activity
        $this->update('activity', ['class' => 'humhub\modules\content\activities\ContentCreated'], ['class' => 'EntryCreated']);
        $this->update('activity', ['class' => \module\calendar\activities\ResponseAttend::className()], ['class' => 'EntryResponseAttend']);
        $this->update('activity', ['class' => \module\calendar\activities\ResponseDeclined::className()], ['class' => 'EntryResponseDeclined']);
        $this->update('activity', ['class' => \module\calendar\activities\ResponseMaybe::className()], ['class' => 'EntryResponseMaybe']);
    }

    public function down()
    {
        echo "m150707_134615_update cannot be reverted.\n";

        return false;
    }

    /*
      // Use safeUp/safeDown to run migration code within a transaction
      public function safeUp()
      {
      }

      public function safeDown()
      {
      }
     */
}
