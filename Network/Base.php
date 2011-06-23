<?php
/**
 * Base Class
 * It contains the Network common functionality.
 * All the Network classes extend this class.
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network
 * @copyright  Fubra Limited
 */
class Oara_Network_Base
{
	/**
	 * 
	 */
	public function checkConnection(){
		return false;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getMerchantList()
	 */
    public function getMerchantList($merchantMap = array()){
    	$result = array();
    	return $result;
    }
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getTransactionList($merchantId, $dStartDate, $dEndDate)
	 */
    public function getTransactionList($merchantList, Zend_Date $dStartDate, Zend_Date $dEndDate){
    	$result = array();
    	return $result;
    }
	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getOverviewList($merchantId, $dStartDate, $dEndDate)
	 */
    public function getOverviewList($transactionList, $merchantList, Zend_Date $dStartDate, Zend_Date $dEndDate){
    	$result = array();
    	return $result;
    }
    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Interface#getPaymentHistory()
     */
    public function getPaymentHistory(){
    	$result = array();
    	return $result;
    }
    
	/**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Interface#getLinks()
     */
    public function getCreatives(){
    	$result = array();
    	return $result;
    }
	
}