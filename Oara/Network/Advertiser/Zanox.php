<?php
/**
 * API Class
 *
 * @author Carlos Morillo Merino
 * @category Oara_Network_Advertiser_Tradedoubler
 * @copyright Fubra Limited
 * @version Release: 01.00
 *         
 */
class Oara_Network_Advertiser_Zanox extends Oara_Network {
	
	
	/**
	 * Client
	 *
	 * @var unknown_type
	 */
	private $_client = null;
	
	private $_idProgram = null;
	

		/**
		 * Constructor
		 */
		public function __construct($credentials) {
			
			$user = $credentials ['user'];
			$password = $credentials ['password'];
			
			$loginUrl = 'https://auth.zanox.com/login?';				
			
			$valuesLogin = array (
					new Oara_Curl_Parameter ( 'loginForm.userName', $user ),
					new Oara_Curl_Parameter ( 'loginForm.password', $password ),
					new Oara_Curl_Parameter ( 'loginForm.loginViaUserAndPassword', "true" )
			);
			
			$this->_client = new Oara_Curl_Access ( $loginUrl, $valuesLogin, $credentials );
			
			$exportReport = $this->_client->getConstructResult();
			
			if (preg_match ( "/programs : [{\"id\":[0-9]+/", $exportReport, $matches )) {
				preg_match ( "/[0-9]+/", $matches[0], $idProgram );
				$this->_idProgram = $idProgram[0];
			}

		}
		/**
		 * Check the connection
		 */
		public function checkConnection() {
			$connection = false;
			$urls = array ();
			$urls [] = new Oara_Curl_Request ( 'https://advertiser.zanox.com/advertiserdashboard/main/app?program='.$this->_idProgram, array () );
			$exportReport = $this->_client->get ( $urls );
			if (preg_match ( "/logout/", $exportReport [0], $matches )) {
				$connection = true;
			}
			return $connection;
		}
		/**
		 * (non-PHPdoc)
		 * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
		 */
		public function getMerchantList() {
			$merchants = Array();
			$obj = Array();
			$obj['cid'] = $this->_idProgram;
			$obj['name'] = 'Zanox';
			$merchants[] = $obj;
	
			return $merchants;
		}
		
		/**
		 * (non-PHPdoc)
		 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
		 */
		public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {			
			$totalTransactions = array ();
/**/
$dStartDate = new Zend_Date('2014-08-01', 'yyyy-MM-dd'); 
$dEndDate  	= new Zend_Date('2014-09-03', 'yyyy-MM-dd');
/**/
			$timestampStartDate = strtotime($dStartDate->toString ( "dd-MM-yyyy" ));  //'05-08-2014'
			$timestampEndDate = strtotime($dEndDate->toString ( "dd-MM-yyyy" ));
			
			$timestampStartDate = $timestampStartDate - 3600;
			$timestampStartDate = $timestampStartDate . '000';
			$timestampEndDate = $timestampEndDate - 3600;
			$timestampEndDate = $timestampEndDate . '000';
			
			$valuesFromExport = array ();
			$valuesFromExport [] = new Oara_Curl_Parameter ( 'transaction_type', 'SALE' );
			$valuesFromExport [] = new Oara_Curl_Parameter ( 'approved', 'true' );
			$valuesFromExport [] = new Oara_Curl_Parameter ( 'rejected', 'true' );
			$valuesFromExport [] = new Oara_Curl_Parameter ( 'open', 'true' );
			$valuesFromExport [] = new Oara_Curl_Parameter ( 'invalid', 'true' );
			$valuesFromExport [] = new Oara_Curl_Parameter ( 'confirmed', 'true' );
//			$valuesFromExport [] = new Oara_Curl_Parameter ( 'date_from', '1407193200000' ); //1407193200000 ==> "1407196800 - 3600" ++ "000"
//			$valuesFromExport [] = new Oara_Curl_Parameter ( 'date_to', '1407193200000' );
			$valuesFromExport [] = new Oara_Curl_Parameter ( 'date_from', $timestampStartDate ); //1407193200000 ==> "1407196800 - 3600" ++ "000"
			$valuesFromExport [] = new Oara_Curl_Parameter ( 'date_to', $timestampEndDate );
			$valuesFromExport [] = new Oara_Curl_Parameter ( 'sale_date', 'true' );
			$valuesFromExport [] = new Oara_Curl_Parameter ( 'pattern', '' );
			$valuesFromExport [] = new Oara_Curl_Parameter ( 'pattern_field', 'ORDER_ID' );
			$valuesFromExport [] = new Oara_Curl_Parameter ( 'sort_column', 'SALE_DATE' );
			$valuesFromExport [] = new Oara_Curl_Parameter ( 'sort_direction', 'DESC' );
			$valuesFromExport [] = new Oara_Curl_Parameter ( 'tracking_category', '' );
			$valuesFromExport [] = new Oara_Curl_Parameter ( 'program_id', $this->_idProgram );
			$valuesFromExport [] = new Oara_Curl_Parameter ( 'locale_name', 'en_US' );			

/*
$arg = array ();
foreach ( $valuesFromExport as $parameter ) {
	$arg [] = $parameter->getKey () . '=' . urlencode ( $parameter->getValue () );
}
echo 'https://advertiser.zanox.com/advertisertransactionconfirmation/main/export?'.implode ( '&', $arg ) ;
echo "\n";
echo 'https://advertiser.zanox.com/advertisertransactionconfirmation/main/export?transaction_type=SALE&approved=true&rejected=true&open=true&invalid=true&confirmed=true&date_from=1407193200000&date_to=1407193200000&sale_date=true&pattern=&pattern_field=ORDER_ID&sort_column=SALE_DATE&sort_direction=DESC&tracking_category=&program_id=7641&locale_name=en_US';
echo "\n";
*/
			$urls = array ();
			$urls [] = new Oara_Curl_Request ( 'https://advertiser.zanox.com/advertisertransactionconfirmation/main/export?', $valuesFromExport );
			try{				
				$result = $this->_client->get($urls);
			} catch (Exception $e){
				return $transactions;
			}
			$exportData = str_getcsv($result[0], ";");
/**/
$exportData=null;
$folder = realpath(dirname(__FILE__)).'/../../data/pdf/';
$my_file = $folder.'tracking_pps_report.csv';

$csvfile = fopen($my_file,'rb');
while(!feof($csvfile)) {
	$exportData[] = fgetcsv($csvfile,1000, ";");

}
/**/
			for ($j = 1; $j < count($exportData)-5; $j++){
				if($exportData[$j][2] == null){
					throw new Exception ( 'Order ID is null' );
				}else{
					$transaction = Array();
					$transaction['custom_id'] = $exportData[$j][4];
					$transaction['unique_id'] = $exportData[$j][2];
					$transaction['merchantId'] = $this->_idProgram;
					$transactionDate = new Zend_Date($exportData[$j][12], 'dd/MM/yy HH:mm CEST');
					$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
					$transaction['amount'] = $exportData[$j][7];
					$transaction['commission'] = $exportData[$j][8];
						
					if ($exportData[$j][0] == 'Confirmed') {
						$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
					} else
					if ($exportData[$j][0] == 'Open') {
						$transaction['status'] = Oara_Utilities::STATUS_PENDING;
					} else
					if ($exportData[$j][0] == 'Rejected') {
						$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
					}
					
					$totalTransactions[] = $transaction;
				}
			
			}
		
			return $totalTransactions;
			
		}
}
