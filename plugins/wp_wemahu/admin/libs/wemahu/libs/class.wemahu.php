<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
namespace Wemahu;

use Wemahu\WemahuException;
use Wemahu\Report;

class Wemahu
{
	const WEMAHU_ROOT = __DIR__;

	private $_Settings;
	private $_Storage;
	private $_Report;
	private $_Database;
	private $_Api;

	private $_audits = array();
	private $_initialized = false;
	private $_intervalStart = 0;
	private $_isComplete = false;
	private $_auditMessages = array();

	public function setSettings(Settings $Settings)
	{
		$this->_Settings = $Settings;
		return true;
	}

	public function setStorage(Storage $Storage)
	{
		$this->_Storage = $Storage;
		return true;
	}

	public function setDatabase(Database $Database)
	{
		$this->_Database = $Database;
		return true;
	}

	public function isComplete()
	{
		return $this->_isComplete;
	}

	public function init()
	{
		try
		{
			// init/check libraries:
			if(!($this->_Settings instanceof Settings))
			{
				throw new WemahuException('Invalid settings object. Make sure settings are set.');
			}
			if(empty($this->_Settings->audits))
			{
				throw new WemahuException('No audits enabled in settings.');
			}
			if($this->_Settings->intervalMode === true && !($this->_Storage instanceof Storage))
			{
				throw new WemahuException('Invalid Storage object.');
			}
			if(!($this->_Database instanceof Database))
			{
				throw new WemahuException('Invalid database object.');
			}
			$this->_Report = new \Wemahu\WemahuReport($this->_Database);
			if(!($this->_Report instanceof Report))
			{
				throw new WemahuException('Invalid report object.');
			}
			if($this->_Settings->useApi === true)
			{
				$this->_Api = new NekudoApi($this->_Settings->apiUrl);
				$this->_Api->countAudit();
			}

			// init audits:
			foreach($this->_Settings->audits as $auditName => $auditEnabled)
			{
				if($auditEnabled !== true)
				{
					continue;
				}
				$auditName = strtolower($auditName);
				if(!file_exists(self::WEMAHU_ROOT . '/audits/class.audit_' . $auditName . '.php'))
				{
					throw new WemahuException('Audit file not found. (Name: ' . $auditName . ')');
				}

				require_once self::WEMAHU_ROOT . '/audits/class.audit_' . $auditName . '.php';
				$auditClassname = 'Wemahu\Audit' . ucfirst($auditName);
				$this->_audits[$auditName] = new $auditClassname($auditName, $this->_Settings);
				$this->_audits[$auditName]->setStorage($this->_Storage);
				$this->_audits[$auditName]->setDatabase($this->_Database);
				$this->_audits[$auditName]->setReport($this->_Report);
				$this->_audits[$auditName]->init();
				$auditMessages = $this->_audits[$auditName]->getMessages();
				$this->_audits[$auditName]->clearMessages();
				$this->_auditMessages = array_merge($this->_auditMessages, $auditMessages);
			}

			if($this->_Settings->useApi === true && !empty($this->_audits['filecheck']))
			{
				$this->updateSignatures();
			}

			if($this->_Settings->intervalMode === true)
			{
				$this->_Storage->set('settings', $this->_Settings, 'default');
				$this->_Storage->store();
			}

			$this->_initialized = true;
			return true;
		}
		catch(\Exception $Exception)
		{
			echo '<strong>Error:</strong> "' . $Exception->getMessage() . '" in:<br />';
			echo $Exception->getFile() . " (Line " . $Exception->getLine() . ")";
		}
	}

	public function reinit()
	{
		try
		{
			// init/check libraries:
			if(!($this->_Storage instanceof Storage))
			{
				throw new WemahuException('Invalid storage object');
			}
			$this->_Storage->load();
			$this->_Settings = $this->_Storage->get('settings', 'default');
			if(!($this->_Settings instanceof Settings))
			{
				throw new WemahuException('Invalid settings object. Make sure settings are set.');
			}
			if(!($this->_Database instanceof Database))
			{
				throw new WemahuException('Invalid database object.');
			}
			$this->_Report = new \Wemahu\WemahuReport($this->_Database);
			if(!($this->_Report instanceof Report))
			{
				throw new WemahuException('Invalid report object. Make sure settings are set.');
			}
			if($this->_Settings->useApi === true)
			{
				$this->_Api = new NekudoApi($this->_Settings->apiUrl);
			}

			foreach($this->_Settings->audits as $auditName => $auditEnabled)
			{
				if($auditEnabled !== true)
				{
					continue;
				}
				$auditName = strtolower($auditName);
				if(!file_exists(self::WEMAHU_ROOT . '/audits/class.audit_' . $auditName . '.php'))
				{
					throw new WemahuException('Audit file not found. (Name: ' . $auditName . ')');
				}

				require_once self::WEMAHU_ROOT . '/audits/class.audit_' . $auditName . '.php';
				$auditClassname = 'Wemahu\Audit' . ucfirst($auditName);
				$this->_audits[$auditName] = new $auditClassname($auditName, $this->_Settings);
				$this->_audits[$auditName]->setStorage($this->_Storage);
				$this->_audits[$auditName]->setDatabase($this->_Database);
				$this->_audits[$auditName]->setReport($this->_Report);
				$this->_audits[$auditName]->reinit();
			}
			$this->_initialized = true;
			return true;
		}
		catch(\Exception $Exception)
		{
			echo '<strong>Error:</strong> "' . $Exception->getMessage() . '" in:<br />';
			echo $Exception->getFile() . " (Line " . $Exception->getLine() . ")";
		}
	}

	public function run()
	{
		try
		{
			if($this->_initialized === false)
			{
				throw new WemahuException('Wemahu not initialized.');
			}
			if(empty($this->_audits))
			{
				throw new WemahuException('No audits enabled in settings.');
			}

			$this->_intervalStart = time();
			$allAuditsDone = true;
			foreach($this->_audits as $Audit)
			{
				$Audit->setIntervalStart($this->_intervalStart);
				$Audit->runAudit();
				$auditMessages = $Audit->getMessages();
				$Audit->clearMessages();
				$this->_auditMessages = array_merge($this->_auditMessages, $auditMessages);
				if($Audit->auditCompleted === false)
				{
					$allAuditsDone = false;
					break;
				}
			}
			$this->_isComplete = $allAuditsDone;
			if($this->_Settings->intervalMode === true)
			{
				$this->_Storage->store();
			}

			return true;
		}
		catch(\Exception $Exception)
		{
			echo '<strong>Error:</strong> "' . $Exception->getMessage() . '" in:<br />';
			echo $Exception->getFile() . " (Line " . $Exception->getLine() . ")";
		}
	}

	public function addToFilecheckWhitelist($reportId)
	{
		if(!isset($this->_audits['filecheck']))
		{
			throw new WemahuException('Filecheck Audit not loaded.');
		}

		$ReportItem = $this->_Report->getItem($reportId);
		$addResult = $this->_audits['filecheck']->addToWhitelist($ReportItem);
		if($this->_Settings->useApi === true)
		{
			$this->_Api->addWhitelistRequest($ReportItem);
		}
		if($addResult !== true)
		{
			return false;
		}
		return true;
	}

	public function reportMalware($reportId)
	{
		$ReportItem = $this->_Report->getItem($reportId);
		if($ReportItem->checkName === 'hashCheck')
		{
			$ReportItem->fileContent = file_get_contents($ReportItem->affectedFile);
		}
		$apiRequestResult = $this->_Api->reportMalware($ReportItem);
		if($apiRequestResult !== true)
		{
			return false;
		}
		return true;
	}

	public function getAuditMessages()
	{
		return $this->_auditMessages;
	}

	public function clearAuditMessages()
	{
		$this->_auditMessages = array();
		return true;
	}

	public function updateSignatures()
	{
		$message = 'Updating signatures... ';
		$signatureInfos = $this->_Api->getSignatureVersionInfos();
		if(empty($signatureInfos))
		{
			$message .= ' failed. (API: ' . $this->_Api->getError() . ')';
			$this->_auditMessages[] = $message;
			return false;
		}

		// check for regex-db update:
		$fileRegexDb = $this->_Settings->auditSettings['filecheck']['pathRegexDb'];
		$pathRegexDb = dirname($fileRegexDb);
		if(!is_writable($pathRegexDb))
		{
			$message .= ' failed. (Target directory not writeable.)';
			$this->_auditMessages[] = $message;
			return false;
		}
		$RegexDb = new FileStorageEngine($fileRegexDb);
		if(!empty($signatureInfos['signature_versions']['regex_complete']))
		{
			if((int)$signatureInfos['signature_versions']['regex_complete']['version'] > (int)$RegexDb->version)
			{
				$downloadResult = Helper::downloadFile($signatureInfos['signature_versions']['regex_complete']['downloadUrl'], $pathRegexDb.'/regex_complete.tmp');
				if($downloadResult === false)
				{
					$message .= ' failed. (Download failed.)';
					$this->_auditMessages[] = $message;
					return false;
				}
				if(sha1_file($pathRegexDb .'/regex_complete.tmp') !== $signatureInfos['signature_versions']['regex_complete']['checksum'])
				{
					$message .= ' failed. (Checksum missmatch)';
					$this->_auditMessages[] = $message;
					unlink($pathRegexDb .'/regex_complete.tmp');
					return false;
				}
				unlink($fileRegexDb);
				rename($pathRegexDb .'/regex_complete.tmp', $pathRegexDb . '/regex_complete.wmdb');
			}
		}

		// check for whitelist-db update:
		$fileRegexWhitelist = $this->_Settings->auditSettings['filecheck']['pathRegexWhitelist'];
		$pathRegexWhitelist = dirname($fileRegexWhitelist);
		if(!is_writable($pathRegexWhitelist))
		{
			$message .= ' failed. (Target directory not writeable.)';
			$this->_auditMessages[] = $message;
			return false;
		}
		$RegexWhitelist = new FileStorageEngine($fileRegexWhitelist);
		if(!empty($signatureInfos['signature_versions']['regex_whitelist']))
		{
			if((int)$signatureInfos['signature_versions']['regex_whitelist']['version'] > (int)$RegexWhitelist->version)
			{
				$downloadResult = Helper::downloadFile($signatureInfos['signature_versions']['regex_whitelist']['downloadUrl'], $pathRegexWhitelist.'/regex_whitelist.tmp');
				if($downloadResult === false)
				{
					$message .= ' failed. (Download failed.)';
					$this->_auditMessages[] = $message;
					return false;
				}
				if(sha1_file($pathRegexWhitelist .'/regex_whitelist.tmp') !== $signatureInfos['signature_versions']['regex_whitelist']['checksum'])
				{
					$message .= ' failed. (Checksum missmatch)';
					$this->_auditMessages[] = $message;
					unlink($pathRegexWhitelist .'/regex_whitelist.tmp');
					return false;
				}
				unlink($fileRegexWhitelist);
				rename($pathRegexWhitelist .'/regex_whitelist.tmp', $pathRegexWhitelist . '/regex_whitelist.wmdb');
			}
		}

		$message .= ' Done.';
		$this->_auditMessages[] = $message;
		return true;
	}

	/**
	 * Calculates a rough number how many percent of an audit are already
	 * done in case interval mode is enabled.
	 *
	 * @return int Percentage already done. 0 if unknown state.
	 */
	public function getPercentageDone()
	{
		$percentDone = 0;
		if($this->_Settings->audits['filecheck'] === true)
		{
			if($this->_Storage->get('filestackCreated', 'filecheck') === false)
			{
				$percentDone = 2;
				return $percentDone;
			}

			$filesInStack = $this->_Storage->get('filesInStack', 'filecheck');
			$filesProcessed = $this->_Storage->get('lastFileScanned', 'filecheck');
			$percentDone = (100 / $filesInStack) * $filesProcessed;
			if($percentDone < 2)
			{
				$percentDone = 2;
			}
			return $percentDone;
		}

		return 0;
	}
}