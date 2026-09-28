<?php $this->layout('theme::partials/form/sections/fieldset'); ?>

<<?php echo esc_attr($settings->challengeElement ?: 'p') ?> <?php echo $this->attr('age-gate-challenge') ?>><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php echo $this->mdLine(esc_html(sprintf($this->stringTemplate($settings->labelButtons, ['age' => $content->getAge($settings->anonymous)]), $content->getAge($settings->anonymous)))) ?><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</<?php echo esc_attr($settings->challengeElement ?: 'p') ?>>
<div <?php echo $this->attr('age-gate-buttons') ?>><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php if ($settings->buttonOrder === 'no-yes'): ?>
        <button <?php echo $this->attr('age-gate-submit-no') ?> type="submit"><?php echo esc_html($settings->labelNo) ?></button><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php endif ?>
    <button type="submit" <?php echo $this->attr('age-gate-submit-yes') ?>><?php echo esc_html($settings->labelYes) ?></button><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php if ($settings->buttonOrder !== 'no-yes'): ?>
        <button <?php echo $this->attr('age-gate-submit-no') ?> type="submit"><?php echo esc_html($settings->labelNo) ?></button><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php endif ?>
</div>
