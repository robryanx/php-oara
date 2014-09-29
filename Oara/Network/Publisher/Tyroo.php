<?php
/**
 The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
 of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.

 Copyright (C) 2014  Fubra Limited
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
 * @category   Oara_Network_Publisher_ShareASale
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Tyroo extends Oara_Network {

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
	 * @return Oara_Network_Publisher_ShareASale
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
				new Oara_Curl_Parameter('username', $user),
				new Oara_Curl_Parameter('password', $password),
				new Oara_Curl_Parameter('loginByInterface', 1),
				new Oara_Curl_Parameter('login', 'Login')
		);

		$dir = COOKIES_BASE_DIR . DIRECTORY_SEPARATOR . $credentials ['cookiesDir'] . DIRECTORY_SEPARATOR . $credentials ['cookiesSubDir'] . DIRECTORY_SEPARATOR;

		if (! Oara_Utilities::mkdir_recursive ( $dir, 0777 )) {
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
			$valuesLogin[] = new Oara_Curl_Parameter($values->getAttribute("name"), $values->getAttribute("value"));
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
		$connection = false;

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
		//$json=json_encode($result);
		//var_dump($json);

		//$jsonArray = json_decode($json, true);

		//$connection = $jsonArray[0];
		$connection = $result[0];

		return $connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {

		$merchants = Array();

		$obj = Array();
		$obj['cid'] = 1;
		$obj['name'] = 'Tyroo';
		$merchants[] = $obj;

		return $merchants;
	/*
		$date = Zend_Date::now();

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
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($idMerchant, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();




		$valuesFromExport = array(
				new Oara_Curl_Parameter('affiliateid', $this->_publisherID),
				new Oara_Curl_Parameter('statsBreakdown', 'day'),
				new Oara_Curl_Parameter('listorder', 'key'),
				new Oara_Curl_Parameter('orderdirection', 'up'),
				new Oara_Curl_Parameter('day', ''),
				new Oara_Curl_Parameter('setPerPage', '15'),
				new Oara_Curl_Parameter('entity', 'affiliate'),
				new Oara_Curl_Parameter('breakdown', 'history'),
				new Oara_Curl_Parameter('sessId', $this->_sessionIDCurl),
				new Oara_Curl_Parameter('type', 'TRAFFICKER'),
				new Oara_Curl_Parameter('windowid', $this->_windowid),
				new Oara_Curl_Parameter('period_preset', 'specific'),
				new Oara_Curl_Parameter('period_start',$dStartDate->toString("dd MMMM yyyy", 'en_US')),/* new Oara_Curl_Parameter('period_start', "01+June+2014"),*/
				new Oara_Curl_Parameter('period_end', $dEndDate->toString("dd MMMM yyyy", 'en_US')),/* new Oara_Curl_Parameter('period_end', "25+June+2014"),*/
				new Oara_Curl_Parameter('plugin', 'advertiser:statshistory')
		);

		$rch = curl_init ();
		$options = $this->_options;
		$arg = array ();
		foreach ( $valuesFromExport as $parameter ) {
			$arg [] = $parameter->getKey () . '=' . urlencode ( $parameter->getValue () );
		}
		curl_setopt ( $rch, CURLOPT_URL, 'http://www.tyroocentral.com/www/admin/stats.php?'.implode ( '&', $arg ) );

		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );

		$folder = realpath(dirname(__FILE__)).'/../../data/pdf/';
		$my_file = $folder.mt_rand().'.csv';
		$handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
		fwrite($handle, $html);
		fclose($handle);

		$objReader = PHPExcel_IOFactory::createReader('CSV');
		$objReader->setReadDataOnly(true);

		$objPHPExcel = @$objReader->load($my_file);
		$objWorksheet = $objPHPExcel->getActiveSheet();

		$highestRow = $objWorksheet->getHighestRow();
		$highestColumn = $objWorksheet->getHighestColumn();

		for ($row = 5; $row <= $highestRow; ++$row) {

			$day = $objWorksheet->getCellByColumnAndRow(0, $row)->getValue();
			$subConv = $objWorksheet->getCellByColumnAndRow(6, $row)->getValue();
			$pendConv = $objWorksheet->getCellByColumnAndRow(10, $row)->getValue();

			if($subConv!=0){
				$transaction = Array();
				$transaction['merchantId'] = "1";
				$transaction['date'] = $day;
				$transaction['amount'] = Oara_Utilities::parseDouble($subConv);
				$transaction['commission'] = Oara_Utilities::parseDouble($subConv);
				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				$totalTransactions[] = $transaction;
			}

			if($pendConv!=0){
				$transaction = Array();
				$transaction['merchantId'] = "1";
				$transaction['date'] = $day;
				$transaction['amount'] = Oara_Utilities::parseDouble($pendConv);
				$transaction['commission'] = Oara_Utilities::parseDouble($pendConv);
				$transaction['status'] = Oara_Utilities::STATUS_PENDING;
				$totalTransactions[] = $transaction;
			}

		}

		unlink($my_file);


		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
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
