<?php
/**
 * Api Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Wow
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_WowTrk extends Oara_Network {
	/**
	 * Soap client.
	 */
	private $_apiClient = null;
	/**
	 * Export client.
	 */
	private $_exportClient = null;

	/**
	 * Credentials Export Parameters
	 * @var array
	 */
	private $_credentialsParameters = array();

	/**
	 * Api key.
	 */
	private $_apiPassword = null;

	/**
	 * page Size.
	 */
	private $_pageSize = 200;

	/**
	 * Constructor.
	 * @param $wow
	 * @return Oara_Network_Publisher_Aw_Api
	 */
	public function __construct($credentials) {
		$user = $credentials['user'];
		$password = $credentials['password'];
		$this->_apiPassword = $credentials['apiPassword'];

		//login through wow website
		$loginUrl = 'http://p.wowtrk.com/';
		$valuesLogin = array(new Oara_Curl_Parameter('data[User][email]', $user),
			new Oara_Curl_Parameter('data[User][password]', $password),
			new Oara_Curl_Parameter('_method', 'POST')
		);

		$this->_exportClient = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
		try {
			$connection = true;
		} catch (Exception $e) {

		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array();

		$valuesFromExport = array();
		$valuesFromExport[] = new Oara_Curl_Parameter('api_key', $this->_apiPassword);

		$urls = array();
		$urls[] = new Oara_Curl_Request('http://p.wowtrk.com/offers/offers.xml?', $valuesFromExport);
		$exportReport = $this->_exportClient->get($urls);

		$exportData = self::loadXml($exportReport[0]);

		foreach ($exportData->offer as $merchant) {
			$obj = array();
			$obj['cid'] = (int) $merchant->id;
			$obj['name'] = (string) $merchant->name;
			$obj['url'] = (string) $merchant->preview_url;
			$merchants[] = $obj;
		}

		return $merchants;
	}
	/**
	 * Convert the string in xml object.
	 * @param $exportReport
	 * @return xml
	 */
	private function loadXml($exportReport = null) {
		$xml = simplexml_load_string($exportReport, null, LIBXML_NOERROR | LIBXML_NOWARNING);
		return $xml;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();

		$valuesFromExport = array();
		$valuesFromExport[] = new Oara_Curl_Parameter('api_key', $this->_apiPassword);
		$valuesFromExport[] = new Oara_Curl_Parameter('start_date', $dStartDate->toString("yyyy-MM-dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('end_date', $dEndDate->toString("yyyy-MM-dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('filter[Stat.offer_id]', implode(",", $merchantList));

		$urls = array();
		$urls[] = new Oara_Curl_Request('http://p.wowtrk.com/stats/lead_report.xml?', $valuesFromExport);
		$exportReport = $this->_exportClient->get($urls);

		$exportData = self::loadXml($exportReport[0]);

		foreach ($exportData->stats as $transaction) {
			if (isset($merchantMap[(string) $transaction->offer])) {
				$obj = array();
				$obj['merchantId'] = $merchantMap[(string) $transaction->offer];
				$date = new Zend_Date((string) $transaction->date_time, "yyyy-MM-dd HH:mm:ss");
				$obj['date'] = $transaction->date;
				$obj['status'] = Oara_Utilities::STATUS_CONFIRMED;
				$obj['customId'] = (string) $transaction->sub_id1;
				$obj['amount'] = Oara_Utilities::parseDouble((string) $transaction->payout);
				$obj['commission'] = Oara_Utilities::parseDouble((string) $transaction->payout);
				if ($obj['amount'] != 0 || $obj['commission'] != 0) {
					$totalTransactions[] = $obj;
				}
			}

		}
		return $totalTransactions;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getOverviewList($merchantId,$dStartDate,$dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalOverview = array();
		$transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);

		$valuesFromExport[] = new Oara_Curl_Parameter('api_key', $this->_apiPassword);
		$valuesFromExport[] = new Oara_Curl_Parameter('start_date', $dStartDate->toString("yyyy-MM-dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('end_date', $dEndDate->toString("yyyy-MM-dd"));
		//$valuesFromExport[] = new Oara_Curl_Parameter('field[]', 'Stat.impressions');
		//$valuesFromExport[] = new Oara_Curl_Parameter('field[]', 'Stat.clicks');
		$valuesFromExport[] = new Oara_Curl_Parameter('group[]', 'Stat.date');
		$valuesFromExport[] = new Oara_Curl_Parameter('filter[Stat.offer_id]', implode(",", $merchantList));

		$urls = array();
		$urls[] = new Oara_Curl_Request('http://p.wowtrk.com/stats/stats.xml?', $valuesFromExport);
		$exportReport = $this->_exportClient->get($urls);

		$exportData = self::loadXml($exportReport[0]);
		if (!isset($exportData->stats)) {
			return $totalOverview;
		}

		foreach ($merchantList as $merchantId) {
			$valuesFromExport = array();
			$valuesFromExport[] = new Oara_Curl_Parameter('api_key', $this->_apiPassword);
			$valuesFromExport[] = new Oara_Curl_Parameter('start_date', $dStartDate->toString("yyyy-MM-dd"));
			$valuesFromExport[] = new Oara_Curl_Parameter('end_date', $dEndDate->toString("yyyy-MM-dd"));
			//$valuesFromExport[] = new Oara_Curl_Parameter('field[]', 'Stat.impressions');
			//$valuesFromExport[] = new Oara_Curl_Parameter('field[]', 'Stat.clicks');
			$valuesFromExport[] = new Oara_Curl_Parameter('group[]', 'Stat.date');
			$valuesFromExport[] = new Oara_Curl_Parameter('filter[Stat.offer_id]', $merchantId);

			$urls = array();
			$urls[] = new Oara_Curl_Request('http://p.wowtrk.com/stats/stats.xml?', $valuesFromExport);
			$exportReport = $this->_exportClient->get($urls);

			$exportData = self::loadXml($exportReport[0]);

			foreach ($exportData->stats as $overview) {

				$overview = Array();
				$overview['merchantId'] = $merchantId;
				$overviewDate = new Zend_Date($overview->date, 'yyyy-MM-dd', 'en');
				$overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");

				$overview['click_number'] = (int) $overview->clicks;
				$overview['impression_number'] = (int) $overview->impressions;
				$overview['transaction_number'] = 0;
				$overview['transaction_confirmed_value'] = 0;
				$overview['transaction_confirmed_commission'] = 0;
				$overview['transaction_pending_value'] = 0;
				$overview['transaction_pending_commission'] = 0;
				$overview['transaction_declined_value'] = 0;
				$overview['transaction_declined_commission'] = 0;
				$overview['transaction_paid_value'] = 0;
				$overview['transaction_paid_commission'] = 0;
				$transactionDateArray = Oara_Utilities::getDayFromArray($merchantId, $transactionArray, $overviewDate);
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

		return $totalOverview;
	}
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();
		/*
		 $urls = array();
		 $urls[] = new Oara_Curl_Request('https://a.wowtrk.com/partners/payment_ecc.html?', array());
		 $content = $this->_exportClient->get($urls);
						
		 $doc = new DOMDocument();
		 libxml_use_internal_errors(true);
		 $doc->validateOnParse = true;
		 $doc->loadHTML($content[0]);
		 $tableList = $doc->getElementsByTagName('table');
		 $registerTable = $tableList->item(6);
		 if ($registerTable != null){
		 $registerLines = $registerTable->childNodes;
		 for ($i = 1;$i < $registerLines->length;$i++) {
		 $registerLine = $registerLines->item($i);
		 $register = $registerLine->childNodes;
						
		 $obj = array();
		 preg_match( '/[0-9]+(,[0-9]{3})*(\.[0-9]{2})?$/', $register->item(2)->nodeValue, $matches);
		 $obj['value'] = Oara_Utilities::parseDouble($matches[0]);
		 $paymentDate = new Zend_Date($register->item(0)->nodeValue, "yyyy-MM-dd");
		 $obj['date'] = $paymentDate->toString("yyyy-MM-dd HH:mm:ss");
		 $obj['pid'] = $paymentDate->toString("yyyyMMdd");
		 $obj['method'] = 'BACS';
						
		 $paymentHistory[] = $obj;
		 }
		 }
		 */
		return $paymentHistory;
	}

}
