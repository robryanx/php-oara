<?php
require_once realpath(dirname(__FILE__)).'/../../../PHPExcel.php';
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Bol
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Bol extends Oara_Network {
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_Daisycon
	 */
	public function __construct($credentials) {
		$user = $credentials['user'];
		$password = $credentials['password'];


		$valuesLogin = array(
			new Oara_Curl_Parameter('j_username', $user),
			new Oara_Curl_Parameter('j_password', $password)
		);

		$loginUrl = 'https://partnerprogramma.bol.com/partner/j_security_check';
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		//If not login properly the construct launch an exception
		$connection = false;
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://partnerprogramma.bol.com/partner/index.do?', array());
		$exportReport = $this->_client->get($urls);

		if (preg_match("/partner\/logout\.do/", $exportReport[0], $match)) {
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

		$obj = array();
		$obj['cid'] = "1";
		$obj['name'] = "Bol.com";
		$merchants[] = $obj;
		
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$folder = realpath(dirname(__FILE__)).'/../../data/pdf/';
		$totalTransactions = array();
		$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
		for ($i = 0; $i < sizeof($dateArray); $i++) {
			$valuesFromExport = array();
			$valuesFromExport[] = new Oara_Curl_Parameter('id', "-1");			
			$valuesFromExport[] = new Oara_Curl_Parameter('fromDate', $dateArray[$i]->toString("yyyy-MM-dd"));
			$valuesFromExport[] = new Oara_Curl_Parameter('toDate', $dateArray[$i]->toString("yyyy-MM-dd"));
			
			

			$urls = array();
			$urls[] = new Oara_Curl_Request('https://partnerprogramma.bol.com/partner/affiliate/productOverview?', $valuesFromExport);
			$exportReport = $this->_client->get($urls);
			
			$my_file = $folder.mt_rand().'.xlsx';
			$handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
			$data = $exportReport[0];
			fwrite($handle, $data);
			fclose($handle);
			
			$objReader = PHPExcel_IOFactory::createReader('Excel2007');
			$objReader->setReadDataOnly(true);
			
			$objPHPExcel = $objReader->load($my_file);
			$objWorksheet = $objPHPExcel->getActiveSheet();
			
			$highestRow = $objWorksheet->getHighestRow(); 
			$highestColumn = $objWorksheet->getHighestColumn(); 
			
			$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); 
			
			for ($row = 2; $row <= $highestRow; ++$row) {

			  for ($col = 0; $col <= $highestColumnIndex; ++$col) {
			    $value =  $objWorksheet->getCellByColumnAndRow(3, $row)->getValue();
			   	$commission = $objWorksheet->getCellByColumnAndRow(4, $row)->getValue();
			    
			    
			    $transaction = Array();
				$transaction['merchantId'] = "1";
				$transaction['date'] = $dateArray[$i]->toString("yyyy-MM-dd HH:mm:ss");

				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				$transaction['amount'] = Oara_Utilities::parseDouble($value);
				$transaction['commission'] = Oara_Utilities::parseDouble($commission);
				$totalTransactions[] = $transaction;
			    
			  }
			}
			unlink($my_file);
		}
		

		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$overviewArray = Array();
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
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();

		return $paymentHistory;
	}

}
