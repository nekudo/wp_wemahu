<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
namespace Wemahu;

class Pathstack
{
	protected $Database;
	private $_tablename;
	private $_pathcache = array();

	public function __construct(Database $Database, $tablename)
	{
		if(empty($tablename))
		{
			throw new WemahuException('Tablename can not be empty in pathstack creation.');
		}
		$this->Database = $Database;
		$this->_tablename = $tablename;
	}

	public function addPath($path)
	{
		if(empty($path))
		{
			return false;
		}
		$this->_pathcache[] = $path;
		return true;
	}

	public function clear()
	{
		$this->_pathcache = array();
		return true;
	}

	public function save()
	{
		if(empty($this->_pathcache))
		{
			return true;
		}
		$runMode = Helper::getRunMode();
		$query = "INSERT INTO " . $this->_tablename . "(path, mode) VALUES ";
		foreach($this->_pathcache as $path)
		{
			$query .= "(" . $this->Database->q($path) . ", '" . $runMode . "'),";
		}
		$query = substr($query, 0, -1);
		$this->Database->setQuery($query);
		$queryResult =  $this->Database->execute();
		if($queryResult !== true)
		{
			return false;
		}
		$this->_pathcache = array();
		return true;
	}
}