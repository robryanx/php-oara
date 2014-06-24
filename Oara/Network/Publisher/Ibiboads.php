<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Ibiboads
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Ibiboads extends Oara_Network {
	
	/**
	 * Client
	 *
	 * @var unknown_type
	 */
	private $_client = null;

	
	/**
	 * Affiliate ID
	 * @var unknown_type
	 */
	private $_affiliateID = null;

	/**
	 * Constructor and Login
	 * @param $cartrawler
	 * @return Oara_Network_Publisher_Tv_Export
	 */
	public function __construct($credentials) {

		$user = $credentials['user'];
		$password = $credentials['password'];
		
		//webpage uses javascript hex_md5 to encode the password
		$valuesLogin = array(
		new Oara_Curl_Parameter('username', $user),
		new Oara_Curl_Parameter('password', md5($password)),
		);
		
		$cookies = realpath(dirname(__FILE__)).'/../../data/curl/'.$credentials['cookiesDir'].'/'.$credentials['cookiesSubDir'].'/'.$credentials["cookieName"].'_cookies.txt';
		unlink($cookies);
		$this->_options = array (
				CURLOPT_USERAGENT => "Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:26.0) Gecko/20100101 Firefox/26.0",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FAILONERROR => true,
				CURLOPT_COOKIEJAR => $cookies,
				CURLOPT_COOKIEFILE => $cookies,
				CURLOPT_HTTPAUTH => CURLAUTH_ANY,
				CURLOPT_AUTOREFERER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_HEADER => false,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTPHEADER => array('Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8','Accept-Language: es,en-us;q=0.7,en;q=0.3','Accept-Encoding: gzip, deflate','Connection: keep-alive', 'Cache-Control: max-age=0'),
				CURLOPT_ENCODING => "gzip",
				CURLOPT_VERBOSE => false
		);
		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL, "http://adsadmin.ibibo.com/ad/login.php" );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );
		
		$dom = new Zend_Dom_Query($html);
		$hidden = $dom->query('input[type="hidden"]');

		foreach ($hidden as $values) {
			$valuesLogin[] = new Oara_Curl_Parameter($values->getAttribute("name"), $values->getAttribute("value"));
		}
		
		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL, "http://adsadmin.ibibo.com/ad/login.php" );
		$options [CURLOPT_POST] = true;
		$arg = array ();
		foreach ( $valuesLogin as $parameter ) {
			$arg [] = $parameter->getKey () . '=' . urlencode ( $parameter->getValue () );
		}
		$options [CURLOPT_POSTFIELDS] = implode ( '&', $arg );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );
		
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
		
		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL, 'http://adsadmin.ibibo.com/ad/publisher/account-index.php' );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );
			
		if (preg_match("/logout/", $html, $matches)) {
			$connection = true;
			
			$dom = new Zend_Dom_Query($html);
			$results = $dom->query('#oaNavigationTabs li div div');
			$finished = false;
			foreach ($results as $result) {
				$linkList = $result->getElementsByTagName('a');
				if ($linkList->length > 0) {
					$attrs = $linkList->item(0)->attributes;
					
					foreach ($attrs as $attrName => $attrNode) {
						if (!$finished && $attrName = 'href') {
							$parseUrl = trim($attrNode->nodeValue);
							$parts = parse_url($parseUrl);
							parse_str($parts['query'], $query);
							$this->_affiliateID = $query['affiliateid'];
							$finished = true;
							if(!is_numeric($this->_affiliateID)){
								throw new Exception ( "Affiliate ID not found" );
							}
						}
					}
				}
			}			
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
		$obj['cid'] = 1;
		$obj['name'] = 'Ibiboads';
		$merchants[] = $obj;

		return $merchants;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = Array();		
		
		$valuesFromExport = array(
				new Oara_Curl_Parameter('entity', 'conversions'),
				new Oara_Curl_Parameter('affiliateid', $this->_affiliateID),
				new Oara_Curl_Parameter('statsBreakdown', 'day'),
				new Oara_Curl_Parameter('period_preset', 'today'),
				new Oara_Curl_Parameter('period_start', $dStartDate->toString ( "yyyy-MM-dd" )), //'2014-06-20'
				new Oara_Curl_Parameter('period_end', $dEndDate->toString ( "yyyy-MM-dd" )), //'2014-06-20'
				new Oara_Curl_Parameter('listorder', 'key'),
				new Oara_Curl_Parameter('orderdirection', 'up'),
				new Oara_Curl_Parameter('setPerPage', '15'),
				new Oara_Curl_Parameter('marketing_type', '-1'),
				new Oara_Curl_Parameter('expand', 'all')
		);
		
		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL, 'http://adsadmin.ibibo.com/ad/publisher/stats.php' );
		$arg = array ();
		foreach ( $valuesFromExport as $parameter ) {
			$arg [] = $parameter->getKey () . '=' . urlencode ( $parameter->getValue () );
		}
		$options [CURLOPT_POSTFIELDS] = implode ( '&', $arg );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );
		
		$valuesFromExport = array(
				new Oara_Curl_Parameter('period_preset', 'specific'),
				new Oara_Curl_Parameter('period_start', $dStartDate->toString ( "yyyy-MM-dd" )), //'2014-06-20'
				new Oara_Curl_Parameter('period_end', $dEndDate->toString ( "yyyy-MM-dd" )), //'2014-06-20'				
				new Oara_Curl_Parameter('scope_advertiser', 'all'),
				new Oara_Curl_Parameter('scope_publisher', $this->_affiliateID),
				new Oara_Curl_Parameter('sheets[performance_by_day]', '1'),
				new Oara_Curl_Parameter('sheets[connection_detail]', '1'),				
				new Oara_Curl_Parameter('plugin', 'reports:oxReportsStandard:conversionTrackingReport')
		);
		
		$dom = new Zend_Dom_Query($html);
		$hidden = $dom->query('input[type="hidden"]');
		
		foreach ($hidden as $values) {
			$valuesFromExport[] = new Oara_Curl_Parameter($values->getAttribute("name"), $values->getAttribute("value"));
		}
		
		$rch = curl_init ();
		$options = $this->_options;
		curl_setopt ( $rch, CURLOPT_URL, 'http://adsadmin.ibibo.com/ad/publisher/report-generate.php' );
		$arg = array ();
		foreach ( $valuesFromExport as $parameter ) {
			$arg [] = $parameter->getKey () . '=' . urlencode ( $parameter->getValue () );
		}
		$options [CURLOPT_POSTFIELDS] = implode ( '&', $arg );
		curl_setopt_array ( $rch, $options );
		$html = curl_exec ( $rch );
		curl_close ( $rch );
		
		$folder = realpath(dirname(__FILE__)).'/../../data/pdf/';
		$my_file = $folder.mt_rand().'.xls';
		
		$handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
		fwrite($handle, $html);
		fclose($handle);
		
		/////quitar
		$my_file = $folder.'prueba.xls';
		/////
			
		$objReader = PHPExcel_IOFactory::createReader('Excel5');
		$objReader->setReadDataOnly(true);
			
		$objPHPExcel = $objReader->load($my_file);
		$objWorksheet = $objPHPExcel->setActiveSheetIndexByName('Connection Detail');
			
		$highestRow = $objWorksheet->getHighestRow();
		$highestColumn = $objWorksheet->getHighestColumn();
			
		$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
		
		$row = 9;
		while ($row <= $highestRow){

			$campaignName = $objWorksheet->getCellByColumnAndRow(1, $row)->getValue();
			++$row;
			
			$column = 1;
			$atributesNotFound = 4;
			$columnConnectionDate = null;
			$columnTransactionID = null;
			$columnSaleValue = null;
			$columnStatus = null;
			$numColumns = ord($highestColumn) - ord('A') + 1;
			
			while ($atributesNotFound > 0 && $column <= $numColumns){
				$textColumnHeader = $objWorksheet->getCellByColumnAndRow($column, $row)->getValue();
				if ($textColumnHeader == 'Connection Date / Time'){
					--$atributesNotFound;
					$columnConnectionDate = $column;
				}else if ($textColumnHeader == 'Transaction_ID' || $textColumnHeader == 'TransactionID' || $textColumnHeader == 'Transaction_Id'){ 
					--$atributesNotFound;
					$columnTransactionID = $column;
				}else if ($textColumnHeader == 'Sale_Value' || $textColumnHeader == 'sale_amt' || $textColumnHeader == 'Sale_value' || $textColumnHeader == 'Sale_Amount'){
					--$atributesNotFound;
					$columnSaleValue = $column;
				}else if ($textColumnHeader == 'Approval Status'){
					--$atributesNotFound;
					$columnStatus = $column;
				}
				++$column;			
			}
			$campaingWithoutAmount = false;
			if ($atributesNotFound > 0 && !is_null($columnSaleValue)){
				throw new Exception ( "Some atribute transaction not found {row: $row}" );
			}else if ($atributesNotFound > 0 && is_null($columnSaleValue)){
				$campaingWithoutAmount = true;
			}			
			++$row;				 
			$campaingFinished = false;
			while(!$campaingFinished && $row <= $highestRow){
				$transaction = Array();
				
				if( $objWorksheet->getCellByColumnAndRow($columnStatus, $row)->getValue() == ''){
					$campaingFinished = true;
					++$row;
					++$row;
				}else if($campaingWithoutAmount){
					++$row;
				}else{
					$transaction['merchantId'] = "1";
					$transaction['date'] = $objWorksheet->getCellByColumnAndRow($columnConnectionDate, $row)->getValue();	
					$transaction['amount'] = $objWorksheet->getCellByColumnAndRow($columnSaleValue, $row)->getValue();
					$transaction['commission'] = $objWorksheet->getCellByColumnAndRow($columnSaleValue, $row)->getValue();
					$transactionStatus = $objWorksheet->getCellByColumnAndRow($columnStatus, $row)->getValue();
					if ($transactionStatus == "Accepted" || $transactionStatus == "Approved") {
						$transaction ['status'] = Oara_Utilities::STATUS_CONFIRMED;
					} else if ($transactionStatus == "Pending" || $transactionStatus == "En attente") {
						$transaction ['status'] = Oara_Utilities::STATUS_PENDING;
					} else if ($transactionStatus == "Rejected" || $transactionStatus == "Ignore" || $transactionStatus == "Duplicate" || $transactionStatus == "Disapproved" || $transactionStatus == "Ignored") {
						$transaction ['status'] = Oara_Utilities::STATUS_DECLINED;
					} else {
						throw new Exception ( "New status found {$transactionStatus}" );
					}
					$totalTransactions[] = $transaction;
					++$row;
				}
			}		
		}
		unlink($my_file);
		
		return $totalTransactions;

	}

	/**
	 *
	 * Function that Convert from a table to Csv
	 * @param unknown_type $html
	 */
	private function htmlToCsv($html) {
		$html = str_replace ( array (
				"\t",
				"\r",
				"\n" 
		), "", $html );
		$csv = "";
		$dom = new Zend_Dom_Query ( $html );
		$results = $dom->query ( 'tr' );
		$count = count ( $results ); // get number of matches: 4
		foreach ( $results as $result ) {
			
			$domTd = new Zend_Dom_Query ( self::DOMinnerHTML($result) );
			$resultsTd = $domTd->query ( 'td' );
			$countTd = count ( $resultsTd );
			$i = 0;
			foreach( $resultsTd as $resultTd) {
				$value = $resultTd->nodeValue;
				if ($i != $countTd - 1) {
					$csv .= trim ( $value ) . ";,";
				} else {
					$csv .= trim ( $value );
				}
				$i++;
			}
			$csv .= "\n";
		}
		$exportData = str_getcsv ( $csv, "\n" );
		return $exportData;
	}
	/**
	 *
	 * Function that returns the innet HTML code
	 * @param unknown_type $element
	 */
	private function DOMinnerHTML($element) {
		$innerHTML = "";
		$children = $element->childNodes;
		foreach ($children as $child) {
			$tmp_dom = new DOMDocument();
			$tmp_dom->appendChild($tmp_dom->importNode($child, true));
			$innerHTML .= trim($tmp_dom->saveHTML());
		}
		return $innerHTML;
	}

}
