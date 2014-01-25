<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
namespace Wemahu;

class NekudoApi extends Api
{
	public function __construct($apiUrl)
	{
		parent::__construct($apiUrl);
	}

	/**
	 * Fetches signature infos from nekudo.com.
	 *
	 * @return mixed Returns array with version information on success or boolean false on error.
	 */
	public function getSignatureVersionInfos()
	{
		$requestResult = $this->doGetRequest('/signature/versions');
		if($requestResult === false)
		{
			return false;
		}
		return $this->getResult();
	}

	/**
	 * Submits a "whitelist-request" to nekudo.com.
	 *
	 * @param \Wemahu\ReportItem $ReportItem A filecheck report-item.
	 * @return boolean Returns true if request was successfully submitted or false otherwise.
	 */
	public function addWhitelistRequest(ReportItem $ReportItem)
	{
		$requestData = array(
			'itemhash' => sha1($ReportItem->matchSnippet . '###' . basename($ReportItem->affectedFile)),
			'item' => base64_encode(serialize($ReportItem->toArray())),
		);
		$requestResult = $this->doPostRequest('/signature/whitelistrequest', $requestData);
		return $requestResult;
	}

	/**
	 * Submits a malware report to nekudo.com.
	 *
	 * @param \Wemahu\ReportItem $ReportItem A filecheck report-item.
	 * @return boolean Returns true if request was successfully submitted or false otherwise.
	 */
	public function reportMalware(ReportItem $ReportItem)
	{
		$requestData = array(
			'itemhash' => $ReportItem->getChecksum(),
			'item' => base64_encode(serialize($ReportItem->toArray())),
		);
		$requestResult = $this->doPostRequest('/general/malwarereport', $requestData);
		return $requestResult;
	}

	/**
	 * Increases the total audits statistic by 1.
	 *
	 * @return boolean Returns true if request was successfully submitted or false otherwise.
	 */
	public function countAudit()
	{
		$requestResult = $this->doGetRequest('/stats/countaudit');
		return $requestResult;
	}
}