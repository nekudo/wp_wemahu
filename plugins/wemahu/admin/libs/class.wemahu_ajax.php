<?php
/**
 * @package	wp_wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @copyright nekudo.com
 * @author Simon Samtleben <support@nekudo.com>
 */

require_once 'wemahu/src.php';

class WemahuAjax
{
	protected $wpdb;
	protected $JsonResponse;
	protected $options;

	public function __construct(wpdb $wpdb, $options)
	{
		$this->wpdb = $wpdb;
		$this->JsonResponse = new JsonResponse;
		$this->options = $options;
	}

	/**
	 * Inits wemahu scanner by passing necessary objects like settings and database.
	 *
	 */
	public function initWemahu()
	{
		$rulesetId = (int)$_POST['ruleset'];
		if(empty($rulesetId))
		{
			$this->returnError('No ruleset selected.');
		}
		$ModelRuleset = new ModelRuleset($this->wpdb);
		$rulesetData = $ModelRuleset->getRulesetData($rulesetId);
		if(empty($rulesetData))
		{
			$this->returnError('Invalid ruleset.');
		}

		// prepare Wemahu settings:
		$WemahuSettings = new Wemahu\Settings();
		$WemahuSettings->intervalMode = true;
		$WemahuSettings->useApi = ((int)$this->options['use_api'] === 1) ? true : false;
		$WemahuSettings->audits['filecheck'] = ((int)$rulesetData['filecheck'] === 1) ? true : false;
		$WemahuSettings->auditSettings['filecheck']['regexCheck'] = ((int)$rulesetData['regex_check'] === 1) ? true : false;
		$WemahuSettings->auditSettings['filecheck']['hashCheck'] = ((int)$rulesetData['hash_check'] === 1) ? true : false;
		$WemahuSettings->auditSettings['filecheck']['scanDir'] = ABSPATH;
		$WemahuSettings->auditSettings['filecheck']['tmpDir'] = WP_PLUGIN_DIR . '/wemahu/tmp';
		$WemahuSettings->auditSettings['filecheck']['pathRegexWhitelistUser'] = WP_PLUGIN_DIR . '/wemahu/tmp/wemahu_regex_whitelist.wmdb';
		if(!empty($rulesetData['scandir']))
		{
			$WemahuSettings->auditSettings['filecheck']['scanDir'] = $rulesetData['scandir'];
		}
		$WemahuSettings->auditSettings['filecheck']['scanDir'] = rtrim($WemahuSettings->auditSettings['filecheck']['scanDir'], '/');
		if(!empty($rulesetData['regex_db']))
		{
			$WemahuSettings->auditSettings['filecheck']['pathRegexDb'] = WP_PLUGIN_DIR . '/wemahu/admin/libs/wemahu/db/' . $rulesetData['regex_db'] . '.wmdb';
		}
		if(!empty($rulesetData['filetypes']))
		{
			$WemahuSettings->auditSettings['filecheck']['extensionFilter'] = $rulesetData['filetypes'];
		}
		if(!empty($rulesetData['filesize_max']))
		{
			$WemahuSettings->auditSettings['filecheck']['sizeFilter'] = $rulesetData['filesize_max'];
		}
		if(!empty($rulesetData['max_results_file']))
		{
			$WemahuSettings->auditSettings['filecheck']['maxResultsFile'] = $rulesetData['max_results_file'];
		}
		if(!empty($rulesetData['max_results_total']))
		{
			$WemahuSettings->auditSettings['filecheck']['maxResultsTotal'] = $rulesetData['max_results_total'];
		}
		if($WemahuSettings->auditSettings['filecheck']['hashCheck'] === true && !empty($rulesetData['hash_check_blacklist']))
		{
			$WemahuSettings->auditSettings['filecheck']['hashCheckBlacklist'] = explode("\n", str_replace("\r", "", $rulesetData['hash_check_blacklist']));
		}

		// Init Wemahu:
		$Wemahu = new Wemahu\Wemahu;
		$Wemahu->setSettings($WemahuSettings);
		$WemahuStorage = new Wemahu\Storage;
		$Wemahu->setStorage($WemahuStorage);
		$WemahuDatabase = new Wemahu\WordpressDatabase($this->wpdb);
		$Wemahu->setDatabase($WemahuDatabase);
		$initResult = $Wemahu->init();

		// Send Response:
		if($initResult === false)
		{
			$this->JsonResponse->setError('Wemahu initialization failed.');
		}
		$auditMessages = $Wemahu->getAuditMessages();
		$auditMessagesHtml = implode('<br />', $auditMessages) . '<br />';
		$this->JsonResponse->setType('init_success');
		$this->JsonResponse->setData('init_msg', $auditMessagesHtml);
		echo $this->JsonResponse->getResponseData();
		exit;
	}

	public function runWemahu()
	{
		// Continue last Wemahu request:
		$Wemahu = new Wemahu\Wemahu;
		$WemahuDatabase = new Wemahu\WordpressDatabase($this->wpdb);
		$Wemahu->setDatabase($WemahuDatabase);
		$WemahuStorage = new Wemahu\Storage;
		$Wemahu->setStorage($WemahuStorage);
		$Wemahu->reinit();
		$runResult = $Wemahu->run();
		if($runResult !== true)
		{
			$this->JsonResponse->setError('An error appeared while running the audits.');
		}
		if($Wemahu->isComplete() === true)
		{
			$this->JsonResponse->setType('audit_complete');
			$this->JsonResponse->setData('audit_msg', 'Audit complete. Fetching results...<br />');
		}
		else
		{
			$auditMessages = $Wemahu->getAuditMessages();
			$auditMessagesHtml = implode('<br />', $auditMessages) . '<br />';
			$this->JsonResponse->setType('audit_incomplete');
			$this->JsonResponse->setData('audit_msg', $auditMessagesHtml);
			$this->JsonResponse->setData('percentDone', $Wemahu->getPercentageDone());
		}

		echo $this->JsonResponse->getResponseData();
		exit;
	}

	public function getWemahuReport()
	{
		$WemahuDatabase = new Wemahu\WordpressDatabase($this->wpdb);
		$WemahuReport = new Wemahu\WemahuReport($WemahuDatabase);
		$WemahuReport->loadItems();
		$View = new WemahuTemplate;
		$View->Report = $WemahuReport;
		$this->JsonResponse->setType('report_success');
		$this->JsonResponse->setData('reportHtml', $View->loadTemplate(WP_PLUGIN_DIR . '/wemahu/admin/views/ajax/report.php'));
		echo $this->JsonResponse->getResponseData();
		exit;
	}

	public function addToWhitelist()
	{
		$reportId = (int)$_POST['reportId'];
		if(empty($reportId))
		{
			$this->JsonResponse->setError('No report-id given.');
			echo $this->JsonResponse->getResponseData();
			exit;
		}

		$Wemahu = new Wemahu\Wemahu;
		$WemahuDatabase = new Wemahu\WordpressDatabase($this->wpdb);
		$Wemahu->setDatabase($WemahuDatabase);
		$WemahuStorage = new Wemahu\Storage;
		$Wemahu->setStorage($WemahuStorage);
		$initResult = $Wemahu->reinit();
		if($initResult !== true)
		{
			$this->JsonResponse->setError('Could not init Wemahu.');
			echo $this->JsonResponse->getResponseData();
			exit;
		}

		$addResult = $Wemahu->addToFilecheckWhitelist($reportId);
		if($addResult !== true)
		{
			$this->JsonResponse->setError('Could not add item to whitelist.');
			echo $this->JsonResponse->getResponseData();
			exit;
		}

		$this->JsonResponse->setMsg('Item successfully added to whitelist.');
		echo $this->JsonResponse->getResponseData();
		exit;
	}

	public function addToBlacklist()
	{
		$reportId = (int)$_POST['reportId'];
		if(empty($reportId))
		{
			$this->JsonResponse->setError('No report-id given.');
			echo $this->JsonResponse->getResponseData();
			exit;
		}

		$Wemahu = new Wemahu\Wemahu;
		$WemahuDatabase = new Wemahu\WordpressDatabase($this->wpdb);
		$Wemahu->setDatabase($WemahuDatabase);
		$WemahuStorage = new Wemahu\Storage;
		$Wemahu->setStorage($WemahuStorage);
		$initResult = $Wemahu->reinit();
		if($initResult !== true)
		{
			$this->JsonResponse->setError('Could not init Wemahu.');
			echo $this->JsonResponse->getResponseData();
			exit;
		}

		$addResult = $Wemahu->reportMalware($reportId);
		if($addResult !== true)
		{
			$this->JsonResponse->setError('Could not send malware-report.');
			echo $this->JsonResponse->getResponseData();
			exit;
		}

		$this->JsonResponse->setMsg('Item successfully reported as malware. Thanks for your help.');
		echo $this->JsonResponse->getResponseData();
		exit;
	}

	public function getReportItemModal()
	{
		$reportId = (int)$_POST['reportId'];
		if(empty($reportId))
		{
			$this->JsonResponse->setError('No report-id given.');
			echo $this->JsonResponse->getResponseData();
			JFactory::getApplication()->close();
		}

		$WemahuDatabase = new Wemahu\WordpressDatabase($this->wpdb);
		$WemahuReport = new Wemahu\WemahuReport($WemahuDatabase);
		$ReportItem = $WemahuReport->getItem($reportId);
		if(empty($ReportItem))
		{
			$this->JsonResponse->setError('Invalid report item');
			echo $this->JsonResponse->getResponseData();
			JFactory::getApplication()->close();
		}

		$View = new WemahuTemplate;
		$View->reportId = $reportId;
		$View->ReportItem = $ReportItem;
		$View->useApi = ((int)$this->options['use_api'] === 1) ? true : false;
		$this->JsonResponse->setData('modalHtml', $View->loadTemplate(WP_PLUGIN_DIR . '/wemahu/admin/views/ajax/report_item_modal.php'));
		echo $this->JsonResponse->getResponseData();
		exit;
	}

	public function getModel($name = 'Dashboard', $prefix = 'WemahuModel', $config = array())
	{
		$model = parent::getModel($name, $prefix, $config);
		return $model;
	}

	protected function returnError($errorMessage = '')
	{
		$this->JsonResponse->setError($errorMessage);
		echo $this->JsonResponse->getResponseData();
		exit;
	}
}