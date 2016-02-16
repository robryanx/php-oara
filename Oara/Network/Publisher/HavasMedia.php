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
class HavasMedia extends \Oara\Network
{
    /**
     * Export client.
     * @var \Oara\Curl\Access
     */
    private $_client = null;
    /**
     * Merchants Export Parameters
     * @var array
     */
    private $_exportMerchantParameters = null;
    /**
     * Transaction Export Parameters
     * @var array
     */
    private $_exportTransactionParameters = null;
    /**
     * Overview Export Parameters
     * @var array
     */
    private $_exportOverviewParameters = null;
    /**
     * Date Format, it's different in some accounts
     * @var string
     */
    private $_dateFormat = null;
    /**
     *
     * Credentials
     * @var array
     */
    private $_credentials = null;

    /**
     * Constructor and Login
     * @param $tradeDoubler
     * @return Td_Export
     */
    public function login($credentials)
    {

        $this->_credentials = $credentials;

        self::login();

        $this->_exportMerchantParameters = array(new \Oara\Curl\Parameter('reportName', 'aAffiliateMyProgramsReport'),
            new \Oara\Curl\Parameter('tabMenuName', ''),
            new \Oara\Curl\Parameter('isPostBack', ''),
            new \Oara\Curl\Parameter('showAdvanced', 'true'),
            new \Oara\Curl\Parameter('showFavorite', 'false'),
            new \Oara\Curl\Parameter('run_as_organization_id', ''),
            new \Oara\Curl\Parameter('minRelativeIntervalStartTime', '0'),
            new \Oara\Curl\Parameter('maxIntervalSize', '0'),
            new \Oara\Curl\Parameter('interval', 'MONTHS'),
            new \Oara\Curl\Parameter('reportPrograms', ''),
            new \Oara\Curl\Parameter('reportTitleTextKey', 'REPORT3_SERVICE_REPORTS_AAFFILIATEMYPROGRAMSREPORT_TITLE'),
            new \Oara\Curl\Parameter('setColumns', 'true'),
            new \Oara\Curl\Parameter('latestDayToExecute', '0'),
            new \Oara\Curl\Parameter('affiliateId', ''),
            new \Oara\Curl\Parameter('includeWarningColumn', 'true'),
            new \Oara\Curl\Parameter('sortBy', 'orderDefault'),
            new \Oara\Curl\Parameter('autoCheckbox', 'columns'),
            new \Oara\Curl\Parameter('autoCheckbox', 'columns'),
            new \Oara\Curl\Parameter('columns', 'programId'),
            new \Oara\Curl\Parameter('autoCheckbox', 'columns'),
            new \Oara\Curl\Parameter('autoCheckbox', 'columns'),
            new \Oara\Curl\Parameter('autoCheckbox', 'columns'),
            new \Oara\Curl\Parameter('autoCheckbox', 'columns'),
            new \Oara\Curl\Parameter('autoCheckbox', 'columns'),
            new \Oara\Curl\Parameter('autoCheckbox', 'columns'),
            new \Oara\Curl\Parameter('autoCheckbox', 'columns'),
            new \Oara\Curl\Parameter('autoCheckbox', 'columns'),
            new \Oara\Curl\Parameter('autoCheckbox', 'columns'),
            new \Oara\Curl\Parameter('autoCheckbox', 'columns'),
            new \Oara\Curl\Parameter('columns', 'affiliateId'),
            new \Oara\Curl\Parameter('autoCheckbox', 'columns'),
            new \Oara\Curl\Parameter('columns', 'applicationDate'),
            new \Oara\Curl\Parameter('autoCheckbox', 'columns'),
            new \Oara\Curl\Parameter('columns', 'status'),
            new \Oara\Curl\Parameter('autoCheckbox', 'useMetricColumn'),
            new \Oara\Curl\Parameter('customKeyMetricCount', '0'),
            new \Oara\Curl\Parameter('metric1.name', ''),
            new \Oara\Curl\Parameter('metric1.midFactor', ''),
            new \Oara\Curl\Parameter('metric1.midOperator', '/'),
            new \Oara\Curl\Parameter('metric1.columnName1', 'programId'),
            new \Oara\Curl\Parameter('metric1.operator1', '/'),
            new \Oara\Curl\Parameter('metric1.columnName2', 'programId'),
            new \Oara\Curl\Parameter('metric1.lastOperator', '/'),
            new \Oara\Curl\Parameter('metric1.factor', ''),
            new \Oara\Curl\Parameter('metric1.summaryType', 'NONE'),
            new \Oara\Curl\Parameter('format', 'CSV'),
            new \Oara\Curl\Parameter('separator', ','),
            new \Oara\Curl\Parameter('dateType', '0'),
            new \Oara\Curl\Parameter('favoriteId', ''),
            new \Oara\Curl\Parameter('favoriteName', ''),
            new \Oara\Curl\Parameter('favoriteDescription', '')
        );

        $this->_exportTransactionParameters = array(new \Oara\Curl\Parameter('reportName', 'aAffiliateEventBreakdownReport'),
            new \Oara\Curl\Parameter('columns', 'programId'),
            new \Oara\Curl\Parameter('columns', 'timeOfVisit'),
            new \Oara\Curl\Parameter('columns', 'timeOfEvent'),
            new \Oara\Curl\Parameter('columns', 'timeInSession'),
            new \Oara\Curl\Parameter('columns', 'lastModified'),
            new \Oara\Curl\Parameter('columns', 'epi1'),
            new \Oara\Curl\Parameter('columns', 'eventName'),
            new \Oara\Curl\Parameter('columns', 'pendingStatus'),
            new \Oara\Curl\Parameter('columns', 'siteName'),
            new \Oara\Curl\Parameter('columns', 'graphicalElementName'),
            new \Oara\Curl\Parameter('columns', 'graphicalElementId'),
            new \Oara\Curl\Parameter('columns', 'productName'),
            new \Oara\Curl\Parameter('columns', 'productNrOf'),
            new \Oara\Curl\Parameter('columns', 'productValue'),
            new \Oara\Curl\Parameter('columns', 'affiliateCommission'),
            new \Oara\Curl\Parameter('columns', 'link'),
            new \Oara\Curl\Parameter('columns', 'leadNR'),
            new \Oara\Curl\Parameter('columns', 'orderNR'),
            new \Oara\Curl\Parameter('columns', 'pendingReason'),
            new \Oara\Curl\Parameter('columns', 'orderValue'),
            new \Oara\Curl\Parameter('isPostBack', ''),
            new \Oara\Curl\Parameter('metric1.lastOperator', '/'),
            new \Oara\Curl\Parameter('interval', ''),
            new \Oara\Curl\Parameter('favoriteDescription', ''),
            new \Oara\Curl\Parameter('event_id', '0'),
            new \Oara\Curl\Parameter('pending_status', '1'),
            new \Oara\Curl\Parameter('run_as_organization_id', ''),
            new \Oara\Curl\Parameter('minRelativeIntervalStartTime', '0'),
            new \Oara\Curl\Parameter('includeWarningColumn', 'true'),
            new \Oara\Curl\Parameter('metric1.summaryType', 'NONE'),
            new \Oara\Curl\Parameter('metric1.operator1', '/'),
            new \Oara\Curl\Parameter('latestDayToExecute', '0'),
            new \Oara\Curl\Parameter('showAdvanced', 'true'),
            new \Oara\Curl\Parameter('breakdownOption', '1'),
            new \Oara\Curl\Parameter('metric1.midFactor', ''),
            new \Oara\Curl\Parameter('reportTitleTextKey', 'REPORT3_SERVICE_REPORTS_AAFFILIATEEVENTBREAKDOWNREPORT_TITLE'),
            new \Oara\Curl\Parameter('setColumns', 'true'),
            new \Oara\Curl\Parameter('metric1.columnName1', 'orderValue'),
            new \Oara\Curl\Parameter('metric1.columnName2', 'orderValue'),
            new \Oara\Curl\Parameter('reportPrograms', ''),
            new \Oara\Curl\Parameter('metric1.midOperator', '/'),
            new \Oara\Curl\Parameter('dateSelectionType', '1'),
            new \Oara\Curl\Parameter('favoriteName', ''),
            new \Oara\Curl\Parameter('affiliateId', ''),
            new \Oara\Curl\Parameter('dateType', '1'),
            new \Oara\Curl\Parameter('period', 'custom_period'),
            new \Oara\Curl\Parameter('tabMenuName', ''),
            new \Oara\Curl\Parameter('maxIntervalSize', '0'),
            new \Oara\Curl\Parameter('favoriteId', ''),
            new \Oara\Curl\Parameter('sortBy', 'timeOfEvent'),
            new \Oara\Curl\Parameter('metric1.name', ''),
            new \Oara\Curl\Parameter('customKeyMetricCount', '0'),
            new \Oara\Curl\Parameter('metric1.factor', ''),
            new \Oara\Curl\Parameter('showFavorite', 'false'),
            new \Oara\Curl\Parameter('separator', ','),
            new \Oara\Curl\Parameter('format', 'CSV')
        );

        $this->_exportOverviewParameters = array(new \Oara\Curl\Parameter('reportName', 'aAffiliateProgramOverviewReport'),
            new \Oara\Curl\Parameter('tabMenuName', ''),
            new \Oara\Curl\Parameter('isPostBack', ''),
            new \Oara\Curl\Parameter('showAdvanced', 'true'),
            new \Oara\Curl\Parameter('showFavorite', 'false'),
            new \Oara\Curl\Parameter('run_as_organization_id', ''),
            new \Oara\Curl\Parameter('minRelativeIntervalStartTime', '0'),
            new \Oara\Curl\Parameter('maxIntervalSize', '12'),
            new \Oara\Curl\Parameter('interval', 'MONTHS'),
            new \Oara\Curl\Parameter('reportPrograms', ''),
            new \Oara\Curl\Parameter('reportTitleTextKey', 'REPORT3_SERVICE_REPORTS_AAFFILIATEPROGRAMOVERVIEWREPORT_TITLE'),
            new \Oara\Curl\Parameter('setColumns', 'true'),
            new \Oara\Curl\Parameter('latestDayToExecute', '0'),
            new \Oara\Curl\Parameter('programTypeId', ''),
            new \Oara\Curl\Parameter('includeWarningColumn', 'true'),
            new \Oara\Curl\Parameter('programId', ''),
            new \Oara\Curl\Parameter('period', 'custom_period'),
            new \Oara\Curl\Parameter('columns', 'programId'),
            new \Oara\Curl\Parameter('columns', 'impNrOf'),
            new \Oara\Curl\Parameter('columns', 'clickNrOf'),
            new \Oara\Curl\Parameter('autoCheckbox', 'columns'),
            new \Oara\Curl\Parameter('autoCheckbox', 'useMetricColumn'),
            new \Oara\Curl\Parameter('customKeyMetricCount', '0'),
            new \Oara\Curl\Parameter('metric1.name', ''),
            new \Oara\Curl\Parameter('metric1.midFactor', ''),
            new \Oara\Curl\Parameter('metric1.midOperator', '/'),
            new \Oara\Curl\Parameter('metric1.columnName1', 'programId'),
            new \Oara\Curl\Parameter('metric1.operator1', '/'),
            new \Oara\Curl\Parameter('metric1.columnName2', 'programId'),
            new \Oara\Curl\Parameter('metric1.lastOperator', '/'),
            new \Oara\Curl\Parameter('metric1.factor', ''),
            new \Oara\Curl\Parameter('metric1.summaryType', 'NONE'),
            new \Oara\Curl\Parameter('format', 'CSV'),
            new \Oara\Curl\Parameter('separator', ';'),
            new \Oara\Curl\Parameter('dateType', '1'),
            new \Oara\Curl\Parameter('favoriteId', ''),
            new \Oara\Curl\Parameter('favoriteName', ''),
            new \Oara\Curl\Parameter('favoriteDescription', '')
        );
    }

    /**
     * @return array
     */
    public function getNeededCredentials()
    {
        $credentials = array();

        $parameter = array();
        $parameter["user"]["description"] = "User Log in";
        $parameter["user"]["required"] = true;
        $credentials[] = $parameter;

        $parameter = array();
        $parameter["password"]["description"] = "Password to Log in";
        $parameter["password"]["required"] = true;
        $credentials[] = $parameter;

        return $credentials;
    }

    /**
     *
     * Login into the web interface
     */
    private function login()
    {
        $user = $this->_credentials['user'];
        $password = $this->_credentials['password'];
        $loginUrl = 'http://publisher.tradedoubler.com/pan/login';

        $valuesLogin = array(new \Oara\Curl\Parameter('j_username', $user),
            new \Oara\Curl\Parameter('j_password', $password)
        );

        $this->_client = new \Oara\Curl\Access($loginUrl, $valuesLogin, $this->_credentials);

    }

    /**
     * Check the connection
     */
    public function checkConnection()
    {
        $connection = false;

        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://publisher.tradedoubler.com/pan/aReport3Selection.action?reportName=aAffiliateProgramOverviewReport', array());
        $exportReport = $this->_client->get($urls);
        if (preg_match('/\(([a-zA-Z]{0,2}[\/\.][a-zA-Z]{0,2}[\/\.][a-zA-Z]{0,2})\)/', $exportReport[0], $match)) {
            $this->_dateFormat = $match[1];
        }

        if ($this->_dateFormat != null) {
            $connection = true;
        }
        return $connection;
    }

    /**
     * It returns the Merchant CVS report.
     * @return $exportReport
     */
    private function getExportMerchantReport($content)
    {
        $merchantReport = self::formatCsv($content);

        $exportData = str_getcsv($merchantReport, "\r\n");
        $merchantReportList = Array();
        $num = count($exportData);
        $websiteMap = array();
        for ($i = 3; $i < $num; $i++) {
            $merchantExportArray = str_getcsv($exportData[$i], ",");

            if ($merchantExportArray[2] != '' && $merchantExportArray[4] != '') {
                $merchantReportList[$merchantExportArray[4]] = $merchantExportArray[2];
                $websiteMap[$merchantExportArray[0]] = "";
            }

        }
        return $merchantReportList;
    }

    /**
     *
     * Format Csv
     * @param unknown_type $csv
     */
    private function formatCsv($csv)
    {
        preg_match_all("/\"([^\"]+?)\",/", $csv, $matches);
        foreach ($matches[1] as $match) {
            if (preg_match("/,/", $match)) {
                $rep = preg_replace("/,/", "", $match);
                $csv = str_replace($match, $rep, $csv);
                $match = $rep;
            }
            if (preg_match("/\n/", $match)) {
                $rep = preg_replace("/\n/", "", $match);
                $csv = str_replace($match, $rep, $csv);
            }
        }
        return $csv;
    }

    /**
     * It returns an array with the different merchants
     * @return array
     */
    private function getMerchantReportList()
    {
        $merchantReportList = Array();

        $valuesFormExport = $this->_exportMerchantParameters;
        $valuesFormExport[] = new \Oara\Curl\Parameter('programAffiliateStatusId', '3');
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://publisher.tradedoubler.com/pan/aReport3Internal.action?', $valuesFormExport);
        $exportReport = $this->_client->post($urls);
        $exportReport[0] = self::checkReportError($exportReport[0], $urls[0]);
        $merchantReportList = self::getExportMerchantReport($exportReport[0]);

        $valuesFormExport = $this->_exportMerchantParameters;
        $valuesFormExport[] = new \Oara\Curl\Parameter('programAffiliateStatusId', '4');
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://publisher.tradedoubler.com/pan/aReport3Internal.action?', $valuesFormExport);
        $exportReport = $this->_client->post($urls);
        $exportReport[0] = self::checkReportError($exportReport[0], $urls[0]);
        $merchantReportListAux = self::getExportMerchantReport($exportReport[0]);
        foreach ($merchantReportListAux as $key => $value) {
            $merchantReportList[$key] = $value;
        }

        return $merchantReportList;
    }

    /**
     * (non-PHPdoc)
     * @see library/Oara/Network/Base#getMerchantList()
     */
    public function getMerchantList()
    {
        $merchantReportList = self::getMerchantReportList();
        $merchants = Array();
        foreach ($merchantReportList as $key => $value) {
            $obj = Array();
            $obj['cid'] = $key;
            $obj['name'] = $value;
            $merchants[] = $obj;
        }

        return $merchants;
    }

    /**
     * (non-PHPdoc)
     * @see \Oara\Network::getTransactionList()
     */
    public function getTransactionList($merchantList = null, \DateTime $dStartDate = null, \DateTime $dEndDate = null)
    {
        $totalTransactions = Array();
        $filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));
        self::login();

        $valuesFormExport = \Oara\Utilities::cloneArray($this->_exportTransactionParameters);
        $valuesFormExport[] = new \Oara\Curl\Parameter('startDate', self::formatDate($dStartDate));
        $valuesFormExport[] = new \Oara\Curl\Parameter('endDate', self::formatDate($dEndDate));
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://publisher.tradedoubler.com/pan/aReport3Internal.action?', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
        $exportReport[0] = self::checkReportError($exportReport[0], $urls[0]);
        $exportData = str_getcsv($exportReport[0], "\r\n");
        $num = count($exportData);
        for ($i = 2; $i < $num - 1; $i++) {

            $transactionExportArray = str_getcsv($exportData[$i], ",");

            if (!isset($transactionExportArray[2])) {
                throw new Exception('Problem getting transaction\n\n');
            }

            if ($transactionExportArray[0] !== '' && change_it_for_isset!((int)$transactionExportArray[2], $merchantList)) {

                $transaction = Array();
                $transaction['merchantId'] = $transactionExportArray[2];
                $transactionDate = self::toDate($transactionExportArray[4]);
                $transaction['date'] = $transactionDate->format!("yyyy-MM-dd HH:mm:ss");
                if ($transactionExportArray[8] != '') {
                    $transaction['unique_id'] = $transactionExportArray[8];
                } else
                    if ($transactionExportArray[7] != '') {
                        $transaction['unique_id'] = $transactionExportArray[7];
                    } else {
                        throw new Exception("No Identifier");
                    }
                if ($transactionExportArray[9] != '') {
                    $transaction['custom_id'] = $transactionExportArray[9];
                }

                if ($transactionExportArray[11] == 'A') {
                    $transaction['status'] = \Oara\Utilities::STATUS_CONFIRMED;
                } else
                    if ($transactionExportArray[11] == 'P') {
                        $transaction['status'] = \Oara\Utilities::STATUS_PENDING;
                    } else
                        if ($transactionExportArray[11] == 'D') {
                            $transaction['status'] = \Oara\Utilities::STATUS_DECLINED;
                        }

                if ($transactionExportArray[19] != '') {
                    $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[19]);
                } else {
                    $transaction['amount'] = \Oara\Utilities::parseDouble($transactionExportArray[20]);
                }

                $transaction['commission'] = \Oara\Utilities::parseDouble($transactionExportArray[20]);
                $totalTransactions[] = $transaction;
            }
        }
        return $totalTransactions;
    }

    public function checkReportError($content, $request, $try = 0)
    {

        if (preg_match('/\/report\/published\/aAffiliateEventBreakdownReport/', $content, $matches)) {
            //report too big, we have to download it and read it
            if (preg_match('/(\/report\/published\/(aAffiliateEventBreakdownReport(.*))\.zip)/', $content, $matches)) {

                $file = "http://publisher.tradedoubler.com" . $matches[0];
                $newfile = realpath(dirname(COOKIES_BASE_DIR)) . '/pdf/' . $matches[2] . '.zip';

                if (!copy($file, $newfile)) {
                    throw new Exception('Failing copying the zip file \n\n');
                }
                $zip = new ZipArchive();
                if ($zip->open($newfile, ZIPARCHIVE::CREATE) !== TRUE) {
                    throw new Exception('Cannot open zip file \n\n');
                }
                $zip->extractTo(realpath(dirname(COOKIES_BASE_DIR)) . '/pdf/');
                $zip->close();

                $unzipFilePath = realpath(dirname(COOKIES_BASE_DIR)) . '/pdf/' . $matches[2];
                $fileContent = file_get_contents($unzipFilePath);
                unlink($newfile);
                unlink($unzipFilePath);
                return $fileContent;
            }

            throw new Exception('Report too big \n\n');

        } else
            if (preg_match("/ error/", $content, $matches)) {
                $urls = array();
                $urls[] = $request;
                $exportReport = $this->_client->get($urls);
                $try++;
                if ($try < 5) {
                    return self::checkReportError($exportReport[0], $request, $try);
                } else {
                    throw new Exception('Problem checking report\n\n');
                }

            } else {
                return $content;
            }

    }

    /**
     * (non-PHPdoc)
     * @see Oara/Network/Base#getPaymentHistory()
     */
    public function getPaymentHistory()
    {
        $filter = new Zend_Filter_LocalizedToNormalized(array('precision' => 2));
        $paymentHistory = array();

        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://publisher.tradedoubler.com/pan/reportSelection/Payment?', array());
        $exportReport = $this->_client->get($urls);
        /*** load the html into the object ***/
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->validateOnParse = true;
        $doc->loadHTML($exportReport[0]);
        $selectList = $doc->getElementsByTagName('select');
        $paymentSelect = null;
        if ($selectList->length > 0) {
            // looking for the payments select
            $it = 0;
            while ($it < $selectList->length) {
                $selectName = $selectList->item($it)->attributes->getNamedItem('name')->nodeValue;
                if ($selectName == 'payment_id') {
                    $paymentSelect = $selectList->item($it);
                    break;
                }
                $it++;
            }
            if ($paymentSelect != null) {
                $paymentLines = $paymentSelect->childNodes;
                for ($i = 0; $i < $paymentLines->length; $i++) {
                    $pid = $paymentLines->item($i)->attributes->getNamedItem("value")->nodeValue;
                    if (is_numeric($pid)) {
                        $obj = array();

                        $paymentLine = $paymentLines->item($i)->nodeValue;
                        $value = preg_replace('/[^0-9\.,]/', "", substr($paymentLine, 10));

                        $date = self::toDate(substr($paymentLine, 0, 10));

                        $obj['date'] = $date->format!("yyyy-MM-dd HH:mm:ss");
                        $obj['pid'] = $pid;
                        $obj['method'] = 'BACS';
                        $obj['value'] = \Oara\Utilities::parseDouble($value);

                        $paymentHistory[] = $obj;
                    }
                }
            }
        }
        return $paymentHistory;
    }

    /**
     *
     * It returns the transactions for a payment
     * @param int $paymentId
     */
    public function paymentTransactions($paymentId, $merchantList, $startDate)
    {
        $transactionList = array();

        $urls = array();
        $valuesFormExport = array();
        $valuesFormExport[] = new \Oara\Curl\Parameter('popup', 'true');
        $valuesFormExport[] = new \Oara\Curl\Parameter('payment_id', $paymentId);
        $urls[] = new \Oara\Curl\Request('http://publisher.tradedoubler.com/pan/reports/Payment.html?', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
        $dom = new Zend_Dom_Query($exportReport[0]);
        $results = $dom->query('a');

        $urls = array();
        foreach ($results as $result) {
            $url = $result->getAttribute('href');
            $urls[] = new \Oara\Curl\Request("http://publisher.tradedoubler.com" . $url . "&format=CSV", array());
        }
        $exportReportList = $this->_client->get($urls);
        foreach ($exportReportList as $exportReport) {
            $exportReportData = str_getcsv($exportReport, "\r\n");
            $num = count($exportReportData);
            for ($i = 2; $i < $num - 1; $i++) {
                $transactionArray = str_getcsv($exportReportData[$i], ";");
                if ($transactionArray[8] != '') {
                    $transactionList[] = $transactionArray[8];
                } else
                    if ($transactionArray[7] != '') {
                        $transactionList[] = $transactionArray[7];
                    } else {
                        throw new Exception("No Identifier");
                    }
            }
        }

        return $transactionList;
    }

    /**
     *
     * Add Dates in a certain format to the criteriaList
     * @param array $criteriaList
     * @param array $dateArray
     * @throws Exception
     */
    private function formatDate($date)
    {
        $dateString = "";
        if ($this->_dateFormat == 'dd/MM/yy') {
            $dateString = $date->format!('dd/MM/yyyy');
        } else
            if ($this->_dateFormat == 'M/d/yy') {
                $dateString = $date->format!('M/d/yy');
            } else
                if ($this->_dateFormat == 'd/MM/yy') {
                    $dateString = $date->format!('d/MM/yy');
                } else
                    if ($this->_dateFormat == 'tt.MM.uu') {
                        $dateString = $date->format!('dd.MM.yy');
                    } else
                        if ($this->_dateFormat == 'jj-MM-aa') {
                            $dateString = $date->format!('dd-MM-yy');
                        } else
                            if ($this->_dateFormat == 'jj/MM/aa') {
                                $dateString = $date->format!('dd/MM/yy');
                            } else
                                if ($this->_dateFormat == 'dd.MM.yy') {
                                    $dateString = $date->format!('dd.MM.yy');
                                } else {
                                    throw new Exception("\n Date Format not supported " . $this->_dateFormat . "\n");
                                }
        return $dateString;
    }

    /**
     *
     * Date String to Object
     * @param unknown_type $dateString
     * @throws Exception
     */
    private function toDate($dateString)
    {
        $transactionDate = null;
        if ($this->_dateFormat == 'dd/MM/yy') {
            $transactionDate = new \DateTime(trim($dateString), "dd/MM/yy HH:mm:ss");
        } else
            if ($this->_dateFormat == 'M/d/yy') {
                $transactionDate = new \DateTime(trim($dateString), "M/d/yy HH:mm:ss");
            } else
                if ($this->_dateFormat == 'd/MM/yy') {
                    $transactionDate = new \DateTime(trim($dateString), "d/MM/yy HH:mm:ss");
                } else
                    if ($this->_dateFormat == 'tt.MM.uu') {
                        $transactionDate = new \DateTime(trim($dateString), "dd.MM.yy HH:mm:ss");
                    } else
                        if ($this->_dateFormat == 'jj-MM-aa') {
                            $transactionDate = new \DateTime(trim($dateString), "dd-MM-yy HH:mm:ss");
                        } else
                            if ($this->_dateFormat == 'jj/MM/aa') {
                                $transactionDate = new \DateTime(trim($dateString), "dd/MM/yy HH:mm:ss");
                            } else
                                if ($this->_dateFormat == 'dd.MM.yy') {
                                    $transactionDate = new \DateTime(trim($dateString), "dd.MM.yy HH:mm:ss");
                                } else {
                                    throw new Exception("\n Date Format not supported " . $this->_dateFormat . "\n");
                                }
        return $transactionDate;
    }
}
