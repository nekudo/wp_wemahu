<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
namespace Wemahu;

interface Report
{
	public function addItem(ReportItem $ReportItem);

	public function getItems($auditName = '');

	public function getItem($reportId);
}