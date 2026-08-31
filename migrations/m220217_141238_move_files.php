<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2022 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\calendar\models\CalendarEntry;
use yii\db\Migration;
use yii\db\Query;

/**
 * Class m220217_141238_move_files
 *
 * Note: This migration intentionally avoids the CalendarEntry ActiveRecord
 * class's find()/save() calls (only its class name is referenced, via
 * CalendarEntry::class, purely to build the object_model string). Historical
 * data migrations must not depend on the *current* model's validation rules
 * or schema expectations, since these may have moved on (e.g. columns added
 * by later migrations that haven't run yet in this batch). Working with the
 * table names and Query Builder directly keeps this migration self-contained.
 */
class m220217_141238_move_files extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Find all Calendar Entries with attached stream files,
        // joined with the file rows themselves.
        // One row per (entry, file) pair.
        $query = (new Query())
            ->select([
                'entryId' => 'calendar_entry.id',
                'description' => 'calendar_entry.description',
                'guid' => 'file.guid',
                'file_name' => 'file.file_name',
                'mime_type' => 'file.mime_type',
            ])
            ->from('calendar_entry')
            ->innerJoin('file', 'file.object_id = calendar_entry.id')
            ->where(['file.object_model' => CalendarEntry::class])
            ->andWhere(['file.show_in_stream' => 1]);

        // Group rows by Calendar Entry
        $entries = [];
        foreach ($query->each(100, $this->db) as $row) {
            $entryId = $row['entryId'];
            $entries[$entryId] ??= [
                'description' => $row['description'],
                'files' => [],
            ];
            $entries[$entryId]['files'][] = $row;
        }

        foreach ($entries as $entryId => $entry) {
            $attachedFilesContent = '';

            // Convert attached files into content/inline files
            foreach ($entry['files'] as $file) {
                $attachedFilesContent .= "\r\n";
                if (str_starts_with((string) $file['mime_type'], 'image/')) {
                    // Image
                    $attachedFilesContent .= '![](file-guid:' . $file['guid'] . ' "' . $file['file_name'] . '")';
                } else {
                    $attachedFilesContent .= '[' . $file['file_name'] . '](file-guid:' . $file['guid'] . ')';
                }
            }

            // Append attached files in the end of entry description
            $this->update(
                'calendar_entry',
                ['description' => $entry['description'] . "\r\n" . $attachedFilesContent],
                ['id' => $entryId],
            );
        }

        // Update all attached files to content mode
        $this->update(
            'file',
            ['show_in_stream' => 0],
            [
                'object_model' => CalendarEntry::class,
                'show_in_stream' => 1,
            ],
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
