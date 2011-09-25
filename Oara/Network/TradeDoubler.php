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
class Oara_Network_TradeDoubler extends Oara_Network{
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
     * Merchant Map
     * @var array
     */
    private $_marchantMap = array();
    /**
     * Date Format, it's different in some accounts
     * @var string
     */
    private $_dateFormat = null;
	/**
	 * Constructor and Login
	 * @param $tradeDoubler
	 * @return Oara_Network_Td_Export
	 */
	public function __construct($credentials)
	{
		
		$user = $credentials['user'];
        $password = $credentials['password'];
        
		$loginUrl = 'http://www.tradedoubler.com/pan/login';
		
		$valuesLogin = array(new Oara_Curl_Parameter('j_username', $user),
                             new Oara_Curl_Parameter('j_password', $password)
                             );
		
		$this->_client = new Oara_Curl_Access($loginUrl, $valuesLogin, $credentials);

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
			                                        

			                                        
	    $this->_exportOverviewParameters = array(new Oara_Curl_Parameter('reportName','aAffiliateProgramOverviewReport'),
													new Oara_Curl_Parameter('tabMenuName',''),
													new Oara_Curl_Parameter('isPostBack',''),
													new Oara_Curl_Parameter('showAdvanced','true'),
													new Oara_Curl_Parameter('showFavorite','false'),
													new Oara_Curl_Parameter('run_as_organization_id',''),
													new Oara_Curl_Parameter('minRelativeIntervalStartTime','0'),
													new Oara_Curl_Parameter('maxIntervalSize','12'),
													new Oara_Curl_Parameter('interval','MONTHS'),
													new Oara_Curl_Parameter('reportPrograms',''),
													new Oara_Curl_Parameter('reportTitleTextKey','REPORT3_SERVICE_REPORTS_AAFFILIATEPROGRAMOVERVIEWREPORT_TITLE'),
													new Oara_Curl_Parameter('setColumns','true'),
													new Oara_Curl_Parameter('latestDayToExecute','0'),
													new Oara_Curl_Parameter('programTypeId',''),
													new Oara_Curl_Parameter('currencyId','GBP'),
													new Oara_Curl_Parameter('includeWarningColumn','true'),
													new Oara_Curl_Parameter('programId',''),
													new Oara_Curl_Parameter('period','custom_period'),
													new Oara_Curl_Parameter('columns','programId'),
													new Oara_Curl_Parameter('columns','impNrOf'),
													new Oara_Curl_Parameter('columns','clickNrOf'),
													new Oara_Curl_Parameter('autoCheckbox','columns'),
													new Oara_Curl_Parameter('autoCheckbox','useMetricColumn'),
													new Oara_Curl_Parameter('customKeyMetricCount','0'),
													new Oara_Curl_Parameter('metric1.name',''),
													new Oara_Curl_Parameter('metric1.midFactor',''),
													new Oara_Curl_Parameter('metric1.midOperator','/'),
													new Oara_Curl_Parameter('metric1.columnName1','programId'),
													new Oara_Curl_Parameter('metric1.operator1','/'),
													new Oara_Curl_Parameter('metric1.columnName2','programId'),
													new Oara_Curl_Parameter('metric1.lastOperator','/'),
													new Oara_Curl_Parameter('metric1.factor',''),
													new Oara_Curl_Parameter('metric1.summaryType','NONE'),
													new Oara_Curl_Parameter('format','CSV'),
													new Oara_Curl_Parameter('separator',';'),
													new Oara_Curl_Parameter('dateType','1'),
													new Oara_Curl_Parameter('favoriteId',''),
													new Oara_Curl_Parameter('favoriteName',''),
													new Oara_Curl_Parameter('favoriteDescription','')
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
												   
												   
			
		$urls = array();
        $urls[] = new Oara_Curl_Request('http://www.tradedoubler.com/pan/aReport3Selection.action?reportName=aAffiliateProgramOverviewReport', array());
        $exportReport = $this->_client->get($urls);
		if (preg_match("/\(([a-zA-Z]{0,2}\/[a-zA-Z]{0,2}\/[a-zA-Z]{0,2})\)/", $exportReport[0], $match)){
			$this->_dateFormat = $match[1];
		}
        
        
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		$connection = false;
		if ($this->_dateFormat != null){
			$connection = true;
		}
		return $connection;
	}
    /**
     * It returns the Merchant CVS report.
     * @return $exportReport
     */
	private function getExportMerchantReport($content){
		$merchantReport = self::formatCsv($content);
		
        $exportData = str_getcsv($merchantReport, "\r\n");
        $merchantReportList = Array();
        $num = count($exportData);
        for ($i = 3; $i < $num; $i++) {
            $merchantExportArray = str_getcsv($exportData[$i], ",");
            
            if ($merchantExportArray[2] != '' && $merchantExportArray[4] != ''){
                $merchantReportList[$merchantExportArray[4]] = $merchantExportArray[2];
            }
        }
        return $merchantReportList;
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
	 * It returns an array with the different merchants
	 * @return array
	 */
	private function getMerchantReportList(){
		$merchantReportList = Array();
		$valuesFormExport = $this->_exportMerchantParameters;
		$valuesFormExport[] = new Oara_Curl_Parameter('programAffiliateStatusId', '3');
				                                
        $urls = array();
        $urls[] = new Oara_Curl_Request('http://www.tradedoubler.com/pan/aReport3Internal.action?', $valuesFormExport);
        $exportReport = $this->_client->post($urls);
        $exportReport[0] = self::checkReportError($exportReport[0], $urls[0]);
        $merchantReportList = self::getExportMerchantReport($exportReport[0]);
        
        $valuesFormExport = $this->_exportMerchantParameters;
		$valuesFormExport[] = new Oara_Curl_Parameter('programAffiliateStatusId', '4');
				                                
        $urls = array();
        $urls[] = new Oara_Curl_Request('http://www.tradedoubler.com/pan/aReport3Internal.action?', $valuesFormExport);
        $exportReport = $this->_client->post($urls);
        $exportReport[0] = self::checkReportError($exportReport[0], $urls[0]);
        $merchantReportListAux = self::getExportMerchantReport($exportReport[0]);
        foreach ($merchantReportListAux as $key => $value){
        	$merchantReportList[$key] = $value;
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
		if ($this->_dateFormat == 'dd/MM/yy'){
	        $startDate = $dStartDate->toString('dd/MM/yyyy');
	        $endDate = $dEndDate->toString('dd/MM/yyyy');
		} else if ($this->_dateFormat == 'M/d/yy') {
			$startDate = $dStartDate->toString('M/d/yy');
	        $endDate = $dEndDate->toString('M/d/yy');
		} else if ($this->_dateFormat == 'd/MM/yy') {
			$startDate = $dStartDate->toString('d/MM/yy');
	        $endDate = $dEndDate->toString('d/MM/yy');
		} else {
			throw new Exception ("\n Date Format not supported ".$this->_dateFormat."\n");
		}

        $valuesFormExport = Oara_Utilities::cloneArray($this->_exportTransactionParameters);
        $valuesFormExport[] = new Oara_Curl_Parameter('startDate', $startDate);
        $valuesFormExport[] = new Oara_Curl_Parameter('endDate', $endDate);
       	$urls = array();
        $urls[] = new Oara_Curl_Request('http://www.tradedoubler.com/pan/aReport3Internal.action?', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
        
        $exportReport[0] = self::checkReportError($exportReport[0], $urls[0]);
		
        
        preg_match_all("/,\"([^\"]+?)\",/", $exportReport[0], $matches);
        foreach ($matches[1] as $match){
        	if (preg_match("/\r\n/", $match)){
        		$rep = preg_replace("/\r\n/","", $match);
        		$exportReport[0] = str_replace($match, $rep, $exportReport[0]);
        	}
        }
        
        $exportData = str_getcsv($exportReport[0],"\r\n");
        $num = count($exportData);
        
        for ($i = 2; $i < $num-1; $i++) {
            $transactionExportArray = str_getcsv($exportData[$i],",");
            
        	if (!isset($transactionExportArray[2])){
        		
				throw new Exception('Problem getting transaction\n\n'.$exportReport[0]);
			}
        	
            if ($transactionExportArray[0] !== '' && in_array((int)$transactionExportArray[2],$merchantList)){
                $transaction = Array();
                $transaction['merchantId'] = $transactionExportArray[2];
                
            	if ($this->_dateFormat == 'dd/MM/yy'){
			       $transactionDate =  new Zend_Date(substr($transactionExportArray[4],0,-4), "dd/MM/yy HH:mm:ss");
				} else if ($this->_dateFormat == 'M/d/yy') {
					$transactionDate =  new Zend_Date(substr($transactionExportArray[4],0,-8), "M/d/yy HH:mm:ss");
				} else if ($this->_dateFormat == 'd/MM/yy') {
					$transactionDate =  new Zend_Date(substr($transactionExportArray[4],0,-4), "d/MM/yy HH:mm:ss");
				} else {
					throw new Exception ("\n Date Format not supported ".$this->_dateFormat."\n");
				}
                
                $transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
                
                if ($transactionExportArray[9] != ''){
                	$transaction['customId'] = $transactionExportArray[9];
                }

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
        $transactionArray = Oara_Utilities::transactionMapPerDay($transactionList);
        
        $mothOverviewUrls = array();
        
        $valuesFormExport = Oara_Utilities::cloneArray($this->_exportOverviewParameters);
        
       	if ($this->_dateFormat == 'dd/MM/yy'){
	    	$valuesFormExport[] = new Oara_Curl_Parameter('startDate', $dStartDate->toString('dd/MM/yy'));
        	$valuesFormExport[] = new Oara_Curl_Parameter('endDate', $dEndDate->toString('dd/MM/yy'));
		} else if ($this->_dateFormat == 'M/d/yy') {
			$valuesFormExport[] = new Oara_Curl_Parameter('startDate', $dStartDate->toString('M/d/yy'));
        	$valuesFormExport[] = new Oara_Curl_Parameter('endDate', $dEndDate->toString('M/d/yy'));
		} else if ($this->_dateFormat == 'd/MM/yy') {
			$valuesFormExport[] = new Oara_Curl_Parameter('startDate', $dStartDate->toString('d/MM/yy'));
        	$valuesFormExport[] = new Oara_Curl_Parameter('endDate', $dEndDate->toString('d/MM/yy'));
		} else {
			throw new Exception ("\n Date Format not supported ".$this->_dateFormat."\n");
		}
        $urls = array();
        $urls[] = new Oara_Curl_Request('http://www.tradedoubler.com/pan/aReport3Internal.action?', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
        
        
        $exportReport[0] = self::checkReportError($exportReport[0], $urls[0]);
        
        $exportData = str_getcsv($exportReport[0],"\r\n");
	            
	    $num = count($exportData);
	    if ($num > 3){
	    	$dateArray = Oara_Utilities::daysOfDifference($dStartDate, $dEndDate);
	    	$dateArraySize = sizeof($dateArray);
	           
	    	for ($i = 0; $i < $dateArraySize; $i++){
	        	$valuesFormExport = Oara_Utilities::cloneArray($this->_exportOverviewParameters);
	                
	            if ($this->_dateFormat == 'dd/MM/yy'){
			    	$valuesFormExport[] = new Oara_Curl_Parameter('startDate', $dateArray[$i]->toString('dd/MM/yy'));
		        	$valuesFormExport[] = new Oara_Curl_Parameter('endDate', $dateArray[$i]->toString('dd/MM/yy'));
				} else if ($this->_dateFormat == 'M/d/yy') {
					$valuesFormExport[] = new Oara_Curl_Parameter('startDate', $dateArray[$i]->toString('M/d/yy'));
		        	$valuesFormExport[] = new Oara_Curl_Parameter('endDate', $dateArray[$i]->toString('M/d/yy'));
				} else if ($this->_dateFormat == 'd/MM/yy') {
					$valuesFormExport[] = new Oara_Curl_Parameter('startDate', $dateArray[$i]->toString('d/MM/yy'));
		        	$valuesFormExport[] = new Oara_Curl_Parameter('endDate', $dateArray[$i]->toString('d/MM/yy'));
				}  else {
					throw new Exception ("\n Date Format not supported ".$this->_dateFormat."\n");
				}
                $mothOverviewUrls[] = new Oara_Curl_Request('http://www.tradedoubler.com/pan/aReport3Internal.action?', $valuesFormExport);
            }                                     
        }
        
        
    	$exportReport = $this->_client->get($mothOverviewUrls);
        $exportReportNumber = count($exportReport);
	    for ($i = 0; $i < $exportReportNumber; $i++){
	    	$exportReport[$i] = self::checkReportError($exportReport[$i], $mothOverviewUrls[$i]);
        	$exportData = str_getcsv($exportReport[$i],"\r\n");
        	$num = count($exportData); 
        	for ($j = 2; $j < $num-1; $j++) {
             	$overviewExportArray = str_getcsv($exportData[$j],";");
	    		$parameter = $mothOverviewUrls[$i]->getParameter(39);
	    		$overviewDate = $parameter->getValue();
	    		
	        	if ($this->_dateFormat == 'dd/MM/yy'){
			    	$overviewDate = new Zend_Date($overviewDate, "dd/MM/yy");
				} else if ($this->_dateFormat == 'M/d/yy') {
					$overviewDate = new Zend_Date($overviewDate, "M/d/yy");
				} else if ($this->_dateFormat == 'd/MM/yy') {
					$overviewDate = new Zend_Date($overviewDate, "d/MM/yy");
				} else {
					throw new Exception ("\n Date Format not supported ".$this->_dateFormat."\n");
				}
				
				if (!isset($overviewExportArray[2])){
					throw new Exception('Problem getting overview\n\n'.$exportReport[$i]);
				}
                
                
            	if ($overviewDate->compare($dStartDate) >= 0 && $overviewDate->compare($dEndDate) <= 0 
            		&& isset($overviewExportArray[2]) && in_array((int)$overviewExportArray[2],$merchantList)){
                	
            		$overview = Array();
                    
                    $overview['merchantId'] = (int)$overviewExportArray[2];
                    $overview['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");

                    $overview['click_number'] = (int)$overviewExportArray[4];
                   	$overview['impression_number'] = (int)$overviewExportArray[3];
                    $overview['transaction_number'] = 0;
                    $overview['transaction_confirmed_value'] = 0;
                    $overview['transaction_confirmed_commission']= 0;
                    $overview['transaction_pending_value']= 0;
                    $overview['transaction_pending_commission']= 0;
                    $overview['transaction_declined_value']= 0;
                    $overview['transaction_declined_commission']= 0;
                    $transactionDateArray = Oara_Utilities::getDayFromArray($overview['merchantId'], $transactionArray, $overviewDate);
                    foreach ($transactionDateArray as $transaction){
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
    
    public function checkReportError($content, $request, $try = 0){

    	if (preg_match("/\/report\/published\/aAffiliateEventBreakdownReport/", $content, $matches)){
        	//report too big, we have to download it and read it
        	if (preg_match("/(\/report\/published\/(aAffiliateEventBreakdownReport(.*))\.zip)/", $content, $matches)){
        		
        		$file = "http://www.tradedoubler.com".$matches[0];
				$newfile = realpath(dirname(__FILE__)).'/../data/pdf/'.$matches[2].'.zip';
				
				if (!copy($file, $newfile)) {
				    throw new Exception('Failing copying the zip file \n\n');
				}
				$zip = new ZipArchive();
				if ($zip->open($newfile, ZIPARCHIVE::CREATE)!==TRUE) {
					throw new Exception('Cannot open zip file \n\n');
				}
				$zip->extractTo(realpath(dirname(__FILE__)).'/../data/pdf');
		        $zip->close();
        		
		        $unzipFilePath = realpath(dirname(__FILE__)).'/../data/pdf/'.$matches[2];
		        $fileContent = file_get_contents($unzipFilePath);
		        unlink($newfile);
		        unlink($unzipFilePath);
		        return $fileContent;
        	}
        	
        	throw new Exception('Report too big \n\n'.$content);
        	
        } else if (preg_match("/ error/", $content, $matches)){
            $urls = array();
	        $urls[] = $request;   
		    $exportReport = $this->_client->get($urls);
		    $try ++;
		    if ($try < 5){
		    	return self::checkReportError($exportReport[0], $request, $try);
		    } else {
		    	throw new Exception('Problem checking report\n\n'.$content);
		    }
		    
        } else {
        	return $content;
        }
    	
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
	    $selectList = $doc->getElementsByTagName('select');
	    $paymentSelect = null;
	    if ($selectList->length > 0){
	    	// looking for the payments select
	    	$it = 0;
	    	while ($it < $selectList->length){
	    		$selectName = $selectList->item($it)->attributes->getNamedItem('name')->nodeValue;
	    		if ($selectName == 'payment_id'){
	    			$paymentSelect = $selectList->item($it);
	    			break;
	    		}
	    		$it++;
	    	}
	    	if ($paymentSelect != null){
			    $paymentLines = $paymentSelect->childNodes;
				for ($i = 0;$i < $paymentLines->length;$i++) {
					$pid = $paymentLines->item($i)->attributes->getNamedItem("value")->nodeValue;
					if (is_numeric($pid)){
						$obj = array();
						
						$paymentLine = $paymentLines->item($i)->nodeValue;
						$paymentLine = htmlentities($paymentLine);
						$paymentLine = str_replace("&Acirc;&nbsp;", "", $paymentLine);
						$paymentLine = html_entity_decode($paymentLine);
						
						if ($this->_dateFormat == 'dd/MM/yy'){
					    	$date = new Zend_Date(substr($paymentLine,0,10), "dd/MM/yy");
						} else if ($this->_dateFormat == 'M/d/yy') {
							$date = new Zend_Date(substr($paymentLine,0,10), "M/d/yy");
						} else if ($this->_dateFormat == 'd/MM/yy') {
							$date = new Zend_Date(substr($paymentLine,0,10), "d/MM/yy");
						}  else {
							throw new Exception ("\n Date Format not supported ".$this->_dateFormat."\n");
						}
						
						$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
						$obj['pid'] = $pid;
						$obj['method'] = 'BACS';
						if (preg_match("/[-+]?[0-9]*,?[0-9]*\.?[0-9]+/", substr($paymentLine,10), $matches)) {
							$obj['value'] = Oara_Utilities::parseDouble($matches[0]);
						} else {
							throw new Exception("Problem reading payments");
						}
						
						$paymentHistory[] = $obj;
					}	
				}
		    }
	    }
    	return $paymentHistory;
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
