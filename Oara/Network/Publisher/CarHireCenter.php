<?php
/**
 * Api Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_CarHireCenter
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_CarHireCenter extends Oara_Network {

	public $_host = null;
	public $_schema = null;
	public $_user = null;
	public $_password = null;
	
	public $_merchantMap = array( "ha"=> 1, "tj" => 2, "ag" =>3, "ae" =>4 );
	/**
	 * Constructor.
	 * @param $credentials
	 * @return Oara_Network_Publisher_CarHireCenter
	 */
	public function __construct($credentials) {

		$this->_host = $credentials['host'];
		$this->_schema = $credentials['schema'];
		$this->_user = $credentials['user'];
		$this->_password = $credentials['password'];
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = true;
		$con = mysql_connect($this->_host, $this->_user, $this->_password);
		if (!$con) {
			return $connection = false;
		}
		$dbcheck = mysql_select_db($this->_schema);
		if (!$dbcheck) {
			return $connection = false;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
	 */
	public function getMerchantList() {
		$merchantList = array();
		$obj = Array();
		$obj['cid'] = 1;
		$obj['name'] = 'HolidayAutos';
		$merchantList[] = $obj;
		
		$obj = Array();
		$obj['cid'] = 2;
		$obj['name'] = 'Car Hire 3000';
		$merchantList[] = $obj;
		
		$obj = Array();
		$obj['cid'] = 3;
		$obj['name'] = 'Argus';
		$merchantList[] = $obj;
		
		$obj = Array();
		$obj['cid'] = 4;
		$obj['name'] = 'Auto Europe';
		$merchantList[] = $obj;
		
		return $merchantList;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		$sql = "SELECT * FROM booking_log WHERE booking_datetime >= '".$dStartDate->toString("yyyy-MM-dd HH:mm:ss")."' AND booking_datetime <= '".$dEndDate->toString("yyyy-MM-dd HH:mm:ss")."'";
		$result = mysql_query($sql);
		if (mysql_num_rows($result) > 0) {
			while ($row = mysql_fetch_array($result)) {
				if (isset($this->_merchantMap[$row['agent_used']])){
					$transaction = array();
					$transaction['merchantId'] = $this->_merchantMap[$row['agent_used']];
					$transaction['unique_id'] = $row['booking_id'];
					$transaction['date'] = $row['booking_datetime'];
					$transaction['amount'] = (double) $row['total_cost'];
					$transaction['commission'] = (double) $row['commission'];
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
	
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
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
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

}
