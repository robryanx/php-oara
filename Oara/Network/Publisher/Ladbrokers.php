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
 * @category   Ladbrokers
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Ladbrokers extends \Oara\Network
{

    /**
     * Client
     * @var unknown_type
     */
    private $_client = null;

    /**
     * Constructor and Login
     * @param $credentials
     * @return Daisycon
     */
    public function __construct($credentials)
    {
        $user = $credentials['user'];
        $password = $credentials['password'];

        $valuesLogin = array(
            new \Oara\Curl\Parameter('j_username', $user),
            new \Oara\Curl\Parameter('j_password', $password),
            new \Oara\Curl\Parameter('submit1', 'GO')
        );


        $loginUrl = 'https://portal.ladbrokespartners.com/portal/j_spring_security_check';
        $this->_client = new \Oara\Curl\Access($loginUrl, $valuesLogin, $credentials);


        $this->_exportPaymentParameters = array(new \Oara\Curl\Parameter('action', 'do_report_payments'),
            new \Oara\Curl\Parameter('daterange', '7')
        );

    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        //If not login properly the construct launch an exception
        $connection = false;
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://portal.ladbrokespartners.com/portal/dashboard.jhtm?currentLanguage=en', array());
        $exportReport = $this->_client->get($urls);


        if (preg_match("/Logout/", $exportReport[0])) {
            $connection = true;
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
        $obj['cid'] = 1;
        $obj['name'] = "Ladbrokers";
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


        return $totalTransactions;
    }

}
