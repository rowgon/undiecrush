<?php if ($errors) : ?>

    <div <?php echo $this->attr('age-gate-errors') ?>><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

        <?php
            $key = array_key_last($errors);
            $error = end($errors);
        ?>
        <p <?php echo $this->attr('age-gate-error') ?>><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php echo $this->mdLine(esc_html(apply_filters('age_gate/error/' . $key, $error))) ?><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </p>

    </div>
<?php endif; ?>
<?php if (is_customize_preview()) : ?>
    <div <?php echo $this->attr('age-gate-errors') ?>><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <p <?php echo $this->attr('age-gate-error') ?>><?php echo esc_html(apply_filters('age_gate/error/demo', __('Demonstration error message', 'age-gate'))) ?></p><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
<?php endif; ?>
<?php if ($settings->method === 'js') : ?>
    <div <?php echo $this->attr('age-gate-errors') ?>></div><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endif ?>
