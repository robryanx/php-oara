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
 * @author     Alejandro Muñoz Odero
 * @category   WinnerAffiliates
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class WinnerAffiliates extends \Oara\Network
{

    private $_credentials = null;
    /**
     * Client
     * @var unknown_type
     */
    private $_client = null;

    /**
     * Constructor and Login
     * @param $credentials
     * @return PureVPN
     */
    public function __construct($credentials)
    {
        $this->_credentials = $credentials;
        self::logIn();

    }

    private function logIn()
    {

        $valuesLogin = array(
            new \Oara\Curl\Parameter('fromUrl', 'https://www.winneraffiliates.com/'),
            new \Oara\Curl\Parameter('username', $this->_credentials['user']),
            new \Oara\Curl\Parameter('password', $this->_credentials['password']),
        );

        $loginUrl = 'https://www.winneraffiliates.com/login/submit';
        $this->_client = new \Oara\Curl\Access($loginUrl, $valuesLogin, $this->_credentials);

    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        //If not login properly the construct launch an exception
        $connection = true;
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://www.winneraffiliates.com/', array());

        $exportReport = $this->_client->get($urls);

        $dom = new Zend_Dom_Query($exportReport[0]);
        $results = $dom->query('#lgUsername');

        if (count($results) > 0) {
            $connection = false;
        }
        return $connection;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Interface#getMerchantList()
     */
    public function getMerchantList()
    {
        $merchants = array();

        $obj = array();
        $obj['cid'] = "1";
        $obj['name'] = "Winner Affiliates";
        $obj['url'] = "https://www.winneraffiliates.com/";
        $merchants[] = $obj;

        return $merchants;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Interface#getTransactionList($aMerchantIds, $dStartDate, $dEndDate, $sTransactionStatus)
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {

        $totalTransactions = array();
        $valuesFormExport = array();

        $valuesFromExport[] = new \Oara\Curl\Parameter('periods', 'custom');
        $valuesFromExport[] = new \Oara\Curl\Parameter('minDate', '{"year":"2009","month":"05","day":"01"}');
        $valuesFromExport[] = new \Oara\Curl\Parameter('show_periods', '1');
        $valuesFromExport[] = new \Oara\Curl\Parameter('fromPeriod', $dStartDate->toString('yyyy-MM-dd'));
        $valuesFromExport[] = new \Oara\Curl\Parameter('toPeriod', $dEndDate->toString('yyyy-MM-dd'));
        $valuesFromExport[] = new \Oara\Curl\Parameter('product', '');
        $valuesFromExport[] = new \Oara\Curl\Parameter('profile', '');
        $valuesFromExport[] = new \Oara\Curl\Parameter('campaign', '16800');
        $valuesFromExport[] = new \Oara\Curl\Parameter('jsonCampaigns', '{"16800":{"group":{"banner":"Banner","product":"Brand","campaign":"Campaign","platform":"Platform","productType":"Product type","profile":"Profile","date":"Stats date","month":"Stats month","var1":"var1","var2":"var2","var3":"var3","var4":"var4"},"order":{"pokerTournamentFees":"Poker tournament fees","pokerRakes":"Poker rakes","chargebacks":"Chargebacks amt","comps":"Comps amt","credits":"Credit amt","depositsAmount":"Deposits amt","depositsCount":"Deposits cnt","realClicks":"Real clicks","realDownloads":"Real downs","realImpressions":"Real imps","withdrawsAmount":"Withdraws","casinoNetGaming":"Casino Net Gaming","pokerNetGaming":"Poker Net Gaming","pokerSideGamesNG":"Poker Side Games Net Gaming","bingoNetGaming":"Bingo Net Gaming","bingoSideGamesNG":"Bingo Side Games Net Gaming","bingoTotalFDCount":"Bingo Total First Deposit Count","casinoTotalFDCount":"Casino Total First Deposit Count","pokerTotalFDCount":"Poker Total First Deposit Count","casinoTotalRealPlayers":"Casino Total Real Players","bingoTotalRealPlayers":"Bingo Total Real Players","pokerTotalRealPlayers":"Poker Total Real Players","tlrAmount":"Top Level Revenue"}}}');
        $valuesFromExport[] = new \Oara\Curl\Parameter('ts_type', 'advertiser');
        $valuesFromExport[] = new \Oara\Curl\Parameter('reportFirst', 'date');
        $valuesFromExport[] = new \Oara\Curl\Parameter('reportSecond', '');
        $valuesFromExport[] = new \Oara\Curl\Parameter('reportThird', '');
        $valuesFromExport[] = new \Oara\Curl\Parameter('columns[]', 'casinoNetGaming');
        $valuesFromExport[] = new \Oara\Curl\Parameter('columns[]', 'tlrAmount');
        $valuesFromExport[] = new \Oara\Curl\Parameter('csvRequested', 'EXPORT CSV');

        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://www.winneraffiliates.com/traffic-stats/advertiser', $valuesFromExport);

        $exportReport = array();
        $exportReport = $this->_client->post($urls);
        $exportData = str_getcsv($exportReport[0], "\n");

        $num = count($exportData);
        for ($i = 1; $i < $num; $i++) {

            $transactionExportArray = str_getcsv($exportData[$i], ",");
            $transaction = Array();
            $transaction['merchantId'] = 1;
            $transactionDate = new \DateTime($transactionExportArray[0], 'yyyy-MM-dd HH:mm:ss', 'en');
            $transaction['date'] = $transactionDate->toString("yyyy-MM-dd HH:mm:ss");
            //unset($transactionDate);
            $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
            $amount = str_replace('$', '', $transactionExportArray[1]);
            $transaction['amount'] = (double)$amount;
            $commission = str_replace('$', '', $transactionExportArray[2]);
            $transaction['commission'] = (double)$commission;

            if ($transaction['amount'] != 0 && $transaction['commission'] != 0) {
                $totalTransactions[] = $transaction;
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


        return $paymentHistory;
    }

}