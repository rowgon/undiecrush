<<?php echo esc_attr($settings->subHeadlineElement ?: 'p') ?> <?php echo $this->attr('age-gate-subheadline') ?>><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <?php echo $this->mdLine(esc_html(sprintf($this->stringTemplate($settings->subheadline, ['age' => $content->getAge($settings->anonymous)]), $content->getAge($settings->anonymous)))) ?><?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</<?php echo esc_attr($settings->subHeadlineElement ?: 'p') ?>>
