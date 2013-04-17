<?php
/**
 * Api Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_FubraWorldSales
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_FubraWorldSales extends Oara_Network {

	public $_cbService = null;
	/**
	 * Constructor.
	 * @param $credentials
	 * @return Oara_Network_Publisher_CarHireCenter
	 */
	public function __construct($credentials) {
		$this->_cbService = new Cb_Client("https://secure.clearbooks.co.uk/", $credentials['password']);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = true;
		try{
			$entityList = $this->_cbService->listEntities();
		} catch(Exception $e){
			$connection = false;
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
		$obj['name'] = 'Fubra World Sales';
		$merchantList[] = $obj;
		
		return $merchantList;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();
		$invoiceList = $this->_cbService->listInvoices(0, "sales", "unpaid", $dStartDate->toString("yyyy-MM-dd"));
		$invoiceList = array_merge($invoiceList, $this->_cbService->listInvoices(0, "sales", "paid", $dStartDate->toString("yyyy-MM-dd")));
		foreach ($invoiceList as $invoice){
			if (substr($invoice->reference,0,3) == "FWS"){
				$invoiceDate = new Zend_Date($invoice->dateCreated, "yyyy-MM-dd");
				if ($invoiceDate->compare($dStartDate) >= 0 && $invoiceDate->compare($dEndDate) <= 0){
					$transaction = array();
					$transaction['merchantId'] = 1;
					$transaction['unique_id'] = $invoice->reference;
					$transaction['date'] = $invoice->dateCreated;
					
					$totalAmount = 0;
					foreach ($invoice->items as $item){
						$totalAmount += $item->unitPrice * $item->quantity;
					}
					
					$transaction['amount'] = (double) $totalAmount;
					$transaction['commission'] = (double) $totalAmount;
					if ($invoice->status == "approved"){
						$transaction['status'] = Oara_Utilities::STATUS_PENDING;
					} else if ($invoice->status == "paid"){
						$transaction['status'] = Oara_Utilities::STATUS_PAID;
					} else if ($invoice->status == "voided"){
						$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
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
