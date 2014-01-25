<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
namespace Wemahu;

/**
 * Defines settings for the Wemahu ScanEngine as well as the various
 * audits. Attributs should be public so settings can be changed by external
 * application.
 */
class Settings
{
	public $intervalMode = false;
	public $intervalMaxLength = 5;
	public $apiUrl = 'http://wmapi.nekudo.com';
	public $useApi = true;

	public $audits = array();
	public $auditSettings = array();

	public function __construct()
	{
		$this->loadAuditSettings();
	}

	public function loadAuditSettings()
	{
		// enable/disable audits:
		$this->audits = array(
			'filecheck' => true,
		);

		// settings for filecheck audit:
		$this->auditSettings = array(
			'filecheck' => array(
				'scanDir' => '.',
				'tmpDir' => '/tmp/',
				'pathRegexDb' => __DIR__ . '/db/regex_complete.wmdb',
				'pathRegexWhitelist' => __DIR__ . '/db/regex_whitelist.wmdb',
				'regexCheck' => true,
				'hashCheck' => true,
				'extensionFilter' => 'php,php4,php5,js,html,gif,png,jpg',
				'sizeFilter' => 1024 * 500, // 500kb
				'maxResultsFile' => 5,
				'maxResultsTotal' => 100,
			),
		);
	}
}