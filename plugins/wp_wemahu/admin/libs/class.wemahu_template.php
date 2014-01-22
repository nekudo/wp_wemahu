<?php
/**
 * @package	wp_wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @copyright nekudo.com
 * @author Simon Samtleben <support@nekudo.com>
 */
class WemahuTemplate
{
	protected $templateVars = array();

	public function __set($name, $value)
	{
		$this->templateVars[$name] = $value;
	}

	public function __get($name)
	{
		return $this->templateVars[$name];
	}

	public function __isset($name)
	{
		return isset($this->templateVars[$name]);
	}

	public function loadTemplate($path)
	{
		if(empty($path) || !file_exists($path))
		{
			return false;
		}
		ob_start();
		include $path;
		return ob_get_clean();
	}
}