<?php
/**
 * Api Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Wg
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_WebGains extends Oara_Network {
	/**
	 * Soap client.
	 */
	private $_soapClient = null;
	/**
	 * Web client.
	 */
	private $_webClient = null;

	/**
	 * Server.
	 */
	private $_server = null;
	/**
	 * Export Merchant Parameters
	 * @var array
	 */
	private $_exportMerchantParameters = null;
	/**
	 * Export Transaction Parameters
	 * @var array
	 */
	private $_exportTransactionParameters = null;
	/**
	 * Export Overview Parameters
	 * @var array
	 */
	private $_exportOverviewParameters = null;
	/**
	 * Converter configuration for the merchants.
	 * @var array
	 */
	private $_merchantConverterConfiguration = Array('programID'			 => 'cid',
		'programName'		 => 'name',
		'programURL'		 => 'url',
		'programDescription' => 'description'
		);
		/**
		 * Converter configuration for the transactions.
		 * @var array
		 */
		private $_transactionConverterConfiguration = Array('status'	 => 'status',
		'saleValue'	 => 'amount',
		'commission' => 'commission',
		'date'		 => 'date',
		'merchantId' => 'merchantId',
		'custom_id'	 => 'clickRef',
		);
		/**
		 * Array with the id from the campaigns
		 * @var array
		 */
		private $_campaignMap = array();
		/**
		 * Constructor.
		 * @param $webgains
		 * @return Oara_Network_Publisher_Wg_Api
		 */
		public function __construct($credentials) {
			$user = $credentials['user'];
			$password = $credentials['password'];

			$wsdlUrl = 'http://ws.webgains.com/aws.php';
			//Setting the client.
			$this->_soapClient = new Zend_Soap_Client($wsdlUrl, array('login'			 => $user,
			'encoding'		 => 'UTF-8',
			'password'		 => $password,
			'compression'	 => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
			'soap_version'	 => SOAP_1_1));

			$serverArray = array();
			$serverArray["uk"] = 'www.webgains.com';
			$serverArray["fr"] = 'www.webgains.fr';
			$serverArray["us"] = 'us.webgains.com';
			$serverArray["de"] = 'www.webgains.de';
			$serverArray["fr"] = 'www.webgains.fr';
			$serverArray["nl"] = 'www.webgains.nl';
			$serverArray["dk"] = 'www.webgains.dk';
			$serverArray["se"] = 'www.webgains.se';
			$serverArray["es"] = 'www.webgains.es';
			$serverArray["ie"] = 'www.webgains.ie';
			$serverArray["it"] = 'www.webgains.it';

			$loginUrlArray = array();
			$loginUrlArray["uk"] = 'https://www.webgains.com/loginform.html?action=login';
			$loginUrlArray["fr"] = 'https://www.webgains.fr/loginform.html?action=login';
			$loginUrlArray["us"] = 'https://us.webgains.com/loginform.html?action=login';
			$loginUrlArray["de"] = 'https://www.webgains.de/loginform.html?action=login';
			$loginUrlArray["fr"] = 'https://www.webgains.fr/loginform.html?action=login';
			$loginUrlArray["nl"] = 'https://www.webgains.nl/loginform.html?action=login';
			$loginUrlArray["dk"] = 'https://www.webgains.dk/loginform.html?action=login';
			$loginUrlArray["se"] = 'https://www.webgains.se/loginform.html?action=login';
			$loginUrlArray["es"] = 'https://www.webgains.es/loginform.html?action=login';
			$loginUrlArray["ie"] = 'https://www.webgains.ie/loginform.html?action=login';
			$loginUrlArray["it"] = 'https://www.webgains.it/loginform.html?action=login';

			$valuesLogin = array(
			new Oara_Curl_Parameter('user_type', 'affiliateuser'),
			new Oara_Curl_Parameter('username', $user),
			new Oara_Curl_Parameter('password', $password)
			);

			foreach ($loginUrlArray as $country => $url){
				$this->_webClient = new Oara_Curl_Access($url, $valuesLogin, $credentials);
				if (preg_match("/program\/list\/index\/joined\/joined/", $this->_webClient->getConstructResult())) {
					$this->_server = $serverArray[$country];
					
					$this->_campaignMap = self::getCampaignMap($this->_webClient->getConstructResult());
					break;
				}
			}




			$this->_exportMerchantParameters = array('username'	 => $user,
			'password'	 => $password
			);
			$this->_exportTransactionParameters = array('username'	 => $user,
			'password'	 => $password
			);
			$this->_exportOverviewParameters = array('username'	 => $user,
			'password'	 => $password
			);

		}
		/**
		 * Check the connection
		 */
		public function checkConnection() {
			$connection = false;
			if ($this->_server != null){
				$connection = true;
			}
			return $connection;
		}
		/**
		 * (non-PHPdoc)
		 * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
		 */
		public function getMerchantList() {
			$merchantList = Array();
			foreach ($this->_campaignMap as $campaignKey => $campaignValue) {
				$merchants = $this->_soapClient->getProgramsWithMembershipStatus($this->_exportMerchantParameters['username'], $this->_exportMerchantParameters['password'], $campaignKey);
				foreach ($merchants as $merchant) {
					if ($merchant->programMembershipStatusName == 'Live' || $merchant->programMembershipStatusName == 'Joined') {
						$merchantList[$merchant->programID] = $merchant;
					}

				}
			}
			$merchantList = Oara_Utilities::soapConverter($merchantList, $this->_merchantConverterConfiguration);

			return $merchantList;
		}
		/**
		 * (non-PHPdoc)
		 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
		 */
		public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
			$totalTransactions = Array();

			$dStartDate = clone $dStartDate;
			$dStartDate->setHour("00");
			$dStartDate->setMinute("00");
			$dStartDate->setSecond("00");
			$dEndDate = clone $dEndDate;
			$dEndDate->setHour("23");
			$dEndDate->setMinute("59");
			$dEndDate->setSecond("59");

			foreach ($this->_campaignMap as $campaignKey => $campaignValue) {
				try{
					$transactionList = $this->_soapClient->getDetailedEarnings($dStartDate->getIso(), $dEndDate->getIso(), $campaignKey, $this->_exportTransactionParameters['username'], $this->_exportTransactionParameters['password']);
				} catch(Exception $e){
					if (preg_match("/60 requests/", $e->getMessage())){
						sleep(60);
						$transactionList = $this->_soapClient->getDetailedEarnings($dStartDate->getIso(), $dEndDate->getIso(), $campaignKey, $this->_exportTransactionParameters['username'], $this->_exportTransactionParameters['password']);
					}
				}
				foreach ($transactionList as $transactionObject) {
					if (in_array($transactionObject->programID, $merchantList)) {
							
						$transaction = array();
						$transaction['merchantId'] = $transactionObject->programID;
						$transactionDate = new Zend_Date($transactionObject->date, "yyyy-MM-ddTHH:mm:ss");
						$transaction["date"] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");

						if ($transactionObject->clickRef != null) {
							$transaction['custom_id'] = $transactionObject->clickRef;
						}

						$transaction['status'] = null;
						$transaction['amount'] = $transactionObject->saleValue;
						$transaction['commission'] = $transactionObject->commission;

						if ($transactionObject->status == 'confirmed') {
							$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
						} else
						if ($transactionObject->status == 'delayed') {
							$transaction['status'] = Oara_Utilities::STATUS_PENDING;
						} else
						if ($transactionObject->status == 'cancelled') {
							$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
						} else {
							throw new Exception('Error in the transaction status');
						}
						$totalTransactions[] = $transaction;
					}
				}

			}
			return $totalTransactions;
		}
		/**
		 * (non-PHPdoc)
		 * @see library/Oara/Network/Oara_Network_Publisher_Base#getOverviewList($merchantId,$dStartDate,$dEndDate)
		 */
		public function getOverviewList($transactionList = array(), $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
			$totalOverviews = Array();
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
					$totalOverviews[] = $overview;
				}
			}

			return $totalOverviews;
		}

		/**
		 * Get the campaings identifiers and returns it in an array.
		 * @return array
		 */
		private function getCampaignMap($html) {
			$campaingMap = array();
			
			$dom = new Zend_Dom_Query($html);
			$results = $dom->query('#affiliateCampaignSelector');
			$merchantLines = $results->current()->childNodes;
			for ($i = 0; $i < $merchantLines->length; $i++) {
				$cid = $merchantLines->item($i)->attributes->getNamedItem("value")->nodeValue;
				$campaingMap[$cid] = $merchantLines->item($i)->nodeValue;
			}
			return $campaingMap;
		}

		/**
		 * (non-PHPdoc)
		 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
		 */
		public function getPaymentHistory() {
			$paymentHistory = array();
			
			$urls = array();

			$urls[] = new Oara_Curl_Request("https://{$this->_server}/affiliates/payment.html", array());
			$exportReport = $this->_webClient->get($urls);

			/*** load the html into the object ***/
			$doc = new DOMDocument();
			libxml_use_internal_errors(true);
			$doc->validateOnParse = true;
			$doc->loadHTML($exportReport[0]);
			$tableList = $doc->getElementsByTagName('table');
			$i = 0;
			$enc = false;
			while ($i < $tableList->length && !$enc) {

				$registerTable = $tableList->item($i);
				if ($registerTable->getAttribute('class') == 'withgrid') {
					$enc = true;
				}
				$i++;
			}
			if (!$enc) {
				throw new Exception('Fail getting the payment History');
			}

			$registerLines = $registerTable->childNodes;
			for ($i = 2; $i < $registerLines->length ; $i++) {

				$obj = array();

				$linkList = $registerLines->item($i)->getElementsByTagName('a');
				$url = $linkList->item(1)->attributes->getNamedItem("href")->nodeValue;
				$parseUrl = parse_url(trim($url));
				$parameters = explode('&', $parseUrl['query']);
				foreach ($parameters as $parameter) {
					$parameterValue = explode('=', $parameter);
					if ($parameterValue[0] == 'payment' || $parameterValue[0] == 'creditnoteid') {
						$obj['pid'] = $parameterValue[1];
					}
				}

				$registerLine = $registerLines->item($i)->childNodes;
				$date = new Zend_Date($registerLine->item(0)->nodeValue, "dd/MM/yy");
				$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
				$value = $registerLine->item(2)->nodeValue;
				preg_match('/[0-9]+(,[0-9]{3})*(\.[0-9]{2})?$/', $value, $matches);
				$obj['value'] = Oara_Utilities::parseDouble($matches[0]);
				$obj['method'] = $registerLine->item(6)->nodeValue;
				$paymentHistory[] = $obj;
			}

			return $paymentHistory;
		}
}
