<?php
namespace Oara\Curl;
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
 * Request Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Curl
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Request
{
    /**
     * Parameter's key.
     * @var string
     */
    private $_url;
    /**
     * Parameter's value
     * @var string
     */
    private $_parameters;

    /**
     * Request constructor.
     * @param $url
     * @param array $parameters
     */
    public function __construct($url, array $parameters)
    {
        $this->_url = $url;
        $this->_parameters = $parameters;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * @param $url
     */
    public function setUrl($url)
    {
        $this->_url = $url;
    }

    /**
     * @return array|string
     */
    public function getParameters()
    {
        return $this->_parameters;
    }

    /**
     * @param $parameters
     */
    public function setParameters($parameters)
    {
        $this->_parameters = $parameters;
    }

    /**
     * @param $index
     * @return mixed
     */
    public function getParameter($index)
    {
        return $this->_parameters[$index];
    }
}
