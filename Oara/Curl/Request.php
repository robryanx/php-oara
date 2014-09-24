<?php
/**
   The goal of the Open Affiliate Report Aggregator (OARA) is to develop a set 
   of PHP classes that can download affiliate reports from a number of affiliate networks, and store the data in a common format.
   
    Copyright (C) 2014  Carlos Morillo Merino
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.
    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
	and we should add some contact information
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
class Oara_Curl_Request {
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
	 * Constructor.
	 * @param $_url
	 * @param $_parameters
	 * @return Oara_Curl_Request
	 */
	public function __construct($url, array $parameters) {
		$this->_url = $url;
		$this->_parameters = $parameters;
	}
	/**
	 * Url Getter.
	 * @return string
	 */
	public function getUrl() {
		return $this->_url;
	}
	/**
	 * Parameter Getter.
	 * @return array
	 */
	public function getParameters() {
		return $this->_parameters;
	}
	/**
	 * Url Setter.
	 * @return none
	 */
	public function setUrl($url) {
		$this->_url = $url;
	}
	/**
	 * Parameter Setter.
	 * @return none
	 */
	public function setParameters($parameters) {
		$this->_parameters = $parameters;
	}
	/**
	 * Parameter Getter.
	 * @return array
	 */
	public function getParameter($index) {
		return $this->_parameters[$index];
	}
}
