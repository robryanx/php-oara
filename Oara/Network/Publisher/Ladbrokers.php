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
 * @category   Td
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Ladbrokers extends \Oara\Network
{

    private $_client = null;

    public function login($credentials)
    {

        $this->_client = new \Oara\Curl\Access($credentials);

        $user = $credentials['user'];
        $password = $credentials['password'];
        $loginUrl = 'https://portal.ladbrokespartners.com/portal/j_spring_security_check?lang=en';

        $valuesLogin = array(
            new \Oara\Curl\Parameter('j_username', $user),
            new \Oara\Curl\Parameter('j_password', $password)
        );

        $urls = array();
        $urls[] = new \Oara\Curl\Request($loginUrl, $valuesLogin);
        $this->_client->post($urls);
    }


    /**
     * @return bool
     */
    public function checkConnection()
    {
        $connection = false;

        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://portal.ladbrokespartners.com/portal/dashboard.jhtm', array());
        $exportReport = $this->_client->get($urls);

        if (\preg_match('/\<a href\=\"j\_spring\_security\_logout\"\>Logout\<\/a\>/', $exportReport[0], $match)) {
            $connection = true;
        }

        return $connection;
    }

    /**
     * @return array
     */
    public function getMerchantList()
    {
        $merchants = Array();

        $obj = Array();
        $obj['cid'] = 1;
        $obj['name'] = 'Ladbrokers';
        $merchants[] = $obj;

        return $merchants;
    }

    /**
     * @param null $merchantList
     * @param \DateTime|null $dStartDate
     * @param \DateTime|null $dEndDate
     * @return array
     * @throws Exception
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {

        
        return array();
    }
}
