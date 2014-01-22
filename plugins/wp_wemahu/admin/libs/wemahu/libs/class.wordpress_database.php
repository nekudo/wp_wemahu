<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
namespace Wemahu;

use Wemahu\Database;

class WordpressDatabase implements Database
{
	protected $wpdb;
	protected $query = '';


	public function __construct(\wpdb $wpdb)
	{
		$this->wpdb = $wpdb;
	}

	public function setQuery($query)
	{
		$this->query = str_replace('#__', $this->wpdb->prefix, $query);
		return $this;
	}

	public function execute()
	{
		if(empty($this->query))
		{
			return false;
		}
		return $this->wpdb->query($this->query);
	}

	public function q($value, $escape = true)
	{
		$this->wpdb->escape_by_ref($value);
		return ($escape === true) ? "'" . $value . "'" : $value;
	}

	public function getRow()
	{
		if(empty($this->query))
		{
			return false;
		}
		return $this->wpdb->get_row($this->query);
	}

	public function getRows()
	{
		if(empty($this->query))
		{
			return false;
		}
		return $this->wpdb->get_results($this->query);
	}
}