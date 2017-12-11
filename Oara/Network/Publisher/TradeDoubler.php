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
class TradeDoubler extends \Oara\Network
{

    protected $_sitesAllowed = array();
    private $_client = null;
    private $_dateFormat = null;

    public function login($credentials)
    {

        $this->_credentials = $credentials;
        $this->_client = new \Oara\Curl\Access($credentials);

        $user = $this->_credentials['user'];
        $password = $this->_credentials['password'];
        $loginUrl = 'http://publisher.tradedoubler.com/pan/login';

        $valuesLogin = array(new \Oara\Curl\Parameter('j_username', $user),
            new \Oara\Curl\Parameter('j_password', $password)
        );

        $urls = array();
        $urls[] = new \Oara\Curl\Request($loginUrl, $valuesLogin);
        $this->_client->post($urls);
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
        $connection = false;

        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://publisher.tradedoubler.com/pan/aReport3Selection.action?reportName=aAffiliateProgramOverviewReport', array());
        $exportReport = $this->_client->get($urls);

        if (\preg_match('/\(([a-zA-Z]{0,4}[\/\.-][a-zA-Z]{0,4}[\/\.-][a-zA-Z]{0,4})\)/', $exportReport[0], $match)) {
            $this->_dateFormat = $match[1];
        }

        if ($this->_dateFormat != null) {
            $connection = true;
        }
        return $connection;
    }

    /**
     * @return array
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
     * It returns an array with the different merchants
     * @return array
     */
    private function getMerchantReportList()
    {

        $valuesFormExport = array(new \Oara\Curl\Parameter('reportName', 'aAffiliateMyProgramsReport'),
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
            new \Oara\Curl\Parameter('favoriteDescription', ''),
            new \Oara\Curl\Parameter('programAffiliateStatusId', '3')
        );
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://publisher.tradedoubler.com/pan/aReport3Internal.action?', $valuesFormExport);
        $exportReport = $this->_client->post($urls);
        $exportReport[0] = self::checkReportError($exportReport[0], $urls[0]);
        $merchantReportList = self::getExportMerchantReport($exportReport[0]);

        $valuesFormExport = array(new \Oara\Curl\Parameter('reportName', 'aAffiliateMyProgramsReport'),
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
            new \Oara\Curl\Parameter('favoriteDescription', ''),
            new \Oara\Curl\Parameter('programAffiliateStatusId', '4')
        );
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

    public function checkReportError($content, $request, $try = 0)
    {

        if (\preg_match('/\/report\/published\/aAffiliateEventBreakdownReport/', $content, $matches)) {
            //report too big, we have to download it and read it
            if (\preg_match('/(\/report\/published\/(aAffiliateEventBreakdownReport(.*))\.zip)/', $content, $matches)) {

                $file = "http://publisher.tradedoubler.com" . $matches[0];
                $newfile = \realpath(\dirname(COOKIES_BASE_DIR)) . '/pdf/' . $matches[2] . '.zip';

                if (!\copy($file, $newfile)) {
                    throw new \Exception('Failing copying the zip file \n\n');
                }
                $zip = new \ZipArchive();
                if ($zip->open($newfile, \ZIPARCHIVE::CREATE) !== TRUE) {
                    throw new \Exception('Cannot open zip file \n\n');
                }
                $zip->extractTo(\realpath(\dirname(COOKIES_BASE_DIR)) . '/pdf/');
                $zip->close();

                $unzipFilePath = \realpath(\dirname(COOKIES_BASE_DIR)) . '/pdf/' . $matches[2];
                $fileContent = \file_get_contents($unzipFilePath);
                \unlink($newfile);
                \unlink($unzipFilePath);
                return $fileContent;
            }

            throw new \Exception('Report too big \n\n');

        } else
            if (\preg_match("/ error/", $content, $matches)) {
                $urls = array();
                $urls[] = $request;
                $exportReport = $this->_client->get($urls);
                $try++;
                if ($try < 5) {
                    return self::checkReportError($exportReport[0], $request, $try);
                } else {
                    throw new \Exception('Problem checking report\n\n');
                }

            } else {
                return $content;
            }

    }

    /**
     * @param $content
     * @return array
     */
    private function getExportMerchantReport($content)
    {

        $merchantReport = self::formatCsv($content);

        $exportData = \str_getcsv($merchantReport, "\r\n");
        $merchantReportList = Array();
        $num = \count($exportData);
        $websiteMap = array();
        for ($i = 3; $i < $num; $i++) {
            $merchantExportArray = \str_getcsv($exportData[$i], ",");

            if ($merchantExportArray[2] != '' && $merchantExportArray[4] != '') {
                $merchantReportList[$merchantExportArray[4]] = $merchantExportArray[2];
                $websiteMap[$merchantExportArray[0]] = "";
            }

        }
        return $merchantReportList;
    }

    /**
     * @param $csv
     * @return mixed
     */
    private function formatCsv($csv)
    {
        \preg_match_all("/\"([^\"]+?)\",/", $csv, $matches);
        foreach ($matches[1] as $match) {
            if (\preg_match("/,/", $match)) {
                $rep = \preg_replace("/,/", "", $match);
                $csv = \str_replace($match, $rep, $csv);
                $match = $rep;
            }
            if (\preg_match("/\n/", $match)) {
                $rep = \preg_replace("/\n/", "", $match);
                $csv = \str_replace($match, $rep, $csv);
            }
        }
        return $csv;
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

        $totalTransactions = Array();
        $valuesFormExport = array(new \Oara\Curl\Parameter('reportName', 'aAffiliateEventBreakdownReport'),
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
        $valuesFormExport[] = new \Oara\Curl\Parameter('startDate', self::formatDate($dStartDate));
        $valuesFormExport[] = new \Oara\Curl\Parameter('endDate', self::formatDate($dEndDate));
        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://publisher.tradedoubler.com/pan/aReport3Internal.action?', $valuesFormExport);
        $exportReport = $this->_client->get($urls);
        $exportReport[0] = self::checkReportError($exportReport[0], $urls[0]);
        $exportData = \str_getcsv($exportReport[0], "\r\n");
        $num = \count($exportData);

        for ($i = 2; $i < $num - 1; $i++) {

            $transactionExportArray = \str_getcsv($exportData[$i], ",");
            if (\count($this->_sitesAllowed) == 0 || \in_array($transactionExportArray[13], $this->_sitesAllowed)) {


                if (trim($transactionExportArray[0]) && trim($transactionExportArray[4]) && (is_null($merchantList) || isset($merchantIdList[(int)$transactionExportArray[2]]))) {

                    $transaction = Array();
                    $transaction['merchantId'] = $transactionExportArray[2];
                    $transactionDate = self::toDate($transactionExportArray[4]);
                    $transaction['date'] = $transactionDate->format("Y-m-d H:i:s");
                    if ($transactionExportArray[8] != '') {
                        $transaction['unique_id'] = \substr($transactionExportArray[8], 0, 200);
                    } else if ($transactionExportArray[7] != '') {
                        $transaction['unique_id'] = \substr($transactionExportArray[7], 0, 200);
                    } else if ($transactionExportArray[9] != '') { // Could be a telesale, in which case let's use the custom ID
                        $transaction['unique_id'] = \substr($transactionExportArray[9], 0, 200);
                    }

                    // If we still don't have a unique id, don't just error out
                    if (empty($transaction['unique_id'])) {
                        continue;
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
        }
        return $totalTransactions;
    }

    /**
     * @param $date
     * @return string
     * @throws Exception
     */
    private function formatDate($date)
    {

        switch ($this->_dateFormat) {
            case 'dd/MM/yy':
                $dateString = $date->format('d/m/Y');
                break;
            case 'M/d/yy':
                $dateString = $date->format('n/j/y');
                break;
            case 'd/MM/yy':
                $dateString = $date->format('j/m/y');
                break;
            case 'tt.MM.uu':
                $dateString = $date->format('d.m.y');
                break;
            case 'jj-MM-aa':
                $dateString = $date->format('d-m-y');
                break;
            case 'jj/MM/aa':
                $dateString = $date->format('d/m/y');
                break;
            case 'dd.MM.yy':
                $dateString = $date->format('d.m.y');
                break;
            case 'yy-MM-dd':
                $dateString = $date->format('y-m-d');
                break;
            case 'd-M-yy':
                $dateString = $date->format('j-n-y');
                break;
            case 'yyyy/MM/dd':
                $dateString = $date->format('Y/m/d');
                break;
            case 'yyyy-MM-dd':
                $dateString = $date->format('Y-m-d');
                break;
            case 'j/MM/aa':
                $dateString = $date->format('j/m/y');
                break;
            default:
                throw new \Exception("\n Date Format not supported " . $this->_dateFormat . "\n");
                break;
        }

        return $dateString;
    }

    /**
     * @param $dateString
     * @return \DateTime|null
     * @throws Exception
     */
    private function toDate($dateString)
    {
        $transactionDate = null;
        $dateString = \trim($dateString);
        $dateParts = explode(' ', $dateString);
        $dateString = $dateParts[0] . ' ' . $dateParts[1];
        switch ($this->_dateFormat) {
            case 'dd/MM/yy':
                $dateString = \str_replace(".", ":", $dateString);
                $format = "d/m/y H:i:s";
                break;
            case 'M/d/yy':
                $format = "n/j/y H:i:s";
                break;
            case 'd/MM/yy':
                $format = "j/m/y H:i:s";
                break;
            case 'tt.MM.uu':
                $format = "d.m.y H:i:s";
                break;
            case 'jj-MM-aa':
                $format = "d-m-y H:i:s";
                break;
            case 'jj/MM/aa':
                $format = "d/m/y H:i:s";
                break;
            case 'dd.MM.yy':
                $format = "d.m.y H:i:s";
                break;
            case 'yy-MM-dd':
                $format = "y-m-d H:i:s";
                break;
            case 'd-M-yy':
                $format = "j-n-y H:i:s";
                break;
            case 'yyyy/MM/dd':
                $format = "Y/m/d H:i:s";
                break;
            case 'yyyy-MM-dd':
                $format = "Y-m-d H:i:s";
                break;
            case 'j/MM/aa':
                $format = "j/m/y H:i:s";
                break;
            default:
                throw new \Exception("\n Date Format not supported " . $this->_dateFormat . "\n");
                break;
        }

        $transactionDate = \DateTime::createFromFormat($format, $dateString);
        return $transactionDate;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getPaymentHistory()
    {
        $paymentHistory = array();

        $urls = array();
        $urls[] = new \Oara\Curl\Request('http://publisher.tradedoubler.com/pan/reportSelection/Payment?', array());
        $exportReport = $this->_client->get($urls);
        /*** load the html into the object ***/
        $doc = new \DOMDocument();
        \libxml_use_internal_errors(true);
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
                    if (\is_numeric($pid)) {
                        $obj = array();

                        $paymentLine = $paymentLines->item($i)->nodeValue;
                        $value = \preg_replace('/[^0-9\.,]/', "", \substr($paymentLine, 10));

                        $paymentParts = \explode(" ",$paymentLine);
                        $date = self::toDate($paymentParts[0]." 00:00:00");

                        $obj['date'] = $date->format("Y-m-d H:i:s");
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
     * @param $paymentId
     * @return array
     * @throws \Exception
     */
    public function paymentTransactions($paymentId)
    {
        $transactionList = array();

        $urls = array();
        $valuesFormExport = array();
        $valuesFormExport[] = new \Oara\Curl\Parameter('popup', 'true');
        $valuesFormExport[] = new \Oara\Curl\Parameter('payment_id', $paymentId);
        $urls[] = new \Oara\Curl\Request('http://publisher.tradedoubler.com/pan/reports/Payment.html?', $valuesFormExport);
        $exportReport = $this->_client->get($urls);


        $doc = new \DOMDocument();
        @$doc->loadHTML(\Oara\Utilities::DOMinnerHTML($exportReport[0]));
        $xpath = new \DOMXPath($doc);
        $results = $xpath->query('//a');

        $urls = array();
        foreach ($results as $result) {
            $url = $result->getAttribute('href');
            $urls[] = new \Oara\Curl\Request("http://publisher.tradedoubler.com" . $url . "&format=CSV", array());
        }
        $exportReportList = $this->_client->get($urls);
        foreach ($exportReportList as $exportReport) {
            $exportReportData = \str_getcsv($exportReport, "\r\n");
            $num = \count($exportReportData);
            for ($i = 2; $i < $num - 1; $i++) {
                $transactionArray = \str_getcsv($exportReportData[$i], ";");
                if ($transactionArray[8] != '') {
                    $transactionList[] = $transactionArray[8];
                } else
                    if ($transactionArray[7] != '') {
                        $transactionList[] = $transactionArray[7];
                    } else {
                        throw new \Exception("No Identifier");
                    }
            }
        }

        return $transactionList;
    }
}
