<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
namespace Wemahu;

interface Database
{
	public function setQuery($query);

	public function execute();

	public function q($value, $escape = true);

	public function getRow();
}