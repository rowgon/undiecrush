<?php $this->layout('theme::partials/form/sections/fieldset'); ?>

<ol <?php echo $this->attr('age-gate-form-elements')?>><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php foreach ($fields as $key => $field) : ?>
        <li <?php echo $this->attr('age-gate-form-section') ?>><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php
                $d = array_merge(
                    [
                        'key' => $key
                    ],
                    $field
                );
            ?>
            <?php echo $this->fetch('theme::partials/form/fields/select', $d) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </li>
    <?php endforeach; ?>
</ol>
