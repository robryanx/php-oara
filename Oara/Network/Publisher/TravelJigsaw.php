<?php
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Tj
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_TravelJigsaw extends Oara_Network {
	/**
	 * Export client.
	 * @var Oara_Curl_Access
	 */
	private $_client = null;

	/**
	 * Transaction Export Parameters
	 * @var array
	 */
	private $_exportTransactionParameters = null;

	/**
	 * Constructor and Login
	 * @param $traveljigsaw
	 * @return Oara_Network_Publisher_Tj_Export
	 */
	public function __construct($credentials) {
		$user = $credentials['user'];
		$password = $credentials['password'];

		$loginUrl = 'http://affiliate.rentalcars.com/access?commit=true';

		$valuesLogin = array(new Oara_Curl_Parameter('login_username', $user),
			new Oara_Curl_Parameter('login_password', $password)
		);

		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;
		$urls = array();
		$urls[] = new Oara_Curl_Request('http://affiliate.rentalcars.com', array());
		$exportReport = $this->_client->get($urls);

		$dom = new Zend_Dom_Query($exportReport[0]);
		$results = $dom->query('#header_logout');
		$count = count($results);
		if ($count > 0) {
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
		$obj['cid'] = 1;
		$obj['name'] = 'Traveljigsaw';
		$obj['url'] = 'http://www.rentalcars.com/';
		$merchants[] = $obj;

		return $merchants;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = Array();

		$valuesFormExport = array();
		$valuesFormExport[] = new Oara_Curl_Parameter('date_start', $dStartDate->get(Zend_Date::TIMESTAMP));
		$valuesFormExport[] = new Oara_Curl_Parameter('date_end', $dEndDate->get(Zend_Date::TIMESTAMP));

		$urls = array();
		$urls[] = new Oara_Curl_Request('http://affiliate.rentalcars.com/booked_excel?', $valuesFormExport);
		$exportReport = $this->_client->get($urls);
		$exportTransactionList = new SimpleXMLElement($exportReport[0]);
		if (isset($exportTransactionList->Worksheet->Table->Row)) {

			for ($i = 3; $i < count($exportTransactionList->Worksheet->Table->Row) - 2; $i++) {
				$exportTransaction = $exportTransactionList->Worksheet->Table->Row[$i];

				$transactionDate = new Zend_Date($exportTransaction->Cell[0]->Data, "d MMM yyyy - HH:mm", 'en_US');
				if ($transactionDate->compare($dStartDate) >= 0 && $transactionDate->compare($dEndDate) <= 0) {

					$transaction = array();
					$transaction['unique_id'] = (string) $exportTransaction->Cell[5]->Data;
					$transaction['merchantId'] = 1;
					$transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
					$transaction['amount'] = (double) $exportTransaction->Cell[6]->Data;
					$transaction['commission'] = (double) $exportTransaction->Cell[7]->Data;

					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;

					$totalTransactions[] = $transaction;
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
