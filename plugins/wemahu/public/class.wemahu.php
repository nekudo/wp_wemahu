<?php
/**
 * @package	wp_wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @copyright nekudo.com
 * @author Simon Samtleben <support@nekudo.com>
 */

class Wemahu
{
	const VERSION = '1.0.2';
	protected $plugin_slug = 'wemahu';

	protected static $instance = null;

	private function __construct()
	{	// enable on new wpmu blog
		//add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );
	}

	public function get_plugin_slug()
	{
		return $this->plugin_slug;
	}

	public static function get_instance()
	{
		if(null == self::$instance )
		{
			self::$instance = new self;
		}
		self::updateDb();
		return self::$instance;
	}

	public function updateDb()
	{
		$installed_ver = get_option('wemahu_db_version');
		if( $installed_ver != self::VERSION )
		{
			self::createTables(true);
		}
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate($network_wide)
	{

		if(function_exists('is_multisite') && is_multisite())
		{
			if($network_wide)
			{
				// Get all blog ids
				$blog_ids = self::get_blog_ids();
				foreach($blog_ids as $blog_id)
				{
					switch_to_blog($blog_id);
					self::single_activate();
				}
				restore_current_blog();
			}
			else
			{
				self::single_activate();
			}
		}
		else
		{
			self::single_activate();
		}
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate($network_wide)
	{
		if(function_exists('is_multisite') && is_multisite())
		{
			if($network_wide)
			{
				// Get all blog ids
				$blog_ids = self::get_blog_ids();
				foreach($blog_ids as $blog_id)
				{
					switch_to_blog( $blog_id );
					self::single_deactivate();
				}
				restore_current_blog();
			}
			else
			{
				self::single_deactivate();
			}
		}
		else
		{
			self::single_deactivate();
		}
	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if (1 !== did_action( 'wpmu_new_blog'))
		{
			return;
		}
		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();
	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids()
	{
		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";
		return $wpdb->get_col($sql);
	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate()
	{
		self::createTables();
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate()
	{
		global $wpdb;

		$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "wm_dirstack");
		$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "wm_filehashes");
		$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "wm_filestack");
		$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "wm_kvs");
		$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "wm_reportitems");
		$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . "wm_rulesets");

		delete_option('wemahu_db_version');
	}

	private static function createTables($update = false)
	{
		global $wpdb;

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$wmDirstack = $wpdb->prefix . 'wm_dirstack';
		$query = "CREATE TABLE ". $wmDirstack . " (
		  path varchar(300) NOT NULL,
		  mode varchar(1) NOT NULL DEFAULT 'w',
		  KEY mode (mode)
		) ENGINE=MEMORY DEFAULT CHARSET=utf8;";
		dbDelta($query);

		$wmFilehashes = $wpdb->prefix . 'wm_filehashes';
		$query = "CREATE TABLE " . $wmFilehashes . " (
		  pathhash varchar(40) NOT NULL,
		  filehash varchar(40) NOT NULL,
		  mode varchar(1) NOT NULL DEFAULT 'w',
		  UNIQUE KEY pathhash (pathhash,mode)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		dbDelta($query);

		$wmFilestack = $wpdb->prefix . 'wm_filestack';
		$query = "CREATE TABLE " . $wmFilestack . " (
		  path varchar(300) NOT NULL,
		  mode varchar(1) NOT NULL DEFAULT 'w',
		  KEY mode (mode)
		) ENGINE=MEMORY DEFAULT CHARSET=utf8;";
		dbDelta($query);

		$wmKvs = $wpdb->prefix . 'wm_kvs';
		$query = "CREATE TABLE " . $wmKvs . " (
		  id_kv int(10) unsigned NOT NULL AUTO_INCREMENT,
		  wm_key varchar(100) NOT NULL,
		  wm_value text NOT NULL,
		  mode varchar(1) NOT NULL DEFAULT 'w',
		  PRIMARY KEY (id_kv),
		  UNIQUE KEY wm_key (wm_key,mode)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
		dbDelta($query);

		$wmReportitems = $wpdb->prefix . 'wm_reportitems';
		$query = "CREATE TABLE " . $wmReportitems . " (
		  id_reportitem int(10) unsigned NOT NULL AUTO_INCREMENT,
		  reportitem text NOT NULL,
		  audit_name varchar(100) NOT NULL,
		  mode varchar(1) NOT NULL DEFAULT 'w',
		  PRIMARY KEY (id_reportitem),
		  KEY mode (mode)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
		dbDelta($query);

		$wmRulesets = $wpdb->prefix . 'wm_rulesets';
		$query = "CREATE TABLE " . $wmRulesets . " (
		  id int(10) unsigned NOT NULL AUTO_INCREMENT,
		  name varchar(100) NOT NULL,
		  filecheck tinyint(1) unsigned NOT NULL DEFAULT '1',
		  scandir varchar(250) NOT NULL,
		  regex_check tinyint(1) unsigned NOT NULL DEFAULT '1',
		  regex_db varchar(50) NOT NULL,
		  hash_check tinyint(1) unsigned NOT NULL DEFAULT '1',
		  hash_check_blacklist text NOT NULL,
		  filetypes varchar(250) NOT NULL,
		  filesize_max bigint(20) unsigned NOT NULL,
		  max_results_file int(10) unsigned NOT NULL DEFAULT '5',
		  max_results_total int(10) unsigned NOT NULL DEFAULT '100',
		  created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  PRIMARY KEY (id)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8;";
		dbDelta($query);

		if($update === false)
		{
			$wpdb->insert($wmRulesets, array(
				'name' => 'default',
				'scandir' => '',
				'regex_check' => 1,
				'regex_db' => 'regex_complete',
				'hash_check' => 1,
				'hash_check_blacklist' => '',
				'filetypes' => 'php,php4,php5,jpg,png,gif,js,html,htm,xml,htaccess',
				'filesize_max' => '500000',
				'max_results_file' => 5,
				'max_results_total' => 100,
			));
		}
		update_option('wemahu_db_version', '1.0.2');
	}
}
