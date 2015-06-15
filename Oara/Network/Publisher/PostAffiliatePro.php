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
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_PureVPN
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_PostAffiliatePro extends Oara_Network {
	private $_credentials = null;
	/**
	 * Client
	 *
	 * @var unknown_type
	 */
	private $_session = null;

	/**
	 * Constructor and Login
	 *
	 * @param
	 *        	$credentials
	 * @return Oara_Network_Publisher_PureVPN
	 */
	public function __construct($credentials) {
		include realpath ( dirname ( __FILE__ ) )."/PostAffiliatePro/PapApi.class.php";
		$this->_credentials = $credentials;
	}

	/**
	 * Check the connection
	 */
	public function checkConnection() {
		// If not login properly the construct launch an exception
		$connection = true;
		$session = new Gpf_Api_Session("http://".$this->_credentials["domain"]."/scripts/server.php");
		if(!@$session->login( $this->_credentials ["user"], $this->_credentials ["password"], Gpf_Api_Session::AFFILIATE)) {
			$connection = false;
		}
		$this->_session = $session;

		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 *
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array ();

		$obj = array ();
		$obj ['cid'] = "1";
		$obj ['name'] = "Post Affiliate Pro ({$this->_credentials["domain"]})";
		$merchants [] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array ();

			
		//----------------------------------------------
		// get recordset of list of transactions
		$request = new Pap_Api_TransactionsGrid($this->_session);
		// set filter
		$request->addFilter('dateinserted', 'D>=', $dStartDate->toString("yyyy-MM-dd"));
		$request->addFilter('dateinserted', 'D<=', $dEndDate->toString("yyyy-MM-dd"));
		$request->setLimit(0, 100);
		$request->setSorting('orderid', false);
		$request->sendNow();
		$grid = $request->getGrid();
		$recordset = $grid->getRecordset();
		// iterate through the records
		foreach($recordset as $rec) {
			$transaction = Array ();
			$transaction ['merchantId'] = 1;
			$transaction ['uniqueId'] = $rec->get('orderid');
			$transactionDate = new Zend_Date ( $rec->get('dateinserted'), 'yyyy-MM-dd HH:mm:ss', 'en' );
			$transaction ['date'] = $transactionDate->toString ( "yyyy-MM-dd HH:mm:ss" );
			unset ( $transactionDate );
			$transaction ['status'] = Oara_Utilities::STATUS_CONFIRMED;

			$transaction ['amount'] = Oara_Utilities::parseDouble (  $rec->get('totalcost') );
			$transaction ['commission'] = Oara_Utilities::parseDouble (  $rec->get('commission') );
			$totalTransactions [] = $transaction;
		}
		//----------------------------------------------
		// in case there are more than 30 records total
		// we should load and display the rest of the records
		// in the cycle
		$totalRecords = $grid->getTotalCount();
		$maxRecords = $recordset->getSize();
		if ($maxRecords > 0) {
			$cycles = ceil($totalRecords / $maxRecords);
			for($i=1; $i<$cycles; $i++) {
				// now get next 30 records
				$request->setLimit($i * $maxRecords, $maxRecords);
				$request->sendNow();
				$recordset = $request->getGrid()->getRecordset();
				// iterate through the records
				foreach($recordset as $rec) {
					$transaction = Array ();
					$transaction ['merchantId'] = 1;
					$transaction ['uniqueId'] = $rec->get('orderid');
					$transactionDate = new Zend_Date ( $rec->get('dateinserted'), 'yyyy-MM-dd HH:mm:ss', 'en' );
					$transaction ['date'] = $transactionDate->toString ( "yyyy-MM-dd HH:mm:ss" );
					unset ( $transactionDate );
					
					
					if ($rec->get('rstatus') == 'D'){
						$transaction ['status'] = Oara_Utilities::STATUS_DECLINED;
					} else if ($rec->get('rstatus') == 'P'){
						$transaction ['status'] = Oara_Utilities::STATUS_PENDING;
					} else if ($rec->get('rstatus') == 'A'){
						$transaction ['status'] = Oara_Utilities::STATUS_CONFIRMED;
					}
		
					$transaction ['amount'] = Oara_Utilities::parseDouble (  $rec->get('totalcost') );
					$transaction ['commission'] = Oara_Utilities::parseDouble (  $rec->get('commission') );
					$totalTransactions [] = $transaction;
				}
			}
		}
		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array ();

		return $paymentHistory;
	}
}
