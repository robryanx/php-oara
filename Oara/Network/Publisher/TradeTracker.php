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
 * @category   Tt
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class TradeTracker extends \Oara\Network
{
    private $_apiClient = null;

    public function login($credentials)
    {
        $user = $credentials['user'];
        $password = $credentials['apipassword'];

        $wsdlUrl = 'http://ws.tradetracker.com/soap/affiliate?wsdl';
        //Setting the client.
        $this->_apiClient = new \SoapClient($wsdlUrl, array('encoding' => 'UTF-8',
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | SOAP_COMPRESSION_DEFLATE,
            'soap_version' => SOAP_1_1));

        $this->_apiClient->authenticate($user, $password, false, 'en_GB');
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
        $parameter["name"] = "Password";
        $credentials["password"] = $parameter;

        return $credentials;
    }

    /**
     * @return bool
     */
    public function checkConnection()
    {
        $connection = true;
        return $connection;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = array();

        $merchantsAux = array();
        $options = array('assignmentStatus' => 'accepted');
        $affiliateSitesList = $this->_apiClient->getAffiliateSites();
        foreach ($affiliateSitesList as $affiliateSite) {
            $campaignsList = $this->_apiClient->getCampaigns($affiliateSite->ID, $options);
            foreach ($campaignsList as $campaign) {
                if (!isset($merchantsAux[$campaign->name])) {
                    $obj = Array();
                    $obj['cid'] = $campaign->ID;
                    $obj['name'] = $campaign->name;
                    $obj['url'] = $campaign->URL;
                    $merchantsAux[$campaign->name] = $obj;
                }
            }
        }
        foreach ($merchantsAux as $merchantAux) {
            $merchants[] = $merchantAux;
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
        $totalTransactions = array();
        $merchantIdList = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);

        $options = array(
            'registrationDateFrom' => $dStartDate->format('Y-m-d'),
            'registrationDateTo' => $dEndDate->add(new \DateInterval('P1D'))->format('Y-m-d'),
        );
        $affiliateSitesList = $this->_apiClient->getAffiliateSites();
        foreach ($affiliateSitesList as $affiliateSite) {
            foreach ($this->_apiClient->getConversionTransactions($affiliateSite->ID, $options) as $transaction) {
                if ($merchantList == null || isset($merchantIdList[(int)$transaction->campaign->ID])) {
                    $object = array();

                    $object['unique_id'] = $transaction->ID;

                    $object['merchantId'] = $transaction->campaign->ID;

                    $transactionDate = new \DateTime($transaction->registrationDate);
                    $object['date'] = $transactionDate->format("Y-m-d H:i:s");

                    if ($transaction->reference != null) {
                        $object['custom_id'] = $transaction->reference;
                    }

                    if ($transaction->transactionStatus == 'accepted') {
                        $object['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                    } else
                        if ($transaction->transactionStatus == 'pending') {
                            $object['status'] = \Oara\Utilities::STATUS_PENDING;
                        } else
                            if ($transaction->transactionStatus == 'rejected') {
                                $object['status'] = \Oara\Utilities::STATUS_DECLINED;
                            }

                    $object['amount'] = \Oara\Utilities::parseDouble($transaction->orderAmount);
                    $object['commission'] = \Oara\Utilities::parseDouble($transaction->commission);
                    $totalTransactions[] = $object;
                }
            }
        }

        return $totalTransactions;
    }

    /**
     * (non-PHPdoc)
     * @see Oara/Network/Base#getPaymentHistory()
     */
    public function getPaymentHistory()
    {
        $paymentHistory = array();
        $options = array();

        foreach ($this->_apiClient->getPayments($options) as $payment) {
            $obj = array();
            $date = new \DateTime($payment->billDate);
            $obj['date'] = $date->format("Y-m-d H:i:s");
            $obj['pid'] = $date->format("Ymd");
            $obj['method'] = 'BACS';
            $obj['value'] = \Oara\Utilities::parseDouble($payment->endTotal);
            $paymentHistory[] = $obj;
        }
        return $paymentHistory;
    }

}
