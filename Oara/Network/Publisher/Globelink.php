<?php
/**
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Globelink
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Globelink extends Oara_Network {
	
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
		
		$loginUrl = "http://affiliate.globelink.co.uk/form/CMSFormsUsersSignin?param=";

		$valuesLogin = array(new Oara_Curl_Parameter('user_login', $user),
			new Oara_Curl_Parameter('user_password', $password),
			new Oara_Curl_Parameter('form-submit', true),
			new Oara_Curl_Parameter('form-submit-button', true),
		);

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = true;

		$valuesFormExport = array();
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://affiliate.globelink.co.uk/home/', $valuesFormExport);
		$exportReport = $this->_client->get($urls);
		if (!preg_match("/\/form\/CMSFormsUsersLogout/", $exportReport[0], $matches)) {
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
		$obj['name'] = "Globelink";
		$obj['url'] = "";
		$obj['cid'] = 1;
		$merchants[] = $obj;
		
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		
		
		$urls = array();
		$valuesFormExport = array();
		$urls[] = new Oara_Curl_Request('http://affiliate.globelink.co.uk/home/', $valuesFormExport);
		$exportReport = $this->_client->get($urls);
		$commmisionUrl = "";
		if (preg_match("/\/profile\/(.*)\/sales/", $exportReport[0], $matches)) {
			$commmisionUrl="http://affiliate.globelink.co.uk/profile/".$matches[1]."/sales/?";
		}
		
		$auxTransactionList = array();
		$page = 1;
		$exit = false;
		while (!$exit){
			$urls = array();
			$valuesFormExport = array();
			$valuesFormExport[] = new Oara_Curl_Parameter('page', $page);
			$valuesFormExport[] = new Oara_Curl_Parameter('count', 20);
			$urls[] = new Oara_Curl_Request($commmisionUrl, $valuesFormExport);
			$exportReport = $this->_client->get($urls);
			var_dump($exportReport[0]);
			$dom = new Zend_Dom_Query($exportReport[0]);
			$results = $dom->query('.affs-list-r');
			
			foreach ($results as $line){
				$auxTransaction = array();
				foreach($line->childNodes as $attribute){
					$value = $attribute->nodeValue;
					if ($value != null){
						$auxTransaction[] = $attribute->nodeValue;
					}
				}
				$auxTransactionList[] = $auxTransaction;
			}
			
			if (preg_match("/<li><span>&raquo;<\/span><\/li>/",$exportReport[0])){
				$exit = true;
			}
			$page++;
		}
		
		foreach  ($auxTransactionList as $auxTransaction) {
			$transactionDate = new Zend_Date($auxTransaction[1], "yyyy-MM-dd HH:mm:ss");
			
			if ($dStartDate->compare($transactionDate) <= 0 && $dEndDate->compare($transactionDate) >= 0) {
				$transaction = Array();
				$transaction['merchantId'] = 1;
				
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
				$transaction['unique_id'] = $auxTransaction[3];
				

				if (strstr($auxTransaction[12], 'No')) {
					$transaction['status'] = Oara_Utilities::STATUS_PENDING;
				} else
					if (strstr($auxTransaction[12], 'Yes')) {
						$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					}
					
				$transaction['amount'] = $auxTransaction[6];
				$transaction['commission'] = $auxTransaction[8];
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
