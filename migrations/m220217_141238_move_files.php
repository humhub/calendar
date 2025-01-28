<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\CalendarEntry;
use humhub\modules\file\models\File;
use yii\db\Migration;

/**
 * Class m220217_141238_move_files
 */
class m220217_141238_move_files extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Find all Calendar Entries with attached files
        $entries = CalendarEntry::find()
            ->innerJoin('file', 'file.object_id = calendar_entry.id')
            ->where(['file.object_model' => CalendarEntry::class])
            ->andWhere(['file.show_in_stream' => 1]);

        foreach ($entries->all() as $entry) {
            /* @var CalendarEntry $entry */
            $attachedFilesContent = '';

            // Convert attached files into content/inline files
            foreach ($entry->fileManager->findStreamFiles() as $file) {
                /* @var File $file */
                $attachedFilesContent .= "\r\n";
                if (strpos($file->mime_type, 'image/') === 0) {
                    // Image
                    $attachedFilesContent .= '![](file-guid:' . $file->guid . ' "' . $file->file_name . '")';
                } else {
                    $attachedFilesContent .= '[' . $file->file_name . '](file-guid:' . $file->guid . ')';
                }
            }

            // Append attached files in the end of entry description
            $entry->description .= "\r\n" . $attachedFilesContent;
            $entry->save();
        }

        // Update all attached files to content mode
        $this->execute(
            'UPDATE file
              SET show_in_stream = 0
            WHERE object_model = :CalendarEntryClass
              AND show_in_stream = 1',
            ['CalendarEntryClass' => CalendarEntry::class],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m220217_141238_move_files cannot be reverted.\n";
        return false;
    }
}
