<?php
/**
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Publicidees
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Publicidees extends Oara_Network {
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_Effiliation
	 */
	public function __construct($credentials) {

		$user = $credentials['user'];
		$password = $credentials['password'];
		
		
		$loginUrl = 'http://es.publicideas.com/logmein.php';

		/*
		//getting the hidden value
		$dom = new Zend_Dom_Query(file_get_contents("http://www.publicidees.es/"));
		$results = $dom->query("input[name='h']");

		$hValue = null;
		foreach ($results as $result) {
			$hValue = $result->getAttribute('value');
		}

		*/
		$valuesLogin = array(new Oara_Curl_Parameter('loginAff', $user),
			new Oara_Curl_Parameter('passAff', $password),
			new Oara_Curl_Parameter('userType', 'aff')
		);
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);
		
		$result =  json_decode($this->_client->getConstructResult());
		
		
		
		
		
		$loginUrl = 'http://affilie.publicidees.com/entree_affilies.php';

		$valuesLogin = array(new Oara_Curl_Parameter('login', $result->login),
			new Oara_Curl_Parameter('pass', $result->pass),
			new Oara_Curl_Parameter('submit', 'Ok'),
			new Oara_Curl_Parameter('h', $result->h)
		);
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;

		$urls = array();
		$urls[] = new Oara_Curl_Request('http://affilie.publicidees.com/', array());
		$exportReport = $this->_client->get($urls);

		if (preg_match("/deconnexion\.php/", $exportReport[0], $matches)) {
			$connection = true;
		}
		return $connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array();
		/*
		$valuesFromExport = array();
		$valuesFromExport[] = new Oara_Curl_Parameter('action', "myprograms");

		$urls = array();
		$urls[] = new Oara_Curl_Request('http://affilie.publicidees.com/index.php?', $valuesFromExport);
		$exportReport = $this->_client->get($urls);

		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query(".listPresentationFluxAff a[target='_blank']");

		$href = null;
		foreach ($results as $result) {
			$href = $result->getAttribute('href');
		}

		$content = file_get_contents($href);
		$xml = simplexml_load_string($content, null, LIBXML_NOERROR | LIBXML_NOWARNING);
		foreach ($xml->program as $merchant) {
			$obj = array();
			$obj['cid'] = (string) $merchant->attributes()->id;
			$obj['name'] = (string) $merchant->program_name;
			$obj['url'] = (string) $merchant->program_url;
			$merchants[] = $obj;
		}
		*/
		
		$obj = array();
		$obj['cid'] = 1;
		$obj['name'] = "Publicidees";
		$merchants[] = $obj;
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		$filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2, 'locale' => 'fr'));
		$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
		$dateArraySize = sizeof($dateArray);
		//foreach ($merchantList as $merchantId) {
			$urls = array();
			for ($i = 0; $i < $dateArraySize; $i++) {
				$valuesFromExport = array();
				$valuesFromExport[] = new Oara_Curl_Parameter('action', "moreallstats");
				$valuesFromExport[] = new Oara_Curl_Parameter('progid', 0);
				$valuesFromExport[] = new Oara_Curl_Parameter('dD', $dateArray[$i]->toString("dd/MM/yyyy"));
				$valuesFromExport[] = new Oara_Curl_Parameter('dF', $dateArray[$i]->toString("dd/MM/yyyy"));
				$valuesFromExport[] = new Oara_Curl_Parameter('periode', "0");
				$valuesFromExport[] = new Oara_Curl_Parameter('expAct', "1");
				$valuesFromExport[] = new Oara_Curl_Parameter('tabid', "0");
				$valuesFromExport[] = new Oara_Curl_Parameter('Submit', "Voir");

				$urls[] = new Oara_Curl_Request('http://affilie.publicidees.com/index.php?', $valuesFromExport);
			}
			$exportReport = $this->_client->get($urls);
			$numExport = count($exportReport);
			for ($i = 0; $i < $numExport; $i++) {
				$exportData = str_getcsv(utf8_decode($exportReport[$i]), "\n");
				$num = count($exportData);

				$headerArray = str_getcsv($exportData[0], ";");
				$headerMap = array();
				if (count($headerArray) > 1) {

					for ($j = 0; $j < count($headerArray); $j++) {
						if ($headerArray[$j] == "" && $headerArray[$j - 1] == "Ventes") {
							$headerMap["pendingVentes"] = $j;
						} else
							if ($headerArray[$j] == "" && $headerArray[$j - 1] == "CA") {
								$headerMap["pendingCA"] = $j;
							} else {
								$headerMap[$headerArray[$j]] = $j;
							}
					}
				}

				for ($j = 1; $j < $num; $j++) {

					$transactionExportArray = str_getcsv($exportData[$j], ";");
					if (isset($headerMap["Ventes"]) && isset($headerMap["pendingVentes"])) {
						$confirmedTransactions = (int) $transactionExportArray[$headerMap["Ventes"]];
						$pendingTransactions = (int) $transactionExportArray[$headerMap["pendingVentes"]];

						for ($z = 0; $z < $confirmedTransactions; $z++) {
							$transaction = Array();
							$transaction['merchantId'] = 1;
							$parameters = $urls[$i]->getParameters();
							$transactionDate = new Zend_Date($parameters[2]->getValue(), "dd/MM/yyyy");
							$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
							$transaction['amount'] = ((double) $filter->filter(substr($transactionExportArray[$headerMap["CA"]], 0, -2)) / $confirmedTransactions);
							$transaction['commission'] = ((double) $filter->filter(substr($transactionExportArray[$headerMap["CA"]], 0, -2)) / $confirmedTransactions);
							$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
							$totalTransactions[] = $transaction;
						}

						for ($z = 0; $z < $pendingTransactions; $z++) {
							$transaction = Array();
							$transaction['merchantId'] = 1;
							$transaction['date'] = $dateArray[$i]->toString("yyyy-MM-dd HH:mm:ss");
							$transaction['amount'] = ((double) $filter->filter(substr($transactionExportArray[$headerMap["pendingCA"]], 0, -2)) / $pendingTransactions);
							$transaction['commission'] = ((double) $filter->filter(substr($transactionExportArray[$headerMap["pendingCA"]], 0, -2)) / $pendingTransactions);
							$transaction['status'] = Oara_Utilities::STATUS_PENDING;
							$totalTransactions[] = $transaction;
						}

					}
				}
			}
		//}

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
