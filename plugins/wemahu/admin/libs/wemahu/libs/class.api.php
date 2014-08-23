<?php
/**
 * @package	Wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @author Simon Samtleben <support@nekudo.com>
 */
namespace Wemahu;

class Api
{
	protected $apiUrl;
	protected $resultType = 'json';
	protected $requestResult;
	protected $error;

	public function __construct($apiUrl)
	{
		if(empty($apiUrl))
		{
			throw new WemahuException('API must be set.');
		}
		$this->apiUrl = $apiUrl;
	}

	public function doGetRequest($requestUri)
	{
		if(empty($requestUri))
		{
			return false;
		}

		$requestUrl = $this->apiUrl . $requestUri;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, 'Wemahu Joomla Instance');
		curl_setopt($ch, CURLOPT_URL, $requestUrl);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		if($result === false)
		{
			$this->error = curl_error($ch);
		}
		curl_close($ch);

		if($this->resultType === 'json')
		{
			$result = json_decode($result, true);
			if(!is_array($result))
			{
				$this->error = 'Invalid Response';
				return false;
			}
			if($result['type'] === 'error')
			{
				$this->error = $result['message'];
				return false;
			}
			$this->requestResult = $result['data'];
			return true;
		}
		else
		{
			$this->requestResult = $result;
		}
		unset($result);

		return true;
	}

	public function doPostRequest($requestUri, $requestData = array())
	{
		if(empty($requestUri))
		{
			return false;
		}

		$requestUrl = $this->apiUrl . $requestUri;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, 'Wemahu Joomla Instance');
		curl_setopt($ch, CURLOPT_URL, $requestUrl);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
		$result = curl_exec($ch);
		if($result === false)
		{
			$this->error = curl_error($ch);
		}
		curl_close($ch);

		if($this->resultType === 'json')
		{
			$result = json_decode($result, true);
			if(!is_array($result))
			{
				$this->error = 'Invalid Response';
				return false;
			}
			if($result['type'] === 'error')
			{
				$this->error = $result['message'];
				return false;
			}
			$this->requestResult = $result['data'];
			return true;
		}
		else
		{
			$this->requestResult = $result;
		}
		unset($result);

		return true;
	}

	public function getResult()
	{
		return $this->requestResult;
	}

	public function getError()
	{
		return $this->error;
	}
}