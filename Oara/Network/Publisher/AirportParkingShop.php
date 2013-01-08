<?php
/**
 * Api Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_AirportParkingShop
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_AirportParkingShop extends Oara_Network {

	public $_host = null;
	public $_schema = null;
	public $_user = null;
	public $_password = null;
	
	public $_merchantMap = array( "TE"=> 1, "SPS" => 2, "PP" =>3, "PAG" =>4 , "HE" =>5 , "FHR" =>6 , "ET" =>7 , "BCP" =>8 , "APH" =>9 );
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
		$obj['name'] = 'Travel Extras';
		$merchantList[] = $obj;
		
		$obj = Array();
		$obj['cid'] = 2;
		$obj['name'] = 'Skyparksecure';
		$merchantList[] = $obj;
		
		$obj = Array();
		$obj['cid'] = 3;
		$obj['name'] = 'Purple Parking';
		$merchantList[] = $obj;
		
		$obj = Array();
		$obj['cid'] = 4;
		$obj['name'] = 'Park and Go';
		$merchantList[] = $obj;
		
		$obj = Array();
		$obj['cid'] = 5;
		$obj['name'] = 'Holiday Extras';
		$merchantList[] = $obj;
		
		$obj = Array();
		$obj['cid'] = 6;
		$obj['name'] = 'FHR';
		$merchantList[] = $obj;
		
		$obj = Array();
		$obj['cid'] = 7;
		$obj['name'] = 'Essential Travel';
		$merchantList[] = $obj;
		
		$obj = Array();
		$obj['cid'] = 8;
		$obj['name'] = 'BCP';
		$merchantList[] = $obj;
		
		$obj = Array();
		$obj['cid'] = 9;
		$obj['name'] = 'APH';
		$merchantList[] = $obj;
		
		return $merchantList;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		$sql = "SELECT * FROM booking WHERE date >= '".$dStartDate->toString("yyyy-MM-dd")."' AND date <= '".$dEndDate->toString("yyyy-MM-dd")."'";
		$result = mysql_query($sql);
		if (mysql_num_rows($result) > 0) {
			while ($row = mysql_fetch_array($result)) {
				if (isset($this->_merchantMap[$row['partner']])){
					$transaction = array();
					$transaction['merchantId'] = $this->_merchantMap[$row['partner']];
					$transaction['unique_id'] = $row['bookingref'];
					$transaction['date'] = $row['date']." ".$row['time'];
					$transaction['amount'] = (double) $row['amount_net'];
					$transaction['commission'] = (double) $row['commission_net'];
					if ($row['cancelled'] == 1){
						$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
					} else {
						$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
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
