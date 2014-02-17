<?php
/*
 * Plugin Name: OPcache Dashboard
 * Plugin URI: http://wordpress.org/plugins/opcache/
 * Description: OPcache dashboard designed for WordPress
 * Version: 0.1.0
 * Author: Daisuke Takahashi(Extend Wings)
 * Author URI: http://www.extendwings.com
 * License: AGPLv3 or later
 * Text Domain: opcache-dashboard
 * Domain Path: /languages/
*/

if(!function_exists('add_action')) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

add_action('init', array('opcache_dashboard', 'init'));
class opcache_dashboard {
	static $instance;

	static function init() {
		if(!self::$instance) {
		/*
			if(did_action('plugins_loaded'))
				self::plugin_textdomain();
			else
				add_action('plugins_loaded', array(__CLASS__, 'plugin_textdomain'));
		*/

			self::$instance = new opcache_dashboard;
		}
		return self::$instance;
	}

	private function opcache_dashboard() {
		add_action('admin_menu', array($this, 'add_admin_menu'));
		if(is_multisite() && is_network_admin())
			add_action('network_admin_menu', array($this, 'add_admin_menu'));
		add_action('wp_loaded', array($this, 'register_assets'));
	}

	function register_assets() {
		if(!wp_script_is('d3js', 'registered'))
			wp_register_script('d3js', '//cdnjs.cloudflare.com/ajax/libs/d3/3.4.1/d3.min.js', false, '3.4.1');
		if(!wp_script_is('opcache', 'registered'))
			wp_register_script('opcache', plugin_dir_url(__FILE__).'chart.js', array('jquery', 'd3js'), '0.1.0', true);
		if(!wp_style_is('opcache', 'registered'))
			wp_register_style('opcache', plugin_dir_url(__FILE__).'style.css', false, '0.1.0');
	}

	function add_admin_menu() {
		add_menu_page(
			__('OPcache Dashboard', 'opcache'),	//page_title
			__('OPcache', 'opcache'),		//menu_title
			'manage_options',			//capability
			'opcache',				//menu_slug
			array($this, 'admin_page'),		//function
			'dashicons-backup',			//icon_url
			'3.14159265359'				//position
		);

		add_action('admin_enqueue_scripts', array($this, 'admin_menu_assets'));
	}

	function admin_menu_assets($hook) {
		if('toplevel_page_opcache' != $hook)
			return;
		wp_enqueue_script('opcache');
		wp_enqueue_style('opcache');
	}

	function admin_page() {
		$config = opcache_get_configuration();
		$status = opcache_get_status();
		$stats = $status['opcache_statistics'];
		$mem_stats = $status['memory_usage'];
		$stats['num_free_keys'] = $stats['max_cached_keys'] - $stats['num_cached_keys'];
		?>
		<div class="wrap"><h2><?php _e('OPcache Dashboard', 'opcache'); ?></h2>
			<div id="widgets-wrap">
				<div id="widgets" class="metabox-holder">
					<div id="postbox-container-1" class="postbox-container">
						<div class="meta-box-sortables ui-sortable">
							<div class="postbox">
								<h3 class="hndle">
									<span>PHP: <?php echo phpversion(); ?> and OPcache: <?php echo $config['version']['version']; ?></span>
								</h3>
								<div class="inside">
									<p id="hits">Hits: <?php echo $this->number_format($stats['opcache_hit_rate'], 2); ?>%</p>
									<p id="memory">
										Memory: <?php echo $this->size($mem_stats['used_memory'] + $mem_stats['wasted_memory']); ?>
											of <?php echo $this->size($config['directives']['opcache.memory_consumption']); ?>
									</p>
									<p id="keys">Keys: <?php echo $stats['num_cached_keys']; ?> of <?php echo $stats['max_cached_keys']; ?></p>
								</div>
							</div>
						</div>
					</div>
					<div id="postbox-container-2" class="postbox-container">
						<div class="meta-box-sortables ui-sortable">
							<div class="postbox">
								<div class="inside">
									<div id="graph">
										<form>
											<label><input type="radio" name="dataset" value="memory" checked>Memory</label>
											<label><input type="radio" name="dataset" value="keys">Keys</label>
											<label><input type="radio" name="dataset" value="hits">Hits</label>
										</form>
										<div id="stats"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="clear"></div>
			</div>
			
		</div><!-- wrap -->
		<script>
			var dataset={
				memory:[<?php echo $mem_stats['used_memory']; ?>,<?php echo $mem_stats['free_memory'];?>,<?php echo $mem_stats['wasted_memory']; ?>],
				keys:[<?php echo $stats['num_cached_keys']; ?>,<?php echo $stats['num_free_keys']; ?>,0],
				hits:[<?php echo $stats['misses']; ?>,<?php echo $stats['hits']; ?>,0]
			};
			var mem_stats=[
				'<?php echo $this->size($mem_stats['used_memory']); ?>',
				'<?php echo $this->size($mem_stats['free_memory']); ?>',
				'<?php echo $this->size($mem_stats['wasted_memory']); ?>',
				'<?php echo $this->number_format($mem_stats['current_wasted_percentage'],2); ?>'
			];
		</script>
		<?php
	}

	function size($size) {
		$si_units = array("", "k", "M", "G", "T", "P", "E", "Z", "Y");
		$i = 0;
		while($size >= 1024 && $i < count($si_units)) {
			$size = round($size / 1024, 2);
			$i++;
		}

		return $this->number_format($size) . $si_units[$i] . "B";
	}

	function number_format($number, $decimals = 2) {
		return number_format($number, $decimals, '.', ',');
	}
}

?>

