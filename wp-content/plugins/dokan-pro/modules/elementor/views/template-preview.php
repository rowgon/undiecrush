<?php
if ( empty( $_GET['id'] ) ) {
    exit;
}

// Reflect only a sanitized screenshot filename so a visitor cannot break out of the src attribute.
$template_id = sanitize_file_name( wp_unslash( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public template preview; the id is sanitized to a screenshot filename.
?>
<img style="width: 100%;" src="<?php echo esc_url( DOKAN_ELEMENTOR_ASSETS . '/images/screenshots/' . $template_id . '.png' ); ?>" alt="">
<?php exit;
