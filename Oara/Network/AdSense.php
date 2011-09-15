<?php
/**
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_As
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_AdSense extends Oara_Network{

	/**
	 * Export Overview Parameters
	 * @var array
	 */
	private $_exportOverviewParameters = null;
	/**
	 * Export Payment Parameters
	 * @var array
	 */
	private $_exportPaymentParameters = null;
	/**
	 * Client
	 * @var unknown_type
	 */
	private $_client = null;
	/**
	 * Constructor and Login
	 * @param $buy
	 * @return Oara_Network_Buy_Api
	 */
	public function __construct($credentials)
	{

		$user = $credentials['user'];
        $password = $credentials['password'];
        $emailChallenge = null;
        if (isset($credentials['emailChallenge']) && $credentials['emailChallenge'] != null){
        	$emailChallenge = $credentials['emailChallenge'];
        }
		$phoneChallenge = null;
        if (isset($credentials['phoneChallenge']) && $credentials['phoneChallenge'] != null){
        	$phoneChallenge = $credentials['phoneChallenge'];
        }
       

		// /adsense/
		$this->_client = new Oara_Curl_Access('https://www.google.com/adsense/', array(), $credentials);
		

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://www.google.com/adsense/signout', array());
		$contentList = $this->_client->get($urls);

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://www.google.com/adsense/login-box.js', array());

		$contentList = $this->_client->get($urls);
		$content = $contentList[0];
		$content = preg_replace(
		array("/\\\\75/", "/\\\\42/", "/\\\\46/", "/\\\\075/"),
		array('=', '"', '&', '='),
		$content
		);
		preg_match('/src="([^"]+)"/', $content, $match);
		$next_url = $match[1];
		$next_url = str_replace('&amp;', '&', $next_url);

		$urls = array();
		$urls[] = new Oara_Curl_Request($next_url, array());
		$contentList = $this->_client->get($urls);



		
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->validateOnParse = true;
		$doc->loadHTML($contentList[0]);
		$hiddenList = $doc->getElementsByTagName('input');
		$hiddenMap = array();
		$nodeListLength = $hiddenList->length; // this value will also change
		for ($i = 0; $i < $nodeListLength; $i ++)
		{
			$node = $hiddenList->item($i);
			if ($node->getAttribute( 'type' ) == 'hidden'){
				$hiddenMap[$node->getAttribute( 'name' )] = $node->getAttribute( 'value' );
			}
		}
		$valuesLogin = array();
		foreach($hiddenMap as $key => $value) {
			$valuesLogin[] = new Oara_Curl_Parameter($key, $value);
		}
		$valuesLogin[] = new Oara_Curl_Parameter('Email', $user);
		$valuesLogin[] = new Oara_Curl_Parameter('Passwd' , $password);
		$valuesLogin[] = new Oara_Curl_Parameter('signIn' , 'Sign in');

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://accounts.google.com/ServiceLoginAuth', $valuesLogin);
		$content = $this->_client->post($urls);
		if (!preg_match("/href=\"\/adsense\/signout\"/", $content[0], $matches)) {
			$urls = array();
			$urls[] = new Oara_Curl_Request('https://www.google.com/adsense/v3/disablebeta', array());
			$content = $this->_client->get($urls);
			
			$urls = array();
			$urls[] = new Oara_Curl_Request('https://www.google.com/accounts/ServiceLoginAuth', $valuesLogin);
			$content = $this->_client->post($urls);
		}
		
		
		$dom = new Zend_Dom_Query(current($content));
		$results = $dom->query('#challengeform');
		//We have to provide the challenge
		$count = count($results);
		if ($count > 0){
			
			$challenge = array();
			$challenge[] = new Oara_Curl_Parameter('continue', 'https://www.google.com/adsense/gaiaauth2?destination=/adsense/home');
			$challenge[] = new Oara_Curl_Parameter('jsenabled' , 'true');
			
			$results = $dom->query('#RecoveryEmailChallengeInput');
			$count = count($results);
			if ($count > 0 && $emailChallenge != null ){
				$challenge[] = new Oara_Curl_Parameter('emailAnswer' , $emailChallenge);
				$challenge[] = new Oara_Curl_Parameter('challengetype' , 'RecoveryEmailChallenge');
			} else {
				$results = $dom->query('#PhoneVerificationChallengeInput');
				$count = count($results);
				if ($count > 0 && $phoneChallenge != null){
					$challenge[] = new Oara_Curl_Parameter('phoneNumber' , $phoneChallenge);
					$challenge[] = new Oara_Curl_Parameter('challengetype' , 'PhoneVerificationChallenge');
				}	
			}
			
			$challenge[] = new Oara_Curl_Parameter('answer' , '');
			$challenge[] = new Oara_Curl_Parameter('address' , '');
			$challenge[] = new Oara_Curl_Parameter('submitChallenge' , '');
			
			if ($emailChallenge != null || $phoneChallenge != null){
				$urls = array();
				$urls[] = new Oara_Curl_Request('https://www.google.com/accounts/LoginVerification?Email='.$user.'&continue=https://www.google.com/adsense/gaiaauth2?destination=/adsense/home&service=adsense', $challenge);
				$content = $this->_client->post($urls);	
			}
			
		}
		
		if (!preg_match("/href=\"\/adsense\/signout\"/", $content[0], $matches)) {
			preg_match('/location\.replace\("(.+?)"\)/', $content[0], $match);
			if (!isset($match[1])){
				throw new Exception('Login problem on google Ad Sense');
			}
			$next_url = $match[1];

			$next_url = urldecode($next_url);
			$urls = array();
			$urls[] = new Oara_Curl_Request($next_url, array());
			$content = $this->_client->get($urls);
		}


		$this->_exportOverviewParameters = array(new Oara_Curl_Parameter('sortColumn', '0'),
		new Oara_Curl_Parameter('reverseSort', 'false'),
		new Oara_Curl_Parameter('outputFormat', 'TSV_EXCEL'),
		new Oara_Curl_Parameter('storedReportId', '-1'),
		new Oara_Curl_Parameter('isOldReport', 'false'),
		new Oara_Curl_Parameter('piStart', '-1'),
		new Oara_Curl_Parameter('reportType', 'channel'),
		new Oara_Curl_Parameter('searchField', '')
		);
			
	}
	/**
	 * Check the connection
	 */
	public function checkConnection(){
		$connection = false;

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://www.google.com/adsense/report/overview', array());
		$content = $this->_client->get($urls);
		if (preg_match("/signout/", $content[0], $matches)) {
			$connection = true;
		}
		return $connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getMerchantList()
	 */
	public function getMerchantList($merchantMap = array()){
		$merchants = Array();
			
		$obj = array();
		$obj['cid'] = 1;
		$obj['name'] = "Google AdSense";
		$obj['url'] = "www.google.com";
		$merchants[] = $obj;

		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
		$totalTransactions = array();
		return $totalTransactions;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Interface#getOverviewList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getOverviewList($transactionList = null, $merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null){
		$overviewArray = array();

		$valuesFromExport = Oara_Utilities::cloneArray($this->_exportOverviewParameters);
		$valuesFromExport[] = new Oara_Curl_Parameter('dateRange.simpleDate', 'today');
		$valuesFromExport[] = new Oara_Curl_Parameter('dateRange.dateRangeType', 'custom');
		$valuesFromExport[] = new Oara_Curl_Parameter('dateRange.customDate.start.year', $dStartDate->get(Zend_Date::YEAR));
		$valuesFromExport[] = new Oara_Curl_Parameter('dateRange.customDate.start.month', $dStartDate->get(Zend_Date::MONTH));
		$valuesFromExport[] = new Oara_Curl_Parameter('dateRange.customDate.start.day', $dStartDate->get(Zend_Date::DAY));
		$valuesFromExport[] = new Oara_Curl_Parameter('dateRange.customDate.end.year', $dEndDate->get(Zend_Date::YEAR));
		$valuesFromExport[] = new Oara_Curl_Parameter('dateRange.customDate.end.month', $dEndDate->get(Zend_Date::MONTH));
		$valuesFromExport[] = new Oara_Curl_Parameter('dateRange.customDate.end.day', $dEndDate->get(Zend_Date::DAY));

		$urls = array();
		$urls[] = new Oara_Curl_Request('https://www.google.com/adsense/report/aggregate?', array());
		$content = $this->_client->get($urls);

		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->validateOnParse = true;
		$doc->loadHTML($content[0]);
		$productSelect = $doc->getElementsByTagName('select');

		$modeList = array();
		$children = $productSelect->item(1)->childNodes;
		foreach ($children as $child) {
			$attrs = $child->attributes;
			foreach ($attrs as $attrName => $attrNode){
				if ($attrName == 'value'){
					$modeList[] = $attrNode->nodeValue;
				}
			}
		}


		$reportsUrls = array();
		$requestUrl = 'https://www.google.com/adsense/report/aggregate?';
		//$modeList = array('afd', 'aff', 'afc', 'afs', 'ref', 'afcm');
		foreach ($modeList as $mode){
			$request = Oara_Utilities::cloneArray($valuesFromExport);
			$request[] = new Oara_Curl_Parameter('product', $mode);
			if ($mode == 'afc'){
				$request[] = new Oara_Curl_Parameter('radlinkChoice', 'COMBINED');
				$request[] = new Oara_Curl_Parameter('unitPref', 'page');
				$request[] = new Oara_Curl_Parameter('groupByPref', 'both');
			} else if ($mode == 'afs'){
				$request[] = new Oara_Curl_Parameter('reportType', 'channel');
				$request[] = new Oara_Curl_Parameter('groupByPref', 'both');
			} else if ($mode == 'ref'){
				$requestUrl = 'https://www.google.com/adsense/report/referrals?';

				$request[] = new Oara_Curl_Parameter('reportType', 'property');
				$request[] = new Oara_Curl_Parameter('groupByPref', 'date');
				$request[] = new Oara_Curl_Parameter('selection_criteria', 'specific_products');
				$request[] = new Oara_Curl_Parameter('allProducts', 'false');
				$request[] = new Oara_Curl_Parameter('productGroupByPref', 'date');
			} else if ($mode == 'afcm'){
				$request[] = new Oara_Curl_Parameter('unitPref', 'page');
				$request[] = new Oara_Curl_Parameter('reportType', 'property');
				$request[] = new Oara_Curl_Parameter('groupByPref', 'date');

			} else if ($mode == 'afd'){
				$request[] = new Oara_Curl_Parameter('reportType', 'domain');
				$request[] = new Oara_Curl_Parameter('groupByPref', 'date');
				$request[] = new Oara_Curl_Parameter('showAllDomainsOption', 'true');
				$request[] = new Oara_Curl_Parameter('domainGroupByPref', 'both');
				$request[] = new Oara_Curl_Parameter('pd', '-3977617293257148564');
			} else if ($mode == 'aff'){
				$request[] = new Oara_Curl_Parameter('reportType', 'channel');
				$request[] = new Oara_Curl_Parameter('groupByPref', 'both');
			}
				
			$modeParams = array();
			$modeParams[] = new Oara_Curl_Parameter('product', $mode);
			$urls = array();
			$urls[] = new Oara_Curl_Request($requestUrl, $modeParams);
			$content = $this->_client->get($urls);
			$doc = new DOMDocument();
			libxml_use_internal_errors(true);
			$doc->validateOnParse = true;
			$doc->loadHTML($content[0]);
			$select = $doc->getElementsByTagName('select');
			for ($i = 0;$i < $select->length;$i++) {
				$selectItem = $select->item($i);
				$selectId = $selectItem->attributes->getNamedItem("id");
				if ($selectId != null &&
				$selectId->nodeValue == "channels_selector-select"){
						
					$activeUrlChannels = $selectItem->childNodes->item(0)->childNodes;
					for ($j = 0;$j < $activeUrlChannels->length;$j++) {
						$channel = $activeUrlChannels->item($j);
						$request[] =  new Oara_Curl_Parameter('c.id', $channel->attributes->getNamedItem("value")->nodeValue);
					}
				}
			}
			$reportsUrls[] = new Oara_Curl_Request($requestUrl, $request);
		}
		$firstDayMonth = new Zend_Date();
		$firstDayMonth->setDay(1);
		$firstDayMonth->setHour("00");
		$firstDayMonth->setMinute("00");
		$firstDayMonth->setSecond("00");
		$contentList = $this->_client->post($reportsUrls);
		for ($i = 0;$i < count($contentList); $i++){
			$mode = $reportsUrls[$i]->getParameter(16)->getValue();
			$exportData = str_getcsv(@iconv('UTF-16', 'UTF-8',$contentList[$i]),"\n");

			$num = count($exportData);
			$parameterMerchantId = 1;
			for ($j = 1; $j < $num-2; $j++) {
				$overviewExportArray = str_getcsv($exportData[$j],"\t");
					
				$obj = array();
				$obj['merchantId'] = $parameterMerchantId;
				$overviewDate = new Zend_Date($overviewExportArray[0],"yyyy-MM-dd");
				$overviewDate->setHour("00");
				$overviewDate->setMinute("00");
				$overviewDate->setSecond("00");
				$obj['date'] = $overviewDate->toString("yyyy-MM-dd HH:mm:ss");
				 
				$obj['link'] = $mode;
				$obj['transaction_number'] = 0;
				$obj['transaction_confirmed_commission'] =  0;
				$obj['transaction_confirmed_value'] =  0;
				$obj['transaction_pending_commission'] =  0;
				$obj['transaction_pending_value'] =  0;
				$obj['transaction_declined_commission'] = 0;
				$obj['transaction_declined_value'] = 0;
				 
				 
				if ($obj['link'] == 'ref') {
					$obj['impression_number'] = (int)Oara_Utilities::parseDouble($overviewExportArray[1]);
					$obj['click_number'] =  Oara_Utilities::parseDouble($overviewExportArray[2]);
						
					if ($firstDayMonth->compare($overviewDate) <= 0){
						$obj['transaction_pending_commission'] = Oara_Utilities::parseDouble($overviewExportArray[6]);
						$obj['transaction_pending_value'] = Oara_Utilities::parseDouble($overviewExportArray[6]);
					} else {
						$obj['transaction_confirmed_commission'] =  Oara_Utilities::parseDouble($overviewExportArray[6]);
						$obj['transaction_confirmed_value'] =  Oara_Utilities::parseDouble($overviewExportArray[6]);
					}
				} else {

					if (!is_numeric($overviewExportArray[1])){
						$obj['website'] = $overviewExportArray[1];
						$obj['impression_number'] = (int)Oara_Utilities::parseDouble($overviewExportArray[2]);
						$obj['click_number'] =  Oara_Utilities::parseDouble($overviewExportArray[3]);
						if ($firstDayMonth->compare($overviewDate) <= 0){
							$obj['transaction_pending_commission'] = Oara_Utilities::parseDouble($overviewExportArray[6]);
							$obj['transaction_pending_value'] = Oara_Utilities::parseDouble($overviewExportArray[6]);
						} else {
							$obj['transaction_confirmed_commission'] =  Oara_Utilities::parseDouble($overviewExportArray[6]);
							$obj['transaction_confirmed_value'] =  Oara_Utilities::parseDouble($overviewExportArray[6]);
						}
					} else {
						$obj['impression_number'] = (int)Oara_Utilities::parseDouble($overviewExportArray[1]);
						$obj['click_number'] =  Oara_Utilities::parseDouble($overviewExportArray[2]);
						if ($firstDayMonth->compare($overviewDate) <= 0){
							$obj['transaction_pending_commission'] = Oara_Utilities::parseDouble($overviewExportArray[5]);
							$obj['transaction_pending_value'] = Oara_Utilities::parseDouble($overviewExportArray[5]);
						} else {
							$obj['transaction_confirmed_commission'] =  Oara_Utilities::parseDouble($overviewExportArray[5]);
							$obj['transaction_confirmed_value'] =  Oara_Utilities::parseDouble($overviewExportArray[5]);
						}
					}

				}
				
				if (Oara_Utilities::checkRegister($obj)){
					$overviewArray[] = $obj;
				}
			}
		}
		unset ($reportsUrls);
		unset ($contentList);
		return $overviewArray;
	}
	/**
	 * (non-PHPdoc)
	 * @see Oara/Network/Oara_Network_Base#getPaymentHistory()
	 */
	public function getPaymentHistory(){
		$paymentHistory = array();
		$urls = array();
		$urls[] = new Oara_Curl_Request('https://www.google.com/adsense/reports-payment?csv=true&reportRange=ALL_TIME&historical=false', array());
		$content = $this->_client->get($urls);
		$exportData = str_getcsv(@iconv('UTF-16', 'UTF-8',$content[0]),"\n");
		$num = count($exportData);
		for ($j = 1; $j < $num; $j++) {
			$paymentExportArray = str_getcsv($exportData[$j],"\t");
			if ($paymentExportArray[1] == "Payment issued"){
				$obj = array();
				$date = new Zend_Date($paymentExportArray[0], "MM/dd/yy");
				$obj['date'] = $date->toString("yyyy-MM-dd HH:mm:ss");
				$obj['pid'] = $date->toString("yyMMdd");
				$obj['method'] = 'BACS';
				$obj['value'] = abs(Oara_Utilities::parseDouble($paymentExportArray[2]));
				$paymentHistory[] = $obj;
			}
		}
		return $paymentHistory;
	}
}
