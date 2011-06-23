<?php
/**
 * Request Class  
 * 
 * @author     Carlos Morillo Merino
 * @category   Oara_Curl
 * @copyright  Fubra Limited
 * @version    Release: 01.00
 * 
 */
class Oara_Curl_Request{
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
    public function __construct($url, array $parameters){
        $this->_url = $url;
        $this->_parameters = $parameters;
    }
    /**
     * Url Getter.
     * @return string
     */
    public function getUrl(){
        return $this->_url;
    }
    /**
     * Parameter Getter.
     * @return array
     */
    public function getParameters(){
        return $this->_parameters;
    }
    /**
     * Url Setter.
     * @return none
     */
    public function setUrl($url){
        $this->_url = $url;
    }
    /**
     * Parameter Setter.
     * @return none
     */
    public function setParameters($parameters){
        $this->_parameters = $parameters;
    }
	/**
     * Parameter Getter.
     * @return array
     */
    public function getParameter($index){
        return $this->_parameters[$index];
    }
}