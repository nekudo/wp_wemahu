<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
namespace Wemahu;

class AuditBase
{
	public $auditCompleted = false;
	public $intervalStart = 0;
	protected $runMode;
	protected $messages = array();
	protected $auditName;
	protected $Settings;
	protected $Report;
	protected $Database;
	protected $Storage;

	public function __construct($auditName, Settings $Settings)
	{
		$this->auditName = $auditName;
		$this->Settings = $Settings;
		$this->overrideAuditSettings();
		$this->runMode = Helper::getRunMode();
	}

	public function setDatabase(Database &$Database)
	{
		$this->Database = $Database;
	}

	public function setStorage(Storage &$Storage)
	{
		$this->Storage = $Storage;
	}

	public function setReport(Report &$Report)
	{
		$this->Report = $Report;
	}

	public function setMessage($msg)
	{
		if(empty($msg))
		{
			return false;
		}
		$this->messages[] = ucfirst($this->auditName) . ': ' . $msg;
		return true;
	}

	public function getMessages()
	{
		return $this->messages;
	}

	public function clearMessages()
	{
		$this->messages = array();
		return true;
	}

	public function setIntervalStart($time)
	{
		$this->intervalStart = $time;
		return true;
	}

	public function intervalLimitIsReached()
	{
		if($this->Settings->intervalMode === false)
		{
			return false;
		}
		$secondsPassed = time() - $this->intervalStart;
		return ($secondsPassed >= $this->Settings->intervalMaxLength) ? true : false;
	}

	protected function overrideAuditSettings()
	{
		if(empty($this->Settings->auditSettings[$this->auditName]))
		{
			return true;
		}
		$auditSettings = $this->Settings->auditSettings[$this->auditName];

		foreach($auditSettings as $settingName => $settingValue)
		{
			if(isset($this->$settingName))
			{
				$this->$settingName = $settingValue;
			}
		}
		return true;
	}
}