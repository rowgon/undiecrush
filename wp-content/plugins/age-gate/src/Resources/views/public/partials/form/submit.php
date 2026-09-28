<div <?php echo $this->attr('age-gate-submit-section') ?>><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <button type="submit" name="ag_settings[submit]" value="1" <?php echo $this->attr('age-gate-submit') ?>><?php echo esc_html($settings->labelSubmit) ?></button><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
