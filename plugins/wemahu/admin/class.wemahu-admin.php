<?php
/**
 * @package	wp_wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @copyright nekudo.com
 * @author Simon Samtleben <support@nekudo.com>
 */

require_once 'libs/class.wemahu_forms.php';
require_once 'libs/class.wemahu_ajax.php';
require_once 'libs/class.wemahu_template.php';
require_once 'libs/class.json_response.php';
require_once 'models/ruleset.php';

class Wemahu_Admin
{
	protected static $instance = null;
	protected $plugin_screen_hook_suffix = null;
	protected $options;

	private function __construct()
	{
		$plugin = Wemahu::get_instance();
		$this->plugin_slug = $plugin->get_plugin_slug();

		// add adminpanel actions/filters:
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
		add_action('admin_menu', array($this, 'add_plugin_admin_menu'));
		$plugin_basename = plugin_basename(plugin_dir_path( __DIR__) . $this->plugin_slug . '.php');
		add_filter('plugin_action_links_' . $plugin_basename, array($this, 'add_action_links'));
		add_action('admin_init', array($this, 'handleOptions'));
		add_action('wp_ajax_handle_ajax', array($this, 'handleAjaxRequest'));

		// register tables:
		global $wpdb;
		$wpdb->rulesets = $wpdb->prefix . 'wm_rulesets';
	}

	public static function get_instance()
	{
		if(null == self::$instance )
		{
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Add stylesheets.
	 */
	public function enqueue_admin_styles()
	{
		if(!isset($this->plugin_screen_hook_suffix))
		{
			return;
		}
		$screen = get_current_screen();
		if(strpos($screen->id, 'wemahu') !== false)
		{
			wp_enqueue_style($this->plugin_slug .'-wpbootstrap', plugins_url('assets/css/bootstrap-wpadmin.min.css', __FILE__), array(), Wemahu::VERSION);
			wp_enqueue_style($this->plugin_slug .'-admin-styles', plugins_url('assets/css/wemahu.css', __FILE__), array(), Wemahu::VERSION);
		}
	}

	/**
	 * Add javascripts.
	 */
	public function enqueue_admin_scripts()
	{
		if(!isset($this->plugin_screen_hook_suffix))
		{
			return;
		}
		$screen = get_current_screen();
		if(strpos($screen->id, 'wemahu') !== false)
		{
			wp_enqueue_script('bootstrap', plugins_url('assets/js/bootstrap.min.js', __FILE__ ), array('jquery'), Wemahu::VERSION);
			wp_enqueue_script('jquery-moment', plugins_url('assets/js/moment.min.js', __FILE__ ), array('jquery'), Wemahu::VERSION);
			wp_enqueue_script('jquery-nanoscroller', plugins_url('assets/js/jquery.nanoscroller.min.js', __FILE__ ), array('jquery'), Wemahu::VERSION);
			wp_enqueue_script('wemahu-main-js', plugins_url('assets/js/wemahu.js', __FILE__ ), array('jquery'), Wemahu::VERSION);
		}
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 */
	public function add_plugin_admin_menu()
	{
		$this->plugin_screen_hook_suffix = add_menu_page(
			'Wemahu Dashboard',
			'Wemahu',
			'manage_options',
			$this->plugin_slug,
			array($this, 'display_wemahu_dashboard')
		);
		$this->plugin_screen_hook_suffix = add_submenu_page(
			$this->plugin_slug,
			'Wemahu Rulesets',
			'Rulesets',
			'manage_options',
			'wemahu_rulesets',
			array($this, 'display_wemahu_rulesets')
		);
		$this->plugin_screen_hook_suffix = add_submenu_page(
			$this->plugin_slug,
			'Wemahu Settings',
			'Settings',
			'manage_options',
			'wemahu_settings',
			array($this, 'display_wemahu_settings')
		);
		$this->plugin_screen_hook_suffix = add_submenu_page(
			$this->plugin_slug,
			'Wemahu Help',
			'Help/About',
			'manage_options',
			'wemahu_help',
			array($this, 'display_wemahu_help')
		);
	}

	/**
	 * Render the dashboard page for this plugin.
	 */
	public function display_wemahu_dashboard()
	{
		global $wpdb;

		if(isset($_POST['task']) && isset($_POST['rv']))
		{
			$this->handleAjaxRequest();
			return true;
		}

		$ModelRuleset  = new ModelRuleset($wpdb);
		$rulesetValues = array();
		$rulesetData = $ModelRuleset->getRulesets();
		foreach($rulesetData as $ruleset)
		{
			$rulesetValues[$ruleset['id']] = $ruleset['name'];
		}

		include_once 'views/dashboard.php';
	}

	/**
	 * Displays the ruleset(s) pages.
	 */
	public function display_wemahu_rulesets()
	{
		global $wpdb;

		$action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : 'list';
		switch($action)
		{
			case 'list':
				include_once 'views/rulesets.php';
			break;

			case 'edit':
				$rulsetId = (int)$_REQUEST['id'];
				$ModelRuleset  = new ModelRuleset($wpdb);
				$rulesetData = $ModelRuleset->getRulesetData($rulsetId);
				include_once 'views/ruleset.php';
			break;

			case 'save':
				$rulsetId = (int)$_POST['id'];
				check_admin_referer('save_ruleset_'.$rulsetId);
				$ModelRuleset  = new ModelRuleset($wpdb);
				$saveResult = $ModelRuleset->saveRuleset($_POST['ruleset'], $rulsetId);
				if($saveResult === false)
				{
					$message = array(
						'type' => 'error',
						'text' => 'Rule could not be saved to database.',
					);
				}
				else
				{
					$message = array(
						'type' => 'updated',
						'text' => 'Rule successfully saved.',
					);
					$rulsetId = $saveResult;
				}
				$rulesetData = $ModelRuleset->getRulesetData($rulsetId);
				include_once 'views/ruleset.php';
			break;

			case 'add':
				$rulesetData = array(
					'id' => 0,
					'name' => '',
					'filecheck' => 1,
					'scandir' => '',
					'regex_check' => 1,
					'hash_check' => 1,
					'filetypes' => 'php,jpg,png,gif,js,html,htm,xml,htaccess',
					'filesize_max' => '500000',
				);
				include_once 'views/ruleset.php';
			break;

			case 'delete':
				$deleteResult = false;
				if(!empty($_GET['id']))
				{
					$rulsetId = (int)$_GET['id'];
					check_admin_referer('delete_'.$rulsetId);
					$ModelRuleset  = new ModelRuleset($wpdb);
					$deleteResult = $ModelRuleset->deleteRuleset($rulsetId);
				}
				if(!empty($_GET['ruleset']))
				{
					check_admin_referer('bulk-rulesets');
					$rulesetIds = $_GET['ruleset'];
					$ModelRuleset  = new ModelRuleset($wpdb);
					foreach($rulesetIds as $rulsetId)
					{
						$deleteResult = $ModelRuleset->deleteRuleset($rulsetId);
						if($deleteResult === false)
						{
							break;
						}
					}
					$deleteResult = true;
				}
				if($deleteResult === false)
				{
					$message = array(
						'type' => 'error',
						'text' => 'Rule(s) could not be deleted database.',
					);
				}
				else
				{
					$message = array(
						'type' => 'updated',
						'text' => 'Rule(s) successfully deleted.',
					);
				}
				include_once 'views/rulesets.php';
			break;
		}
	}

	/**
	 * Displays wemahu settings page.
	 */
	public function display_wemahu_settings()
	{
		include_once 'views/settings.php';
	}

	public function display_wemahu_help()
	{
		include_once 'views/help.php';
	}

	/**
	 * Handles ajax requests. (Calls methods in wemahu-ajax class.
	 *
	 * @return bool
	 */
	public function handleAjaxRequest()
	{
		if(empty($_POST['task']) || empty($_POST['rv']))
		{
			return false;
		}
		global $wpdb;

		$task = $_POST['task'];
		$WemahuAjax = new WemahuAjax($wpdb, $this->options);
		if(method_exists($WemahuAjax, $task))
		{
			call_user_func(array($WemahuAjax, $task));
		}
		return true;
	}

	/**
	 * Handles the plugin options.
	 */
	public function handleOptions()
	{
		global $wpdb;

		$this->options = get_option('wemahu');
		$WemahuForms = new Wemahu_Forms($this->options);

		register_setting(
			'wemahu', // Option group
			'wemahu', // Option name
			array($WemahuForms, 'sanitizeInput') // Sanitize
		);

		// General options

		add_settings_section(
			'wemahu_general_settings', // ID
			'Genreal Settings', // Title
			array($WemahuForms, 'displaySectionInfoGeneral'), // Callback
			'wemahu_settings_page' // Page
		);
		add_settings_field(
			'use_api', // ID
			'Allow API usage', // Title
			array( $WemahuForms, 'displayCheckboxInput' ), // Callback
			'wemahu_settings_page', // Page
			'wemahu_general_settings', // Section
			array(
				//'label_for' => 'use_api',
				'name' => 'use_api',
				'id' => 'use_api',
				'group' => 'wemahu',
				'desc' => 'Allows the plugin to contact nekudo.com to send and receive data. This includes signature/whitelist updates e.g.',
			)
		);

		// Cronjob options
		$ModelRuleset  = new ModelRuleset($wpdb);
		$rulesetValues = array();
		$rulesetData = $ModelRuleset->getRulesets();
		foreach($rulesetData as $ruleset)
		{
			$rulesetValues[$ruleset['id']] = $ruleset['name'];
		}

		add_settings_section(
			'wemahu_cronjob_settings', // ID
			'Cronjob Settings', // Title
			array($WemahuForms, 'displaySectionInfoCronjob'), // Callback
			'wemahu_settings_page' // Page
		);
		add_settings_field(
			'cron_ruleset', // ID
			'Cronjob ruleset', // Title
			array( $WemahuForms, 'displayDropdownInput' ), // Callback
			'wemahu_settings_page', // Page
			'wemahu_cronjob_settings', // Section
			array(
				'label_for' => 'cron_ruleset',
				'name' => 'cron_ruleset',
				'values' => $rulesetValues,
				'id' => 'cron_ruleset',
				'group' => 'wemahu',
				'desc' => 'Here you can select the ruleset that will be used by wemahu cronjobs.',
			)
		);
		add_settings_field(
			'cron_sendmail', // ID
			'Send report email', // Title
			array( $WemahuForms, 'displayCheckboxInput' ), // Callback
			'wemahu_settings_page', // Page
			'wemahu_cronjob_settings', // Section
			array(
				//'label_for' => 'use_api',
				'name' => 'cron_sendmail',
				'id' => 'cron_sendmail',
				'group' => 'wemahu',
				'desc' => 'Set if the Wemahu cron script should send a report by email.',
			)
		);
		add_settings_field(
			'cron_emptyreport', // ID
			'Send empty reports', // Title
			array( $WemahuForms, 'displayCheckboxInput' ), // Callback
			'wemahu_settings_page', // Page
			'wemahu_cronjob_settings', // Section
			array(
				//'label_for' => 'use_api',
				'name' => 'cron_emptyreport',
				'id' => 'cron_emptyreport',
				'group' => 'wemahu',
				'desc' => 'Set if you want to receive and email even if the report is empty.',
			)
		);
		add_settings_field(
			'cron_email', // ID
			'Report email address', // Title
			array( $WemahuForms, 'displayTextInput' ), // Callback
			'wemahu_settings_page', // Page
			'wemahu_cronjob_settings', // Section
			array(
				'label_for' => 'cron_email',
				'name' => 'cron_email',
				'id' => 'cron_email',
				'group' => 'wemahu',
				'desc' => 'This is the email-address cronjob reports will be send to. Leave empty to use wordpress system email-address.',
				'classes' => 'regular-text ltr',
			)
		);
	}
}