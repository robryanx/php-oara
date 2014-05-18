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
		$valuesFromExport[] = new Oara_Curl_Parameter('limit', 0);
		
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

		foreach ($exportData->stats->stat as $transaction) {
			if (isset($merchantMap[(string) $transaction->offer])) {
				$obj = array();
				$obj['merchantId'] = $merchantMap[(string) $transaction->offer];
				$date = new Zend_Date((string) $transaction->date_time, "yyyy-MM-dd HH:mm:ss");
				$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
				$obj['status'] = Oara_Utilities::STATUS_CONFIRMED;
				$obj['customId'] = (string) $transaction->sub_id;
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
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();
		
		return $paymentHistory;
	}

}
