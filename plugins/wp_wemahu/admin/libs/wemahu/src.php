<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
$maxNestingLevel = ini_get('xdebug.max_nesting_level');
if(!empty($maxNestingLevel))
{
	ini_set('xdebug.max_nesting_level', 0);
}

// settings:
require_once __DIR__ . '/class.settings.php';

// exceptions:
require_once __DIR__ . '/libs/exceptions/class.wemahu_exception.php';
require_once __DIR__ . '/libs/exceptions/class.audit_exception.php';

// audits:
require_once __DIR__ . '/libs/audits/interface.audit.php';
require_once __DIR__ . '/libs/audits/class.audit_base.php';
require_once __DIR__ . '/libs/audits/class.audit_filecheck.php';

// reports:
require_once __DIR__ . '/libs/interface.report.php';
require_once __DIR__ . '/libs/class.report.php';
require_once __DIR__ . '/libs/class.report_item.php';

// misc:
require_once __DIR__ . '/libs/class.storage.php';
require_once __DIR__ . '/libs/class.pathstack.php';
require_once __DIR__ . '/libs/class.file_storage_engine.php';
require_once __DIR__ . '/libs/interface.database.php';
require_once __DIR__ . '/libs/class.joomla_database.php';
require_once __DIR__ . '/libs/class.wordpress_database.php';
require_once __DIR__ . '/libs/class.wemahu.php';
require_once __DIR__ . '/libs/class.helper.php';
require_once __DIR__ . '/libs/class.api.php';
require_once __DIR__ . '/libs/class.nekudo_api.php';