<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_AutoEurope
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_AutoEurope extends Oara_Network {
	/**
	 * Export client.
	 *
	 * @var Oara_Curl_Access
	 */
	private $_client = null;
	
	/**
	 * Transaction Export Parameters
	 *
	 * @var array
	 */
	private $_exportTransactionParameters = null;
	
	/**
	 * Constructor and Login
	 *
	 * @param
	 *        	$au
	 * @return Oara_Network_Publisher_AutoEurope_Export
	 */
	public function __construct($credentials) {
		$user = $credentials ['user'];
		$password = $credentials ['password'];
		$loginUrl = 'https://www.auto-europe.co.uk/afftools/index.cfm';
		
		$valuesLogin = array (
				new Oara_Curl_Parameter ( 'action', 'runreport' ),
				new Oara_Curl_Parameter ( 'alldates', 'all' ),
				new Oara_Curl_Parameter ( 'membername', $user ),
				new Oara_Curl_Parameter ( 'affpass', $password ),
				new Oara_Curl_Parameter ( 'Post', 'Login' ) 
		);
		
		$this->_client = new Oara_Curl_Access ( $loginUrl, $valuesLogin, $credentials );
		
		$this->_exportTransactionParameters = array (
				new Oara_Curl_Parameter ( 'pDB', 'UK' ),
				new Oara_Curl_Parameter ( 'content', 'PDF' ) 
		);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
		$urls = array ();
		$urls [] = new Oara_Curl_Request ( 'https://www.auto-europe.co.uk/afftools/index.cfm', array () );
		$exportReport = $this->_client->get ( $urls );
		if (preg_match ( "/logout\.cfm/", $exportReport [0], $matches )) {
			$connection = true;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 *
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = Array ();
		$obj = Array ();
		$obj ['cid'] = 1;
		$obj ['name'] = 'Auto Europe';
		$obj ['url'] = 'https://www.auto-europe.co.uk';
		$merchants [] = $obj;
		
		return $merchants;
	}
	/**
	 * (non-PHPdoc)
	 *
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = Array ();
		
		$dEndDate->addDay ( 1 );
		$valuesFormExport = Oara_Utilities::cloneArray ( $this->_exportTransactionParameters );
		$valuesFormExport [] = new Oara_Curl_Parameter ( 'pDate1', $dStartDate->toString ( "MM/d/yyyy" ) );
		$valuesFormExport [] = new Oara_Curl_Parameter ( 'pDate2', $dEndDate->toString ( "MM/d/yyyy" ) );
		$urls = array ();
		$urls [] = new Oara_Curl_Request ( 'https://www.auto-europe.co.uk/afftools/iatareport_popup.cfm?', $valuesFormExport );
		$exportReport = $this->_client->post ( $urls );
		$xmlTransactionList = self::readTransactions ( $exportReport [0] );
		
		foreach ( $xmlTransactionList as $xmlTransaction ) {
			$transaction = array ();
			$transaction ['merchantId'] = 1;
			$date = new Zend_date ( $xmlTransaction ['Booked'], "MM/dd/yyyy" );
			$transaction ['date'] = $date->toString ( "yyyy-MM-dd" );
			$transaction ['amount'] = ( double ) $xmlTransaction ['commissionValue'];
			$transaction ['commission'] = ( double ) $xmlTransaction ['commission'];
			$transaction ['status'] = Oara_Utilities::STATUS_CONFIRMED;
			$transaction ['unique_id'] = $xmlTransaction ['Res #'];
			if (isset ( $xmlTransaction ['Affiliate1'] ) && isset ( $xmlTransaction ['Affiliate2'] )) {
				$customId = ( string ) $xmlTransaction ['Affiliate1'] . ( string ) $xmlTransaction ['Affiliate2'];
				$customId = "afal-" . current ( unpack ( 'H*', base64_decode ( str_replace ( array (
						".",
						"-" 
				), array (
						"/",
						"+" 
				), $customId ) ) ) );
				$transaction ['custom_id'] = $customId;
			}
			
			$totalTransactions [] = $transaction;
		}
		return $totalTransactions;
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalOverviews = Array ();
		$transactionArray = Oara_Utilities::transactionMapPerDay ( $transactionList );
		foreach ( $transactionArray as $merchantId => $merchantTransaction ) {
			foreach ( $merchantTransaction as $date => $transactionList ) {
				
				$overview = Array ();
				
				$overview ['merchantId'] = $merchantId;
				$overviewDate = new Zend_Date ( $date, "yyyy-MM-dd" );
				$overview ['date'] = $overviewDate->toString ( "yyyy-MM-dd HH:mm:ss" );
				$overview ['click_number'] = 0;
				$overview ['impression_number'] = 0;
				$overview ['transaction_number'] = 0;
				$overview ['transaction_confirmed_value'] = 0;
				$overview ['transaction_confirmed_commission'] = 0;
				$overview ['transaction_pending_value'] = 0;
				$overview ['transaction_pending_commission'] = 0;
				$overview ['transaction_declined_value'] = 0;
				$overview ['transaction_declined_commission'] = 0;
				$overview ['transaction_paid_value'] = 0;
				$overview ['transaction_paid_commission'] = 0;
				foreach ( $transactionList as $transaction ) {
					$overview ['transaction_number'] ++;
					if ($transaction ['status'] == Oara_Utilities::STATUS_CONFIRMED) {
						$overview ['transaction_confirmed_value'] += $transaction ['amount'];
						$overview ['transaction_confirmed_commission'] += $transaction ['commission'];
					} else if ($transaction ['status'] == Oara_Utilities::STATUS_PENDING) {
						$overview ['transaction_pending_value'] += $transaction ['amount'];
						$overview ['transaction_pending_commission'] += $transaction ['commission'];
					} else if ($transaction ['status'] == Oara_Utilities::STATUS_DECLINED) {
						$overview ['transaction_declined_value'] += $transaction ['amount'];
						$overview ['transaction_declined_commission'] += $transaction ['commission'];
					} else if ($transaction ['status'] == Oara_Utilities::STATUS_PAID) {
						$overview ['transaction_paid_value'] += $transaction ['amount'];
						$overview ['transaction_paid_commission'] += $transaction ['commission'];
					}
				}
				$totalOverviews [] = $overview;
			}
		}
		
		return $totalOverviews;
	}
	/**
	 * Read the html table in the report
	 *
	 * @param string $htmlReport        	
	 * @param Zend_Date $startDate        	
	 * @param Zend_Date $endDate        	
	 * @param int $iteration        	
	 * @return array:
	 */
	public function readTransactions($htmlReport) {
		$pdfContent = '';
		$dom = new Zend_Dom_Query ( $htmlReport );
		$links = $dom->query ( '.text a' );
		$pdfUrl = null;
		foreach ( $links as $link ) {
			$pdfUrl = $link->getAttribute ( 'href' );
		}
		
		$urls = array ();
		$urls [] = new Oara_Curl_Request ( $pdfUrl, array () );
		$exportReport = $this->_client->get ( $urls );
		// writing temp pdf
		$exportReportUrl = explode ( '/', $pdfUrl );
		$exportReportUrl = $exportReportUrl [count ( $exportReportUrl ) - 1];
		$dir = realpath ( dirname ( __FILE__ ) ) . '/../../data/pdf/';
		$fh = fopen ( $dir . $exportReportUrl, 'w' );
		fwrite ( $fh, $exportReport [0] );
		fclose ( $fh );
		// parsing the pdf
		
		$pipes = null;
		$descriptorspec = array (
				0 => array (
						'pipe',
						'r' 
				),
				1 => array (
						'pipe',
						'w' 
				),
				2 => array (
						'pipe',
						'w' 
				) 
		);
		$pdfReader = proc_open ( "pdftohtml -xml -stdout " . $dir . $exportReportUrl, $descriptorspec, $pipes, null, null );
		if (is_resource ( $pdfReader )) {
			
			$pdfContent = '';
			$error = '';
			$stdin = $pipes [0];
			$stdout = $pipes [1];
			$stderr = $pipes [2];
			
			while ( ! feof ( $stdout ) ) {
				$pdfContent .= fgets ( $stdout );
			}
			
			while ( ! feof ( $stderr ) ) {
				$error .= fgets ( $stderr );
			}
			fclose ( $stdin );
			fclose ( $stdout );
			fclose ( $stderr );
			$exit_code = proc_close ( $pdfReader );
		}
		unlink ( $dir . $exportReportUrl );
		
		$xml = new SimpleXMLElement ( $pdfContent );
		
		$list = $xml->xpath ( "page" );
		$numberPages = 0;
		$numberPages = count ( $list );
		$transationList = array ();
		for($page = 1; $page <= $numberPages; $page ++) {
			
			$topHeader = null;
			$top = null;
			$list = $xml->xpath ( "page[@number=$page]/text[@font=0 and b = \"Agent\"]" );
			if (count ( $list ) > 0) {
				$header = current ( $list );
				$attributes = $header->attributes ();
				$top = ( int ) $attributes ['top'];
			} else {
				throw new Exception ( "No Header Found" );
			}
			
			if ($top == null) {
				throw new Exception ( "No Top Found" );
			}
			$fromTop = $top - 3;
			$toTop = $top + 3;
			$list = $xml->xpath ( "page[@number=$page]/text[@top>$fromTop and @top<$toTop and @font=0]" );
			$headerList = array ();
			foreach ( $list as $header ) {
				$xmlHeader = new stdClass ();
				$attributes = $header->attributes ();
				$xmlHeader->top = ( int ) $attributes ['top'];
				$xmlHeader->left = ( int ) $attributes ['left'];
				$xmlHeader->width = ( int ) $attributes ['width'];
				foreach ( $header->children () as $child ) {
					$xmlHeader->name = ( string ) $child;
				}
				if (strpos ( $xmlHeader->name, "commission" ) === false) {
					$headerList [(int)$xmlHeader->left] = $xmlHeader;
				} else {
					$xmlHeaderCommissionValue = new stdClass ();
					$xmlHeaderCommissionValue->top = $xmlHeader->top;
					$xmlHeaderCommissionValue->left = $xmlHeader->left;
					$xmlHeaderCommissionValue->width = 100;
					$xmlHeaderCommissionValue->name = ( string ) "commissionValue";
					
					$xmlHeaderCommission = new stdClass ();
					$xmlHeaderCommission->top = $xmlHeader->top;
					$xmlHeaderCommission->left = $xmlHeader->left + $xmlHeaderCommissionValue->width;
					$xmlHeaderCommission->width = 150;
					$xmlHeaderCommission->name = ( string ) "commission";
					
					$headerList [(int)$xmlHeaderCommissionValue->left] = $xmlHeaderCommissionValue;
					$headerList [(int)$xmlHeaderCommission->left] = $xmlHeaderCommission;
				}
				
			}
			ksort($headerList);
			$list = $xml->xpath ( "page[@number=$page]/text[@font=2]" );
			$rowList = array ();
			foreach ( $list as $row ) {
				$attributes = $row->attributes ();
				$top = ( int ) $attributes ['top'];
				if (! in_array ( $top, $rowList )) {
					$rowList [] = $top;
				}
			}
			
			
			foreach ( $rowList as $top ) {
				$transaction = array ();
				$list = $xml->xpath ( "page[@number=$page]/text[@top=$top and @font=2]" );
				
				foreach ( $list as $value ) {
					$attributes = $value->attributes ();
					$fromLeft = ( int ) $attributes ['left'];
					$toLeft = ( int ) ($attributes ['left'] + $attributes ['width']);
					
					$i = 0;
					$enc = false;
					foreach ($headerList as $header) {
						$headerFromLeft = $header->left;
						$headerToLeft = $header->left + $header->width;
						
						$between1 = self::between((int)$headerFromLeft, (int)$headerToLeft, (int)$fromLeft);
						$between2 = self::between((int)$headerFromLeft, (int)$headerToLeft, (int)$toLeft);
						
						$between3 = self::between((int)$fromLeft, (int)$toLeft, (int)$headerFromLeft);
						$between4 = self::between((int)$fromLeft, (int)$toLeft, (int)$headerToLeft);
						
						if ($between1 || $between2  || $between3 || $between4) {
							$transaction [$header->name] = ( string ) $value;
							break;
						}
					}
				}
				$transationList [] = $transaction;
			}
		}
		return $transationList;
	}
	
	function between($from,$to, $value){
		return $from <= $value && $value <= $to;
	}
}
