<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_PayMode
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_PayMode extends Oara_Network{
	/**
	 * Export Transaction Parameters
	 * @var array
	 */
	private $_exportTransactionParameters = null;
	/**
	 * Export Payment Parameters
	 * @var array
	 */
	private $_exportPaymentParameters = null;
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;
	
	/**
	 * AgentNumber
	 * @var unknown_type
	 */
	private $_agent = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Daisycon
	 */
	public function __construct($credentials)
	{
		$user = $credentials['user'];
		$password = $credentials['password'];
		$valuesLogin = array(
		new Oara_Curl_Parameter('username', $user),
		new Oara_Curl_Parameter('password', $password),
		new Oara_Curl_Parameter('Enter', 'Enter')
		);

		$loginUrl = 'https://secure.paymode.com/paymode/do-login.jsp?';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);


		$this->_exportTransactionParameters = array(new Oara_Curl_Parameter('isDetailReport', 'true'),
		new Oara_Curl_Parameter('method', 'ALL'),
		new Oara_Curl_Parameter('currency', 'ALL_CURRENCIES'),
		new Oara_Curl_Parameter('amount', ''),
		new Oara_Curl_Parameter('disburserName', ''),
		new Oara_Curl_Parameter('remitType', 'CAR'),
		new Oara_Curl_Parameter('CAR_customerName', ''),
		new Oara_Curl_Parameter('CAR_confirmationNumber', ''),
		new Oara_Curl_Parameter('CAR_franchiseNumber', ''),
		new Oara_Curl_Parameter('CAR_remitStartDate', ''),
		new Oara_Curl_Parameter('CAR_remitEndDate', ''),
		new Oara_Curl_Parameter('CAR_rentalLocation', ''),
		new Oara_Curl_Parameter('CAR_agreementNumber', ''),
		new Oara_Curl_Parameter('CAR_commissionAmount', ''),
		new Oara_Curl_Parameter('CAR_sortBy', 'CUSTOMER_NAME'),
		new Oara_Curl_Parameter('submit1', 'Submit'),
		new Oara_Curl_Parameter('AIR_customerName', ''),
		new Oara_Curl_Parameter('AIR_confirmationNumber', ''),
		new Oara_Curl_Parameter('AIR_agreementNumber', ''),
		new Oara_Curl_Parameter('AIR_issueDate', ''),
		new Oara_Curl_Parameter('AIR_sortBy', 'CUSTOMER_NAME'),

		new Oara_Curl_Parameter('CRUISE_vesselName', ''),
		new Oara_Curl_Parameter('CRUISE_customerName', ''),
		new Oara_Curl_Parameter('CRUISE_confirmationNumber', ''),
		new Oara_Curl_Parameter('CRUISE_remitStartDate', ''),
		new Oara_Curl_Parameter('CRUISE_duration', ''),
		new Oara_Curl_Parameter('CRUISE_commissionAmount', ''),
		new Oara_Curl_Parameter('CRUISE_sortBy', 'FACILITY_NAME'),

		new Oara_Curl_Parameter('HOTEL_hotelName', ''),
		new Oara_Curl_Parameter('HOTEL_customerName', ''),
		new Oara_Curl_Parameter('HOTEL_confirmationNumber', ''),
		new Oara_Curl_Parameter('HOTEL_remitStartDate', ''),
		new Oara_Curl_Parameter('HOTEL_duration', ''),
		new Oara_Curl_Parameter('HOTEL_commissionAmount', ''),
		new Oara_Curl_Parameter('HOTEL_sortBy', 'FACILITY_NAME')
		);


			
		$this->_exportPaymentParameters = array(
		new Oara_Curl_Parameter('isDetailReport', 'false'),
		new Oara_Curl_Parameter('method', 'ALL'),
		new Oara_Curl_Parameter('currency', 'ALL_CURRENCIES'),
		new Oara_Curl_Parameter('amount', ''),
		new Oara_Curl_Parameter('disburserName', ''),
		new Oara_Curl_Parameter('submit1', 'Submit')
		);
			
			
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		//If not login properly the construct launch an exception
		$connection = false;
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://secure.paymode.com/paymode/home.jsp?', array());
		$exportReport = $this->_client->get($urls);

		if (preg_match("/paymode\/logout\.jsp/", $exportReport[0], $matches)){
				
			$urls = array();
			$urls[] = new Oara_Curl_Request('https://secure.paymode.com/paymode/reports-pre_commission_history.jsp?', array());
			$exportReport = $this->_client->get($urls);
			$dom = new Zend_Dom_Query($exportReport[0]);
			$results = $dom->query('input[type="checkbox"]');
			$agentNumber = null;
			foreach ($results as $result){
				$this->_agentNumber = $result->getAttribute("id");
			}
				
			$connection = true;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getMerchantList()
	 */
	public function getMerchantList($merchantMap = array()){
		$merchants = array();

		$obj = array();
		$obj['cid'] = 1;
		$obj['name'] = "Pay Mode";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){

		$totalTransactions = array();
		$filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));

		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter($this->_agentNumber, "on");
		$valuesFromExport[] = new Oara_Curl_Parameter('startDate', $dStartDate->toString("MM/dd/yyyy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('endDate', $dEndDate->toString("MM/dd/yyyy"));

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://secure.paymode.com/paymode/post-coll_comm_hist_detail.jsp?', $valuesFromExport);
		$exportReport = $this->_client->post($urls);
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://secure.paymode.com/paymode/tewf/navGenericReport.jsp?presentation=excel', array());
		$exportReport = $this->_client->get($urls);

		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('tr[valign="top"]');
		foreach ($results as $line){
			$transaction = Array();
			$lineHtml = self::DOMinnerHTML($line);
			$domLine = new Zend_Dom_Query($lineHtml);
			$resultsLine = $domLine->query('.rptcontentText');
			if (count($resultsLine) > 0){

				$transaction['merchantId'] = 1;
				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				$i = 0;
				foreach ($resultsLine as $attribute){
					if ($i == 5){
						$transactionDate = new Zend_Date($attribute->nodeValue, 'MM/dd/yyyy', 'en');
						$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
					} else if ($i == 13){
						$transaction['amount'] = $filter->filter($attribute->nodeValue);
						$transaction['commission'] = $filter->filter($attribute->nodeValue);
					}
					$i++;
				}

				$totalTransactions[] = $transaction;

			}
		}
			
		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
		$totalOverviews = Array();
		$transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);
		foreach ($transactionArray as $merchantId => $merchantTransaction){
			foreach ($merchantTransaction as $date => $transactionList){

				$overview = Array();

				$overview['merchantId'] = $merchantId;
				$overviewDate = new Zend_Date($date, "yyyy-MM-dd");
				$overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
				$overview['click_number'] = 0;
				$overview['impression_number'] = 0;
				$overview['transaction_number'] = 0;
				$overview['transaction_confirmed_value'] = 0;
				$overview['transaction_confirmed_commission']= 0;
				$overview['transaction_pending_value']= 0;
				$overview['transaction_pending_commission']= 0;
				$overview['transaction_declined_value']= 0;
				$overview['transaction_declined_commission']= 0;
				foreach ($transactionList as $transaction){
					$overview['transaction_number'] ++;
					if ($transaction['status'] == Oara_Utilities::STATUS_CONFIRMED){
						$overview['transaction_confirmed_value'] += $transaction['amount'];
						$overview['transaction_confirmed_commission'] += $transaction['commission'];
					} else if ($transaction['status'] == Oara_Utilities::STATUS_PENDING){
						$overview['transaction_pending_value'] += $transaction['amount'];
						$overview['transaction_pending_commission'] += $transaction['commission'];
					} else if ($transaction['status'] == Oara_Utilities::STATUS_DECLINED){
						$overview['transaction_declined_value'] += $transaction['amount'];
						$overview['transaction_declined_commission'] += $transaction['commission'];
					}
				}
				$totalOverviews[] = $overview;
			}
		}

		return $totalOverviews;

	}
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Base#getPaymentHistory()
	 */
	public function getPaymentHistory(){

		$paymentHistory = array();

		$filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));
		$startDate = new Zend_Date("01-01-2010", "dd-MM-yyyy");
		$endDate = new Zend_Date();

		$dateList = Oara_Utilities::monthsOfDifference($startDate, $endDate);
		foreach ($dateList as $date){
			$monthStartDate = clone $date;
			$monthEndDate = null;

			$monthEndDate = clone $date;
			$monthEndDate->setDay(1);
			$monthEndDate->addMonth(1);
			$monthEndDate->subDay(1);

			$monthEndDate->setHour(23);
			$monthEndDate->setMinute(59);
			$monthEndDate->setSecond(59);

			$valuesFromExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
			$valuesFromExport[] = new Oara_Curl_Parameter($this->_agentNumber, "on");
			$valuesFromExport[] = new Oara_Curl_Parameter('startDate', $monthStartDate->toString("MM/dd/yyyy"));
			$valuesFromExport[] = new Oara_Curl_Parameter('endDate', $monthEndDate->toString("MM/dd/yyyy"));

			$urls = array();
			$urls[] = new Oara_Curl_Request('https://secure.paymode.com/paymode/post-coll_comm_hist_summary.jsp', $valuesFromExport);
			$exportReport = $this->_client->post($urls);
			$urls = array();
			$urls[] = new Oara_Curl_Request('https://secure.paymode.com/paymode/tewf/navGenericReport.jsp?presentation=excel', array());
			$exportReport = $this->_client->get($urls);

			$dom = new Zend_Dom_Query($exportReport[0]);
			$results = $dom->query('tr[valign="top"]');
			foreach ($results as $line){
				$payment = Array();
				$lineHtml = self::DOMinnerHTML($line);
				$domLine = new Zend_Dom_Query($lineHtml);
				$resultsLine = $domLine->query('.rptcontentText');
				if (count($resultsLine) > 0){
					$i = 0;
					foreach ($resultsLine as $attribute){
						if ($i == 0){
							$payment['pid'] = $attribute->nodeValue;
						} else if ($i == 3){
							$paymentDate = new Zend_Date($attribute->nodeValue, 'MM/dd/yyyy', 'en');
							$payment['date'] = $paymentDate->toString("yyyy-MM-dd HH:mm:ss");
						} else if ($i == 4){
							$payment['method'] = $attribute->nodeValue;
						} else if ($i == 6){
							$payment['value'] = $filter->filter($attribute->nodeValue);
						}
						$i++;
					}

					$paymentHistory[] = $payment;

				}
			}

		}
		return $paymentHistory;
	}


	/**
	 *
	 * Function that returns the inner HTML code
	 * @param unknown_type $element
	 */
	private function DOMinnerHTML($element)
	{
		$innerHTML = "";
		$children = $element->childNodes;
		foreach ($children as $child)
		{
			$tmp_dom = new DOMDocument();
			$tmp_dom->appendChild($tmp_dom->importNode($child, true));
			$innerHTML.=trim($tmp_dom->saveHTML());
		}
		return $innerHTML;
	}

}