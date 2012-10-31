<?php
/**
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_NetAfiliation
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_NetAffiliation extends Oara_Network {
	/**
	 * Server Number
	 * @var array
	 */
	private $_serverNumber = null;
	/**
	 * Export Credentials
	 * @var array
	 */
	private $_credentials = null;

	/**
	 * Client
	 * @var Oara_Curl_Access
	 */
	private $_client = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return null
	 */
	public function __construct($credentials) {

		$this->_credentials = $credentials;

		$user = $credentials['user'];
		$password = $credentials['password'];

		$loginUrl = "http://www.netaffiliation.com/charge.php?";

		$valuesLogin = array(new Oara_Curl_Parameter('identif', $user),
			new Oara_Curl_Parameter('mdp', $password),
			new Oara_Curl_Parameter('q', 'dif')
		);

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

		$cookieLocalion = realpath(dirname(__FILE__)).'/../data/curl/'.$credentials['cookiesDir'].'/'.$credentials['cookiesSubDir'].'/'.$credentials["cookieName"].'_cookies.txt';
		;
		$cookieContent = file_get_contents($cookieLocalion);
		$serverNumber = null;
		if (preg_match("/www(.)\.netaffiliation\.com/", $cookieContent, $matches)) {
			$this->_serverNumber = $matches[1];
		}

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = true;

		$valuesFormExport = array();
		// $valuesFormExport[] = new Oara_Curl_Parameter('datefrom', $startDate);
		//  $valuesFormExport[] = new Oara_Curl_Parameter('dateto', $endDate);
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://www'.$this->_serverNumber.'.netaffiliation.com/aff/index.php', $valuesFormExport);
		$exportReport = $this->_client->get($urls);
		if (!preg_match("/logout\.php/", $exportReport[0], $matches)) {
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

		$valuesFormExport = array();
		$valuesFormExport[] = new Oara_Curl_Parameter('rechmc', '');
		$valuesFormExport[] = new Oara_Curl_Parameter('rechdate', '0');
		$valuesFormExport[] = new Oara_Curl_Parameter('rechercher', '1');
		$valuesFormExport[] = new Oara_Curl_Parameter('nbr', '20');
		$valuesFormExport[] = new Oara_Curl_Parameter('tri', 'd');
		$valuesFormExport[] = new Oara_Curl_Parameter('croi', '2');
		$valuesFormExport[] = new Oara_Curl_Parameter('ind', '0');
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://www'.$this->_serverNumber.'.netaffiliation.com/aff/install.php?', $valuesFormExport);

		while (!empty($urls)) {
			$exportReport = $this->_client->post($urls);
			$dom = new Zend_Dom_Query($exportReport[0]);

			$results = $dom->query('.lignenormale');
			foreach ($results as $result) {
				$obj = array();
				$nameNode = $result->childNodes->item(4);
				$obj['name'] = $nameNode->nodeValue;

				$urlNode = $result->childNodes->item(16)->childNodes->item(0);
				$merchantUrl = $urlNode->getAttribute('href');
				$urlArray = parse_url($merchantUrl);
				$paramsList = explode("&", $urlArray["query"]);
				$paramArray = array();
				foreach ($paramsList as $paramString) {
					$paramValue = explode("=", $paramString);
					$paramArray[$paramValue[0]] = $paramValue[1];
				}
				$obj['url'] = "";
				$obj['cid'] = $paramArray["prog"];
				$merchants[] = $obj;
			}

			$urls = array();
			$results = $dom->query('a .lien');
			foreach ($results as $result) {
				$url = $result->getAttribute('href');
				$value = $result->nodeValue;
				if ($value != ">>" && strstr($value, ">")) {
					$urls[] = new Oara_Curl_Request('http://www'.$this->_serverNumber.'.netaffiliation.com/aff/'.$url, array());
				}
			}
		}

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		$filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2, 'locale' => 'fr'));
		$valuesFormExport = array();
		$valuesFormExport[] = new Oara_Curl_Parameter('per', '0');

		$valuesFormExport[] = new Oara_Curl_Parameter('duj', $dStartDate->get(Zend_Date::DAY_SHORT));
		$valuesFormExport[] = new Oara_Curl_Parameter('dum', $dStartDate->get(Zend_Date::MONTH_SHORT));
		$valuesFormExport[] = new Oara_Curl_Parameter('dua', $dStartDate->get(Zend_Date::YEAR));

		$valuesFormExport[] = new Oara_Curl_Parameter('auj', $dEndDate->get(Zend_Date::DAY_SHORT));
		$valuesFormExport[] = new Oara_Curl_Parameter('aum', $dEndDate->get(Zend_Date::MONTH_SHORT));
		$valuesFormExport[] = new Oara_Curl_Parameter('aua', $dEndDate->get(Zend_Date::YEAR));

		$valuesFormExport[] = new Oara_Curl_Parameter('progs[]', '-1');
		$valuesFormExport[] = new Oara_Curl_Parameter('objsel', '0');
		$valuesFormExport[] = new Oara_Curl_Parameter('dim', '2');
		$valuesFormExport[] = new Oara_Curl_Parameter('rappel', '5');
		$valuesFormExport[] = new Oara_Curl_Parameter('etat', '3');
		$valuesFormExport[] = new Oara_Curl_Parameter('telecharge', '');
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://www'.$this->_serverNumber.'.netaffiliation.com/aff/stats.php?', $valuesFormExport);
		$exportReport = $this->_client->get($urls);

		//sales
		$exportData = str_getcsv($exportReport[0], "\n");
		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], ";");
			if (in_array((int) $merchantMap[$transactionExportArray[0]], $merchantList)) {
				$transaction = Array();
				$merchantId = (int) $merchantMap[$transactionExportArray[0]];
				$transaction['merchantId'] = $merchantId;
				$transactionDate = new Zend_Date($transactionExportArray[3], "dd/MM/yyyy HH:mm:ss");
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

				if ($transactionExportArray[5] != null) {
					$transaction['custom_id'] = $transactionExportArray[5];
				}

				if (strstr($transactionExportArray[4], 'validé')) {
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				} else
					if (strstr($transactionExportArray[4], 'refusé')) {
						$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
					} else {
						$transaction['status'] = Oara_Utilities::STATUS_PENDING;
					}
				$transaction['amount'] = $filter->filter($transactionExportArray[9]);
				$transaction['commission'] = $filter->filter($transactionExportArray[9]);
				$totalTransactions[] = $transaction;
			}
		}
		//forms
		$valuesFormExport = array();
		$valuesFormExport[] = new Oara_Curl_Parameter('per', '0');

		$valuesFormExport[] = new Oara_Curl_Parameter('duj', $dStartDate->get(Zend_Date::DAY_SHORT));
		$valuesFormExport[] = new Oara_Curl_Parameter('dum', $dStartDate->get(Zend_Date::MONTH_SHORT));
		$valuesFormExport[] = new Oara_Curl_Parameter('dua', $dStartDate->get(Zend_Date::YEAR));

		$valuesFormExport[] = new Oara_Curl_Parameter('auj', $dEndDate->get(Zend_Date::DAY_SHORT));
		$valuesFormExport[] = new Oara_Curl_Parameter('aum', $dEndDate->get(Zend_Date::MONTH_SHORT));
		$valuesFormExport[] = new Oara_Curl_Parameter('aua', $dEndDate->get(Zend_Date::YEAR));

		$valuesFormExport[] = new Oara_Curl_Parameter('progs[]', '-1');
		$valuesFormExport[] = new Oara_Curl_Parameter('objsel', '0');
		$valuesFormExport[] = new Oara_Curl_Parameter('dim', '2');
		$valuesFormExport[] = new Oara_Curl_Parameter('rappel', '4');
		$valuesFormExport[] = new Oara_Curl_Parameter('etat', '3');
		$valuesFormExport[] = new Oara_Curl_Parameter('telecharge', '');
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://www'.$this->_serverNumber.'.netaffiliation.com/aff/stats.php?', $valuesFormExport);
		$exportReport = $this->_client->get($urls);

		$exportData = str_getcsv($exportReport[0], "\n");
		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], ";");
			if (in_array((int) $merchantMap[$transactionExportArray[0]], $merchantList)) {
				$transaction = Array();
				$merchantId = (int) $merchantMap[$transactionExportArray[0]];
				$transaction['merchantId'] = $merchantId;
				$transactionDate = new Zend_Date($transactionExportArray[3], "dd/MM/yyyy HH:mm:ss");
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

				if ($transactionExportArray[5] != null) {
					$transaction['custom_id'] = $transactionExportArray[5];
				}

				if (strstr($transactionExportArray[4], 'validé')) {
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				} else
					if (strstr($transactionExportArray[4], 'refusé')) {
						$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
					} else {
						$transaction['status'] = Oara_Utilities::STATUS_PENDING;
					}
				$transaction['amount'] = $filter->filter($transactionExportArray[9]);
				$transaction['commission'] = $filter->filter($transactionExportArray[9]);
				$totalTransactions[] = $transaction;
			}
		}
		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getOverviewList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$overviewArray = array();

		$transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);

		foreach ($transactionArray as $merchantId => $merchantTransaction) {
			foreach ($merchantTransaction as $date => $transactionList) {

				$overview = Array();

				$overview['merchantId'] = $merchantId;
				$overviewDate = new Zend_Date($date, "yyyy-MM-dd");
				$overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
				$overview['click_number'] = 0;
				$overview['impression_number'] = 0;
				$overview['transaction_number'] = 0;
				$overview['transaction_confirmed_value'] = 0;
				$overview['transaction_confirmed_commission'] = 0;
				$overview['transaction_pending_value'] = 0;
				$overview['transaction_pending_commission'] = 0;
				$overview['transaction_declined_value'] = 0;
				$overview['transaction_declined_commission'] = 0;
				$overview['transaction_paid_value'] = 0;
				$overview['transaction_paid_commission'] = 0;
				foreach ($transactionList as $transaction) {
					$overview['transaction_number']++;
					if ($transaction['status'] == Oara_Utilities::STATUS_CONFIRMED) {
						$overview['transaction_confirmed_value'] += $transaction['amount'];
						$overview['transaction_confirmed_commission'] += $transaction['commission'];
					} else
						if ($transaction['status'] == Oara_Utilities::STATUS_PENDING) {
							$overview['transaction_pending_value'] += $transaction['amount'];
							$overview['transaction_pending_commission'] += $transaction['commission'];
						} else
							if ($transaction['status'] == Oara_Utilities::STATUS_DECLINED) {
								$overview['transaction_declined_value'] += $transaction['amount'];
								$overview['transaction_declined_commission'] += $transaction['commission'];
							} else
								if ($transaction['status'] == Oara_Utilities::STATUS_PAID) {
									$overview['transaction_paid_value'] += $transaction['amount'];
									$overview['transaction_paid_commission'] += $transaction['commission'];
								}
				}
				$overviewArray[] = $overview;
			}
		}
		return $overviewArray;
	}
}
