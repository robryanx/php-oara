<?php
namespace Oara\Network\Publisher;
    /**
     * The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set
     * of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.
     *
     * Copyright (C) 2016  Fubra Limited
     * This program is free software: you can redistribute it and/or modify
     * it under the terms of the GNU Affero General Public License as published by
     * the Free Software Foundation, either version 3 of the License, or any later version.
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU Affero General Public License for more details.
     * You should have received a copy of the GNU Affero General Public License
     * along with this program.  If not, see <http://www.gnu.org/licenses/>.
     *
     * Contact
     * ------------
     * Fubra Limited <support@fubra.com> , +44 (0)1252 367 200
     **/
/**
 * Export Class
 *
 * @author     Carlos Morillo Merino
 * @category   Affiliate4You
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Affiliate4You extends \Oara\Network
{

    private $_user = null;
    private $_pass = null;
    private $_client = null;

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        $this->_user = $credentials['user'];
        $this->_pass = $credentials['apipassword'];
        $this->_client = new \Oara\Curl\Access($credentials);
    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["description"] = "User Log in";
        $parameter["required"] = true;
        $parameter["name"] = "User";
        $credentials["user"] = $parameter;

        $parameter = array();
        $parameter["description"] = "API password";
        $parameter["required"] = true;
        $parameter["name"] = "API password";
        $credentials["apipassword"] = $parameter;

        return $credentials;
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        //If not login properly the construct launch an exception
        $connection = true;
        $urls = array();
        $valuesFromExport = array();
        $valuesFromExport[] = new \Oara\Curl\Parameter('email', $this->_user);
        $valuesFromExport[] = new \Oara\Curl\Parameter('apikey', $this->_pass);
        $valuesFromExport[] = new \Oara\Curl\Parameter('limit', "1");
        $urls[] = new \Oara\Curl\Request("http://api.affiliate4you.nl/1.0/campagnes/all.csv?", $valuesFromExport);

        try {
            $this->_client->get($urls);
        } catch (\Exception $e) {
            $connection = false;
        }
        return $connection;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = Array();

        $page = 1;
        $import = true;
        while ($import) {

            $totalRows = ($page * 100);

            $urls = array();
            $valuesFromExport = array();
            $valuesFromExport[] = new \Oara\Curl\Parameter('email', $this->_user);
            $valuesFromExport[] = new \Oara\Curl\Parameter('apikey', $this->_pass);
            $valuesFromExport[] = new \Oara\Curl\Parameter('limit', 100);
            $valuesFromExport[] = new \Oara\Curl\Parameter('page', $page);
            $urls[] = new \Oara\Curl\Request("http://api.affiliate4you.nl/1.0/campagnes/all.csv?", $valuesFromExport);
            $result = $this->_client->get($urls);
            $exportData = \str_getcsv($result[0], "\n");

            for ($i = 1; $i < \count($exportData); $i++) {
                $merchantExportArray = \str_getcsv($exportData[$i], ";");
                $obj = Array();
                $obj['cid'] = $merchantExportArray[1];
                $obj['name'] = $merchantExportArray[2];
                $merchants[] = $obj;
            }

            if (\count($exportData) != ($totalRows + 1)) {
                $import = false;
            }
            $page++;

        }


        return $merchants;
    }

    /**
     * @param null $merchantList
     * @param \DateTime|null $dStartDate
     * @param \DateTime|null $dEndDate
     * @return array
     */
	public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
	{
		$transactions = array();
		$page = 1;
		$import = true;

		$merchantIdMap = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);

		while ($import) {

			$pagesize = 300;

			$urls = array();
			$valuesFromExport = array();
			$valuesFromExport[] = new \Oara\Curl\Parameter('email', $this->_user);
			$valuesFromExport[] = new \Oara\Curl\Parameter('apikey', $this->_pass);
			$valuesFromExport[] = new \Oara\Curl\Parameter('from', $dStartDate->format("Y-m-d"));
			$valuesFromExport[] = new \Oara\Curl\Parameter('to', $dEndDate->format("Y-m-d"));
			$valuesFromExport[] = new \Oara\Curl\Parameter('limit', $pagesize);
			$valuesFromExport[] = new \Oara\Curl\Parameter('page', $page);
			$urls[] = new \Oara\Curl\Request("http://api.affiliate4you.nl/1.0/orders.csv?", $valuesFromExport);
			try {
				$result = $this->_client->get($urls);
			} catch (\Exception $e) {
				return $transactions;
			}

			$exportData = \str_getcsv($result[0], "\n");

			for ($i = 1; $i < \count($exportData); $i++) {

				$transactionExportArray = \str_getcsv($exportData[$i], ";");
				if (isset($merchantIdMap[$transactionExportArray[12]])) {
					$transaction = Array();
					$transaction['unique_id'] = $transactionExportArray[3];
					$transaction['merchantId'] = $transactionExportArray[12];
					$transaction['date'] = $transactionExportArray[0];

					if ($transactionExportArray[8] != null) {
						$transaction['custom_id'] = $transactionExportArray[8];
					}

					if ($transactionExportArray[5] == 'approved') {
						$transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
					} else
						if ($transactionExportArray[5] == 'new' || $transactionExportArray[5] == 'onhold') {
							$transaction['status'] = \Oara\Utilities::STATUS_PENDING;
						} else
							if ($transactionExportArray[5] == 'declined') {
								$transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
							}

					$transaction['amount'] = $transactionExportArray[4];
					$transaction['commission'] = $transactionExportArray[1];
					$transactions[] = $transaction;
				}
			}


			if (\count($exportData) != ($pagesize+1)) {
				$import = false;
			}
			$page++;
		}
		return $transactions;
	}

}
