<?php $this->layout('layouts/' . $settings->method);?>
<?php //$this->layout('layouts/standard');?>

<div <?php echo $this->attr('age-gate-wrapper'); ?>><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php if ($settings->method === 'js') : ?>
        <?php $this->insert('theme::partials/decoration/loader'); ?>
    <?php endif; ?>
    <div <?php echo $this->attr('age-gate-background-colour'); ?>></div><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <div <?php echo $this->attr('age-gate-background'); ?>><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <?php do_action('age_gate/form/background', $settings->backgroundImage) ?>
    </div>
    <?php do_action('age_gate/before'); ?>

    <div <?php echo $this->attr('age-gate'); ?>><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php
        do_action('age_gate/form/before');

        /**
         * @hooked AgeGate\ - 10
         */
        do_action('age_gate/form');

        do_action('age_gate/form/after');
        ?>
    </div>
    <?php do_action('age_gate/after'); ?>
</div>
