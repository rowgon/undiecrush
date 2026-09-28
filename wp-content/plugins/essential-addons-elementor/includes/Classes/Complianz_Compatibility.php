<?php

namespace Essential_Addons_Elementor\Pro\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Complianz_Compatibility {

	public function __construct() {
		if ( ! $this->is_complianz_active() ) {
			return;
		}

		add_filter( 'cmplz_known_script_tags', [ $this, 'register_script_tags' ] );
		add_filter( 'cmplz_detected_services', [ $this, 'register_detected_services' ] );
		add_filter( 'cmplz_placeholder_markers', [ $this, 'register_placeholder_markers' ] );
		add_filter( 'cmplz_dependencies', [ $this, 'register_dependencies' ] );
		// Complianz enqueues cmplz-cookiebanner at PHP_INT_MAX - 50, we need to run after that.
		add_action( 'wp_enqueue_scripts', [ $this, 'add_reinit_script' ], PHP_INT_MAX - 40 );
		add_action( 'wp_head', [ $this, 'add_placeholder_styles' ] );
	}

	private function is_complianz_active() {
		return defined( 'cmplz_free' ) || defined( 'cmplz_premium' );
	}

	public function register_script_tags( $tags ) {
		$tags[] = 'gmap/gmap.min.js';
		$tags[] = 'adv-google-map';
		$tags[] = 'maps.googleapis.com';

		return $tags;
	}

	public function register_detected_services( $services ) {
		if ( ! in_array( 'google-maps', $services ) ) {
			$services[] = 'google-maps';
		}

		return $services;
	}

	public function register_placeholder_markers( $tags ) {
		$tags['google-maps'][] = 'eael-google-map';

		return $tags;
	}

	public function register_dependencies( $tags ) {
		$tags['maps.googleapis.com'] = 'adv-google-map';

		return $tags;
	}

	/**
	 * Hide the map error notice until scripts are unblocked,
	 * preventing the "no API key" flash before consent resolves.
	 */
	public function add_placeholder_styles() {
		?>
		<style>.elementor-widget-eael-google-map .google-map-notice { display: none; }</style>
		<?php
	}

	public function add_reinit_script() {
		if ( ! function_exists( 'cmplz_uses_thirdparty' ) || ! cmplz_uses_thirdparty( 'google-maps' ) ) {
			return;
		}

		ob_start();
		?>
		<script>
			document.addEventListener("cmplz_run_after_all_scripts", cmplz_eael_fire_google_map);
			function cmplz_eael_fire_google_map() {
				setTimeout(function() {
					if (typeof elementorFrontend === "undefined") {
						return;
					}
					var mapWidgets = document.querySelectorAll(".elementor-widget-eael-google-map");
					mapWidgets.forEach(function(widget) {
						var mapEl = widget.querySelector(".eael-google-map");
						var noticeEl = widget.querySelector(".google-map-notice");
						if (mapEl) {
							// Restore map visibility hidden by the "no API key" error handler
							mapEl.style.display = "";
							jQuery(mapEl).removeData("eael-map-initialized");
							jQuery(mapEl).removeData("eael-map-pending");
						}
						if (noticeEl) {
							// Clear the error notice
							noticeEl.innerHTML = "";
							noticeEl.className = "google-map-notice";
							noticeEl.removeAttribute("style");
						}
						if (typeof elementorFrontend.elementsHandler !== "undefined") {
							elementorFrontend.elementsHandler.runReadyTrigger(jQuery(widget));
						}
					});
				}, 2000);
			}
		</script>
		<?php
		$script = ob_get_clean();
		$script = str_replace( array( '<script>', '</script>' ), '', $script );
		wp_add_inline_script( 'cmplz-cookiebanner', $script );
	}
}
