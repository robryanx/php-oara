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
 * Parameter Class
 *
 * @author     Carlos Morillo Merino
 * @category   Oara_Curl
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 *
 */
class Oara_Curl_Parameter {
	/**
	 * Parameter's key.
	 * @var string
	 */
	private $_key;
	/**
	 * Parameter's value
	 * @var string
	 */
	private $_value;
	/**
	 * Constructor.
	 * @param $key
	 * @param $value
	 * @return Oara_Curl_Parameter
	 */
	public function __construct($key, $value) {
		$this->_key = $key;
		$this->_value = $value;
	}
	/**
	 * key's Getter.
	 * @return unknown_type
	 */
	public function getKey() {
		return $this->_key;
	}
	/**
	 * value's Getter.
	 * @return unknown_type
	 */
	public function getValue() {
		return $this->_value;
	}
	/**
	 * key's Setter.
	 * @return unknown_type
	 */
	public function setKey($key) {
		$this->_key = $key;
	}
	/**
	 * value's Setter.
	 * @return unknown_type
	 */
	public function setValue($value) {
		$this->_value = $value;
	}
}
