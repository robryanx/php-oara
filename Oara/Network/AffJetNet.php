<?php
/**
 * Data Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_AffJetNet
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_AffJetNet extends Oara_Network{
	//Db user
	private $_user = null;
	//Db pass
	private $_pass = null;
	//Db host
	private $_host = null;
	//Db name
	private $_db = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 */
	public function __construct($credentials)
	{
		$this->_user = $credentials['user'];
		$this->_password = $credentials['password'];
		$this->_host = $credentials['host'];
		$this->_db = $credentials['db'];
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		$connection = true;
		try{
			$conn = mysql_connect($this->_host, $this->_user, $this->_password);
			if (!$conn) {
				throw new Exception('Error connecting to mysql'. mysql_error());
			}
			$db_selected = mysql_select_db($this->_db, $conn);
			if ( $db_selected == false){
				throw new Exception('Error connecting to the data base'. mysql_error());
			}
		} catch (Exception $e){
			$connection = false;
		}

		
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getMerchantList()
	 */
	public function getMerchantList($merchantMap = array())
	{
		$merchants = Array();
		
		$result = mysql_query("SELECT * FROM affjet_net_merchant");

		while ($row = mysql_fetch_assoc($result)) {
		    $obj = Array();
			$obj['cid'] = $row["id"];
			$obj['name'] = $row["name"];
			$obj['url'] = $row["url"];
			$merchants[] = $obj;
		}
		mysql_free_result($result);
		return $merchants;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null , Zend_Date $dStartDate = null , Zend_Date $dEndDate = null)
	{
		$totalTransactions = Array();
		
		$query = "SELECT * FROM affjet_net_transaction WHERE date>='".$dStartDate->toString("yyyy-MM-dd HH:mm:ss")."' AND date<='".$dEndDate->toString("yyyy-MM-dd HH:mm:ss")."' AND merchant_id IN (". implode(",", $merchantList).")";
		$result = mysql_query($query);
		
		while ($row = mysql_fetch_assoc($result)) {
			
			$transaction = array();
			$transaction['merchantId'] = $row["merchant_id"];
			$transaction['date'] = $row["date"];
			$transaction['amount'] = $row["amount"];
			$transaction['commission'] = $row["commission"];
			$transaction['status'] = $row["status"];
			$transaction['customId'] = $row["custom_id"];
			$totalTransactions[] = $transaction;
		}
		mysql_free_result($result);
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
		$result = mysql_query("SELECT * FROM affjet_net_payment");

		while ($row = mysql_fetch_assoc($result)) {
		    $obj = Array();
			$obj['date'] = $row["date"];
			$obj['value'] = $row["value"];
			$obj['method'] = $row["method"];
			$paymentHistory[] = $obj;
		}
		mysql_free_result($result);
		return $paymentHistory;
	}

}