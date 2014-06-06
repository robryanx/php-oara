<?php
/**
 * Api Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Dgm
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Dgm extends Oara_Network {
	/**
	 * Soap client.
	 */
	private $_apiClient = null;
	private $_user = null;
	private $_pass = null;
	/**
	 * Merchant Campaigns
	 * 
	 * @var array
	 */
	private $_advertisersCampaings = null;
	/**
	 * Export Merchants Parameters
	 * 
	 * @var array
	 */
	private $_exportMerchantParameters = null;
	
	/**
	 * Constructor.
	 * 
	 * @param
	 *        	$dgm
	 * @return Oara_Network_Publisher_Dgm_Api
	 */
	public function __construct($credentials) {
		// Reading the different parameters.
		$this->_user = $credentials ['user'];
		$this->_pass = $credentials ['password'];
		
		$wsdlUrl = 'http://webservices.dgperform.com/dgmpublisherwebservices.cfc?wsdl';
		// Setting the apiClient.
		$this->_apiClient = new Zend_Soap_Client ( $wsdlUrl, array (
				'encoding' => 'UTF-8',
				'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
				'soap_version' => SOAP_1_1 
		) );
	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = true;
		
		$merchantsImportXml = $this->_apiClient->GetCampaigns ( $this->_user, $this->_pass, 'approved' );
		$xmlObject = new SimpleXMLElement ( $merchantsImportXml );
		if ($xmlObject->attributes ()->status == 'error') {
			$connection = false;
		}
		return $connection;
	}
	/**
	 * (non-PHPdoc)
	 * 
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array ();
		
		$merchantsImportXml = $this->_apiClient->GetCampaigns ( $this->_user, $this->_pass, 'approved' );
		$xmlObject = new SimpleXMLElement ( $merchantsImportXml );
		if ($xmlObject->attributes ()->status == 'error') {
			throw new Exception ( 'Error advertisers not found' );
		}
		
		foreach ( $xmlObject->campaigns->campaign as $campaing ) {
			
			$obj = array ();
			$obj ['cid'] = ( string ) $campaing->advertiserid;
			$obj ['name'] = ( string ) $campaing->advertisername;
			$merchants [] = $obj;
			
			if (! isset ( $this->_advertisersCampaings [( string ) $campaing->advertiserid] )) {
				$this->_advertisersCampaings [( string ) $campaing->advertiserid] = ( string ) $campaing->campaignid;
			} else {
				$this->_advertisersCampaings [( string ) $campaing->advertiserid] .= ',' . ( string ) $campaing->campaignid;
			}
		}
		
		return $merchants;
	}
	
	/**
	 * (non-PHPdoc)
	 * 
	 * @see library/Oara/Network/Oara_Network_Publisher_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = Array ();
		
		$transactionXml = $this->_apiClient->GetSales ( $this->_user, $this->_pass, 0, 'all', 'validated', $dStartDate->toString ( "yyyy-MM-dd" ), $dEndDate->toString ( "yyyy-MM-dd" ) );
		$xmlObject = new SimpleXMLElement ( $transactionXml );
		if ($xmlObject->attributes ()->status != 'error') {
			
			$campaignIdList = array ();
			foreach ( $merchantList as $merchantId ) {
				$campaingList = explode ( ",", $this->_advertisersCampaings [( string ) $merchantId] );
				foreach ( $campaingList as $campaignId ) {
					$campaignIdList [$campaignId] = $merchantId;
				}
			}
			
			foreach ( $xmlObject->sales->sale as $sale ) {
				
				if (isset ( $campaignIdList [( string ) $sale->Campaignid] )) {
					
					$transaction = Array ();
					$transaction ['unique_id'] = ( string ) $sale->OrderID;
					$transaction ['merchantId'] = $campaignIdList [( string ) $sale->CampaignID];
					$transactionDate = new Zend_Date ( ( string ) $sale->Saledate, 'yyyy-MM-dd HH:mm:ss' );
					$transaction ['date'] = $transactionDate->toString ( "yyyy-MM-dd HH:mm:ss" );
					
					if (( string ) $sale->CompanyID != null) {
						$transaction ['custom_id'] = ( string ) $sale->CompanyID;
					}
					
					if (( string ) $sale->SaleStatus == 'Approved') {
						$transaction ['status'] = Oara_Utilities::STATUS_CONFIRMED;
					} else if (( string ) $sale->SaleStatus == 'Pending') {
						$transaction ['status'] = Oara_Utilities::STATUS_PENDING;
					} else if (( string ) $sale->SaleStatus == 'Deleted') {
						$transaction ['status'] = Oara_Utilities::STATUS_DECLINED;
					}
					
					$transaction ['amount'] = ( string ) $sale->SaleValue;
					
					$transaction ['commission'] = ( string ) $sale->SaleCommission;
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
