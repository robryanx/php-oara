<?php
/**
 * Utilities Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Utilities
{
	/**
	 * confirmed status
	 * @var string
	 */
	const STATUS_CONFIRMED = 'confirmed';
	/**
	 * pending status
	 * @var string
	 */
	const STATUS_PENDING = 'pending';
	/**
	 * declined status
	 * @var string
	 */
	const STATUS_DECLINED = 'declined';



	/**
	 * It returns the value's position in the array for a type.
	 * @param array $array
	 * @param $type
	 * @param $value
	 * @return object
	 */
	public static function arrayFetchValue(array $array, $type, $value){
		$returnValue = null;
		$i = 0;
		$enc = false;
		while ($i < count($array) && !$enc){
			$element = $array[$i];
			$elementValue = $element[$type];
			if($value == $elementValue){
				$enc = true;
				$returnValue = $element;
			}
			$i++;
		}
		return $returnValue;
	}
	/**
	 * Soap results to Data Base results
	 * @param array $soapResults
	 * @param array $converterRules
	 * @return array
	 */
	public static function soapConverter(array $soapResults, array $converterRules){
		$convertion = array();
		foreach ($soapResults as $soapResult){
			$objectValue = array();
			foreach ($converterRules as  $key=>$rule){
				$groupRule = explode(',', $rule);
				foreach ($groupRule as $individualRule){
					if(isset($soapResult->$key)){
						$attribute = $soapResult->$key;
						if($attribute !== null){
							$objectValue[$individualRule]= $attribute;
						}
					}
				}
				
			}
			$convertion[] = $objectValue;
		}
		unset($soapResults);
		return $convertion;
	}
	/**
	 *
	 * Return an array with the different years
	 * @param $starDate
	 * @param $endDate
	 */
	public static function yearsOfDifference(Zend_Date $starDate = null, Zend_Date $endDate = null){
		if($starDate->compare($endDate)>0){
			throw new Exception ('The start date can not be later than the end date');
		}


		$difference = ($endDate->get(Zend_Date::YEAR) - $starDate->get(Zend_Date::YEAR));
		$dateArray = array();
		$dateArray[] = clone $starDate;
		/**If there are more than 1 month of difference ,
		 the next  element starts in the first day of the month**/
		for($i=0;$i<$difference;$i++){
			$auxDate = clone $starDate;
			$auxDate->addYear($i+1);
			$auxDate->setDay(1);
			$auxDate->setMonth(1);
			$dateArray[] = $auxDate;
		}

		return $dateArray;
	}
	/**
	 * Return an array with the different dates between two dates, one element per month.
	 * @param Zend_Date $starDate
	 * @param Zend_Date $endDate
	 * @return array
	 */
	public static function monthsOfDifference(Zend_Date $starDate = null, Zend_Date $endDate = null, $gap = 1){
		if($starDate->compare($endDate)>0){
			throw new Exception ('The start date can not be later than the end date');
		}

		$monthsOfDifferenceBetweenYears = ($endDate->get(Zend_Date::YEAR) - $starDate->get(Zend_Date::YEAR))*12;
		$difference = (int)((($endDate->get(Zend_Date::MONTH) + $monthsOfDifferenceBetweenYears) - $starDate->get(Zend_Date::MONTH))/$gap);
		$dateArray = array();
		$dateArray[] = clone $starDate;
		/**If there are more than 1 month of difference ,
		 the next  element starts in the first day of the month**/
		for($i = 0;$i < $difference;$i++){
			$auxDate = clone $starDate;
			$auxDate->addMonth(($i+1)*$gap);
			$auxDate->setDay(1);
			$dateArray[] = $auxDate;
		}

		return $dateArray;
	}
	/**
	 * Return an array with the different dates between two dates, one element per day.
	 * @param Zend_Date $starDate
	 * @param Zend_Date $endDate
	 * @return array
	 */
	public static function daysOfDifference(Zend_Date $starDate = null, Zend_Date $endDate = null){
		if($starDate->compare($endDate)>0){
			throw new Exception ('The start date can not be later than the end date');
		}
		$difference = intval(self::numberOfDaysBetweenTwoDates($starDate, $endDate));
		$dateArray = array();
		$dateArray[] = clone $starDate;
		/**If there are more than 1 month of difference ,
		 the next  element starts in the first day of the month**/
		for($i=0;$i<$difference;$i++){
			$auxDate = clone $starDate;
			$auxDate->addDay($i+1);
			$dateArray[] = $auxDate;
		}

		return $dateArray;
	}
	/**
	 * Return an array with the different dates between two dates, one element per week.
	 * @param Zend_Date $starDate
	 * @param Zend_Date $endDate
	 * @return array
	 */
	public static function weeksOfDifference(Zend_Date $starDate = null, Zend_Date $endDate = null){
		if($starDate->compare($endDate)>0){
			throw new Exception ('The start date can not be later than the end date');
		}
		$auxStartDate = clone $starDate;
		$weekDay = $starDate->get(Zend_Date::WEEKDAY_DIGIT);
		$subDays = ($weekDay+6)%7;
		$auxStartDate->subDay($subDays);

		$difference = intval(self::numberOfDaysBetweenTwoDates($auxStartDate, $endDate)/7);

		$dateArray = array();
		$dateArray[] = clone $starDate;
		/**If there are more than 1 month of difference ,
		 the next  element starts in the first day of the month**/
		for($i=0;$i < $difference;$i++){
			$auxDate = clone $auxStartDate;
			$auxDate->addWeek($i+1);
			$dateArray[] = $auxDate;
		}


		return $dateArray;
	}
	/**
	 * Return the number of days between two Dates.
	 * @param Zend_Date $starDate
	 * @param Zend_Date $endDate
	 * @return int
	 */
	public static function numberOfDaysBetweenTwoDates(Zend_Date $starDate = null, Zend_Date $endDate = null){
		$starDate = clone $starDate;
		$endDate = clone $endDate;
		if($starDate->compare($endDate)>0){
			throw new Exception ('The start date can not be later than the end date');
		}
		$diff = $endDate->getTimestamp() - $starDate->getTimestamp();
		return $diff / 60 / 60 / 24;
	}
	/**
	 * Clone the array.
	 * @param array $cloneArray
	 * @return array
	 */
	public static function cloneArray(array $cloneArray){
		$returnArray = array();
		foreach($cloneArray as $element){
			$returnArray[] = clone $element;
		}
		return $returnArray;
	}
	/**
	 * Bubble Sort, order ASC.
	 * @param array $dataArray
	 * @return array
	 */
	public static function bubbleSort(array $dataArray){
		$count = count($dataArray);
		for($i=0; $i<$count-1; $i++){
			for($j=$i+1; $j<$count; $j++)
			{
				if ($dataArray[$i]->compare($dataArray[$j]) >= 0)
				{
					$tmp = $dataArray[$i];
					$dataArray[$i] = $dataArray[$j];
					$dataArray[$j] = $tmp;
				}

			}
		}

		return $dataArray;
	}
	/**
	 * 
	 * Compare date by strings
	 * @param unknown_type $a
	 * @param unknown_type $b
	 */
	public static function compareDates($a, $b){
		return strcmp($a['date'], $b['date']);
	}
	/**
	 * Bubble Sort, order ASC.
	 * @param array $dataArray
	 * @return array
	 */
	public static function registerBubbleSort(array $dataArray){
		usort($dataArray, array("Oara_Utilities", "compareDates"));

		return $dataArray;
	}
	/**
	 * Returns true if there is some attribute distinct than zero.
	 * @param array $entity
	 * @param $attributes
	 * @return boolean
	 */
	public static function attributeDistinctThanZero($entity, array $attributes){
		$result = false;
		$i = 0;
		$long = count($attributes);
		while ($i < $long && !$result){
			if($entity->$attributes[$i] != 0){
				$result = true;
			}
			$i++;
		}
			
		return $result;
	}
	/**
	 * Encrypted Password
	 * @param $pass
	 * @return string
	 */
	public static function encodePassword($pass) {
		$newPassword = "";
		$passIndex = 0;
		$passIterations = (int)strlen($pass)/2;
		for($i = 1; $i < $passIterations ; $i++){
			$code = md5(uniqid(rand(), true));
			$passwordKey = substr($code, 0, 2);
			$newPassword .= substr($pass, $passIndex, 2).$passwordKey;
			$passIndex += 2;
		}
		$newPassword .= substr($pass, $passIndex);
		
		return base64_encode($newPassword);
	}
	
	
	/**
	 * Decrypt Password
	 * @param $pass
	 * @return string
	 */
	
	public static function decodePassword($pass) {
		$newPassword = "";
		$passIndex = 0;
		$pass = base64_decode($pass);
		$passIterations = (int)strlen($pass)/4;
		for($i = 1; $i < $passIterations ; $i++){
			$newPassword .= substr($pass, $passIndex, 2);
			$passIndex += 4;
		}
		$newPassword .= substr($pass, $passIndex);
		return $newPassword;
		
	}
	
	/**
	 * Set the criteria for the datePeriod Select
	 * @param $criteriaList
	 * @param $formData
	 * @return array
	 */
	public static function datePeriodSelectCriteria(array $criteriaList, array $formData, $path){
		if (isset($formData['datePeriodSelect']) && $formData['datePeriodSelect'] != null){
			if ($formData['datePeriodSelect'] == 'last30Days'){
				$startDate = new Zend_Date();
				$startDate->subDay(30);
				$startDate->setHour(00);
				$startDate->setMinute(00);
				$startDate->setSecond(00);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Ge($path, $startDate->toString("yyyy-MM-ddTHH:mm:ss"));

				$endDate = new Zend_Date();
				$endDate->setHour(23);
				$endDate->setMinute(59);
				$endDate->setSecond(59);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Le($path, $endDate->toString("yyyy-MM-ddTHH:mm:ss"));

			} else if ($formData['datePeriodSelect'] == 'yesterday'){
				$startDate = new Zend_Date();
				$startDate->subDay(1);
				$startDate->setHour(00);
				$startDate->setMinute(00);
				$startDate->setSecond(00);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Ge($path, $startDate->toString("yyyy-MM-ddTHH:mm:ss"));

				$endDate = new Zend_Date();
				$endDate->subDay(1);
				$endDate->setHour(23);
				$endDate->setMinute(59);
				$endDate->setSecond(59);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Le($path, $endDate->toString("yyyy-MM-ddTHH:mm:ss"));

			} else if ($formData['datePeriodSelect'] == 'thisWeek'){
				$startDate = new Zend_Date();
				$daysToBeggining = 6;
				if($startDate->get(Zend_Date::WEEKDAY_DIGIT) != 0){
					$daysToBeggining = $startDate->get(Zend_Date::WEEKDAY_DIGIT) - 1;
				}
				//echo $daysToBeggining;
				$startDate->subDay($daysToBeggining);
				$startDate->setHour(00);
				$startDate->setMinute(00);
				$startDate->setSecond(00);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Ge($path, $startDate->toString("yyyy-MM-ddTHH:mm:ss"));

				$endDate = new Zend_Date();
				$endDate->setHour(23);
				$endDate->setMinute(59);
				$endDate->setSecond(59);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Le($path, $endDate->toString("yyyy-MM-ddTHH:mm:ss"));

			} else if ($formData['datePeriodSelect'] == 'lastWeek'){
				$startDate = new Zend_Date();
				$daysToBeggining = 13;
				if($startDate->get(Zend_Date::WEEKDAY_DIGIT) != 0){
					$daysToBeggining = 7 + $startDate->get(Zend_Date::WEEKDAY_DIGIT) - 1;
				}
				$startDate->subDay($daysToBeggining);
				$startDate->setHour(00);
				$startDate->setMinute(00);
				$startDate->setSecond(00);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Ge($path, $startDate->toString("yyyy-MM-ddTHH:mm:ss"));

				$endDate = new Zend_Date();
				$endDate->subDay($daysToBeggining - 6);
				$endDate->setHour(23);
				$endDate->setMinute(59);
				$endDate->setSecond(59);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Le($path, $endDate->toString("yyyy-MM-ddTHH:mm:ss"));
				
			} else if ($formData['datePeriodSelect'] == 'lastTwoWeeks'){
				$startDate = new Zend_Date();
				$daysToBeggining = 20;
				if($startDate->get(Zend_Date::WEEKDAY_DIGIT) != 0){
					$daysToBeggining = 14 + $startDate->get(Zend_Date::WEEKDAY_DIGIT) - 1;
				}
				$startDate->subDay($daysToBeggining);
				$startDate->setHour(00);
				$startDate->setMinute(00);
				$startDate->setSecond(00);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Ge($path, $startDate->toString("yyyy-MM-ddTHH:mm:ss"));

				$endDate = new Zend_Date();
				$endDate->subDay($daysToBeggining - 13);
				$endDate->setHour(23);
				$endDate->setMinute(59);
				$endDate->setSecond(59);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Le($path, $endDate->toString("yyyy-MM-ddTHH:mm:ss"));
				
			} else if ($formData['datePeriodSelect'] == 'thisMonth'){
				$startDate = new Zend_Date();
				$startDate->setDay(1);
				$startDate->setHour(00);
				$startDate->setMinute(00);
				$startDate->setSecond(00);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Ge($path, $startDate->toString("yyyy-MM-ddTHH:mm:ss"));

				$endDate = new Zend_Date();
				$endDate->setHour(23);
				$endDate->setMinute(59);
				$endDate->setSecond(59);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Le($path, $endDate->toString("yyyy-MM-ddTHH:mm:ss"));

			} else if ($formData['datePeriodSelect'] == 'lastMonth'){
				$startDate = new Zend_Date();
				$startDate->setDay(1);
				$startDate->subMonth(1);
				$startDate->setHour(00);
				$startDate->setMinute(00);
				$startDate->setSecond(00);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Ge($path, $startDate->toString("yyyy-MM-ddTHH:mm:ss"));

				$endDate = new Zend_Date();
				$endDate->setDay(1);
				$endDate->subDay(1);
				$endDate->setHour(23);
				$endDate->setMinute(59);
				$endDate->setSecond(59);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Le($path, $endDate->toString("yyyy-MM-ddTHH:mm:ss"));

			} else if ($formData['datePeriodSelect'] == 'lastThreeMonths'){
				$startDate = new Zend_Date();
				$startDate->setDay(1);
				$startDate->subMonth(3);
				$startDate->setHour(00);
				$startDate->setMinute(00);
				$startDate->setSecond(00);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Ge($path, $startDate->toString("yyyy-MM-ddTHH:mm:ss"));

				$endDate = new Zend_Date();
				$endDate->setDay(1);
				$endDate->subDay(1);
				$endDate->setHour(23);
				$endDate->setMinute(59);
				$endDate->setSecond(59);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Le($path, $endDate->toString("yyyy-MM-ddTHH:mm:ss"));

			} else if ($formData['datePeriodSelect'] == 'lastSixMonths'){
				$startDate = new Zend_Date();
				$startDate->setDay(1);
				$startDate->subMonth(6);
				$startDate->setHour(00);
				$startDate->setMinute(00);
				$startDate->setSecond(00);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Ge($path, $startDate->toString("yyyy-MM-ddTHH:mm:ss"));

				$endDate = new Zend_Date();
				$endDate->setDay(1);
				$endDate->subDay(1);
				$endDate->setHour(23);
				$endDate->setMinute(59);
				$endDate->setSecond(59);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Le($path, $endDate->toString("yyyy-MM-ddTHH:mm:ss"));

			} else if ($formData['datePeriodSelect'] == 'allTime'){
					

			} else if ($formData['datePeriodSelect'] == 'custom'){
				if (isset($formData['startDate']) && $formData['startDate'] != null){
					$startDate = new Zend_Date($formData['startDate'], 'dd/MM/yyyy');
					$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Ge($path, $startDate->toString("yyyy-MM-ddTHH:mm:ss"));
				}
				if (isset($formData['endDate']) && $formData['endDate'] != null){
					$endDate = new Zend_Date($formData['endDate'], 'dd/MM/yyyy');
					$endDate->setHour(23);
					$endDate->setMinute(59);
					$endDate->setSecond(59);
					$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Le($path, $endDate->toString("yyyy-MM-ddTHH:mm:ss"));
				}
			}
		}
		return $criteriaList;
	}
	/**
	 * Get the day for this transaction array
	 * @param map $dateArray
	 * @param Zend_Date $date
	 * @return array
	 */
	public static function getDayFromArray($merchantId, $dateArray, Zend_Date $date){
		$resultArray = array();
		if (isset($dateArray[$merchantId])){
			$dateString = $date->toString("yyyy-MM-dd");
			if (isset($dateArray[$merchantId][$dateString])){
				$resultArray = $dateArray[$merchantId][$dateString];
			}
		}
		return $resultArray;
	}

	/**
	 * Check If the register has interesting information
	 * @param array $register
	 * @param array $properties
	 * @return boolean
	 */
	public static function checkRegister(array $register){
		$ok = false;
		$i = 0;
		$properties = array(
	                        'click_number',
	                        'impression_number',
	                        'transaction_number',
	                        'transaction_confirmed_value',
	                        'transaction_confirmed_commission',
	                        'transaction_pending_value',
	                        'transaction_pending_commission',
	                        'transaction_declined_value',
	                        'transaction_declined_commission'
	                        );
	                        while ($i < count($properties) && !$ok){
	                        	if ($register[$properties[$i]] != 0){
	                        		$ok = true;
	                        	}
	                        	$i++;
	                        }
	                        return $ok;
	}
	/**
	 * Filter the transactionList per day
	 * @param array $transactionList
	 * @return array
	 */
	public static function transactionMapPerDay(array $transactionList){
		$transactionMap = array();
		foreach ($transactionList as $transaction){
			if (!isset($transactionMap[$transaction['merchantId']])){
				$transactionMap[$transaction['merchantId']] = array();
			}
			$dateString = substr($transaction['date'], 0, 10);
			if (!isset($transactionMap[$transaction['merchantId']][$dateString])){
				$transactionMap[$transaction['merchantId']][$dateString] = array();
			}
			$transactionMap[$transaction['merchantId']][$dateString][] = $transaction;
		}
			
		return $transactionMap;
	}

	/**
	 * Parse Double, delete odd characters.
	 * @param $data
	 * @return double
	 */
	public static function parseDouble($data){
		$double = 0;
		if($data != null){
			
			$bits = explode(",",trim($data)); // split input value up to allow checking
       
        	$last = strlen($bits[count($bits) -1]); // gets part after first comma (thousands (or decimals if incorrectly used by user)
	        if ($last <3){ // checks for comma being used as decimal place
	            $convertnum = str_replace(",",".",trim($data));
	        } else {
	        	$convertnum = str_replace(",","",trim($data));
	        }
	        $double = number_format((float)$convertnum, 2, '.', '');
		}
		return $double;
	}
	/**
	 * Makes directory, returns TRUE if exists or made
	 *
	 * @param string $pathname The directory path.
	 * @return boolean returns TRUE if exists or made or FALSE on failure.
	 */
	public static function mkdir_recursive($dir, $mode)
	{
		$return = false;
		if (is_dir($dir) || mkdir($dir,$mode,true)){
			$return = true;
		}
		
		return $return;
	}
	/**
	 * Delete From criteriaList
	 * @param  $criteriaList
	 * @param  $restriction
	 */
	public static function deleteFromCriteriaList($criteriaList, $restriction, $property = null){
		$newList = array();
		foreach ($criteriaList as $criteria){
			if ($property == null) {
				if (!$criteria instanceof $restriction){
					$newList[] = $criteria;
				}
			} else {
				$propertyList = $criteria->getProperty();
				$criteriaProperty = $propertyList[0];
				if (!$criteria instanceof $restriction && $criteriaProperty != $property){
					$newList[] = $criteria;
				}
			}
		}
		return $newList;
	}

	/**
	 * Choose the amount format for the charts tooltip
	 * @param $amount
	 * @param $compareSelectValue
	 * @return string
	 */
	public static function chooseAmountFormat($amount , $compareSelectValue, $currency){
		$amountFormatted = $currency->toCurrency($amount);
		
		if($compareSelectValue == 'click_number'){
			$amountFormatted = $currency->toCurrency(doubleval($amount), array('display' => Zend_Currency::NO_SYMBOL,'precision' => 0))." ".Core::getRegistry()->get('translate')->_("common.clicks");
		} else if ($compareSelectValue == 'transaction_number'){
			$amountFormatted = $currency->toCurrency(doubleval($amount), array('display' => Zend_Currency::NO_SYMBOL,'precision' => 0))." ".Core::getRegistry()->get('translate')->_("common.transaction");
		} else if ($compareSelectValue == 'impression_number'){
			$amountFormatted = $currency->toCurrency(doubleval($amount), array('display' => Zend_Currency::NO_SYMBOL,'precision' => 0))." ".Core::getRegistry()->get('translate')->_("common.impressions");	
		}else if ($compareSelectValue == 'EPC' ||
				  $compareSelectValue == 'CPA' ||
				  $compareSelectValue == 'ECPM'){

			$amountFormatted = $currency->toCurrency(doubleval($amount), array('precision' => 6));
				
		} else if ($compareSelectValue == 'CR' ||
				   $compareSelectValue == 'CTR' ||
				   $compareSelectValue == 'COR'){
				
			$amountFormatted = $currency->toCurrency(doubleval($amount), array('display' => Zend_Currency::NO_SYMBOL,'precision' => 2))." %";
				
		}
		return $amountFormatted;
	}
	/**
	 * Choose the measure for the charts tooltip
	 * @param $compareSelectValue
	 * @return string
	 */
	public static function chooseMeasure($compareSelectValue, $currency){
	
		$measure = '';
		if($compareSelectValue == 'click_number'){
			$measure = Core::getRegistry()->get('translate')->_("common.clicks");
		} else if ($compareSelectValue == 'transaction_number'){
			$measure = Core::getRegistry()->get('translate')->_("common.transaction");
		} else if ($compareSelectValue == 'impression_number'){
			$measure = Core::getRegistry()->get('translate')->_("common.impressions");
		} else if ($compareSelectValue == 'CR' ||
				   $compareSelectValue == 'CTR' ||
				   $compareSelectValue == 'COR'){
			$measure = Core::getRegistry()->get('translate')->_("common.percent");
		}
		return $measure;
	}

	/**
	 * Set the pie chart
	 * @param string $tableName
	 * @param unknown_type $form
	 * @param unknown_type $pieChartList
	 * @param unknown_type $formData
	 * @param unknown_type $attrLabel
	 * @param unknown_type $attrAmount
	 */
	public static function setPieChart($table, $tableName, $criteriaList, $formData, $attrLabel, $attrAmount, $compareSelectName = 'compareSelect'){
		$pieChartObject = new stdClass();
		$pieChartObject->charOkLimit  = Oara_Utilities::checkCharLimit($formData, $criteriaList, $table, $attrAmount);
		$pieArray = array();
		if ($pieChartObject->charOkLimit){
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Having($attrAmount,'> 0');
			$dao = Dao_Factory_Doctrine::createDoctrineDaoInstance($table, false);
			
			$pieChartList = $dao->findBy($criteriaList, false, DOCTRINE::HYDRATE_SCALAR);
			
			
			$i = 1;
			$currencyNamespace = new Zend_Session_Namespace('currency');
			foreach ($pieChartList as $data){
				$label = $data[$tableName.$attrLabel];
				if ($attrLabel == '_status'){
					if ($label == Oara_Utilities::STATUS_CONFIRMED){
						$label = Core::getRegistry()->get('translate')->_("common.confirmed");
					} else if ($label == Oara_Utilities::STATUS_PENDING){
						$label = Core::getRegistry()->get('translate')->_("common.pending");
					} else if ($label == Oara_Utilities::STATUS_DECLINED){
						$label = Core::getRegistry()->get('translate')->_("common.declined");
					}
				}
				if ($label != null){
					$amount = $data[$table.'_'.$attrAmount];
					
					if ($amount != null && $amount != 0){
						$attributes = array('y'=>round((double)$amount, 3),
		                          			'text'=>$label,
		                          			'id'=>$i,
		                          			'stroke'=>'black',
		                          			'tooltip'=>$label.', '.Oara_Utilities::chooseAmountFormat($amount, $formData['compareSelect'], $currencyNamespace->currency),
											);
						$pieArray[] = $attributes;
						$i++;
					}
				}
			}
		}
		$pieChartObject->pieChart = $pieArray;
		return $pieChartObject;
	}

	/**
	 * Calculate all the data needed for the bar Chart
	 * @param $table
	 * @param $form
	 * @param $criteriaList
	 * @param $formdata
	 * @param $attrLabel
	 * @param $attrAmount
	 */
	public static function setBarChart($table, $tableName, $criteriaList, $formData, $attrLabel, $attrAmount, $compareSelectName = 'compareSelect'){
		//Calculating the max and min date for this chart
		

		$criteriaAux = Oara_Utilities::cloneArray($criteriaList);
		$criteriaAux = Oara_Utilities::deleteFromCriteriaList($criteriaAux, new Dao_Doctrine_Criteria_Restriction_Groupby());
		$criteriaAux = Oara_Utilities::deleteFromCriteriaList($criteriaAux, new Dao_Doctrine_Criteria_Restriction_Select('','_'));

		$barChartObject = new stdClass();
		$lineArray = array();
		$xArray = array();
		$frequency = $formData["frequencySelect"];
		$criteriaLimit = Oara_Utilities::cloneArray($criteriaList);
		$barChartObject->chartOkFrequency = 1;
		$barChartObject->chartOkLimit = Oara_Utilities::checkCharLimit($formData, $criteriaLimit, $table, $attrAmount);
		if ($barChartObject->chartOkLimit){
			$startDate = Oara_Utilities::getMinDate($criteriaAux, $formData, $table);
			//setting up the start date for the array of dates
			$dateArrayStartDate = clone $startDate;
			$dateArrayStartDate->setHour(0);
			$dateArrayStartDate->setMinute(0);
			$dateArrayStartDate->setSecond(0);
				
			$endDate = Oara_Utilities::getMaxDate($criteriaAux, $formData, $table);
			
			//setting up the end date for the array of dates
			$dateArrayEndDate = clone $endDate;
			$dateArrayEndDate->setHour(0);
			$dateArrayEndDate->setMinute(0);
			$dateArrayEndDate->setSecond(0);
			//deleting old criteria for the date property
			$criteriaList = Oara_Utilities::deleteFromCriteriaList($criteriaList, new Dao_Doctrine_Criteria_Restriction_Ge('',''), 'date');
			$criteriaList = Oara_Utilities::deleteFromCriteriaList($criteriaList, new Dao_Doctrine_Criteria_Restriction_Le('',''), 'date');
				
				
			//Choose the better gap between dates
			$mode = null;
			$dateArray = array();
			
			
			$numberOfDays = Oara_Utilities::numberOfDaysBetweenTwoDates($startDate, $endDate);
			$frequencyOptions = array();
			if ($numberOfDays < 32){
				$frequencyOptions[] = 'daily';
				$frequencyOptions[] = 'weekly';
				$frequencyOptions[] = 'monthly';
				$frequencyOptions[] = 'yearly';
				if ($frequency == 'none'){
					$frequency = 'daily';
				}
			} else if ($numberOfDays < 96){
				$frequencyOptions[] = 'weekly';
				$frequencyOptions[] = 'monthly';
				$frequencyOptions[] = 'yearly';
				if ($frequency == 'none'){
					$frequency = 'weekly';
				}
			} else if($numberOfDays < 540){
				$frequencyOptions[] = 'monthly';
				$frequencyOptions[] = 'yearly';
				if ($frequency == 'none'){
					$frequency = 'monthly';
				}
			} else {
				$frequencyOptions[] = 'yearly';
				if ($frequency == 'none'){
					$frequency = 'yearly';
				}
			}
			
			if (in_array($frequency, $frequencyOptions)){
				if ($frequency=="daily"){
	
					$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Groupby('date','DAY');
					$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Groupby('date','MONTH');
					$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Groupby('date','YEAR');
					$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Select('date', '_startdate');
					$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Select('date', '_enddate');
	
					$dateArray = Oara_Utilities::daysOfDifference($dateArrayStartDate, $dateArrayEndDate);
					
					$frequency = 'daily';
	
				} else if ($frequency=="weekly"){
					$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Groupby('date','YEARWEEK','1');
					$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Select('date_sub(date, interval WEEKDAY(date)-0 day)', '_startdate', true);
					$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Select('date_add(date, interval 6-WEEKDAY(date) day)', '_enddate', true);
	
					$dateArray = Oara_Utilities::weeksOfDifference($dateArrayStartDate, $dateArrayEndDate);
					
					$frequency = 'weekly';
	
				} else if($frequency=="monthly"){
					$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Groupby('date','MONTH');
					$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Groupby('date','YEAR');
					$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Select('date_sub(date, interval DAYOFMONTH(date)-1 day)', '_startdate', true);
					$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Select('LAST_DAY(date)', '_enddate', true);
	
					$dateArray = Oara_Utilities::monthsOfDifference($dateArrayStartDate, $dateArrayEndDate);
					
					$frequency = 'monthly';
				}  else if($frequency=="yearly"){
	
					$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Groupby('date','YEAR');
					$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Select('MAKEDATE( EXTRACT(YEAR FROM date),1)', '_startdate', true);
					$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Select('STR_TO_DATE(CONCAT(12,31,EXTRACT(YEAR FROM date)), \'%m%d%Y\')', '_enddate', true);
						
					$dateArray = Oara_Utilities::yearsOfDifference($dateArrayStartDate, $dateArrayEndDate);
					
					$frequency = 'yearly';
				}
			
				
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Having($attrAmount,'> 0');
					
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Ge('date', $startDate->toString("yyyy-MM-ddTHH:mm:ss"));
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Le('date', $endDate->toString("yyyy-MM-ddTHH:mm:ss"));
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Order('_startdate', 'ASC');
					
				//fetching the data
				$dao = Dao_Factory_Doctrine::createDoctrineDaoInstance($table);
				//$time_start = microtime(true);
				$dataList = $dao->findBy($criteriaList, false, Doctrine::HYDRATE_SCALAR);
				//$time_end = microtime(true);
				//$time = $time_end - $time_start;
				//echo count($dataList)."    ".$time;
				
				//setting up the x array for the chart
				$dateMap = array();
				$dateArrayLength = count($dateArray);
				for ($i = 0; $i < $dateArrayLength ; $i++){
					$dateStartString = $dateArray[$i]->toString("dd-MM-yyyy");
					$xArray[] = array(
		                              'value'=> $i+1,
		                              'text'=> $dateStartString,
					);
					$dateMap[$dateStartString] = $i+1;
				}
					
					
				$checkGaps = array();
				//Get the data from the db
					
				//check if the label is static
				$staticLabel = false;
				if (substr($attrLabel,0,1) !== '_'){
					$label = $attrLabel;
					$staticLabel = true;
				}
				foreach ($dataList as $data){
	
					//if it is not , calculate the label dynamically
					if ($staticLabel == false){
						$label = null;
						$label = $data[$tableName.$attrLabel];
						//if we are doing a status chart is a bit different
						if ($attrLabel == '_status'){
							if ($label == Oara_Utilities::STATUS_CONFIRMED){
								$label = Core::getRegistry()->get('translate')->_("common.confirmed");
							} else if ($label == Oara_Utilities::STATUS_PENDING){
								$label = Core::getRegistry()->get('translate')->_("common.pending");
							} else if ($label == Oara_Utilities::STATUS_DECLINED){
								$label = Core::getRegistry()->get('translate')->_("common.declined");
							}
						}
					}
	
					if ($label != null) {
	
						$amount = $data[$table.'_'.$attrAmount];
						if ($amount != 0){
							$dateStart = new Zend_Date($data[$table.'_'.'_startdate'], "yyyy-MM-dd");
							$dateEnd = new Zend_Date($data[$table.'_'.'_enddate'], "yyyy-MM-dd");
							//checking if the start date is correct
							if ($dateStart->compare($startDate) < 0){
								$dateStart = $startDate;
							}
	
							$dateStartString = $dateStart->toString("dd-MM-yyyy");
							$dateEndString = $dateEnd->toString("dd-MM-yyyy");
								
							if (!isset($lineArray[$label])){
								$lineArray[$label] = array_fill(0, $dateArrayLength, 0);
							}
							$currentXPos = $dateMap[$dateStartString];
							$lineArray[$label][$currentXPos-1] = (double)$amount;
						}
					}
				}
	
			} else {
				$barChartObject->chartOkFrequency = 0;
			}
		}
		//Set the lineChartY view variable with the lineArray
		$barChartObject->barChartY = $lineArray;
		//Set the lineChartX view variable with the labels of the dates
		$barChartObject->barChartX = Zend_Json::encode($xArray);
		$compare = Oara_Utilities::getCompareSelect(strtolower($table));
		$barChartObject->axisXTitle = Core::getRegistry()->get('translate')->_("common.date");
		$barChartObject->axisYTitle = $compare[$formData['compareSelect']];
		$currencyNamespace = new Zend_Session_Namespace('currency');
		$currency = $currencyNamespace->currency;
		$barChartObject->measure = Oara_Utilities::chooseMeasure($formData['compareSelect'], $currencyNamespace->currency);
		$barChartObject->frequency = $frequency;
		$barChartObject->locale = $currency->getLocale();
		$barChartObject->currency = $currency->getShortName();
		return $barChartObject;
	}
	/**
	 * Calculate all the data needed for the comparison Bar Chart
	 * @param $table
	 * @param $form
	 * @param $criteriaList
	 * @param $formdata
	 * @param $attrLabel
	 * @param $attrAmount
	 */
	public static function setComparisonBarChart($table, $tableName, $criteriaList, $formData, $attrLabel, $attrAmount, $compareSelectName = 'compareSelect'){
		//Calculating the max and min date for this chart
		
		$attrAmountArray = explode(',', $attrAmount);
		
		$barChartObject = new stdClass();
		$lineArray = array();
		$xArray = array();
		$measureArray = array();
		$amountArray = array();
		$barChartObject->charOkLimit  = 1;
		if ($barChartObject->charOkLimit){
			
			//get the statistcs for the whole month
			
			$firstStartDate = new Zend_Date();
			$firstStartDate->subDay(1);
			$firstStartDate->setDay(1);
			$firstStartDate->subMonth(1);
			$firstStartDate->setHour(0);
			$firstStartDate->setMinute(0);
			$firstStartDate->setSecond(0);
			
			$firstEndDate = new Zend_Date();
			$firstEndDate->subDay(1);
			$firstEndDate->setDay(1);
			$firstEndDate->subDay(1);
			$firstEndDate->setHour(23);
			$firstEndDate->setMinute(59);
			$firstEndDate->setSecond(59);
			
			$criteriaReferenceList = Oara_Utilities::cloneArray($criteriaList);
			$criteriaReferenceList[] = new Dao_Doctrine_Criteria_Restriction_Ge('date', $firstStartDate->toString("yyyy-MM-dd HH:mm:ss"));
			$criteriaReferenceList[] = new Dao_Doctrine_Criteria_Restriction_Le('date', $firstEndDate->toString("yyyy-MM-dd HH:mm:ss"));
			
			//Choose the better gap between dates
			$dateArray = array();

			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Groupby('date','MONTH');
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Groupby('date','YEAR');
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Select('date_sub(date, interval DAYOFMONTH(date)-1 day)', '_startdate', true);
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Select('LAST_DAY(date)', '_enddate', true);


			$firstStartDate = new Zend_Date();
			$firstStartDate->subDay(1);
			$firstStartDate->setDay(1);
			$firstStartDate->setHour(0);
			$firstStartDate->setMinute(0);
			$firstStartDate->setSecond(0);
			
			$firstEndDate = new Zend_Date();
			$firstEndDate->subDay(1);
			$firstEndDate->setHour(23);
			$firstEndDate->setMinute(59);
			$firstEndDate->setSecond(59);
			
			$secondStartDate = new Zend_Date();
			$secondStartDate->subDay(1);
			$secondStartDate->setDay(1);
			$secondStartDate->subMonth(1);
			$secondStartDate->setHour(0);
			$secondStartDate->setMinute(0);
			$secondStartDate->setSecond(0);
			
			$secondEndDate = clone $firstEndDate;
			$secondEndDate->subMonth(1);
			
			$secondEndDate = clone $firstEndDate;
			$secondEndDate->subMonth(1);
				
			$criteriaAndFirstDate = new Dao_Doctrine_Criteria_Restriction_And();
			$criteriaAndFirstDate->addRestriction(new Dao_Doctrine_Criteria_Restriction_Ge('date', $firstStartDate->toString("yyyy-MM-dd HH:mm:ss")));
			$criteriaAndFirstDate->addRestriction(new Dao_Doctrine_Criteria_Restriction_Le('date', $firstEndDate->toString("yyyy-MM-dd HH:mm:ss")));
			
			$criteriaAndSecondDate = new Dao_Doctrine_Criteria_Restriction_And();
			$criteriaAndSecondDate->addRestriction(new Dao_Doctrine_Criteria_Restriction_Ge('date', $secondStartDate->toString("yyyy-MM-dd HH:mm:ss")));
			$criteriaAndSecondDate->addRestriction(new Dao_Doctrine_Criteria_Restriction_Le('date', $secondEndDate->toString("yyyy-MM-dd HH:mm:ss")));
			
			$criteriaOr = new Dao_Doctrine_Criteria_Restriction_Or();
			$criteriaOr->addRestriction($criteriaAndFirstDate);
			$criteriaOr->addRestriction($criteriaAndSecondDate);
			
			$criteriaList[] = $criteriaOr;
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Order('_startdate', 'ASC');
				
			//fetching the data
			$dao = Dao_Factory_Doctrine::createDoctrineDaoInstance($table);
			//echo $dao->getSql($criteriaList, false);
			$dataList = $dao->findBy($criteriaList, false, Doctrine::HYDRATE_SCALAR);

			$dataReference = $dao->findBy($criteriaReferenceList, false, Doctrine::HYDRATE_SCALAR);
			
			
			//setting up the x array for the chart
			$dateMap = array();
			$dateArray = array($secondStartDate, $firstStartDate);
			$dateArrayEnd = array($secondEndDate, $firstEndDate);
			$dateArrayLength = count($dateArray);
			
			//get the actual currency
			$currencyNamespace = new Zend_Session_Namespace('currency');
			$currency = $currencyNamespace->currency;

			for ($i = 0; $i < count($attrAmountArray); $i++){
				for ($j = 0; $j < $dateArrayLength ; $j++){
					
					$dateString = $dateArray[$j]->toString("MMM")." (".$dateArray[$j]->toString("d").$dateArray[$j]->get(Zend_Date::DAY_SUFFIX).
								  "-".$dateArrayEnd[$j]->toString("d").$dateArrayEnd[$j]->get(Zend_Date::DAY_SUFFIX).")";
					
					$xArray[] = array(
		                              'value'=> count($xArray)+1,
		                              'text'=> $dateString,
									 );
									 
					$dateStartString = $dateArray[$j]->toString("dd-MM-yyyy");				 
					$dateMap[$i][$dateStartString] = count($xArray)-1;
				}
				
				$xArray[] = array('value'=> count($xArray)+1,
		                          'text'=> '',
								 );
			}
			
			
			//var_dump($xArray);
			for ($i = 0; $i < count($attrAmountArray); $i++){
				$label = substr($attrAmountArray[$i], 1);
				$newLabel = '';
				switch ($label){
					case 'transaction_number':
						$newLabel =  Core::getRegistry()->get('translate')->_("home.comparison.totalTransaction");
						break;
					case 'commission':
						$newLabel =  Core::getRegistry()->get('translate')->_("home.comparison.totalCommission");
						break;
				}
				
				$amountReference = $dataReference[0][$table.'_'.$attrAmountArray[$i]];
				
				foreach ($dataList as $data){
					$amount = $data[$table.'_'.$attrAmountArray[$i]];
					
					if ($amount != 0){
						$dateStart = new Zend_Date($data[$table.'_'.'_startdate'], "yyyy-MM-dd");
						$dateStart->setDay(1);
						
						$dateStartString = $dateStart->toString("dd-MM-yyyy");
							
						if (!isset($lineArray[$newLabel])){
							$lineArray[$newLabel] = array_fill(0, count($xArray)-1, 0);
						}
						$currentXPos = $dateMap[$i][$dateStartString];
						
						$lineArray[$newLabel][$currentXPos] = (int)(($amount/$amountReference)*100);
						$amountArray[$currentXPos] = (int)$amount;
						$measureArray[$currentXPos] = Oara_Utilities::chooseMeasure($label, $currency);
					}
				}
			}
		}

		//Set the lineChartY view variable with the lineArray
		$barChartObject->barChartY = $lineArray;
		//Set the lineChartX view variable with the labels of the dates
		$barChartObject->barChartX = Zend_Json::encode($xArray);
		$barChartObject->axisXTitle = Core::getRegistry()->get('translate')->_("common.date");
		$barChartObject->axisYTitle = Core::getRegistry()->get('translate')->_("home.comparison.completed");
		
		$barChartObject->measure = $measureArray;
		$barChartObject->amount = $amountArray;
		$barChartObject->locale = $currency->getLocale();
		$barChartObject->currency = $currency->getShortName();

		return $barChartObject;
	}
	/**
	 * Calculate the Min Date
	 * @param array $criteriaList
	 * @param array $formData
	 * @param string $table
	 */
	public static function getMinDate($criteriaList, $formData, $table){
		$startDate = null;
		//We are getting the start and End date for this query
		if (isset($formData['startDate']) && $formData['startDate'] != null){
			$startDate = new Zend_Date($formData['startDate'], 'dd/MM/yyyy');
		} else{
			$dao = Dao_Factory_Doctrine::createDoctrineDaoInstance($table);
			$startDate = new Zend_Date($dao->getMin($criteriaList,'date'), 'dd/MM/yyyy');
		}
		//Set the hour at the first hour
		$startDate->setHour(00);
		$startDate->setMinute(00);
		$startDate->setSecond(00);
		return $startDate;
	}
	/**
	 * Calculate the Min Date
	 * @param array $criteriaList
	 * @param array $formData
	 * @param string $table
	 */
	public static function getMaxDate($criteriaList, $formData, $table){
		$endDate = null;
		
		Zend_Date::setOptions(array('fix_dst' => false));
		
		//If isn't set the endDate , get the max
		if(isset($formData['endDate']) && $formData['endDate'] != null){
			$endDate = new Zend_Date($formData['endDate'], 'dd/MM/yyyy');
		} else{
			$dao = Dao_Factory_Doctrine::createDoctrineDaoInstance($table);
			$endDate = new Zend_Date($dao->getMax($criteriaList,'date'), 'dd/MM/yyyy');
			
		}
		//Set the hour at the last hour
		$endDate->setHour(23);
		$endDate->setMinute(59);
		$endDate->setSecond(59);
		return $endDate;
	}
	/**
	 * Bar Char check limit
	 * @param array $criteriaList
	 * @param array $table
	 */
	public static function checkCharLimit($formData, $criteriaList, $table, $attrAmount){
		$ok = 1;
		$config = Core::getRegistry()->get('applicationIni');
		$limit = $config->chart->limit;
		
		$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Having($attrAmount,'> 0');
		
		if ($formData['scopeSelect'] !== 'none'){
			$dao = Dao_Factory_Doctrine::createDoctrineDaoInstance($table);

			$registerNumber = $dao->getCount($criteriaList);
			if ($registerNumber > $limit){
				$ok = 0;
			}
		}
		return $ok;
	}

	/**
	 * Get the model and returns a map with the paths
	 */
	public static function getModelMap(){

		if (!defined("MODELMAP")){
			$modelMap = array();
			$modelMap['AffjetAffiliateNetwork']['AffjetAffiliateNetworkConfig'] = 'AffjetAffiliateNetworkConfig';
			$modelMap['AffjetAlert']['AffjetGroup'] = 'AffjetGroup';
			$modelMap['AffjetAlert']['AffjetRoles'] = 'AffjetGroup->AffjetGroupRAffjetRoles->AffjetRoles';
			$modelMap['AffjetAlert']['AffjetGroupRAffjetRoles'] = 'AffjetGroup->AffjetGroupRAffjetRoles';
			$modelMap['AffjetAlert']['AffjetCb'] = 'AffjetGroup->AffjetCb';
			$modelMap['AffjetAlert']['AffjetUser'] = 'AffjetGroup->AffjetGroupRAffjetUser->AffjetUser';
			$modelMap['AffjetAlert']['AffjetSubcription'] = 'AffjetGroup->AffjetGroupRAffjetUser->AffjetSubcription';
			$modelMap['AffjetAlert']['AffjetResources'] = 'AffjetGroup->AffjetGroupRAffjetRoles->AffjetRoles->AffjetRolesRAffjetResources->AffjetResources';
			$modelMap['AffjetAlert']['AffjetRolesRAffjetResources'] = 'AffjetGroup->AffjetGroupRAffjetRoles->AffjetRoles->AffjetRolesRAffjetResources';
			$modelMap['AffjetAlert']['AffjetAlertConfig'] = 'AffjetAlertConfig';
			$modelMap['AffjetAlert']['AffjetInvitation'] = 'AffjetGroup->AffjetGroupRAffjetUser->AffjetInvitation';
			$modelMap['AffjetAlert']['AffjetAlertRAffjetGroupRAffjetUser'] = 'AffjetAlertRAffjetGroupRAffjetUser';
			$modelMap['AffjetAlert']['AffjetGroupRAffjetUser'] = 'AffjetGroup->AffjetGroupRAffjetUser';
			$modelMap['AffjetGroup']['AffjetAlert'] = 'AffjetAlert';
			$modelMap['AffjetGroup']['AffjetRoles'] = 'AffjetGroupRAffjetRoles->AffjetRoles';
			$modelMap['AffjetGroup']['AffjetGroupRAffjetRoles'] = 'AffjetGroupRAffjetRoles';
			$modelMap['AffjetGroup']['AffjetCb'] = 'AffjetCb';
			$modelMap['AffjetGroup']['AffjetUser'] = 'AffjetGroupRAffjetUser->AffjetUser';
			$modelMap['AffjetGroup']['AffjetSubcription'] = 'AffjetGroupRAffjetUser->AffjetSubcription';
			$modelMap['AffjetGroup']['AffjetResources'] = 'AffjetGroupRAffjetRoles->AffjetRoles->AffjetRolesRAffjetResources->AffjetResources';
			$modelMap['AffjetGroup']['AffjetRolesRAffjetResources'] = 'AffjetGroupRAffjetRoles->AffjetRoles->AffjetRolesRAffjetResources';
			$modelMap['AffjetGroup']['AffjetAlertConfig'] = 'AffjetAlert->AffjetAlertConfig';
			$modelMap['AffjetGroup']['AffjetInvitation'] = 'AffjetGroupRAffjetUser->AffjetInvitation';
			$modelMap['AffjetGroup']['AffjetAlertRAffjetGroupRAffjetUser'] = 'AffjetAlert->AffjetAlertRAffjetGroupRAffjetUser';
			$modelMap['AffjetGroup']['AffjetGroupRAffjetUser'] = 'AffjetGroupRAffjetUser';
			$modelMap['AffjetRoles']['AffjetAlert'] = 'AffjetGroupRAffjetRoles->AffjetGroup->AffjetAlert';
			$modelMap['AffjetRoles']['AffjetGroup'] = 'AffjetGroupRAffjetRoles->AffjetGroup';
			$modelMap['AffjetRoles']['AffjetGroupRAffjetRoles'] = 'AffjetGroupRAffjetRoles';
			$modelMap['AffjetRoles']['AffjetCb'] = 'AffjetGroupRAffjetRoles->AffjetGroup->AffjetCb';
			$modelMap['AffjetRoles']['AffjetUser'] = 'AffjetGroupRAffjetUser->AffjetUser';
			$modelMap['AffjetRoles']['AffjetSubcription'] = 'AffjetGroupRAffjetUser->AffjetSubcription';
			$modelMap['AffjetRoles']['AffjetResources'] = 'AffjetRolesRAffjetResources->AffjetResources';
			$modelMap['AffjetRoles']['AffjetRolesRAffjetResources'] = 'AffjetRolesRAffjetResources';
			$modelMap['AffjetRoles']['AffjetAlertConfig'] = 'AffjetGroupRAffjetRoles->AffjetGroup->AffjetAlert->AffjetAlertConfig';
			$modelMap['AffjetRoles']['AffjetInvitation'] = 'AffjetInvitation';
			$modelMap['AffjetRoles']['AffjetAlertRAffjetGroupRAffjetUser'] = 'AffjetGroupRAffjetUser->AffjetAlertRAffjetGroupRAffjetUser';
			$modelMap['AffjetRoles']['AffjetGroupRAffjetUser'] = 'AffjetGroupRAffjetUser';
			$modelMap['AffjetGroupRAffjetRoles']['AffjetAlert'] = 'AffjetGroup->AffjetAlert';
			$modelMap['AffjetGroupRAffjetRoles']['AffjetGroup'] = 'AffjetGroup';
			$modelMap['AffjetGroupRAffjetRoles']['AffjetRoles'] = 'AffjetRoles';
			$modelMap['AffjetGroupRAffjetRoles']['AffjetCb'] = 'AffjetGroup->AffjetCb';
			$modelMap['AffjetGroupRAffjetRoles']['AffjetUser'] = 'AffjetGroup->AffjetGroupRAffjetUser->AffjetUser';
			$modelMap['AffjetGroupRAffjetRoles']['AffjetSubcription'] = 'AffjetGroup->AffjetGroupRAffjetUser->AffjetSubcription';
			$modelMap['AffjetGroupRAffjetRoles']['AffjetResources'] = 'AffjetRoles->AffjetRolesRAffjetResources->AffjetResources';
			$modelMap['AffjetGroupRAffjetRoles']['AffjetRolesRAffjetResources'] = 'AffjetRoles->AffjetRolesRAffjetResources';
			$modelMap['AffjetGroupRAffjetRoles']['AffjetAlertConfig'] = 'AffjetGroup->AffjetAlert->AffjetAlertConfig';
			$modelMap['AffjetGroupRAffjetRoles']['AffjetInvitation'] = 'AffjetRoles->AffjetInvitation';
			$modelMap['AffjetGroupRAffjetRoles']['AffjetAlertRAffjetGroupRAffjetUser'] = 'AffjetGroup->AffjetAlert->AffjetAlertRAffjetGroupRAffjetUser';
			$modelMap['AffjetGroupRAffjetRoles']['AffjetGroupRAffjetUser'] = 'AffjetGroup->AffjetGroupRAffjetUser';
			$modelMap['AffjetCb']['AffjetAlert'] = 'AffjetGroup->AffjetAlert';
			$modelMap['AffjetCb']['AffjetGroup'] = 'AffjetGroup';
			$modelMap['AffjetCb']['AffjetRoles'] = 'AffjetGroup->AffjetGroupRAffjetRoles->AffjetRoles';
			$modelMap['AffjetCb']['AffjetGroupRAffjetRoles'] = 'AffjetGroup->AffjetGroupRAffjetRoles';
			$modelMap['AffjetCb']['AffjetUser'] = 'AffjetGroup->AffjetGroupRAffjetUser->AffjetUser';
			$modelMap['AffjetCb']['AffjetSubcription'] = 'AffjetGroup->AffjetGroupRAffjetUser->AffjetSubcription';
			$modelMap['AffjetCb']['AffjetResources'] = 'AffjetGroup->AffjetGroupRAffjetRoles->AffjetRoles->AffjetRolesRAffjetResources->AffjetResources';
			$modelMap['AffjetCb']['AffjetRolesRAffjetResources'] = 'AffjetGroup->AffjetGroupRAffjetRoles->AffjetRoles->AffjetRolesRAffjetResources';
			$modelMap['AffjetCb']['AffjetAlertConfig'] = 'AffjetGroup->AffjetAlert->AffjetAlertConfig';
			$modelMap['AffjetCb']['AffjetInvitation'] = 'AffjetGroup->AffjetGroupRAffjetUser->AffjetInvitation';
			$modelMap['AffjetCb']['AffjetAlertRAffjetGroupRAffjetUser'] = 'AffjetGroup->AffjetAlert->AffjetAlertRAffjetGroupRAffjetUser';
			$modelMap['AffjetCb']['AffjetGroupRAffjetUser'] = 'AffjetGroup->AffjetGroupRAffjetUser';
			$modelMap['AffjetUser']['AffjetAlert'] = 'AffjetGroupRAffjetUser->AffjetGroup->AffjetAlert';
			$modelMap['AffjetUser']['AffjetGroup'] = 'AffjetGroupRAffjetUser->AffjetGroup';
			$modelMap['AffjetUser']['AffjetRoles'] = 'AffjetGroupRAffjetUser->AffjetRoles';
			$modelMap['AffjetUser']['AffjetGroupRAffjetRoles'] = 'AffjetGroupRAffjetUser->AffjetGroup->AffjetGroupRAffjetRoles';
			$modelMap['AffjetUser']['AffjetCb'] = 'AffjetGroupRAffjetUser->AffjetGroup->AffjetCb';
			$modelMap['AffjetUser']['AffjetSubcription'] = 'AffjetGroupRAffjetUser->AffjetSubcription';
			$modelMap['AffjetUser']['AffjetResources'] = 'AffjetGroupRAffjetUser->AffjetRoles->AffjetRolesRAffjetResources->AffjetResources';
			$modelMap['AffjetUser']['AffjetRolesRAffjetResources'] = 'AffjetGroupRAffjetUser->AffjetRoles->AffjetRolesRAffjetResources';
			$modelMap['AffjetUser']['AffjetAlertConfig'] = 'AffjetGroupRAffjetUser->AffjetGroup->AffjetAlert->AffjetAlertConfig';
			$modelMap['AffjetUser']['AffjetInvitation'] = 'AffjetGroupRAffjetUser->AffjetInvitation';
			$modelMap['AffjetUser']['AffjetAlertRAffjetGroupRAffjetUser'] = 'AffjetGroupRAffjetUser->AffjetAlertRAffjetGroupRAffjetUser';
			$modelMap['AffjetUser']['AffjetGroupRAffjetUser'] = 'AffjetGroupRAffjetUser';
			$modelMap['AffjetSubcription']['AffjetAlert'] = 'AffjetGroupRAffjetUser->AffjetGroup->AffjetAlert';
			$modelMap['AffjetSubcription']['AffjetGroup'] = 'AffjetGroupRAffjetUser->AffjetGroup';
			$modelMap['AffjetSubcription']['AffjetRoles'] = 'AffjetGroupRAffjetUser->AffjetRoles';
			$modelMap['AffjetSubcription']['AffjetGroupRAffjetRoles'] = 'AffjetGroupRAffjetUser->AffjetGroup->AffjetGroupRAffjetRoles';
			$modelMap['AffjetSubcription']['AffjetCb'] = 'AffjetGroupRAffjetUser->AffjetGroup->AffjetCb';
			$modelMap['AffjetSubcription']['AffjetUser'] = 'AffjetGroupRAffjetUser->AffjetUser';
			$modelMap['AffjetSubcription']['AffjetResources'] = 'AffjetGroupRAffjetUser->AffjetRoles->AffjetRolesRAffjetResources->AffjetResources';
			$modelMap['AffjetSubcription']['AffjetRolesRAffjetResources'] = 'AffjetGroupRAffjetUser->AffjetRoles->AffjetRolesRAffjetResources';
			$modelMap['AffjetSubcription']['AffjetAlertConfig'] = 'AffjetGroupRAffjetUser->AffjetGroup->AffjetAlert->AffjetAlertConfig';
			$modelMap['AffjetSubcription']['AffjetInvitation'] = 'AffjetGroupRAffjetUser->AffjetInvitation';
			$modelMap['AffjetSubcription']['AffjetAlertRAffjetGroupRAffjetUser'] = 'AffjetGroupRAffjetUser->AffjetAlertRAffjetGroupRAffjetUser';
			$modelMap['AffjetSubcription']['AffjetGroupRAffjetUser'] = 'AffjetGroupRAffjetUser';
			$modelMap['AffjetResources']['AffjetAlert'] = 'AffjetRolesRAffjetResources->AffjetRoles->AffjetGroupRAffjetRoles->AffjetGroup->AffjetAlert';
			$modelMap['AffjetResources']['AffjetGroup'] = 'AffjetRolesRAffjetResources->AffjetRoles->AffjetGroupRAffjetRoles->AffjetGroup';
			$modelMap['AffjetResources']['AffjetRoles'] = 'AffjetRolesRAffjetResources->AffjetRoles';
			$modelMap['AffjetResources']['AffjetGroupRAffjetRoles'] = 'AffjetRolesRAffjetResources->AffjetRoles->AffjetGroupRAffjetRoles';
			$modelMap['AffjetResources']['AffjetCb'] = 'AffjetRolesRAffjetResources->AffjetRoles->AffjetGroupRAffjetRoles->AffjetGroup->AffjetCb';
			$modelMap['AffjetResources']['AffjetUser'] = 'AffjetRolesRAffjetResources->AffjetRoles->AffjetGroupRAffjetUser->AffjetUser';
			$modelMap['AffjetResources']['AffjetSubcription'] = 'AffjetRolesRAffjetResources->AffjetRoles->AffjetGroupRAffjetUser->AffjetSubcription';
			$modelMap['AffjetResources']['AffjetRolesRAffjetResources'] = 'AffjetRolesRAffjetResources';
			$modelMap['AffjetResources']['AffjetAlertConfig'] = 'AffjetRolesRAffjetResources->AffjetRoles->AffjetGroupRAffjetRoles->AffjetGroup->AffjetAlert->AffjetAlertConfig';
			$modelMap['AffjetResources']['AffjetInvitation'] = 'AffjetRolesRAffjetResources->AffjetRoles->AffjetInvitation';
			$modelMap['AffjetResources']['AffjetAlertRAffjetGroupRAffjetUser'] = 'AffjetRolesRAffjetResources->AffjetRoles->AffjetGroupRAffjetUser->AffjetAlertRAffjetGroupRAffjetUser';
			$modelMap['AffjetResources']['AffjetGroupRAffjetUser'] = 'AffjetRolesRAffjetResources->AffjetRoles->AffjetGroupRAffjetUser';
			$modelMap['AffjetRolesRAffjetResources']['AffjetAlert'] = 'AffjetRoles->AffjetGroupRAffjetRoles->AffjetGroup->AffjetAlert';
			$modelMap['AffjetRolesRAffjetResources']['AffjetGroup'] = 'AffjetRoles->AffjetGroupRAffjetRoles->AffjetGroup';
			$modelMap['AffjetRolesRAffjetResources']['AffjetRoles'] = 'AffjetRoles';
			$modelMap['AffjetRolesRAffjetResources']['AffjetGroupRAffjetRoles'] = 'AffjetRoles->AffjetGroupRAffjetRoles';
			$modelMap['AffjetRolesRAffjetResources']['AffjetCb'] = 'AffjetRoles->AffjetGroupRAffjetRoles->AffjetGroup->AffjetCb';
			$modelMap['AffjetRolesRAffjetResources']['AffjetUser'] = 'AffjetRoles->AffjetGroupRAffjetUser->AffjetUser';
			$modelMap['AffjetRolesRAffjetResources']['AffjetSubcription'] = 'AffjetRoles->AffjetGroupRAffjetUser->AffjetSubcription';
			$modelMap['AffjetRolesRAffjetResources']['AffjetResources'] = 'AffjetResources';
			$modelMap['AffjetRolesRAffjetResources']['AffjetAlertConfig'] = 'AffjetRoles->AffjetGroupRAffjetRoles->AffjetGroup->AffjetAlert->AffjetAlertConfig';
			$modelMap['AffjetRolesRAffjetResources']['AffjetInvitation'] = 'AffjetRoles->AffjetInvitation';
			$modelMap['AffjetRolesRAffjetResources']['AffjetAlertRAffjetGroupRAffjetUser'] = 'AffjetRoles->AffjetGroupRAffjetUser->AffjetAlertRAffjetGroupRAffjetUser';
			$modelMap['AffjetRolesRAffjetResources']['AffjetGroupRAffjetUser'] = 'AffjetRoles->AffjetGroupRAffjetUser';
			$modelMap['AffjetAlertConfig']['AffjetAlert'] = 'AffjetAlert';
			$modelMap['AffjetAlertConfig']['AffjetGroup'] = 'AffjetAlert->AffjetGroup';
			$modelMap['AffjetAlertConfig']['AffjetRoles'] = 'AffjetAlert->AffjetGroup->AffjetGroupRAffjetRoles->AffjetRoles';
			$modelMap['AffjetAlertConfig']['AffjetGroupRAffjetRoles'] = 'AffjetAlert->AffjetGroup->AffjetGroupRAffjetRoles';
			$modelMap['AffjetAlertConfig']['AffjetCb'] = 'AffjetAlert->AffjetGroup->AffjetCb';
			$modelMap['AffjetAlertConfig']['AffjetUser'] = 'AffjetAlert->AffjetGroup->AffjetGroupRAffjetUser->AffjetUser';
			$modelMap['AffjetAlertConfig']['AffjetSubcription'] = 'AffjetAlert->AffjetGroup->AffjetGroupRAffjetUser->AffjetSubcription';
			$modelMap['AffjetAlertConfig']['AffjetResources'] = 'AffjetAlert->AffjetGroup->AffjetGroupRAffjetRoles->AffjetRoles->AffjetRolesRAffjetResources->AffjetResources';
			$modelMap['AffjetAlertConfig']['AffjetRolesRAffjetResources'] = 'AffjetAlert->AffjetGroup->AffjetGroupRAffjetRoles->AffjetRoles->AffjetRolesRAffjetResources';
			$modelMap['AffjetAlertConfig']['AffjetInvitation'] = 'AffjetAlert->AffjetGroup->AffjetGroupRAffjetUser->AffjetInvitation';
			$modelMap['AffjetAlertConfig']['AffjetAlertRAffjetGroupRAffjetUser'] = 'AffjetAlert->AffjetAlertRAffjetGroupRAffjetUser';
			$modelMap['AffjetAlertConfig']['AffjetGroupRAffjetUser'] = 'AffjetAlert->AffjetGroup->AffjetGroupRAffjetUser';
			$modelMap['AffjetAffiliateNetworkConfig']['AffjetAffiliateNetwork'] = 'AffjetAffiliateNetwork';
			$modelMap['AffjetInvitation']['AffjetAlert'] = 'AffjetGroupRAffjetUser->AffjetGroup->AffjetAlert';
			$modelMap['AffjetInvitation']['AffjetGroup'] = 'AffjetGroupRAffjetUser->AffjetGroup';
			$modelMap['AffjetInvitation']['AffjetRoles'] = 'AffjetRoles';
			$modelMap['AffjetInvitation']['AffjetGroupRAffjetRoles'] = 'AffjetRoles->AffjetGroupRAffjetRoles';
			$modelMap['AffjetInvitation']['AffjetCb'] = 'AffjetGroupRAffjetUser->AffjetGroup->AffjetCb';
			$modelMap['AffjetInvitation']['AffjetUser'] = 'AffjetGroupRAffjetUser->AffjetUser';
			$modelMap['AffjetInvitation']['AffjetSubcription'] = 'AffjetGroupRAffjetUser->AffjetSubcription';
			$modelMap['AffjetInvitation']['AffjetResources'] = 'AffjetRoles->AffjetRolesRAffjetResources->AffjetResources';
			$modelMap['AffjetInvitation']['AffjetRolesRAffjetResources'] = 'AffjetRoles->AffjetRolesRAffjetResources';
			$modelMap['AffjetInvitation']['AffjetAlertConfig'] = 'AffjetGroupRAffjetUser->AffjetGroup->AffjetAlert->AffjetAlertConfig';
			$modelMap['AffjetInvitation']['AffjetAlertRAffjetGroupRAffjetUser'] = 'AffjetGroupRAffjetUser->AffjetAlertRAffjetGroupRAffjetUser';
			$modelMap['AffjetInvitation']['AffjetGroupRAffjetUser'] = 'AffjetGroupRAffjetUser';
			$modelMap['AffjetAlertRAffjetGroupRAffjetUser']['AffjetAlert'] = 'AffjetAlert';
			$modelMap['AffjetAlertRAffjetGroupRAffjetUser']['AffjetGroup'] = 'AffjetGroupRAffjetUser->AffjetGroup';
			$modelMap['AffjetAlertRAffjetGroupRAffjetUser']['AffjetRoles'] = 'AffjetGroupRAffjetUser->AffjetRoles';
			$modelMap['AffjetAlertRAffjetGroupRAffjetUser']['AffjetGroupRAffjetRoles'] = 'AffjetGroupRAffjetUser->AffjetGroup->AffjetGroupRAffjetRoles';
			$modelMap['AffjetAlertRAffjetGroupRAffjetUser']['AffjetCb'] = 'AffjetGroupRAffjetUser->AffjetGroup->AffjetCb';
			$modelMap['AffjetAlertRAffjetGroupRAffjetUser']['AffjetUser'] = 'AffjetGroupRAffjetUser->AffjetUser';
			$modelMap['AffjetAlertRAffjetGroupRAffjetUser']['AffjetSubcription'] = 'AffjetGroupRAffjetUser->AffjetSubcription';
			$modelMap['AffjetAlertRAffjetGroupRAffjetUser']['AffjetResources'] = 'AffjetGroupRAffjetUser->AffjetRoles->AffjetRolesRAffjetResources->AffjetResources';
			$modelMap['AffjetAlertRAffjetGroupRAffjetUser']['AffjetRolesRAffjetResources'] = 'AffjetGroupRAffjetUser->AffjetRoles->AffjetRolesRAffjetResources';
			$modelMap['AffjetAlertRAffjetGroupRAffjetUser']['AffjetAlertConfig'] = 'AffjetAlert->AffjetAlertConfig';
			$modelMap['AffjetAlertRAffjetGroupRAffjetUser']['AffjetInvitation'] = 'AffjetGroupRAffjetUser->AffjetInvitation';
			$modelMap['AffjetAlertRAffjetGroupRAffjetUser']['AffjetGroupRAffjetUser'] = 'AffjetGroupRAffjetUser';
			$modelMap['AffjetGroupRAffjetUser']['AffjetAlert'] = 'AffjetGroup->AffjetAlert';
			$modelMap['AffjetGroupRAffjetUser']['AffjetGroup'] = 'AffjetGroup';
			$modelMap['AffjetGroupRAffjetUser']['AffjetRoles'] = 'AffjetRoles';
			$modelMap['AffjetGroupRAffjetUser']['AffjetGroupRAffjetRoles'] = 'AffjetGroup->AffjetGroupRAffjetRoles';
			$modelMap['AffjetGroupRAffjetUser']['AffjetCb'] = 'AffjetGroup->AffjetCb';
			$modelMap['AffjetGroupRAffjetUser']['AffjetUser'] = 'AffjetUser';
			$modelMap['AffjetGroupRAffjetUser']['AffjetSubcription'] = 'AffjetSubcription';
			$modelMap['AffjetGroupRAffjetUser']['AffjetResources'] = 'AffjetRoles->AffjetRolesRAffjetResources->AffjetResources';
			$modelMap['AffjetGroupRAffjetUser']['AffjetRolesRAffjetResources'] = 'AffjetRoles->AffjetRolesRAffjetResources';
			$modelMap['AffjetGroupRAffjetUser']['AffjetAlertConfig'] = 'AffjetGroup->AffjetAlert->AffjetAlertConfig';
			$modelMap['AffjetGroupRAffjetUser']['AffjetInvitation'] = 'AffjetInvitation';
			$modelMap['AffjetGroupRAffjetUser']['AffjetAlertRAffjetGroupRAffjetUser'] = 'AffjetAlertRAffjetGroupRAffjetUser';
			$modelMap['Merchant']['AffiliateNetworkConfig'] = 'AffiliateNetwork->AffiliateNetworkConfig';
			$modelMap['Merchant']['CbInvoices'] = 'AffiliateNetwork->AffiliateNetworkRCbEntity->CbInvoices';
			$modelMap['Merchant']['Transaction'] = 'Link->Website->Transaction';
			$modelMap['Merchant']['AffiliateNetworkRCbEntity'] = 'AffiliateNetwork->AffiliateNetworkRCbEntity';
			$modelMap['Merchant']['Project'] = 'Link->Website->ProjectRWebsite->Project';
			$modelMap['Merchant']['ProjectRAffjetUser'] = 'Link->Website->ProjectRWebsite->Project->ProjectRAffjetUser';
			$modelMap['Merchant']['Payment'] = 'AffiliateNetwork->Payment';
			$modelMap['Merchant']['ProjectRWebsite'] = 'Link->Website->ProjectRWebsite';
			$modelMap['Merchant']['Overview'] = 'Link->Website->Overview';
			$modelMap['Merchant']['Website'] = 'Link->Website';
			$modelMap['Merchant']['Link'] = 'Link';
			$modelMap['Merchant']['AffiliateNetwork'] = 'AffiliateNetwork';
			$modelMap['AffiliateNetworkConfig']['Merchant'] = 'AffiliateNetwork->Merchant';
			$modelMap['AffiliateNetworkConfig']['CbInvoices'] = 'AffiliateNetwork->AffiliateNetworkRCbEntity->CbInvoices';
			$modelMap['AffiliateNetworkConfig']['Transaction'] = 'AffiliateNetwork->Merchant->Link->Website->Transaction';
			$modelMap['AffiliateNetworkConfig']['AffiliateNetworkRCbEntity'] = 'AffiliateNetwork->AffiliateNetworkRCbEntity';
			$modelMap['AffiliateNetworkConfig']['Project'] = 'AffiliateNetwork->Merchant->Link->Website->ProjectRWebsite->Project';
			$modelMap['AffiliateNetworkConfig']['ProjectRAffjetUser'] = 'AffiliateNetwork->Merchant->Link->Website->ProjectRWebsite->Project->ProjectRAffjetUser';
			$modelMap['AffiliateNetworkConfig']['Payment'] = 'AffiliateNetwork->Payment';
			$modelMap['AffiliateNetworkConfig']['ProjectRWebsite'] = 'AffiliateNetwork->Merchant->Link->Website->ProjectRWebsite';
			$modelMap['AffiliateNetworkConfig']['Overview'] = 'AffiliateNetwork->Merchant->Link->Website->Overview';
			$modelMap['AffiliateNetworkConfig']['Website'] = 'AffiliateNetwork->Merchant->Link->Website';
			$modelMap['AffiliateNetworkConfig']['Link'] = 'AffiliateNetwork->Merchant->Link';
			$modelMap['AffiliateNetworkConfig']['AffiliateNetwork'] = 'AffiliateNetwork';
			$modelMap['CbInvoices']['Merchant'] = 'AffiliateNetworkRCbEntity->AffiliateNetwork->Merchant';
			$modelMap['CbInvoices']['AffiliateNetworkConfig'] = 'AffiliateNetworkRCbEntity->AffiliateNetwork->AffiliateNetworkConfig';
			$modelMap['CbInvoices']['Transaction'] = 'AffiliateNetworkRCbEntity->AffiliateNetwork->Merchant->Link->Website->Transaction';
			$modelMap['CbInvoices']['AffiliateNetworkRCbEntity'] = 'AffiliateNetworkRCbEntity';
			$modelMap['CbInvoices']['Project'] = 'AffiliateNetworkRCbEntity->AffiliateNetwork->Merchant->Link->Website->ProjectRWebsite->Project';
			$modelMap['CbInvoices']['ProjectRAffjetUser'] = 'AffiliateNetworkRCbEntity->AffiliateNetwork->Merchant->Link->Website->ProjectRWebsite->Project->ProjectRAffjetUser';
			$modelMap['CbInvoices']['Payment'] = 'AffiliateNetworkRCbEntity->AffiliateNetwork->Payment';
			$modelMap['CbInvoices']['ProjectRWebsite'] = 'AffiliateNetworkRCbEntity->AffiliateNetwork->Merchant->Link->Website->ProjectRWebsite';
			$modelMap['CbInvoices']['Overview'] = 'AffiliateNetworkRCbEntity->AffiliateNetwork->Merchant->Link->Website->Overview';
			$modelMap['CbInvoices']['Website'] = 'AffiliateNetworkRCbEntity->AffiliateNetwork->Merchant->Link->Website';
			$modelMap['CbInvoices']['Link'] = 'AffiliateNetworkRCbEntity->AffiliateNetwork->Merchant->Link';
			$modelMap['CbInvoices']['AffiliateNetwork'] = 'AffiliateNetworkRCbEntity->AffiliateNetwork';
			$modelMap['Transaction']['Merchant'] = 'Website->Link->Merchant';
			$modelMap['Transaction']['AffiliateNetworkConfig'] = 'Website->Link->Merchant->AffiliateNetwork->AffiliateNetworkConfig';
			$modelMap['Transaction']['CbInvoices'] = 'Website->Link->Merchant->AffiliateNetwork->AffiliateNetworkRCbEntity->CbInvoices';
			$modelMap['Transaction']['AffiliateNetworkRCbEntity'] = 'Website->Link->Merchant->AffiliateNetwork->AffiliateNetworkRCbEntity';
			$modelMap['Transaction']['Project'] = 'Website->ProjectRWebsite->Project';
			$modelMap['Transaction']['ProjectRAffjetUser'] = 'Website->ProjectRWebsite->Project->ProjectRAffjetUser';
			$modelMap['Transaction']['Payment'] = 'Website->Link->Merchant->AffiliateNetwork->Payment';
			$modelMap['Transaction']['ProjectRWebsite'] = 'Website->ProjectRWebsite';
			$modelMap['Transaction']['Overview'] = 'Website->Overview';
			$modelMap['Transaction']['Website'] = 'Website';
			$modelMap['Transaction']['Link'] = 'Website->Link';
			$modelMap['Transaction']['AffiliateNetwork'] = 'Website->Link->Merchant->AffiliateNetwork';
			$modelMap['AffiliateNetworkRCbEntity']['Merchant'] = 'AffiliateNetwork->Merchant';
			$modelMap['AffiliateNetworkRCbEntity']['AffiliateNetworkConfig'] = 'AffiliateNetwork->AffiliateNetworkConfig';
			$modelMap['AffiliateNetworkRCbEntity']['CbInvoices'] = 'CbInvoices';
			$modelMap['AffiliateNetworkRCbEntity']['Transaction'] = 'AffiliateNetwork->Merchant->Link->Website->Transaction';
			$modelMap['AffiliateNetworkRCbEntity']['Project'] = 'AffiliateNetwork->Merchant->Link->Website->ProjectRWebsite->Project';
			$modelMap['AffiliateNetworkRCbEntity']['ProjectRAffjetUser'] = 'AffiliateNetwork->Merchant->Link->Website->ProjectRWebsite->Project->ProjectRAffjetUser';
			$modelMap['AffiliateNetworkRCbEntity']['Payment'] = 'AffiliateNetwork->Payment';
			$modelMap['AffiliateNetworkRCbEntity']['ProjectRWebsite'] = 'AffiliateNetwork->Merchant->Link->Website->ProjectRWebsite';
			$modelMap['AffiliateNetworkRCbEntity']['Overview'] = 'AffiliateNetwork->Merchant->Link->Website->Overview';
			$modelMap['AffiliateNetworkRCbEntity']['Website'] = 'AffiliateNetwork->Merchant->Link->Website';
			$modelMap['AffiliateNetworkRCbEntity']['Link'] = 'AffiliateNetwork->Merchant->Link';
			$modelMap['AffiliateNetworkRCbEntity']['AffiliateNetwork'] = 'AffiliateNetwork';
			$modelMap['Project']['Merchant'] = 'ProjectRWebsite->Website->Link->Merchant';
			$modelMap['Project']['AffiliateNetworkConfig'] = 'ProjectRWebsite->Website->Link->Merchant->AffiliateNetwork->AffiliateNetworkConfig';
			$modelMap['Project']['CbInvoices'] = 'ProjectRWebsite->Website->Link->Merchant->AffiliateNetwork->AffiliateNetworkRCbEntity->CbInvoices';
			$modelMap['Project']['Transaction'] = 'ProjectRWebsite->Website->Transaction';
			$modelMap['Project']['AffiliateNetworkRCbEntity'] = 'ProjectRWebsite->Website->Link->Merchant->AffiliateNetwork->AffiliateNetworkRCbEntity';
			$modelMap['Project']['ProjectRAffjetUser'] = 'ProjectRAffjetUser';
			$modelMap['Project']['Payment'] = 'ProjectRWebsite->Website->Link->Merchant->AffiliateNetwork->Payment';
			$modelMap['Project']['ProjectRWebsite'] = 'ProjectRWebsite';
			$modelMap['Project']['Overview'] = 'ProjectRWebsite->Website->Overview';
			$modelMap['Project']['Website'] = 'ProjectRWebsite->Website';
			$modelMap['Project']['Link'] = 'ProjectRWebsite->Website->Link';
			$modelMap['Project']['AffiliateNetwork'] = 'ProjectRWebsite->Website->Link->Merchant->AffiliateNetwork';
			$modelMap['ProjectRAffjetUser']['Merchant'] = 'Project->ProjectRWebsite->Website->Link->Merchant';
			$modelMap['ProjectRAffjetUser']['AffiliateNetworkConfig'] = 'Project->ProjectRWebsite->Website->Link->Merchant->AffiliateNetwork->AffiliateNetworkConfig';
			$modelMap['ProjectRAffjetUser']['CbInvoices'] = 'Project->ProjectRWebsite->Website->Link->Merchant->AffiliateNetwork->AffiliateNetworkRCbEntity->CbInvoices';
			$modelMap['ProjectRAffjetUser']['Transaction'] = 'Project->ProjectRWebsite->Website->Transaction';
			$modelMap['ProjectRAffjetUser']['AffiliateNetworkRCbEntity'] = 'Project->ProjectRWebsite->Website->Link->Merchant->AffiliateNetwork->AffiliateNetworkRCbEntity';
			$modelMap['ProjectRAffjetUser']['Project'] = 'Project';
			$modelMap['ProjectRAffjetUser']['Payment'] = 'Project->ProjectRWebsite->Website->Link->Merchant->AffiliateNetwork->Payment';
			$modelMap['ProjectRAffjetUser']['ProjectRWebsite'] = 'Project->ProjectRWebsite';
			$modelMap['ProjectRAffjetUser']['Overview'] = 'Project->ProjectRWebsite->Website->Overview';
			$modelMap['ProjectRAffjetUser']['Website'] = 'Project->ProjectRWebsite->Website';
			$modelMap['ProjectRAffjetUser']['Link'] = 'Project->ProjectRWebsite->Website->Link';
			$modelMap['ProjectRAffjetUser']['AffiliateNetwork'] = 'Project->ProjectRWebsite->Website->Link->Merchant->AffiliateNetwork';
			$modelMap['Payment']['Merchant'] = 'AffiliateNetwork->Merchant';
			$modelMap['Payment']['AffiliateNetworkConfig'] = 'AffiliateNetwork->AffiliateNetworkConfig';
			$modelMap['Payment']['CbInvoices'] = 'AffiliateNetwork->AffiliateNetworkRCbEntity->CbInvoices';
			$modelMap['Payment']['Transaction'] = 'AffiliateNetwork->Merchant->Link->Website->Transaction';
			$modelMap['Payment']['AffiliateNetworkRCbEntity'] = 'AffiliateNetwork->AffiliateNetworkRCbEntity';
			$modelMap['Payment']['Project'] = 'AffiliateNetwork->Merchant->Link->Website->ProjectRWebsite->Project';
			$modelMap['Payment']['ProjectRAffjetUser'] = 'AffiliateNetwork->Merchant->Link->Website->ProjectRWebsite->Project->ProjectRAffjetUser';
			$modelMap['Payment']['ProjectRWebsite'] = 'AffiliateNetwork->Merchant->Link->Website->ProjectRWebsite';
			$modelMap['Payment']['Overview'] = 'AffiliateNetwork->Merchant->Link->Website->Overview';
			$modelMap['Payment']['Website'] = 'AffiliateNetwork->Merchant->Link->Website';
			$modelMap['Payment']['Link'] = 'AffiliateNetwork->Merchant->Link';
			$modelMap['Payment']['AffiliateNetwork'] = 'AffiliateNetwork';
			$modelMap['ProjectRWebsite']['Merchant'] = 'Website->Link->Merchant';
			$modelMap['ProjectRWebsite']['AffiliateNetworkConfig'] = 'Website->Link->Merchant->AffiliateNetwork->AffiliateNetworkConfig';
			$modelMap['ProjectRWebsite']['CbInvoices'] = 'Website->Link->Merchant->AffiliateNetwork->AffiliateNetworkRCbEntity->CbInvoices';
			$modelMap['ProjectRWebsite']['Transaction'] = 'Website->Transaction';
			$modelMap['ProjectRWebsite']['AffiliateNetworkRCbEntity'] = 'Website->Link->Merchant->AffiliateNetwork->AffiliateNetworkRCbEntity';
			$modelMap['ProjectRWebsite']['Project'] = 'Project';
			$modelMap['ProjectRWebsite']['ProjectRAffjetUser'] = 'Project->ProjectRAffjetUser';
			$modelMap['ProjectRWebsite']['Payment'] = 'Website->Link->Merchant->AffiliateNetwork->Payment';
			$modelMap['ProjectRWebsite']['Overview'] = 'Website->Overview';
			$modelMap['ProjectRWebsite']['Website'] = 'Website';
			$modelMap['ProjectRWebsite']['Link'] = 'Website->Link';
			$modelMap['ProjectRWebsite']['AffiliateNetwork'] = 'Website->Link->Merchant->AffiliateNetwork';
			$modelMap['Overview']['Merchant'] = 'Website->Link->Merchant';
			$modelMap['Overview']['AffiliateNetworkConfig'] = 'Website->Link->Merchant->AffiliateNetwork->AffiliateNetworkConfig';
			$modelMap['Overview']['CbInvoices'] = 'Website->Link->Merchant->AffiliateNetwork->AffiliateNetworkRCbEntity->CbInvoices';
			$modelMap['Overview']['Transaction'] = 'Website->Transaction';
			$modelMap['Overview']['AffiliateNetworkRCbEntity'] = 'Website->Link->Merchant->AffiliateNetwork->AffiliateNetworkRCbEntity';
			$modelMap['Overview']['Project'] = 'Website->ProjectRWebsite->Project';
			$modelMap['Overview']['ProjectRAffjetUser'] = 'Website->ProjectRWebsite->Project->ProjectRAffjetUser';
			$modelMap['Overview']['Payment'] = 'Website->Link->Merchant->AffiliateNetwork->Payment';
			$modelMap['Overview']['ProjectRWebsite'] = 'Website->ProjectRWebsite';
			$modelMap['Overview']['Website'] = 'Website';
			$modelMap['Overview']['Link'] = 'Website->Link';
			$modelMap['Overview']['AffiliateNetwork'] = 'Website->Link->Merchant->AffiliateNetwork';
			$modelMap['Website']['Merchant'] = 'Link->Merchant';
			$modelMap['Website']['AffiliateNetworkConfig'] = 'Link->Merchant->AffiliateNetwork->AffiliateNetworkConfig';
			$modelMap['Website']['CbInvoices'] = 'Link->Merchant->AffiliateNetwork->AffiliateNetworkRCbEntity->CbInvoices';
			$modelMap['Website']['Transaction'] = 'Transaction';
			$modelMap['Website']['AffiliateNetworkRCbEntity'] = 'Link->Merchant->AffiliateNetwork->AffiliateNetworkRCbEntity';
			$modelMap['Website']['Project'] = 'ProjectRWebsite->Project';
			$modelMap['Website']['ProjectRAffjetUser'] = 'ProjectRWebsite->Project->ProjectRAffjetUser';
			$modelMap['Website']['Payment'] = 'Link->Merchant->AffiliateNetwork->Payment';
			$modelMap['Website']['ProjectRWebsite'] = 'ProjectRWebsite';
			$modelMap['Website']['Overview'] = 'Overview';
			$modelMap['Website']['Link'] = 'Link';
			$modelMap['Website']['AffiliateNetwork'] = 'Link->Merchant->AffiliateNetwork';
			$modelMap['Link']['Merchant'] = 'Merchant';
			$modelMap['Link']['AffiliateNetworkConfig'] = 'Merchant->AffiliateNetwork->AffiliateNetworkConfig';
			$modelMap['Link']['CbInvoices'] = 'Merchant->AffiliateNetwork->AffiliateNetworkRCbEntity->CbInvoices';
			$modelMap['Link']['Transaction'] = 'Website->Transaction';
			$modelMap['Link']['AffiliateNetworkRCbEntity'] = 'Merchant->AffiliateNetwork->AffiliateNetworkRCbEntity';
			$modelMap['Link']['Project'] = 'Website->ProjectRWebsite->Project';
			$modelMap['Link']['ProjectRAffjetUser'] = 'Website->ProjectRWebsite->Project->ProjectRAffjetUser';
			$modelMap['Link']['Payment'] = 'Merchant->AffiliateNetwork->Payment';
			$modelMap['Link']['ProjectRWebsite'] = 'Website->ProjectRWebsite';
			$modelMap['Link']['Overview'] = 'Website->Overview';
			$modelMap['Link']['Website'] = 'Website';
			$modelMap['Link']['AffiliateNetwork'] = 'Merchant->AffiliateNetwork';
			$modelMap['AffiliateNetwork']['Merchant'] = 'Merchant';
			$modelMap['AffiliateNetwork']['AffiliateNetworkConfig'] = 'AffiliateNetworkConfig';
			$modelMap['AffiliateNetwork']['CbInvoices'] = 'AffiliateNetworkRCbEntity->CbInvoices';
			$modelMap['AffiliateNetwork']['Transaction'] = 'Merchant->Link->Website->Transaction';
			$modelMap['AffiliateNetwork']['AffiliateNetworkRCbEntity'] = 'AffiliateNetworkRCbEntity';
			$modelMap['AffiliateNetwork']['Project'] = 'Merchant->Link->Website->ProjectRWebsite->Project';
			$modelMap['AffiliateNetwork']['ProjectRAffjetUser'] = 'Merchant->Link->Website->ProjectRWebsite->Project->ProjectRAffjetUser';
			$modelMap['AffiliateNetwork']['Payment'] = 'Payment';
			$modelMap['AffiliateNetwork']['ProjectRWebsite'] = 'Merchant->Link->Website->ProjectRWebsite';
			$modelMap['AffiliateNetwork']['Overview'] = 'Merchant->Link->Website->Overview';
			$modelMap['AffiliateNetwork']['Website'] = 'Merchant->Link->Website';
			$modelMap['AffiliateNetwork']['Link'] = 'Merchant->Link';
					
			define("MODELMAP", serialize($modelMap));
		}
	}
	/**
	 * Read the modelMap and give us the path
	 * @param string $modelStart
	 * @param string $modelEnd
	 * @param array $modelMap
	 */
	public static function breadthFirst($modelStart, $modelEnd, $modelMap){
		$path = null;
		$queue = array();
		$paths = array();
		$enc = false;
		$traversed = array();
		array_push($queue, $modelStart);
		while (!$enc) {
			$element = array_shift($queue);
			$path = array_shift($paths);

			if ($element == null){
				break;
			}

			$traversed[] = $element;

			if (isset($modelMap[$element][$modelEnd])){
				$path .= $modelMap[$element][$modelEnd];
				$enc = true;
			} else {
				$childList = $modelMap[$element];

				foreach($childList as $child => $childValue){
					if (!in_array( $child, $traversed) && !in_array( $child, $queue)){
						array_push($paths, $path.$childValue.'->');
						array_push($queue, $child);
					}

				}
					
			}

		}
		return $path;
	}
	/**
	 * Returns the path for the queries.
	 */
	public static function getQueryPath ($modelStart, $modelEnd, $attribute){
		$modelMap = unserialize(MODELMAP);
		$path = $attribute;
		if (isset($modelMap[$modelStart][$modelEnd])){
			$path = $modelMap[$modelStart][$modelEnd].'->'.$attribute;
		}

		return $path;
	}
	/**
	 * Choose the table scope
	 * @param string $tableName
	 */
	public static function scopeTable($scope){
		$tableName = null;
		if ($scope == 'status'){
			$tableName = 'Transaction_';
		} else {
			$tableName = ucfirst($scope).'_';
		}
		return $tableName;
	}
	/**
	 * Function that generate the code for the multi option filter.
	 * @param string $filter
	 */
	public static function addFilter($filter){
		$result = '';
		$urlHelper = new Zend_View_Helper_Url();
		$result .= '<button type="button" dojoType="dijit.form.Button" title="'.Core::getRegistry()->get('translate')->_("common.filter.add").'">
						<img src="'.Core::getBaseUrl().'/images/icons/plus-icon.png"  
						 	 onclick="addSearchFilter(\''. $filter .'\',
								    				 \''. $urlHelper->url(array('controller' => 'ajax','action' => 'add.search.filter'),'default', true) .'\',
								    				 \''. $urlHelper->url(array('controller' => 'ajax','action' => 'remove.search.filter'),'default', true) .'\',
							                         \''. Core::getRegistry()->get('translate')->_("common.add.search.filter.error") .'\')" />
					</button>';
		$result .= '<button type="button" dojoType="dijit.form.Button" title="'.Core::getRegistry()->get('translate')->_("common.filter.wildcard").'">
    					<img src="'.Core::getBaseUrl().'/images/icons/magnifying-glass.png" 
    						 onclick="openWildcardDialog(\''. $filter .'\')" />
				   </button>';
		
		return $result;
	}
	/**
	 * Function that generate the code for the multi option div.
	 * @param string $filter
	 */
	public static function addFilterDiv($filter){
		$result = '';
		$urlHelper = new Zend_View_Helper_Url();
			
		$searchFilter = new Zend_Session_Namespace('searchFilter');
		if (isset($searchFilter->$filter)){
			$serachFilterValue = $searchFilter->$filter;
			foreach ($serachFilterValue as $key => $value){
				$result	.= '<tr><td>';
				$result	.= '<input type="checkbox" dojoType="dijit.form.CheckBox" checked="checked" class="'.$filter.'"
							 	   name="filterSelectedCheckbox'.$filter.$key.'" id="filterSelectedCheckbox'.$filter.$key.'" value="'.$key.'"
								   onchange="removeSearchFilter(this,\''.$urlHelper->url(array('controller' => 'ajax','action' => 'remove.search.filter'),'default', true).'\')">';
				if (substr($key, 0, 15) == 'affjetWildcard_'){
					$result	.= '<b title="'.$value.'"> <span style="color:#990000">W</span> - '.Oara_Utilities::formatFiltersSelected($value).'</b></input>';
				} else {
					$result	.= '<b title="'.$value.'" >'.Oara_Utilities::formatFiltersSelected($value).'</b></input>';
				} 
								   
				$result	.= '</td></tr>';
			}
		}
		return $result;
	}
	/**
	 * Translate from searchFilter session to formData
	 */
	public static function searchFilterToFormData($table){
		$hiddenAttributtes = '';
		$searchFilter = new Zend_Session_Namespace('searchFilter');
		$selectArray = array('projectSelect', 'affiliateSelect','merchantSelect','linkSelect', 'websiteSelect');
		if ($table == 'transaction'){
			$selectArray[] = 'statusSelect';
		}


		foreach ($selectArray as $select){
			if (isset($searchFilter->$select)){
				$hiddenAttributtes .= '<div id="'.$select.'AddedFilters">';
				$filterMap = $searchFilter->$select;
				$count = 0;
				foreach ($filterMap as $key => $value){
					$formDataNewFilter = $select.$count;
					$hiddenAttributtes .= '<input type="hidden" name="'.$formDataNewFilter.'" value="'.$key.'"></input>';
					$count++;
				}
				$hiddenAttributtes .= '</div>';
			}
		}
		return $hiddenAttributtes;
	}
	/**
	 * Return how many filters we are currently using
	 */
	public static function countFilters($controller){
		$filtersNumber = 0;
		$searchFilter = new Zend_Session_Namespace('searchFilter');
		$selectArray = array('projectSelect', 'affiliateSelect','merchantSelect','linkSelect', 'websiteSelect');
		if ($controller == 'transaction'){
			$selectArray[] = 'statusSelect';
		}
		foreach ($selectArray as $select){
			if (isset($searchFilter->$select)){
				$filtersNumber += count($searchFilter->$select);
			}
		}
		return $filtersNumber;
	}
	/**
	 * Get all the options for the compare Select
	 */
	public static function getCompareSelect($controller){
		$compare = array();
		if ($controller == 'overview'){
			$compare['transaction_confirmed_value'] = Core::getRegistry()->get('translate')->_("filter.compare.transactionConfirmedValue");
			$compare['transaction_confirmed_commission'] = Core::getRegistry()->get('translate')->_("filter.compare.transactionConfirmedCommission");
			$compare['transaction_pending_value'] = Core::getRegistry()->get('translate')->_("filter.compare.transactionPendingValue");
			$compare['transaction_pending_commission'] = Core::getRegistry()->get('translate')->_("filter.compare.transactionPendingCommission");
			$compare['transaction_declined_value'] = Core::getRegistry()->get('translate')->_("filter.compare.transactionDeclinedValue");
			$compare['transaction_declined_commission'] = Core::getRegistry()->get('translate')->_("filter.compare.transactionDeclinedCommission");

			$compare['transaction_number'] = Core::getRegistry()->get('translate')->_("filter.compare.transactionNumber");
			$compare['click_number'] = Core::getRegistry()->get('translate')->_("filter.compare.clickNumber");
			$compare['impression_number'] = Core::getRegistry()->get('translate')->_("filter.compare.impressionNumber");

			$compare['EPC'] = Core::getRegistry()->get('translate')->_("filter.compare.EPC");
			$compare['CR'] = Core::getRegistry()->get('translate')->_("filter.compare.CR");
			$compare['CTR'] = Core::getRegistry()->get('translate')->_("filter.compare.CTR");
			$compare['COR'] = Core::getRegistry()->get('translate')->_("filter.compare.COR");
			$compare['CPA'] = Core::getRegistry()->get('translate')->_("filter.compare.CPA");
			$compare['ECPM'] = Core::getRegistry()->get('translate')->_("filter.compare.ECPM");
				
		} else if ($controller == 'transaction'){
			$compare['amount'] = Core::getRegistry()->get('translate')->_("filter.compare.amount");
			$compare['commission'] = Core::getRegistry()->get('translate')->_("filter.compare.commission");
		}

		return $compare;
	}
	/**
	 * Get all the options for the group Select
	 */
	public static function getGroupSelect($controller, $mode){
		$scope = array();

		$scope['project'] = Core::getRegistry()->get('translate')->_("filter.group.project");
		$scope['affiliateNetwork'] = Core::getRegistry()->get('translate')->_("filter.group.affiliate");
		$scope['merchant'] = Core::getRegistry()->get('translate')->_("filter.group.merchant");
		$scope['link'] = Core::getRegistry()->get('translate')->_("filter.group.link");
		$scope['website'] = Core::getRegistry()->get('translate')->_("filter.group.website");
		
		if ($controller == 'transaction'){
				
			$scope['status'] = Core::getRegistry()->get('translate')->_("filter.group.status");
			if ($mode == null | $mode == 'list' || $mode == 'ranking' || $mode == 'barChart'){
				$scope['none'] = Core::getRegistry()->get('translate')->_("filter.group.none");
			}
				
		} else if ($controller == 'overview'){
			
			if ($mode == 'barChart'){
				$scope['none'] = Core::getRegistry()->get('translate')->_("filter.group.none");
			}
				
		}
		return $scope;
	}
	/**
	 * Get all the options for the mode Select
	 */
	public static function getModeSelect(){
		$mode = array();
		$mode['list'] = Core::getRegistry()->get('translate')->_("common.list.mode.list");
		$mode['ranking'] = Core::getRegistry()->get('translate')->_("common.list.mode.ranking");
		$mode['pieChart'] = Core::getRegistry()->get('translate')->_("common.list.mode.pieChart");
		$mode['barChart'] = Core::getRegistry()->get('translate')->_("common.list.mode.barChart");
		return $mode;
	}
	/**
	 * Get all the options for the data period
	 */
	public static function getDatePeriodSelect(){
		$datePeriod = array();
		$datePeriod['yesterday'] = Core::getRegistry()->get('translate')->_("common.date.period.yesterday");
		$datePeriod['thisWeek'] = Core::getRegistry()->get('translate')->_("common.date.period.thisWeek");
		$datePeriod['lastWeek'] = Core::getRegistry()->get('translate')->_("common.date.period.lastWeek");
		$datePeriod['lastTwoWeeks'] = Core::getRegistry()->get('translate')->_("common.date.period.lastToWeeks");
		$datePeriod['last30Days'] = Core::getRegistry()->get('translate')->_("common.date.period.last30Days");
		$datePeriod['thisMonth'] = Core::getRegistry()->get('translate')->_("common.date.period.thisMonth");
		$datePeriod['lastMonth'] = Core::getRegistry()->get('translate')->_("common.date.period.lastMonth");
		$datePeriod['lastThreeMonths'] = Core::getRegistry()->get('translate')->_("common.date.period.lastTreeMonths");
		$datePeriod['lastSixMonths'] = Core::getRegistry()->get('translate')->_("common.date.period.lastSixMonths");
		$datePeriod['allTime'] = Core::getRegistry()->get('translate')->_("common.date.period.allTime");
		$datePeriod['custom'] = Core::getRegistry()->get('translate')->_("common.date.period.custom");
		return $datePeriod;
	}
	/**
	 * Format the website format on the project
	 * @param string $value
	 */
	public static function formatWebsiteProject($value){
		if (strlen($value) > 17){
			$value = substr($value, 0 , 15).'..';
		}
		return $value;
	}
	/**
	 * Format the fiters selected
	 * @param string $value
	 */
	public static function formatFiltersSelected($value){
		if (strlen($value) > 17){
			$value = substr($value, 0 , 15).'..';
		}
		return $value;
	}
	/**
	 * Get the Frequency select
	 * @return string
	 */
	public static function getFrequencySelect(){
		$frequency = array();
		$frequency['daily'] = Core::getRegistry()->get('translate')->_("common.frequency.daily");
		$frequency['weekly'] = Core::getRegistry()->get('translate')->_("common.frequency.weekly");
		$frequency['monthly'] = Core::getRegistry()->get('translate')->_("common.frequency.mothly");
		$frequency['yearly'] = Core::getRegistry()->get('translate')->_("common.frequency.yearly");
		$frequency['none'] = Core::getRegistry()->get('translate')->_("common.frequency.none");

		return $frequency;
	}
	
	/**
	 * Get the Currency select
	 * @return string
	 */
	public static function getCurrencySelect(){
		$currency = array();
		$currency['GBP'] = Core::getRegistry()->get('translate')->_("common.currency.gbp");
		$currency['EUR'] = Core::getRegistry()->get('translate')->_("common.currency.eur");
		$currency['USD'] = Core::getRegistry()->get('translate')->_("common.currency.usd");
		return $currency;
	}

	/**
	 * Display the Tabs
	 * @param  $container
	 * @param  $navigation
	 */
	public static function displayTabs($container, $navigation){
		$urlHelper = new Zend_View_Helper_Url();
		echo '<ul>';
		// loop root level (only has Home, but later may have an Admin root page?)
		foreach ($container as $page) {
			$moduleId = $page->id;
			
			$pagesContainer = $navigation->findOneById($moduleId);
			$firstContainerPage = null;
			foreach ($pagesContainer as $pageContainer) {
				$controller = $pageContainer->controller;
				$action = $pageContainer->action;
				if ($pageContainer->isVisible() && $navigation->getAcl()->isAllowed($navigation->getRole(), $controller, $action)) {
					$firstContainerPage = $pageContainer;
				}
			}
			
			if ($firstContainerPage != null){
				$isActive = $page->isActive(true);
				if ($isActive){
					echo '<li class="selected" ><h1><a href="'.$urlHelper->url(array('controller' => $firstContainerPage->controller,'action' => $firstContainerPage->action), 'default', true) .'" >'.$page->label .'</a></h1></li>';
					$currentModule = array('currentModuleId' => $moduleId);
					Core::getRegistry()->set('currentModule', $currentModule);
				} else {
					echo '<li ><h1><a href="'.$urlHelper->url(array('controller' => $firstContainerPage->controller,'action' => $firstContainerPage->action), 'default', true) .'" >'.$page->label .'</a></h1></li>';
				}
			}
		}
		echo '</ul>';
	}

	/**
	 * Display the Menu
	 * @param  $container
	 * @param  $navigation
	 */
	public static function displayMenu($container, $navigation){
		$urlHelper = new Zend_View_Helper_Url();
		$config = Core::getRegistry()->get('applicationIni');
		
		if (count ($container) > 1) {
			echo '<ul>';
			foreach ($container as $page) {
				$controller = $page->controller;
				$action = $page->action;
				if ($page->isVisible() && $navigation->getAcl()->isAllowed($navigation->getRole(), $controller, $action)) {
					// check if it is active (not recursive)
					$isActive = $page->isActive(true);
					if ($isActive) {
						echo '<li class="selected" ><h1>' . $navigation->menu()->htmlify($page) . '</h1></li>';
					} else {
						if ($page->controller == 'about'){
							echo '<li><h1><a href="'.$config->wordpress->location.'WordPress/about/" target="_blank">'.$page->label .'</a></h1></li>';
						} else if ($page->controller == 'faq'){
							echo '<li><h1><a href="'.$config->wordpress->location.'WordPress/faqs/" target="_blank">'.$page->label .'</a</h1></li>';
						} else if ($page->controller == 'guides'){
							echo '<li><h1><a href="'.$config->wordpress->location.'WordPress/guides/" target="_blank">'.$page->label .'</a</h1></li>';
						} else {
							echo '<li><h1><a href="'.$urlHelper->url(array('controller' => $page->controller,'action' => $page->action), 'default', true) .'" >'.$page->label .'</a></h1></li>';
						}

					}
				}
			}
			echo '</ul>';
				
				
		}
	}


	/**
	 *
	 * Ping the Subcriptions data base
	 */
	public static function pingSubscriptions(){

		$affjetSettingDao = Dao_Factory_Doctrine::createDoctrineDaoInstance('AffjetSetting');
		$affjetSubcriptionDao = Dao_Factory_Doctrine::createDoctrineDaoInstance('AffjetSubcription');
		$affjetGroupRAffjetUserDao = Dao_Factory_Doctrine::createDoctrineDaoInstance('AffjetGroupRAffjetUser');

		$config = Core::getRegistry()->get('applicationIni');
		$passportArray = $config->fubraPassport->toArray();
		//Setting up the client for the payments
		$client = new Fubra_Service_Payments_Client($config->payment->url,
													array(
												        'siteCode' => $passportArray['PASSPORT_CODE'],
												        'siteKey' => $passportArray['FPWS_AUTH'],
													)
													);
			

		//Checking if we have a subscription for this group already
		$affjetSetting = new AffjetSetting();
		$criteriaList = array();
		$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('name', 'payment_ping_since');
		$affjetSettingCollection = $affjetSettingDao->findBy($criteriaList);
		if (count($affjetSettingCollection) > 0){
			$affjetSetting = $affjetSettingCollection->getFirst();
		}
		$response = $client->subscriptionList(array( 'since' => $affjetSetting->value ));

		foreach ($response['subscriptions'] as $subscription) {
			$affjetSubcription = new AffjetSubcription();
				
			$criteriaList = array();
			$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('reference', $subscription['reference']);
			$affjetSubcriptionCollection = $affjetSubcriptionDao->findBy($criteriaList);
				
			if (count($affjetSubcriptionCollection) > 0){
				$affjetSubcription = $affjetSubcriptionCollection->getFirst();

			} else {
				$criteriaList = array();
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('affjet_user_id', $subscription['userId']);
				$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('affjet_group_id', $subscription['groupId']);
				$affjetGroupRAffjetUserCollection = $affjetGroupRAffjetUserDao->findBy($criteriaList);
				if (count($affjetGroupRAffjetUserCollection) == 1){
					$affjetSubcription->affjet_group_r_affjet_user_id = $affjetGroupRAffjetUserCollection->getFirst()->id;
				}
			}
				
			if ($affjetSubcription->affjet_group_r_affjet_user_id != null){

				$affjetSubcription->name = $subscription['name'];
				$affjetSubcription->reference = $subscription['reference'];
				$affjetSubcription->amount = $subscription['amount'];
				$affjetSubcription->amount_currency = $subscription['amountCurrency'];
				$affjetSubcription->state = $subscription['state'];

				$affjetSubcription->tariff_id = $subscription['tariffId'];
				$affjetSubcription->invoice_next = $subscription['invoiceNext'];
				$affjetSubcription->payment_next = $subscription['paymentNext'];
				$affjetSubcription->started = $subscription['started'];
				$affjetSubcription->closed = $subscription['closed'];

				$affjetSubcription = $affjetSubcriptionDao->saveEntity($affjetSubcription);
			}
		}

		if ($response['lastUpdated'] != null){
			$affjetSetting->value = $response['lastUpdated'];
			$affjetSetting->name = 'payment_ping_since';
			$affjetSettingDao->saveEntity($affjetSetting);
		}
	}

	/**
	 *
	 * Add a new dashboard to the data base
	 * @param $table
	 * @param $formData
	 * @param $searchFilter
	 */
	public static function addNewDashboard($table, array $formData, Zend_Session_Namespace $searchFilter, $affjetUserId, $view){
		$dashboardDao = Dao_Factory_Doctrine::createDoctrineDaoInstance('Dashboard');
		$criteriaList = array();
		$criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('affjet_user_id', $affjetUserId);
		$dahsboardNumber = $dashboardDao->getCount($criteriaList);
		$config = Core::getRegistry()->get('applicationIni');
		if ($dahsboardNumber < $config->dashboard->limit) {

			if (isset($formData['datePeriodSelect']) && isset($formData['scopeSelect']) && isset($formData['modeSelect'])){


				$dashboardFiltersDao = Dao_Factory_Doctrine::createDoctrineDaoInstance('DashboardFilters');
					
				$dashboard = new Dashboard();
				if (isset($formData['datePeriodSelect'])){
					$dashboard->period = $formData['datePeriodSelect'];
				}
				if (isset($formData['startDate']) && $formData['startDate'] != ''){
					$dashboard->from = $formData['startDate'];
				}
				if (isset($formData['endDate']) && $formData['endDate'] != ''){
					$dashboard->to = $formData['endDate'];
				}
				if (isset($formData['compareSelect'])){
					$dashboard->compare = $formData['compareSelect'];
				}
				if (isset($formData['scopeSelect'])){
					$dashboard->group = $formData['scopeSelect'];
				}
				if (isset($formData['modeSelect'])){
					$dashboard->mode = $formData['modeSelect'];
				}
				if (isset($formData['frequencySelect'])){
					$dashboard->frequency = $formData['frequencySelect'];
				}
				$dashboard->affjet_user_id = $affjetUserId;
				$dashboard->title = $formData['dashboardNameTextBox'];
				$dashboard->module = $table;
				$dashboard = $dashboardDao->saveEntity($dashboard);

				$selectArray = array('projectSelect', 'affiliateSelect','merchantSelect','linkSelect', 'websiteSelect', 'statusSelect');
				foreach ($selectArray as $select){
					if (isset($formData[$select]) && $formData[$select] != '0'){
						$dashboardFilters = new DashboardFilters();
						$dashboardFilters->dashboard_id = $dashboard->id;
						$dashboardFilters->filter = $select;
						$dashboardFilters->value = $formData[$select];
						$dashboardFilters = $dashboardFiltersDao->saveEntity($dashboardFilters);
					}
					if (isset($searchFilter->$select)){
						$filterMap = $searchFilter->$select;
						foreach ($filterMap as $key => $value){
							$dashboardFilters = new DashboardFilters();
							$dashboardFilters->dashboard_id = $dashboard->id;
							$dashboardFilters->filter = $select.'Filter';
							$dashboardFilters->value = $key;
							$dashboardFilters = $dashboardFiltersDao->saveEntity($dashboardFilters);
						}
					}
				}

				if (isset($formData['columnsList'])){
					$columnList = preg_split("/[-]/", $formData['columnsList']);
					foreach($columnList as $column){
						if ($column != 'notes'){
							$dashboardFilters = new DashboardFilters();
							$dashboardFilters->dashboard_id = $dashboard->id;
							$dashboardFilters->filter = 'columnFilter';
							$dashboardFilters->value = $column;
							$dashboardFilters = $dashboardFiltersDao->saveEntity($dashboardFilters);
						}
						
					}
				}

				//Send a message
				$view->messenger(Core::getRegistry()->get('translate')->_("dashboard.new.ok"), 'ok');
			}
		} else {
			//Send a message
			$view->messenger(Core::getRegistry()->get('translate')->_("dashboard.new.maxDashboard"), 'error');
				
		}
	}
	/**
	 * 
	 * Glue the parsed url and returns the string
	 * @param string $parsed
	 */
	public static function glue_url($parsed)
    {
	    if (! is_array($parsed)) return false;
	    $uri = isset($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '':'//'): '';
	    $uri .= isset($parsed['user']) ? $parsed['user'].($parsed['pass']? ':'.$parsed['pass']:'').'@':'';
	    $uri .= isset($parsed['host']) ? $parsed['host'] : '';
	    $uri .= isset($parsed['port']) ? ':'.$parsed['port'] : '';
	    if(isset($parsed['path']))
	        {
	        $uri .= (substr($parsed['path'],0,1) == '/')?$parsed['path']:'/'.$parsed['path'];
	        }
	    $uri .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
	    $uri .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';
	    return $uri;
    }
    /**
     * 
     * Get the query and builds an array.
     * @param array $op
     */
    public static function parseUrlQuery($str) {
	    $op = array();
	    $str = str_replace("&amp", "&", $str);
	    $pairs = explode("&", $str);
	    foreach ($pairs as $pair) {
	        list($k, $v) = array_map("urldecode", explode("=", $pair));
	        $op[$k] = $v;
	    }
	    return $op;
	} 
	/**
     * 
     * Get the help message for the networks.
     * @param array $affjetAffiliateId
     */
    public static function helpAffiliate($affjetAffiliateId) {
    	$affjetAffiliateNetworkDao = Dao_Factory_Doctrine::createDoctrineDaoInstance('AffjetAffiliateNetwork');
    	$translate = Core::getRegistry()->get('translate');
    	
    	$criteriaList = array();
	    $criteriaList[] = new Dao_Doctrine_Criteria_Restriction_Eq('id', $affjetAffiliateId);
	    $affjetAffiliateNetwork = $affjetAffiliateNetworkDao->findBy($criteriaList)->getFirst();
    	$code = $affjetAffiliateNetwork->code;
    	
    	
    	$affiliateHelp = "";
    	//selecting hel for the credentials
    	switch ($code){ 		
   			case 'aw':
    			$affiliateHelp = $translate->_("affiliate.regsiter.help.affiliateWindow");
   			break;
   			case 'td':
    			$affiliateHelp = $translate->_("affiliate.regsiter.help.tradeDoubler");
   			break;
    		case 'omg':
    			$affiliateHelp = $translate->_("affiliate.regsiter.help.omg");
   			break;
   			case 'cj':
    			$affiliateHelp = $translate->_("affiliate.regsiter.help.comissionJunction");
   			break;
    		case 'buy':
   				$affiliateHelp = $translate->_("affiliate.regsiter.help.buyAt");
    		break;
    		case 'dgm':
    			$affiliateHelp = $translate->_("affiliate.regsiter.help.dgm");
    		break;
    		case 'wg':
    			$affiliateHelp = $translate->_("affiliate.regsiter.help.webgains");
   			break;
   			case 'st':
   				$affiliateHelp = $translate->_("affiliate.regsiter.help.silvertap");
    		break;
    		case 'af':
    			$affiliateHelp = $translate->_("affiliate.regsiter.help.affiliateFuture");
    		break;
    		case 'an':
    			$affiliateHelp = $translate->_("affiliate.regsiter.help.affilinet");
    		break;
    		case 'zn':
    			$affiliateHelp = $translate->_("affiliate.regsiter.help.zanox");
    		break;
    		case 'as':
    			$affiliateHelp = $translate->_("affiliate.regsiter.help.adSense");
    		break;
    		case 'lsuk':
    			$affiliateHelp = $translate->_("affiliate.regsiter.help.linkshareUk");
    		break;
    		case 'lsus':
    			$affiliateHelp = $translate->_("affiliate.regsiter.help.linkshareUs");
    		break;
    		case 'wow':
    			$affiliateHelp = $translate->_("affiliate.regsiter.help.wowTrk");
    		break;
    		case 'smg':
    			$affiliateHelp = $translate->_("affiliate.regsiter.help.starComMediaVest");
    		break;
    		case 'por':
    			$affiliateHelp = $translate->_("affiliate.regsiter.help.paidOnResults");
    		break;
    		case 'tt':
    			$affiliateHelp = $translate->_("affiliate.regsiter.help.tradeTracker");
    		break;
    		default:
    			$affiliateHelp = '';
    		break;

    	}
    	return $affiliateHelp;
	} 
	
	/**
     * Calculate the number of iterations needed
     * @param $rowAvailable
     * @param $rowsReturned
     */
	public static function calculeIterationNumber($rowAvailable, $rowsReturned){
		$iterationDouble = (double)($rowAvailable/$rowsReturned);
		$iterationInt = (int)($rowAvailable/$rowsReturned);
		if($iterationDouble > $iterationInt){
			$iterationInt++;
		}
		return $iterationInt;
	}

}