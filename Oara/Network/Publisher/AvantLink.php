<?php
require_once realpath(dirname(__FILE__)).'/../../../PHPExcel.php';
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_AvantLink
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_AvantLink extends Oara_Network {
	
	private $_domain = null;
	private $_id = null;
	private $_apikey = null;
	
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_ShareASale
	 */
	public function __construct($credentials) {

		$user = $credentials ['user'];
		$password = $credentials ['password'];
		// Choosing the Linkshare network
		
		if ($credentials ['network'] == 'ca') {
			$this->_domain = "https://www.avantlink.ca";
		}
		
		$valuesLogin = array (
				new Oara_Curl_Parameter ( 'strLoginType', 'affiliate' ),
				new Oara_Curl_Parameter ( 'cmdLogin', 'Login' ),
				new Oara_Curl_Parameter ( 'loginre', '' ),
				new Oara_Curl_Parameter ( 'strEmailAddress', $user ),
				new Oara_Curl_Parameter ( 'strPassword', $password ),
				new Oara_Curl_Parameter ( 'intScreenResWidth', '1920' ),
				new Oara_Curl_Parameter ( 'intScreenResHeight', '1080' ) 
		);
		// Login to the Linkshare Application
		$this->_client = new Oara_Curl_Access ( $this->_domain."/login.php", $valuesLogin, $credentials );
		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
			
		$urls = array ();
		$urls [] = new Oara_Curl_Request ( 'https://www.avantlink.ca/affiliate/view_edit_auth_key.php', array () );
		$result = $this->_client->get ( $urls );
		if (preg_match ( "/<p><strong>Affiliate ID:<\/strong> (.*)?<\/p>/", $result [0], $matches )) {
			$this->_id = $matches[1];
			if (preg_match ( "/<p><strong>API Authorization Key:<\/strong> (.*)?<\/p>/", $result [0], $matches )) {
				$this->_apikey = $matches[1];
				$connection = true;
			}
			
		}
		return $connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		
		$merchants = array();
		$params = array (
				new Oara_Curl_Parameter ( 'cmdDownload', 'Download All Active Merchants' ),
				new Oara_Curl_Parameter ( 'strRelationStatus', 'active' )
		);
		
		$urls = array ();
		$urls [] = new Oara_Curl_Request ( $this->_domain.'/affiliate/merchants.php',$params );
		$result = $this->_client->post ( $urls );
		
		$folder = realpath(dirname(__FILE__)).'/../../data/pdf/';
		$my_file = $folder.mt_rand().'.xls';
		
		$handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
		$data = $result[0];
		fwrite($handle, $data);
		fclose($handle);
			
		$objReader = PHPExcel_IOFactory::createReader('Excel5');
		$objReader->setReadDataOnly(true);
			
		$objPHPExcel = $objReader->load($my_file);
		$objWorksheet = $objPHPExcel->getActiveSheet();
			
		$highestRow = $objWorksheet->getHighestRow();
		$highestColumn = $objWorksheet->getHighestColumn();
			
		$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
			
		for ($row = 2; $row <= $highestRow; ++$row) {
		
			$obj = Array();
			$obj['cid'] = $objWorksheet->getCellByColumnAndRow(0, $row)->getValue();
			$obj['name'] = $objWorksheet->getCellByColumnAndRow(1, $row)->getValue();
			$merchants[] = $obj;
			 
		}
		unlink($my_file);
		
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($idMerchant, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		
		
		$affiliate_id = $this->_id;
		$auth_key = $this->_apikey;
		
		$strUrl = 'https://www.avantlink.ca/api.php';
		$strUrl .= "?affiliate_id=$affiliate_id";
		$strUrl .= "&auth_key=$auth_key";
		$strUrl .= "&module=AffiliateReport";
		$strUrl .= "&output=" . urlencode('csv');
		$strUrl .= "&report_id=8";
		$strUrl .= "&date_begin=" . urlencode($dStartDate->toString("yyyy-MM-dd HH:mm:ss"));
		$strUrl .= "&date_end=" . urlencode($dEndDate->toString("yyyy-MM-dd HH:mm:ss"));
		$strUrl .= "&include_inactive_merchants=0";
		$strUrl .= "&search_results_include_cpc=0";
		
		$returnResult = self::makeCall($strUrl);
		$exportData = str_getcsv($returnResult, "\r\n");
		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], ",");
			if (count($transactionExportArray) > 1 && in_array((int) $transactionExportArray[17], $merchantList)) {
				$transaction = Array();
				$merchantId = (int) $transactionExportArray[17];
				$transaction['merchantId'] = $merchantId;
				$transactionDate = new Zend_Date($transactionExportArray[11], 'MM-dd-yyyy HH:mm:ss');
				$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
				$transaction['unique_id'] = (int)$transactionExportArray[5];

				if ($transactionExportArray[4] != null) {
					$transaction['custom_id'] = $transactionExportArray[4];
				}

				$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				$transaction['amount'] = Oara_Utilities::parseDouble(preg_replace("/[^0-9\.,]/", "", $transactionExportArray[6]));
				$transaction['commission'] = Oara_Utilities::parseDouble(preg_replace("/[^0-9\.,]/", "", $transactionExportArray[7]));
				$totalTransactions[] = $transaction;
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
	/**
	 * 
	 * Make the call for this API
	 * @param string $actionVerb
	 */
	private function makeCall($strUrl){
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $strUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$returnResult = curl_exec($ch);
		curl_close($ch);
		return $returnResult;
	}
}
