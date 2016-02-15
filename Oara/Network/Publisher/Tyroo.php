<?php
namespace Oara\Network\Publisher;
/**
 The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
 of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.

 Copyright (C) 2016  Fubra Limited
 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU Affero General Public License for more details.
 You should have received a copy of the GNU Affero General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.

 Contact
 ------------
 Fubra Limited <support@fubra.com> , +44 (0)1252 367 200
 **/
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   ShareASale
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Tyroo extends \Oara\Network {

	/**
	 * username
	 * @var string
	 */
	private $_username = null;

	/**
	 * password
	 * @var string
	 */
	private $_password = null;

	/**
	 * sessionID
	 * @var string
	 */
	private $_sessionID = null;

	/**
	 * publisherID
	 * @var string
	 */
	private $_publisherID = null;

	/**
	 * windowid
	 * @var string
	 */
	private $_windowid = null;

	/**
	 * sessionIDCurl
	 * @var string
	 */
	private $_sessionIDCurl = null;

	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return ShareASale
	 */
	public function __construct($credentials) {

		$this->_username = $credentials['user'];
		$this->_password = $credentials['password'];

		$postdata = http_build_query(
				array('class' => 'Logon',
						'method' => 'logon',
						'val1' => $this->_username,
						'val2' => $this->_password,
						'val3' => ''));
		$opts = array('http' =>array('method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => $postdata));
		$context  = stream_context_create($opts);
		$result = unserialize(file_get_contents('http://www.tyroocentral.com/www/api/v2/xmlrpc/APICall.php', false, $context));
		$json=json_encode($result);
		//var_dump($json);

		//$this->_sessionID = substr($json, 2, 29);
		$this->_sessionID = $result[0];

		$user = $credentials['user'];
		$password = $credentials['password'];

		//webpage uses javascript hex_md5 to encode the password
		$valuesLogin = array(
				new \Oara\Curl\Parameter('username', $user),
				new \Oara\Curl\Parameter('password', $password),
				new \Oara\Curl\Parameter('loginByInterface', 1),
				new \Oara\Curl\Parameter('login', 'Login')
		);

		$dir = COOKIES_BASE_DIR . DIRECTORY_SEPARATOR . $credentials ['cookiesDir'] . DIRECTORY_SEPARATOR . $credentials ['cookiesSubDir'] . DIRECTORY_SEPARATOR;

		if (! \Oara\Utilities::mkdir_recursive ( $dir, 0777 )) {
			throw new Exception ( 'Problem creating folder in Access' );
		}

		$cookies = $dir . $credentials["cookieName"] . '_cookies.txt';
		unlink($cookies);
		$this->_options = array (
				CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:26.0) Gecko/20100101 Firefox/26.0",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FAILONERROR => true,
				CURLOPT_COOKIEJAR => $cookies,
				CURLOPT_COOKIEFILE => $cookies,
				CURLOPT_HTTPAUTH => CURLAUTH_ANY,
				CURLOPT_AUTOREFERER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_HEADER => false,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTPHEADER => array('Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8','Accept-Language: es,en-us;q=0.7,en;q=0.3','Accept-Encoding: gzip, deflate','Connection: keep-alive', 'Cache-Control: max-age=0'),
				CURLOPT_ENCODING => "gzip",
				CURLOPT_VERBOSE => false
		);
		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL, "http://www.tyroocentral.com/www/admin/index.php" );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );

		$dom = new Zend_Dom_Query($html);
		$hidden = $dom->query('input[type="hidden"]');

		foreach ($hidden as $values) {
			$valuesLogin[] = new \Oara\Curl\Parameter($values->getAttribute("name"), $values->getAttribute("value"));
		}

		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL, "http://www.tyroocentral.com/www/admin/index.php" );
		$options [CURLOPT_POST] = true;
		$arg = array ();
		foreach ( $valuesLogin as $parameter ) {
			$arg [] = $parameter->getKey () . '=' . urlencode ( $parameter->getValue () );
		}
		$options [CURLOPT_POSTFIELDS] = implode ( '&', $arg );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		$dom = new Zend_Dom_Query($html);
		$hidden = $dom->query('input[type="hidden"]');

		foreach ($hidden as $values) {
			if ($values->getAttribute("name") == 'affiliateid'){
				$this->_publisherID = $values->getAttribute("value");
			}
		}

		$results = $dom->query('#oaNavigationTabs li div div');
		$finished = false;
		foreach ($results as $result) {
			$linkList = $result->getElementsByTagName('a');
			if ($linkList->length > 0) {
				$attrs = $linkList->item(0)->attributes;

				foreach ($attrs as $attrName => $attrNode) {
					if (!$finished && $attrName = 'href') {
						$parseUrl = trim($attrNode->nodeValue);
						$parts = parse_url($parseUrl);
						parse_str($parts['query'], $query);
						$this->_windowid = $query['windowid'];
						$this->_sessionIDCurl = $query['sessId'];
						$finished = true;
					}
				}
			}
		}
		curl_close ( $rch );

	}

	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$postdata = http_build_query(
				array('class' => 'Publisher',
						'method' => 'getPublisher',
						'val1' => $this->_sessionID,
						'val2' => $this->_publisherID,
						'val3' => ''));
		$opts = array('http' =>array('method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => $postdata));
		$context  = stream_context_create($opts);
		$result = unserialize(file_get_contents('http://www.tyroocentral.com/www/api/v2/xmlrpc/APICall.php', false, $context));
		$connection = $result[0];

		return $connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Interface#getMerchantList()
	 */
	public function getMerchantList() {

		$merchants = Array();

		$obj = Array();
		$obj['cid'] = 1;
		$obj['name'] = 'Tyroo';
		$merchants[] = $obj;

		return $merchants;
	/*
		$date = \DateTime::now();

		$postdata = http_build_query(
				array('class' => 'Publisher',
						'method' => 'getPublisherCampaignStatistics',
						'val1' => $this->_sessionID,
						'val2' => $this->_publisherID,
						'val3' => "1900-01-01",
						'val4' => $date->toString("yyyy-MM-dd"),
						'val5' => '',
						'val6' => ''));
		$opts = array('http' =>array('method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => $postdata));
		$context  = stream_context_create($opts);
		$result = unserialize(file_get_contents('http://www.tyroocentral.com/www/api/v2/xmlrpc/APICall.php', false, $context));
		$json = json_encode($result);

		$jsonArray = json_decode($json, true);

		for ($i=0; $i < count($jsonArray[1]);$i++){
			$obj = Array();
			$obj['cid']  = $jsonArray[1][$i]["campaignid"];
			$obj['name'] = $jsonArray[1][$i]["campaignname"];
			$merchants[] = $obj;
		}
*/
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Interface#getTransactionList($idMerchant, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null) {
		$totalTransactions = array();

		$postdata = http_build_query(
			array('class' => 'Publisher',
				'method' => 'getPublisherDailyStatistics',
				'val1' => $this->_sessionIDCurl,
				'val2' => $this->_publisherID,
				'val3' => $dStartDate->toString("yyyy-MM-dd", 'en_US'),
				'val4' => $dEndDate->toString("yyyy-MM-dd", 'en_US'),
				'val5' => 'Asia/Calcutta',
				'val6' => ''));
		$opts = array('http' =>array('method'  => 'POST',
			'header'  => 'Content-type: application/x-www-form-urlencoded',
			'content' => $postdata));
		$context  = stream_context_create($opts);
		$result = unserialize(file_get_contents('http://www.tyroocentral.com/www/api/v2/xmlrpc/APICall.php', false, $context));
		$json = json_encode($result);
		$transactionsList = json_decode($json, true);
		foreach ($transactionsList[1] as $transactionJson){
			if ($transactionJson["revenue"] != 0){
				$transaction = Array();
				$transaction['merchantId'] = "1";
				$transaction['date'] = $transactionJson["day"];
				$transaction['amount'] = \Oara\Utilities::parseDouble($transactionJson["revenue"]);
				$transaction['commission'] = \Oara\Utilities::parseDouble($transactionJson["revenue"]);
				$transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
				$totalTransactions[] = $transaction;
			}
		}



		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();

		return $paymentHistory;
	}
	/**
	 *
	 * Make the call for this API
	 * @param string $actionVerb
	 */
	private function makeCall($actionVerb, $params = ""){


		return $returnResult;
	}
}
