<?php
/**
 *   @copyright Copyright (c) 2008-2009 Quality Unit s.r.o.
 *   @author Quality Unit
 *   @package PapApi
 *   @since Version 1.0.0
 *   
 *   Licensed under the Quality Unit, s.r.o. Dual License Agreement,
 *   Version 1.0 (the "License"); you may not use this file except in compliance
 *   with the License. You may obtain a copy of the License at
 *   http://www.qualityunit.com/licenses/gpf
 *   Generated on: 2016-05-12 02:51:36
 *   PAP version: 5.5.2.2, GPF version: 1.3.36.0
 *   
 */

@ini_set('session.gc_maxlifetime', 28800);
@ini_set('session.cookie_path', '/');
@ini_set('session.use_cookies', true);
@ini_set('magic_quotes_runtime', false);
@ini_set('session.use_trans_sid', false);
@ini_set('zend.ze1_compatibility_mode', false);

if (!class_exists('Gpf', false)) {
    class Gpf {
        const YES = 'Y';
        const NO = 'N';
    }
}

if (!class_exists('Gpf_Object', false)) {		
    class Gpf_Object {
        protected function createDatabase() {
            return Gpf_DbEngine_Database::getDatabase();
        }
    
        public function _($message) {
            return $message;
        }
    
        public function _localize($message) {
            return $message;
        }
    
        public function _sys($message) {
            return $message;
        }
    }
}

if (!interface_exists('Gpf_Rpc_Serializable', false)) {
  interface Gpf_Rpc_Serializable {
  
      public function toObject();
  
      public function toText();
  }

} //end Gpf_Rpc_Serializable

if (!interface_exists('Gpf_Rpc_DataEncoder', false)) {
  interface Gpf_Rpc_DataEncoder {
      function encodeResponse(Gpf_Rpc_Serializable $response);
  }
  
  

} //end Gpf_Rpc_DataEncoder

if (!interface_exists('Gpf_Rpc_DataDecoder', false)) {
  interface Gpf_Rpc_DataDecoder {
      /**
       * @param string $str
       * @return StdClass
       */
      function decode($str);
  }
  
  

} //end Gpf_Rpc_DataDecoder

if (!class_exists('Gpf_Rpc_Array', false)) {
  class Gpf_Rpc_Array extends Gpf_Object implements Gpf_Rpc_Serializable, IteratorAggregate {
  
  	private $array;
  
  	function __construct(array $array = null){
  		if($array === null){
  			$this->array = array();
  		}else{
  			$this->array = $array;
  		}
  	}
  
  	public function add($response) {
  		if(is_scalar($response) || $response instanceof Gpf_Rpc_Serializable) {
  			$this->array[] = $response;
  			return;
  		}
  		throw new Gpf_Exception("Value of type " . gettype($response) . " is not scalar or Gpf_Rpc_Serializable");
  	}
  
  	public function toObject() {
  		$array = array();
  		foreach ($this->array as $response) {
  			if($response instanceof Gpf_Rpc_Serializable) {
  				$array[] = $response->toObject();
  			} else {
  				$array[] = $response;
  			}
  		}
  		return $array;
  	}
  
  	public function toText() {
  		return var_dump($this->array);
  	}
  
  	public function getCount() {
  		return count($this->array);
  	}
  
  	public function get($index) {
  		return $this->array[$index];
  	}
  
  	/**
  	 *
  	 * @return ArrayIterator
  	 */
  	public function getIterator() {
  		return new ArrayIterator($this->array);
  	}
  }

} //end Gpf_Rpc_Array

if (!class_exists('Gpf_Rpc_Server', false)) {
  class Gpf_Rpc_Server extends Gpf_Object {
      const REQUESTS = 'requests';
      const REQUESTS_SHORT = 'R';
      const RUN_METHOD = 'run';
      const FORM_REQUEST = 'FormRequest';
      const FORM_RESPONSE = 'FormResponse';
      const BODY_DATA_NAME = 'D';
  
  
      const HANDLER_FORM = 'Y';
      const HANDLER_JASON = 'N';
      const HANDLER_WINDOW_NAME = 'W';
  
      /**
       * @var Gpf_Rpc_DataEncoder
       */
      private $dataEncoder;
      /**
       * @var Gpf_Rpc_DataDecoder
       */
      private $dataDecoder;
  
      public function __construct() {
      }
  
      private function initDatabaseLogger() {
          $logger = Gpf_Log_Logger::getInstance();
  
          if(!$logger->checkLoggerTypeExists(Gpf_Log_LoggerDatabase::TYPE)) {
              $logger->setGroup(Gpf_Common_String::generateId(10));
              $logLevel = Gpf_Settings::get(Gpf_Settings_Gpf::LOG_LEVEL_SETTING_NAME);
              $logger->add(Gpf_Log_LoggerDatabase::TYPE, $logLevel);
          }
      }
  
      /**
       * Return response to standard output
       */
      public function executeAndEcho($request = '') {
          $response = $this->encodeResponse($this->execute($request));
          Gpf_ModuleBase::startGzip();
          echo $response;
          Gpf_ModuleBase::flushGzip();
      }
  
      /**
       * @return Gpf_Rpc_Serializable
       */
      public function execute($request = '') {
          try {
              if(isset($_REQUEST[self::BODY_DATA_NAME])) {
                  $request = $this->parseRequestDataFromPost($_REQUEST[self::BODY_DATA_NAME]);
              }
              if($this->isStandardRequestUsed($_REQUEST)) {
                  $request = $this->setStandardRequest();
              }
  
              $this->setDecoder($request);
              $params = new Gpf_Rpc_Params($this->decodeRequest($request));
              $this->setEncoder($params);
              $response = $this->executeRequest($params);
          } catch (Exception $e) {
              return new Gpf_Rpc_ExceptionResponse($e);
          }
          if (!$this->isFormRequest($request)) {
              Gpf_Http::setHeader('Content-Type', 'application/json; charset=utf-8');
          }
          return $response;
      }
  
      private function parseRequestDataFromPost($data) {
          if(get_magic_quotes_gpc()) {
              return stripslashes($data);
          }
          return $data;
      }
  
      /**
       *
       * @param unknown_type $requestObj
       * @return Gpf_Rpc_Serializable
       */
      private function executeRequest(Gpf_Rpc_Params $params) {
          try {
              Gpf_Db_LoginHistory::logRequest();
              return $this->callServiceMethod($params);
          } catch (Gpf_Rpc_SessionExpiredException $e) {
              return $e;
          } catch (Exception $e) {
              return new Gpf_Rpc_ExceptionResponse($e);
          }
      }
  
      protected function callServiceMethod(Gpf_Rpc_Params $params) {
          $method = new Gpf_Rpc_ServiceMethod($params);
          return $method->invoke($params);
      }
  
      /**
       * Compute correct handler type for server response
       *
       * @param array $requestData
       * @param string $type
       * @return string
       */
      private function getEncoderHandlerType($requestData) {
          if ($this->isFormHandler($requestData, self::FORM_RESPONSE, self::HANDLER_FORM)) {
              return self::HANDLER_FORM;
          }
          if ($this->isFormHandler($requestData, self::FORM_RESPONSE, self::HANDLER_WINDOW_NAME)) {
              return self::HANDLER_WINDOW_NAME;
          }
          return self::HANDLER_JASON;
      }
  
  
      private function isFormHandler($requestData, $type, $handler) {
          return (isset($_REQUEST[$type]) && $_REQUEST[$type] == $handler) ||
          (isset($requestData) && isset($requestData[$type]) && $requestData[$type] == $handler);
      }
  
      private function decodeRequest($requestData) {
          return $this->dataDecoder->decode($requestData);
      }
  
      private function isStandardRequestUsed($requestArray) {
          return is_array($requestArray) && array_key_exists(Gpf_Rpc_Params::CLASS_NAME, $requestArray);
      }
  
      private function setStandardRequest() {
          return array_merge($_POST, $_GET);
      }
  
      private function isFormRequest($request) {
          return $this->isFormHandler($request, self::FORM_REQUEST, self::HANDLER_FORM);
      }
  
      private function encodeResponse(Gpf_Rpc_Serializable $response) {
          return $this->dataEncoder->encodeResponse($response);
      }
  
  
      private function setDecoder($request) {
          if ($this->isFormRequest($request)) {
              $this->dataDecoder = new Gpf_Rpc_FormHandler();
          } else {
              $this->dataDecoder = new Gpf_Rpc_Json();
          }
      }
  
      private function setEncoder(Gpf_Rpc_Params $params) {
          switch ($params->get(self::FORM_RESPONSE)) {
              case self::HANDLER_FORM:
                  $this->dataEncoder = new Gpf_Rpc_FormHandler();
                  break;
              case self::HANDLER_WINDOW_NAME:
                  $this->dataEncoder = new Gpf_Rpc_WindowNameHandler();
                  break;
              default:
                  $this->dataEncoder = new Gpf_Rpc_Json();
                  break;
          }
      }
  
      /**
       * Executes multi request
       *
       * @service
       * @anonym
       * @return Gpf_Rpc_Serializable
       */
      public function run(Gpf_Rpc_Params $params) {
          $response = new Gpf_Rpc_Array();
          foreach ($this->getRequestsArray($params) as $request) {
              $response->add($this->executeRequest(new Gpf_Rpc_Params($request)));
          }
          return $response;
      }
  
      public function getRequestsArray(Gpf_Rpc_Params $params) {
          $requestArray = $params->get(self::REQUESTS);
  
          if ($requestArray === null) {
              $requestArray = $params->get(self::REQUESTS_SHORT);
          }
          return $requestArray;
      }
  
      /**
       * Set time offset between client and server and store it to session
       * Offset is computed as client time - server time
       *
       * @anonym
       * @service
       * @param Gpf_Rpc_Params $params
       * @return Gpf_Rpc_Action
       */
      public function syncTime(Gpf_Rpc_Params $params) {
          $action = new Gpf_Rpc_Action($params);
          Gpf_Session::getInstance()->setTimeOffset($action->getParam('offset')/1000);
          $action->addOk();
          return $action;
      }
  }

} //end Gpf_Rpc_Server

if (!class_exists('Gpf_Rpc_MultiRequest', false)) {
  class Gpf_Rpc_MultiRequest extends Gpf_Object {
      private $url = '';
      private $useNewStyleRequestsEncoding;
      private $maxTimeout;
      /**
       *
       * @var Gpf_Rpc_Array
       */
      private $requests;
      /**
       * @var Gpf_Rpc_Json
       */
      private $json;
      protected $serverClassName = 'Gpf_Rpc_Server';
  
      private $sessionId = null;
  
      private $debugRequests = false;
  
      /**
       * @var Gpf_Rpc_MultiRequest
       */
      private static $instance;
  
      public function __construct() {
          $this->json = new Gpf_Rpc_Json();
          $this->requests = new Gpf_Rpc_Array();
      }
  
      public function useNewStyleRequestsEncoding($useNewStyle) {
          $this->useNewStyleRequestsEncoding = $useNewStyle;
      }
      
      public function setMaxTimeout($timeout) {
          $this->maxTimeout = $timeout;
      }
  
      /**
       * @return Gpf_Rpc_MultiRequest
       */
      public static function getInstance() {
          if(self::$instance === null) {
              self::$instance = new Gpf_Rpc_MultiRequest();
          }
          return self::$instance;
      }
  
      public static function setInstance(Gpf_Rpc_MultiRequest $instance) {
          self::$instance = $instance;
      }
  
      public function add(Gpf_Rpc_Request $request) {
          $this->requests->add($request);
      }
  
      protected function sendRequest($requestBody) {
          $request = new Gpf_Net_Http_Request();
  
          $request->setMethod('POST');
          $request->setBody(Gpf_Rpc_Server::BODY_DATA_NAME . '=' . urlencode($requestBody));
          $request->setUrl($this->url);
          if ($this->maxTimeout != '') {
              $request->setMaxTimeout($this->maxTimeout);
          }
  
          $client = new Gpf_Net_Http_Client();
          $response = $client->execute($request);
          return $response->getBody();
      }
  
      public function setSessionId($sessionId) {
          $this->sessionId = $sessionId;
      }
  
      public function setDebugRequests($debug) {
          $this->debugRequests = $debug;
      }
  
      public function send() {
          $request = new Gpf_Rpc_Request($this->serverClassName, Gpf_Rpc_Server::RUN_METHOD);
          if ($this->useNewStyleRequestsEncoding) {
              $request->addParam(Gpf_Rpc_Server::REQUESTS_SHORT, $this->requests);
          } else {
              $request->addParam(Gpf_Rpc_Server::REQUESTS, $this->requests);
          }
          if($this->sessionId != null) {
              $request->addParam("S", $this->sessionId);
          }
          $requestBody = $this->json->encodeResponse($request);
          $responseText = $this->sendRequest($requestBody);
          if($this->debugRequests) {
              echo "REQUEST: ".$requestBody."<br/>";
              echo "RESPONSE: ".$responseText."<br/><br/>";
          }
          $responseArray = $this->json->decode($responseText);
  
          if (!is_array($responseArray)) {
              throw new Gpf_Exception("Response decoding failed: not array. Received text: $responseText");
          }
  
          if (count($responseArray) != $this->requests->getCount()) {
              throw new Gpf_Exception("Response decoding failed: Number of responses is not same as number of requests");
          }
  
          $exception = false;
          foreach ($responseArray as $index => $response) {
              if (is_object($response) && isset($response->e)) {
                  $exception = true;
                  $this->requests->get($index)->setResponseError($response->e);
              } else {
                  $this->requests->get($index)->setResponse($response);
              }
          }
          if($exception) {
              $messages = '';
              foreach ($this->requests as $request) {
                  $messages .= $request->getResponseError() . "|";
              }
          }
          $this->requests = new Gpf_Rpc_Array();
          if($exception) {
              throw new Gpf_Rpc_ExecutionException($messages);
          }
      }
  
      public function setUrl($url) {
          $this->url = $url;
      }
  
      public function getUrl() {
          return $this->url;
      }
  
      private function getCookies() {
          $cookiesString = '';
          foreach ($_COOKIE as $name => $value) {
              $cookiesString .= "$name=$value;";
          }
          return $cookiesString;
      }
  }
  

} //end Gpf_Rpc_MultiRequest

if (!class_exists('Gpf_Rpc_Params', false)) {
  class Gpf_Rpc_Params extends Gpf_Object implements Gpf_Rpc_Serializable {
      private $params;
      const CLASS_NAME = 'C';
      const METHOD_NAME = 'M';
      const SESSION_ID = 'S';
      const ACCOUNT_ID = 'aid';
  
      function __construct($params = null) {
          if($params === null) {
              $this->params = new stdClass();
              return;
          }
          $this->params = $params;
      }
  
      public static function createGetRequest($className, $methodName = 'execute', $formRequest = false, $formResponse = false) {
          $requestData = array();
          $requestData[self::CLASS_NAME] = $className;
          $requestData[self::METHOD_NAME] = $methodName;
          $requestData[Gpf_Rpc_Server::FORM_REQUEST] = $formRequest ? Gpf::YES : '';
          $requestData[Gpf_Rpc_Server::FORM_RESPONSE] = $formResponse ? Gpf::YES : '';
          return $requestData;
      }
  
      /**
       *
       * @param unknown_type $className
       * @param unknown_type $methodName
       * @param unknown_type $formRequest
       * @param unknown_type $formResponse
       * @return Gpf_Rpc_Params
       */
      public static function create($className, $methodName = 'execute', $formRequest = false, $formResponse = false) {
          $params = new Gpf_Rpc_Params();
          $obj = new stdClass();
          foreach (self::createGetRequest($className, $methodName, $formRequest, $formResponse) as $name => $value) {
              $params->add($name,$value);
          }
          return $params;
      }
  
      public function setArrayParams(array $params) {
          foreach ($params as $name => $value) {
              $this->add($name, $value);
          }
      }
  
      public function exists($name) {
          if(!is_object($this->params) || !array_key_exists($name, $this->params)) {
              return false;
          }
          return true;
      }
  
      /**
       *
       * @param unknown_type $name
       * @return mixed Return null if $name does not exist.
       */
      public function get($name) {
          if(!$this->exists($name)) {
              return null;
          }
          return $this->params->{$name};
      }
  
      public function set($name, $value) {
          if(!$this->exists($name)) {
              return;
          }
          $this->params->{$name} = $value;
      }
  
      public function add($name, $value) {
          $this->params->{$name} = $value;
      }
  
      public function getClass() {
          return $this->get(self::CLASS_NAME);
      }
  
      public function getMethod() {
          return $this->get(self::METHOD_NAME);
      }
  
      public function getSessionId() {
          $sessionId = $this->get(self::SESSION_ID);
          if ($sessionId === null || strlen(trim($sessionId)) == 0) {
              Gpf_Session::create(new Gpf_ApiModule());
          }
          return $sessionId;
      }
      
      public function clearSessionId() {
          $this->set(self::SESSION_ID, null);
      }
  
      public function getAccountId() {
          return $this->get(self::ACCOUNT_ID);
      }
  
      public function toObject() {
          return $this->params;
      }
  
      public function toText() {
          throw new Gpf_Exception("Unimplemented");
      }
  }
  

} //end Gpf_Rpc_Params

if (!class_exists('Gpf_Exception', false)) {
  class Gpf_Exception extends Exception {
  
      private $id;
  
      public function __construct($message,$code = null) {
          if (defined('FULL_EXCEPTION_TRACE')) {
              $message .= "<br>\nTRACE:<br>\n" . $this->getTraceAsString();
          }
          parent::__construct($message,$code);
      }
  
      protected function logException() {
          Gpf_Log::error($this->getMessage());
      }
  
      public function setId($id) {
          $this->id = $id;
      }
  
      public function getId() {
          return $this->id;
      }
  
  }

} //end Gpf_Exception

if (!class_exists('Gpf_Data_RecordSetNoRowException', false)) {
  class Gpf_Data_RecordSetNoRowException extends Gpf_Exception {
      public function __construct($keyValue) {
          parent::__construct("'Row $keyValue does not exist");
      }
      
      protected function logException() {
      }
  }

} //end Gpf_Data_RecordSetNoRowException

if (!class_exists('Gpf_Rpc_ExecutionException', false)) {
  class Gpf_Rpc_ExecutionException extends Gpf_Exception {
       
      function __construct($message) {
          parent::__construct('RPC Execution exception: ' . $message);
      }
  }

} //end Gpf_Rpc_ExecutionException

if (!class_exists('Gpf_Rpc_Object', false)) {
  class Gpf_Rpc_Object extends Gpf_Object implements Gpf_Rpc_Serializable {
      
      private $object;
      
      public function __construct($object = null) {
          $this->object = $object;
      }
      
      public function toObject() {
          if ($this->object != null) {
              return $this->object;
          }
          return $this;
      }
      
      public function toText() {
          return var_dump($this);
      }
  }
  

} //end Gpf_Rpc_Object

if (!class_exists('Gpf_Rpc_Request', false)) {
  class Gpf_Rpc_Request extends Gpf_Object implements Gpf_Rpc_Serializable {
      protected $className;
      protected $methodName;
      private $responseError;
      protected $response;
      protected $apiSessionObject = null;
      private $useNewStyleRequestsEncoding = false;
      private $maxTimeout = null;
  
      /**
       * @var Gpf_Rpc_MultiRequest
       */
      private $multiRequest;
  
      /**
       * @var Gpf_Rpc_Params
       */
      protected $params;
      private $accountId = null;
  
      public function __construct($className, $methodName, Gpf_Api_Session $apiSessionObject = null) {
          $this->className = $className;
          $this->methodName = $methodName;
          $this->params = new Gpf_Rpc_Params();
          $this->setRequiredParams($this->className, $this->methodName);
          if($apiSessionObject != null) {
              $this->apiSessionObject = $apiSessionObject;
          }
      }
      
      public function setMaxTimeout($timeout) {
          $this->maxTimeout = $timeout;
      }
  
      public function useNewStyleRequestsEncoding($useNewStyle) {
          $this->useNewStyleRequestsEncoding = $useNewStyle;
      }
  
      public function setAccountId($accountId) {
          $this->accountId = $accountId;
      }
  
      public function addParam($name, $value) {
          if(is_scalar($value) || is_null($value)) {
              $this->params->add($name, $value);
              return;
          }
          if($value instanceof Gpf_Rpc_Serializable) {
              $this->params->add($name, $value->toObject());
              return;
          }
          throw new Gpf_Exception("Cannot add request param: Value ($name=$value) is not scalar or Gpf_Rpc_Serializable");
      }
  
      /**
       *
       * @return Gpf_Rpc_MultiRequest
       */
      private function getMultiRequest() {
          if($this->multiRequest === null) {
              return Gpf_Rpc_MultiRequest::getInstance();
          }
          return $this->multiRequest;
      }
  
      public function setUrl($url) {
          $this->multiRequest = new Gpf_Rpc_MultiRequest();
          $this->multiRequest->setUrl($url);
      }
  
      public function send() {
          if($this->apiSessionObject != null) {
              $this->multiRequest = new Gpf_Rpc_MultiRequest();
              $this->multiRequest->setUrl($this->apiSessionObject->getUrl());
              $this->multiRequest->useNewStyleRequestsEncoding($this->useNewStyleRequestsEncoding);
              $this->multiRequest->setMaxTimeout($this->maxTimeout);
              $this->multiRequest->setSessionId($this->apiSessionObject->getSessionId());
              $this->multiRequest->setDebugRequests($this->apiSessionObject->getDebug());
          }
           
          $multiRequest = $this->getMultiRequest();
          $multiRequest->add($this);
          $multiRequest->useNewStyleRequestsEncoding($this->useNewStyleRequestsEncoding);
          $multiRequest->setMaxTimeout($this->maxTimeout);
      }
  
      public function sendNow() {
          $this->send();
          $this->getMultiRequest()->send();
      }
  
      public function setResponseError($message) {
          $this->responseError = $message;
      }
  
      public function getResponseError() {
          return $this->responseError;
      }
  
      public function setResponse($response) {
          $this->response = $response;
      }
  
      public function toObject() {
          return $this->params->toObject();
      }
  
      public function toText() {
          throw new Gpf_Exception("Unimplemented");
      }
  
      /**
       *
       * @return stdClass
       */
      final public function getStdResponse() {
          if(isset($this->responseError)) {
              throw new Gpf_Rpc_ExecutionException($this->responseError);
          }
          if($this->response === null) {
              throw new Gpf_Exception("Request not executed yet.");
          }
          return $this->response;
      }
  
      final public function getResponseObject() {
          return new Gpf_Rpc_Object($this->getStdResponse());
      }
  
      private function setRequiredParams($className, $methodName) {
          $this->addParam(Gpf_Rpc_Params::CLASS_NAME, $className);
          $this->addParam(Gpf_Rpc_Params::METHOD_NAME, $methodName);
      }
  
      /**
       * @param Gpf_Rpc_Params $params
       */
      public function setParams(Gpf_Rpc_Params $params) {
          $originalParams = $this->params;
          $this->params = $params;
          $this->setRequiredParams($originalParams->getClass(), $originalParams->getMethod());
      }
  }
  

} //end Gpf_Rpc_Request

if (!interface_exists('Gpf_HttpResponse', false)) {
  interface Gpf_HttpResponse {
      public function setCookieValue($name, $value = null, $expire = null, $path = null, $domain = null, $secure = null, $httpOnly = null);
      
      public function setHeaderValue($name, $value, $replace = true, $httpResponseCode = null);
  }

} //end Gpf_HttpResponse

if (!class_exists('Gpf_Http', false)) {
  class Gpf_Http extends Gpf_Object implements Gpf_HttpResponse {
      /**
       *
       * @var Gpf_HttpResponse
       */
      private static $instance = null;
      
      /**
       * @return Gpf_Http
       */
      private static function getInstance() {
          if(self::$instance === null) {
              self::$instance = new Gpf_Http();
          }
          return self::$instance;
      }
      
      public static function setInstance(Gpf_HttpResponse $instance) {
          self::$instance = $instance;
      }
      
      public static function setCookie($name, $value = null, $expire = null, $path = null, $domain = null, $secure = null, $httpOnly = null) {
          self::getInstance()->setCookieValue($name, $value, $expire, $path, $domain, $secure, $httpOnly);
      }
      
      public static function setHeader($name, $value, $httpResponseCode = null) {
          self::getInstance()->setHeaderValue($name, $value, true, $httpResponseCode);
      }
      
      public function setHeaderValue($name, $value, $replace = true, $httpResponseCode = null) {
          $fileName = '';
          $line = '';
          if(headers_sent($fileName, $line)) {
              throw new Gpf_Exception("Headers already sent in $fileName line $line while setting header $name: $value");
          }
          header($name . ': ' . $value, $replace, $httpResponseCode);
      }
      
      public function setCookieValue($name, $value = null, $expire = null, $path = null, $domain = null, $secure = null, $httpOnly = null) {
          setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
      }
      
      public static function getCookie($name) {
          if (!array_key_exists($name, $_COOKIE)) {
              return null;
          }
          return $_COOKIE[$name];
      }
      
      public static function getUserAgent() {
          $userAgent = '';
          if (isset($_SERVER['HTTP_USER_AGENT'])) {
              $userAgent .= $_SERVER['HTTP_USER_AGENT'];
          }
          if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
              $userAgent .= ' - ' . $_SERVER['HTTP_ACCEPT_LANGUAGE'];
          }
          return $userAgent;
      }
  
      public static function getRemoteIp() {
          if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
              $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
              $ipAddresses = explode(',', $ip);
              foreach ($ipAddresses as $ipAddress) {
                  $ipAddress = trim($ipAddress);
                  if (self::isValidIp($ipAddress)) {
                      return $ipAddress;
                  }
              }
          }
          if (isset($_SERVER['REMOTE_ADDR'])) {
              return $_SERVER['REMOTE_ADDR'];
          }
          return '';
      }
  
      public static function isSSL() {
          if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == "https") {
              return true;
          }
          if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != "off") {
              return true;
          }
          return false;
      }
  
      private static function isValidIp($ip) {
          if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
              return true;
          }
          return false;
      }
  }

} //end Gpf_Http

if (!interface_exists('Gpf_Templates_HasAttributes', false)) {
  interface Gpf_Templates_HasAttributes {
      function getAttributes();
  }

} //end Gpf_Templates_HasAttributes

if (!class_exists('Gpf_Data_RecordHeader', false)) {
  class Gpf_Data_RecordHeader extends Gpf_Object {
      private $ids = array();
      
      /**
       * Create Record header object
       *
       * @param array $headerArray
       */
      public function __construct($headerArray = null) {
          if($headerArray === null) {
              return;
          }
          
          if (!$this->isIterable($headerArray)) {
              $e = new Gpf_Exception('');
              Gpf_Log::error('Not correct header for RecordHeader, trace: '.$e->getTraceAsString());
              
              return;
          }
          
          foreach ($headerArray as $id) {
              $this->add($id);
          }
      }
      
      public function contains($id) {
          return array_key_exists($id, $this->ids);
      }
  
      public function add($id) {
          if($this->contains($id)) {
              return;
          }
  
          $this->ids[$id] = count($this->ids);
      }
  
      public function getIds() {
          return array_keys($this->ids);
      }
  
      public function getIndex($id) {
          if(!$this->contains($id)) {
              throw new Gpf_Exception("Unknown column '" . $id ."'");
          }
          return $this->ids[$id];
      }
      
      public function getSize() {
          return count($this->ids);
      }
  
      public function toArray() {
          $response = array();
          foreach ($this->ids as $columnId => $columnIndex) {
              $response[] = $columnId;
          }
          return $response;
      }
          
      public function toObject() {
          $result = array();
          foreach ($this->ids as $columnId => $columnIndex) {
              $result[] = $columnId;
          }
          return $result;
      }
      
      private function isIterable($var) {
          return (is_array($var) || $var instanceof Traversable || $var instanceof stdClass);
      }
  }
  

} //end Gpf_Data_RecordHeader

if (!interface_exists('Gpf_Data_Row', false)) {
  interface Gpf_Data_Row {
      public function get($name);
  
      public function set($name, $value);
  }

} //end Gpf_Data_Row

if (!class_exists('Gpf_Data_Record', false)) {
  class Gpf_Data_Record extends Gpf_Object implements Iterator, Gpf_Rpc_Serializable,
      Gpf_Templates_HasAttributes, Gpf_Data_Row {
      private $record;
      /**
       *
       * @var Gpf_Data_RecordHeader
       */
      private $header;
      private $position;
  
      /**
       * Create record
       *
       * @param array $header
       * @param array $array values of record from array
       */
      public function __construct($header, $array = array()) {
          if (is_array($header)) {
              $header = new Gpf_Data_RecordHeader($header);
          }
          $this->header = $header;
          $this->record = array_values($array);
          while(count($this->record) < $this->header->getSize()) {
              $this->record[] = null;
          }
      }
      
      function getAttributes() {
          $ret = array();
          foreach ($this as $name => $value) {
              $ret[$name] = $value;
          }
          return $ret;
      }
      
      /**
       * @return Gpf_Data_RecordHeader
       */
      public function getHeader() {
          return $this->header;
      }
      
      public function contains($id) {
          return $this->header->contains($id);
      }
      
      public function get($id) {
          $index = $this->header->getIndex($id);
          return $this->record[$index];
      }
  
      public function set($id, $value) {
          $index = $this->header->getIndex($id);
          $this->record[$index] = $value;
      }
      
      public function add($id, $value) {
          $this->header->add($id);
          $this->set($id, $value);
      }
      
      public function toObject() {
          return $this->record;
      }
      
      public function loadFromObject(array $array) {
          $this->record = $array;
      }
      
      public function toText() {
          return implode('-', $this->record);
      }
  
      public function current() {
          if(!isset($this->record[$this->position])) {
              return null;
          }
          return $this->record[$this->position];
      }
  
      public function key() {
          $ids = $this->header->getIds();
          return $ids[$this->position];
      }
  
      public function next() {
          $this->position++;
      }
  
      public function rewind() {
          $this->position = 0;
      }
  
      public function valid() {
          return $this->position < $this->header->getSize();
      }
      
      public function translateRowColumn($column) {
          $this->set($column, $this->_localize($this->get($column)));
      }
  }
  

} //end Gpf_Data_Record

if (!class_exists('Gpf_Data_Grid', false)) {
  class Gpf_Data_Grid extends Gpf_Object {
      /**
       * @var Gpf_Data_RecordSet
       */
  	private $recordset;
      private $totalCount;
      
      public function loadFromObject(stdClass  $object) {
          $this->recordset = new Gpf_Data_RecordSet();
          $this->recordset->loadFromObject($object->rows);
          $this->totalCount = $object->count;
      }
      
      /**
       * @return Gpf_Data_RecordSet
       */
      public function getRecordset() {
      	return $this->recordset;
      }
      
      public function getTotalCount() {
      	return $this->totalCount;
      }
  }
  

} //end Gpf_Data_Grid

if (!class_exists('Gpf_Data_Filter', false)) {
  class Gpf_Data_Filter extends Gpf_Object implements Gpf_Rpc_Serializable {
      const LIKE = "L";
      const NOT_LIKE = "NL";
      const EQUALS = "E";
      const NOT_EQUALS = "NE";
      
      const DATE_EQUALS = "D=";
      const DATE_GREATER = "D>";
      const DATE_LOWER = "D<";
      const DATE_EQUALS_GREATER = "D>=";
      const DATE_EQUALS_LOWER = "D<=";
      const DATERANGE_IS = "DP";
      const TIME_EQUALS = "T=";
      const TIME_GREATER = "T>";
      const TIME_LOWER = "T<";
      const TIME_EQUALS_GREATER = "T>=";
      const TIME_EQUALS_LOWER = "T<=";
      
      const RANGE_TODAY = 'T';
      const RANGE_YESTERDAY = 'Y';
      const RANGE_LAST_7_DAYS = 'L7D';
      const RANGE_LAST_30_DAYS = 'L30D';
      const RANGE_LAST_90_DAYS = 'L90D';
      const RANGE_THIS_WEEK = 'TW';
      const RANGE_LAST_WEEK = 'LW';
      const RANGE_LAST_2WEEKS = 'L2W';
      const RANGE_LAST_WORKING_WEEK = 'LWW';
      const RANGE_THIS_MONTH = 'TM';
      const RANGE_LAST_MONTH = 'LM';
      const RANGE_THIS_YEAR = 'TY';
      const RANGE_LAST_YEAR = 'LY';
                  
  	private $code;
  	private $operator;
  	private $value;
  	
  	public function __construct($code, $operator, $value) {
  		$this->code = $code;
  		$this->operator = $operator;
  		$this->value = $value;
  	}
  	
  	public function toObject() {
  		return array($this->code, $this->operator, $this->value);
  	}
  	
  	public function toText() {
  		throw new Gpf_Exception("Unsupported");
  	}
  }
  

} //end Gpf_Data_Filter

if (!class_exists('Gpf_Rpc_GridRequest', false)) {
  class Gpf_Rpc_GridRequest extends Gpf_Rpc_Request {
  
  	private $filters = array();
  	
  	private $limit = '';
  	private $offset = '';
  	
  	private $sortColumn = '';
  	private $sortAscending = false;
  	
      /**
       * @return Gpf_Data_Grid
       */
      public function getGrid() {
          $response = new Gpf_Data_Grid();
          $response->loadFromObject($this->getStdResponse());
          return $response;
      }
      
      public function getFilters() {
          return $this->filters;
      }
  
      /**
       * 
       * @return Gpf_Rpc_Params
       */
      public function getParams() {
          return $this->params;
      }
  
  	/**
       * adds filter to grid
       *
       * @param unknown_type $code
       * @param unknown_type $operator
       * @param unknown_type $value
       */
      public function addFilter($code, $operator, $value) {
      	$this->filters[] = new Gpf_Data_Filter($code, $operator, $value);
      }
      
      public function setLimit($offset, $limit) {
      	$this->offset = $offset;
      	$this->limit = $limit;
      }
      
      public function setSorting($sortColumn, $sortAscending = false) {
      	$this->sortColumn = $sortColumn;
      	$this->sortAscending = $sortAscending;
      }
      
      public function send() {
      	if(count($this->filters) > 0) {
      		$this->addParam("filters", $this->getFiltersParameter());
      	}
  		if($this->sortColumn !== '') {
  			$this->addParam("sort_col", $this->sortColumn);
  			$this->addParam("sort_asc", ($this->sortAscending ? 'true' : 'false'));
  		}
  		if($this->offset !== '') {
  			$this->addParam("offset", $this->offset);
  		}
  		if($this->limit !== '') {
  			$this->addParam("limit", $this->limit);
  		}
  		
      	parent::send();
      }
      
      protected function getFiltersParameter() {
      	$filters = new Gpf_Rpc_Array();
      	
      	foreach($this->filters as $filter) {
      		$filters->add($filter);
      	}
      	
      	return $filters;
      }
  }
  
  

} //end Gpf_Rpc_GridRequest

if (!class_exists('Gpf_Data_RecordSet', false)) {
  class Gpf_Data_RecordSet extends Gpf_Object implements IteratorAggregate, Gpf_Rpc_Serializable {
  
      const SORT_ASC = 'ASC';
      const SORT_DESC = 'DESC';
  
      protected $_array;
      /**
       * @var Gpf_Data_RecordHeader
       */
      private $_header;
  
      function __construct() {
          $this->init();
      }
  
      public function loadFromArray($rows) {
          $this->setHeader($rows[0]);
  
          for ($i = 1; $i < count($rows); $i++) {
              $this->add($rows[$i]);
          }
      }
  
      public function setHeader($header) {
          if($header instanceof Gpf_Data_RecordHeader) {
              $this->_header = $header;
              return;
          }
          $this->_header = new Gpf_Data_RecordHeader($header);
      }
  
      /**
       * @return Gpf_Data_RecordHeader
       */
      public function getHeader() {
          return $this->_header;
      }
  
      public function addRecord(Gpf_Data_Record $record) {
          $this->_array[] = $record;
      }
  
      public function removeRecord($i) {
          unset($this->_array[$i]);
      }
  
      /**
       * Adds new row to RecordSet
       *
       * @param array $record array of data for all columns in record
       */
      public function add($record) {
          $this->addRecord($this->getRecordObject($record));
      }
  
      /**
       * @return Gpf_Data_Record
       */
      public function createRecord() {
          return new Gpf_Data_Record($this->_header);
      }
  
      public function toObject() {
          $response = array();
          $response[] = $this->_header->toObject();
          foreach ($this->_array as $record) {
              $response[] = $record->toObject();
          }
          return $response;
      }
  
      public function loadFromObject($array) {
          if($array === null) {
              throw new Gpf_Exception('Array must be not NULL');
          }
          $this->_header = new Gpf_Data_RecordHeader($array[0]);
          for($i = 1; $i < count($array);$i++) {
              $record = new Gpf_Data_Record($this->_header);
              $record->loadFromObject($array[$i]);
              $this->loadRecordFromObject($record);
          }
      }
  
      public function sort($column, $sortType = 'ASC') {
          if (!$this->_header->contains($column)) {
              throw new Gpf_Exception('Undefined column');
          }
          $sorter = new Gpf_Data_RecordSet_Sorter($column, $sortType);
          $this->_array = $sorter->sort($this->_array);
      }
  
      protected function loadRecordFromObject(Gpf_Data_Record $record) {
          $this->_array[] = $record;
      }
  
      public function toArray() {
          $response = array();
          foreach ($this->_array as $record) {
              $response[] = $record->getAttributes();
          }
          return $response;
      }
  
      public function toText() {
          $text = '';
          foreach ($this->_array as $record) {
              $text .= $record->toText() . "<br>\n";
          }
          return $text;
      }
  
      /**
       * Return number of rows in recordset
       *
       * @return integer
       */
      public function getSize() {
          return count($this->_array);
      }
  
      /**
       * @return Gpf_Data_Record
       */
      public function get($i) {
          return $this->_array[$i];
      }
  
      /**
       * @param array/Gpf_Data_Record $record
       * @return Gpf_Data_Record
       */
      private function getRecordObject($record) {
          if(!($record instanceof Gpf_Data_Record)) {
              $record = new Gpf_Data_Record($this->_header->toArray(), $record);
          }
          return $record;
      }
  
      private function init() {
          $this->_array = array();
          $this->_header = new Gpf_Data_RecordHeader();
      }
  
      public function clear() {
          $this->init();
      }
  
      public function load(Gpf_SqlBuilder_SelectBuilder $select) {
          $this->init();
  
          foreach ($select->select->getColumns() as $column) {
              $this->_header->add($column->getAlias());
          }
          $statement = $this->createDatabase()->execute($select->toString());
          while($rowArray = $statement->fetchRow()) {
              $this->add($rowArray);
          }
      }
  
      /**
       *
       * @return ArrayIterator
       */
      public function getIterator() {
          return new ArrayIterator($this->_array);
      }
  
      public function getRecord($keyValue = null) {
          if(!array_key_exists($keyValue, $this->_array)) {
              return $this->createRecord();
          }
          return $this->_array[$keyValue];
      }
  
      public function addColumn($id, $defaultValue = "") {
          $this->_header->add($id);
          foreach ($this->_array as $record) {
              $record->add($id, $defaultValue);
          }
      }
  
      /**
       * Creates shalow copy of recordset containing only headers
       *
       * @return Gpf_Data_RecordSet
       */
      public function toShalowRecordSet() {
         $copy = new Gpf_Data_RecordSet();
         $copy->setHeader($this->_header->toArray());
         return $copy;
      }
  }
  
  class Gpf_Data_RecordSet_Sorter {
  
      private $sortColumn;
      private $sortType;
  
      function __construct($column, $sortType) {
          $this->sortColumn = $column;
          $this->sortType = $sortType;
      }
  
      public function sort(array $sortedArray) {
          usort($sortedArray, array($this, 'compareRecords'));
          return $sortedArray;
      }
  
      private function compareRecords($record1, $record2) {
          if ($record1->get($this->sortColumn) == $record2->get($this->sortColumn)) {
              return 0;
          }
          return $this->compare($record1->get($this->sortColumn), $record2->get($this->sortColumn));
      }
  
      private function compare($value1, $value2) {
          if ($this->sortType == Gpf_Data_RecordSet::SORT_ASC) {
              return (strtolower($value1) < strtolower($value2)) ? -1 : 1;
          }
          return (strtolower($value1) < strtolower($value2)) ? 1 : -1;
      }
  }

} //end Gpf_Data_RecordSet

if (!class_exists('Gpf_Data_IndexedRecordSet', false)) {
  class Gpf_Data_IndexedRecordSet extends Gpf_Data_RecordSet {
      private $key;
  
      /**
       *
       * @param int $keyIndex specifies which column should be used as a key
       */
      function __construct($key) {
          parent::__construct();
          $this->key = $key;
      }
      
      public function addRecord(Gpf_Data_Record $record) {
          $this->_array[$record->get($this->key)] = $record;
      }
      
      /**
       * @param String $keyValue
       * @return Gpf_Data_Record
       */
      public function createRecord($keyValue = null) {
          if($keyValue === null) {
              return parent::createRecord();
          }
          if(!array_key_exists($keyValue, $this->_array)) {
              $record = $this->createRecord();
              $record->set($this->key, $keyValue);
              $this->addRecord($record);
          }
          return $this->_array[$keyValue];
      }
      
      protected function loadRecordFromObject(Gpf_Data_Record $record) {    
          $this->_array[$record->get($this->key)] = $record; 
      }                
          
      /**
       * @param String $keyValue
       * @return Gpf_Data_Record
       */
      public function getRecord($keyValue = null) {
          if (!isset($this->_array[$keyValue])) {
              throw new Gpf_Data_RecordSetNoRowException($keyValue);
          }
          return $this->_array[$keyValue];
      }
      
      /**
       * @param String $keyValue
       * @return boolean
       */
      public function existsRecord($keyValue) {
          return isset($this->_array[$keyValue]);
      }
      
      /**
       * @param String $sortOptions (SORT_ASC, SORT_DESC, SORT_REGULAR, SORT_NUMERIC, SORT_STRING)
       * @return boolean
       */
      public function sortByKeyValue($sortOptions) {
          return array_multisort($this->_array, $sortOptions);
      }
  }
  

} //end Gpf_Data_IndexedRecordSet

if (!class_exists('Gpf_Net_Http_Request', false)) {
  class Gpf_Net_Http_Request extends Gpf_Object {
  	const CRLF = "\r\n";
  
  	private $method = 'GET';
  	private $url;
  
  	//proxy server
  	private $proxyServer = '';
  	private $proxyPort = '';
  	private $proxyUser = '';
  	private $proxyPassword = '';
  
  	//URL components
  	private $scheme = 'http';
  	private $host = '';
  	private $port = 80;
  	private $http_user = '';
  	private $http_password = '';
  	private $path = '';
  	private $query = '';
  	private $fragment = '';
  	private $cookies = '';
  	
  	private $maxTimeout = null;
  
  	private $body = '';
  	private $headers = array();
  
  	public function setCookies($cookies) {
  		$this->cookies = $cookies;
  	}
  
  	public function getCookies() {
  		return $this->cookies;
  	}
  
      public function getCookiesString() {
          $cookies = '';
          if (!is_array($this->cookies)) {
              return $cookies;
          }
          foreach ($this->cookies as $key => $value) {
              $cookies .= "$key=$value; ";
          }
          return $cookies;
      }
      
      public function getMaxTimeout() {
          return $this->maxTimeout;
      }
      
      public function setMaxTimeout($timeout) {
          $this->maxTimeout = $timeout;
      }
  
  	public function getCookiesHeader() {
  		return "Cookie: " . $this->getCookiesString();
  	}
  
  	public function setUrl($url) {
  		$this->url = $url;
  		$this->parseUrl();
  	}
  
  	public function getUrl() {
  		return $this->url;
  	}
  
  	private function parseUrl() {
  		$components = parse_url($this->url);
  		if (array_key_exists('scheme', $components)) {
  			$this->scheme = $components['scheme'];
  		}
  		if (array_key_exists('host', $components)) {
  			$this->host = $components['host'];
  		}
  		if (array_key_exists('port', $components)) {
  			$this->port = $components['port'];
  		}
  		if (array_key_exists('user', $components)) {
  			$this->http_user = $components['user'];
  		}
  		if (array_key_exists('pass', $components)) {
  			$this->http_password = $components['pass'];
  		}
  		if (array_key_exists('path', $components)) {
  			$this->path = $components['path'];
  		}
  		if (array_key_exists('query', $components)) {
  			$this->query = $components['query'];
  		}
  		if (array_key_exists('fragment', $components)) {
  			$this->fragment = $components['fragment'];
  		}
  	}
  
  	public function getScheme() {
  		return $this->scheme;
  	}
  
  	public function getHost() {
  		if (strlen($this->proxyServer)) {
  			return $this->proxyServer;
  		}
  		return $this->host;
  	}
  
  	public function getPort() {
  		if (strlen($this->proxyServer)) {
  			return $this->proxyPort;
  		}
  
  		if (strlen($this->port)) {
  			return $this->port;
  		}
  		return 80;
  	}
  
  	public function getHttpUser() {
  		return $this->http_user;
  	}
  
      public function setHttpUser($user) {
          $this->http_user = $user;
      }
  
  	public function getHttpPassword() {
  		return $this->http_password;
  	}
  
      public function setHttpPassword($pass) {
          $this->http_password = $pass;
      }
  
  	public function getPath() {
  		return $this->path;
  	}
  
  	public function getQuery() {
  		return $this->query;
  	}
  
  	public function addQueryParam($name, $value) {
  		if (is_array($value)) {
  			foreach($value as $key => $subValue) {
  				$this->addQueryParam($name."[".$key."]", $subValue);
  			}
  			return;
  		}
  		$this->query .= ($this->query == '') ? '?' : '&';
  		$this->query .= $name.'='.urlencode($value);
  	}
  
  	public function getFragment() {
  		return $this->fragment;
  	}
  
  	/**
  	 * Set if request method is GET or POST
  	 *
  	 * @param string $method possible values are POST or GET
  	 */
  	public function setMethod($method) {
  		$method = strtoupper($method);
  		if ($method != 'GET' && $method != 'POST') {
  			throw new Gpf_Exception('Unsupported HTTP method: ' . $method);
  		}
  		$this->method = $method;
  	}
  
  	/**
  	 * get the request method
  	 *
  	 * @access   public
  	 * @return   string
  	 */
  	public function getMethod() {
  		return $this->method;
  	}
  
  	/**
  	 * In case request should be redirected through proxy server, set proxy server settings
  	 * This function should be called after function setHost !!!
  	 *
  	 * @param string $server
  	 * @param string $port
  	 * @param string $user
  	 * @param string $password
  	 */
  	public function setProxyServer($server, $port, $user, $password) {
  		$this->proxyServer = $server;
  		$this->proxyPort = $port;
  		$this->proxyUser = $user;
  		$this->proxyPassword = $password;
  	}
  
  	public function getProxyServer() {
  		return $this->proxyServer;
  	}
  
  	public function getProxyPort() {
  		return $this->proxyPort;
  	}
  
  	public function getProxyUser() {
  		return $this->proxyUser;
  	}
  
  	public function getProxyPassword() {
  		return $this->proxyPassword;
  	}
  
  	public function setBody($body) {
  		$this->body = $body;
  	}
  
  	public function getBody() {
  		return $this->body;
  	}
  
  	/**
  	 * Set header value
  	 *
  	 * @param string $name
  	 * @param string $value
  	 */
  	public function setHeader($name, $value) {
  		$this->headers[$name] = $value;
  	}
  
  	/**
  	 * Get header value
  	 *
  	 * @param string $name
  	 * @return string
  	 */
  	public function getHeader($name) {
  		if (array_key_exists($name, $this->headers)) {
  			return $this->headers[$name];
  		}
  		return null;
  	}
  
  	/**
  	 * Return array of headers
  	 *
  	 * @return array
  	 */
  	public function getHeaders() {
  		$headers = array();
  		foreach ($this->headers as $headerName => $headerValue) {
  			$headers[] = "$headerName: $headerValue";
  		}
  		return $headers;
  	}
  
  	private function initHeaders() {
  		if ($this->getPort() == '80') {
  			$this->setHeader('Host', $this->getHost());
  		} else {
  			$this->setHeader('Host', $this->getHost() . ':' . $this->getPort());
  		}
  		if (isset($_SERVER['HTTP_USER_AGENT'])) {
  			$this->setHeader('User-Agent', $_SERVER['HTTP_USER_AGENT']);
  		}
  		if (isset($_SERVER['HTTP_ACCEPT'])) {
  			$this->setHeader('Accept', $_SERVER['HTTP_ACCEPT']);
  		}
  		if (isset($_SERVER['HTTP_ACCEPT_CHARSET'])) {
  			$this->setHeader('Accept-Charset', $_SERVER['HTTP_ACCEPT_CHARSET']);
  		}
  		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
  			$this->setHeader('Accept-Language', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
  		}
  		if (isset($_SERVER['HTTP_REFERER'])) {
  			$this->setHeader('Referer', $_SERVER['HTTP_REFERER']);
  		}
  		if ($this->getMethod() == 'POST' && !strlen($this->getHeader("Content-Type"))) {
  			$this->setHeader("Content-Type", "application/x-www-form-urlencoded");
  		}
  		if ($this->getHttpPassword() != '' && $this->getHttpUser() != '') {
              $this->setHeader('Authorization', 'Basic ' . base64_encode($this->getHttpUser() . ':' . $this->getHttpPassword()));
  		}
  
  		$this->setHeader('Content-Length', strlen($this->getBody()));
  		$this->setHeader('Connection', 'close');
  
  		if (strlen($this->proxyUser)) {
  			$this->setHeader('Proxy-Authorization',
              'Basic ' . base64_encode ($this->proxyUser . ':' . $this->proxyPassword));
  		}
  
  	}
  
  	public function getUri() {
  		$uri = $this->getPath();
  		if (strlen($this->getQuery())) {
  			$uri .= '?' . $this->getQuery();
  		}
  		return $uri;
  	}
  
  	public function toString() {
  		$this->initHeaders();
  		$out = sprintf('%s %s HTTP/1.0' . self::CRLF, $this->getMethod(), $this->getUri());
  		$out .= implode(self::CRLF, $this->getHeaders()) . self::CRLF . $this->getCookiesHeader() . self::CRLF;
  		$out .= self::CRLF . $this->getBody();
  		return $out;
  	}
  
  }

} //end Gpf_Net_Http_Request

if (!class_exists('Gpf_Net_Http_ClientBase', false)) {
  abstract class Gpf_Net_Http_ClientBase extends Gpf_Object {
      const CONNECTION_TIMEOUT = 20;
  
      //TODO: rename this method to "send()"
      /**
       * @param Gpf_Net_Http_Request $request
       * @return Gpf_Net_Http_Response
       */
      public function execute(Gpf_Net_Http_Request $request) {
  
          if (!$this->isNetworkingEnabled()) {
              throw new Gpf_Exception($this->_('Network connections are disabled'));
          }
  
          if (!strlen($request->getUrl())) {
              throw new Gpf_Exception('No URL defined.');
          }
  
          $this->setProxyServer($request);
          if (Gpf_Php::isFunctionEnabled('curl_init') && Gpf_Php::isFunctionEnabled('curl_exec')) {
              return $this->executeWithCurl($request);
          } else {
              return $this->executeWithSocketOpen($request);
          }
      }
  
      protected abstract function isNetworkingEnabled();
  
      /**
       * @param Gpf_Net_Http_Request $request
       * @return Gpf_Net_Http_Response
       */
      private function executeWithSocketOpen(Gpf_Net_Http_Request $request) {
          $timeout = self::CONNECTION_TIMEOUT;
          if ($request->getMaxTimeout() != '') {
              $timeout = $request->getMaxTimeout();
          }
          
          $scheme = ($request->getScheme() == 'ssl' || $request->getScheme() == 'https') ? 'ssl://' : '';
          $proxySocket = @fsockopen($scheme . $request->getHost(), $request->getPort(), $errorNr,
          $errorMessage, $timeout);
  
          if($proxySocket === false) {
              $gpfErrorMessage = $this->_sys('Could not connect to server: %s:%s, Failed with error: %s', $request->getHost(), $request->getPort(), $errorMessage);
              Gpf_Log::error($gpfErrorMessage);
              throw new Gpf_Exception($gpfErrorMessage);
          }
  
          $requestText = $request->toString();
  
          $result = @fwrite($proxySocket, $requestText);
          if($result === false || $result != strlen($requestText)) {
              @fclose($proxySocket);
              $gpfErrorMessage = $this->_sys('Could not send request to server %s:%s', $request->getHost(), $request->getPort());
              Gpf_Log::error($gpfErrorMessage);
              throw new Gpf_Exception($gpfErrorMessage);
          }
  
          $result = '';
          while (false === @feof($proxySocket)) {
              try {
                  if(false === ($data = @fread($proxySocket, 8192))) {
                      Gpf_Log::error($this->_sys('Could not read from proxy socket'));
                      throw new Gpf_Exception("could not read from proxy socket");
                  }
                  $result .= $data;
              } catch (Exception $e) {
                  Gpf_Log::error($this->_sys('Proxy failed: %s', $e->getMessage()));
                  @fclose($proxySocket);
                  throw new Gpf_Exception($this->_('Proxy failed: %s', $e->getMessage()));
              }
          }
          @fclose($proxySocket);
  
          $response = new Gpf_Net_Http_Response();
          $response->setResponseText($result);
  
          return $response;
      }
  
  
      /**
       * @param Gpf_Net_Http_Request $request
       * @return Gpf_Net_Http_Response
       *      */
      private function executeWithCurl(Gpf_Net_Http_Request $request) {
          $session = curl_init($request->getUrl());
  
          if ($request->getMethod() == 'POST') {
              @curl_setopt ($session, CURLOPT_POST, true);
              @curl_setopt ($session, CURLOPT_POSTFIELDS, $request->getBody());
          }
  
          $cookies = $request->getCookiesString();
          if($cookies) {
              @curl_setopt($session, CURLOPT_COOKIE, $cookies);
          }
  
          @curl_setopt($session, CURLOPT_HEADER, true);
          @curl_setopt($session, CURLOPT_CONNECTTIMEOUT, self::CONNECTION_TIMEOUT);
          @curl_setopt($session, CURLOPT_HTTPHEADER, $request->getHeaders());
          @curl_setopt($session, CURLOPT_FOLLOWLOCATION, true);
          @curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
          if ($request->getHttpPassword() != '' && $request->getHttpUser() != '') {
          	@curl_setopt($session, CURLOPT_USERPWD, $request->getHttpUser() . ":" . $request->getHttpPassword());
          	@curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
          }
          @curl_setopt ($session, CURLOPT_SSL_VERIFYHOST, 0);
          @curl_setopt ($session, CURLOPT_SSL_VERIFYPEER, 0);
          if ($request->getMaxTimeout() != '') {
              @curl_setopt($ch, CURLOPT_TIMEOUT, $request->getMaxTimeout()); 
          }
  
          $this->setupCurlProxyServer($session, $request);
  
          // Make the call
          $result = curl_exec($session);
          $error = curl_error($session);
  
          curl_close($session);
  
          if (strlen($error)) {
              throw new Gpf_Exception('Curl error: ' . $error . '; ' . $request->getUrl());
          }
  
          $response = new Gpf_Net_Http_Response();
          $response->setResponseText($result);
  
          return $response;
      }
  
      protected function setProxyServer(Gpf_Net_Http_Request $request) {
          try {
              $proxyServer = Gpf_Settings::get(Gpf_Settings_Gpf::PROXY_SERVER_SETTING_NAME);
              $proxyPort = Gpf_Settings::get(Gpf_Settings_Gpf::PROXY_PORT_SETTING_NAME);
              $proxyUser = Gpf_Settings::get(Gpf_Settings_Gpf::PROXY_USER_SETTING_NAME);
              $proxyPassword = Gpf_Settings::get(Gpf_Settings_Gpf::PROXY_PASSWORD_SETTING_NAME);
              $request->setProxyServer($proxyServer, $proxyPort, $proxyUser, $proxyPassword);
          } catch (Gpf_Exception $e) {
              $request->setProxyServer('', '', '', '');
          }
      }
  
      private function setupCurlProxyServer($curlSession, Gpf_Net_Http_Request $request) {
          if (strlen($request->getProxyServer()) && strlen($request->getProxyPort())) {
              @curl_setopt($curlSession, CURLOPT_PROXY, $request->getProxyServer() . ':' . $request->getProxyPort());
              if (strlen($request->getProxyUser())) {
                  @curl_setopt($curlSession, CURLOPT_PROXYUSERPWD, $request->getProxyUser() . ':' . $request->getProxyPassword());
              }
          }
      }
  }

} //end Gpf_Net_Http_ClientBase

if (!class_exists('Gpf_Net_Http_Response', false)) {
  class Gpf_Net_Http_Response extends Gpf_Object {
  
      private $responseText = '';
      private $header = '';
      private $body = '';
  
      public function setResponseText($responseText) {
          $this->responseText = $responseText;
          $this->parse();
      }
  
      public function getHeadersText() {
          return $this->header;
      }
  
      private function getHeaderPosition($pos) {
          return strpos($this->responseText, "\r\n\r\nHTTP", $pos);
      }
  
      public function getBody() {
          return $this->body;
      }
  
      private function parse() {
          $offset = 0;
          while ($this->getHeaderPosition($offset)) {
              $offset = $this->getHeaderPosition($offset) + 4;
          }
          if (($pos = strpos($this->responseText, "\r\n\r\n", $offset)) > 0) {
              $this->body = substr($this->responseText, $pos + 4);
              $this->header = substr($this->responseText, $offset, $pos - $offset);
              return;
          }
          $this->body = '';
          $this->header = '';
      }
  
  
  
      public function getResponseCode() {
          $headers = $this->getHeaders();
          if ($headers == false || !isset($headers['status'])) {
              return false;
          }
          preg_match('/.*?\s([0-9]*?)\s.*/', $headers['status'], $match);
          if (!isset($match[1])) {
              return false;
          }
          return $match[1];
      }
  
      public function getHeaders() {
          return $this->httpParseHeaders($this->header);
      }
  
      private function httpParseHeaders($headers=false){
          if($headers === false){
              return false;
          }
          $headers = str_replace("\r","",$headers);
          $headers = explode("\n",$headers);
          foreach($headers as $value){
              $header = explode(": ",$value);
              if($header[0] && !isset($header[1])){
                  $headerdata['status'] = $header[0];
              } elseif($header[0] && isset($header[1])){
                  $headerdata[$header[0]] = $header[1];
              }
          }
          return $headerdata;
      }
  }

} //end Gpf_Net_Http_Response

if (!class_exists('Gpf_Rpc_Form', false)) {
  class Gpf_Rpc_Form extends Gpf_Object implements Gpf_Rpc_Serializable, IteratorAggregate {
      const FIELD_NAME  = "name";
      const FIELD_VALUE = "value";
      const FIELD_ERROR = "error";
      const FIELD_VALUES = "values";
  
      private $isError = false;
      private $errorMessage = "";
      private $infoMessage = "";
      private $status;
      /**
       * @var Gpf_Data_IndexedRecordSet
       */
      private $fields;
      /**
       * @var Gpf_Rpc_Form_Validator_FormValidatorCollection
       */
      private $validators;
  
      public function __construct(Gpf_Rpc_Params $params = null) {
          $this->fields = new Gpf_Data_IndexedRecordSet(self::FIELD_NAME);
  
          $header = new Gpf_Data_RecordHeader();
          $header->add(self::FIELD_NAME);
          $header->add(self::FIELD_VALUE);
          $header->add(self::FIELD_VALUES);
          $header->add(self::FIELD_ERROR);
          $this->fields->setHeader($header);
          
          $this->validator = new Gpf_Rpc_Form_Validator_FormValidatorCollection($this);
          
          if($params) {
              $this->loadFieldsFromArray($params->get("fields"));
          }
      }
  
      /**
       * @param $validator
       * @param $fieldName
       * @param $fieldLabel
       */
      public function addValidator(Gpf_Rpc_Form_Validator_Validator $validator, $fieldName, $fieldLabel = null) {
          $this->validator->addValidator($validator, $fieldName, $fieldLabel);
      }
      
      /**
       * @return boolean
       */
      public function validate() {
          return $this->validator->validate();
      }
      
      public function loadFieldsFromArray($fields) {
          for ($i = 1; $i < count($fields); $i++) {
              $field = $fields[$i];
              $this->fields->add($field);
          }
      }
      
      /**
       *
       * @return ArrayIterator
       */
      public function getIterator() {
          return $this->fields->getIterator();
      }
      
      public function addField($name, $value) {
          $record = $this->fields->createRecord($name);
          $record->set(self::FIELD_VALUE, $value);
      }
      
      public function setField($name, $value, $values = null, $error = "") {
          $record = $this->fields->createRecord($name);
          $record->set(self::FIELD_VALUE, $value);
          $record->set(self::FIELD_VALUES, $values);
          $record->set(self::FIELD_ERROR, $error);
      }
      
      public function setFieldError($name, $error) {
          $this->isError = true;
          $record = $this->fields->getRecord($name);
          $record->set(self::FIELD_ERROR, $error);
      }
      
      public function getFieldValue($name) {
          $record = $this->fields->getRecord($name);
          return $record->get(self::FIELD_VALUE);
      }
      
      public function getFieldValues($name) {
          $record = $this->fields->getRecord($name);
          return $record->get(self::FIELD_VALUES);
      }
  
      public function getFieldError($name) {
          $record = $this->fields->getRecord($name);
          return $record->get(self::FIELD_ERROR);
      }
      
      public function existsField($name) {
          return $this->fields->existsRecord($name);
      }
       
      public function load(Gpf_Data_Row $row) {
          foreach($row as $columnName => $columnValue) {
              $this->setField($columnName, $row->get($columnName));
          }
      }
  
      /**
       * @return Gpf_Data_IndexedRecordSet
       */
      public function getFields() {
          return $this->fields;
      }
      
      public function fill(Gpf_Data_Row $row) {
          foreach ($this->fields as $field) {
              try {
                  $row->set($field->get(self::FIELD_NAME), $field->get(self::FIELD_VALUE));
              } catch (Exception $e) {
              }
          }
      }
      
      public function toObject() {
          $response = new stdClass();
          $response->fields = $this->fields->toObject();
          if ($this->isSuccessful()) {
              $response->success = Gpf::YES;
              $response->message = $this->infoMessage;
          } else {
              $response->success = "N";
              $response->message = $this->errorMessage;
          }
          return $response;
      }
      
      public function loadFromObject(stdClass $object) {
          if ($object->success == Gpf::YES) {
          	$this->setInfoMessage($object->message);
          } else {
          	$this->setErrorMessage($object->message);
          }
          
          $this->fields = new Gpf_Data_IndexedRecordSet(self::FIELD_NAME);
          $this->fields->loadFromObject($object->fields);
      }
      
      public function toText() {
          return var_dump($this->toObject());
      }
  
      public function setErrorMessage($message) {
          $this->isError = true;
          $this->errorMessage = $message;
      }
      
      public function getErrorMessage() {
          if ($this->isError) {
              return $this->errorMessage;
          }
          return "";
      }
      
      public function setInfoMessage($message) {
          $this->infoMessage = $message;
      }
      
      public function setSuccessful() {
          $this->isError = false;
      }
      
      public function getInfoMessage() {
          if ($this->isError) {
              return "";
          }
          return $this->infoMessage;
      }
      
      
      /**
       * @return boolean
       */
      public function isSuccessful() {
          return !$this->isError;
      }
      
      /**
       * @return boolean
       */
      public function isError() {
          return $this->isError;
      }
  }
  

} //end Gpf_Rpc_Form

if (!class_exists('Gpf_Rpc_Form_Validator_FormValidatorCollection', false)) {
  class Gpf_Rpc_Form_Validator_FormValidatorCollection extends Gpf_Object {
      
      /**
       * @var array<Gpf_Rpc_Form_Validator_FieldValidator>
       */
      private $validators;
      /**
       * @var Gpf_Rpc_Form
       */
      private $form;
      
      public function __construct(Gpf_Rpc_Form $form) {
          $this->form = $form;
          $this->validators = array();
      }
      
      /**
       * @param $fieldName
       * @param $validator
       */
      public function addValidator(Gpf_Rpc_Form_Validator_Validator $validator, $fieldName, $fieldLabel = null) {
          if (!array_key_exists($fieldName, $this->validators)) {
              $this->validators[$fieldName] = new Gpf_Rpc_Form_Validator_FieldValidator(($fieldLabel === null ? $fieldName : $fieldLabel));
          }
          $this->validators[$fieldName]->addValidator($validator);
      }
      
      /**
       * @return boolean
       */
      public function validate() {
          $errorMsg = false;
          foreach ($this->validators as $fieldName => $fieldValidator) {
              if (!$fieldValidator->validate($this->form->getFieldValue($fieldName))) {
                  $errorMsg = true;
                  $this->form->setFieldError($fieldName, $fieldValidator->getMessage());
              }
          }
          if ($errorMsg) {
              $this->form->setErrorMessage($this->_('There were errors, please check highlighted fields'));
          }
          return !$errorMsg;
      }
  }

} //end Gpf_Rpc_Form_Validator_FormValidatorCollection

if (!class_exists('Gpf_Rpc_FormRequest', false)) {
  class Gpf_Rpc_FormRequest extends Gpf_Rpc_Request {
      /**
       * @var Gpf_Rpc_Form
       */
      private $fields;
      
      public function __construct($className, $methodName, Gpf_Api_Session $apiSessionObject = null) {
          parent::__construct($className, $methodName, $apiSessionObject);
          $this->fields = new Gpf_Rpc_Form();
      }
      
      public function send() {
          $this->addParam('fields', $this->fields->getFields());
          parent::send();
      }
      
      /**
       * @return Gpf_Rpc_Form
       */
      public function getForm() {
          $response = new Gpf_Rpc_Form();
          $response->loadFromObject($this->getStdResponse());
          return $response;
      }
  
      public function setField($name, $value) {
          if (is_scalar($value) || $value instanceof Gpf_Rpc_Serializable) {
              $this->fields->setField($name, $value);
          } else {
              throw new Gpf_Exception("Not supported value");
          }
      }
      
      public function setFields(Gpf_Data_IndexedRecordSet $fields) {
      	$this->fields->loadFieldsFromArray($fields->toArray());
      }    
  }

} //end Gpf_Rpc_FormRequest

if (!class_exists('Gpf_Rpc_RecordSetRequest', false)) {
  class Gpf_Rpc_RecordSetRequest extends Gpf_Rpc_Request {
  
      /**
       * @return Gpf_Data_IndexedRecordSet
       */
      public function getIndexedRecordSet($key) {
          $response = new Gpf_Data_IndexedRecordSet($key);
          $response->loadFromObject($this->getStdResponse());
          return $response;
      }
      
      
      /**
       * @return Gpf_Data_RecordSet
       */
      public function getRecordSet() {
          $response = new Gpf_Data_RecordSet();
          $response->loadFromObject($this->getStdResponse());
          return $response;
      }
  }
  

} //end Gpf_Rpc_RecordSetRequest

if (!class_exists('Gpf_Rpc_DataRequest', false)) {
  class Gpf_Rpc_DataRequest extends Gpf_Rpc_Request {
      /**
       * @var Gpf_Rpc_Data
       */
      private $data;
      
      private $filters = array();
      
      public function __construct($className, $methodName, Gpf_Api_Session $apiSessionObject = null) {
          parent::__construct($className, $methodName, $apiSessionObject);
          $this->data = new Gpf_Rpc_Data();
      }
      
      /**
       * @return Gpf_Rpc_Data
       */
      public function getData() {
          $response = new Gpf_Rpc_Data();
          $response->loadFromObject($this->getStdResponse());
          return $response;
      }
  
      public function setField($name, $value) {
          if (is_scalar($value) || $value instanceof Gpf_Rpc_Serializable) {
              $this->data->setParam($name, $value);
          } else {
              throw new Gpf_Exception("Not supported value");
          }
      }
      
      /**
       * adds filter to grid
       *
       * @param unknown_type $code
       * @param unknown_type $operator
       * @param unknown_type $value
       */
      public function addFilter($code, $operator, $value) {
          $this->filters[] = new Gpf_Data_Filter($code, $operator, $value);
      }
      
      public function send() {
          $this->addParam('data', $this->data->getParams());
          
          if(count($this->filters) > 0) {
              $this->addParam("filters", $this->addFiltersParameter());
          }
          parent::send();
      }
      
      private function addFiltersParameter() {
          $filters = new Gpf_Rpc_Array();
          
          foreach($this->filters as $filter) {
              $filters->add($filter);
          }
          
          return $filters;
      }
  }

} //end Gpf_Rpc_DataRequest

if (!class_exists('Gpf_Rpc_Data', false)) {
  class Gpf_Rpc_Data extends Gpf_Object implements Gpf_Rpc_Serializable {
  	const NAME  = "name";
      const VALUE = "value";
      const DATA = "data";
      const ID = "id";
      
  	/**
  	 * @var Gpf_Data_IndexedRecordSet
  	 */
      private $params;
      
      /**
       * @var string
       */
      private $id;
      
      
      /**
       * @var Gpf_Rpc_FilterCollection
       */
      private $filters;
      
      /**
       * @var Gpf_Data_IndexedRecordSet
       */
      private $response;
      
      /**
       *
       * @return Gpf_Data_IndexedRecordSet
       */
      public function getParams() {
          return $this->params;
      }
      
      /**
       * Create instance to handle DataRequest
       *
       * @param Gpf_Rpc_Params $params
       */
      public function __construct(Gpf_Rpc_Params $params = null) {
      	if($params === null) {
      	    $params = new Gpf_Rpc_Params();
      	}
          
      	$this->filters = new Gpf_Rpc_FilterCollection($params);
          
      	$this->params = new Gpf_Data_IndexedRecordSet(self::NAME);
      	$this->params->setHeader(array(self::NAME, self::VALUE));
          
          if ($params->exists(self::DATA) !== null) {
              $this->loadParamsFromArray($params->get(self::DATA));
          }
          
          $this->id = $params->get(self::ID);
          
          $this->response = new Gpf_Data_IndexedRecordSet(self::NAME);
          $this->response->setHeader(array(self::NAME, self::VALUE));
      }
      
     /**
       * Return id
       *
       * @return string
       */
      public function getId() {
          return $this->id;
      }
      
      /**
       * Return parameter value
       *
       * @param String $name
       * @return unknown
       */
      public function getParam($name) {
          try {
             return $this->params->getRecord($name)->get(self::VALUE);
          } catch (Gpf_Data_RecordSetNoRowException $e) {
             return null;
          }
      }
      
      public function setParam($name, $value) {
          self::setValueToRecordset($this->params, $name, $value);
      }
      
      public function loadFromObject(array $object) {
          $this->response->loadFromObject($object);
          $this->params->loadFromObject($object);
      }
          
      /**
       * @return Gpf_Rpc_FilterCollection
       */
      public function getFilters() {
      	return $this->filters;
      }
  
      private static function setValueToRecordset(Gpf_Data_IndexedRecordSet $recordset, $name, $value) {
          try {
             $record = $recordset->getRecord($name);
          } catch (Gpf_Data_RecordSetNoRowException $e) {
             $record = $recordset->createRecord();
             $record->set(self::NAME, $name);
             $recordset->addRecord($record);
          }
          $record->set(self::VALUE, $value);
      }
      
      public function setValue($name, $value) {
          self::setValueToRecordset($this->response, $name, $value);
      }
      
      public function getSize() {
          return $this->response->getSize();
      }
      
      public function getValue($name) {
          try {
              return $this->response->getRecord($name)->get(self::VALUE);
          } catch (Gpf_Data_RecordSetNoRowException $e) {
          }
          return null;
      }
      
      public function toObject() {
      	return $this->response->toObject();
      }
  
      public function toText() {
      	return $this->response->toText();
      }
  
      private function loadParamsFromArray($data) {
          for ($i = 1; $i < count($data); $i++) {
              $this->params->add($data[$i]);
          }
      }
  }

} //end Gpf_Rpc_Data

if (!class_exists('Gpf_Rpc_FilterCollection', false)) {
  class Gpf_Rpc_FilterCollection extends Gpf_Object implements IteratorAggregate {
  
      /**
       * @var array of Gpf_SqlBuilder_Filter
       */
      private $filters;
  
      public function __construct(Gpf_Rpc_Params $params = null) {
          $this->filters = array();
          if ($params != null) {
              $this->init($params);
          }
      }
      
      public function add(array $filterArray) {
      	$this->filters[] = new Gpf_SqlBuilder_Filter($filterArray);
      }
      
      public function loadDefaultFilterCollection($filterType) {
          if ($filterType == '') {
              return;
          }
          
          $filterId = Gpf_Db_Table_Filters::getInstance()->getDefaultFilterId($filterType);
          if ($filterId == '') {
              return;
          }
          $this->loadFilterById($filterId);
      }
      
      public function loadFilterById($filterId) {
          $filters = new Gpf_Db_FilterCondition();
          $filters->setFilterId($filterId);
          $collection = $filters->loadCollection();
          
          foreach ($collection as $filterCondition) {
              if ($filterCondition->get(Gpf_Db_Table_FilterConditions::VALUE) != '') {
                  $this->add(array(
                          Gpf_SqlBuilder_Filter::FILTER_CODE => $filterCondition->get(Gpf_Db_Table_FilterConditions::CODE),
                          Gpf_SqlBuilder_Filter::FILTER_OPERATOR => $filterCondition->get(Gpf_Db_Table_FilterConditions::OPERATOR),
                          Gpf_SqlBuilder_Filter::FILTER_VALUE => $filterCondition->get(Gpf_Db_Table_FilterConditions::VALUE)
                  ));
              }
          }
      }
  
      private function init(Gpf_Rpc_Params $params) {
          $filtersArray = $params->get("filters");
          if (!is_array($filtersArray)) {
              return;
          }
          foreach ($filtersArray as $filterArray) {
              $this->add($filterArray);
          }
      }
  
      /**
       *
       * @return ArrayIterator
       */
      public function getIterator() {
          return new ArrayIterator($this->filters);
      }
  
      public function addTo(Gpf_SqlBuilder_WhereClause $whereClause) {
          foreach ($this->filters as $filter) {
              $filter->addTo($whereClause);
          }
      }
  
      /**
       * Returns first filter with specified code.
       * If filter with specified code does not exists null is returned.
       *
       * @param string $code
       * @return array<Gpf_SqlBuilder_Filter>
       */
      public function getFilter($code) {
      	$filters = array();
          foreach ($this->filters as $filter) {
              if ($filter->getCode() == $code) {
                  $filters[] = $filter;
              }
          }
          return $filters;
      }
      
      public function isFilter($code) {
          foreach ($this->filters as $filter) {
              if ($filter->getCode() == $code) {
                  return true;
              }
          }
          return false;
      }
      
      public function getFilterValue($code) {
          $filters = $this->getFilter($code);
          if (count($filters) == 1) {
              return $filters[0]->getValue();
          }
          return "";
      }
  
      public function matches(Gpf_Data_Record $row) {
          foreach ($this->filters as $filter) {
              if (!$filter->matches($row)) {
                  return false;
              }
          }
          return true;
      }
  
      public function getSize() {
          return count($this->filters);
      }
  }

} //end Gpf_Rpc_FilterCollection

if (!class_exists('Gpf_Rpc_PhpErrorHandler', false)) {
  class Gpf_Rpc_PhpErrorHandler {
      
      private $errorTypes;
      private $callback;
      private $params;
  
      public function handleError($severity, $message, $filename, $lineno) {
          if (error_reporting() == 0) {
              return;
          }
          if (error_reporting() & $severity) {
              $exception = new ErrorException($message, 0, $severity, $filename, $lineno);
              Gpf_Log::warning('Error calling function: ' . $this->getFunctionName($this->callback) . ', with parameters: ' . var_export($this->params, true) . '. Error trace: ' . $exception->getTraceAsString());
              if ($severity != E_WARNING && $severity != E_NOTICE) {
                  throw $exception;
              }
          }
      }
  
      public function callMethod($callback, $params = null, $errorTypes = E_ALL) {
          $this->callback = $callback;
          $this->errorTypes = $errorTypes;
          $this->params = $params;
          $oldErrorHandler = set_error_handler(array(&$this, 'handleError'), $errorTypes);
          try {
              $result = call_user_func_array($callback, $params);
          } catch (ErrorException $e) {
              $result = null;
          }
          set_error_handler($oldErrorHandler);
          return $result;
      }
  
      private function getFunctionName($callback) {
          if (is_array($callback)) {
              return get_class($callback[0]).'->'.$callback[1];
          }
          return $callback;
      }
  }
  

} //end Gpf_Rpc_PhpErrorHandler

if (!class_exists('Gpf_Php', false)) {
  class Gpf_Php {
  
      /**
       * Check if function is enabled and exists in php
       *
       * @param $functionName
       * @return boolean Returns true if function exists and is enabled
       */
      public static function isFunctionEnabled($functionName) {
          if (function_exists($functionName) && strstr(ini_get("disable_functions"), $functionName) === false) {
              return true;
          }
          return false;
      }
      
      /**
       * Check if extension is loaded
       * 
       * @param $extensionName
       * @return boolean Returns true if extension is loaded
       */
      public static function isExtensionLoaded($extensionName) {
          return extension_loaded($extensionName);
      }
  
  }

} //end Gpf_Php

if (!class_exists('Gpf_Rpc_ActionRequest', false)) {
  class Gpf_Rpc_ActionRequest extends Gpf_Rpc_Request {
      
      /**
       * @return Gpf_Rpc_Action
       */
      public function getAction() {
          $action = new Gpf_Rpc_Action(new Gpf_Rpc_Params());
          $action->loadFromObject($this->getStdResponse());
          return $action;        
      }
  }
  

} //end Gpf_Rpc_ActionRequest

if (!class_exists('Gpf_Rpc_Action', false)) {
  class Gpf_Rpc_Action extends Gpf_Object implements Gpf_Rpc_Serializable {
      private $errorMessage = "";
      private $infoMessage = "";
      private $successCount = 0;
      private $errorCount = 0;
      /**
       * @var Gpf_Rpc_Params
       */
      private $params; 
      
      const IDS = 'ids';
      const IDS_REQUEST = 'idsRequest';
      
      public function __construct(Gpf_Rpc_Params $params, $infoMessage = '', $errorMessage = '') {
          $this->params = $params;
          $this->infoMessage = $infoMessage;
          $this->errorMessage = $errorMessage;
      }
  
      public function getIds() {
          if ($this->params->exists(self::IDS)) {
              return new ArrayIterator($this->params->get(self::IDS));
          }
          if ($this->params->exists(self::IDS_REQUEST)) {
              return $this->getRequestIdsIterator();
          }
          throw new Gpf_Exception('No ids selected');
      }
      
      public function getParam($name) {
          return $this->params->get($name);
      }
      
      public function existsParam($name) {
          return $this->params->exists($name);
      }
      
      protected function getRequestIdsIterator() {
          $json = new Gpf_Rpc_Json();
          $requestParams = new Gpf_Rpc_Params($json->decode($this->params->get(self::IDS_REQUEST)));
          $c = $requestParams->getClass();
          $gridService = new $c;
          if(!($gridService instanceof Gpf_View_GridService)) {
              throw new Gpf_Exception(sprintf('%s is not Gpf_View_GridService class.', $requestParams->getClass()));
          }
          return $gridService->getIdsIterator($requestParams);
      }
      
      public function toObject() {
          $response = new stdClass();
          $response->success = Gpf::YES;
          
          $response->errorMessage = "";
          if ($this->errorCount > 0) {
              $response->success = "N";
              $response->errorMessage = $this->_($this->errorMessage, $this->errorCount);
          }
          
          $response->infoMessage = "";
          if ($this->successCount > 0) {
              $response->infoMessage = $this->_($this->infoMessage, $this->successCount);
          }
          
          return $response;
      }
      
      public function loadFromObject(stdClass $object) {
          $this->errorMessage = $object->errorMessage;
          $this->infoMessage = $object->infoMessage;
  
          if($object->success == Gpf::NO) {
              $this->addError();
          }
      }
      
      public function isError() {
          return $this->errorCount > 0;
      }
      
      public function toText() {
          if ($this->isError()) {
              return $this->_($this->errorMessage, $this->errorCount);
          } else {
              return $this->_($this->infoMessage, $this->successCount);
          }
      }
  
      public function setErrorMessage($message) {
          $this->errorMessage = $message;
      }
      
      public function getErrorMessage() {
          return $this->errorMessage;
      }
  
      public function getInfoMessage() {
          return $this->infoMessage;
      }
  
      public function setInfoMessage($message) {
          $this->infoMessage = $message;
      }
  
      public function addOk($count = 1) {
          $this->successCount += $count;
      }
  
      public function addError($count = 1) {
          $this->errorCount += $count;
      }
      
  }
  

} //end Gpf_Rpc_Action

if (!class_exists('Gpf_Rpc_Map', false)) {
  class Gpf_Rpc_Map extends Gpf_Object implements Gpf_Rpc_Serializable {
  
      function __construct(array  $array){
          $this->array = $array;
      }
  
      public function toObject() {
          return $this->array;
      }
  
      public function toText() {
          return var_dump($this->array);
      }
  }
  

} //end Gpf_Rpc_Map

if (!class_exists('Gpf_Log', false)) {
  class Gpf_Log  {
      const CRITICAL = 50;
      const ERROR = 40;
      const WARNING = 30;
      const INFO = 20;
      const DEBUG = 10;
      
      /**
       * @var Gpf_Log_Logger
       */
      private static $logger;
         
      /**
       * @return Gpf_Log_Logger
       */
      private static function getLogger() {
          if (self::$logger == null) {
              self::$logger = Gpf_Log_Logger::getInstance();
          }
          return self::$logger;
      }
      
      private function __construct() {
      }
      
      public static function disableType($type) {
          self::getLogger()->disableType($type);
      }
      
      public static function enableAllTypes() {
          self::getLogger()->enableAllTypes();
      }
      
      /**
       * logs message
       *
       * @param string $message
       * @param string $logLevel
       * @param string $logGroup
       */
      public static function log($message, $logLevel, $logGroup = null) {
          self::getLogger()->log($message, $logLevel, $logGroup);
      }
  
      /**
       * logs debug message
       *
       * @param string $message
       * @param string $logGroup
       */
      public static function debug($message, $logGroup = null) {
          self::getLogger()->debug($message, $logGroup);
      }
          
      /**
       * logs info message
       *
       * @param string $message
       * @param string $logGroup
       */
      public static function info($message, $logGroup = null) {
          self::getLogger()->info($message, $logGroup);
      }
      
      /**
       * logs warning message
       *
       * @param string $message
       * @param string $logGroup
       */
      public static function warning($message, $logGroup = null) {
          self::getLogger()->warning($message, $logGroup);
      }
      
      /**
       * logs error message
       *
       * @param string $message
       * @param string $logGroup
       */
      public static function error($message, $logGroup = null) {
          self::getLogger()->error($message, $logGroup);
      }
  
      /**
       * logs critical error message
       *
       * @param string $message
       * @param string $logGroup
       */
      public static function critical($message, $logGroup = null) {
          self::getLogger()->critical($message, $logGroup);
      }
  
      /**
       * Attach new log system
       *
       * @param string $type 
       *      Gpf_Log_LoggerDisplay::TYPE
       *      Gpf_Log_LoggerFile::TYPE
       *      Gpf_Log_LoggerDatabase::TYPE
       * @param string $logLevel
       *      Gpf_Log::CRITICAL
       *      Gpf_Log::ERROR
       *      Gpf_Log::WARNING
       *      Gpf_Log::INFO
       *      Gpf_Log::DEBUG
       * @return Gpf_Log_LoggerBase
       */
      public static function addLogger($type, $logLevel) {
          if($type instanceof Gpf_Log_LoggerBase) {
              return self::getLogger()->addLogger($type, $logLevel);
          }
          return self::getLogger()->add($type, $logLevel);        
      }
      
      public static function removeAll() {
          self::getLogger()->removeAll();
      }
  
      public static function isLogToDisplay() {
          return self::getLogger()->isLogToDisplay();
      }
  }

} //end Gpf_Log

if (!class_exists('Gpf_Log_Logger', false)) {
  class Gpf_Log_Logger extends Gpf_Object {
      /**
       * @var array
       */
      static private $instances = array();
      /**
       * @var array
       */
      private $loggers = array();
  
      /**
       * array of custom parameters
       */
      private $customParameters = array();
      
      private $disabledTypes = array();
      
      private $group = null;
      private $type = null;
      private $logToDisplay = false;
      
      /**
       * returns instance of logger class.
       * You can add instance name, if you want to have multiple independent instances of logger
       *
       * @param string $instanceName
       * @return Gpf_Log_Logger
       */
      public static function getInstance($instanceName = '_') {
          if($instanceName == '') {
              $instanceName = '_';
          }
  
          if (!array_key_exists($instanceName, self::$instances)) {
              self::$instances[$instanceName] = new Gpf_Log_Logger();
          }
          $instance = self::$instances[$instanceName];
          return $instance;
      }
      
      public static function isLoggerInsert($sqlString) {
          return strpos($sqlString, 'INSERT INTO ' . Gpf_Db_Table_Logs::getName()) !== false;
      }
      
      /**
       * attachs new log system
       *
       * @param unknown_type $system
       * @return Gpf_Log_LoggerBase
       */
      public function add($type, $logLevel) {
      	if($type == Gpf_Log_LoggerDisplay::TYPE) {
      		$this->logToDisplay = true;
      	}
          return $this->addLogger($this->create($type), $logLevel);
      }
  
      /**
       * Checks if logger with te specified type was already initialized
       *
       * @param unknown_type $type
       * @return unknown
       */
      public function checkLoggerTypeExists($type) {
          if(array_key_exists($type, $this->loggers)) {
          	return true;
          }
      	
          return false;
      }
      
      /**
       * returns true if debugging writes log to display
       *
       * @return boolean
       */
      public function isLogToDisplay() {
      	return $this->logToDisplay && !in_array(Gpf_Log_LoggerDisplay::TYPE, $this->disabledTypes);
      }
      
      public function removeAll() {
          $this->loggers = array();
          $this->customParameters = array();
          $this->disabledTypes = array();
          $this->logToDisplay = false;
          $this->group = null;
      }
      
      /**
       *
       * @param Gpf_Log_LoggerBase $logger
       * @param int $logLevel
       * @return Gpf_Log_LoggerBase
       */
      public function addLogger(Gpf_Log_LoggerBase $logger, $logLevel) {
          $this->enableType($logger->getType());
          if($logger->getType() == Gpf_Log_LoggerDisplay::TYPE) {
              $this->logToDisplay = true;
          }
          if(!$this->checkLoggerTypeExists($logger->getType())) {
          	$logger->setLogLevel($logLevel);
          	$this->loggers[$logger->getType()] = $logger;
          	return $logger;
          } else {
          	$ll = new Gpf_Log_LoggerDatabase();
          	$existingLogger = $this->loggers[$logger->getType()];
          	if($existingLogger->getLogLevel() > $logLevel) {
          		$existingLogger->setLogLevel($logLevel);
          	}
          	return $existingLogger;
          }
      }
      
      public function getGroup() {
          return $this->group;
      }
          
      public function setGroup($group = null) {
          $this->group = $group;
          if($group === null) {
              $this->group = Gpf_Common_String::generateId(10);
          }
      }
      
      public function setType($type) {
          $this->type = $type;
      }
      
      /**
       * function sets custom parameter for the logger
       *
       * @param string $name
       * @param string $value
       */
      public function setCustomParameter($name, $value) {
          $this->customParameters[$name] = $value;
      }
  
      /**
       * returns custom parameter
       *
       * @param string $name
       * @return string
       */
      public function getCustomParameter($name) {
          if(isset($this->customParameters[$name])) {
              return $this->customParameters[$name];
          }
          return '';
      }
  
      /**
       * logs message
       *
       * @param string $message
       * @param string $logLevel
       * @param string $logGroup
       */
      public function log($message, $logLevel, $logGroup = null) {
          $time = time();
          $group = $logGroup;
          if($this->group !== null) {
              $group = $this->group;
              if($logGroup !== null) {
                  $group .= ' ' . $logGroup;
              }
          }
  	
          $callingFile = $this->findLogFile();
          $file = $callingFile['file'];
          if(isset($callingFile['classVariables'])) {
          	$file .= ' '.$callingFile['classVariables'];
          }
          $line = $callingFile['line'];
  
          $ip = Gpf_Http::getRemoteIp();
          if ($ip == '') {
              $ip = '127.0.0.1';
          }
  
          foreach ($this->loggers as $logger) {
          	if(!in_array($logger->getType(), $this->disabledTypes)) {
                  $logger->logMessage($time, $message, $logLevel, $group, $ip, $file, $line, $this->type);
              }
          }
      }
      
      /**
       * logs debug message
       *
       * @param string $message
       * @param string $logGroup
       */
      public function debug($message, $logGroup = null) {
          $this->log($message, Gpf_Log::DEBUG, $logGroup);
      }
  
      /**
       * logs info message
       *
       * @param string $message
       * @param string $logGroup
       */
      public function info($message, $logGroup = null) {
          $this->log($message, Gpf_Log::INFO, $logGroup);
      }
  
      /**
       * logs warning message
       *
       * @param string $message
       * @param string $logGroup
       */
      public function warning($message, $logGroup = null) {
          $this->log($message, Gpf_Log::WARNING, $logGroup);
      }
  
      /**
       * logs error message
       *
       * @param string $message
       * @param string $logGroup
       */
      public function error($message, $logGroup = null) {
          $this->log($message, Gpf_Log::ERROR, $logGroup);
      }
  
      /**
       * logs critical error message
       *
       * @param string $message
       * @param string $logGroup
       */
      public function critical($message, $logGroup = null) {
          $this->log($message, Gpf_Log::CRITICAL, $logGroup);
      }
  
      public function disableType($type) {
          $this->disabledTypes[$type] = $type;
      }
  
      public function enableType($type) {
          if(in_array($type, $this->disabledTypes)) {
              unset($this->disabledTypes[$type]);
          }
      }
      
      public function enableAllTypes() {
          $this->disabledTypes = array();
      }
      
      /**
       *
       * @return Gpf_Log_LoggerBase
       */
      private function create($type) {
          switch($type) {
              case Gpf_Log_LoggerDisplay::TYPE:
                  return new Gpf_Log_LoggerDisplay();
              case Gpf_Log_LoggerFile::TYPE:
                  return new Gpf_Log_LoggerFile();
              case Gpf_Log_LoggerDatabase::TYPE:
              case 'db':
                  return new Gpf_Log_LoggerDatabase();
          }
          throw new Gpf_Log_Exception("Log system '$type' does not exist");
      }
      
      private function findLogFile() {
          $calls = debug_backtrace();
          
          $foundObject = null;
          
          // special handling for sql benchmarks
          if($this->sqlBenchmarkFound($calls)) {
              $foundObject = $this->findFileBySqlBenchmark();
          }
  
          if($foundObject == null) {
              $foundObject = $this->findFileByCallingMethod($calls);
          }
          if($foundObject == null) {
              $foundObject = $this->findLatestObjectBeforeString("Logger.class.php");
          }
          if($foundObject == null) {
              $last = count($calls);
              $last -= 1;
              if($last <0) {
                  $last = 0;
              }
          
              $foundObject = $calls[$last];
          }
          
          return $foundObject;
      }
      
      private function sqlBenchmarkFound($calls) {
          foreach($calls as $obj) {
              if(isset($obj['function']) && $obj['function'] == "sqlBenchmarkEnd") {
                  return true;
              }
          }
          return false;
      }
      
      private function findFileBySqlBenchmark() {
          $foundFile = $this->findLatestObjectBeforeString("DbEngine");
          if($foundFile != null && is_object($foundFile['object'])) {
              $foundFile['classVariables'] = $this->getObjectVariables($foundFile['object']);
          }
          return $foundFile;
      }
      
      private function getObjectVariables($object) {
          if(is_object($object)) {
              $class = get_class($object);
              $methods = get_class_methods($class);
              if(in_array("__toString", $methods)) {
                  return $object->__toString();
              }
          }
          return '';
      }
      
      private function findFileByCallingMethod($calls) {
          $functionNames = array('debug', 'info', 'warning', 'error', 'critical', 'log');
          $foundObject = null;
          foreach($functionNames as $name) {
              $foundObject = $this->findCallingFile($calls, $name);
              if($foundObject != null) {
                  return $foundObject;
              }
          }
          
          return null;
      }
      
      private function findCallingFile($calls, $functionName) {
          foreach($calls as $obj) {
              if(isset($obj['function']) && $obj['function'] == $functionName) {
                  return $obj;
              }
          }
          
          return null;
      }
      
      private function findLatestObjectBeforeString($text) {
          $callsReversed = array_reverse( debug_backtrace() );
      
          $lastObject = null;
          foreach($callsReversed as $obj) {
              if(!isset($obj['file'])) {
                  continue;
              }
              $pos = strpos($obj['file'], $text);
              if($pos !== false && $lastObject != null) {
                  return $lastObject;
              }
              $lastObject = $obj;
          }
          return null;
      }
  }

} //end Gpf_Log_Logger

if (!class_exists('Gpf_Api_IncompatibleVersionException', false)) {
  class Gpf_Api_IncompatibleVersionException extends Exception {
  
      private $apiLink;
  
      public function __construct($url) {
          $this->apiLink = $url. '?C=Gpf_Api_DownloadAPI&M=download&FormRequest=Y&FormResponse=Y';
          parent::__construct('Version of API not corresponds to the Application version. Please <a href="' . $this->apiLink . '">download latest version of API</a>.', 0);
      }
      
      public function getApiDownloadLink() {
          return $this->apiLink;
      }
  
  }

} //end Gpf_Api_IncompatibleVersionException

if (!class_exists('Gpf_Api_Session', false)) {
  class Gpf_Api_Session extends Gpf_Object {
      const MERCHANT = 'M';
      const AFFILIATE = 'A';
  
      const AUTHENTICATE_CLASS_NAME = 'Gpf_Api_AuthService';
      const AUTHENTICATE_METHOD_NAME = 'authenticate';
  
  	private $url;
  	private $sessionId = '';
  	private $debug = false;
  	private $message = '';
  	private $roleType = '';
  
  	public function __construct($url) {
  		$this->url = $url;
  	}
  	/**
  	 *
  	 * @param $username
  	 * @param $password
  	 * @param $roleType Gpf_Api_Session::MERCHANT or Gpf_Api_Session::AFFILIATE
  	 * @param $languageCode language code (e.g. en-US, de-DE, sk, cz, du, ...)
  	 * @return boolean true if user was successfully logged
  	 */
      public function login($username, $password, $roleType = self::MERCHANT, $languageCode = null) {
      	return $this->authenticateRequest($username, $password, '', $roleType, $languageCode);
      }
  
  	/**
  	 *
  	 * @param $authtoken
  	 * @param $roleType Gpf_Api_Session::MERCHANT or Gpf_Api_Session::AFFILIATE
  	 * @param $languageCode language code (e.g. en-US, de-DE, sk, cz, du, ...)
  	 * @return boolean true if user was successfully logged
  	 */
      public function loginWithAuthToken($authtoken, $roleType = self::MERCHANT, $languageCode = null) {
          return $this->authenticateRequest('', '', $authtoken, $roleType, $languageCode);
      }
  
  	/**
  	 *
  	 * @param $username
  	 * @param $password
  	 * @param $authtoken
  	 * @param $roleType Gpf_Api_Session::MERCHANT or Gpf_Api_Session::AFFILIATE
  	 * @param $languageCode language code (e.g. en-US, de-DE, sk, cz, du, ...)
  	 * @return boolean true if user was successfully logged
  	 */
      private function authenticateRequest($username, $password, $authtoken, $roleType = self::MERCHANT, $languageCode = null) {
          $request = new Gpf_Rpc_FormRequest($this->getAuthenticateClassName(), self::AUTHENTICATE_METHOD_NAME, $this);
          $request->setUrl($this->url);
          if ($username != '' && $password != '') {
              $request->setField('username', $username);
              $request->setField('password', $password);
          } else {
              $request->setField('authToken', $authtoken);
          }
          $request->setField('roleType', $roleType);
          $request->setField('isFromApi', Gpf::YES);
          $request->setField('apiVersion', self::getAPIVersion());
          if($languageCode != null) {
              $request->setField("language", $languageCode);
          }
  
          $this->roleType = $roleType;
  
          try {
              $request->sendNow();
          } catch(Exception $e) {
              $this->setMessage("Connection error: ".$e->getMessage());
              return false;
          }
  
          $form = $request->getForm();
          $this->checkApiVersion($form);
  
          $this->message = $form->getInfoMessage();
  
          if($form->isSuccessful() && $form->existsField("S")) {
              $this->sessionId = $form->getFieldValue("S");
              $this->setMessage($form->getInfoMessage());
              return true;
          }
  
          $this->setMessage($form->getErrorMessage());
          return false;
      }
  
      /**
       * Get version of installed application
       *
       * @return string version of installed application
       */
      public function getAppVersion() {
          $request = new Gpf_Rpc_FormRequest($this->getAuthenticateClassName(), "getAppVersion");
          $request->setUrl($this->url);
  
          try {
              $request->sendNow();
          } catch(Exception $e) {
              $this->setMessage("Connection error: ".$e->getMessage());
              return false;
          }
  
          $form = $request->getForm();
          return $form->getFieldValue('version');
      }
  
  
  	public function getMessage() {
  		return $this->message;
  	}
  
  	private function setMessage($msg) {
  		$this->message = $msg;
  	}
  
  	public function getDebug() {
  		return $this->debug;
  	}
  
  	public function setDebug($debug = true) {
  		$this->debug = $debug;
  	}
  
  	public function getSessionId() {
  		return $this->sessionId;
  	}
  
      public function setSessionId($sessionId, $roleType = self::MERCHANT) {
          $this->sessionId = $sessionId;
          $this->roleType = $roleType;
      }
  
  	public function getRoleType() {
  		return $this->roleType;
  	}
  
  	public function getUrl() {
  		return $this->url;
  	}
  
  	public function getUrlWithSessionInfo($url) {
  	    if (strpos($url, '?') === false) {
  	        return $url . '?S=' . $this->getSessionId();
  	    }
  	    return $url . '&S=' . $this->getSessionId();
  	}
  
  	protected function getAuthenticateClassName() {
  	    return self::AUTHENTICATE_CLASS_NAME;
  	}
  
  	/**
  	 * Check API version
  	 * (has to be protected because of Drupal integration)
  	 *
  	 * @param $latestVersion
  	 * @throws Gpf_Api_IncompatibleVersionException
  	 */
  	protected function checkApiVersion(Gpf_Rpc_Form $form) {
  		if ($form->getFieldValue('correspondsApi') === Gpf::NO) {
  		    $exception = new Gpf_Api_IncompatibleVersionException($this->url);
  		    trigger_error($exception->getMessage(), E_USER_NOTICE);
  		}
  	}
  
  	/**
  	 * @return String
  	 */
  	public static function getAPIVersion($fileName = __FILE__) {
  		$fileHandler = fopen($fileName, 'r');
  		fseek($fileHandler, -6 -32, SEEK_END);
  		$hash = fgets($fileHandler);
  		return substr($hash, 0, -1);
  	}
  }

} //end Gpf_Api_Session

if (!class_exists('Gpf_Rpc_Json', false)) {
  class Gpf_Rpc_Json implements Gpf_Rpc_DataEncoder, Gpf_Rpc_DataDecoder {
      /**
       * Marker constant for Services_JSON::decode(), used to flag stack state
       */
      const SERVICES_JSON_SLICE = 1;
  
      /**
       * Marker constant for Services_JSON::decode(), used to flag stack state
       */
      const SERVICES_JSON_IN_STR = 2;
  
      /**
       * Marker constant for Services_JSON::decode(), used to flag stack state
       */
      const SERVICES_JSON_IN_ARR = 3;
  
      /**
       * Marker constant for Services_JSON::decode(), used to flag stack state
       */
      const SERVICES_JSON_IN_OBJ = 4;
  
      /**
       * Marker constant for Services_JSON::decode(), used to flag stack state
       */
      const SERVICES_JSON_IN_CMT = 5;
  
      /**
       * Behavior switch for Services_JSON::decode()
       */
      const SERVICES_JSON_LOOSE_TYPE = 16;
  
      /**
       * Behavior switch for Services_JSON::decode()
       */
      const SERVICES_JSON_SUPPRESS_ERRORS = 32;
      
      /**
       * @var Gpf_Rpc_Json
       */
      private static $instance;
  
      /**
       * constructs a new JSON instance
       *
       * @param    int     $use    object behavior flags; combine with boolean-OR
       *
       *                           possible values:
       *                           - SERVICES_JSON_LOOSE_TYPE:  loose typing.
       *                                   "{...}" syntax creates associative arrays
       *                                   instead of objects in decode().
       *                           - SERVICES_JSON_SUPPRESS_ERRORS:  error suppression.
       *                                   Values which can't be encoded (e.g. resources)
       *                                   appear as NULL instead of throwing errors.
       *                                   By default, a deeply-nested resource will
       *                                   bubble up with an error, so all return values
       *                                   from encode() should be checked with isError()
       */
      function __construct($use = 0)
      {
          $this->use = $use;
      }
      
      
  
      /**
       * convert a string from one UTF-16 char to one UTF-8 char
       *
       * Normally should be handled by mb_convert_encoding, but
       * provides a slower PHP-only method for installations
       * that lack the multibye string extension.
       *
       * @param    string  $utf16  UTF-16 character
       * @return   string  UTF-8 character
       * @access   private
       */
      function utf162utf8($utf16)
      {
          // oh please oh please oh please oh please oh please
          if(Gpf_Php::isFunctionEnabled('mb_convert_encoding')) {
              return mb_convert_encoding($utf16, 'UTF-8', 'UTF-16');
          }
  
          $bytes = (ord($utf16{0}) << 8) | ord($utf16{1});
  
          switch(true) {
              case ((0x7F & $bytes) == $bytes):
                  // this case should never be reached, because we are in ASCII range
                  // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                  return chr(0x7F & $bytes);
  
              case (0x07FF & $bytes) == $bytes:
                  // return a 2-byte UTF-8 character
                  // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                  return chr(0xC0 | (($bytes >> 6) & 0x1F))
                  . chr(0x80 | ($bytes & 0x3F));
  
              case (0xFFFF & $bytes) == $bytes:
                  // return a 3-byte UTF-8 character
                  // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                  return chr(0xE0 | (($bytes >> 12) & 0x0F))
                  . chr(0x80 | (($bytes >> 6) & 0x3F))
                  . chr(0x80 | ($bytes & 0x3F));
          }
  
          // ignoring UTF-32 for now, sorry
          return '';
      }
  
      /**
       * convert a string from one UTF-8 char to one UTF-16 char
       *
       * Normally should be handled by mb_convert_encoding, but
       * provides a slower PHP-only method for installations
       * that lack the multibye string extension.
       *
       * @param    string  $utf8   UTF-8 character
       * @return   string  UTF-16 character
       * @access   private
       */
      function utf82utf16($utf8)
      {
          // oh please oh please oh please oh please oh please
          if(Gpf_Php::isFunctionEnabled('mb_convert_encoding')) {
              return mb_convert_encoding($utf8, 'UTF-16', 'UTF-8');
          }
  
          switch(strlen($utf8)) {
              case 1:
                  // this case should never be reached, because we are in ASCII range
                  // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                  return $utf8;
  
              case 2:
                  // return a UTF-16 character from a 2-byte UTF-8 char
                  // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                  return chr(0x07 & (ord($utf8{0}) >> 2))
                  . chr((0xC0 & (ord($utf8{0}) << 6))
                  | (0x3F & ord($utf8{1})));
  
              case 3:
                  // return a UTF-16 character from a 3-byte UTF-8 char
                  // see: http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                  return chr((0xF0 & (ord($utf8{0}) << 4))
                  | (0x0F & (ord($utf8{1}) >> 2)))
                  . chr((0xC0 & (ord($utf8{1}) << 6))
                  | (0x7F & ord($utf8{2})));
          }
  
          // ignoring UTF-32 for now, sorry
          return '';
      }
  
      public function encodeResponse(Gpf_Rpc_Serializable $response) {
          return $this->encode($response->toObject());
      }
  
      /**
       * encodes an arbitrary variable into JSON format
       *
       * @param    mixed   $var    any number, boolean, string, array, or object to be encoded.
       *                           see argument 1 to Services_JSON() above for array-parsing behavior.
       *                           if var is a strng, note that encode() always expects it
       *                           to be in ASCII or UTF-8 format!
       *
       * @return   mixed   JSON string representation of input var or an error if a problem occurs
       * @access   public
       */
      public function encode($var, $options = null) {
          if ($this->isJsonEncodeEnabled()) {
              return @json_encode($var, $options);
          }
          switch (gettype($var)) {
              case 'boolean':
                  return $var ? 'true' : 'false';
  
              case 'NULL':
                  return 'null';
  
              case 'integer':
                  return (int) $var;
  
              case 'double':
              case 'float':
                  return (float) $var;
  
              case 'string':
                  // STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
                  $ascii = '';
                  $strlen_var = strlen($var);
  
                  /*
                   * Iterate over every character in the string,
                   * escaping with a slash or encoding to UTF-8 where necessary
                   */
                  for ($c = 0; $c < $strlen_var; ++$c) {
  
                      $ord_var_c = ord($var{$c});
  
                      switch (true) {
                          case $ord_var_c == 0x08:
                              $ascii .= '\b';
                              break;
                          case $ord_var_c == 0x09:
                              $ascii .= '\t';
                              break;
                          case $ord_var_c == 0x0A:
                              $ascii .= '\n';
                              break;
                          case $ord_var_c == 0x0C:
                              $ascii .= '\f';
                              break;
                          case $ord_var_c == 0x0D:
                              $ascii .= '\r';
                              break;
  
                          case $ord_var_c == 0x22:
                          case $ord_var_c == 0x2F:
                          case $ord_var_c == 0x5C:
                              // double quote, slash, slosh
                              if ($options == JSON_UNESCAPED_SLASHES && $ord_var_c == 0x2F) {
                                  $ascii .= $var{$c};
                              } else {
                                  $ascii .= '\\'.$var{$c};
                              }
                              break;
  
                          case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
                              // characters U-00000000 - U-0000007F (same as ASCII)
                              $ascii .= $var{$c};
                              break;
  
                          case (($ord_var_c & 0xE0) == 0xC0):
                              // characters U-00000080 - U-000007FF, mask 1 1 0 X X X X X
                              // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                              $char = pack('C*', $ord_var_c, ord($var{$c + 1}));
                              $c += 1;
                              $utf16 = $this->utf82utf16($char);
                              $ascii .= sprintf('\u%04s', bin2hex($utf16));
                              break;
  
                          case (($ord_var_c & 0xF0) == 0xE0):
                              // characters U-00000800 - U-0000FFFF, mask 1 1 1 0 X X X X
                              // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                              $char = pack('C*', $ord_var_c,
                              ord($var{$c + 1}),
                              ord($var{$c + 2}));
                              $c += 2;
                              $utf16 = $this->utf82utf16($char);
                              $ascii .= sprintf('\u%04s', bin2hex($utf16));
                              break;
  
                          case (($ord_var_c & 0xF8) == 0xF0):
                              // characters U-00010000 - U-001FFFFF, mask 1 1 1 1 0 X X X
                              // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                              $char = pack('C*', $ord_var_c,
                              ord($var{$c + 1}),
                              ord($var{$c + 2}),
                              ord($var{$c + 3}));
                              $c += 3;
                              $utf16 = $this->utf82utf16($char);
                              $ascii .= sprintf('\u%04s', bin2hex($utf16));
                              break;
  
                          case (($ord_var_c & 0xFC) == 0xF8):
                              // characters U-00200000 - U-03FFFFFF, mask 111110XX
                              // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                              $char = pack('C*', $ord_var_c,
                              ord($var{$c + 1}),
                              ord($var{$c + 2}),
                              ord($var{$c + 3}),
                              ord($var{$c + 4}));
                              $c += 4;
                              $utf16 = $this->utf82utf16($char);
                              $ascii .= sprintf('\u%04s', bin2hex($utf16));
                              break;
  
                          case (($ord_var_c & 0xFE) == 0xFC):
                              // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                              // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                              $char = pack('C*', $ord_var_c,
                              ord($var{$c + 1}),
                              ord($var{$c + 2}),
                              ord($var{$c + 3}),
                              ord($var{$c + 4}),
                              ord($var{$c + 5}));
                              $c += 5;
                              $utf16 = $this->utf82utf16($char);
                              $ascii .= sprintf('\u%04s', bin2hex($utf16));
                              break;
                      }
                  }
  
                  return '"'.$ascii.'"';
  
                          case 'array':
                              /*
                               * As per JSON spec if any array key is not an integer
                               * we must treat the the whole array as an object. We
                               * also try to catch a sparsely populated associative
                               * array with numeric keys here because some JS engines
                               * will create an array with empty indexes up to
                               * max_index which can cause memory issues and because
                               * the keys, which may be relevant, will be remapped
                               * otherwise.
                               *
                               * As per the ECMA and JSON specification an object may
                               * have any string as a property. Unfortunately due to
                               * a hole in the ECMA specification if the key is a
                               * ECMA reserved word or starts with a digit the
                               * parameter is only accessible using ECMAScript's
                               * bracket notation.
                               */
  
                              // treat as a JSON object
                              if (is_array($var) && count($var) && (array_keys($var) !== range(0, sizeof($var) - 1))) {
                                  $optionsArray = array();
                                  for ($i = 0; $i < count($var); $i++) {
                                      $optionsArray[] = $options;
                                  }
                                  $properties = array_map(array($this, 'name_value'), array_keys($var), array_values($var), $optionsArray);
  
                                  foreach($properties as $property) {
                                      if(Gpf_Rpc_Json::isError($property)) {
                                          return $property;
                                      }
                                  }
  
                                  return '{' . join(',', $properties) . '}';
                              }
                              
                              $optionsArray = array();
                              for ($i = 0; $i < count($var); $i++) {
                                  $optionsArray[] = $options;
                              }
  
                              // treat it like a regular array
                              $elements = array_map(array($this, 'encode'), $var, $optionsArray);
  
                              foreach($elements as $element) {
                                  if(Gpf_Rpc_Json::isError($element)) {
                                      return $element;
                                  }
                              }
  
                              return '[' . join(',', $elements) . ']';
  
                          case 'object':
                              $vars = get_object_vars($var);
                              $optionsArray = array();
                              for ($i = 0; $i < count($vars); $i++) {
                                  $optionsArray[] = $options;
                              }
                              $properties = array_map(array($this, 'name_value'),
                              array_keys($vars),
                              array_values($vars), 
                              $optionsArray);
  
                              foreach($properties as $property) {
                                  if(Gpf_Rpc_Json::isError($property)) {
                                      return $property;
                                  }
                              }
  
                              return '{' . join(',', $properties) . '}';
  
                          default:
                              if ($this->use & self::SERVICES_JSON_SUPPRESS_ERRORS) {
                                  return 'null';
                              }
                              return new Gpf_Rpc_Json_Error(gettype($var)." can not be encoded as JSON string");
          }
      }
  
      /**
       * array-walking function for use in generating JSON-formatted name-value pairs
       *
       * @param    string  $name   name of key to use
       * @param    mixed   $value  reference to an array element to be encoded
       *
       * @return   string  JSON-formatted name-value pair, like '"name":value'
       * @access   private
       */
      function name_value($name, $value, $options = null)
      {
          $encoded_value = $this->encode($value, $options);
  
          if(Gpf_Rpc_Json::isError($encoded_value)) {
              return $encoded_value;
          }
  
          return $this->encode(strval($name), $options) . ':' . $encoded_value;
      }
  
      /**
       * reduce a string by removing leading and trailing comments and whitespace
       *
       * @param    $str    string      string value to strip of comments and whitespace
       *
       * @return   string  string value stripped of comments and whitespace
       * @access   private
       */
      function reduce_string($str)
      {
          $str = preg_replace(array(
  
          // eliminate single line comments in '// ...' form
                  '#^\s*//(.+)$#m',
  
          // eliminate multi-line comments in '/* ... */' form, at start of string
                  '#^\s*/\*(.+)\*/#Us',
  
          // eliminate multi-line comments in '/* ... */' form, at end of string
                  '#/\*(.+)\*/\s*$#Us'
  
                  ), '', $str);
  
                  // eliminate extraneous space
                  return trim($str);
      }
  
      /**
       * decodes a JSON string into appropriate variable
       *
       * @param    string  $str    JSON-formatted string
       *
       * @return   mixed   number, boolean, string, array, or object
       *                   corresponding to given JSON input string.
       *                   See argument 1 to Services_JSON() above for object-output behavior.
       *                   Note that decode() always returns strings
       *                   in ASCII or UTF-8 format!
       * @access   public
       */
  
      public function decode($str) {
          if ($this->isJsonDecodeEnabled()) {
              $errorHandler = new Gpf_Rpc_PhpErrorHandler();
              $response = $errorHandler->callMethod('json_decode', array($str));
              return $response;
          }
  
          $str = $this->reduce_string($str);
  
          switch (strtolower($str)) {
              case 'true':
                  return true;
  
              case 'false':
                  return false;
  
              case 'null':
                  return null;
  
              default:
                  $m = array();
  
                  if (is_numeric($str)) {
                      // Lookie-loo, it's a number
  
                      // This would work on its own, but I'm trying to be
                      // good about returning integers where appropriate:
                      // return (float)$str;
  
                      // Return float or int, as appropriate
                      return ((float)$str == (integer)$str)
                      ? (integer)$str
                      : (float)$str;
  
                  } elseif (preg_match('/^("|\').*(\1)$/s', $str, $m) && $m[1] == $m[2]) {
                      // STRINGS RETURNED IN UTF-8 FORMAT
                      $delim = substr($str, 0, 1);
                      $chrs = substr($str, 1, -1);
                      $utf8 = '';
                      $strlen_chrs = strlen($chrs);
  
                      for ($c = 0; $c < $strlen_chrs; ++$c) {
  
                          $substr_chrs_c_2 = substr($chrs, $c, 2);
                          $ord_chrs_c = ord($chrs{$c});
  
                          switch (true) {
                              case $substr_chrs_c_2 == '\b':
                                  $utf8 .= chr(0x08);
                                  ++$c;
                                  break;
                              case $substr_chrs_c_2 == '\t':
                                  $utf8 .= chr(0x09);
                                  ++$c;
                                  break;
                              case $substr_chrs_c_2 == '\n':
                                  $utf8 .= chr(0x0A);
                                  ++$c;
                                  break;
                              case $substr_chrs_c_2 == '\f':
                                  $utf8 .= chr(0x0C);
                                  ++$c;
                                  break;
                              case $substr_chrs_c_2 == '\r':
                                  $utf8 .= chr(0x0D);
                                  ++$c;
                                  break;
  
                              case $substr_chrs_c_2 == '\\"':
                              case $substr_chrs_c_2 == '\\\'':
                              case $substr_chrs_c_2 == '\\\\':
                              case $substr_chrs_c_2 == '\\/':
                                  if (($delim == '"' && $substr_chrs_c_2 != '\\\'') ||
                                  ($delim == "'" && $substr_chrs_c_2 != '\\"')) {
                                      $utf8 .= $chrs{++$c};
                                  }
                                  break;
  
                              case preg_match('/\\\u[0-9A-F]{4}/i', substr($chrs, $c, 6)):
                                  // single, escaped unicode character
                                  $utf16 = chr(hexdec(substr($chrs, ($c + 2), 2)))
                                  . chr(hexdec(substr($chrs, ($c + 4), 2)));
                                  $utf8 .= $this->utf162utf8($utf16);
                                  $c += 5;
                                  break;
  
                              case ($ord_chrs_c >= 0x20) && ($ord_chrs_c <= 0x7F):
                                  $utf8 .= $chrs{$c};
                                  break;
  
                              case ($ord_chrs_c & 0xE0) == 0xC0:
                                  // characters U-00000080 - U-000007FF, mask 1 1 0 X X X X X
                                  //see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                  $utf8 .= substr($chrs, $c, 2);
                                  ++$c;
                                  break;
  
                              case ($ord_chrs_c & 0xF0) == 0xE0:
                                  // characters U-00000800 - U-0000FFFF, mask 1 1 1 0 X X X X
                                  // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                  $utf8 .= substr($chrs, $c, 3);
                                  $c += 2;
                                  break;
  
                              case ($ord_chrs_c & 0xF8) == 0xF0:
                                  // characters U-00010000 - U-001FFFFF, mask 1 1 1 1 0 X X X
                                  // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                  $utf8 .= substr($chrs, $c, 4);
                                  $c += 3;
                                  break;
  
                              case ($ord_chrs_c & 0xFC) == 0xF8:
                                  // characters U-00200000 - U-03FFFFFF, mask 111110XX
                                  // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                  $utf8 .= substr($chrs, $c, 5);
                                  $c += 4;
                                  break;
  
                              case ($ord_chrs_c & 0xFE) == 0xFC:
                                  // characters U-04000000 - U-7FFFFFFF, mask 1111110X
                                  // see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
                                  $utf8 .= substr($chrs, $c, 6);
                                  $c += 5;
                                  break;
  
                          }
  
                      }
  
                      return $utf8;
  
                  } elseif (preg_match('/^\[.*\]$/s', $str) || preg_match('/^\{.*\}$/s', $str)) {
                      // array, or object notation
  
                      if ($str{0} == '[') {
                          $stk = array(self::SERVICES_JSON_IN_ARR);
                          $arr = array();
                      } else {
                          if ($this->use & self::SERVICES_JSON_LOOSE_TYPE) {
                              $stk = array(self::SERVICES_JSON_IN_OBJ);
                              $obj = array();
                          } else {
                              $stk = array(self::SERVICES_JSON_IN_OBJ);
                              $obj = new stdClass();
                          }
                      }
  
                      array_push($stk, array('what'  => self::SERVICES_JSON_SLICE,
                                             'where' => 0,
                                             'delim' => false));
  
                      $chrs = substr($str, 1, -1);
                      $chrs = $this->reduce_string($chrs);
  
                      if ($chrs == '') {
                          if (reset($stk) == self::SERVICES_JSON_IN_ARR) {
                              return $arr;
  
                          } else {
                              return $obj;
  
                          }
                      }
  
                      //print("\nparsing {$chrs}\n");
  
                      $strlen_chrs = strlen($chrs);
  
                      for ($c = 0; $c <= $strlen_chrs; ++$c) {
  
                          $top = end($stk);
                          $substr_chrs_c_2 = substr($chrs, $c, 2);
  
                          if (($c == $strlen_chrs) || (($chrs{$c} == ',') && ($top['what'] == self::SERVICES_JSON_SLICE))) {
                              // found a comma that is not inside a string, array, etc.,
                              // OR we've reached the end of the character list
                              $slice = substr($chrs, $top['where'], ($c - $top['where']));
                              array_push($stk, array('what' => self::SERVICES_JSON_SLICE, 'where' => ($c + 1), 'delim' => false));
                              //print("Found split at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");
  
                              if (reset($stk) == self::SERVICES_JSON_IN_ARR) {
                                  // we are in an array, so just push an element onto the stack
                                  array_push($arr, $this->decode($slice));
  
                              } elseif (reset($stk) == self::SERVICES_JSON_IN_OBJ) {
                                  // we are in an object, so figure
                                  // out the property name and set an
                                  // element in an associative array,
                                  // for now
                                  $parts = array();
  
                                  if (preg_match('/^\s*(["\'].*[^\\\]["\'])\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                                      // "name":value pair
                                      $key = $this->decode($parts[1]);
                                      $val = $this->decode($parts[2]);
  
                                      if ($this->use & self::SERVICES_JSON_LOOSE_TYPE) {
                                          $obj[$key] = $val;
                                      } else {
                                          $obj->$key = $val;
                                      }
                                  } elseif (preg_match('/^\s*(\w+)\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
                                      // name:value pair, where name is unquoted
                                      $key = $parts[1];
                                      $val = $this->decode($parts[2]);
  
                                      if ($this->use & self::SERVICES_JSON_LOOSE_TYPE) {
                                          $obj[$key] = $val;
                                      } else {
                                          $obj->$key = $val;
                                      }
                                  }
  
                              }
  
                          } elseif ((($chrs{$c} == '"') || ($chrs{$c} == "'")) && ($top['what'] != self::SERVICES_JSON_IN_STR)) {
                              // found a quote, and we are not inside a string
                              array_push($stk, array('what' => self::SERVICES_JSON_IN_STR, 'where' => $c, 'delim' => $chrs{$c}));
                              //print("Found start of string at {$c}\n");
  
                          } elseif (($chrs{$c} == $top['delim']) &&
                          ($top['what'] == self::SERVICES_JSON_IN_STR) &&
                          (($chrs{$c - 1} != '\\') ||
                          ($chrs{$c - 1} == '\\' && $chrs{$c - 2} == '\\'))) {
                              // found a quote, we're in a string, and it's not escaped
                              array_pop($stk);
                              //print("Found end of string at {$c}: ".substr($chrs, $top['where'], (1 + 1 + $c - $top['where']))."\n");
  
                          } elseif (($chrs{$c} == '[') &&
                          in_array($top['what'], array(self::SERVICES_JSON_SLICE, self::SERVICES_JSON_IN_ARR, self::SERVICES_JSON_IN_OBJ))) {
                              // found a left-bracket, and we are in an array, object, or slice
                              array_push($stk, array('what' => self::SERVICES_JSON_IN_ARR, 'where' => $c, 'delim' => false));
                              //print("Found start of array at {$c}\n");
  
                          } elseif (($chrs{$c} == ']') && ($top['what'] == self::SERVICES_JSON_IN_ARR)) {
                              // found a right-bracket, and we're in an array
                              array_pop($stk);
                              //print("Found end of array at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");
  
                          } elseif (($chrs{$c} == '{') &&
                          in_array($top['what'], array(self::SERVICES_JSON_SLICE, self::SERVICES_JSON_IN_ARR, self::SERVICES_JSON_IN_OBJ))) {
                              // found a left-brace, and we are in an array, object, or slice
                              array_push($stk, array('what' => self::SERVICES_JSON_IN_OBJ, 'where' => $c, 'delim' => false));
                              //print("Found start of object at {$c}\n");
  
                          } elseif (($chrs{$c} == '}') && ($top['what'] == self::SERVICES_JSON_IN_OBJ)) {
                              // found a right-brace, and we're in an object
                              array_pop($stk);
                              //print("Found end of object at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");
  
                          } elseif (($substr_chrs_c_2 == '/*') &&
                          in_array($top['what'], array(self::SERVICES_JSON_SLICE, self::SERVICES_JSON_IN_ARR, self::SERVICES_JSON_IN_OBJ))) {
                              // found a comment start, and we are in an array, object, or slice
                              array_push($stk, array('what' => self::SERVICES_JSON_IN_CMT, 'where' => $c, 'delim' => false));
                              $c++;
                              //print("Found start of comment at {$c}\n");
  
                          } elseif (($substr_chrs_c_2 == '*/') && ($top['what'] == self::SERVICES_JSON_IN_CMT)) {
                              // found a comment end, and we're in one now
                              array_pop($stk);
                              $c++;
  
                              for ($i = $top['where']; $i <= $c; ++$i)
                              $chrs = substr_replace($chrs, ' ', $i, 1);
  
                              //print("Found end of comment at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");
  
                          }
  
                      }
  
                      if (reset($stk) == self::SERVICES_JSON_IN_ARR) {
                          return $arr;
  
                      } elseif (reset($stk) == self::SERVICES_JSON_IN_OBJ) {
                          return $obj;
  
                      }
  
                  }
          }
      }
      
      protected function isJsonEncodeEnabled() {
          return Gpf_Php::isFunctionEnabled('json_encode');
      }
      
      protected function isJsonDecodeEnabled() {
          return Gpf_Php::isFunctionEnabled('json_decode');
      }
      
  
      /**
       * @todo Ultimately, this should just call PEAR::isError()
       */
      function isError($data, $code = null)
      {
          if (is_object($data) &&
              (get_class($data) == 'Gpf_Rpc_Json_Error' || is_subclass_of($data, 'Gpf_Rpc_Json_Error'))) {
                  return true;
          }
          return false;
      }
      
      public static function encodeStatic($var, $options = null) {
          return self::getInstance()->encode($var, $options);
      }
      
      public static function decodeStatic($var) {
          return self::getInstance()->decode($var);
      }
      
      private static function getInstance() {
          if (self::$instance === null) {
              self::$instance = new self;
          }
          return self::$instance;
      }
  }
  
  class Gpf_Rpc_Json_Error {
      private $message;
      
      public function __construct($message) {
          $this->message = $message;
      }
  }
  

} //end Gpf_Rpc_Json

if (!class_exists('Gpf_Rpc_JsonObject', false)) {
  class Gpf_Rpc_JsonObject extends Gpf_Object {
      
      public function __construct($object = null) {
          if ($object != null) {
              $this->initFrom($object);
          }
      }
      
      public function decode($string) {
          if ($string == null || $string == "") {
              throw new Gpf_Exception("Invalid format (".get_class($this).")");
          }
          $string = stripslashes($string);
          $json = new Gpf_Rpc_Json();
          $object = $json->decode($string);
          if (!is_object($object)) {
              throw new Gpf_Exception("Invalid format (".get_class($this).")");
          }
          $this->initFrom($object);
      }
      
      private function initFrom($object) {
          $object_vars = get_object_vars($object);
          foreach ($object_vars as $name => $value) {
              if (property_exists($this, $name)) {
                  $this->$name = $value;
              }
          }
      }
      
      public function encode() {
          $json = new Gpf_Rpc_Json();
          return $json->encode($this);
      }
      
      public function __toString() {
          return $this->encode();
      }
  }

} //end Gpf_Rpc_JsonObject

if (!class_exists('Pap_Api_Object', false)) {
  class Pap_Api_Object extends Gpf_Object {
      private $session;
      protected $class = '';
      private $message = '';
  
      const FIELD_NAME  = "name";
      const FIELD_VALUE = "value";
      const FIELD_ERROR = "error";
      const FIELD_VALUES = "values";
      const FIELD_OPERATOR = 'operator';
      
      const OPERATOR_EQUALS = '=';
      const OPERATOR_LIKE = 'L';
  
      /**
       * @var Gpf_Data_IndexedRecordSet
       */
      private $fields;
  
      public function __construct(Gpf_Api_Session $session) {
          $this->session = $session;
          $this->fields = new Gpf_Data_IndexedRecordSet(self::FIELD_NAME);
  
          $header = new Gpf_Data_RecordHeader();
          $header->add(self::FIELD_NAME);
          $header->add(self::FIELD_VALUE);
          $header->add(self::FIELD_VALUES);
          $header->add(self::FIELD_OPERATOR);
          $header->add(self::FIELD_ERROR);
  
          $this->fields->setHeader($header);
      }
  
      public function setField($name, $value, $operator = self::OPERATOR_EQUALS) {
          $record = $this->fields->createRecord($name);
          $record->set(self::FIELD_VALUE, $value);
          $record->set(self::FIELD_OPERATOR, $operator);
  
          $this->fields->add($record);
      }
  
      public function getField($name) {
         	try {
         	    $record = $this->fields->getRecord($name);
         	    return $record->get(self::FIELD_VALUE);
         	} catch(Exception $e) {
         	    return null;
         	}
      }
  
      public function addErrorMessages(Gpf_Data_IndexedRecordSet $fields) {
          foreach($fields as $field) {
              if($field->get(self::FIELD_ERROR) != '') {
                  $this->message .= '<br>'.$field->get(self::FIELD_NAME).' - '.$field->get(self::FIELD_ERROR);
              }
          }
      }
  
      public function setFields(Gpf_Data_IndexedRecordSet $fields) {
          foreach($fields as $field) {
              $this->setField($field->get(self::FIELD_NAME), $field->get(self::FIELD_VALUE));
          }
      }
  
      public function getFields() {
          return $this->fields;
      }
  
      public function getSession() {
          return $this->session;
      }
  
      public function getMessage() {
          return $this->message;
      }
  
      protected function getPrimaryKey() {
          throw new Exception("You have to define method getPrimaryKey() in the extended class!");
      }
  
      protected function getGridRequest() {
          throw new Exception("You have to define method getGridRequest() in the extended class!");
      }
  
      protected function fillFieldsToGridRequest($request) {
          foreach($this->fields as $field) {
              if($field->get(self::FIELD_VALUE) != '') {
                  $request->addFilter($field->get(self::FIELD_NAME), $field->get(self::FIELD_OPERATOR), $field->get(self::FIELD_VALUE));
              }
          }
      }
  
      protected function getPrimaryKeyFromFields() {
          $request = $this->getGridRequest();
          if($request == null) {
              throw new Exception("You have to set ".$this->getPrimaryKey()." before calling load()!");
          }
  
          $this->fillFieldsToGridRequest($request);
  
          $request->setLimit(0, 1);
          $request->sendNow();
          $grid = $request->getGrid();
          if($grid->getTotalCount() == 0) {
              throw new Exception("No rows found!");
          }
          if($grid->getTotalCount() > 1) {
              throw new Exception("Too many rows found!");
          }
          $recordset = $grid->getRecordset();
  
          foreach($recordset as $record) {
              $this->setField($this->getPrimaryKey(), $record->get($this->getPrimaryKey()));
              break;
          }
      }
  
      protected function afterCallRequest() {
      }
  
      private function primaryKeyIsDefined() {
          $field =  $this->getField($this->getPrimaryKey());
          if($field == null || $field == '') {
              return false;
          }
          return true;
      }
  
      /**
       * function checks if at least some field is filled
       * (we'll use that field as filter for the grid)
       *
       */
      private function someFieldIsFilled() {
          foreach($this->fields as $field) {
              if($field->get(self::FIELD_VALUE) != '') {
                  return true;
              }
          }
  
          return false;
      }
  
      private function callRequest($method) {
          $this->message = '';
  
          $request = new Gpf_Rpc_FormRequest($this->class, $method, $this->session);
          $this->beforeCallRequest($request);
          foreach($this->getFields() as $field) {
              if($field->get(self::FIELD_VALUE) !== null) {
                  $request->setField($field->get(self::FIELD_NAME), $field->get(self::FIELD_VALUE));
              }
          }
  
          try {
              $request->sendNow();
          } catch(Gpf_Exception $e) {
              if(strpos($e->getMessage(), 'Row does not exist') !== false) {
                  throw new Exception("Row with this ID does not exist");
              }
          }
  
          $form = $request->getForm();
          if($form->isError()) {
              $this->message = $form->getErrorMessage();
              $this->addErrorMessages($form->getFields());
              return false;
          } else {
              $this->message = $form->getInfoMessage();
          }
  
          $this->setFields($form->getFields());
  
          $this->afterCallRequest();
  
          return true;
      }
  
      /**
       * @throws Exception
       */
      public function load() {
          if(!$this->primaryKeyIsDefined()) {
              if($this->getGridRequest() == null) {
                  throw new Exception("You have to set ".$this->getPrimaryKey()." before calling load()!");
              }
  
              if(!$this->someFieldIsFilled()) {
                  throw new Exception("You have to set at least one field before calling load()!");
              }
  
              $this->getPrimaryKeyFromFields();
          }
  
          $this->setField("Id", $this->getField($this->getPrimaryKey()));
  
          return $this->callRequest("load");
      }
  
      /**
       * @throws Exception
       */
      public function save() {
          if(!$this->primaryKeyIsDefined()) {
              throw new Exception("You have to set ".$this->getPrimaryKey()." before calling save()!");
          }
          $this->setField("Id", $this->getField($this->getPrimaryKey()));
  
          return $this->callRequest("save");
      }
  
      public function add() {
          $this->fillEmptyRecord();
  
          return $this->callRequest("add");
      }
      
      protected function fillEmptyRecord() {
      }
  
      protected function beforeCallRequest(Gpf_Rpc_FormRequest $request) {
      }
  }

} //end Pap_Api_Object

if (!class_exists('Pap_Api_AffiliatesGrid', false)) {
  class Pap_Api_AffiliatesGrid extends Gpf_Rpc_GridRequest {
  	
  	private $dataValues = null;
  	
      public function __construct(Gpf_Api_Session $session) {
          if($session->getRoleType() == Gpf_Api_Session::AFFILIATE) {
      		throw new Exception("This class can be used only by merchant!");
      	} else {
      		parent::__construct("Pap_Merchants_User_AffiliatesGrid", "getRows", $session);
      	}
      }
  }

} //end Pap_Api_AffiliatesGrid

if (!class_exists('Pap_Api_BannersGrid', false)) {
  class Pap_Api_BannersGrid extends Gpf_Rpc_GridRequest {
  	
      public function __construct(Gpf_Api_Session $session) {
          if($session->getRoleType() == Gpf_Api_Session::AFFILIATE) {
      		throw new Exception("This class can be used only by merchant!");
      	} else {
      		parent::__construct("Pap_Merchants_Banner_BannersGrid", "getRows", $session);
      	}
      }
  }

} //end Pap_Api_BannersGrid

if (!class_exists('Pap_Api_Affiliate', false)) {
  class Pap_Api_Affiliate extends Pap_Api_Object {
  
      private $dataValues = null;
  
      public function __construct(Gpf_Api_Session $session) {
          if($session->getRoleType() == Gpf_Api_Session::AFFILIATE) {
              $this->class = "Pap_Affiliates_Profile_PersonalDetailsForm";
          } else {
              $this->class = "Pap_Signup_AffiliateForm";
          }
           
          parent::__construct($session);
  
          $this->getDataFields();
      }
  
      public function getUserid() { return $this->getField("userid"); }
      public function setUserid($value) {
          $this->setField("userid", $value);
          $this->setField("Id", $value);
      }
  
      public function getRefid() { return $this->getField("refid"); }
  
      public function setRefid($value, $operator = self::OPERATOR_EQUALS) { 
          $this->setField('refid', $value, $operator);
      }
  
      public function getStatus() { return $this->getField("rstatus"); }
      public function setStatus($value) { $this->setField("rstatus", $value); }
  
      public function getMinimumPayout() { return $this->getField("minimumpayout"); }
      public function setMinimumPayout($value) { $this->setField("minimumpayout", $value); }
  
      public function getPayoutOptionId() { return $this->getField("payoutoptionid"); }
      public function setPayoutOptionId($value) { $this->setField("payoutoptionid", $value); }
  
      public function getNote() { return $this->getField("note"); }
      public function setNote($value) { $this->setField("note", $value); }
  
      public function getPhoto() { return $this->getField("photo"); }
      public function setPhoto($value) { $this->setField("photo", $value); }
  
      public function getAuthToken() { return $this->getField('authtoken'); }
  
      public function getUsername() { return $this->getField("username"); }
  
      public function setUsername($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField('username', $value, $operator);
      }
  
      public function getPassword() { return $this->getField("rpassword"); }
      public function setPassword($value) { $this->setField("rpassword", $value); }
  
      public function getFirstname() { return $this->getField("firstname"); }
  
      public function setFirstname($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField('firstname', $value, $operator);
      }
  
      public function getLastname() { return $this->getField("lastname"); }
  
      public function setLastname($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField('lastname', $value, $operator);
      }
  
      public function getParentUserId() { return $this->getField("parentuserid"); }
      public function setParentUserId($value) { $this->setField("parentuserid", $value); }
  
      public function getVisitorId() { return $this->getField("visitorId"); }
      public function setVisitorId($value) { $this->setField("visitorId", $value); }
  
      public function getIp() { return $this->getField("ip"); }
      public function setIp($value) { $this->setField("ip", $value); }
  
      public function getNotificationEmail() { return $this->getField("notificationemail"); }
      public function setNotificationEmail($value) { $this->setField("notificationemail", $value); }
  
      public function getLanguage() { return $this->getField('lang'); }
      public function setLanguage($value) { $this->setField('lang', $value); }
  
      public function disableSignupBonus() { $this->setField('createSignupComm', Gpf::NO); }
      public function disableReferralCommissions() { $this->setField('createReferralComm', Gpf::NO); }
  
      public function getData($index) {
          $this->checkIndex($index);
          return $this->getField("data$index");
      }
      public function setData($index, $value, $operator = self::OPERATOR_EQUALS) {
          $this->checkIndex($index);
          $this->setField("data$index", $value, $operator);
      }
  
      public function setPayoutOptionField($code, $value) {
          $this->setField($code, $value);
      }
  
      public function getDataName($index) {
          $this->checkIndex($index);
          $dataField = "data$index";
           
          if(!is_array($this->dataValues) || !isset($this->dataValues[$dataField])) {
              return '';
          }
           
          return $this->dataValues[$dataField]['name'];
      }
  
      public function getDataStatus($index) {
          $this->checkIndex($index);
          $dataField = "data$index";
           
          if(!is_array($this->dataValues) || !isset($this->dataValues[$dataField])) {
              return 'U';
          }
           
          return $this->dataValues[$dataField]['status'];
      }
  
      public function sendConfirmationEmail() {
          $params = new Gpf_Rpc_Params();
          $params->add('ids', array($this->getUserid()));
          return $this->sendActionRequest('Pap_Merchants_User_AffiliateForm', 'sendSignupConfirmation', $params);
      }
  
      /**
       * @param $campaignID
       * @param $sendNotification
       */
      public function assignToPrivateCampaign($campaignID, $sendNotification = false) {
          $params = new Gpf_Rpc_Params();
          $params->add('campaignId', $campaignID);
          $params->add('sendNotification', ($sendNotification ? Gpf::YES : Gpf::NO));
          $params->add('ids', array($this->getUserid()));
          return $this->sendActionRequest('Pap_Db_UserInCommissionGroup', 'addUsers', $params);
      }
  
      private function checkIndex($index) {
          if(!is_numeric($index) || $index > 25 || $index < 1) {
              throw new Exception("Incorrect index '$index', it must be between 1 and 25");
          }
           
          return true;
      }
  
      protected function fillEmptyRecord() {
          $this->setField("userid", "");
          $this->setField("agreeWithTerms", Gpf::YES);
      }
  
      protected function getPrimaryKey() {
          return "userid";
      }
  
      protected function getGridRequest() {
          return new Pap_Api_AffiliatesGrid($this->getSession());
      }
  
      /**
       * retrieves names and states of data1..data25 fields
       *
       */
      protected function getDataFields() {
          $request = new Gpf_Rpc_RecordsetRequest("Gpf_Db_Table_FormFields", "getFields", $this->getSession());
          $request->addParam("formId","affiliateForm");
          $request->addParam("status","M,O");
           
          try {
              $request->sendNow();
          } catch(Exception $e) {
              throw new Exception("Cannot load datafields. Error: ".$e->getMessage());
          }
           
          $recordset = $request->getRecordSet();
          $this->dataValues = array();
          foreach($recordset as $record) {
              $this->dataValues[$record->get("code")]['name'] = $record->get("name");
              $this->dataValues[$record->get("code")]['status'] = $record->get("status");
          }
      }
  
      private function sendActionRequest($className, $method, Gpf_Rpc_Params $params) {
          $request = new Gpf_Rpc_ActionRequest($className, $method, $this->getSession());
          $request->setParams($params);
          return $request->sendNow();
      }
  
      protected function beforeCallRequest(Gpf_Rpc_FormRequest $request) {
          $request->addParam('isFromApi', Gpf::YES);
      }
  }

} //end Pap_Api_Affiliate

if (!class_exists('Pap_Api_TransactionsGrid', false)) {
  class Pap_Api_TransactionsGrid extends Gpf_Rpc_GridRequest {
  	
      const REFUND_MERCHANT_NOTE = 'merchant_note';
      const REFUND_TYPE = 'status';
      const REFUND_FEE = 'fee';
      const TYPE_REFUND = 'R';
      const TYPE_CHARGEBACK = 'H';
  
  	private $dataValues = null;
  	
      public function __construct(Gpf_Api_Session $session) {
      	if($session->getRoleType() == Gpf_Api_Session::AFFILIATE) {
      		$className = "Pap_Affiliates_Reports_TransactionsGrid";
      	} else {
      		$className = "Pap_Merchants_Transaction_TransactionsGrid";
      	}
      	parent::__construct($className, "getRows", $session);
      }
  
      public function refund($note = '', $fee = 0) {
          return $this->makeRefundChargeback(self::TYPE_REFUND, $note, $fee);
      }
  
      public function chargeback($note = '', $fee = 0) {
          return $this->makeRefundChargeback(self::TYPE_CHARGEBACK, $note, $fee);
      }
  
      private function makeRefundChargeback($type, $note, $fee) {        
          if ($this->apiSessionObject->getRoleType() == Gpf_Api_Session::AFFILIATE) {
              throw new Exception("This method can be used only by merchant!"); 
          }
          if ($this->getFiltersParameter()->getCount() == 0) {
              throw new Exception("Refund / Chargeback in transactions grid is possible to make only with filters!");
          }
  
          $request = new Gpf_Rpc_ActionRequest('Pap_Merchants_Transaction_TransactionsForm', 'makeRefundChargebackByParams', $this->apiSessionObject);
          $request->addParam('filters', $this->getFiltersParameter());
          $request->addParam(self::REFUND_MERCHANT_NOTE, $note);
          $request->addParam(self::REFUND_TYPE, $type);
          $request->addParam(self::REFUND_FEE, $fee);
  
          $request->sendNow();
  
          return $request->getAction();
      }
  }

} //end Pap_Api_TransactionsGrid

if (!class_exists('Pap_Api_Transaction', false)) {
  class Pap_Api_Transaction extends Pap_Api_Object {
      
      const TRACKING_METHOD_MANUAL_COMMISSION = 'M';
  
      private $dataValues = null;
  
      public function __construct(Gpf_Api_Session $session) {
          if($session->getRoleType() == Gpf_Api_Session::AFFILIATE) {
              throw new Exception("This class can be used only by merchant!");
          } else {
              $this->class = "Pap_Merchants_Transaction_TransactionsForm";
          }
           
          parent::__construct($session);
      }
  
      public function getTransid() { return $this->getField("transid"); }
      public function setTransid($value) {
          $this->setField("transid", $value);
          $this->setField("Id", $value);
      }
  
      public function getType() { return $this->getField("rtype"); }
      public function setType($value) { $this->setField("rtype", $value); }
  
      public function getStatus() { return $this->getField("rstatus"); }
      public function setStatus($value) { $this->setField("rstatus", $value); }
  
      public function getMultiTierCreation() { return $this->getField("multiTier"); }
      public function setMultiTierCreation($value) { $this->setField("multiTier", $value); }
  
      public function getUserid() { return $this->getField("userid"); }
      public function setUserid($value) { $this->setField("userid", $value); }
  
      public function getBannerid() { return $this->getField("bannerid"); }
      public function setBannerid($value) { $this->setField("bannerid", $value); }
  
      public function getParentBannerid() { return $this->getField("parentbannerid"); }
      public function setParentBannerid($value) { $this->setField("parentbannerid", $value); }
  
      public function getCampaignid() { return $this->getField("campaignid"); }
      public function setCampaignid($value) { $this->setField("campaignid", $value); }
  
      public function getCountryCode() { return $this->getField("countrycode"); }
      public function setCountryCode($value) { $this->setField("countrycode", $value); }
  
      public function getDateInserted() { return $this->getField("dateinserted"); }
      public function setDateInserted($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("dateinserted", $value, $operator);
      }
  
      public function getDateApproved() { return $this->getField("dateapproved"); }
      public function setDateApproved($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("dateapproved", $value, $operator);
      }
  
      public function getPayoutStatus() { return $this->getField("payoutstatus"); }
      public function setPayoutStatus($value) { $this->setField("payoutstatus", $value); }
  
      public function getPayoutHistoryId() { return $this->getField("payouthistoryid"); }
      public function setPayoutHistoryId($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("payouthistoryid", $value, $operator);
      }
  
      public function getRefererUrl() { return $this->getField("refererurl"); }
      public function setRefererUrl($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("refererurl", $value, $operator);
      }
  
      public function getIp() { return $this->getField("ip"); }
      public function setIp($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("ip", $value, $operator);
      }
  
      public function getBrowser() { return $this->getField("browser"); }
      public function setBrowser($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("browser", $value, $operator);
      }
  
      public function getCommission() { return $this->getField("commission"); }
      public function setCommission($value) { $this->setField("commission", $value); }
  
      public function getOrderId() { return $this->getField("orderid"); }
      public function setOrderId($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("orderid", $value, $operator);
      }
  
      public function getProductId() { return $this->getField("productid"); }
      public function setProductId($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("productid", $value, $operator);
      }
  
      public function getTotalCost() { return $this->getField("totalcost"); }
      public function setTotalCost($value) { $this->setField("totalcost", $value); }
  
      public function getRecurringCommid() { return $this->getField("recurringcommid"); }
      public function setRecurringCommid($value) { $this->setField("recurringcommid", $value); }
  
      public function getFirstClickTime() { return $this->getField("firstclicktime"); }
      public function setFirstClickTime($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("firstclicktime", $value, $operator);
      }
  
      public function getFirstClickReferer() { return $this->getField("firstclickreferer"); }
      public function setFirstClickReferer($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("firstclickreferer", $value, $operator);
      }
  
      public function getFirstClickIp() { return $this->getField("firstclickip"); }
      public function setFirstClickIp($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("firstclickip", $value, $operator);
      }
  
      public function getFirstClickData1() { return $this->getField("firstclickdata1"); }
      public function setFirstClickData1($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("firstclickdata1", $value, $operator);
      }
  
      public function getFirstClickData2() { return $this->getField("firstclickdata2"); }
      public function setFirstClickData2($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("firstclickdata2", $value, $operator);
      }
  
      public function getClickCount() { return $this->getField("clickcount"); }
      public function setClickCount($value) { $this->setField("clickcount", $value); }
  
      public function getLastClickTime() { return $this->getField("lastclicktime"); }
      public function setLastClickTime($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("lastclicktime", $value, $operator);
      }
  
      public function getLastClickReferer() { return $this->getField("lastclickreferer"); }
      public function setLastClickReferer($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("lastclickreferer", $value, $operator);
      }
  
      public function getLastClickIp() { return $this->getField("lastclickip"); }
      public function setLastClickIp($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("lastclickip", $value, $operator);
      }
  
      public function getLastClickData1() { return $this->getField("lastclickdata1"); }
      public function setLastClickData1($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("lastclickdata1", $value, $operator);
      }
  
      public function getLastClickData2() { return $this->getField("lastclickdata2"); }
      public function setLastClickData2($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("lastclickdata2", $value, $operator);
      }
  
      public function getTrackMethod() { return $this->getField("trackmethod"); }
      public function setTrackMethod($value) { $this->setField("trackmethod", $value); }
  
      public function getOriginalCurrencyId() { return $this->getField("originalcurrencyid"); }
      public function setOriginalCurrencyId($value) { $this->setField("originalcurrencyid", $value); }
  
      public function getOriginalCurrencyValue() { return $this->getField("originalcurrencyvalue"); }
      public function setOriginalCurrencyValue($value) { $this->setField("originalcurrencyvalue", $value); }
  
      public function getOriginalCurrencyRate() { return $this->getField("originalcurrencyrate"); }
      public function setOriginalCurrencyRate($value) { $this->setField("originalcurrencyrate", $value); }
  
      public function getTier() { return $this->getField("tier"); }
      public function setTier($value) { $this->setField("tier", $value); }
  
      public function getChannel() { return $this->getField("channel"); }
      public function setChannel($value) { $this->setField("channel", $value); }
  
      public function getCommTypeId() { return $this->getField("commtypeid"); }
      public function setCommTypeId($value) { $this->setField("commtypeid", $value); }
  
      public function getMerchantNote() { return $this->getField("merchantnote"); }
      public function setMerchantNote($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("merchantnote", $value, $operator);
      }
  
      public function getSystemNote() { return $this->getField("systemnote"); }
      public function setSystemNote($value, $operator = self::OPERATOR_EQUALS) {
          $this->setField("systemnote", $value, $operator);
      }
  
      public function getParentTransactionId() { return $this->getField("parenttransid"); }
      public function setParentTransactionId($value) { $this->setField("parenttransid", $value); }
  
      public function getData($index) {
          $this->checkIndex($index);
          return $this->getField("data$index");
      }
      public function setData($index, $value, $operator = self::OPERATOR_EQUALS) {
          $this->checkIndex($index);
          $this->setField("data$index", $value, $operator);
      }
  
      /**
       * @param $note optional note that will be added to the refund/chargeback transaction
       * @param $fee that will be added to the refund/chargeback transaction
       * @return Gpf_Rpc_Action
       */
      public function chargeBack($note = '', $fee = 0, $refundMultiTier = false) {
          return $this->makeRefundChargeBack($note, 'H', $fee, $refundMultiTier);
      }
  
      /**
       * @param $note optional note that will be added to the refund/chargeback transaction
       * @param $fee that will be added to the refund/chargeback transaction
       * @return Gpf_Rpc_Action
       */
      public function refund($note = '', $fee = 0, $refundMultiTier = false) {
          return $this->makeRefundChargeBack($note, 'R', $fee, $refundMultiTier);
      }
  
      /**
       * @return Gpf_Rpc_Action
       */
      private function makeRefundChargeBack($note, $type, $fee, $refundMultiTier) {
          if ($this->getTransid() == '') {
              throw new Gpf_Exception("No transaction ID. Call setTransid() or load transaction before calling refund/chargeback");
          }
          $request = new Gpf_Rpc_ActionRequest($this->class, 'makeRefundChargeback', $this->getSession());
          $request->addParam('merchant_note', $note);
          $request->addParam('refund_multitier', $refundMultiTier ? 'Y' : 'N');
          $request->addParam('status', $type);
          $request->addParam('ids', new Gpf_Rpc_Map(array($this->getTransid())));
          $request->addParam('fee', $fee);
          $request->sendNow();
          return $request->getAction();
      }
  
      /**
       * @param $orderid order ID of transaction which will be approved
       * @param $note optional note that will be added to the transaction
       * @return Gpf_Rpc_Action
       */
      public function approveByOrderId($note = '') {
          return $this->changeStatusPerOrderId($note, 'A');
      }
  
      /**
       * @param $orderid order ID of transaction which will be declined
       * @param $note optional note that will be added to the transaction
       * @return Gpf_Rpc_Action
       */
      public function declineByOrderId($note = '') {
          return $this->changeStatusPerOrderId($note, 'D');
      }
  
      /**
       * @return Gpf_Rpc_Action
       */
      private function changeStatusPerOrderId($note, $type) {
          if ($this->getOrderId() == '') {
              throw new Gpf_Exception('Order ID cannot be empty!');
          }
          $request = new Gpf_Rpc_ActionRequest($this->class, 'changeStatusPerOrderId', $this->getSession());
          $request->addParam('merchant_note', $note);
          $request->addParam('status', $type);
          $request->addParam('orderid', $this->getOrderId());
          $request->sendNow();
          return $request->getAction();
      }
  
  
      private function checkIndex($index) {
          if(!is_numeric($index) || $index > 5 || $index < 1) {
              throw new Exception("Incorrect index '$index', it must be between 1 and 5");
          }
           
          return true;
      }
  
      protected function fillEmptyRecord() {
          $this->setTransid("");
          if($this->getType() == '') {
              $this->setType("A");
          }
          if($this->getMultiTierCreation() == '') {
              $this->setMultiTierCreation('N');
          }
          if($this->getTrackMethod() == '') {
              $this->setTrackMethod(self::TRACKING_METHOD_MANUAL_COMMISSION);
          }
      }
  
      protected function getPrimaryKey() {
          return "id";
      }
  
      protected function getGridRequest() {
          return new Pap_Api_TransactionsGrid($this->getSession());
      }
  }

} //end Pap_Api_Transaction

if (!class_exists('Pap_Tracking_Action_RequestActionObject', false)) {
  class Pap_Tracking_Action_RequestActionObject extends Gpf_Rpc_JsonObject {
      public $ac   = ''; // actionCode
      public $t    = ''; // totalCost
      public $f    = ''; // fixedCost
      public $o    = ''; // order ID
      public $p    = ''; // product ID
      public $d1   = ''; // data1
      public $d2   = ''; // data2
      public $d3   = ''; // data3
      public $d4   = ''; // data4
      public $d5   = ''; // data5
      public $a    = ''; // affiliate ID
      public $c    = ''; // campaign ID
      public $b    = ''; // banner ID
      public $ch   = ''; // channel ID
      public $cc   = ''; // custom commission
      public $ccfc = ''; // load next tiers from campaign
      public $s    = ''; // status
      public $cr   = ''; // currency
      public $cp   = ''; // coupon code
      public $ts   = ''; // time stamp
      
      public function __construct($object = null) {
          parent::__construct($object);
      }
  
      public function getActionCode() {
          return $this->ac;
      }
  
      public function getTotalCost() {
          return $this->t;
      }
  
      public function getFixedCost() {
          return $this->f;
      }
  
      public function getOrderId() {
          return $this->o;
      }
  
      public function getProductId() {
          return $this->p;
      }
  
      public function getData1() {
          return $this->d1;
      }
  
      public function getData2() {
          return $this->d2;
      }
  
      public function getData3() {
          return $this->d3;
      }
  
      public function getData4() {
          return $this->d4;
      }
  
      public function getData5() {
          return $this->d5;
      }
  
      public function getData($i) {
          $dataVar = 'd'.$i;
          return $this->$dataVar;
      }
  
      public function setData($i, $value) {
          $dataVar = 'd'.$i;
          $this->$dataVar = $value;
      }
  
      public function getAffiliateId() {
          return $this->a;
      }
  
      public function getCampaignId() {
          return $this->c;
      }
      
      public function getBannerId() {
          return $this->b;
      }
  
      public function getChannelId() {
          return $this->ch;
      }
  
      public function getCustomCommission() {
          return $this->cc;
      }
  
      public function getCustomCommissionNextTiersFromCampaign() {
          return $this->ccfc;
      }
  
      public function getStatus() {
          return $this->s;
      }
  
      public function getCurrency() {
          return $this->cr;
      }
  
      public function getCouponCode() {
          return $this->cp;
      }
  
      public function getTimeStamp() {
          return $this->ts;
      }
  
      public function setActionCode($value) {
          $this->ac = $value;
      }
  
      public function setTotalCost($value) {
          $this->t = $value;
      }
  
      public function setFixedCost($value) {
          $this->f = $value;
      }
  
      public function setOrderId($value) {
          $this->o = $value;
      }
  
      public function setProductId($value) {
          $this->p = $value;
      }
  
      public function setData1($value) {
          $this->d1 = $value;
      }
  
      public function setData2($value) {
          $this->d2 = $value;
      }
  
      public function setData3($value) {
          $this->d3 = $value;
      }
  
      public function setData4($value) {
          $this->d4 = $value;
      }
  
      public function setData5($value) {
          $this->d5 = $value;
      }
  
      public function setAffiliateId($value) {
          $this->a = $value;
      }
  
      public function setCampaignId($value) {
          $this->c = $value;
      }
      
      public function setBannerId($value) {
          $this->b = $value;
      }
  
      public function setChannelId($value) {
          $this->ch = $value;
      }
  
      public function setCustomCommission($value) {
          $this->cc = $value;
      }
  
      public function setCustomCommissionNextTiersFromCampaign($value) {
          $this->ccfc = $value;
      }
  
      public function setStatus($value) {
          $this->s = $value;
      }
  
      public function setCurrency($value) {
          $this->cr = $value;
      }
  
      public function setCouponCode($value) {
          $this->cp = $value;
      }
  
      public function setTimeStamp($value) {
          $this->ts = $value;
      }
  
  }

} //end Pap_Tracking_Action_RequestActionObject

if (!class_exists('Pap_Tracking_Request', false)) {
  class Pap_Tracking_Request extends Gpf_Object {
      const PARAM_CAMPAIGN_ID_SETTING_NAME = 'campaignId';
  
      /* other action parameters */
      const PARAM_ACTION_DEBUG = 'PDebug';
      const PARAM_CALL_FROM_JAVASCRIPT = 'cjs';
  
      /* Constant param names */
      const PARAM_LINK_STYLE = 'ls';
      const PARAM_REFERRERURL_NAME = 'refe';
  
      /* Param setting names */
      const PARAM_DESTINATION_URL_SETTING_NAME = 'param_name_extra_data3';
      const PARAM_CHANNEL_DEFAULT = 'chan';
      const PARAM_CURRENCY = 'cur';
  
      /* Forced parameter names */
      const PARAM_FORCED_AFFILIATE_ID = 'AffiliateID';
      const PARAM_FORCED_BANNER_ID = 'BannerID';
      const PARAM_FORCED_CAMPAIGN_ID = 'CampaignID';
      const PARAM_FORCED_CHANNEL_ID = 'Channel';
      const PARAM_FORCED_IP = 'Ip';
  
      private $countryCode;
  
      protected $request;
  
      /**
       * @var Gpf_Log_Logger
       */
      protected $logger;
  
      function __construct() {
          $this->request = $_REQUEST;
      }
  
      public function parseUrl($url) {
          $this->request = array();
          if ($url === null || $url == '') {
              return;
          }
          $parsedUrl = @parse_url('?'.ltrim($url, '?'));
          if ($parsedUrl === false || !array_key_exists('query', $parsedUrl)) {
              return;
          }
          $args = explode('&', @$parsedUrl['query']);
          foreach ($args as $arg) {
              $parts = explode('=', $arg, 2);
              if (count($parts) == 2) {
                  $this->request[$parts[0]] = $parts[1];
              }
          }
      }
  
      public function getAffiliateId() {
          return $this->getRequestParameter(self::getAffiliateClickParamName());
      }
  
      public function getForcedAffiliateId() {
          return $this->getRequestParameter(self::getForcedAffiliateParamName());
      }
  
      public function getBannerId() {
          return $this->getRequestParameter(self::getBannerClickParamName());
      }
  
      public function getForcedBannerId() {
          return $this->getRequestParameter(self::getForcedBannerParamName());
      }
  
      /**
       * @return Pap_Common_User
       */
      public function getUser() {
          try {
              return Pap_Affiliates_User::loadFromId($this->getRequestParameter($this->getAffiliateClickParamName()));
          } catch (Gpf_Exception $e) {
              return null;
          }
      }
  
      /**
       * @param string $id
       * @return string
       */
      public function getRawExtraData($i) {
          $extraDataParamName = $this->getExtraDataParamName($i);
          if (!isset($this->request[$extraDataParamName])) {
              return '';
          }
          $str = preg_replace("/%u([0-9a-f]{3,4})/i", "&#x\\1;",urldecode($this->request[$extraDataParamName]));
          return html_entity_decode($str,null,'UTF-8');
      }
  
      public function setRawExtraData($i, $value) {
          $extraDataParamName = $this->getExtraDataParamName($i);
          $this->request[$extraDataParamName] = $value;
      }
  
      /**
       * returns custom click link parameter data1
       * It first checks for forced parameter Data1 given as parameter to JS tracking code
       *
       * @return string
       */
      public function getClickData1() {
          $value = $this->getRequestParameter('pd1');
          if($value != '') {
              return $value;
          }
  
          $paramName = self::getClickData1ParamName();
          if (!isset($this->request[$paramName])) {
              return '';
          }
          return $this->request[$paramName];
      }
  
      /**
       * returns custom click link parameter data2
       * It first checks for forcet parameter Data2 given as parameter to JS tracking code
       *
       * @return string
       */
      public function getClickData2() {
          $value = $this->getRequestParameter('pd2');
          if($value != '') {
              return $value;
          }
  
          $paramName = self::getClickData2ParamName();
          if (!isset($this->request[$paramName])) {
              return '';
          }
          return $this->request[$paramName];
      }
  
      public static function getClickData1ParamName() {
          return Gpf_Settings::get(Pap_Settings::PARAM_NAME_EXTRA_DATA.'1');
      }
  
      public static function getClickData2ParamName() {
          return Gpf_Settings::get(Pap_Settings::PARAM_NAME_EXTRA_DATA.'2');
      }
  
      public function getRefererUrl() {
          if (isset($this->request[self::PARAM_REFERRERURL_NAME]) && $this->request[self::PARAM_REFERRERURL_NAME] != '') {
              return self::decodeRefererUrl($this->request[self::PARAM_REFERRERURL_NAME]);
          }
          if (isset($_SERVER['HTTP_REFERER'])) {
              return self::decodeRefererUrl($_SERVER['HTTP_REFERER']);
          }
          return '';
      }
  
      public function getIP() {
          if ($this->getForcedIp() !== '') {
              return $this->getForcedIp();
          }
          return Gpf_Http::getRemoteIp();
      }
  
      public function getCountryCode() {
          if ($this->countryCode === null) {
              $context = new Gpf_Data_Record(
              array(Pap_Db_Table_RawImpressions::IP, Pap_Db_Table_Impressions::COUNTRYCODE), array($this->getIP(), ''));
              Gpf_Plugins_Engine::extensionPoint('Tracker.request.getCountryCode', $context);
              $this->countryCode = $context->get(Pap_Db_Table_Impressions::COUNTRYCODE);
          }
          return $this->countryCode;
      }
  
      /**
       * @return NULL|Pap_Db_UserAgent
       */
      public function getUserAgentObject() {
          if (Gpf_Http::getUserAgent() == '') {
              return null;
          }
          return Pap_Db_Table_UserAgents::getInstance()->insertUserAgent(Gpf_Http::getUserAgent());
      }
  
      public function getLinkStyle() {
          if (!isset($this->request[self::PARAM_LINK_STYLE]) || $this->request[self::PARAM_LINK_STYLE] != '1') {
              return Pap_Tracking_ClickTracker::LINKMETHOD_REDIRECT;
          }
          return Pap_Tracking_ClickTracker::LINKMETHOD_URLPARAMETERS;
      }
  
      /**
       * set logger
       *
       * @param Gpf_Log_Logger $logger
       */
      public function setLogger($logger) {
          $this->logger = $logger;
      }
  
      protected function debug($msg) {
          if($this->logger != null) {
              $this->logger->debug($msg);
          }
      }
  
      public function getRequestParameter($paramName) {
          if (!isset($this->request[$paramName])) {
              return '';
          }
          return $this->request[$paramName];
      }
  
      public function setRequestParameter($paramName, $value) {
          $this->request[$paramName] = $value;
      }
  
      static public function getRotatorBannerParamName() {
          return Gpf_Settings::get(Pap_Settings::PARAM_NAME_ROTATOR_ID);
      }
  
      static public function getSpecialDestinationUrlParamName() {
          return Gpf_Settings::get(Pap_Settings::PARAM_NAME_DESTINATION_URL);
      }
  
      public function getRotatorBannerId() {
          return $this->getRequestParameter(self::getRotatorBannerParamName());
      }
  
      public function getExtraDataParamName($i) {
          return Gpf_Settings::get(Pap_Settings::PARAM_NAME_EXTRA_DATA).$i;
      }
  
      public function getDebug() {
          if(isset($_GET[self::PARAM_ACTION_DEBUG])) {
              return strtoupper($_GET[self::PARAM_ACTION_DEBUG]);
          }
          return '';
      }
  
      public function toString() {
          $params = array();
          foreach($this->request as $key => $value) {
              $params .= ($params != '' ? ", " : '')."$key=$value";
          }
          return $params;
      }
  
      public function getRecognizedClickParameters() {
          $params = 'Debug='.$this->getDebug();
          $params .= ',Data1='.$this->getClickData1();
          $params .= ',Data2='.$this->getClickData2();
  
          return $params;
      }
  
      static public function getAffiliateClickParamName() {
          return Gpf_Settings::get(Pap_Settings::PARAM_NAME_USER_ID);
      }
  
      static public function getBannerClickParamName() {
          $parameterName = trim(Gpf_Settings::get(Pap_Settings::PARAM_NAME_BANNER_ID));
          if($parameterName == '') {
              $mesage = Gpf_Lang::_('Banner ID parameter name is empty. Review URL parameter name settings');
              Gpf_Log::critical($mesage);
              throw new Gpf_Exception($mesage);
          }
          return $parameterName;
      }
  
      static public function getChannelParamName() {
          return Pap_Tracking_Request::PARAM_CHANNEL_DEFAULT;
      }
  
      public function getChannelId() {
          return $this->getRequestParameter(self::getChannelParamName());
      }
  
      static public function getForcedAffiliateParamName() {
          return Pap_Tracking_Request::PARAM_FORCED_AFFILIATE_ID;
      }
  
      static public function getForcedBannerParamName() {
          return Pap_Tracking_Request::PARAM_FORCED_BANNER_ID;
      }
  
      public function getForcedCampaignId() {
          return $this->getRequestParameter(self::getForcedCampaignParamName());
      }
  
      static public function getForcedCampaignParamName() {
          return Pap_Tracking_Request::PARAM_FORCED_CAMPAIGN_ID;
      }
  
      public function getForcedChannelId() {
          return $this->getRequestParameter(Pap_Tracking_Request::PARAM_FORCED_CHANNEL_ID);
      }
  
      public function getCampaignId() {
          return $this->getRequestParameter(self::getCampaignParamName());
      }
  
      static public function getCampaignParamName() {
          $parameterName = trim(Gpf_Settings::get(Pap_Settings::PARAM_NAME_CAMPAIGN_ID));
          if($parameterName == '') {
              $mesage = Gpf_Lang::_('Campaign ID parameter name is empty. Review URL parameter name settings');
              Gpf_Log::critical($mesage);
              throw new Gpf_Exception($mesage);
          }
          return $parameterName;
      }
  
      public function getCurrency() {
          return $this->getRequestParameter(self::PARAM_CURRENCY);
      }
  
      /**
       * @deprecated used in CallBackTracker plugins only. should be moved to callback tracker
       */
      public function getPostParam($name) {
          if (!isset($_POST[$name])) {
              return '';
          }
          return $_POST[$name];
      }
  
      /**
       * This function does escape http:// and https:// in url as mod_rewrite disables requests with ://
       *
       * @param $url
       * @return encoded url
       */
      public static function encodeRefererUrl($url) {
          $url = str_replace('http://', 'H_', $url);
          $url = str_replace('https://', 'S_', $url);
          return $url;
      }
  
      /**
       * This function does decoded encoded url
       *
       * @param encoded $url
       * @return $url
       */
      public static function decodeRefererUrl($url) {
          if (substr($url, 0, 2) == 'H_') {
              return 'http://' . substr($url, 2);
          }
          if (substr($url, 0, 2) == 'S_') {
              return 'https://' . substr($url, 2);
          }
          return $url;
      }
  
      private function getForcedIp() {
          return $this->getRequestParameter(self::PARAM_FORCED_IP);
      }
  }

} //end Pap_Tracking_Request

if (!class_exists('Pap_Api_Tracker', false)) {
  class Pap_Api_Tracker extends Gpf_Object {
  
      /**
       * @var Gpf_Api_Session
       */
      private $session;
      private $trackingResponse;
      private $visitorId;
      private $accountId;
      /**
       * @var array<Pap_Tracking_Action_RequestActionObject>
       */
      private $sales = array();
      const VISITOR_COOKIE_NAME = 'PAPVisitorId';
      
      const NOT_LOADED_YET = '-1';
      /**
       * @var Gpf_Rpc_Data
       */
      private $affiliate = self::NOT_LOADED_YET;
      /**
       * @var Gpf_Rpc_Data
       */
      private $campaign = self::NOT_LOADED_YET;
      /**
       * @var Gpf_Rpc_Data
       */
      private $channel = self::NOT_LOADED_YET;
      
      /**
       * This class requires correctly initialized merchant session
       *
       * @param Gpf_Api_Session $session
       */
      public function __construct(Gpf_Api_Session $session) {
          if($session->getRoleType() == Gpf_Api_Session::AFFILIATE) {
              throw new Exception("This class can be used only by merchant!");
          }
          $this->session = $session;
          $this->visitorId = @$_COOKIE[self::VISITOR_COOKIE_NAME];
      }
      
      public function setVisitorId($visitorId) {
          $this->visitorId = $visitorId;
      }
  
      public function getVisitorId() {
          return $this->visitorId;
      }
  
      public function setAccountId($accountId) {
          $this->accountId = $accountId;
      }
      
      public function track() {
          $request = new Gpf_Net_Http_Request();
          $request->setUrl(str_replace('server.php', 'track.php', $this->session->getUrl()));
          $request->setMethod('POST');
  
  		$this->setQueryParams($request);
          if ($this->session->getDebug()) {
              $request->addQueryParam('PDebug', 'Y');
          }
          
          $request->setUrl($request->getUrl() . $request->getQuery());
          $request->setBody("sale=".$this->getSaleParams());
          if ($this->session->getDebug()) {
              echo 'Tracking request: '.$request->getUrl();
              echo '&sale=' . urlencode($this->getSaleParams())."<br>\n";
          }
          $response = $this->sendRequest($request);
          $this->trackingResponse = trim($response->getBody());
          if ($this->session->getDebug()) {
              echo 'Tracking response: '.$this->trackingResponse."<br>\n";
          }
          $this->parseResponse();
          $this->affiliate = self::NOT_LOADED_YET;
      }
      
      protected function setQueryParams(Gpf_Net_Http_Request $request) {
      	$request->addQueryParam('visitorId', $this->visitorId);
      	$request->addQueryParam('accountId', $this->accountId);
          $request->addQueryParam('url', Pap_Tracking_Request::encodeRefererUrl($this->getUrl()));
          $request->addQueryParam('referrer', Pap_Tracking_Request::encodeRefererUrl($this->getReferrerUrl()));
          $request->addQueryParam('tracking', '1');
          $request->addQueryParam('getParams', $this->getGetParams()->getQuery());
          $request->addQueryParam('cookies', $this->getOldCookies());
          $request->addQueryParam('ip', $this->getIp());
          $request->addQueryParam('useragent', $this->getUserAgent());
      }
      
      protected function getIp() {
      	return @Gpf_Http::getRemoteIp();
      }
      
      protected function getUserAgent() {
      	return Gpf_Http::getUserAgent();
      }
      
      protected function sendRequest(Gpf_Net_Http_Request $request) {
          $client = new Gpf_Net_Http_Client();
          return $client->execute($request);
      }
  
      public function saveCookies() {
          if ($this->trackingResponse == '') {
              return;
          }
          $this->includeJavascript();
          $this->saveCookiesByJavascript();
      }
  
      public function save3rdPartyCookiesOnly($cookieDomainValidity = null) {
      	if ($this->visitorId == null) {
              return;
          }
          $this->save3rdPartyCookie(self::VISITOR_COOKIE_NAME, $this->visitorId, time() + 315569260, true, $cookieDomainValidity);
      }
  
      /**
       * @return Gpf_Rpc_Data
       */
      public function getAffiliate() {
      	return $this->getData($this->affiliate, 'getAffiliate', 'userid');
      }
      
      /**
       * @return Gpf_Rpc_Data
       */
      public function getCampaign() {
      	return $this->getData($this->campaign, 'getCampaign', 'campaignid');
      }
  
      /**
       * @return Gpf_Rpc_Data
       */
      public function getChannel() {
          return $this->getData($this->channel, 'getChannel', 'channelid');
      }
  
      private function getData(&$data, $method, $primaryKeyName) {
      	if ($this->visitorId == '') {
              return null;
          }
          if ($data === self::NOT_LOADED_YET) {
              $request = new Gpf_Rpc_DataRequest('Pap_Tracking_Visit_SingleVisitorProcessor', $method, $this->session);
              $request->addParam('visitorId', $this->visitorId);
              $request->addParam('accountId', $this->accountId);
              if ($this->session->getSessionId() == '') {
                  $request->addParam('initSession', Gpf::YES);
              }
              $request->sendNow();
              $data = $request->getData();
              if (is_null($data->getValue($primaryKeyName))) {
              	$data = null;
              }
          }
          return $data;
      }
      
      /**
       * Creates and returns new sale
       *
       * @return Pap_Tracking_ActionObject
       */
      public function createSale() {
          return $this->createAction('');
      }
  
      /**
       * Creates and returns new action
       *
       * @param string $actionCode
       * @return Pap_Tracking_ActionObject
       */
      public function createAction($actionCode = '') {
          $sale = new Pap_Tracking_Action_RequestActionObject();
          $sale->setActionCode($actionCode);
          $this->sales[] = $sale;
          return $sale;
      }
  
      protected function getSaleParams() {
          if (count($this->sales) == 0) {
              return '';
          }
          $json = new Gpf_Rpc_Json();
          return $json->encode($this->sales);
      }
      
      /**
       * Parses track.php response. Response can be empty or setVisitor('4c5e2151b8856e55dbfeb247c22300Hg');
       */
      private function parseResponse() {
          if ($this->trackingResponse == '') {
              return;
          }
          if (!preg_match('/^setVisitor\(\'([a-zA-Z0-9]+)\'\);/', $this->trackingResponse, $matches)) {
              return;
          }
          if ($matches[1] != '') {
              $this->visitorId = $matches[1];
          }
      }
  
      private function includeJavascript() {
          $trackjsUrl = str_replace('server.php', 'trackjs.php', $this->session->getUrl());
          echo '<script id="pap_x2s6df8d" src="'.$trackjsUrl.'" type="text/javascript"></script>';
      }
  
      private function saveCookiesByJavascript() {
          echo '<script type="text/javascript">'.$this->trackingResponse.'</script>';
      }
  
      protected function getUrl() {
          if (array_key_exists('PATH_INFO', $_SERVER) && @$_SERVER['PATH_INFO'] != '') {
              $scriptName = str_replace('\\', '/', @$_SERVER['PATH_INFO']);
          } else {
              if (array_key_exists('SCRIPT_NAME', $_SERVER)) {
                  $scriptName = str_replace('\\', '/', @$_SERVER['SCRIPT_NAME']);
              } else {
                  $scriptName = '';
              }
          }
          $portString = '';
          if(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80
          && $_SERVER['SERVER_PORT'] != 443) {
              $portString = ':' . $_SERVER["SERVER_PORT"];
          }
          $protocol = 'http';
          if(isset($_SERVER['HTTPS']) && strlen($_SERVER['HTTPS']) > 0 && strtolower($_SERVER['HTTPS']) != 'off') {
              $protocol = 'https';
          }
          return $protocol . '://' . $this->getServerName() . $portString . $scriptName;
      }
  
      private function getServerName() {
          if (isset($_SERVER["SERVER_NAME"])) {
              return $_SERVER["SERVER_NAME"];
          }
          return 'localhost';
      }
  
      protected function getReferrerUrl() {
          if (array_key_exists('HTTP_REFERER', $_SERVER) && $_SERVER['HTTP_REFERER'] != '') {
              return $_SERVER['HTTP_REFERER'];
          }
          return '';
      }
  
      protected function getOldCookies() {
          $oldCookieNames = array('PAPCookie_Sale', 'PAPCookie_FirstClick', 'PAPCookie_LastClick');
          $oldCookies = '';
          foreach ($oldCookieNames as $oldCookieName) {
              if (array_key_exists($oldCookieName, $_COOKIE) && $_COOKIE[$oldCookieName] != '') {
                  $oldCookies .= $oldCookieName.'='.urlencode($_COOKIE[$oldCookieName]).'||';
              }
          }
          return rtrim($oldCookies, '||');
      }
  
      /**
       * @return Gpf_Net_Http_Request
       */
      protected function getGetParams() {
          $getParams = new Gpf_Net_Http_Request();
          if (is_array($_GET) && count($_GET) > 0) {
              foreach ($_GET as $name => $value) {
                  $getParams->addQueryParam($name, $value);
              }
          }
          return $getParams;
      }
  
      protected function save3rdPartyCookie($name, $value, $expire, $overwrite, $cookieDomainValidity = null) {
          if (!$overwrite && isset($_COOKIE[$name]) && $_COOKIE[$name] != '') {
              return;
          }
          if ($cookieDomainValidity == null) {
              Gpf_Http::setCookie($name, $value, $expire, "/");
          } else {
              Gpf_Http::setCookie($name, $value, $expire, "/", $cookieDomainValidity);
          }
      }
  
  }

} //end Pap_Api_Tracker

if (!class_exists('Pap_Api_SaleTracker', false)) {
  class Pap_Api_SaleTracker extends Pap_Api_Tracker {
  
      /**
       * @param string $saleScriptUrl Url to sale.php script
       */
      public function __construct($saleScriptUrl, $debug = false) {
          $session = new Gpf_Api_Session(str_replace('sale.php', 'server.php', $saleScriptUrl));
          if ($debug) {
              $session->setDebug(true);
          }
          parent::__construct($session);
      }
  
      /**
       * sets value of the cookie to be used
       *
       * @param string $value
       */
      public function setCookieValue($value) {
          $this->setVisitorId($value);
      }
  
      /**
       * Registers all created sales
       */
      public function register() {
          $this->track();
      }
  }

} //end Pap_Api_SaleTracker

if (!class_exists('Pap_Api_ClickTracker', false)) {
  class Pap_Api_ClickTracker extends Pap_Api_Tracker {
      
      private $affiliateId;
      private $bannerId;
      private $campaignId;
      private $data1;
      private $data2;
      private $channelId;
      
      /**
       * This class requires correctly initialized merchant session
       * @param Gpf_Api_Session $session
       */
      public function __construct(Gpf_Api_Session $session) {
          parent::__construct($session);
      }
      
          /**
       * Use this function if you want to explicitly specify affiliate which made the click
       *
       * @param $affiliateId
       */
      public function setAffiliateId($affiliateId) {
          $this->affiliateId = $affiliateId;
      }
  
      /**
       * Use this function if you want to explicitly specify banner through which the click was made
       *
       * @param $bannerId
       */
      public function setBannerId($bannerId) {
          $this->bannerId = $bannerId;
      }
  
      /**
       * Use this function if you want to explicitly specify campaign for this click
       *
       * @param $campaignId
       */
      public function setCampaignID($campaignId) {
          $this->campaignId = $campaignId;
      }
  
      public function setData1($data1) {
          $this->data1 = $data1;
      }
  
      public function setData2($data2) {
          $this->data2 = $data2;
      }
  
      /**
       * Use this function if you want to explicitly specify channel through which this click was made
       *
       * @param $bannerId
       */
      public function setChannel($channelId) {
          $this->channelId = $channelId;
      }
      
      /**
       * @return Gpf_Net_Http_Request
       */
      protected function getGetParams() {
          $getParams = parent::getGetParams();
          if ($this->affiliateId != '') {
              $getParams->addQueryParam('AffiliateID', $this->affiliateId);
          }
          if ($this->bannerId != '') {
              $getParams->addQueryParam('BannerID', $this->bannerId);
          }
          if ($this->campaignId != '') {
              $getParams->addQueryParam('CampaignID', $this->campaignId);
          }
          if ($this->channelId != '') {
              $getParams->addQueryParam('chan', $this->channelId);
          }
          if ($this->data1 != '') {
              $getParams->addQueryParam('pd1', $this->data1);
          }
          if ($this->data2 != '') {
              $getParams->addQueryParam('pd2', $this->data2);
          }
          return $getParams;
      }
  }

} //end Pap_Api_ClickTracker

if (!class_exists('Pap_Api_RecurringCommission', false)) {
  class Pap_Api_RecurringCommission extends Pap_Api_Object {
  
      public function __construct(Gpf_Api_Session $session) {
          parent::__construct($session);
          $this->class = 'Pap_Features_RecurringCommissions_RecurringCommissionsForm';
      }
      
      public function setOrderId($value, $operator = self::OPERATOR_EQUALS) { 
      	$this->setField('orderid', $value, $operator);    
      }
  
      public function setTotalCost($value) { 
      	$this->setField('totalcost', $value);    
      }
  
      public function getId() {
          return $this->getField('recurringcommissionid');
      }
      
      protected function getPrimaryKey() {
      	return "id";
      }
  
      protected function getGridRequest() {
  		return new Pap_Api_RecurringCommissionsGrid($this->getSession());
      }
  
      public function createCommissions() {
          $request = new Gpf_Rpc_ActionRequest('Pap_Features_RecurringCommissions_RecurringCommissionsForm',
                                               'createCommissions', $this->getSession());
          $request->addParam('id', $this->getId());
          $request->addParam('orderid', $this->getField('orderid'));
          $request->addParam('totalcost', $this->getField('totalcost'));
          if ($this->getSession()->getSessionId() == '') {
              $request->addParam('initSession', Gpf::YES);
          }
          $request->sendNow();
          $action = $request->getAction();
          if ($action->isError()) {
              throw new Gpf_Exception($action->getErrorMessage());
          }
      }
  
      public function createCommissionsReturnIds() {
          $request = new Gpf_Rpc_DataRequest('Pap_Features_RecurringCommissions_RecurringCommissionsForm',
                                              'createCommissionsReturnIds', $this->getSession());
          $request->addParam('id', $this->getId());
          $request->addParam('orderid', $this->getField('orderid'));
          $request->addParam('totalcost', $this->getField('totalcost'));
          if ($this->getSession()->getSessionId() == '') {
              $request->addParam('initSession', Gpf::YES);
          }
          $request->sendNow();
          return $request->getData()->getValue(Gpf_Rpc_Action::IDS);
      }
  }

} //end Pap_Api_RecurringCommission

if (!class_exists('Pap_Api_RecurringCommissionsGrid', false)) {
  class Pap_Api_RecurringCommissionsGrid extends Gpf_Rpc_GridRequest {
      
      private $dataValues = null;
      
      public function __construct(Gpf_Api_Session $session) {
          if($session->getRoleType() == Gpf_Api_Session::AFFILIATE) {
              throw new Exception("This class can be used only by merchant!");
          } else {
              parent::__construct("Pap_Features_RecurringCommissions_RecurringCommissionsGrid", "getRows", $session);
          }
      }
  }

} //end Pap_Api_RecurringCommissionsGrid

if (!class_exists('Pap_Api_PayoutsGrid', false)) {
  class Pap_Api_PayoutsGrid extends Gpf_Rpc_GridRequest {
  
      const PAP_MERCHANTS_PAYOUT_PAYAFFILIATESFORM_SUCCESS = 'success';
  
      private $affiliatesToPay = array();
      
      public function __construct(Gpf_Api_Session $session) {
          if($session->getRoleType() == Gpf_Api_Session::AFFILIATE) {
              throw new Gpf_Exception('Only merchant can view payouts grid. Please login as merchant.');
          }
          
          $className = 'Pap_Merchants_Payout_PayAffiliatesGrid';
          parent::__construct($className, 'getRows', $session);
      }
      
      public function payAffiliates($paymentNote = '', $affiliateNote = '', $send_payment_to_affiliate = Gpf::NO, $send_generated_invoices_to_merchant = Gpf::NO, $send_generated_invoices_to_affiliates = Gpf::NO) {
          $this->checkMerchantRole();
          if (count($this->getAffiliatesToPay()) == 0) {
              throw new Gpf_Exception('You must select at least one affiliate to pay.');
          }
          try {
             $this->sendPayTransactionsCall($paymentNote, $affiliateNote, $send_payment_to_affiliate, $send_generated_invoices_to_merchant, $send_generated_invoices_to_affiliates);
          } catch (Gpf_Exception $e) {
              throw new Gpf_Exception('Error during paying affiliates: ' . $e->getMessage());
          }
      }
  
      protected function sendPayTransactionsCall($paymentNote, $affiliateNote, $send_payment_to_affiliate, $send_generated_invoices_to_merchant, $send_generated_invoices_to_affiliates) {
          $request = new Gpf_Rpc_ActionRequest('Pap_Merchants_Payout_PayAffiliatesForm', 'payAffiliates', $this->apiSessionObject);
          $request->addParam('paymentNote', $paymentNote);
          $request->addParam('affiliateNote', $affiliateNote);
          $request->addParam('send_payment_to_affiliate', $send_payment_to_affiliate);
          $request->addParam('send_generated_invoices_to_merchant', $send_generated_invoices_to_merchant);
          $request->addParam('send_generated_invoices_to_affiliates', $send_generated_invoices_to_affiliates);
          $request->addParam('ids', new Gpf_Rpc_Array($this->getAffiliatesToPay()));
          $request->addParam('filters', new Gpf_Rpc_Array($this->getFilters()));
          $request->sendNow();
  
          if ($request->getResponseError() != '') {
              throw new Gpf_Exception($request->getResponseError());
          }
          $response = $request->getStdResponse();
  
          if ($response->success == 'Y' && strpos($response->infoMessage, self::PAP_MERCHANTS_PAYOUT_PAYAFFILIATESFORM_SUCCESS) !== 0 ) {
              $request->sendNow();
          }
      }
  
      public function addAllAffiliatesToPay() {
          $this->checkMerchantRole();
          try {
              $grid = $this->getGrid();
              $recordset = $grid->getRecordset();
              foreach($recordset as $rec) {
                  $this->addAffiliateToPay($rec->get('id'));
              }
          } catch (Gpf_Exception $e) {
              throw new Gpf_Exception('You must load list of affiliates first!');
          }
      }
      
      public function addAffiliateToPay($affiliateId) {
          if(!in_array($affiliateId, $this->affiliatesToPay)) {
              $this->affiliatesToPay[] = $affiliateId;
          }
      }
      
      public function getAffiliatesToPay() {
          return $this->affiliatesToPay;
      }
      
      private function checkMerchantRole() {
          if($this->apiSessionObject->getRoleType() == Gpf_Api_Session::AFFILIATE) {
              throw new Gpf_Exception('Only merchant is allowed to pay affiliates.');
          }
      }
  }

} //end Pap_Api_PayoutsGrid

if (!class_exists('Pap_Api_PayoutsHistoryGrid', false)) {
  class Pap_Api_PayoutsHistoryGrid extends Gpf_Rpc_GridRequest {
      public function __construct(Gpf_Api_Session $session) {
      	if($session->getRoleType() == Gpf_Api_Session::AFFILIATE) {
              throw new Gpf_Exception('Only merchant can view payouts history. Please login as merchant.');
          }
          parent::__construct('Pap_Merchants_Payout_PayoutsHistoryGrid', 'getRows', $session);
      }
      
      public function getPayeesDeatilsInfo($payoutId) {
          $this->checkMerchantRole();
          $request = new Gpf_Rpc_DataRequest('Pap_Merchants_Payout_PayoutsHistoryGrid', 'payeesDetails', $this->apiSessionObject);
          $request->addFilter('id', 'E', $payoutId);
          $request->sendNow();
          $results = $request->getData();
          
          $output = array();
          
          for ($i=0; $i<$results->getSize(); $i++) {
              $userinfo = $results->getValue('user' . $i);
              $data = new Gpf_Rpc_Data();
              $data->loadFromObject($userinfo);
              $output[] = $data;
          }
          return $output;
      }
      
      private function checkMerchantRole() {
          if($this->apiSessionObject->getRoleType() == Gpf_Api_Session::AFFILIATE) {
              throw new Gpf_Exception('Only merchant is allowed to to view payee details.');
          }
          return true;
      }
  }

} //end Pap_Api_PayoutsHistoryGrid

if (!class_exists('Pap_Api_Session', false)) {
  class Pap_Api_Session extends Gpf_Api_Session {
  
      const AUTHENTICATE_CLASS_NAME = 'Pap_Api_AuthService';
  
      
      protected function getAuthenticateClassName() {
          return self::AUTHENTICATE_CLASS_NAME;
      }
  }

} //end Pap_Api_Session

if (!class_exists('Gpf_Net_Http_Client', false)) {
    class Gpf_Net_Http_Client extends Gpf_Net_Http_ClientBase {

        protected function isNetworkingEnabled() {
            return true;
        }

        protected function setProxyServer(Gpf_Net_Http_Request $request) {
        }
    }
}
/*
VERSION
8196792c989f9a0c0f267921691e8519
*/
?>