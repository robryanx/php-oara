<?php
/**
 The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
 of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.

 Copyright (C) 2014  Carlos Morillo Merino
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
 * @category   Oara_Network_Publisher_Webepartners
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_PrivateInternetAccess extends Oara_Network {
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;

	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_Daisycon
	 */
	public function __construct($credentials) {
		$user = $credentials['user'];
		$password = $credentials['password'];

		$url = "https://www.privateinternetaccess.com/affiliates/sign_in";

		$valuesLogin = array(
		new Oara_Curl_Parameter('affiliate[email]', $user),
		new Oara_Curl_Parameter('affiliate[password]', $password),
		);

		$this->_client = new Oara_Curl_Access($url, $valuesLogin, $credentials);



		$dir = COOKIES_BASE_DIR . DIRECTORY_SEPARATOR . $credentials['cookiesDir'] . DIRECTORY_SEPARATOR . $credentials['cookiesSubDir'] . DIRECTORY_SEPARATOR;

		$cookieName = $credentials["cookieName"];
		$cookies = $dir.$cookieName.'_cookies.txt';

		$defaultOptions = array(
		CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:22.0) Gecko/20100101 Firefox/22.0",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FAILONERROR => true,
		CURLOPT_COOKIEJAR => $cookies,
		CURLOPT_COOKIEFILE => $cookies,
		CURLOPT_AUTOREFERER => true,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_HEADER => false,
		//CURLOPT_VERBOSE => true,
		);

		//Init curl
		$ch = curl_init();
		$options = $defaultOptions;
		$options[CURLOPT_URL] = $url;
		$options[CURLOPT_FOLLOWLOCATION] = true;
		curl_setopt_array($ch, $options);
		$result = curl_exec($ch);
		$err = curl_errno($ch);
		$errmsg = curl_error($ch);
		$info = curl_getinfo($ch);


		$dom = new Zend_Dom_Query($result);
		$results = $dom->query('input[type="hidden"]');
		$hiddenValue = null;
		foreach ($results as $result){
			$name = $result->attributes->getNamedItem("name")->nodeValue;
			if ($name == "authenticity_token"){
				$hiddenValue = $result->attributes->getNamedItem("value")->nodeValue;
			}
		}
		if ($hiddenValue == null){
			throw new Exception("hidden value not found");
		}

		$valuesLogin = array(
		new Oara_Curl_Parameter('authenticity_token', $hiddenValue),
		new Oara_Curl_Parameter('affiliate[email]', $user),
		new Oara_Curl_Parameter('affiliate[password]', $password),
		new Oara_Curl_Parameter('utf8', '&#x2713;'),
		new Oara_Curl_Parameter('commit', 'Login'),
		new Oara_Curl_Parameter('affiliate[remember_me]', '0'),
		);

		// Login form fields
		$return = array();
		foreach ($valuesLogin as $parameter) {
			$return[] = $parameter->getKey().'='.urlencode($parameter->getValue());
		}
		$arg = implode('&', $return);

		//Init curl
		$ch = curl_init();
		$options = $defaultOptions;
		$options[CURLOPT_URL] = $url;
		$options[CURLOPT_FOLLOWLOCATION] = true;
		$options[CURLOPT_POSTFIELDS] = $arg;
		$options[CURLOPT_POST] = true;
		curl_setopt_array($ch, $options);

		$result = curl_exec($ch);

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {

		$connection = true;
		$valuesFormExport = array();
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://www.privateinternetaccess.com/affiliates/affiliate_dashboard', $valuesFormExport);
		$exportReport = $this->_client->get($urls);
		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('.login');

		if (count($results) > 0) {
			$connection = false;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array();

		$obj = array();
		$obj['cid'] = "1";
		$obj['name'] = "Private Internet Access";
		$obj['url'] = "https://www.privateinternetaccess.com/affiliates";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
		$dateArraySize = sizeof($dateArray);


		for ($j = 0; $j < $dateArraySize; $j++) {
			$valuesFormExport = array();
			$valuesFormExport[] = new Oara_Curl_Parameter('date', $dateArray[$j]->toString("yyyy-MM-dd"));
			$valuesFormExport[] = new Oara_Curl_Parameter('period', 'day');

			$urls = array();
			$urls[] = new Oara_Curl_Request('https://www.privateinternetaccess.com/affiliates/affiliate_dashboard?', $valuesFormExport);
			$exportReport = $this->_client->get($urls);
			$dom = new Zend_Dom_Query($exportReport[0]);
			$results = $dom->query('.coupon_code table');
			if (count($results) > 0) {
				$exportData = self::htmlToCsv(self::DOMinnerHTML($results->current()));

				for($z=1; $z < count($exportData)-2; $z++){
					$transactionLineArray = str_getcsv($exportData[$z], ";");
					$numberTransactions = (int)$transactionLineArray[1];
					$commission = preg_replace("/[^0-9\.,]/", "", $transactionLineArray[2]);
					$commission = ((double)$commission)/$numberTransactions;
					for($y=0; $y < $numberTransactions; $y++){
						$transaction = Array();
						$transaction['merchantId'] = "1";
						$transaction['date'] =  $dateArray[$j]->toString("yyyy-MM-dd HH:mm:ss");
						$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
						$transaction['amount'] = $commission;
						$transaction['commission'] = $commission;
						$totalTransactions[] = $transaction;
					}
				}
			}


		}

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
	 * Function that Convert from a table to Csv
	 * @param unknown_type $html
	 */
	private function htmlToCsv($html) {
		$html = str_replace(array("\t", "\r", "\n"), "", $html);
		$csv = "";
		$dom = new Zend_Dom_Query($html);
		$results = $dom->query('tr');
		$count = count($results); // get number of matches: 4
		foreach ($results as $result) {
			$tdList = $result->childNodes;
			$tdNumber = $tdList->length;
			if ($tdNumber > 0) {
				for ($i = 0; $i < $tdNumber; $i++) {
					$value = $tdList->item($i)->nodeValue;
					if ($i != $tdNumber - 1) {
						$csv .= trim($value).";";
					} else {
						$csv .= trim($value);
					}
				}
				$csv .= "\n";
			}
		}
		$exportData = str_getcsv($csv, "\n");
		return $exportData;
	}
	/**
	 *
	 * Function that returns the innet HTML code
	 * @param unknown_type $element
	 */
	private function DOMinnerHTML($element) {
		$innerHTML = "";
		$children = $element->childNodes;
		foreach ($children as $child) {
			$tmp_dom = new DOMDocument();
			$tmp_dom->appendChild($tmp_dom->importNode($child, true));
			$innerHTML .= trim($tmp_dom->saveHTML());
		}
		return $innerHTML;
	}


}