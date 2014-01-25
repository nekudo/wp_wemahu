<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
namespace Wemahu;

use Wemahu\Report;

class WemahuReport implements Report
{
	protected $runMode;
	protected $Database;
	public $reportItems = array();

	public function __construct(Database $Database)
	{
		$this->Database = $Database;
		$this->runMode = Helper::getRunMode();
	}

	public function addItem(ReportItem $ReportItem)
	{
		if(empty($ReportItem->auditName))
		{
			throw new WemahuException('AuditName in ReportItem can not be empty.');
		}

		$auditName = $ReportItem->auditName;
		$reportitem = serialize($ReportItem);
		$reportitem = base64_encode($reportitem);
		$this->Database->setQuery("INSERT INTO #__wm_reportitems (reportitem,audit_name,mode) VALUES(" . $this->Database->q($reportitem) . "," . $this->Database->q($auditName) . "," . $this->Database->q($this->runMode) . ")");
		$this->Database->execute();

		return true;
	}

	public function loadItems()
	{
		$this->Database->setQuery("SELECT * FROM #__wm_reportitems WHERE mode = " . $this->Database->q($this->runMode))->execute();
		$rows = $this->Database->getRows();
		if(empty($rows))
		{
			return false;
		}
		foreach($rows as $row)
		{
			$ReportItem = unserialize(base64_decode($row->reportitem));
			if(!isset($this->reportItems[$row->audit_name]))
			{
				$this->reportItems[$row->audit_name] = array();
			}
			$this->reportItems[$row->audit_name][$row->id_reportitem] = $ReportItem;
		}
		return true;
	}

	public function getItems($auditName = '')
	{
		if(empty($auditName))
		{
			return $this->reportItems;
		}

		if(!isset($this->reportItems[$auditName]))
		{
			return false;
		}

		return $this->reportItems[$auditName];
	}

	public function getItem($reportId)
	{
		if(empty($reportId))
		{
			return false;
		}
		$this->Database->setQuery("SELECT * FROM #__wm_reportitems WHERE id_reportitem = " . (int)$reportId . " AND mode = " . $this->Database->q($this->runMode))->execute();
		$row = $this->Database->getRow();
		if(empty($row))
		{
			return false;
		}
		$ReportItem = unserialize(base64_decode($row->reportitem));
		return $ReportItem;
	}
}