<?php $this->layout('partials/fields/wrapper', ['field' => $field, 'lang' => $lang ?? false]) ?>

<?php $this->start('input') ?>
    <label class="ag-switch">
        <input type="checkbox" name="<?php echo esc_attr($this->form_key($field_prefix . '.' . $name)) ?>" id="<?php echo esc_attr(sanitize_title('wp_age_gate_' . $name)) ?>" <?php echo (($data[$name] ?? false) ? 'checked' : '') ?> <?php echo html_build_attributes($field['attributes'] ?? []); ?> value="1" /><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <span class="ag-switch__slider"></span>
    </label>
<?php $this->stop(); ?>
