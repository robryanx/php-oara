<?php
/**
 The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
 of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.

 Copyright (C) 2014  Carlos Morillo Merino
 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU Affero General Public License as published by
 the Free Software Foundation, either version 3 of the License, or any later version.
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU Affero General Public License for more details.
 You should have received a copy of the GNU Affero General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.

 Contact
 ------------
 Fubra Limited <support@fubra.com> , +44 (0)1252 367 200
 **/
/**
 * API Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Network_Publisher_Efiliation
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Network_Publisher_Effiliation extends Oara_Network {
	/**
	 * Export Credentials
	 * @var array
	 */
	private $_credentials = null;
	/**
	 * Constructor and Login
	 * @param $credentials
	 * @return Oara_Network_Publisher_Effiliation
	 */
	public function __construct($credentials) {

		$this->_credentials = $credentials;

	}
	/**
	 * Check the connection
	 */
	public function checkConnection() {
		$connection = false;

		$content = file_get_contents('http://api.effiliation.com/apiv2/transaction.csv?key='.$this->_credentials["apiPassword"]);
		if (!preg_match("/bad credentials !/", $content, $matches)) {
			$connection = true;
		}
		return $connection;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getMerchantList()
	 */
	public function getMerchantList() {
		$merchants = array();
		$url = 'http://api.effiliation.com/apiv2/programs.xml?key='.$this->_credentials["apiPassword"]."&filter=active";
		$content = @file_get_contents($url);
		$xml = simplexml_load_string($content, null, LIBXML_NOERROR | LIBXML_NOWARNING);
		foreach ($xml->program as $merchant) {
			$obj = array();
			$obj['cid'] = (string) $merchant->id_programme;
			$obj['name'] = (string) $merchant->nom;
			$obj['url'] = "";
			$merchants[] = $obj;
		}
		return $merchants;
	}

	/**
	 * (non-PHPdoc)
	 * @see library/Oara/Network/Oara_Network_Publisher_Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate)
	 */
	public function getTransactionList($merchantList = null, Zend_Date $dStartDate = null, Zend_Date $dEndDate = null, $merchantMap = null) {
		$totalTransactions = array();

		$url = 'http://api.effiliation.com/apiv2/transaction.csv?key='.$this->_credentials["apiPassword"].'&start='.$dStartDate->toString("dd/MM/yyyy").'&end='.$dEndDate->toString("dd/MM/yyyy").'&type=datetran';
		$content = utf8_encode(file_get_contents($url));
		$exportData = str_getcsv($content, "\n");
		$num = count($exportData);
		for ($i = 1; $i < $num; $i++) {
			$transactionExportArray = str_getcsv($exportData[$i], "|");
			if (in_array((int) $transactionExportArray[2], $merchantList)) {
				/*
				$numFields = 0;
				foreach ($transactionExportArray as $fieldValue){
					if ($fieldValue == "Valide" || $fieldValue == "Attente" || $fieldValue == "Refusé"){
						break;
					}
					$numFields ++;
				}
				*/
				
				$transaction = Array();
				$merchantId = (int) $transactionExportArray[2];
				$transaction['merchantId'] = $merchantId;
				$transaction['date'] = $transactionExportArray[10];
				$transaction['unique_id'] = $transactionExportArray[0];

				if ($transactionExportArray[15] != null) {
					$transaction['custom_id'] = $transactionExportArray[15];
				}

				if ($transactionExportArray[9] == 'Valide') {
					$transaction['status'] = Oara_Utilities::STATUS_CONFIRMED;
				} else
					if ($transactionExportArray[9] == 'Attente') {
						$transaction['status'] = Oara_Utilities::STATUS_PENDING;
					} else
						if ($transactionExportArray[9] == 'Refusé') {
							$transaction['status'] = Oara_Utilities::STATUS_DECLINED;
						}
				$transaction['amount'] = Oara_Utilities::parseDouble($transactionExportArray[7]);
				$transaction['commission'] = Oara_Utilities::parseDouble($transactionExportArray[8]);
				$totalTransactions[] = $transaction;
			}
		}
		return $totalTransactions;
	}

}
