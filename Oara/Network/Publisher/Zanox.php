<?php
/**
 * Api Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Zn
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Zanox extends Oara_Network {
	/**
	 * Soap client.
	 */
	private $_apiClient = null;

	/**
	 * page Size.
	 */
	private $_pageSize = 50;

	/**
	 * Constructor.
	 * @param $affiliateWindow
	 * @return Oara_Network_Publisher_Zn_Api
	 */
	public function __construct($credentials) {

		$api = Oara_Network_Publisher_Zanox_Zapi_ApiClient::factory(PROTOCOL_SOAP, VERSION_2011_03_01);

		$connectId = $credentials['connectId'];
		$secretKey = $credentials['secretKey'];
		$publicKey = $credentials['publicKey'];

		$api->setConnectId($connectId);
		$api->setSecretKey($secretKey);
		$api->setPublicKey($publicKey);

		$this->_apiClient = $api;

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = true;
		try {
			$profile = $this->_apiClient->getProfile();
		} catch (Exception $e) {
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
	
		$programApplicationList = $this->_apiClient->getProgramApplications(null, null, "confirmed", 0, $this->_pageSize);
		if ($programApplicationList->total > 0) {
			$iterationProgramApplicationList = self::calculeIterationNumber($programApplicationList->total, $this->_pageSize);
			for ($j = 0; $j < $iterationProgramApplicationList; $j++) {

				$programApplicationList = $this->_apiClient->getProgramApplications(null, null, "confirmed", $j, $this->_pageSize);
				foreach ($programApplicationList->programApplicationItems->programApplicationItem as $programApplication) {
					if (!isset($merchantList[$programApplication->program->id])) {
						$obj = array();
						$obj['cid'] = $programApplication->program->id;
						$obj['name'] = $programApplication->program->_;
						$merchantList[$programApplication->program->id] = $obj;
					}
				}

			}
		}
		return $merchantList;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId,$dStartDate,$dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();

		$dStartDate = clone $dStartDate;
		$dStartDate->setHour("00");
		$dStartDate->setMinute("00");
		$dStartDate->setSecond("00");
		$dEndDate = clone $dEndDate;
		$dEndDate->setHour("23");
		$dEndDate->setMinute("59");
		$dEndDate->setSecond("59");

		$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
		foreach ($dateArray as $date) {
			$totalAuxTransactions = array();
			$transactionList = $this->_apiClient->getSales($date->toString("yyyy-MM-dd"), 'trackingDate', null, null, null, 0, $this->_pageSize);
			if ($transactionList->total > 0) {
				$iteration = self::calculeIterationNumber($transactionList->total, $this->_pageSize);
				for ($i = 0; $i < $iteration; $i++) {
					$transactionList = $this->_apiClient->getSales($date->toString("yyyy-MM-dd"), 'trackingDate', null, null, null, $i, $this->_pageSize);
					$totalAuxTransactions = array_merge($totalAuxTransactions, $transactionList->saleItems->saleItem);
					unset($transactionList);
					gc_collect_cycles();
				}

			}
			$leadList = $this->_apiClient->getLeads($date->toString("yyyy-MM-dd"), 'trackingDate', null, null, null, 0, $this->_pageSize);
			if ($leadList->total > 0) {
				$iteration = self::calculeIterationNumber($leadList->total, $this->_pageSize);
				for ($i = 0; $i < $iteration; $i++) {
					$leadList = $this->_apiClient->getLeads($date->toString("yyyy-MM-dd"), 'trackingDate', null, null, null, $i, $this->_pageSize);
					$totalAuxTransactions = array_merge($totalAuxTransactions, $leadList->leadItems->leadItem );
					unset($leadList);
					gc_collect_cycles();
				}
			}

			foreach ($totalAuxTransactions as $transaction) {

				if (in_array($transaction->program->id, $merchantList)) {
					$obj = array();
					
					$obj['currency'] = $transaction->currency;
					
					if ($transaction->reviewState == 'confirmed') {
						$obj['status'] = Oara_Utilities::STATUS_CONFIRMED;
					} else
						if ($transaction->reviewState == 'open' || $transaction->reviewState == 'approved') {
							$obj['status'] = Oara_Utilities::STATUS_PENDING;
						} else
							if ($transaction->reviewState == 'rejected') {
								$obj['status'] = Oara_Utilities::STATUS_DECLINED;
							}
					if (!isset($transaction->amount) || $transaction->amount == 0) {
						$obj['amount'] = $transaction->commission;
					} else {
						$obj['amount'] = $transaction->amount;
					}

					if (isset($transaction->gpps) && $transaction->gpps != null) {
						foreach ($transaction->gpps->gpp as $gpp) {
							if ($gpp->id == "zpar0") {
								if (strlen($gpp->_) > 100) {
									$gpp->_ = substr($gpp->_, 0, 100);
								}
								$obj['custom_id'] = $gpp->_;
							}
						}
					}
					$obj['unique_id'] = $transaction->id;
					$obj['commission'] = $transaction->commission;
					$transactionDate = new Zend_Date($transaction->trackingDate, "yyyy-MM-dd HH:mm:ss");
					$obj['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
					$obj['merchantId'] = $transaction->program->id;
					$totalTransactions[] = $obj;
				}

			}
			unset($totalAuxTransactions);
			gc_collect_cycles();
		}
		return $totalTransactions;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getOverviewList($merchantId,$dStartDate,$dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalOverview = Array();
		//At first, we need to be sure that there are some data.
		$auxStartDate = clone $dStartDate;
		$auxStartDate->setHour("00");
		$auxStartDate->setMinute("00");
		$auxStartDate->setSecond("00");
		$auxEndDate = clone $dEndDate;
		$auxEndDate->setHour("23");
		$auxEndDate->setMinute("59");
		$auxEndDate->setSecond("59");
		$auxEndDate->addDay(1);

		$transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);

		foreach ($merchantList as $merchantId) {
			$overviewList = $this->_apiClient->getReportBasic($auxStartDate->toString("yyyy-MM-dd"), $auxEndDate->toString("yyyy-MM-dd"), 'trackingDate', null, $merchantId, null, null, null, null, array('day'));
			if ($overviewList->total > 0) {
				foreach ($overviewList->reportItems->reportItem as $overview) {
					$obj = array();
					$obj['merchantId'] = $merchantId;
					$overviewDate = new Zend_Date($overview->day, "yyyy-MM-dd");
					$obj['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");

					$obj['impression_number'] = $overview->total->viewCount;
					$obj['click_number'] = $overview->total->clickCount;
					$obj['transaction_number'] = 0;

					$obj['transaction_confirmed_commission'] = 0;
					$obj['transaction_confirmed_value'] = 0;
					$obj['transaction_pending_commission'] = 0;
					$obj['transaction_pending_value'] = 0;
					$obj['transaction_declined_commission'] = 0;
					$obj['transaction_declined_value'] = 0;
					$obj['transaction_paid_value'] = 0;
					$obj['transaction_paid_commission'] = 0;
					$transactionDateArray = Oara_Utilities::getDayFromArray($obj['merchantId'], $transactionArray, $overviewDate, true);
					foreach ($transactionDateArray as $transaction) {
						$obj['transaction_number']++;
						if ($transaction['status'] == Oara_Utilities::STATUS_CONFIRMED) {
							$obj['transaction_confirmed_value'] += $transaction['amount'];
							$obj['transaction_confirmed_commission'] += $transaction['commission'];
						} else
							if ($transaction['status'] == Oara_Utilities::STATUS_PENDING) {
								$obj['transaction_pending_value'] += $transaction['amount'];
								$obj['transaction_pending_commission'] += $transaction['commission'];
							} else
								if ($transaction['status'] == Oara_Utilities::STATUS_DECLINED) {
									$obj['transaction_declined_value'] += $transaction['amount'];
									$obj['transaction_declined_commission'] += $transaction['commission'];
								} else
									if ($transaction['status'] == Oara_Utilities::STATUS_PAID) {
										$obj['transaction_paid_value'] += $transaction['amount'];
										$obj['transaction_paid_commission'] += $transaction['commission'];
									}
					}
					if (Oara_Utilities::checkRegister($obj)) {
						$totalOverview[] = $obj;
					}
				}
			}
			unset($overviewList);
			gc_collect_cycles();
		}

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
				$totalOverview[] = $overview;
			}
		}
		return $totalOverview;
	}
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */

	public function getPaymentHistory() {
		$paymentHistory = array();
		$paymentList = $this->_apiClient->getPayments(0, $this->_pageSize);

		if ($paymentList->total > 0) {
			$iteration = self::calculeIterationNumber($paymentList->total, $this->_pageSize);
			for ($j = 0; $j < $iteration; $j++) {
				$paymentList = $this->_apiClient->getPayments($j, $this->_pageSize);
				foreach ($paymentList->paymentItems->paymentItem as $payment) {
					$obj = array();
					$paymentDate = new Zend_Date($payment->createDate, "yyyy-MM-ddTHH:mm:ss");
					$obj['method'] = 'BACS';
					$obj['pid'] = $paymentDate->toString("yyyyMMddHHmmss");
					$obj['value'] = $payment->amount;

					$obj['date'] = $paymentDate->toString("yyyy-MM-dd HH:mm:ss");

					$paymentHistory[] = $obj;
				}
			}
		}
		return $paymentHistory;
	}

	/**
	 * Calculate the number of iterations needed
	 * @param $rowAvailable
	 * @param $rowsReturned
	 */
	private function calculeIterationNumber($rowAvailable, $rowsReturned) {
		$iterationDouble = (double) ($rowAvailable / $rowsReturned);
		$iterationInt = (int) ($rowAvailable / $rowsReturned);
		if ($iterationDouble > $iterationInt) {
			$iterationInt++;
		}
		return $iterationInt;
	}
}
