<div <?php echo $this->attr('age-gate-form-fields') ?>><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php do_action('age_gate/fields/before'); ?>
    <?php echo $this->section('content') ?><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php do_action('age_gate/fields/after') ?>
</div>
