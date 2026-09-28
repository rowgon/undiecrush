<?php do_action('age_gate/script_template/before'); ?>
<<?php echo esc_attr($settings->renderer ?: 'template') ?> id="tmpl-age-gate" <?php echo $settings->renderer === 'script' ? 'type="text/template"' : '' ?> <?php echo $this->attr('age-gate-template'); ?>><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php do_action('age_gate/script_content/before'); ?>
    <?php echo $this->section('content'); ?><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php do_action('age_gate/script_content/after'); ?>
</<?php echo esc_attr($settings->renderer ?: 'template') ?>>
<?php do_action('age_gate/script_template/after'); ?>
