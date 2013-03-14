<?php
/**
 * Data Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_AffJetNet
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_AffJetNet extends Oara_Network {
	//Db user
	private $_user = null;
	//Db pass
	private $_password = null;
	//Db partnerId
	private $_partnerId = null;
	//User id
	private $_userId = null;
	//Is admin of the partnership?
	private $_isAdmin = false;
	
	private $_isUser = false;
	/**
	 * Constructor and Login
	 * @param $credentials
	 */
	public function __construct($credentials) {
		$this->_user = $credentials['user'];
		$this->_password = $credentials['password'];
		$this->_partnerId = $credentials['partnerId'];
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = true;

		try {
			Db_Utilities::initDoctrineAffjetNet();
			$affjetNetUserDao = Dao_Factory_Doctrine::createDoctrineDaoInstance('AffjetNetUser');
			$criteriaList = array();
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('AffjetNetPartner->id', $this->_partnerId);
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('user', $this->_user);
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('pass', $this->_password);

			$affjetNetUser = $affjetNetUserDao->findBy($criteriaList)->getFirst();
			if ($affjetNetUser == null) {
				$connection = false;
			} else {
				$this->_userId = $affjetNetUser->id;
				if ($affjetNetUser->AffjetNetRole->name == "admin") {
					$this->_isAdmin = true;
				} else {
					$this->_isUser = true;
				}
			}
		} catch (Exception $e) {
			$connection = false;
		}
		return $connection;
	}
	
	/**
	 * IsAdmin
	 */
	public function isAdmin() {
		return $this->_isAdmin;
	}
	
	/**
	 * IsUser
	 */
	public function isUser() {
		return $this->_isUser;
	}
	
	/**
	 * getUserId
	 */
	public function getUserId() {
		return $this->_userId;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = Array();

		if ($this->_isAdmin) {
			$affjetNetUserRMerchantDao = Dao_Factory_Doctrine::createDoctrineDaoInstance('AffjetNetUserRAffjetNetMerchant');
			$criteriaList = array();
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('AffjetNetMerchant->AffjetNetPartner->id', $this->_partnerId);
			$affjetNetUserRMerchantList = $affjetNetUserRMerchantDao->findBy($criteriaList);
			foreach ($affjetNetUserRMerchantList as $affjetNetUserRMerchant) {
				if ($affjetNetUserRMerchant->AffjetNetUser->AffjetNetRole->name != "admin") {
					$obj = Array();
					$obj['cid'] = $affjetNetUserRMerchant->id;
					$obj['name'] = $affjetNetUserRMerchant->AffjetNetUser->name." (".$affjetNetUserRMerchant->AffjetNetMerchant->name.")";
					$obj['url'] = $affjetNetUserRMerchant->AffjetNetMerchant->url;
					$merchants[] = $obj;
				}
			}

		} else {
			$affjetNetMerchantDao = Dao_Factory_Doctrine::createDoctrineDaoInstance('AffjetNetMerchant');
			$criteriaList = array();
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('AffjetNetPartner->id', $this->_partnerId);
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('AffjetNetUserRAffjetNetMerchant->AffjetNetUser->id', $this->_userId);

			$affjetNetMerchantList = $affjetNetMerchantDao->findBy($criteriaList);

			foreach ($affjetNetMerchantList as $affjetNetMerchant) {
				$obj = Array();
				$obj['cid'] = $affjetNetMerchant->id;
				$obj['name'] = $affjetNetMerchant->name;
				$obj['url'] = $affjetNetMerchant->url;
				$merchants[] = $obj;
			}
		}

		return $merchants;
	}
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = Array();

		$affjetNetTransactionDao = Dao_Factory_Doctrine::createDoctrineDaoInstance('AffjetNetTransaction');
		$criteriaList = array();
		if ($this->_isAdmin) {
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Select('AffjetNetClick->AffjetNetUserRAffjetNetMerchant->id', "_merchantId");
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_In('AffjetNetClick->AffjetNetUserRAffjetNetMerchant->id', $merchantList, false);
		} else {
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Select('AffjetNetClick->AffjetNetUserRAffjetNetMerchant->AffjetNetMerchant->id', "_merchantId");
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_In('AffjetNetClick->AffjetNetUserRAffjetNetMerchant->AffjetNetMerchant->id', $merchantList, false);
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('AffjetNetClick->AffjetNetUserRAffjetNetMerchant->AffjetNetUser->id', $this->_userId);
		}

		$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('AffjetNetClick->AffjetNetUserRAffjetNetMerchant->AffjetNetMerchant->AffjetNetPartner->id', $this->_partnerId);
		$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Ge('date', $dStartDate->toString("yyyy-MM-dd HH:mm:ss"));
		$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Le('date', $dEndDate->toString("yyyy-MM-dd HH:mm:ss"));
		$affjetNetTransactionList = $affjetNetTransactionDao->findBy($criteriaList);

		foreach ($affjetNetTransactionList as $transaction) {

			$object = array();
			$object['unique_id'] = $transaction->order_id;
			$object['merchantId'] = $transaction->_merchantId;
			$object['date'] = $transaction->date;
			$object['amount'] = $transaction->amount;
			$object['commission'] = $transaction->commission;
			$object['status'] = $transaction->status;
			$object['custom_id'] = $transaction->custom_id;
			$object['ip'] = $transaction->AffjetNetClick->ip;
			$object['click_date'] = $transaction->AffjetNetClick->date;
			$totalTransactions[] = $object;
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

		$affjetNetClickDao = Dao_Factory_Doctrine::createDoctrineDaoInstance('AffjetNetClick');
		$criteriaList = array();

		if ($this->_isAdmin) {
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Select('AffjetNetUserRAffjetNetMerchant->id', "_merchantId");
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_In('AffjetNetUserRAffjetNetMerchant->id', $merchantList, false);
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Groupby('AffjetNetUserRAffjetNetMerchant->id');
		} else {
			
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Select('AffjetNetUserRAffjetNetMerchant->AffjetNetMerchant->id', "_merchantId");
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_In('AffjetNetUserRAffjetNetMerchant->AffjetNetMerchant->id', $merchantList, false);
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('AffjetNetUserRAffjetNetMerchant->AffjetNetUser->id', $this->_userId);
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Groupby('AffjetNetUserRAffjetNetMerchant->AffjetNetMerchant->id');
		}
		
		$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Select('date', "_date");
		$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Select('COUNT(*)', "_clickNumber", true);
		$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('AffjetNetUserRAffjetNetMerchant->AffjetNetMerchant->AffjetNetPartner->id', $this->_partnerId);
		
		
		$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Ge('date', $dStartDate->toString("yyyy-MM-dd HH:mm:ss"));
		$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Le('date', $dEndDate->toString("yyyy-MM-dd HH:mm:ss"));

		$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Groupby('date', 'DAY');
		$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Groupby('date', 'MONTH');
		$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Groupby('date', 'YEAR');
		$affjetNetClickList = $affjetNetClickDao->findBy($criteriaList);

		foreach ($affjetNetClickList as $affjetNetClick) {

			$overview = Array();
			$overviewDate = new Zend_Date($affjetNetClick->_date, "yyyy-MM-dd HH:mm:ss");
			$overviewDate->setHour(0);
			$overviewDate->setMinute(0);
			$overviewDate->setSecond(0);

			$overview['merchantId'] = $affjetNetClick->_merchantId;
			$overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
			$overview['click_number'] = $affjetNetClick->_clickNumber;
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

			$transactionList = Oara_Utilities::getDayFromArray($affjetNetClick->_merchantId, $transactionArray, $overviewDate);

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

		return $totalOverviews;
	}

	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Publisher_Base#getPaymentHistory()
	 */
	public function getPaymentHistory() {
		$paymentHistory = array();

		$affjetNetPaymentDao = Dao_Factory_Doctrine::createDoctrineDaoInstance('AffjetNetPayment');
		$criteriaList = array();
		$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('AffjetNetPartner->id', $this->_partnerId);
		if (!$this->_isAdmin) {
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('AffjetNetUser->id', $this->_userId);
		}
		$affjetNetPaymentList = $affjetNetPaymentDao->findBy($criteriaList);

		foreach ($affjetNetPaymentList as $payment) {
			$obj = Array();
			$obj['pid'] = $payment->id;
			$obj['date'] = $payment->date;
			$obj['value'] = $payment->value;
			$obj['method'] = $payment->method;
			$paymentHistory[] = $obj;
		}
		return $paymentHistory;
	}

}
