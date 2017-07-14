=== Stock Exporter for WooCommerce ===
Contributors: webdados, wonderm00n
Tags: woocommerce, ecommerce, e-commerce, stock
Author URI: http://www.webdados.pt
Plugin URI: http://www.webdados.pt/produtos-e-servicos/internet/desenvolvimento-wordpress/exportacao-stock-woocommerce-wordpress/
Requires at least: 4.4
Tested up to: 4.7.3
Stable tag: 0.4.1

Export a simple CSV file report with the current WooCommerce products stock.

== Description ==

This plugin allows you to export a simple report, in a CSV file, with all the products, or only the ones where stock is managed, and its current stock on WooCommerce.
The file is UTF-16, comma-separated and the values are enclosed in double quotes.

= Features: =

* Generates a simple CSV file report with the current WooCommerce products stock;
* It's also possible to see the report as a HTML table directly on the plugin's admin page;
* WPML compatible;

== Installation ==

* Use the included automatic install feature on your WordPress admin panel and search for “WooCommerce Stock Exporter”.
* Go to WooCoomerce > Stock Exporter and click on “Export WooCommerce Stock” to generate the report.

== Frequently Asked Questions ==

= Can this plugin also...? =

Nop! WooCoomerce Stock Report on a CSV file or HTML table. That's it.

== Changelog ==

= 0.4.1 =
* Fixes a bug introduced on 0.4 that wouldn't allow to export on WooCommerce older than 3.0

= 0.4 =
* Tested and adapted to work with WooCommerce 3.0.0-rc.2
* New WC_Product_Stock_Exporter and WC_Product_Variation_Stock_Exporter classes (extends WC_Product and WC_Product_Variation) to be used by the plugin to get and product details
* Added ID to the list of fixed fields to export
* Changed all product/variation and categories separator to `|`
* Bumped `Tested up to` tag

= 0.3.1 =
* Bumped `Tested up to` and `Requires at least` tags

= 0.3 =
* Release date: 2016-05-11
* You can now choose aditional fields to include on the report: Categories, Regular Price and any custom field from any plugin
* The options are saved to be used as default next time
* Update sponsored by ideiahomedesign.pt


= 0.2 =
* Release date: 2016-04-05
* Added the product price to the report
* readme.txt fixes

= 0.1.1 =
* Release date: 2015-11-19
* Fix: Translations were not loaded correctly
* Plugin URI added to readme.txt

= 0.1 =
* Release date: 2015-11-19
* Initial release, sponsored by ideiahomedesign.pt