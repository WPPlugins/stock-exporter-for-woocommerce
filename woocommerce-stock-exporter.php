<?php
/**
 * Plugin Name: Stock Exporter for WooCommerce
 * Description: Simple stock report CSV exporter for WooCommerce
 * Version: 0.4.1
 * Author: Webdados
 * Author URI: http://www.webdados.pt
 * Text Domain: woocommerce-stock-exporter
 * Domain Path: /lang
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Check if WooCommerce is active
 **/
// Get active network plugins - "Stolen" from Novalnet Payment Gateway
function wse_active_nw_plugins() {
	if (!is_multisite())
		return false;
	$wse_activePlugins = (get_site_option('active_sitewide_plugins')) ? array_keys(get_site_option('active_sitewide_plugins')) : array();
	return $wse_activePlugins;
}
if (in_array('woocommerce/woocommerce.php', (array) get_option('active_plugins')) || in_array('woocommerce/woocommerce.php', (array) wse_active_nw_plugins())) {

	class WC_Stock_Reporter {

		public $version = '0.4.1';

		//Init the class
		public function __construct() {
			// Load our Products Class
			add_action('plugins_loaded', array($this, 'load_products_class'));
			// Load translation files
			add_action('plugins_loaded', array($this, 'load_textdomain'));
			//Add admin menu item
			add_action('admin_menu', array($this, 'add_admin_menu_item'));
			//Process
			add_action('admin_init', array($this, 'woocommerce_stock_exporter_page_process'));
			//Some settings
			$this->sep = '|';
			$this->sep_replace = '-';
			//Defaults - Options saved by the user
			$this->defaults=get_option('woocoomerce_stock_export');
			if (!$this->defaults) {
				$this->defaults=array();
				$this->defaults['woocoomerce_stock_export_products']='all';
				$this->defaults['woocoomerce_stock_export_fields']=array();
				$this->defaults['woocoomerce_stock_export_output']='csv';
				$this->defaults['woocoomerce_stock_export_fields_custom']='';
			}
			$this->defaults['woocoomerce_stock_export_fields_custom']=$this->explode_custom_fields($this->defaults['woocoomerce_stock_export_fields_custom']);
			//Fields
			$this->export_fields_options = array(
				array(
					'value'	=>	'id',
					'label'	=>	__('ID', 'woocommerce-stock-exporter'),
					'type'	=>	'fixed',
				),
				array(
					'value'	=>	'sku',
					'label'	=>	__('SKU', 'woocommerce-stock-exporter'),
					'type'	=>	'fixed',
				),
				array(
					'value'	=>	'type',
					'label'	=>	__('Product type', 'woocommerce-stock-exporter'),
					'type'	=>	'fixed',
				),
				array(
					'value'	=>	'product',
					'label'	=>	__('Product', 'woocommerce-stock-exporter'),
					'type'	=>	'fixed',
				),
				array(
					'value'	=>	'product_cat',
					'label'	=>	__('Categories', 'woocommerce-stock-exporter'),
					'type'	=>	'optional',
				),
				array(
					'value'	=>	'regular_price',
					'label'	=>	__('Regular price', 'woocommerce-stock-exporter'),
					'type'	=>	'optional',
				),
				array(
					'value'	=>	'price',
					'label'	=>	__('Price', 'woocommerce-stock-exporter'),
					'type'	=>	'optional',
				),
				array(
					'value'	=>	'custom_fields',
					'label'	=>	__('Custom fields (comma separated)', 'woocommerce-stock-exporter'),
					'type'	=>	'custom_fields',
				),
				array(
					'value'	=>	'stock',
					'label'	=>	__('Stock', 'woocommerce-stock-exporter'),
					'type'	=>	'fixed',
				),
			);
		}

		//Load our Products Class
		public function load_products_class() {
			require_once( dirname(__FILE__).'/class-wc-product-stock-exporter.php' );
		}

		//Load translation files
		public function load_textdomain() {
			$domain='woocommerce-stock-exporter';
			if ($loaded = load_plugin_textdomain($domain, false, trailingslashit(WP_LANG_DIR))) {
				return $loaded;
			} else {
				return load_plugin_textdomain($domain, false, dirname(plugin_basename( __FILE__ )).'/lang/');
			}
		}

		//Check capabilities
		public function check_capabilities() {
			//Maybe a bit redundant
			return (current_user_can('manage_options') || current_user_can('manage_woocommerce') || current_user_can('view_woocommerce_reports'));
		}

		//Add admin menu item
		public function add_admin_menu_item() {
			 if ($this->check_capabilities()) add_submenu_page('woocommerce', _x('Stock Exporter for WooCommerce', 'admin page title', 'woocommerce-stock-exporter'), _x('Stock Exporter', 'admin menu item', 'woocommerce-stock-exporter'), 'view_woocommerce_reports', 'woocommerce_stock_exporter', array($this, 'woocommerce_stock_exporter_page'));
		}

		//Admin screen
		public function woocommerce_stock_exporter_page() {
			$show_products_options = array(
				array(
					'value'	=>	'all',
					'label'	=>	__('All products', 'woocommerce-stock-exporter'),
				),
				array(
					'value'	=>	'managed',
					'label'	=>	__('Products with managed stock', 'woocommerce-stock-exporter'),
				),
			);
			$output_options = array(
				array(
					'value'	=>	'csv',
					'label'	=>	__('CSV file', 'woocommerce-stock-exporter'),
				),
				array(
					'value'	=>	'screen',
					'label'	=>	__('HTML table on screen', 'woocommerce-stock-exporter'),
				),
			);
			?>
			<div class="wrap">
				<h2><?php _ex('Stock Exporter for WooCommerce', 'admin page title', 'woocommerce-stock-exporter'); ?></h2>
				<p><?php _e('Click the button below o generate a WooCommerce stock report, in CSV, of all the products on this website where stock is managed.', 'woocommerce-stock-exporter'); ?></p>
				<?php
				//WPML
				if (function_exists('icl_object_id')) {
					?>
					<p><?php _e('WPML users: You can export the report on a different language by changing it on this page top bar.', 'woocommerce-stock-exporter'); ?></p>
					<?php
				}
				?>
				<form method="post" id="woocoomerce-stock-export-form" action="">
					<table class="form-table">
						<tbody>
							<tr>
								<th scope="row" class="titledesc"><?php _e('Products to return', 'woocommerce-stock-exporter'); ?></th>
								<td>
									<select name="woocoomerce_stock_export_products">
										<?php
										foreach($show_products_options as $option) {
											?>
											<option value="<?php echo $option['value']; ?>"<?php if ($this->show_products==$option['value']) echo ' selected="selected"'; ?>><?php echo $option['label']; ?></option>
											<?php
										}
										?>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row" class="titledesc"><?php _e('Fields', 'woocommerce-stock-exporter'); ?></th>
								<td>
									<?php
									foreach($this->export_fields_options as $option) {
										?>
										<div>
											<?php
											switch($option['type']) {
												case 'fixed':
													?>
													<input type="hidden" name="woocoomerce_stock_export_fields[]" id="export_fields_options_<?php echo $option['value']; ?>" value="<?php echo $option['value']; ?>"/>
													<span class="dashicons dashicons-yes"></span>
													<?php
													break;
												case 'optional':
												case 'custom_fields':
													?>
													<input type="checkbox" name="woocoomerce_stock_export_fields[]" id="export_fields_options_<?php echo $option['value']; ?>" value="<?php echo $option['value']; ?>"<?php if ( in_array($option['value'], $this->defaults['woocoomerce_stock_export_fields']) ) echo ' checked="checked"'; ?>/>
													<?php
													break;
											}
											?>											
											<lable for="export_fields_options_<?php echo $option['value']; ?>"><?php echo $option['label']; ?></label>
											<?php
											if ($option['type']=='custom_fields') {
												?>
												<input type="text" name="woocoomerce_stock_export_fields_custom" size="35" value="<?php echo esc_attr(implode(' , ', $this->defaults['woocoomerce_stock_export_fields_custom'])); ?>"/>
												<?php
											}
											?>
										</div>
										<?php
									}
									?>
								</td>
							</tr>
							<tr>
								<th scope="row" class="titledesc"><?php _e('Output', 'woocommerce-stock-exporter'); ?></th>
								<td>
									<select name="woocoomerce_stock_export_output">
										<?php
										foreach($output_options as $option) {
											?>
											<option value="<?php echo $option['value']; ?>"<?php if ($this->output_type==$option['value']) echo ' selected="selected"'; ?>><?php echo $option['label']; ?></option>
											<?php
										}
										?>
									</select>
								</td>
							</tr>
						</tbody>
					</table>
					<?php submit_button(__('Export WooCommerce Stock', 'woocommerce-stock-exporter'), 'primary', 'woocoomerce_stock_export_button'); ?>
				</form>
				<?php
				if ($this->output_type=='screen' && isset($this->screen_output)) {
					?>
					<hr/>
					<?php
					echo $this->screen_output;
				}
				?>
			</div>
			<?php
		}
		//Admin screen - export
		public function woocommerce_stock_exporter_page_process() {
			global $plugin_page;
			if ($plugin_page=='woocommerce_stock_exporter' && $this->check_capabilities()) {
				if (isset($_POST['woocoomerce_stock_export_button'])) {
					update_option( 'woocoomerce_stock_export', $_POST );
					$this->defaults=get_option('woocoomerce_stock_export');
					$this->defaults['woocoomerce_stock_export_fields_custom']=$this->explode_custom_fields($this->defaults['woocoomerce_stock_export_fields_custom']);
				}
				$this->show_products=(isset($_POST['woocoomerce_stock_export_products']) ? trim($_POST['woocoomerce_stock_export_products']) : $this->defaults['woocoomerce_stock_export_products']);
				$this->output_type=(isset($_POST['woocoomerce_stock_export_output']) ? trim($_POST['woocoomerce_stock_export_output']) : $this->defaults['woocoomerce_stock_export_output']);
				if (isset($_POST['woocoomerce_stock_export_button'])) {
					$this->make_csv();
				}
			}
		}

		//
		public function explode_custom_fields( $fields ) {
			$fields=trim($fields);
			$fields=explode(',',$fields);
			foreach($fields as $key => $field) {
				$fields[$key]=trim($field);
				if (trim($fields[$key])=='') unset($fields[$key]);
			}
			return $fields;
		}

		//Terms - This could be improved to use new 3.0 methods like get_category_ids or get_tag_ids
		public function get_terms( $product_id, $tax='product_cat' ) {
			$terms = get_the_terms( $product_id, $tax );
			$txt='';
			if ( $terms ) {
				foreach ( $terms as $term ) {
					$txt.=str_replace( $this->sep, $this->sep_replace, trim($term->name) ).' '.$this->sep.' ';
				}
			}
			return trim($txt, ' '.$this->sep);
		}

		//Each value
		public function get_value( $field, $product, $variation=null ) {
			$product_type = version_compare( WC_VERSION, '3.0', '>=' ) ? $product->get_type() : $product->product_type;
			$_product = new WC_Product_Stock_Exporter( version_compare( WC_VERSION, '3.0', '>=' ) ? $product->get_id() : $product->id );
			$id = $_product->se_get_id();
			switch ( $product_type ) {
				case 'variable':
					if ($variation) {
						$_variation = new WC_Product_Variation_Stock_Exporter( version_compare( WC_VERSION, '3.0', '>=' ) ? $variation->get_id() : $variation->id );
						$id = $_variation->se_get_id();
					}
					break;
			}
			switch($field) {
				case 'id':
					return array( $id );
					break;
				case 'sku':
					return array( trim( str_replace( $this->sep, $this->sep_replace, $_product->get_sku() ).($product_type=='variable' ? ' '.$this->sep.' '.str_replace( $this->sep, $this->sep_replace, $_variation->get_sku() ) : ''), ' '.$this->sep ) );
					break;
				case 'type':
					return array( $product_type );
					break;

				case 'product':
					return array( trim( str_replace( $this->sep, $this->sep_replace, $_product->get_title() ).($product_type=='variable' ? ' '.$this->sep.' '.str_replace( $this->sep, $this->sep_replace, get_the_title($id) ) : ''), ' '.$this->sep ) );
					break;
				case 'product_cat':
					return array( $this->get_terms($_product->id) );
					break;
				case 'regular_price':
					return array( $product_type=='variable' ? $_variation->get_regular_price() : $product->get_regular_price() );
					break;
				case 'price':
					return array( $product_type=='variable' ? $_variation->get_price() : $product->get_price() );
					break;
				case 'custom_fields':
					$temp=array();
					foreach ($this->defaults['woocoomerce_stock_export_fields_custom'] as $key) {
						$temp[] = (
									$product_type=='variable'
									?
									$_variation->se_get_meta($key)
									:
									$_product->se_get_meta($key)
								);
					}
					return $temp;
					break;
				case 'stock':
					return array(
						$product_type=='variable'
						?
						( $_variation->managing_stock() ? $_variation->get_stock_quantity() : __('not managed', 'woocommerce-stock-exporter') )
						:
						( $product->managing_stock() ? $product->get_stock_quantity() : __('not managed', 'woocommerce-stock-exporter') )
					);
					break;
			}
		}

		//The CSV itself
		public function make_csv() {
			//Options
			
			switch($this->output_type) {
				case 'csv':
					//Correct headers
					header('Content-Type: text/csv; charset=utf-8');
					header('Content-Disposition: attachment; filename=woocommerce_stock_exporter_'.current_time('Y_m_d').'.csv');
					//Create a file pointer connected to the output stream
					$output = fopen('php://output', 'w');
					break;
				default:
					//Nothing really
					break;
			}
			//Init the output array and add column headers for the CSV
			$output_array[0] = array();
			foreach( $this->export_fields_options as $field ) {
				if ( in_array($field['value'], $_POST['woocoomerce_stock_export_fields']) ) {
					switch( $field['value'] ) {
						case 'custom_fields':
							foreach ( $this->defaults['woocoomerce_stock_export_fields_custom'] as $key)  {
								$output_array[0][] = $key;
							}
							break;
						default:
							$output_array[0][] = $field['label'];
							break;
					}
				}
			}
			//Get all products
			if ( version_compare( WC_VERSION, '3.0', '>=' ) ) {
				$products = wc_get_products( array(
					'status' 		=> 'publish',
					'limit'			=> -1, //This is not a very good idea
					'orderby'		=> 'title',
					'order'			=> 'ASC',
				) );
			} else {
				$args = array(
					'post_type'			=> 'product',
					'post_status' 		=> 'publish',
					'posts_per_page' 	=> -1, //This is not a very good idea
					'orderby'			=> 'title',
					'order'				=> 'ASC',
				);
				$loop = new WP_Query($args);
				$products = array();
				while ( $loop->have_posts() ) : $loop->the_post();
					global $product;
					$products[] = $product;
				endwhile;
			}
			foreach($products as $product) {
				//if ( !version_compare( WC_VERSION, '3.0', '>=' ) ) $product = new WC_Product($product->ID); //It's already a product, although we're using WP_Query to get them...
				$product_type = version_compare( WC_VERSION, '3.0', '>=' ) ? $product->get_type() : $product->product_type;
				if ( $product_type == 'variable' ) {
					$variations = $product->get_available_variations();
					foreach ( $variations as $temp ) {
						$variation = new WC_Product_Variation( $temp['variation_id'] );
						if ( $this->show_products=='all' || ($this->show_products=='managed' && $variation->managing_stock()) ) {
								$temp = array();
								foreach( $this->export_fields_options as $field ) {
									if ( in_array($field['value'], $_POST['woocoomerce_stock_export_fields']) ) {
										$temp = array_merge( $temp , $this->get_value( $field['value'], $product, $variation ) );
									}
								}
								$output_array[] = $temp;
						}
					}
				} else {
					if ( $this->show_products=='all' || ($this->show_products=='managed' && $product->managing_stock()) ) {
						$temp=array();
						foreach( $this->export_fields_options as $field ) {
							if ( in_array($field['value'], $_POST['woocoomerce_stock_export_fields']) ) {
								$temp = array_merge( $temp , $this->get_value( $field['value'], $product, null ) );
							}
						}
						$output_array[] = $temp;
					}
				}
			}
			//Output
			switch( $this->output_type ) {
				case 'csv':
					//CSV'it
					foreach( $output_array as $i=> $temp ) {
						$output_array[$i] = '"'.implode('","', $temp).'"';
					}
					fwrite( $output, chr(255).chr(254).iconv("UTF-8", "UTF-16LE//IGNORE", implode("\n", $output_array) ) );
					die();
					break;
				case 'screen':
					ob_start();
					?>
					<p><b><?php echo count($output_array)-1; ?> <?php _e('products', 'woocommerce-stock-exporter'); ?></b></p>
					<table class="widefat">
						<thead>
							<tr>
								<?php
								foreach($output_array[0] as $value) {
									?>
									<th scope="col"><?php echo $value; ?></th>
									<?php
								}
								?>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach($output_array as $key => $values) {
								if ($key>0) {
									?>
									<tr class="<?php echo ($key%2==0 ? '' : 'alternate'); ?>">
									<?php
									foreach($values as $value) {
										?>
										<td class="column-columnname"><?php echo $value; ?></td>
										<?php
									}
									?>
									</tr>
									<?php
								}
							}
							?>
						</tbody>
					</table>
					<?php
					$this->screen_output = ob_get_clean();
					break;
			}
		}

	}
	
	if (is_admin()) {
		$wse = new WC_Stock_Reporter();
	}


	/* If you're reading this you must know what you're doing ;-) Greetings from sunny Portugal! */


}