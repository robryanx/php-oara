<?php
require_once "GoogleApiClient/src/apiClient.php";
require_once "GoogleApiClient/src/contrib/apiAnalyticsService.php";
/**
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Taxis
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Taxis extends Oara_Network {

	/**
	 * Adsense Client
	 * @var unknown_type
	 */
	private $_analytics = null;

	/**
	 *  Client
	 * @var unknown_type
	 */
	private $_client = null;

	/**
	 *  Payments
	 * @var unknown_type
	 */
	private $_payments = null;
	/**
	 * Airport Map with analytics ID
	 */
	private $_airportMap = array(
		"Aberdeen"		 => 13961381,
		"Belfast"		 => 13961491,
		"Birmingham"	 => 13961592,
		"Blackpool"		 => 13961638,
		"Bristol"		 => 13961669,
		"Cardiff"		 => 13961682,
		"East Midlands"	 => 13961723,
		"Edinburgh"		 => 13961757,
		"Gatwick"		 => 13961766,
		"Glasgow"		 => 13961804,
		"Heathrow"		 => 13961843,
		"Humberside"	 => 13961862,
		"Leeds"			 => 13961886,
		"Liverpool"		 => 13962012,
		"London City"	 => 13971469,
		"Luton"			 => 13962098,
		"Manchester"	 => 13962139,
		"Newcastle"		 => 13962162,
		"Prestwick"		 => 13962187,
		"Doncaster"		 => 13962208,
		"Southampton"	 => 13962231,
		"Stansted"		 => 13962255,
		"Teesside"		 => 13962279,
		"Various"		 => 1
	);
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_Taxis
	 */
	public function __construct($credentials) {
		$client = new apiClient();
		$client->setApplicationName("AffJet");
		$client->setClientId($credentials['clientId']);
		$client->setClientSecret($credentials['clientSecret']);
		$client->setAccessToken($credentials['oauth2']);
		$client->setAccessType('offline');
		$this->_client = $client;
		$this->_analytics = new apiAnalyticsService($client);

		$clientOptions = array('url' => $credentials["paymentsUrl"], 'user' => $credentials["paymentsUser"], 'auth' => $credentials["paymentsAuth"]);

		//Setting up the client for the payments
		$this->_payments = new Fubra_Service_Payments_Client($clientOptions);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
		if ($this->_client->getAccessToken()) {
			$connection = true;
		}
		return $connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = Array();

		$obj = array();
		$obj['cid'] = 1;
		$obj['name'] = "Various";
		$obj['url'] = "";
		$merchants[] = $obj;

		$profiles = $this->_analytics->management_profiles->listManagementProfiles("~all", "~all");
		foreach ($profiles["items"] as $profile) {
			if (preg_match("/-airport-guide\.co\.uk/", $profile["name"])) {
				$obj = array();
				$obj['cid'] = $profile["id"];
				$obj['name'] = array_search($profile["id"], $this->_airportMap);
				$obj['url'] = $profile["websiteUrl"];
				$merchants[] = $obj;
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
		$now = new Zend_Date();
		$now->setHour(23);
		$now->setMinute(59);
		$now->setSecond(59);
		
		
		$dateArray = Oara_Utilities::monthsOfDifference($dStartDate, $now);
		for ($i = 0; $i < count($dateArray); $i++) {
			$monthStartDate = clone $dateArray[$i];
			$monthEndDate = null;

			if ($i != count($dateArray) - 1) {
				$monthEndDate = clone $dateArray[$i];
				$monthEndDate->setDay(1);
				$monthEndDate->addMonth(1);
				$monthEndDate->subDay(1);
			} else {
				$monthEndDate = $now;
			}
			$monthEndDate->setHour(23);
			$monthEndDate->setMinute(59);
			$monthEndDate->setSecond(59);
			$monthEndDate->addDay(1);

			
			//echo "from ".$monthStartDate->toString("yyyy-MM-dd")." to ".$monthEndDate->toString("yyyy-MM-dd")."\n";
			$response = $this->_payments->subscriptionList(array('since' => $monthStartDate->toString("yyyy-MM-dd"), 'to' => $monthEndDate->toString("yyyy-MM-dd"), 'state' => 'closed'));
			$totalTransactions = array_merge($totalTransactions, self::getTransactionFromSubscription($response, $merchantList, $dStartDate, $dEndDate));

			$response = $this->_payments->subscriptionList(array('since' => $monthStartDate->toString("yyyy-MM-dd"), 'to' => $monthEndDate->toString("yyyy-MM-dd"), 'state' => 'open'));
			$totalTransactions = array_merge($totalTransactions, self::getTransactionFromSubscription($response, $merchantList, $dStartDate, $dEndDate));

		}

		return $totalTransactions;
	}

	private function getTransactionFromSubscription($response, $merchantList, $dStartDate, $dEndDate) {
		$totalTransactions = array();
		foreach ($response['subscriptions'] as $subscription) {

			if ($subscription["reference"] != null) {

				$invoiceList = $this->_payments->invoiceList(array('reference' => $subscription["reference"], 'since' => $dStartDate->toString("yyyy-MM-dd")));

				foreach ($invoiceList["invoices"] as $invoice) {

					$invoiceDate = new Zend_Date(date('Y-m-d h:m:s', $invoice["created"]), "yyyy-MM-dd HH:mm:ss");
					if ($invoiceDate->compare($dStartDate) >= 0 && $invoiceDate->compare($dEndDate) <= 0) {

						$merchantId = null;
						if (preg_match("/various/", $invoice["name"])) {
							$merchantId = $this->_airportMap["Various"];
						} else {
							$nameArray = explode("-", $invoice["name"]);
							if (isset($nameArray[1])) {
								$nameAirport = trim($nameArray[1]);
								if (isset($this->_airportMap[$nameAirport])) {
									$merchantId = $this->_airportMap[$nameAirport];
								}
							}
						}
						if (in_array($merchantId, $merchantList)) {
							$transaction = Array();
							$transaction['merchantId'] = $merchantId;

							$transaction['date'] = $invoiceDate->toString("yyyy-MM-dd HH:mm:ss");
							$transaction['unique_id'] = (int) $invoice["id"];

							if ($subscription["reference"] != null) {
								$transaction['custom_id'] = $subscription["reference"];
							}

							if ($invoice["state"] == "paid") {
								$transaction['status'] = Oara_Utilities::STATUS_PAID;
							} else
								if ($invoice["state"] == "pending") {
									$transaction['status'] = Oara_Utilities::STATUS_PENDING;
								} else {
									$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
								}
							$transaction['amount'] = Oara_Utilities::parseDouble($invoice["amountNet"]);
							$transaction['commission'] = Oara_Utilities::parseDouble($invoice["amountNet"]);
							$totalTransactions[] = $transaction;
						} else {
							//echo "Merchant not found\n\n";
						}

					}
				}
			} else {
				//echo "No Reference\n\n";
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

		$analyticsData = array();
		foreach ($merchantList as $merchantId) {
			if ($merchantId != 1) {
				$ids = "ga:$merchantId";
				$start_date = $dStartDate->toString("yyyy-MM-dd");
				$end_date = $dEndDate->toString("yyyy-MM-dd");
				$metrics = "ga:visits,ga:pageviews";
				$dimensions = "ga:date,ga:pagePath";
				$filters = "ga:pagePath=@taxi-transfer.html";
				$optParams = array('dimensions' => $dimensions, 'filters' => $filters);
				$data = $this->_analytics->data_ga->get($ids, $start_date, $end_date, $metrics, $optParams);
				foreach ($data["rows"] as $row) {
					if (!isset($analyticsData[$row[0]][$merchantId])) {
						$analyticsData[$row[0]][$merchantId]["impressions"] = 0;
						$analyticsData[$row[0]][$merchantId]["clicks"] = 0;
					}
					if ($row[1] == "/taxi-transfer.html") {
						$analyticsData[$row[0]][$merchantId]["impressions"] += $row[3];
					} else {
						$analyticsData[$row[0]][$merchantId]["clicks"] += $row[3];
					}
				}
			}
		}

		foreach ($analyticsData as $dataDate => $merchatList) {
			foreach ($merchatList as $merchantId => $merchantData) {
				if (in_array($merchantId, $merchantList)) {

					$overview = Array();
					$overview['merchantId'] = $merchantId;
					$overviewDate = new Zend_Date($dataDate, 'yyyyMMdd');
					$overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");

					$overview['click_number'] = (int) $merchantData["clicks"];
					$overview['impression_number'] = (int) $merchantData["impressions"];
					$overview['transaction_number'] = 0;
					$overview['transaction_confirmed_value'] = 0;
					$overview['transaction_confirmed_commission'] = 0;
					$overview['transaction_pending_value'] = 0;
					$overview['transaction_pending_commission'] = 0;
					$overview['transaction_declined_value'] = 0;
					$overview['transaction_declined_commission'] = 0;
					$overview['transaction_paid_value'] = 0;
					$overview['transaction_paid_commission'] = 0;
					$transactionDateArray = Oara_Utilities::getDayFromArray($overview['merchantId'], $transactionArray, $overviewDate, true);
					foreach ($transactionDateArray as $transaction) {
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
					if (Oara_Utilities::checkRegister($overview)) {
						$overviewArray[] = $overview;
					}
				}
			}
		}

		foreach ($transactionArray as $merchantId => $merchantTransaction) {
			foreach ($merchantTransaction as $date => $transactionList) {
				if (in_array($merchantId, $merchantList)) {
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
		}

		return $overviewArray;
	}
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();

		return $paymentHistory;
	}
}
