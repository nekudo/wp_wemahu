<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
namespace Wemahu;

class FileStorageEngine
{
	private $_pathDbFile = '';
	private $_items = array();
	private $_pkName = '';
	private $_autoIncrement = 0;
	private $_fieldCount = 0;
	private $_fieldNames = array();

	public $version = '';

	public function __construct($pathDbFile)
	{
		if(empty($pathDbFile) || !file_exists($pathDbFile))
		{
			throw new WemahuException('Database file not found.');
		}
		$this->_pathDbFile = $pathDbFile;
		$this->_load();
	}

	public function getAll()
	{
		return $this->_items;
	}

	public function get($key, $default)
	{
		if(!isset($this->_items[$key]))
		{
			return $default;
		}
		return $this->_items[$key];
	}

	public function add($values)
	{
		if(!is_array($values))
		{
			return false;
		}
		if(!isset($values[$this->_pkName]))
		{
			$values[$this->_pkName] = $this->_autoIncrement;
		}
		if(count($values) !== $this->_fieldCount)
		{
			return false;
		}
		if(isset($this->_items[$values[$this->_pkName]]))
		{
			return false;
		}

		$valuesOrdered = array();
		foreach($this->_fieldNames as $fieldName)
		{
			if(!isset($values[$fieldName]))
			{
				return false;
			}
			$valuesOrdered[$fieldName] = $values[$fieldName];
		}

		$this->_items[] = $valuesOrdered;
		return $this->_save();
	}

	public function delete($id)
	{
		if(!isset($this->_items[$id]))
		{
			return false;
		}
		unset($this->_items[$id]);
		return $this->_save();
	}

	private function _load()
	{
		if(empty($this->_pathDbFile))
		{
			throw new WemahuException('Database-file can not be empty.');
		}
		$fileContent = file_get_contents($this->_pathDbFile);
		if($fileContent === false)
		{
			throw new WemahuException('Could not load Database file.');
		}
		$lines = explode("\n", $fileContent);

		// first lines contain meta-data:
		if(count($lines) < 3)
		{
			throw new WemahuException('Database file seems to be invalid.');
		}
		$matches = array();
		$matchCount = preg_match('#>\sWMDBVN\s-\s(?<version>[0-9]{10})$#iUs', $lines[0], $matches);
		if($matchCount !== 1 || empty($matches['version']))
		{
			throw new WemahuException('Database file seems to be invalid.');
		}
		$this->version = $matches['version'];
		unset($lines[0]);

		$matchCount = 0;
		$matches = array();
		$matchCount = preg_match('#>\sWMDBFN\s-\s(?<fieldnames>.+)$#iUs', $lines[1], $matches);
		if($matchCount !== 1 || empty($matches['fieldnames']))
		{
			throw new WemahuException('Database file seems to be invalid.');
		}
		$this->_fieldNames = explode(',', $matches['fieldnames']);
		$this->_fieldCount = count($this->_fieldNames);
		unset($lines[1]);

		$matchCount = 0;
		$matches = array();
		$matchCount = preg_match('#>\sWMDBPK\s-\s(?<pk>.+)$#iUs', $lines[2], $matches);
		if($matchCount !== 1 || empty($matches['pk']))
		{
			throw new WemahuException('Database file seems to be invalid.');
		}
		$this->_pkName = $matches['pk'];
		unset($lines[2]);

		if(!in_array($this->_pkName, $this->_fieldNames))
		{
			throw new WemahuException('Invalid primary key.');
		}

		// db-file can be empty:
		if(empty($lines))
		{
			return true;
		}

		foreach($lines as $line)
		{
			$lineTrimmed = trim($line);
			if(empty($lineTrimmed))
			{
				continue;
			}
			$cols = explode('@@', $lineTrimmed);
			if(count($cols) !== $this->_fieldCount)
			{
				continue;
			}
			$item = array_combine($this->_fieldNames, $cols);
			$this->_items[$item[$this->_pkName]] = $item;
			$this->_autoIncrement = ($item[$this->_pkName] > $this->_autoIncrement) ? $item[$this->_pkName] : $this->_autoIncrement;
		}
		$this->_autoIncrement++;

		return true;
	}

	private function _save()
	{
		if(empty($this->_fieldNames))
		{
			throw new WemahuException('Field names are empty. Can\'t save database.');
		}
		if(empty($this->version))
		{
			throw new WemahuException('No version set. Can\'t save database.');
		}
		if(empty($this->_pkName))
		{
			throw new WemahuException('Primary key not set. Can\'t save database.');
		}

		$fileContent = '';
		$fileContent .= "> WMDBVN - " . $this->version . "\n";
		$fileContent .= "> WMDBFN - " . implode(',', $this->_fieldNames) . "\n";
		$fileContent .= "> WMDBPK - " . $this->_pkName . "\n";
		foreach($this->_items as $item)
		{
			$fileContent .= implode('@@', $item) . "\n";
		}
		$putResult = file_put_contents($this->_pathDbFile, $fileContent);
		if($putResult === false)
		{
			throw new WemahuException('Could not write database file.');
		}
		unset($fileContent);
		return true;
	}
}