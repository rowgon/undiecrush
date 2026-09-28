<?php
/**
 * Plugin Name: UndieCrush Enhancements (MU-Plugin)
 * Description: Añade avisos de Envío 100% Discreto en el Carrito y Checkout, y habilita la conversión automática de imágenes a formato WebP.
 * Version: 1.0.0
 * Author: Antigravity AI
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. CONVERSIÓN AUTOMÁTICA DE IMÁGENES A WEBP
 * Convierte todas las imágenes JPEG y PNG subidas a la biblioteca a formato ultra ligero WebP.
 */
add_filter('image_editor_output_format', function ($formats) {
    $formats['image/jpeg'] = 'image/webp';
    $formats['image/png']  = 'image/webp';
    return $formats;
});

/**
 * 2. AVISO Y SELLO DE "ENVÍO 100% DISCRETO" EN CARRITO Y CHECKOUT
 */
function undiecrush_render_discreet_shipping_banner() {
    ?>
    <div class="undiecrush-discreet-banner" style="background: linear-gradient(135deg, #18181b 0%, #27272a 100%); color: #f4f4f5; border-left: 4px solid #e11d48; padding: 16px 20px; border-radius: 8px; margin: 20px 0; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 15px;">
        <div style="font-size: 28px; line-height: 1; flex-shrink: 0;">🔒</div>
        <div>
            <h4 style="margin: 0 0 4px 0; color: #fff; font-size: 16px; font-weight: 700; letter-spacing: 0.5px;">Garantía de Envío 100% Discreto y Confidencial</h4>
            <p style="margin: 0; font-size: 14px; color: #a1a1aa; line-height: 1.4;">
                Tu privacidad es nuestra prioridad absoluta. Todos los envíos se entregan en cajas o bolsas de seguridad totalmente selladas y opacas, <strong style="color: #fff;">sin logotipos, nombres de tienda ni descripción externa del contenido</strong>. Ni el repartidor sabrá qué viaja dentro de tu paquete.
            </p>
        </div>
    </div>
    <?php
}
add_action('woocommerce_before_cart', 'undiecrush_render_discreet_shipping_banner', 10);
add_action('woocommerce_before_checkout_form', 'undiecrush_render_discreet_shipping_banner', 10);

/**
 * 3. AÑADIR CASILLA DE CONFIRMACIÓN DE DISCRECIÓN EN EL CHECKOUT
 */
add_action('woocommerce_review_order_before_submit', function() {
    echo '<div style="background: #27272a; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; color: #d4d4d8; display: flex; align-items: center; gap: 10px;">
        <span style="color: #10b981; font-size: 18px;">✓</span>
        <span><strong>Embalaje Protegido:</strong> Tu pedido será empacado y etiquetado bajo estricto protocolo de confidencialidad y anonimato exterior.</span>
    </div>';
});

/**
 * 4. NAVEGACIÓN AGÉNTICA (AI AGENTS / LLMS.TXT ROUTER)
 * Sirve dinámicamente los archivos llms.txt y llms-full.txt con cabeceras estándar para agentes de IA y crawlers.
 */
add_action('init', function() {
    $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (in_array(rtrim($request_uri, '/'), ['/llms.txt', '/.well-known/llms.txt', '/llms-full.txt'])) {
        $file_name = ($request_uri === '/llms-full.txt') ? 'llms-full.txt' : 'llms.txt';
        $file_path = ABSPATH . $file_name;
        if (file_exists($file_path)) {
            header('Content-Type: text/markdown; charset=UTF-8');
            header('X-Robots-Tag: index, follow');
            header('Access-Control-Allow-Origin: *');
            readfile($file_path);
            exit;
        }
    }
});

/**
 * 5. OPTIMIZACIÓN EXTREMA DE RENDIMIENTO MÓVIL Y CORE WEB VITALS (LCP / FID / INP)
 * Elimina scripts superfluos en móviles, emojis, dashicons innecesarios y aplaza fragmentos AJAX de WooCommerce en páginas estáticas.
 */
add_action('init', function() {
    // Desactivar scripts de emojis que consumen peticiones HTTP en móvil
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
});

// Desactivar fragmentos de carrito en páginas donde el carrito no se modifica (acelera carga móvil)
add_action('wp_enqueue_scripts', function() {
    if (!is_admin() && !is_user_logged_in() && !is_cart() && !is_checkout()) {
        wp_dequeue_style('dashicons');
    }
    if (function_exists('is_woocommerce') && !is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
        wp_dequeue_script('wc-cart-fragments');
    }
}, 99);

/**
 * 6. MEJORAS DE ACCESIBILIDAD Y ARIA (AUDITORÍA LIGHTHOUSE & NAVEGACIÓN AGÉNTICA)
 * Asegura formato correcto del árbol de accesibilidad y roles semánticos en diálogos/modales de verificación de edad.
 */
add_action('wp_footer', function() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Enriquecer árbol de accesibilidad para modales y Age Gate
        var modals = document.querySelectorAll('.age-gate-wrapper, .elementor-popup-modal, [role="dialog"], .shopkeeper-modal');
        modals.forEach(function(modal) {
            if (!modal.getAttribute('role')) modal.setAttribute('role', 'dialog');
            if (!modal.getAttribute('aria-modal')) modal.setAttribute('aria-modal', 'true');
            if (!modal.getAttribute('aria-label') && !modal.getAttribute('aria-labelledby')) {
                modal.setAttribute('aria-label', 'Verificación de Edad y Seguridad');
            }
        });

        // Asegurar que botones sin texto explícito tengan etiquetas ARIA descriptivas para lectores y agentes IA
        var iconButtons = document.querySelectorAll('button:not([aria-label]):not([aria-labelledby]), a[role="button"]:not([aria-label])');
        iconButtons.forEach(function(btn) {
            var text = btn.textContent.trim();
            if (!text) {
                var icon = btn.querySelector('i, svg, img');
                if (icon && icon.className) {
                    btn.setAttribute('aria-label', 'Acción de interfaz: ' + icon.className);
                } else {
                    btn.setAttribute('aria-label', 'Botón interactivo');
                }
            }
        });
    });
    </script>
    <?php
}, 99);
