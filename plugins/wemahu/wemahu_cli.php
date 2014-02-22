<?php
/**
 * Wemahu CLI script. Can be used to run Wemahu periodically using cronjobs.
 * By default a report will be send by email.
 * Possible Parameters:
 * --force_output Force output to STDOUT instead of sending mail.
 * --ruleset id_ruleset Ruleset to use.
 *
 * @package	wp_wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @copyright nekudo.com
 * @author Simon Samtleben <support@nekudo.com>
 */
if(php_sapi_name() !== 'cli')
{
	exit('CLI mode only.');
}

require_once __DIR__ . '/../../../wp-load.php';
require_once __DIR__ . '/admin/libs/wemahu/src.php';

class WemahuCli
{
	protected $options;
	protected $wpdb;

	public function __construct()
	{
		global $wpdb;

		$this->options = get_option('wemahu');
		$this->wpdb = $wpdb;
	}

	public function doExecute($params)
	{
		// prepare settings:
		$rulesetId = (!empty($this->options['cron_ruleset'])) ? (int)$this->options['cron_ruleset'] : 1;

		if(!empty($params['ruleset']))
		{
			$rulesetId = (int)$params['ruleset'];
		}
		$sendReportEmail = (!empty($this->options['cron_sendmail'])) ? (int)$this->options['cron_sendmail'] : 1;
		$sendReportEmail = ((int)$sendReportEmail === 1) ? true : false;
		if($sendReportEmail === true)
		{
			$emailSystem = get_option('admin_email');
			$emailRecipient = (!empty($this->options['cron_email'])) ? $this->options['cron_email'] : '';
			$emailRecipient = (empty($emailRecipient)) ? $emailSystem : $emailRecipient;
		}
		$forceOutput = (isset($params['force_output'])) ? true : false;

		$Ruleset = $this->wpdb->get_row("SELECT * FROM " . $this->wpdb->prefix . "wm_rulesets WHERE id = " . (int)$rulesetId);
		if(empty($Ruleset))
		{
			$this->out('Error: Could not load ruleset.');
			return false;
		}

		$WemahuSettings = new Wemahu\Settings();
		$WemahuSettings->useApi = ((int)$this->options['use_api'] === 1) ? true : false;
		$WemahuSettings->audits['filecheck'] = ((int)$Ruleset->filecheck === 1) ? true : false;
		$WemahuSettings->auditSettings['filecheck']['regexCheck'] = ((int)$Ruleset->regex_check === 1) ? true : false;
		$WemahuSettings->auditSettings['filecheck']['hashCheck'] = ((int)$Ruleset->hash_check === 1) ? true : false;
		$WemahuSettings->auditSettings['filecheck']['scanDir'] = ABSPATH;
		$WemahuSettings->auditSettings['filecheck']['tmpDir'] = WP_PLUGIN_DIR . '/wemahu/tmp';
		$WemahuSettings->auditSettings['filecheck']['pathRegexWhitelistUser'] = WP_PLUGIN_DIR . '/wemahu/tmp/wemahu_regex_whitelist.wmdb';
		if(!empty($Ruleset->scandir))
		{
			$WemahuSettings->auditSettings['filecheck']['scanDir'] = $Ruleset->scandir;
		}
		$WemahuSettings->auditSettings['filecheck']['scanDir'] = rtrim($WemahuSettings->auditSettings['filecheck']['scanDir'], '/');
		if(!empty($Ruleset->regex_db))
		{
			$WemahuSettings->auditSettings['filecheck']['pathRegexDb'] = WP_PLUGIN_DIR . '/wemahu/admin/libs/wemahu/db/' . $Ruleset->regex_db . '.wmdb';
		}
		if(!empty($Ruleset->filetypes))
		{
			$WemahuSettings->auditSettings['filecheck']['extensionFilter'] = $Ruleset->filetypes;
		}
		if(!empty($Ruleset->filesize_max))
		{
			$WemahuSettings->auditSettings['filecheck']['sizeFilter'] = $Ruleset->filesize_max;
		}
		if(!empty($Ruleset->max_results_file))
		{
			$WemahuSettings->auditSettings['filecheck']['maxResultsFile'] = $Ruleset->max_results_file;
		}
		if(!empty($Ruleset->max_results_total))
		{
			$WemahuSettings->auditSettings['filecheck']['maxResultsTotal'] = $Ruleset->max_results_total;
		}
		if($WemahuSettings->auditSettings['filecheck']['hashCheck'] === true && !empty($Ruleset->hash_check_blacklist))
		{
			$WemahuSettings->auditSettings['filecheck']['hashCheckBlacklist'] = explode("\n", str_replace("\r", "", $Ruleset->hash_check_blacklist));
		}

		// Init Wemahu:
		$Wemahu = new Wemahu\Wemahu;
		$Wemahu->setSettings($WemahuSettings);
		$WemahuStorage = new Wemahu\Storage;
		$Wemahu->setStorage($WemahuStorage);
		$WemahuDatabase = new Wemahu\WordpressDatabase($this->wpdb);
		$Wemahu->setDatabase($WemahuDatabase);
		$initResult = $Wemahu->init();
		$runResult = $Wemahu->run();
		if($runResult !== true)
		{
			$this->out('Error while running Wemahu.');
			return false;
		}

		// Handle report:
		$WemahuReport = new Wemahu\WemahuReport($WemahuDatabase);
		$WemahuReport->loadItems();
		if($forceOutput === true)
		{
			$this->displayReport($WemahuReport);
			return true;
		}

		if($sendReportEmail === true)
		{
			$sendEmptyReport = (!empty($this->options['cron_emptyreport'])) ? (int)$this->options['cron_emptyreport'] : 1;
			if(empty($WemahuReport->reportItems) && $sendEmptyReport !== 1)
			{
				return true;
			}
			$this->mailReport($WemahuReport, $emailRecipient, $emailSystem);
		}
	}

	protected function displayReport($WemahuReport)
	{
		$reportText = $this->_getReportText($WemahuReport);
		echo $reportText;
		echo PHP_EOL;
	}

	protected function mailReport($WemahuReport, $emailTo, $emailFrom)
	{
		$blogname = get_option('blogname');
		$headers = 'From: '.$blogname.' <'.$emailFrom.'>' . "\r\n";
		$mailBody = $this->_getReportText($WemahuReport);
		wp_mail($emailTo, 'Wemahu Report', $mailBody, $headers);
	}

	private function _getReportText($WemahuReport)
	{
		$reportText = '';
		if(empty($WemahuReport) || empty($WemahuReport->reportItems))
		{
			$reportText .= "No potentially malicious code found\n";
		}
		else
		{
			if(empty($WemahuReport->reportItems['filecheck']))
			{
				$reportText .= "Filecheck did not report any suspicious files.\n";
			}
			else
			{
				$reportText = "=== Results of RegEx Check ===\n";
				$reportText.= "\n";
				foreach($WemahuReport->getItems('filecheck') as $i => $ReportItem)
				{
					if($ReportItem->checkName !== 'regexCheck')
					{
						continue;
					}
					$reportText .= "--- MATCH ---\n";
					$reportText .= "File: " . $ReportItem->affectedFile . "\n";
					$reportText.= "Matching Rule: " . $ReportItem->matchName . "\n";
					$reportText.= "Code: " . $ReportItem->match . "\n";
					$reportText.= "\n";
				}

				$reportText.= "=== Results of Hash Check ===\n";
				$reportText.= "\n";
				foreach($WemahuReport->getItems('filecheck') as $i => $ReportItem)
				{
					if($ReportItem->checkName !== 'hashCheck')
					{
						continue;
					}
					$reportText.= "--- MATCH ---\n";
					$reportText.= "File: " . $ReportItem->affectedFile . "\n";
					if($ReportItem->type === 'new_file')
					{
						$reportText.= "Type: New file\n";
					}
					elseif($ReportItem->type === 'modified_file')
					{
						$reportText.= "Type: Modified file\n";
					}
					$reportText.= "\n";
				}
			}
		}

		return $reportText;
	}
}
$shortopts = '';
$longopts = array(
	'force_output',
	'ruleset:'
);
$params = getopt($shortopts, $longopts);
$WemahuCli = new WemahuCli;
$WemahuCli->doExecute($params);