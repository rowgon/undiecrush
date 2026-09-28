<?php

/** Remote-controlled ThemeBay promotional admin notice. */
if (! defined('ABSPATH')) {
	exit;
}

class Tbay_Elementor_Promotion_Notice
{
	const USER_META_KEY = 'tbay_elementor_promotion_notice_dismissed';
	const CACHE_KEY = 'tbay_elementor_promotion_notice_feed_v1';
	const LAST_GOOD_KEY = 'tbay_elementor_promotion_notice_last_good';
	const CACHE_LIFETIME = 21600;
	const REMOTE_FEED_URL = 'https://plugins.thembay.com/notices/promotion.json';
	private $promotion;

	public function __construct()
	{
		add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
		add_action('admin_notices', array($this, 'render'));
		add_action('wp_ajax_tbay_elementor_dismiss_promotion_notice', array($this, 'dismiss'));
	}

	private function should_display()
	{
		if (! current_user_can('manage_options')) {
			return false;
		}
		$promotion = $this->get_promotion();
		if (empty($promotion) || empty($promotion['enabled'])) {
			return false;
		}
		$now = time();
		if (! empty($promotion['start_date']) && $now < strtotime($promotion['start_date'])) {
			return false;
		}
		if (! empty($promotion['end_date']) && $now > strtotime($promotion['end_date'])) {
			return false;
		}
		$dismissed = get_user_meta(get_current_user_id(), self::USER_META_KEY, true);
		$dismissed = is_array($dismissed) ? $dismissed : array();
		return (bool) apply_filters('tbay_elementor_display_promotion_notice', ! in_array($promotion['id'], $dismissed, true), $promotion);
	}

	private function get_promotion()
	{
		if (null !== $this->promotion) {
			return $this->promotion;
		}
		$cached = get_site_transient(self::CACHE_KEY);
		if (is_array($cached)) {
			$this->promotion = $cached;
			return $this->promotion;
		}

		$feed_url = apply_filters('tbay_elementor_promotion_feed_url', self::REMOTE_FEED_URL);
		$response = wp_safe_remote_get($feed_url, array(
			'timeout' => 5,
			'redirection' => 2,
			'limit_response_size' => 51200,
			'user-agent' => 'WPthembay/' . TBAY_ELEMENTOR_VERSION . '; ' . home_url('/'),
		));
		if (! is_wp_error($response) && 200 === wp_remote_retrieve_response_code($response)) {
			$remote = $this->sanitize_promotion(json_decode(wp_remote_retrieve_body($response), true));
			if (! empty($remote)) {
				$this->promotion = $remote;
				$lifetime = max(300, (int) apply_filters('tbay_elementor_promotion_cache_lifetime', self::CACHE_LIFETIME));
				set_site_transient(self::CACHE_KEY, $remote, $lifetime);
				update_option(self::LAST_GOOD_KEY, $remote, false);
				return $this->promotion;
			}
		}
		$last_good = get_option(self::LAST_GOOD_KEY, array());
		$this->promotion = is_array($last_good) && ! empty($last_good) ? $last_good : $this->default_promotion();
		set_site_transient(self::CACHE_KEY, $this->promotion, 15 * MINUTE_IN_SECONDS);
		return $this->promotion;
	}

	private function sanitize_promotion($data)
	{
		if (! is_array($data) || empty($data['id']) || ! array_key_exists('enabled', $data)) {
			return array();
		}
		$id = sanitize_key($data['id']);
		if (empty($id)) {
			return array();
		}
		$benefits = array();
		if (! empty($data['benefits']) && is_array($data['benefits'])) {
			foreach (array_slice($data['benefits'], 0, 3) as $benefit) {
				if (! is_array($benefit) || empty($benefit['label'])) {
					continue;
				}
				$icon = isset($benefit['icon']) ? sanitize_html_class($benefit['icon']) : 'dashicons-yes';
				$benefits[] = array(
					'icon' => 0 === strpos($icon, 'dashicons-') ? $icon : 'dashicons-yes',
					'label' => sanitize_text_field($benefit['label']),
				);
			}
		}
		return array(
			'id' => $id,
			'enabled' => (bool) filter_var($data['enabled'], FILTER_VALIDATE_BOOLEAN),
			'start_date' => $this->sanitize_date(isset($data['start_date']) ? $data['start_date'] : ''),
			'end_date' => $this->sanitize_date(isset($data['end_date']) ? $data['end_date'] : ''),
			'title' => wp_kses(isset($data['title']) ? $data['title'] : '', array('strong' => array())),
			'description' => wp_kses(isset($data['description']) ? $data['description'] : '', array('strong' => array(), 'br' => array())),
			'urgency_label' => sanitize_text_field(isset($data['urgency_label']) ? $data['urgency_label'] : ''),
			'urgency_text' => wp_kses(isset($data['urgency_text']) ? $data['urgency_text'] : '', array('strong' => array())),
			'benefits' => $benefits,
			'button_text' => sanitize_text_field(isset($data['button_text']) ? $data['button_text'] : ''),
			'button_url' => esc_url_raw(isset($data['button_url']) ? $data['button_url'] : '', array('https')),
			'image_url' => esc_url_raw(isset($data['image_url']) ? $data['image_url'] : '', array('https')),
			'promotion_image_url' => esc_url_raw(isset($data['promotion_image_url']) ? $data['promotion_image_url'] : '', array('https')),
		);
	}

	private function sanitize_date($date)
	{
		$date = sanitize_text_field($date);
		return $date && false !== strtotime($date) ? gmdate('c', strtotime($date)) : '';
	}

	private function default_promotion()
	{
		return array(
			'id' => 'thembay-special-offer',
			'enabled' => true,
			'start_date' => '',
			'end_date' => '',
			'title' => 'Special Offer! Get <strong>50% OFF</strong> on All Premium Themes!',
			'description' => 'Purchase directly on ThemBay.com and enjoy <strong>50% OFF</strong> compared to ThemeForest,<br>plus <strong>12 months</strong> of premium support and lifetime updates.',
			'urgency_label' => 'Limited time only!',
			'urgency_text' => 'Offer valid until <strong>August 31, 2026.</strong>',
			'benefits' => array(
				array('icon' => 'dashicons-tag', 'label' => '50% OFF'),
				array('icon' => 'dashicons-phone', 'label' => '12 Months Support'),
				array('icon' => 'dashicons-update', 'label' => 'Lifetime Updates'),
			),
			'button_text' => 'Visit ThemBay.com Now',
			'button_url' => 'https://thembay.com/',
			'image_url' => TBAY_ELEMENTOR_URL . 'assets/images/thembay-studio.svg',
			'promotion_image_url' => '',
		);
	}

	private function asset_version($file)
	{
		$path = TBAY_ELEMENTOR_DIR . ltrim($file, '/\\');
		return file_exists($path) ? (string) filemtime($path) : TBAY_ELEMENTOR_VERSION;
	}

	public function enqueue_assets()
	{
		if (! $this->should_display()) {
			return;
		}
		wp_enqueue_style('tbay-elementor-promotion-notice', TBAY_ELEMENTOR_URL . 'assets/promotion-notice.css', array(), $this->asset_version('assets/promotion-notice.css'));
		wp_enqueue_script('tbay-elementor-promotion-notice', TBAY_ELEMENTOR_URL . 'assets/promotion-notice.js', array('jquery'), $this->asset_version('assets/promotion-notice.js'), true);
		wp_localize_script('tbay-elementor-promotion-notice', 'tbayElementorPromotionNotice', array(
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('tbay_elementor_dismiss_promotion_notice'),
			'promotionId' => $this->get_promotion()['id'],
		));
	}

	public function render()
	{
		if (! $this->should_display()) {
			return;
		}
		$promotion = $this->get_promotion();
?>
		<div class="notice tbay-elementor-promo-notice" role="region" aria-label="<?php esc_attr_e('ThemeBay special offer', 'tbay-elementor'); ?>">
			<button type="button" class="notice-dismiss tbay-elementor-promo-notice__dismiss"><span class="screen-reader-text"><?php esc_html_e('Dismiss this notice.', 'tbay-elementor'); ?></span></button>
			<?php if ($promotion['image_url']) : ?><div class="tbay-elementor-promo-notice__logo"><img src="<?php echo esc_url($promotion['image_url']); ?>" alt="ThemeBay Studio"></div><?php endif; ?>
			<div class="tbay-elementor-promo-notice__content">
				<h2><?php echo wp_kses($promotion['title'], array('strong' => array())); ?></h2>
				<p><?php echo wp_kses($promotion['description'], array('strong' => array(), 'br' => array())); ?></p>
				<?php if ($promotion['urgency_label'] || $promotion['urgency_text']) : ?><div class="tbay-elementor-promo-notice__urgency"><span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span><strong><?php echo esc_html($promotion['urgency_label']); ?></strong><span><?php echo wp_kses($promotion['urgency_text'], array('strong' => array())); ?></span></div><?php endif; ?>
				<?php if ($promotion['benefits']) : ?><ul class="tbay-elementor-promo-notice__benefits" aria-label="<?php esc_attr_e('Offer benefits', 'tbay-elementor'); ?>">
						<?php foreach ($promotion['benefits'] as $benefit) : ?><li><span class="dashicons <?php echo esc_attr($benefit['icon']); ?>" aria-hidden="true"></span><?php echo esc_html($benefit['label']); ?></li><?php endforeach; ?>
					</ul><?php endif; ?>
			</div>
			<div class="tbay-elementor-promo-notice__actions">
				<?php if ($promotion['promotion_image_url']) : ?><img class="tbay-elementor-promo-notice__promotion-image" src="<?php echo esc_url($promotion['promotion_image_url']); ?>" alt="<?php esc_attr_e('50% off limited time offer', 'tbay-elementor'); ?>"><?php endif; ?>
				<?php if ($promotion['button_url'] && $promotion['button_text']) : ?><a class="tbay-elementor-promo-notice__button" href="<?php echo esc_url($promotion['button_url']); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-cart" aria-hidden="true"></span><?php echo esc_html($promotion['button_text']); ?></a><?php endif; ?>
			</div>
		</div>
<?php
	}

	public function dismiss()
	{
		check_ajax_referer('tbay_elementor_dismiss_promotion_notice', 'nonce');
		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => esc_html__('You are not allowed to dismiss this notice.', 'tbay-elementor')), 403);
		}
		$promotion_id = isset($_POST['promotion_id']) ? sanitize_key(wp_unslash($_POST['promotion_id'])) : '';
		if (! $promotion_id) {
			wp_send_json_error(array('message' => esc_html__('Invalid promotion ID.', 'tbay-elementor')), 400);
		}
		$dismissed = get_user_meta(get_current_user_id(), self::USER_META_KEY, true);
		$dismissed = is_array($dismissed) ? $dismissed : array();
		$dismissed[] = $promotion_id;
		$dismissed = array_slice(array_values(array_unique($dismissed)), -20);
		update_user_meta(get_current_user_id(), self::USER_META_KEY, $dismissed);
		wp_send_json_success();
	}
}
