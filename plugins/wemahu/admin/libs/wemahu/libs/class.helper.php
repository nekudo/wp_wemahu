<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
namespace Wemahu;

class Helper
{
	public static function downloadFile($urlSource, $targetPath)
	{
		if(empty($urlSource) || empty($targetPath))
		{
			return false;
		}

		// download file (writing directly to filepointer with curl so we do not run into memory limits)
		$fp = fopen($targetPath, 'w');
		if($fp === false)
		{
			return false;
		}
		$ch = curl_init($urlSource);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Wemahu');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		$downloadResult = curl_exec($ch);
		curl_close($ch);
		fclose($fp);
		if($downloadResult !== true)
		{
			return false;
		}

		// check if file exits on local drive:
		if(!file_exists($targetPath))
		{
			return false;
		}
		return true;
	}

	/**
	 * Detects if script was called through cli interface or not.
	 *
	 * @return string Returns "c" if script is executed from commandline or "w" otherwise.
	 */
	public static function getRunMode()
	{
		return (php_sapi_name() === 'cli') ? 'c' : 'w';
	}
}