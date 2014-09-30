<?php
/**
 The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
 of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.

 Copyright (C) 2014  Fubra Limited
 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU Affero General Public License for more details.
 You should have received a copy of the GNU Affero General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.

 Contact
 ------------
 Fubra Limited <support@fubra.com> , +44 (0)1252 367 200
 **/
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
		$folder = realpath ( dirname ( COOKIES_BASE_DIR ) ) . '/pdf/';
		$totalTransactions = array();
		$valuesFromExport = array();
		$valuesFromExport[] = new Oara_Curl_Parameter('id', "-1");			
		$valuesFromExport[] = new Oara_Curl_Parameter('yearStart', $dStartDate->toString("yyyy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('monthStart', $dStartDate->toString("MM"));
		$valuesFromExport[] = new Oara_Curl_Parameter('dayStart', $dStartDate->toString("dd"));
		$valuesFromExport[] = new Oara_Curl_Parameter('yearEnd', $dEndDate->toString("yyyy"));
		$valuesFromExport[] = new Oara_Curl_Parameter('monthEnd', $dEndDate->toString("MM"));
		$valuesFromExport[] = new Oara_Curl_Parameter('dayEnd', $dEndDate->toString("dd"));
		
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://partnerprogramma.bol.com/partner/s/excelReport/orders?', $valuesFromExport);
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

		    
		   	$transaction = Array();
		   	$transaction['unique_id'] = $objWorksheet->getCellByColumnAndRow(0, $row)->getValue()."_".$objWorksheet->getCellByColumnAndRow(1, $row)->getValue();
		   	$transaction['merchantId'] = "1";
		   	
		   	$transactionDate = new Zend_Date($objWorksheet->getCellByColumnAndRow(2, $row)->getValue(), 'dd-MM-yyyy');
		   	$transaction['date'] = $transactionDate->toString("yyyy-MM-dd 00:00:00");
		   	
		   	$transaction['custom_id'] = $objWorksheet->getCellByColumnAndRow(8, $row)->getValue();
		
		   	
		   	if ($objWorksheet->getCellByColumnAndRow(14, $row)->getValue() == 'geaccepteerd') {
		   		$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
		   	} else
		   	if ($objWorksheet->getCellByColumnAndRow(14, $row)->getValue() == 'in behandeling') {
		   		$transaction['status'] = Oara_Utilities::STATUS_PENDING;
		   	} else
		   	if ($objWorksheet->getCellByColumnAndRow(14, $row)->getValue() == 'geweigerd: klik te oud' || $objWorksheet->getCellByColumnAndRow(14, $row)->getValue() == 'geweigerd') {
		   		$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
		   	} else {
		   		echo "new status ".$objWorksheet->getCellByColumnAndRow(14, $row)->getValue();
		   	}
		   	
		   	$transaction['amount'] = $objWorksheet->getCellByColumnAndRow(11, $row)->getValue();
		   	
		   	$transaction['commission'] = $objWorksheet->getCellByColumnAndRow(12, $row)->getValue();
		   	$totalTransactions[] = $transaction;
		    
		}
		unlink($my_file);

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

}
