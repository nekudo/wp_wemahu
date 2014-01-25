<?php
/**
 * @package	wp_wemahu
 * @license GNU General Public License version 2 or later; see license.txt
 * @copyright nekudo.com
 * @author Simon Samtleben <support@nekudo.com>
 */

class JsonResponse
{
	private $_responseType = '';
	private $_responseData = array();
	private $_errorMessage = '';

	public function __construct()
	{
		$this->_responseType = 'success';
	}

	public function setType($responseType)
	{
		if(empty($responseType))
		{
			return false;
		}
		$this->_responseType = $responseType;
		return true;
	}

	public function setData($key, $value)
	{
		if(empty($key))
		{
			return false;
		}
		$this->_responseData[$key] = $value;
		return true;
	}

	public function setError($errorMessage)
	{
		if(empty($errorMessage))
		{
			return false;
		}
		$this->_errorMessage = $errorMessage;
		$this->setType('error');
		return true;
	}

	public function setMsg($msg, $type = 'success')
	{
		if(empty($msg) || empty($type))
		{
			return false;
		}

		$this->_responseType = 'message';
		$this->_responseData['msgType'] = $type;
		$this->_responseData['msg'] = $msg;
		return true;
	}

	public function getResponseData()
	{
		$responseData = array();
		switch($this->_responseType)
		{
			case 'error':
				$responseData['type'] = 'error';
				$responseData['errorMsg'] = $this->_errorMessage;
				$responseData['errorMsgHtml'] = '<div class="alert alert-error">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<strong>Error!</strong> ' . $this->_errorMessage . '
					</div>';
				break;

			case 'message':
				$responseData['type'] = 'message';
				$responseData['msg'] = $this->_responseData['msg'];
				if($this->_responseData['msgType'] === 'success')
				{
					$responseData['msgHtml'] = '<div class="alert alert-success">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					' . $this->_responseData['msg'] . '
					</div>';
				}
				elseif($this->_responseData['msgType'] === 'warning')
				{
					$responseData['msgHtml'] = '<div class="alert alert-warning">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<strong>Warning!</strong> ' . $this->_responseData['msg'] . '
					</div>';
				}
				else
				{
					$responseData['msgHtml'] = '<div class="alert alert-notice">
					<button type="button" class="close" data-dismiss="alert">&times;</button>
					<strong>Notice!</strong> ' . $this->_responseData['msg'] . '
					</div>';
				}
				break;

			default:
				$responseData['type'] = $this->_responseType;
				$responseData['data'] = $this->_responseData;
				break;
		}

		return json_encode($responseData);
	}
}