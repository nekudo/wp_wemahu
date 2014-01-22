<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
namespace Wemahu;

class ReportItem
{
	public $auditName;
	public $checkName;
	protected $reportItemData;

	public function __construct($auditName, $checkName = '')
	{
		if(empty($auditName))
		{
			throw new WemahuException('auditName can not be empty.');
		}
		$this->setAuditName($auditName);
		$this->setCheckName($checkName);
	}

	public function __set($name, $value)
	{
		$this->reportItemData[$name] = $value;
	}

	public function __get($name)
	{
		if(!isset($this->reportItemData[$name]))
		{
			return null;
		}
		return $this->reportItemData[$name];
	}

	public function __isset($name)
	{
		return isset($this->reportItemData[$name]);
	}

	public function setAuditName($auditName)
	{
		$this->auditName = $auditName;
	}

	public function setCheckName($checkName)
	{
		$this->checkName = $checkName;
	}

	public function getChecksum()
	{
		switch($this->checkName)
		{
			case 'regexCheck':
				return sha1($this->matchSnippet . '###' . basename($this->affectedFile));
			break;

			case 'hashCheck':
				return sha1(sha1_file($this->affectedFile) .  '###' . basename($this->affectedFile));
			break;
		}
		return false;
	}

	public function toArray()
	{
		$data = array();
		foreach ($this->reportItemData as $key => $value)
		{
			$data[$key] = $value;
		}
		return $data;
	}
}