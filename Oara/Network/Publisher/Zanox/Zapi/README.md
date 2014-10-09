zanox API client in PHP
=======================

A)  Requirements

    1) Zanox credentials

       a) Your connect ID
       b) Your secret key for secure API calls
       c) Your public key (optional)


    2) Environment

       a) PHP 5



B)  Setup

    1) Copy all files into your server location, where the client should be used
    2) Make sure all file permissions are set correctly



C)  Usage

    1) Include the API client base into your code

       <code>

            require_once '<your API client include dir>ApiClient.php';

       </code>


    2) Instantiate a API client object

       The client can be invoked with three different protocols (XML, JSON SOAP)
       and two different version (2009-07-01, 2011-03-01). The
       default protocol is XML and the default version is 2011-03-01 if no
       parameter are given.

       Examples:

       a) Instantiate API client with default values

          <code>

              $api = ApiClient::factory();

          </code>


       b) Instantiate API client with JSON as protocol and default version 2011-03-01

          <code>

              $api = ApiClient::factory(PROTOCOL_JSON, VERSION_DEFAULT);

          </code>



    3) Setup your credential information

       Setup

       <code>

           $connectId = '__your_connect_id__';
           $secretKey = '__your_secrect_key__';

           $api->setConnectId($connectId);
           $api->setSecretKey($secretKey);

      </code>



    4) Make the API request

       Use the client object to make the client request to the Zanox API.

       a) API request for searchPrograms with XML as protocol

       <code>

           $xml = $api->searchPrograms();
           print_r($xml);

       </code>


       b) API request for searchPrograms with JSON as protocol

       <code>

           $json = $api->searchPrograms();
           print_r($json);

       </code>


D)  Usage examples:

    1) Get admedia

       <code>

            $api = ApiClient::factory(PROTOCOL_XML, VERSION_DEFAULT);

            $api->setConnectId($connectId);
            $api->setSecretKey($secretKey);

            $programId      = 738;
            $region         = NULL;
            $format         = 8;
            $partnerShip    = NULL;
            $purpose        = NULL;
            $admediumType   = 'image';
            $categoryId     = NULL;
            $adspaceId      = NULL;
            $page           = 0;
            $items          = 10;

            $xml = $api->getAdmedia ($programId, $region, $format, $partnerShip,
                            $purpose, $admediumType, $categoryId, $adspaceId,
                            $page, $items);
            print_r($xml);

       </code>


    2) Search products

       <code>

            $api = ApiClient::factory(PROTOCOL_XML, VERSION_DEFAULT);

            $api->setConnectId($connectId);
            $api->setSecretKey($secretKey);

            $query      = "auto";
            $searchType = 'phrase';
            $programs   = array(660,10,20,30);
            $region     = NULL;
            $categoryId = NULL;
            $programId  = array();
            $hasImages  = true;
            $minPrice   = 0;
            $maxPrice   = NULL;
            $adspaceId  = NULL;
            $page       = 0;
            $items      = 10;

            $xml = $api->searchProducts($query, $searchType, $region,
                            $categoryId, $programId, $hasImages, $minPrice,
                            $maxPrice, $adspaceId, $page, $items);
            print_r($xml);

       </code>
