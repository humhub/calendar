<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\widgets\Button;

/* @var Button[] $buttons */
/* @var array $options */
?>
<div class="btn-group calendar-entry-participants-export-button pull-right">
    <?= Button::info()->icon('download')->sm()
        ->link($buttons[0]->getHref())
        ->pjax(false)->loader(false) ?>
    <?= Button::info()->icon('caret-down')->sm()
        ->cssClass('dropdown-toggle')
        ->options(['data-toggle' => 'dropdown'])
        ->loader(false) ?>
    <ul class="dropdown-menu">
        <?php foreach ($buttons as $button) : ?>
            <li><?= $button->pjax(false)->sm() ?></li>
        <?php endforeach; ?>
    </ul>
</div>
