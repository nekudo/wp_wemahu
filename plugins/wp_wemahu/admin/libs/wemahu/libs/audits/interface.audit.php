<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
namespace Wemahu;

interface Audit
{
	public function __construct($auditName, Settings $Settings);

	public function setDatabase(Database &$Database);

	public function setStorage(Storage &$Storage);

	public function setReport(Report &$Report);

	public function init();

	public function reinit();

	public function runAudit();
}