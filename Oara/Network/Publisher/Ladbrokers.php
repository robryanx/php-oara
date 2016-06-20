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
        $merchantIdList = \Oara\Utilities::getMerchantIdMapFromMerchantList($merchantList);

        $valuesFormExport = array();
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[0].field', 'startDate');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[0].label', 'Start Date');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[0].type', 'date');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[0].value', $dStartDate->format("Y-m-d"));
        $valuesFormExport[] = new \Oara\Curl\Parameter('startDate', $dStartDate->format("Y-m-d"));
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[1].field', 'endDate');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[1].label', 'End Date');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[1].type', 'date');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[1].value', $dEndDate->format("Y-m-d"));
        $valuesFormExport[] = new \Oara\Curl\Parameter('endDate', $dEndDate->format("Y-m-d"));
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[2].field', 'profile');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[2].label', 'Profile');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[2].type', 'text');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[2].value', '');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[3].field', 'reportBy1');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[3].label', 'Report By');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[3].type', 'list');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[3].value', 'stat_date');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[4].field', 'reportBy2');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[4].label', 'Report By');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[4].type', 'list');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[4].value', '');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[5].field', 'reportBy3');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[5].label', 'Report By');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[5].type', 'list');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[5].value', '');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[6].field', 'reportBy4');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[6].label', 'Report By');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[6].type', 'list');
        $valuesFormExport[] = new \Oara\Curl\Parameter('reportView.input[6].value', '');
        $valuesFormExport[] = new \Oara\Curl\Parameter('export', "on");
        
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://portal.ladbrokespartners.com/portal/stats.jhtm', $valuesFormExport);
        $this->_client->post($urls);

        $valuesFormExport = array();
        $valuesFormExport[] = new \Oara\Curl\Parameter('intermediatoryDateFormat', "yy-mm-dd");
        $valuesFormExport[] = new \Oara\Curl\Parameter('userDisplayName', "Ami Spencer");
        $valuesFormExport[] = new \Oara\Curl\Parameter('currentLanguage', "en");
        $urls = array();
        $urls[] = new \Oara\Curl\Request('https://portal.ladbrokespartners.com/portal/exportToCSV.jhtm?', $valuesFormExport);
        $csv = $this->_client->get($urls);
        
        return array();
    }
}
