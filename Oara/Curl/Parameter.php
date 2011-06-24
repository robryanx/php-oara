<?php
/**
 * Parameter Class  
 * 
 * @author     Carlos Morillo Merino
 * @category   Oara_Curl
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 * 
 */
class Oara_Curl_Parameter{
	/**
	 * Parameter큦 key.
	 * @var string
	 */
	private $_key;
	/**
     * Parameter큦 value
     * @var string
     */
	private $_value;
	/**
	 * Constructor.
	 * @param $key
	 * @param $value
	 * @return Oara_Curl_Parameter
	 */
	public function __construct($key, $value){
		$this->_key = $key;
		$this->_value = $value;
	}
	/**
	 * key큦 Getter.
	 * @return unknown_type
	 */
	public function getKey(){
		return $this->_key;
	}
	/**
     * value큦 Getter.
     * @return unknown_type
     */
	public function getValue(){
		return $this->_value;
	}
	/**
     * key큦 Setter.
     * @return unknown_type
     */
	public function setKey($key){
		$this->_key = $key;
	}
	/**
     * value큦 Setter.
     * @return unknown_type
     */
	public function setValue($value){
		$this->_value = $value;
	}
}