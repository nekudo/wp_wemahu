<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
namespace Wemahu;

use Wemahu\Database;

class JoomlaDatabase implements Database
{
	private $_JDBObject;

	public function __construct(\JDatabaseDriver $JDBObject)
	{
		$this->_JDBObject = $JDBObject;
	}

	public function setQuery($query)
	{
		$this->_JDBObject->setQuery($query);
		return $this;
	}

	public function execute()
	{
		return $this->_JDBObject->execute();
	}

	public function q($value, $escape = true)
	{
		return $this->_JDBObject->q($value, $escape);
	}

	public function getRow()
	{
		return $this->_JDBObject->loadObject();
	}

	public function getRows()
	{
		return $this->_JDBObject->loadObjectList();
	}
}