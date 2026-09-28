<?php if ($settings->remember) : ?>
    <div <?php echo $this->attr('age-gate-remember-wrapper') ?>><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <label <?php echo $this->attr('age-gate-remember') ?>><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <input <?php echo $this->attr('age-gate-remember-checkbox') ?> <?php checked($settings->rememberAutoCheck) ?> /> <span <?php echo $this->attr('age-gate-remember-text') ?>><?php echo esc_html($settings->labelRemember) ?></span><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </label>
    </div>
<?php endif; ?>
