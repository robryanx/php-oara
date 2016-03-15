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
 * Api Class
 *
 * @author     Carlos Morillo Merino
 * @category   Dgm
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Dgm extends \Oara\Network
{
    /**
     * Soap client.
     */
    private $_apiClient = null;
    private $_user = null;
    private $_pass = null;
    private $_advertisersCampaings = null;

    /**
     * @param $credentials
     */
    public function login($credentials)
    {
        // Reading the different parameters.
        $this->_user = $credentials ['user'];
        $this->_pass = $credentials ['password'];

        $wsdlUrl = 'http://webservices.dgperform.com/dgmpublisherwebservices.cfc?wsdl';
        // Setting the apiClient.
        $this->_apiClient = new \SoapClient ($wsdlUrl, array(
            'encoding' => 'UTF-8',
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
            'soap_version' => SOAP_1_1
        ));
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
        $parameter["description"] = "Password to Log in";
        $parameter["required"] = true;
        $parameter["name"] = "password";
        $credentials["password"] = $parameter;

        return $credentials;
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        $connection = true;

        $merchantsImportXml = $this->_apiClient->GetCampaigns($this->_user, $this->_pass, 'approved');
        $xmlObject = new \SimpleXMLElement ($merchantsImportXml);
        if ($xmlObject->attributes()->status == 'error') {
            $connection = false;
        }
        return $connection;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getMerchantList()
    {
        $merchants = array();

        $merchantsImportXml = $this->_apiClient->GetCampaigns($this->_user, $this->_pass, 'approved');
        $xmlObject = new \SimpleXMLElement ($merchantsImportXml);
        if ($xmlObject->attributes()->status == 'error') {
            throw new \Exception ('Error advertisers not found');
        }

        foreach ($xmlObject->campaigns->campaign as $campaing) {

            $obj = array();
            $obj ['cid'] = ( string )$campaing->advertiserid;
            $obj ['name'] = ( string )$campaing->advertisername;
            $merchants [] = $obj;

            if (!isset ($this->_advertisersCampaings [( string )$campaing->advertiserid])) {
                $this->_advertisersCampaings [( string )$campaing->advertiserid] = ( string )$campaing->campaignid;
            } else {
                $this->_advertisersCampaings [( string )$campaing->advertiserid] .= ',' . ( string )$campaing->campaignid;
            }
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
        $totalTransactions = Array();

        $transactionXml = $this->_apiClient->GetSales($this->_user, $this->_pass, 0, 'all', 'validated', $dStartDate->format("Y-m-d"), $dEndDate->format("Y-m-d"));
        $xmlObject = new \SimpleXMLElement ($transactionXml);
        if ($xmlObject->attributes()->status != 'error') {

            $campaignIdList = array();
            foreach ($merchantList as $merchantId) {
                if (isset($this->_advertisersCampaings [( string )$merchantId])) {
                    $campaingList = \explode(",", $this->_advertisersCampaings [( string )$merchantId]);
                    foreach ($campaingList as $campaignId) {
                        $campaignIdList [$campaignId] = $merchantId;
                    }
                }
            }

            foreach ($xmlObject->sales->sale as $sale) {

                if (isset ($campaignIdList [( string )$sale->Campaignid])) {

                    $transaction = Array();
                    $transaction ['unique_id'] = ( string )$sale->OrderID;
                    $transaction ['merchantId'] = $campaignIdList [( string )$sale->CampaignID];
                    $transaction ['date'] = ( string )$sale->SaleDate;

                    if (( string )$sale->CompanyID != null) {
                        $transaction ['custom_id'] = ( string )$sale->CompanyID;
                    }

                    if (( string )$sale->SaleStatus == 'Approved') {
                        $transaction ['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                    } else if (( string )$sale->SaleStatus == 'Pending') {
                        $transaction ['status'] = \Oara\Utilities::STATUS_PENDING;
                    } else if (( string )$sale->SaleStatus == 'Deleted') {
                        $transaction ['status'] = \Oara\Utilities::STATUS_DECLINED;
                    }

                    $transaction ['amount'] = ( string )$sale->SaleValue;

                    $transaction ['commission'] = ( string )$sale->SaleCommission;
                    $totalTransactions [] = $transaction;
                }
            }
        }

        return $totalTransactions;
    }
}
