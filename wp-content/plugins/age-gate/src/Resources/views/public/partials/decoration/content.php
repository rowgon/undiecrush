<?php if ($preview['content'] ?? $settings->content) : ?>
    <div <?php echo $this->attr('age-gate-additional-information') ?>><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php echo wp_kses_post(wp_unslash(do_shortcode($this->mdText($preview['content'] ?? $settings->content)))) ?>
    </div>
<?php endif; ?>
