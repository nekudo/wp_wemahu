<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
namespace Wemahu;

/**
 * All data that is need in multiple requests should go into this storage.
 */

class Storage
{
	private $_storage = array();

	public function __construct()
	{
		if(session_id() === '')
		{
			session_start();
		}
	}

	public function set($key, $value, $namespace = null)
	{
		if(empty($key))
		{
			throw new WemahuException('Storage key can not be empty.');
		}
		if(empty($namespace))
		{
			$this->_storage[$key] = $value;
			return true;
		}
		$this->_storage[$namespace][$key] = $value;
		return true;
	}

	public function get($key, $namespace = null)
	{
		if(empty($key))
		{
			throw new WemahuException('Storage key can not be empty.');
		}
		if(empty($namespace))
		{
			if(!isset($this->_storage[$key]))
			{
				return false;
			}
			return $this->_storage[$key];
		}
		else
		{
			if(!isset($this->_storage[$namespace]))
			{
				return false;
			}
			if(!isset($this->_storage[$namespace][$key]))
			{
				return false;
			}
			return $this->_storage[$namespace][$key];
		}
	}

	public function getAll($namespace = null)
	{
		if(empty($namespace))
		{
			return $this->_storage;
		}
		else
		{
			if(!isset($this->_storage[$namespace]))
			{
				return false;
			}
			return $this->_storage[$namespace];
		}
	}

	public function store()
	{
		$data = serialize($this->_storage);
		$_SESSION['wemahu_storage'] = $data;
		return true;
	}

	public function load()
	{
		if(!isset($_SESSION['wemahu_storage']))
		{
			throw new WemahuException('Could not load wemahu storage from session');
		}
		$data = unserialize($_SESSION['wemahu_storage']);
		if(!is_array($data))
		{
			throw new WemahuException('Could not unserialze session');
		}
		$this->_storage = $data;
		return true;
	}
}