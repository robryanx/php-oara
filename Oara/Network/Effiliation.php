<?php
/**
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Efiliation
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Effiliation extends Oara_Network {
	/**
	 * Export Credentials
	 * @var array
	 */
	private $_credentials = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Effiliation
	 */
	public function __construct($credentials) {

		$this->_credentials = $credentials;

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;

		$content = file_get_contents('http://api.effiliation.com/api/transaction.csv?key='.$this->_credentials["apiPassword"]);
		if (!preg_match("/bad credentials !/", $content, $matches)) {
			$connection = true;
		}
		return $connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array();

		$content = @file_get_contents('http://api.effiliation.com/api/programmes.xml?key='.$this->_credentials["apiPassword"]);
		$xml = simplexml_load_string($content, null, LIBXML_NOERROR | LIBXML_NOWARNING);
		foreach ($xml->programme as $merchant) {
			if ((string) $merchant->etat == "inscrit") {
				$obj = array();
				$obj['cid'] = (string) $merchant->id_programme;
				$obj['name'] = (string) $merchant->nom;
				$obj['url'] = "";
				$merchants[] = $obj;
			}
		}
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();

		$content = file_get_contents('http://api.effiliation.com/api/transaction.csv?key='.$this->_credentials["apiPassword"].'&start='.$dStartDate->toString("dd/MM/yyyy").'&end='.$dEndDate->toString("dd/MM/yyyy").'&type=datetran');
		$exportData = str_getcsv($content, "\n");
		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], "|");
			if (in_array((int) $transactionExportArray[2], $merchantList)) {
				$transaction = Array();
				$merchantId = (int) $transactionExportArray[2];
				$transaction['merchantId'] = $merchantId;
				$transaction['date'] = $transactionExportArray[4];
				$transaction['unique_id'] = $transactionExportArray[9];

				if ($transactionExportArray[0] != null) {
					$transaction['custom_id'] = $transactionExportArray[0];
				}

				if ($transactionExportArray[8] == 'Valide') {
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				} else
					if ($transactionExportArray[8] == 'Attente') {
						$transaction['status'] = Oara_Utilities::STATUS_PENDING;
					} else
						if ($transactionExportArray[8] == 'Refusé') {
							$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
						}
				$transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[6]);
				$transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[7]);
				$totalTransactions[] = $transaction;
			}
		}
		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getOverviewList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$overviewArray = array();

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
}
