<?php

/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_ItunesConnect
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_ItunesConnect extends Oara_Network {
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;
	private $_constructResult = null;
	private $_user = null;
	private $_password = null;

	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_itunesConnect
	 */
	public function __construct($credentials) {
		$user = $credentials['user'];
		$password = $credentials['password'];

		$url = "https://itunesconnect.apple.com/WebObjects/iTunesConnect.woa/wo/2.1.9.3.5.2.1.1.3.1.1";

		$valuesLogin = array(
		new Oara_Curl_Parameter('theAccountName', $user),
		new Oara_Curl_Parameter('theAccountPW', $password),
		new Oara_Curl_Parameter('1.Continue.x', "56"),
		new Oara_Curl_Parameter('1.Continue.y', "10"),
		new Oara_Curl_Parameter('theAuxValue', ""),
		);

		$this->_user = $user;
		$this->_password = $password;
		$this->_apiPassword = $credentials['apiPassword'];
		//$this->_client = new Oara_Curl_Access($url, $valuesLogin, $credentials);
		//$this->_constructResult =  $this->_client->getConstructResult();
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {

		$connection = false;

		//if (preg_match("/Sign Out/", $this->_constructResult)) {
		$connection = true;
		//}
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
		$obj['name'] = "Itunes Connect";
		$obj['url'] = "https://itunesconnect.apple.com";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();


		$dirDestination = realpath(dirname(__FILE__)).'/../../data/pdf';

		$now = new Zend_Date();
		if ($now->toString("yyyy-MM") != $dStartDate->toString("yyyy-MM")){



			$fileName = "S_M_{$this->_apiPassword}_".$dStartDate->toString("yyyyMM").".txt.gz";
			// Raising this value may increase performance
			$buffer_size = 4096; // read 4kb at a time
			$local_file = $dirDestination."/".$fileName;
			$url = "http://affjet.dc.fubra.net/tools/ItunesConnect/ic.php?user=".urlencode($this->_user)."&password=".urlencode($this->_password)."&apiPassword=".urlencode($this->_apiPassword)."&type=M&date=".$dStartDate->toString("yyyyMM");
			\file_put_contents($local_file, file_get_contents($url));

			$out_file_name = \str_replace('.gz', '', $local_file);

			// Open our files (in binary mode)
			$file = \gzopen($local_file, 'rb');
			if ($file != null){


				$out_file = \fopen($out_file_name, 'wb');

				// Keep repeating until the end of the input file
				while(!\gzeof($file)) {
					// Read buffer-size bytes
					// Both fwrite and gzread and binary-safe
					\fwrite($out_file, \gzread($file, $buffer_size));
				}

				// Files are done, close files
				\fclose($out_file);
				\gzclose($file);


				unlink($local_file);

				$salesReport = file_get_contents($out_file_name);
				$salesReport = explode("\n", $salesReport);
				for ($i = 1; $i < count($salesReport) - 1; $i++) {

					$row = str_getcsv($salesReport[$i], "\t");

					$sub = false;
					if ($row[7] < 0){
						$sub = true;
						$row[7] = abs($row[7]);
					}
					for ($j=0 ; $j < $row[7]; $j++){

						$obj = array();
						$obj['merchantId'] = "1";
						$obj['date'] = $dEndDate->toString("yyyy-MM-dd")." 00:00:00";
						$obj['custom_id'] = $row[4];
						$comission = 0.3;
						if ($row[2] == "FUBRA1PETROLPRICES1" || $row[2] == "com.fubra.petrolpricespro.subscriptionYear"){
							$value = 2.99;
							$obj['amount'] = Oara_Utilities::parseDouble($value);
							$obj['commission'] = Oara_Utilities::parseDouble($value - ($value*$comission));
						} else if ($row[2] == "FUBRA1WORLDAIRPORTCODES1"){

							if ($obj['date'] < "2013-04-23 00:00:00"){
								$value = 0.69;
								$obj['amount'] = Oara_Utilities::parseDouble($value);
								$obj['commission'] = Oara_Utilities::parseDouble($value - ($value*$comission));
							} else {
								$value = 1.49;
								$obj['amount'] = Oara_Utilities::parseDouble($value);
								$obj['commission'] = Oara_Utilities::parseDouble($value - ($value*$comission));
							}
						} else {
							throw new Exception("APP not found {$row[2]}");
						}
							
						if ($sub){
							$obj['amount'] = -$obj['amount'];
							$obj['commission'] = -$obj['commission'];
						}

						$obj['status'] = Oara_Utilities::STATUS_CONFIRMED;

						$totalTransactions[] = $obj;
					}
				}
				unlink($out_file_name);
			}

		} else {

			$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
			$dateArraySize = sizeof($dateArray);
			for ($z = 0; $z < $dateArraySize; $z++) {
				$transactionDate = $dateArray[$z];


				$fileName = "S_D_{$this->_apiPassword}_".$transactionDate->toString("yyyyMMdd").".txt.gz";


				// Raising this value may increase performance
				$buffer_size = 4096; // read 4kb at a time
				$local_file = $dirDestination."/".$fileName;
				$url = "http://affjet.dc.fubra.net/tools/ItunesConnect/ic.php?user=".urlencode($this->_user)."&password=".urlencode($this->_password)."&apiPassword=".urlencode($this->_apiPassword)."&type=D&date=".$transactionDate->toString("yyyyMMdd");
				\file_put_contents($local_file, file_get_contents($url));

				$out_file_name = \str_replace('.gz', '', $local_file);

				// Open our files (in binary mode)
				$file = \gzopen($local_file, 'rb');
				if ($file != null){


					$out_file = \fopen($out_file_name, 'wb');

					// Keep repeating until the end of the input file
					while(!\gzeof($file)) {
						// Read buffer-size bytes
						// Both fwrite and gzread and binary-safe
						\fwrite($out_file, \gzread($file, $buffer_size));
					}

					// Files are done, close files
					\fclose($out_file);
					\gzclose($file);


					unlink($local_file);

					$salesReport = file_get_contents($out_file_name);
					$salesReport = explode("\n", $salesReport);
					for ($i = 1; $i < count($salesReport) - 1; $i++) {

						$row = str_getcsv($salesReport[$i], "\t");

						$sub = false;
						if ($row[7] < 0){
							$sub = true;
							$row[7] = abs($row[7]);
						}
						for ($j=0 ; $j < $row[7]; $j++){

							$obj = array();
							$obj['merchantId'] = "1";
							$obj['date'] = $transactionDate->toString("yyyy-MM-dd")." 00:00:00";
							$obj['custom_id'] = $row[4];
							if ($row[2] == "FUBRA1PETROLPRICES1" || $row[2] == "com.fubra.petrolpricespro.subscriptionYear"){
								$value = 2.99;
								$comission = 0.3;
								$obj['amount'] = Oara_Utilities::parseDouble($value);
								$obj['commission'] = Oara_Utilities::parseDouble($value - ($value*$comission));
							} else if ($row[2] == "FUBRA1WORLDAIRPORTCODES1"){

								$comission = 0.3;
								if ($obj['date'] < "2013-04-23 00:00:00"){
									$value = 0.69;
									$obj['amount'] = Oara_Utilities::parseDouble($value);
									$obj['commission'] = Oara_Utilities::parseDouble($value - ($value*$comission));
								} else {
									$value = 1.49;
									$obj['amount'] = Oara_Utilities::parseDouble($value);
									$obj['commission'] = Oara_Utilities::parseDouble($value - ($value*$comission));
								}
							} else {
								throw new Exception("APP not found {$row[2]}");
							}

							if ($sub){
								$obj['amount'] = -$obj['amount'];
								$obj['commission'] = -$obj['commission'];
							}

							$obj['status'] = Oara_Utilities::STATUS_CONFIRMED;

							$totalTransactions[] = $obj;
						}
					}
					unlink($out_file_name);

				}


			}

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



	/**
	 *
	 * Function that Convert from a table to Csv
	 * @param unknown_type $html
	 */
	private function htmlToCsv($html) {
		$html = str_replace(array("\t", "\r", "\n"), "", $html);
		$csv = "";
		$dom = new Zend_Dom_Query($html);
		$results = $dom->query('tr');
		$count = count($results); // get number of matches: 4
		foreach ($results as $result) {
			$tdList = $result->childNodes;
			$tdNumber = $tdList->length;
			if ($tdNumber > 0) {
				for ($i = 0; $i < $tdNumber; $i++) {
					$value = $tdList->item($i)->nodeValue;
					if ($i != $tdNumber - 1) {
						$csv .= trim($value).";";
					} else {
						$csv .= trim($value);
					}
				}
				$csv .= "\n";
			}
		}
		$exportData = str_getcsv($csv, "\n");
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