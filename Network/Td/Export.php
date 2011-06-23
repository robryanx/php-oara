<?php
/**
 * Export Class  
 * 
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Td
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 * 
 */
class Oara_Network_Td_Export extends Oara_Network_Base{
    /**
     * Export client.
     * @var Oara_Curl_Access
     */
	private $_client = null;
	/**
	 * Merchants Export Parameters
	 * @var array
	 */
	private $_exportMerchantParameters = null;
	/**
	 * Transaction Export Parameters
	 * @var array
	 */
	private $_exportTransactionParameters = null;
	/**
	 * Overview Export Parameters
	 * @var array
	 */
	private $_exportOverviewParameters = null;
	/**
	 * Creative Export Parameters
	 */
	private $_exportCreativeParameters = null;
	/**
     * Websites List
     * @var array
     */
    private $_websitesList = array();
    /**
     * Merchant Map
     * @var array
     */
    private $_marchantMap = array();
	/**
	 * Constructor and Login
	 * @param $tradeDoubler
	 * @return Oara_Network_Td_Export
	 */
	public function __construct($tradeDoubler, $groupId, $mode)
	{
		$configuration = $tradeDoubler->AffiliateNetworkConfig->toArray();

		$user = Oara_Utilities::arrayFetchValue($configuration, 'key', 'user');
		$user = Oara_Utilities::decodePassword($user['value']);
		$password = Oara_Utilities::arrayFetchValue($configuration, 'key', 'password');
		$password = Oara_Utilities::decodePassword($password['value']);
		
		$loginUrl = 'http://www.tradedoubler.com/pan/login';
		
		$valuesLogin = array(new Oara_Curl_Parameter('j_username', $user),
                             new Oara_Curl_Parameter('j_password', $password)
                             );
		
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $tradeDoubler, $groupId, $mode);

        $this->_exportMerchantParameters = array(new Oara_Curl_Parameter('reportName', 'aAffiliateMyProgramsReport'),
				                                 new Oara_Curl_Parameter('tabMenuName', ''),
				                                 new Oara_Curl_Parameter('isPostBack', ''),
				                                 new Oara_Curl_Parameter('showAdvanced', 'true'),
				                                 new Oara_Curl_Parameter('showFavorite', 'false'),
				                                 new Oara_Curl_Parameter('run_as_organization_id', ''),
				                                 new Oara_Curl_Parameter('minRelativeIntervalStartTime', '0'),
				                                 new Oara_Curl_Parameter('maxIntervalSize', '0'),
				                                 new Oara_Curl_Parameter('interval', 'MONTHS'),
				                                 new Oara_Curl_Parameter('reportPrograms', ''),
				                                 new Oara_Curl_Parameter('reportTitleTextKey', 'REPORT3_SERVICE_REPORTS_AAFFILIATEMYPROGRAMSREPORT_TITLE'),
				                                 new Oara_Curl_Parameter('setColumns', 'true'),
				                                 new Oara_Curl_Parameter('latestDayToExecute', '0'),
				                                 new Oara_Curl_Parameter('affiliateId', ''),
				                                 new Oara_Curl_Parameter('includeWarningColumn', 'true'),
				                                 new Oara_Curl_Parameter('sortBy', 'orderDefault'),
				                                 new Oara_Curl_Parameter('autoCheckbox', 'columns'),
				                                 new Oara_Curl_Parameter('autoCheckbox', 'columns'),
				                                 new Oara_Curl_Parameter('columns', 'programId'),
				                                 new Oara_Curl_Parameter('autoCheckbox', 'columns'),
				                                 new Oara_Curl_Parameter('autoCheckbox', 'columns'),
				                                 new Oara_Curl_Parameter('autoCheckbox', 'columns'),
				                                 new Oara_Curl_Parameter('autoCheckbox', 'columns'),
				                                 new Oara_Curl_Parameter('autoCheckbox', 'columns'),
				                                 new Oara_Curl_Parameter('autoCheckbox', 'columns'),
				                                 new Oara_Curl_Parameter('autoCheckbox', 'columns'),
				                                 new Oara_Curl_Parameter('autoCheckbox', 'columns'),
				                                 new Oara_Curl_Parameter('autoCheckbox', 'columns'),
				                                 new Oara_Curl_Parameter('autoCheckbox', 'columns'),
				                                 new Oara_Curl_Parameter('columns', 'affiliateId'),
				                                 new Oara_Curl_Parameter('autoCheckbox', 'columns'),
				                                 new Oara_Curl_Parameter('columns', 'applicationDate'),
				                                 new Oara_Curl_Parameter('autoCheckbox', 'columns'),
				                                 new Oara_Curl_Parameter('columns', 'status'),
				                                 new Oara_Curl_Parameter('autoCheckbox', 'useMetricColumn'),
				                                 new Oara_Curl_Parameter('customKeyMetricCount', '0'),
				                                 new Oara_Curl_Parameter('metric1.name', ''),
				                                 new Oara_Curl_Parameter('metric1.midFactor', ''),
				                                 new Oara_Curl_Parameter('metric1.midOperator', '/'),
				                                 new Oara_Curl_Parameter('metric1.columnName1', 'programId'),
				                                 new Oara_Curl_Parameter('metric1.operator1', '/'),
				                                 new Oara_Curl_Parameter('metric1.columnName2', 'programId'),
				                                 new Oara_Curl_Parameter('metric1.lastOperator', '/'),
				                                 new Oara_Curl_Parameter('metric1.factor', ''),
				                                 new Oara_Curl_Parameter('metric1.summaryType', 'NONE'),
				                                 new Oara_Curl_Parameter('format', 'CSV'),
				                                 new Oara_Curl_Parameter('separator', ','),
				                                 new Oara_Curl_Parameter('dateType', '0'),
				                                 new Oara_Curl_Parameter('favoriteId', ''),
				                                 new Oara_Curl_Parameter('favoriteName', ''),
				                                 new Oara_Curl_Parameter('favoriteDescription', '')
				                                 );
		                                 
		$this->_exportTransactionParameters = array(new Oara_Curl_Parameter('reportName', 'aAffiliateEventBreakdownReport'),
													new Oara_Curl_Parameter('columns', 'programId'),
													new Oara_Curl_Parameter('columns', 'timeOfVisit'),
													new Oara_Curl_Parameter('columns', 'timeOfEvent'),
													new Oara_Curl_Parameter('columns', 'timeInSession'),
													new Oara_Curl_Parameter('columns', 'lastModified'),
													new Oara_Curl_Parameter('columns', 'epi1'),
													new Oara_Curl_Parameter('columns', 'eventName'),
													new Oara_Curl_Parameter('columns', 'pendingStatus'),
													new Oara_Curl_Parameter('columns', 'siteName'),
													new Oara_Curl_Parameter('columns', 'graphicalElementName'),
													new Oara_Curl_Parameter('columns', 'graphicalElementId'),
													new Oara_Curl_Parameter('columns', 'productName'),
													new Oara_Curl_Parameter('columns', 'productNrOf'),
													new Oara_Curl_Parameter('columns', 'productValue'),
													new Oara_Curl_Parameter('columns', 'affiliateCommission'),
													new Oara_Curl_Parameter('columns', 'link'),
													new Oara_Curl_Parameter('columns', 'leadNR'),
													new Oara_Curl_Parameter('columns', 'orderNR'),
													new Oara_Curl_Parameter('columns', 'pendingReason'),
													new Oara_Curl_Parameter('columns', 'orderValue'),
													new Oara_Curl_Parameter('isPostBack', ''),
													new Oara_Curl_Parameter('metric1.lastOperator', '/'),
													new Oara_Curl_Parameter('interval', ''),
													new Oara_Curl_Parameter('favoriteDescription', ''),
													new Oara_Curl_Parameter('currencyId', 'GBP'),
													new Oara_Curl_Parameter('event_id', '0'),
													new Oara_Curl_Parameter('pending_status', '1'),
													new Oara_Curl_Parameter('run_as_organization_id', ''),
													new Oara_Curl_Parameter('minRelativeIntervalStartTime', '0'),
													new Oara_Curl_Parameter('includeWarningColumn', 'true'),
													new Oara_Curl_Parameter('metric1.summaryType', 'NONE'),
													new Oara_Curl_Parameter('metric1.operator1', '/'),
													new Oara_Curl_Parameter('latestDayToExecute', '0'),
													new Oara_Curl_Parameter('showAdvanced', 'true'),
													new Oara_Curl_Parameter('breakdownOption', '1'),
													new Oara_Curl_Parameter('metric1.midFactor', ''),
													new Oara_Curl_Parameter('reportTitleTextKey', 'REPORT3_SERVICE_REPORTS_AAFFILIATEEVENTBREAKDOWNREPORT_TITLE'),
													new Oara_Curl_Parameter('setColumns', 'true'),
													new Oara_Curl_Parameter('metric1.columnName1', 'orderValue'),
													new Oara_Curl_Parameter('metric1.columnName2', 'orderValue'),
													new Oara_Curl_Parameter('reportPrograms', ''),
													new Oara_Curl_Parameter('metric1.midOperator', '/'),
													new Oara_Curl_Parameter('dateSelectionType', '1'),
													new Oara_Curl_Parameter('favoriteName', ''),
													new Oara_Curl_Parameter('affiliateId', ''),
													new Oara_Curl_Parameter('dateType', '1'),
													new Oara_Curl_Parameter('period', 'custom_period'),
													new Oara_Curl_Parameter('tabMenuName', ''),
													new Oara_Curl_Parameter('maxIntervalSize', '0'),
													new Oara_Curl_Parameter('favoriteId', ''),
													new Oara_Curl_Parameter('sortBy', 'timeOfEvent'),
													new Oara_Curl_Parameter('metric1.name', ''),
													new Oara_Curl_Parameter('customKeyMetricCount', '0'),
													new Oara_Curl_Parameter('metric1.factor', ''),
													new Oara_Curl_Parameter('showFavorite', 'false'),
													new Oara_Curl_Parameter('separator', ','),
													new Oara_Curl_Parameter('format', 'CSV')
			                                        );
			                                        
	    $this->_exportOverviewParameters = array(new Oara_Curl_Parameter('reportName', 'aAffiliateGraphicalElementReport'),
	                                               new Oara_Curl_Parameter('columns', 'graphicalElementName'),
	                                               new Oara_Curl_Parameter('columns', 'graphicalElementId'),
	                                               new Oara_Curl_Parameter('columns', 'graphicalElementSize'),
	                                               new Oara_Curl_Parameter('columns', 'graphicalElementType'),
	                                               new Oara_Curl_Parameter('columns', 'impNrOf'),
	                                               new Oara_Curl_Parameter('columns', 'clickNrOf'),
	                                               new Oara_Curl_Parameter('columns', 'clickRate'),
	                                               new Oara_Curl_Parameter('columns', 'uvNrOf'),
	                                               new Oara_Curl_Parameter('columns', 'uvRate'),
	                                               new Oara_Curl_Parameter('columns', 'leadNrOf'),
	                                               new Oara_Curl_Parameter('columns', 'leadCommission'),
	                                               new Oara_Curl_Parameter('columns', 'leadRate'),
	                                               new Oara_Curl_Parameter('columns', 'saleNrOf'),
	                                               new Oara_Curl_Parameter('columns', 'saleCommission'),
	                                               new Oara_Curl_Parameter('columns', 'conversionRate'),
	                                               new Oara_Curl_Parameter('columns', 'totalOrderValue'),
	                                               new Oara_Curl_Parameter('columns', 'keyMetricECPM'),
	                                               new Oara_Curl_Parameter('columns', 'cpo'),
	                                               new Oara_Curl_Parameter('columns', 'affiliateCommission'),
	                                               new Oara_Curl_Parameter('columns', 'link'),
	                                               new Oara_Curl_Parameter('columns', 'programName'),
	                                               new Oara_Curl_Parameter('isPostBack', ''),
	                                               new Oara_Curl_Parameter('metric1.lastOperator', '/'),
	                                               new Oara_Curl_Parameter('interval', 'MONTHS'),
	                                               new Oara_Curl_Parameter('favoriteDescription', ''),
	                                               new Oara_Curl_Parameter('currencyId', 'GBP'),
	                                               new Oara_Curl_Parameter('run_as_organization_id', ''),
	                                               new Oara_Curl_Parameter('minRelativeIntervalStartTime', '0'),
	                                               new Oara_Curl_Parameter('metric1.summaryType', 'NONE'),
	                                               new Oara_Curl_Parameter('metric1.operator1e', '/'),
	                                               new Oara_Curl_Parameter('latestDayToExecute', '0'),
	                                               new Oara_Curl_Parameter('showAdvanced', 'false'),
	                                               new Oara_Curl_Parameter('adType', ''),
	                                               new Oara_Curl_Parameter('eventTypeId', '0'),
	                                               new Oara_Curl_Parameter('metric1.midFactor', ''),
	                                               new Oara_Curl_Parameter('reportTitleTextKey', 'REPORT3_SERVICE_REPORTS_AAFFILIATEGRAPHICALELEMENTREPORT_TITLE'),
	                                               new Oara_Curl_Parameter('setColumns', 'true'),
	                                               new Oara_Curl_Parameter('metric1.columnName1', 'graphicalElementId'),
	                                               new Oara_Curl_Parameter('metric1.columnName2', 'graphicalElementId'),
	                                               new Oara_Curl_Parameter('reportPrograms', ''),
	                                               new Oara_Curl_Parameter('adServeing', ''),
	                                               new Oara_Curl_Parameter('metric1.midOperator', '/'),
	                                               new Oara_Curl_Parameter('favoriteName', ''),
	                                               new Oara_Curl_Parameter('dateType', '1'),
	                                               new Oara_Curl_Parameter('period', 'custom_period'),
	                                               new Oara_Curl_Parameter('tabMenuName', ''),
	                                               new Oara_Curl_Parameter('dateType', '1'),
	                                               new Oara_Curl_Parameter('maxIntervalSize', '12'),
	                                               new Oara_Curl_Parameter('favoriteId', ''),
	                                               new Oara_Curl_Parameter('metric1.name', ''),
	                                               new Oara_Curl_Parameter('geStatus', 'all'),
	                                               new Oara_Curl_Parameter('customKeyMetricCount', '0'),
	                                               new Oara_Curl_Parameter('metric1.factor', ''),
	                                               new Oara_Curl_Parameter('showFavorite', 'false'),
	                                               new Oara_Curl_Parameter('separator', ''),
	                                               new Oara_Curl_Parameter('programTypeId', ''),
	                                               new Oara_Curl_Parameter('format', 'CSV'),
	                                               );
	                                               
		$this->_exportCreativeParameters = array(new Oara_Curl_Parameter('programGEListParameterTransport.currentPage', '1'),
	                                               new Oara_Curl_Parameter('searchPerformed', 'true'),
	                                               new Oara_Curl_Parameter('searchType', 'ge'),
	                                               new Oara_Curl_Parameter('programGEListParameterTransport.deepLinking', ''),
	                                               new Oara_Curl_Parameter('programGEListParameterTransport.tariffStructure', ''),
	                                               new Oara_Curl_Parameter('programGEListParameterTransport.orderBy', 'lastUpdated'),
	                                               new Oara_Curl_Parameter('programGEListParameterTransport.websiteStatusId', ''),
	                                               new Oara_Curl_Parameter('programGEListParameterTransport.pageSize', '100'),
	                                               new Oara_Curl_Parameter('programAdvancedListParameterTransport.directAutoApprove', ''),
	                                               new Oara_Curl_Parameter('programGEListParameterTransport.graphicalElementTypeId', ''),
	                                               new Oara_Curl_Parameter('programGEListParameterTransport.graphicalElementSize', ''),
	                                               new Oara_Curl_Parameter('programGEListParameterTransport.width', ''),
	                                               new Oara_Curl_Parameter('programGEListParameterTransport.height', ''),
	                                               new Oara_Curl_Parameter('programGEListParameterTransport.lastUpdated', ''),
	                                               new Oara_Curl_Parameter('programGEListParameterTransport.graphicalElementNameOrId', ''),
	                                               new Oara_Curl_Parameter('programGEListParameterTransport.showGeGraphics', 'true'),
	                                               new Oara_Curl_Parameter('programAdvancedListParameterTransport.pfAdToolUnitName', ''),
	                                               new Oara_Curl_Parameter('programAdvancedListParameterTransport.pfAdToolProductPerCell', ''),
	                                               new Oara_Curl_Parameter('programAdvancedListParameterTransport.pfAdToolDescription', ''),
	                                               new Oara_Curl_Parameter('programAdvancedListParameterTransport.pfTemplateTableRows', ''),
	                                               new Oara_Curl_Parameter('programAdvancedListParameterTransport.pfTemplateTableColumns', ''),
	                                               new Oara_Curl_Parameter('programAdvancedListParameterTransport.pfTemplateTableWidth', ''),
	                                               new Oara_Curl_Parameter('programAdvancedListParameterTransport.pfTemplateTableHeight', ''),
	                                               new Oara_Curl_Parameter('programAdvancedListParameterTransport.pfAdToolContentUnitRule', '')
												   );
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		$connection = false;
		if (!preg_match("/Session timed out/", self::getExportMerchantReport(), $matches)){
			$connection = true;
		}
		return $connection;
	}
    /**
     * It returns the Merchant CVS report.
     * @return $exportReport
     */
	private function getExportMerchantReport(){
		$valuesFormExport = $this->_exportMerchantParameters;
		$valuesFormExport[] = new Oara_Curl_Parameter('programAffiliateStatusId', '3');
				                                
        $urls = array();
        $urls[] = new Oara_Curl_Request('http://www.tradedoubler.com/pan/aReport3Internal.action?', $valuesFormExport);
        $exportReport = $this->_client->post($urls);
        return self::formatCsv($exportReport[0]);
	}
	/**
	 * 
	 * Format Csv
	 * @param unknown_type $csv
	 */
	private function formatCsv($csv){
		preg_match_all("/\"([^\"]+?)\",/", $csv, $matches);
        foreach ($matches[1] as $match){
        	if (preg_match("/,/", $match)){
        		$rep = preg_replace("/,/","", $match);
        		$csv = str_replace($match, $rep, $csv);
        		$match = $rep;
        	}
        	if (preg_match("/\n/", $match)){
        		$rep = preg_replace("/\n/","", $match);
        		$csv = str_replace($match, $rep, $csv);
        	}
        }
		return $csv;
	}
   
	/**
	 * It returns an array with the site's names for an especific merchant
	 * @param $idMerchant
	 * @return array
	 */
	private function getSitesFromMerchant($merchantList = null){
		$siteMap = array();
		$siteList = array();
		foreach($merchantList as $idMerchant){
			if (isset($this->_websitesList[$idMerchant])){
				$siteList[$idMerchant] = $this->_websitesList[$idMerchant];	
			}
		}
		foreach ($siteList as $merchantId => $websiteMap){
			foreach ($websiteMap as $websiteid => $websiteName){
				$siteMap[$websiteid] = $websiteName;
			}
		}
        return $siteMap;
	}
	/**
	 * It returns an array with the different merchants
	 * @return array
	 */
	private function getMerchantReportList(){
		
		$merchantReport = self::getExportMerchantReport();
		
        $exportData = str_getcsv($merchantReport, "\r\n");
        $merchantReportList = Array();
        $num = count($exportData);
        for ($i = 3; $i < $num; $i++) {
            $merchantExportArray = str_getcsv($exportData[$i], ",");
            if (count($merchantExportArray) < 5){
            	throw new Exception ("Problem getting the merchant ");
            }
            
            if ($merchantExportArray[2] != '' && $merchantExportArray[4] != ''){
                $merchantReportList[$merchantExportArray[4]] = $merchantExportArray[2];
                
                //Fill the website list
	            if(!isset($this->_websitesList[$merchantExportArray[4]])){
	            	$this->_websitesList[$merchantExportArray[4]] = array();
	            }
	            $this->_websitesList[$merchantExportArray[4]][$merchantExportArray[1]] = $merchantExportArray[0];
            }
        }
        
        $valuesFormExport = $this->_exportMerchantParameters;
		$valuesFormExport[] = new Oara_Curl_Parameter('programAffiliateStatusId', '4');
				                                
        $urls = array();
        $urls[] = new Oara_Curl_Request('http://www.tradedoubler.com/pan/aReport3Internal.action?', $valuesFormExport);
        $exportReport = $this->_client->post($urls);
        $exportData = str_getcsv(self::formatCsv($exportReport[0]), "\r\n");
		$num = count($exportData);
        for ($i = 3; $i < $num; $i++) {
            $merchantExportArray = str_getcsv($exportData[$i], ",");
            if (!isset($merchantExportArray[2])){
            	throw new Exception ('Error getting merchant report');
            }
            if ($merchantExportArray[2] != '' && $merchantExportArray[4] != ''){
                //Fill the website list
	            if(!isset($this->_websitesList[$merchantExportArray[4]])){
	            	$this->_websitesList[$merchantExportArray[4]] = array();
	            }
	            $this->_websitesList[$merchantExportArray[4]][$merchantExportArray[1]] = $merchantExportArray[0];
            }
        }
        
        return $merchantReportList;
	}
    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getMerchantList()
     */
	public function getMerchantList($merchantMap = array())
	{
        $merchantReportList = self::getMerchantReportList();
        $merchants = Array();
        foreach($merchantReportList as $key=>$value){
        	$obj = Array();
        	$obj['cid'] = $key;
        	$obj['name'] = $value;
        	$merchants[] = $obj;
        }
		$this->_merchantMap = $merchantMap;
		foreach ($merchants as $merchant){
			if (!isset($this->_merchantMap[$merchant['name']])){
				$this->_merchantMap[$merchant['name']] = $merchant['cid'];
			}
			
		}
        
        return $merchants;
	}
    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getTransactionList($merchantId, $dStartDate, $dEndDate)
     */
	public function getTransactionList($merchantList = null , Zend_Date $dStartDate = null , Zend_Date $dEndDate = null)
	{
		$totalTransactions = Array();
        $startDate = $dStartDate->toString('dd/MM/yyyy');
        $endDate = $dEndDate->toString('dd/MM/yyyy');

        $valuesFormExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
        $valuesFormExport[] = new Oara_Curl_Parameter('startDate', $startDate);
        $valuesFormExport[] = new Oara_Curl_Parameter('endDate', $endDate);
       	$urls = array();
        $urls[] = new Oara_Curl_Request('http://www.tradedoubler.com/pan/aReport3Internal.action?', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
        
        preg_match_all("/,\"([^\"]+?)\",/", $exportReport[0], $matches);
        foreach ($matches[1] as $match){
        	if (preg_match("/\r\n/", $match)){
        		$rep = preg_replace("/\r\n/","", $match);
        		$exportReport[0] = str_replace($match, $rep, $exportReport[0]);
        	}
        }
        
        $exportData = str_getcsv($exportReport[0],"\r\n");
        $num = count($exportData);
        if ($num < 3){
         	throw new Exception ('Error getting transaction report');
        }
        for ($i = 2; $i < $num-1; $i++) {
            $transactionExportArray = str_getcsv($exportData[$i],",");
        	if (!isset($transactionExportArray[2])){
                throw new Exception ("Problem getting the transactions");
            }
            if ($transactionExportArray[0] !== '' && in_array((int)$transactionExportArray[2],$merchantList)){
                $transaction = Array();
                $transaction['merchantId'] = $transactionExportArray[2];
                $transaction['website'] = $transactionExportArray[13];
                $transactionDate =  new Zend_Date(substr($transactionExportArray[4],0,-4), "dd/MM/YY HH:mm:ss");
                $transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
                $transaction['program'] = $transactionExportArray[10];
                $transaction['link'] = $transactionExportArray[14];
                $transaction['linkId'] = $transactionExportArray[15];
                if ($transactionExportArray[11] == 'A'){
                	$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
                } else if ($transactionExportArray[11] == 'P'){
                    $transaction['status'] = Oara_Utilities::STATUS_PENDING;
                } else if ($transactionExportArray[11] == 'D'){
                    $transaction['status'] = Oara_Utilities::STATUS_DECLINED;
                }
                        
                if ($transactionExportArray[7] != ''){
                    $transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[20]);
                } else {
                    $transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[19]);
                }    
                        
                $transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[20]);
                $totalTransactions[] = $transaction;
            }
        }
        return $totalTransactions;
        
	}
    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Oara_Network_Base#getOverviewList($merchantId, $dStartDate, $dEndDate)
     */
    public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
        $totalOverviews = Array();
        $transactionArray = self::transactionMapPerDay($transactionList);
        
        $mothOverviewUrls = array();
        $sites = self::getSitesFromMerchant($merchantList);
        foreach ($sites as $key => $value){
        	$valuesFormExport = Oara_Utilities::cloneArray($this->_exportOverviewParameters);
        	$valuesFormExport[] = new Oara_Curl_Parameter('startDate', $dStartDate->toString('dd/MM/yy'));
        	$valuesFormExport[] = new Oara_Curl_Parameter('endDate', $dEndDate->toString('dd/MM/yy'));
        	$valuesFormExport[] = new Oara_Curl_Parameter('affiliateId', $key);
        	$urls = array();
        	$urls[] = new Oara_Curl_Request('http://www.tradedoubler.com/pan/aReport3.action?', $valuesFormExport);
        	$exportReport = $this->_client->get($urls);
        	$exportData = array();
        	if (!preg_match("/error/", $exportReport[0], $matches)){
            	$exportData = str_getcsv($exportReport[0],"\r\n");
            }
            $num = count($exportData);
            if ($num > 3){
            	$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
            	$dateArraySize = sizeof($dateArray);
           		
            	for ($i = 0; $i < $dateArraySize; $i++){
                	$valuesFormExport = Oara_Utilities::cloneArray($this->_exportOverviewParameters);
                	$valuesFormExport[] = new Oara_Curl_Parameter('startDate', $dateArray[$i]->toString('dd/MM/yy'));
                	$valuesFormExport[] = new Oara_Curl_Parameter('endDate', $dateArray[$i]->toString('dd/MM/yy'));
                	$valuesFormExport[] = new Oara_Curl_Parameter('affiliateId', $key);
                	$mothOverviewUrls[] = new Oara_Curl_Request('http://www.tradedoubler.com/pan/aReport3.action?', $valuesFormExport);
                }                                     
            }
        }
        
    	$exportReport = $this->_client->get($mothOverviewUrls);
        $exportReportNumber = count($exportReport);
	    for ($i = 0; $i < $exportReportNumber; $i++){
        	$exportData = str_getcsv($exportReport[$i],"\r\n");
        	$num = count($exportData); 
        	for ($j = 2; $j < $num-1; $j++) {
             	$overviewExportArray = str_getcsv($exportData[$j],";");
	    		$parameter = $mothOverviewUrls[$i]->getParameter(58);
	    		$overviewDate = $parameter->getValue();
                $overviewDate = new Zend_Date($overviewDate, "dd/MM/yy");
                if (!isset($overviewExportArray[4])){
                	throw new Exception ("Problem getting the overview");
                }
                $merchantName = preg_replace("/,/","", $overviewExportArray[4]);
                if(!isset($this->_merchantMap[$merchantName])){
                	echo 'not found the merchant '.$merchantName."\n\n";
                }
                
            	if ($overviewDate->compare($dStartDate) >= 0 && $overviewDate->compare($dEndDate) <= 0 
            		&& isset($this->_merchantMap[$merchantName]) && in_array((int)$this->_merchantMap[$merchantName],$merchantList)){
                	
            		$overview = Array();
                    
                    $overview['merchantId'] = (int)$this->_merchantMap[$merchantName];
                    $overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
                    $overview['link'] = $overviewExportArray[0];
                    $parameter = $mothOverviewUrls[$i]->getParameter(60);
	    			$parameterWebsite = $parameter->getValue();
                    $overview['website'] = $sites[$parameterWebsite];
                    $overview['click_number'] = (int)$overviewExportArray[6];
                   	$overview['impression_number'] = (int)$overviewExportArray[5];
                    $overview['transaction_number'] = 0;
                    $overview['transaction_confirmed_value'] = 0;
                    $overview['transaction_confirmed_commission']= 0;
                    $overview['transaction_pending_value']= 0;
                    $overview['transaction_pending_commission']= 0;
                    $overview['transaction_declined_value']= 0;
                    $overview['transaction_declined_commission']= 0;
                    $transactionDateArray = self::getDayFromArray($overview['merchantId'],$overviewExportArray[1],$overview['website'], $transactionArray, $overviewDate);
                    foreach ($transactionDateArray as $transaction){
                       $overview['link'] = $transaction['link'];
                       $overview['transaction_number'] ++;
                       if ($transaction['status'] == Oara_Utilities::STATUS_CONFIRMED){
                       	   $overview['transaction_confirmed_value'] += $transaction['amount'];
                           $overview['transaction_confirmed_commission'] += $transaction['commission'];
                       } else if ($transaction['status'] == Oara_Utilities::STATUS_PENDING){
                           $overview['transaction_pending_value'] += $transaction['amount'];
                           $overview['transaction_pending_commission'] += $transaction['commission'];
                       } else if ($transaction['status'] == Oara_Utilities::STATUS_DECLINED){
                           $overview['transaction_declined_value'] += $transaction['amount'];
                           $overview['transaction_declined_commission'] += $transaction['commission'];
                       }
                   }
                   if (Oara_Utilities::checkRegister($overview)){
                       $totalOverviews[] = $overview;
                   }
               }
           }
        }
        
        return $totalOverviews;                                 	
    }
    
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Base#getPaymentHistory()
	 */
	public function getPaymentHistory(){
    	$paymentHistory = array();
    	
    	$urls = array();
        $urls[] = new Oara_Curl_Request('http://www.tradedoubler.com/pan/reportSelection/Payment?', array());   
        $exportReport = $this->_client->get($urls);
		/*** load the html into the object ***/
	    $doc = new DOMDocument();
	    libxml_use_internal_errors(true);
	    $doc->validateOnParse = true;
	    $doc->loadHTML($exportReport[0]);
	    $paymentSelect = $doc->getElementsByTagName('select');
	    if ($paymentSelect->length > 0){
		    $paymentLines = $paymentSelect->item(0)->childNodes;
			for ($i = 0;$i < $paymentLines->length;$i++) {
				$pid = $paymentLines->item($i)->attributes->getNamedItem("value")->nodeValue;
				if (is_numeric($pid)){
					$obj = array();
					$date = new Zend_Date(substr($paymentLines->item($i)->nodeValue,0,10), "dd/MM/yy");
					$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
					$obj['pid'] = $pid;
					$obj['method'] = 'BACS';
					if (preg_match("/[-+]?[0-9]*,?[0-9]*\.?[0-9]+/", substr($paymentLines->item($i)->nodeValue,10), $matches)) {
						$obj['value'] = Oara_Utilities::parseDouble($matches[0]);
					} else {
						throw new Exception("Problem reading payments");
					}
					
					$paymentHistory[] = $obj;
				}	
			}
	    }
    	return $paymentHistory;
    }
	/**
     * Filter the transactionList per day
     * @param array $transactionList
     * @return array
     */
    public function transactionMapPerDay(array $transactionList){
    	$transactionMap = array();
    	foreach ($transactionList as $transaction){
    		$dateString = substr($transaction['date'], 0, 10);
    		if (!isset($transactionMap[$transaction['merchantId']][$transaction['linkId']][$transaction['website']][$dateString])){
    			$transactionMap[$transaction['merchantId']][$transaction['linkId']][$transaction['website']][$dateString] = array();
    		}
            
    		$transactionMap[$transaction['merchantId']][$transaction['linkId']][$transaction['website']][$dateString][] = $transaction;
    	}
    	
    	return $transactionMap;
    }
	/**
	 * Get the day for this transaction array
	 * @param map $dateArray
	 * @param Zend_Date $date
	 * @return array
	 */
	public function getDayFromArray($merchantId, $linkId, $website, $dateArray, Zend_Date $date){
		$resultArray = array();
		if (isset($dateArray[$merchantId][$linkId][$website])){
			$dateString = $date->toString("yyyy-MM-dd");
			if (isset($dateArray[$merchantId][$linkId][$website][$dateString])){
				$resultArray = $dateArray[$merchantId][$linkId][$website][$dateString];
			}
		}
		return $resultArray;
	}
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Base#getCreatives()
	 */
	public function getCreatives(){
		$creativesMap = array();
		
    	$merchantList = self::getMerchantList();
    	
    	foreach ($merchantList as $merchant){
    		$websiteFirstId = null;
    		$websiteMap = array();
    		if (isset($this->_websitesList[$merchant['cid']])){
				$websiteMap = $this->_websitesList[$merchant['cid']];	
			}
			
			foreach ($websiteMap as $websiteid => $websiteName){
				$websiteFirstId = $websiteid;
				break;
			}
			
			if ($websiteFirstId != null){
				$urls = array();
				$valuesFormExport = Oara_Utilities::cloneArray($this->_exportCreativeParameters);
		       	$valuesFormExport[] = new Oara_Curl_Parameter('programGEListParameterTransport.siteId', $websiteFirstId);
		       	$valuesFormExport[] = new Oara_Curl_Parameter('programGEListParameterTransport.programIdOrName', $merchant['cid']);
	       		$urls[] = new Oara_Curl_Request('http://www.tradedoubler.com/pan/aGEList.action?', $valuesFormExport);
	       		
				$exportReport = $this->_client->post($urls);
		    	for ($i = 0; $i < count($exportReport); $i++){
		    		if (preg_match_all("/javascript: showCode\((.+)?\)/", $exportReport[$i], $matches)){
		    			foreach ($matches[1] as $parameters){
		    				$paramatersArray = explode(',', $parameters);
		    				$programId = $paramatersArray[1];
		    				$graphicalElementId = $paramatersArray[0];
		    				$affiliateId = $paramatersArray[3];
		    				if (is_numeric($programId) && is_numeric($graphicalElementId) && is_numeric($affiliateId)){
		    					/**
			    				$creativesMap[(string)$programId][] = "<script type=\"text/javascript\">
																	   var uri = 'http://impgb.tradedoubler.com/imp?type(img)g($graphicalElementId)a($affiliateId)' + new String (Math.random()).substring (2, 11);
																	   document.write('<a href=\"http://clkuk.tradedoubler.com/click?p=$programId&a=$affiliateId&g=$graphicalElementId\" target=\"_BLANK\"><img src=\"'+uri+'\" border=0></a>');
																	   </script>";
			    				
								$creativesMap[(string)$programId][] = "<script type=\"text/javascript\">
																	   var uri = 'http://impgb.tradedoubler.com/imp?type(iframe)g($graphicalElementId)a($affiliateId)' + new String (Math.random()).substring (2, 11);
																	   document.write('<iframe src=\"'+uri +'\" width=\"234\" height=\"60\" frameborder=\"0\" border=\"0\" marginwidth=\"0\" marginheight=\"0\" scrolling=\"no\"></iframe>');
																	   </script>";
								**/
								
								$creativesMap[(string)$programId][] = "<script type=\"text/javascript\">
																	   var uri = 'http://impgb.tradedoubler.com/imp?type(js)g($graphicalElementId)a($affiliateId)' + new String (Math.random()).substring (2, 11);
																	   document.write('<sc'+'ript type=\"text/javascript\" src=\"'+uri+'\" charset=\"\"></sc'+'ript>');
																	   </script>";
		    				}
		    			}
		    		}
		    	}
			}
    	}
		return $creativesMap;
	}
}