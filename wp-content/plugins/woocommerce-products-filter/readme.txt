=== HUSKY - Products Filter Professional for WooCommerce ===
Contributors: realmag777
Tags: filter, product filter, woocommerce, woof, ajax filter
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 3.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 6.0
WC tested up to: 10.9

HUSKY - WooCommerce Products Filter Professional (formerly WOOF) - flexible, easy and robust professional products filter for WooCommerce.

== Description ==

**[HUSKY - WooCommerce Products Filter Professional](https://demo.products-filter.com/)** (formerly known as WOOF) is an advanced and versatile filter plugin that enhances the functionality of WooCommerce. It empowers your website visitors to easily search and filter products based on categories, attributes, tags, taxonomies, meta fields, and product price. With its powerful and user-friendly features, HUSKY provides a seamless filtering experience to help customers find the desired products efficiently.

🔗 HUSKY enables the generation of [SEO-friendly links](https://demo.products-filter.com/swoof/color-red/product_cat-sport/) such as: `https://yoursite.com/swoof/color-red/product_cat-sport/`. These links can be included in your sitemap file, contributing to improved search engine rankings.

🛠️ Using the [Filter Front Builder](https://demo.products-filter.com/filter-front-builder-demo/) HUSKY allows you to create filter forms directly on the site front end.

✅ Supports the latest version of WooCommerce. A must-have plugin for your WooCommerce-powered online store!

👨‍💻 If you are a WP+PHP developer and want to create something unusual in the search form interface — using HUSKY extension API and default extensions as examples, you can create any HTML items for the search form and even custom WooCommerce products loop templates for your own purposes.

🐘 PHP 7.4 - 8.x full compatibility.

🌐 Demo site 1: [demo.products-filter.com](https://demo.products-filter.com/)
🌐 Demo site 2: [Filter Form Front Builder](https://demo.products-filter.com/filter-front-builder-demo/)
🌐 Demo site 3: [Smart Designer - filter elements constructor](https://demo-sd.products-filter.com/)
🌐 Demo site 4: [demo10k.products-filter.com - 10,000 products](https://demo10k.products-filter.com/)
🌐 Demo site 5: [turbo.products-filter.com - 23,000 products](https://turbo.products-filter.com/)


=== HUSKY Products Filter Features ===

🌐 **Representation:** HUSKY can be used as a Shortcode or as a Widget. The special shortcode `[woof]` allows inserting the product filter in any part of your site. Product taxonomies and attributes can be displayed in the search form as: radio, checkbox, drop-down, multi-drop-down, radio buttons in drop-down, color, image, label, hierarchy drop-down, [attributes/taxonomy range-slider](https://demo.products-filter.com/taxonomy-range-slider/), checkbox buttons in drop-down.

🛠️ **Filter Form Front Builder:** Assemble a custom filter form directly on the site front end using the shortcode `[woof_front_builder]`. [Demo](https://products-filter.com/extencion/filter-form-front-builder/)

🛒 **Products shortcode:** `[woof_products per_page=8 columns=3 is_ajax=1 taxonomies=product_cat:9]` allows displaying and filtering targeted products on a single page or as part of post content in redirect or AJAX mode. Using the `custom_tpl` attribute it is possible to use fully custom templates for maximum flexibility.

🔄 **Products searching by AJAX:** Optionally allows filtering WooCommerce products without page reloading. Works with 95% of WordPress themes.

📊 **Dynamic products recount:** Displays in the search form how many relevant results will be found if a filter element is selected.

🔍 [**Filter products by Meta Data:**](https://products-filter.com/extencion/woocommerce-filter-by-meta-fields/) Allows adding meta fields data (text and number type) into the filter search flow via the plugin settings page.

🔢 **Search products by SKU:** HUSKY allows filtering products by SKU as part of the main search form or via the special shortcode `[woof_sku_filter]`.

💲 **Search products by Price:** Can be displayed as a range slider or as a drop-down with range selection.

📝 **WooCommerce products text search:** Search by title, content, excerpt, and their combinations. Supports the special shortcode `[woof_text_filter]`.

🎨 [**Smart Designer:**](https://demo-sd.products-filter.com/) A custom filter elements design constructor that allows the WooCommerce shop admin to create specially designed filter elements for specific business purposes.

🔧 **Filter by ACF fields:** Supported ACF types: `select`, `radio`, `true_false`. Create meta fields in ACF panel, set meta data to products, filter by them.

🧩 [**Step by step products filter:**](https://products-filter.com/extencion/woocommerce-step-by-step-filter/) Allows creating a products wizard where customers can step by step select the products they want to buy.

📈 [**Statistics:**](https://products-filter.com/extencion/statistic/) Analyze search data and understand what is most interesting to your customers.

⚡ [**Quick Search:**](https://products-filter.com/extencion/quick-search/) Instant search regardless of products quantity — without AJAX or page reloading.

🚀 [**Turbo Mode:**](https://products-filter.com/extencion/turbo-mode/) Avoids generating large MySQL queries while filtering products on the front end.

📬 [**Products Messenger:**](https://products-filter.com/extencion/products-messenger/) Allows logged-in customers to subscribe to filter combinations and be notified when matching products appear in the shop. Supports widget and shortcode `[woof_products_messenger]`.

💾 [**Saver of Search Query:**](https://products-filter.com/extencion/saver-of-search-query/) Allows customers to save search combinations and access them in the future with one click.

♾️ [**Infinite Scrolling:**](https://products-filter.com/make-infinite-scroll-for-filtered-products-also/) Load and browse WooCommerce products without clicking pagination buttons.

🖼️ Images can be used as filter HTML elements in the [search form](https://demo.products-filter.com/all-by-images-only/).

🎨 Colors can be used as filter HTML elements in the [search form](https://demo.products-filter.com/clothing-by-color-only/).

💲 Price filter as range-slider OR as drop-down.

🔘 Show the hidden search form as a [BUTTON](https://demo.products-filter.com/hidden-search-form/).

🎛️ Different skins can be selected for radio and checkbox HTML elements in the filter form.

📝 Possibility to create custom products layout templates and use them with `[woof_products]` shortcode in AJAX and redirect mode via `custom_tpl` and `tpl_index` attributes.

🔧 Possible to create any extensions for the plugin (for developers). See the `ext` folder for code examples.

📚 HUSKY has a wide API described in the [CODEX](https://products-filter.com/codex/).

🔗 HUSKY uses native WooCommerce API which allows coexistence and cooperation with other WooCommerce plugins.

🌍 [WPML compatible](https://wpml.org/extensions/woof-woocommerce-products-filter/)

💱 Compatible with WooCommerce Currency Switcher.

🤖 **HUSKY is ChatGPT-friendly.** You can ask ChatGPT about plugin features using the former name WOOF. Example: "what attribute to use to make redirection in woof shortcode"

🛠️ **Strong technical support that works with real code every day.**


== Installation ==

1. Upload the `woocommerce-products-filter` folder to the `/wp-content/plugins/` directory, or install via the WordPress admin interface.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Make sure WooCommerce is installed and activated.
4. Go to **WooCommerce > HUSKY** to configure the plugin.
5. Add the `[woof]` shortcode to any page, or use the HUSKY widget in your sidebar.


== Frequently Asked Questions ==

= Where can I see a demo? =
* [Main demo](https://demo.products-filter.com/)
* [Smart Designer](https://demo-sd.products-filter.com/)
* [Demo with 10,000 products](https://demo10k.products-filter.com/)
* [Demo Turbo with 23,000 products](https://turbo.products-filter.com/)
* [Demo with Divi theme](https://demo-divi.products-filter.com/)
* [Demo with Avada theme](https://demo-avada.products-filter.com/)

= Where can I find video tutorials? =
[Video Tutorials](https://products-filter.com/video/)

= How do I create a custom taxonomy? =
Use [Custom Post Type UI](https://wordpress.org/plugins/custom-post-type-ui/).

= Where is the full FAQ? =
[FAQ](https://products-filter.com/faq/)

= Where is the documentation? =
[Codex](https://products-filter.com/codex/)

= Does HUSKY support AJAX filtering? =
Yes. AJAX filtering is supported out of the box. Enable it in the plugin settings under the Advanced tab.

= Is HUSKY compatible with WPML? =
Yes. HUSKY is fully compatible with WPML and Polylang.

= Does it support custom product attributes? =
Yes. Any product attribute registered in WooCommerce is automatically available as a filter option.

= What about security fixes? =
We take security seriously and release fixes regularly. You can report security vulnerabilities through the Patchstack Vulnerability Disclosure Program: [Report a security vulnerability](https://patchstack.com/database/vdp/woocommerce-products-filter).


== Screenshots ==

1. Filter widget on the front end
2. Plugin settings panel - General tab
3. Smart Designer - custom filter element styles
4. Filter Form Front Builder - drag-and-drop layout
5. SEO Links extension settings
6. Statistics extension
7. Dynamic recount in action


== Changelog ==

= 3.4.0 =
* Fixed: shortcode [woof_slideout] attribute tax_exclude
* Tested with latest woocommerce version 10.9.1

= 3.3.9 =
* Improved: sanitization of request data to comply with WordPress coding standards
* Fixed: settings not saving due to incorrect JSON decoding
* Fixed: infinite recursion in stat extension caused by woof_get_request_data filter
* Fixed: textarea fields showing wrong quotes and extra whitespace in admin
* Fixed: radio reset button not working with iCheck
* Fixed: PHP warning from file_put_contents in smart_designer extension

= 3.3.8.2 - May 19, 2026 =
* Fixed: prevent conflicts when used alongside HUSKY free version

= 3.3.8.1 - February 12, 2026 =
* Fixed issue with front_comprssd.js file

= 3.3.8 - February 6, 2026 =
* Heap of small fixes

= 3.3.7.4 - December 15, 2025 =
* Some small fixes
* Security fix - thanks to Athiwat Tiprasaharn (Jitlada) and wordfence.com

= 3.3.7.3 - November 25, 2025 =
* Some small fixes
* Security fix - thanks to Athiwat Tiprasaharn (Jitlada) and wordfence.com

= 3.3.7.2 - October 20, 2025 =
* Some small fixes
* Security fix - thanks to LionTree and wordfence.com

= 3.3.7.1 - June 13, 2025 =
* Set of small fixes
* Security fix - thanks to LVT-tholv2k from Patchstack.com

= 3.3.7 - May 23, 2025 =
* Bunch of small fixes
* New option in tab Design: "Preserve the state of unchecked checkbox/radio hierarchy"

= 3.3.6.6 - March 10, 2025 =
* Small fixes
* Security fix - thanks to Hiroho from wordfence.com

= 3.3.6.5 - February 21, 2025 =
* Security fix - thanks to Dimas Maulana from Patchstack.com

= 3.3.6.4 - November 12, 2024 =
* Security fix - thanks to Daniel Scheidt from vorwerk.de

= 3.3.6.3 - September 23, 2024 =
* Security fix - thanks to shaman0x01 from wordfence.com

= 3.3.6.2 - August 1, 2024 =
* Security fix - thanks to Rafie Muhammad from Patchstack.com
* Fix for WPML

= 3.3.6.1 - July 10, 2024 =
* Security fix - thanks to Arkadiusz Hydzik from wordfence.com
* Small fixes

= 3.3.6 - May 27, 2024 =
* Security fix - thanks to Richard Telleng (stueotue) from wordfence.com
* Bunch of small fixes
* New features

= 3.3.5.3 - March 14, 2024 =
* 2 security fixes - thanks to Wordfence.com
* 1 security fix - thanks to Patchstack.com

= 3.3.5.2 - March 5, 2024 =
* Security fix - thanks to Dhabaleshwar Das from Patchstack.com
* Security fix - thanks to Krzysztof Zajac from Wordfence.com

= 3.3.5.1 - January 16, 2024 =
* Security fix - thanks to Yudistira Arya from patchstack.com

= 3.3.5 - January 11, 2024 =
* Bunch of small fixes
* New features

= 3.3.4.4 - September 22, 2023 =
* Security fix - thanks to Rafie M from patchstack.com

= 3.3.4.3 - August 21, 2023 =
* 2 security fixes - thanks to Darius Sveikauskas from patchstack.com

= 3.3.4.2 - July 26, 2023 =
* Fix for ACF extension
* Added advanced option: Forcing disabling of functionality

= 3.3.4.1 - July 18, 2023 =
* Fix for Smart Designer installation

= 3.3.4 - July 17, 2023 =
* Heap of small fixes
* Filter Form Front Builder added

= 3.3.3 - May 18, 2023 =
* Heap of small fixes
* New features

= 3.3.2 - January 10, 2023 =
* Rebranding: WOOF to HUSKY
* Security fix - thanks to Animesh from WPScan

= 3.3.1 - December 17, 2022 =
* New extension: Smart Designer
* Security fix - thanks to Animesh from WPScan

= 3.3.0 - September 14, 2022 =
* Code sanitizing
* Code refactoring


== Upgrade Notice ==

= 3.3.8.1 =
Recommended update. Contains a fix for the front_comprssd.js file.


== License ==

This plugin is copyright pluginus.net 2012-2026 with GNU General Public License by realmag777.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
