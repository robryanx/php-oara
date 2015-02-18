<?php

require_once DIR . '/version/2009-07-01/model/IMethods.php';
require_once DIR . '/includes/ApiMethods.php';
require_once DIR . '/includes/ApiConstSoap.php';

/**
 * Soap Api Methods
 *
 * The module implements all Api methods defined by the IMethods interface.
 *
 * Supported Version: PHP >= 5.0
 *
 * @author      Thomas Nicolai (thomas.nicolai@sociomantic.com)
 * @author      Lars Kirchhoff (lars.kirchhoff@sociomantic.com)
 *
 * @see         http://wiki.zanox.com/en/Web_Services
 * @see         http://apps.zanox.com
 *
 * @package     ApiClient
 * @version     2009-09-01
 * @copyright   Copyright (c) 2007-2009 zanox.de AG
 */
class SoapMethods extends ApiMethods implements IMethods
{

    /**
     * Get a single product.
     *
     * @param      string      $zupId          product id hash
     * @param      int         $adspaceId      adspace id (optional)
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            single product item or false
     */
    public function getProduct( $zupId, $adspaceId = NULL )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['zupId']     = $zupId;
        $parameter['adspaceId'] = $adspaceId;

        $this->setSecureApiCall(false);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get product categories.
     *
     * @param      int         $rootCategory   category id (optional)
     * @param      bool        $includeChilds  include child nodes (optional)
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            single product item or false
     */
    public function getProductCategories( $rootCategory = 0, $includeChilds = false )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['rootCategory']  = $rootCategory;
        $parameter['includeChilds'] = $includeChilds;

        $this->setSecureApiCall(false);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get products by advertiser program.
     *
     * @param      int         $programId      advertiser program id
     * @param      int         $adspaceId      adspace id (optional)
     * @param      string      $modifiedDate   last modification date (optional)
     * @param      int         $page           page of result set (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            product result set or false
     */
    public function getProducts( $programId, $adspaceId = NULL,
        $modifiedDate = NULL, $page = 0, $items = 10 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['programId']    = $programId;
        $parameter['adspaceId']    = $adspaceId;
        $parameter['modifiedDate'] = $modifiedDate;
        $parameter['page']         = $page;
        $parameter['items']        = $items;

        $this->setSecureApiCall(false);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Search for products.
     *
     * @param      string      $query          search string
     * @param      string      $searchType     search type (optional)
     *                                         (contextual or phrase)
     * @param      string      $ip             products with sales region
     *                                         within the region of the ip
     *                                         address (optional)
     * @param      string      $region         limit search to region (optional)
     * @param      int         $categoryId     limit search to categorys (optional)
     * @param      array       $programId      limit search to program list of
     *                                         programs (optional)
     * @param      boolean     $hasImages      products with images (optional)
     * @param      float       $minPrice       minimum price (optional)
     * @param      float       $maxPrice       maximum price (optional)
     * @param      int         $adspaceId      adspace id (optional)
     * @param      int         $page           page of result set (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            list of products or false
     */
    public function searchProducts( $query, $searchType = 'phrase', $ip = NULL,
        $region = NULL, $categoryId = NULL, $programId = array(),
        $hasImages = true, $minPrice = 0, $maxPrice = NULL, $adspaceId = NULL,
        $page = 0, $items = 10 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['query']      = $query;
        $parameter['searchType'] = $searchType;
        $parameter['ip']         = $ip;
        $parameter['region']     = $region;
        $parameter['categoryId'] = $categoryId;
        $parameter['programId']  = $programId;
        $parameter['hasImages']  = $hasImages;
        $parameter['minPrice']   = $minPrice;
        $parameter['maxPrice']   = $maxPrice;
        $parameter['adspaceId']  = $adspaceId;
        $parameter['page']       = $page;
        $parameter['items']      = $items;

        $this->setSecureApiCall(false);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }


    /**
     * Retrieve a single zanox advertiser program item.
     *
     * @param      int         $programId      id of program to retrieve
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            program item or false
     */
    public function getProgram ( $programId )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['programId'] = $programId;

        $this->setSecureApiCall(false);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get advertiser program categories.
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            category result set or false
     */
    public function getProgramCategories()
    {
        $method = ucfirst(__FUNCTION__);

        $this->setSecureApiCall(false);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get advertiser program applications by adspace.
     *
     * @param      int         $adspaceId      advertising space id
     * @param      int         $page           page of result set (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            program result set or false
     */
    public function getProgramsByAdspace( $adspaceId, $page = 0, $items = 10 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['adspaceId']    = $adspaceId;
        $parameter['page']         = $page;
        $parameter['items']        = $items;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Search zanox advertiser programs.
     *
     * @param      string      $query          search string
     * @param      string      $startDate      program start date (optional)
     * @param      string      $partnerShip    partnership status (optional)
     *                                         (direct or indirect)
     * @param      boolean     $hasProducts    program has product data
     * @param      string      $ip             programs in region of ip address
     * @param      string      $region         program region
     * @param      string      $categoryId     program category id
     * @param      int         $page           page of result set
     * @param      int         $items          items per page
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            programs result set or false
     */
    public function searchPrograms( $query, $startDate = NULL,
        $partnerShip = NULL, $hasProducts = false, $ip = NULL,
        $region = NULL, $categoryId = NULL, $page = 0, $items = 10 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['query']       = $query;
        $parameter['startDate']   = $startDate;
        $parameter['partnerShip'] = $partnerShip;
        $parameter['hasProducts'] = $hasProducts;
        $parameter['ip']          = $ip;
        $parameter['region']      = $region;
        $parameter['categoryId']  = $categoryId;
        $parameter['page']        = $page;
        $parameter['items']       = $items;

        $this->setSecureApiCall(false);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Create program application for a given adspace.
     *
     * @param      int         $programId     advertiser program id
     * @param      int         $adspaceId     advertising space id
     *
     * @access     public
     * @category   signature
     *
     * @return     boolean                    true or false
     */
    public function createProgramApplication( $programId, $adspaceId )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['programId'] = $programId;
        $parameter['adspaceId'] = $adspaceId;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Delete program application.
     *
     * @param      int         $programId     advertiser program id
     * @param      int         $adspaceId     advertising space id
     *
     * @access     public
     * @category   signature
     *
     * @return     boolean                     true or false
     */
    public function deleteProgramApplication ( $programId, $adspaceId )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['programId'] = $programId;
        $parameter['adspaceId'] = $adspaceId;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get a single admedium.
     *
     * @param      int         $admediumId     advertising medium id
     * @param      int         $adspaceId      advertising space id (optional)
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            single product item or false
     */
    public function getAdmedium( $admediumId, $adspaceId = NULL )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['admediumId'] = $admediumId;
        $parameter['adspaceId']  = $adspaceId;

        $this->setSecureApiCall(false);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get admedium categories.
     *
     * @param      int         $programId      program admedium categories
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            list of admedium categories
     */
    public function GetAdmediumCategories( $programId )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['programId'] = $programId;

        $this->setSecureApiCall(false);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Retrieve all advertising media items.
     *
     * Note: The admedium categories are specific to each advertiser program.
     *
     * Supported admedium types are
     *
     *    801: Text
     *    802: Image
     *    803: Image with text
     *    804: HTML (may also include Flash)
     *    805: Script (may also include Flash)
     *
     * @param      int         $programId      advertiser program id (optional)
     * @param      string      $ip             ip address (geo feature) (optional)
     * @param      string      $region         limit search to region (optional)
     * @param      string      $format         admedia format (optional)
     * @param      string      $partnerShip    partnership status (optional)
     *                                         (direct or indirect)
     * @param      string      $purpose        purpose of admedia (optional)
     *                                         (startPage, productDeeplink,
     *                                         categoryDeeplink, searchDeeplink)
     * @param      string      $admediumType   type of admedium (optional)
     *                                         (html, script, lookatMedia, image,
     *                                         imageText, text)
     * @param      int         $categoryId     admedium category id (optional)
     * @param      int         $adspaceId      adspace id (optional)
     * @param      int         $page           page of result set (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            admedia result set or false
     */
    public function getAdmedia( $programId = NULL, $ip = NULL, $region = NULL,
        $format = NULL, $partnerShip = NULL, $purpose = NULL,
        $admediumType = NULL, $categoryId = NULL, $adspaceId = NULL, $page = 0,
        $items = 10 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['programId']    = $programId;
        $parameter['ip']           = $ip;
        $parameter['region']       = $region;
        $parameter['format']       = $format;
        $parameter['partnerShip']  = $partnerShip;
        $parameter['purpose']      = $purpose;
        $parameter['admediumType'] = $admediumType;
        $parameter['categoryId']   = $categoryId;
        $parameter['adspaceId']    = $adspaceId;
        $parameter['page']         = $page;
        $parameter['items']        = $items;

        $this->setSecureApiCall(false);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Returns a single advertising spaces.
     *
     * @param      int         $adspaceId      advertising space id
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            adspace item or false
     */
    public function getAdspace( $adspaceId )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['adspaceId'] = $adspaceId;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Returns all advertising spaces.
     *
     * @param      int         $page           result set page (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            adspaces result set or false
     */
    public function getAdspaces ( $page = 0, $items = 10 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['page']  = $page;
        $parameter['items'] = $items;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Create advertising space (signature).
     *
     * @param      string      $name           adspace name
     * @param      string      $lang           language of adspace (e.g. en)
     * @param      string      $url            url of adspace
     * @param      string      $contact        contact address (email)
     * @param      string      $description    description of adspace
     * @param      string      $adspaceType    adspace typ (website, email or searchengine)
     * @param      array       $scope          adspace scope (private or business)
     * @param      int         $visitors       adspace monthly visitors
     * @param      int         $impressions    adspace monthly page impressions
     * @param      string      $keywords       keywords for adspace (optional)
     * @param      array       $regions        adspace customer regions (optional)
     * @param      array       $categories     adspace categories (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            adspace item or false
     */
    public function createAdspace ( $name, $lang, $url, $contact, $description,
        $adspaceType, $scope, $visitors, $impressions, $keywords = NULL,
        $regions = array(), $categories = array() )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['adspaceItem']['name']        = $name;
        $parameter['adspaceItem']['lang']        = $lang;
        $parameter['adspaceItem']['url']         = $url;
        $parameter['adspaceItem']['contact']     = $contact;
        $parameter['adspaceItem']['description'] = $description;
        $parameter['adspaceItem']['adspaceType'] = $adspaceType;
        $parameter['adspaceItem']['scope']       = $scope;
        $parameter['adspaceItem']['visitors']    = $visitors;
        $parameter['adspaceItem']['impressions'] = $impressions;
        $parameter['adspaceItem']['keywords']    = $keywords;
        $parameter['adspaceItem']['regions']     = $regions;
        $parameter['adspaceItem']['categories']  = $categories;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Update advertising space.
     *
     * @param      int         $adspaceId      adspace id
     * @param      string      $name           adspace name
     * @param      string      $lang           language of adspace (e.g. en)
     * @param      string      $url            url of adspace
     * @param      string      $contact        contact address (email)
     * @param      string      $description    description of adspace
     * @param      string      $adspaceType    adspace typ (website, email or searchengine)
     * @param      array       $scope          adspace scope (private or business)
     * @param      int         $visitors       adspace monthly visitors
     * @param      int         $impressions    adspace monthly page impressions
     * @param      string      $keywords       keywords for adspace (optional)
     * @param      array       $regions        adspace customer regions (optional)
     * @param      array       $categories     adspace categories (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            adspace item or false
     */
    public function updateAdspace ( $adspaceId, $name, $lang, $url, $contact,
        $description, $adspaceType, $scope, $visitors, $impressions,
        $keywords = NULL, $regions = array(), $categories = array() )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['adspaceItem']['id']          = $adspaceId;
        $parameter['adspaceItem']['name']        = $name;
        $parameter['adspaceItem']['lang']        = $lang;
        $parameter['adspaceItem']['url']         = $url;
        $parameter['adspaceItem']['contact']     = $contact;
        $parameter['adspaceItem']['description'] = $description;
        $parameter['adspaceItem']['adspaceType'] = $adspaceType;
        $parameter['adspaceItem']['scope']       = $scope;
        $parameter['adspaceItem']['visitors']    = $visitors;
        $parameter['adspaceItem']['impressions'] = $impressions;
        $parameter['adspaceItem']['keywords']    = $keywords;
        $parameter['adspaceItem']['regions']     = $regions;
        $parameter['adspaceItem']['categories']  = $categories;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }


    /**
     * Removes advertising space.
     *
     * @param      int         $adspaceId      advertising space id
     *
     * @access     public
     * @category   signature
     *
     * @return     boolean                     true on success
     */
    public function deleteAdspace( $adspaceId )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['adspaceId']  = $adspaceId;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Return zanox user profile.
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            profile item
     */
    public function getProfile()
    {
        $method = ucfirst(__FUNCTION__);

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Update zanox user profile.
     *
     * @param      array       $profileId      user profile id
     * @param      array       $firstName      first name
     * @param      array       $lastName       last name
     * @param      array       $email          email address
     * @param      array       $country        country or residence
     * @param      array       $street1        street 1
     * @param      array       $street2        street 2 (optional)
     * @param      array       $city           city
     * @param      array       $company        name of company (optional)
     * @param      array       $phone          phone number (optional)
     * @param      array       $mobile         mobile number (optional)
     * @param      array       $fax            fax number (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     boolean                     true on success
     */
    public function updateProfile( $profileId, $firstName, $lastName, $email,
        $country, $street1, $street2 = NULL, $city, $zipcode, $company = NULL,
        $phone = NULL, $mobile = NULL, $fax = NULL )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['profileItem']['id']        = $profileId;
        $parameter['profileItem']['firstName'] = $firstName;
        $parameter['profileItem']['lastName']  = $lastName;
        $parameter['profileItem']['email']     = $email;
        $parameter['profileItem']['country']   = $country;
        $parameter['profileItem']['street1']   = $street1;
        $parameter['profileItem']['street2']   = $street2;
        $parameter['profileItem']['city']      = $city;
        $parameter['profileItem']['zipcode']   = $zipcode;
        $parameter['profileItem']['company']   = $company;
        $parameter['profileItem']['phone']     = $phone;
        $parameter['profileItem']['mobile']    = $mobile;
        $parameter['profileItem']['fax']       = $fax;

        // stupid bug fix ;-) its not really getting updated to 10 ;-)
        $parameter['profileItem']['adrank']    = 10;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get back accounts.
     *
     * @param      int         $page           result set page (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            account balances result set or
     *                                         false
     */
    public function getBankAccounts( $page = 0, $items = 10 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['page']  = $page;
        $parameter['items'] = $items;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get single back account.
     *
     * @param      int         $bankAccountId  result set page
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            account balances result set or
     *                                         false
     */
    public function getBankAccount( $bankAccountId )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['bankAccountId']  = $bankAccountId;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get account balance
     *
     * @param      int         $currency       currence code of balance account
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            payment item or false
     */
    public function getBalance( $currency )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['currency']  = $currency;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get currency account balances.
     *
     * @param      int         $page           result set page (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            account balances result set or
     *                                         false
     */
    public function getBalances( $page = 0, $items = 10 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['page']  = $page;
        $parameter['items'] = $items;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get payment transactions of the current zanox account.
     *
     * @param      int         $page           page of result set (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            payments result set or false
     */
    public function getPayments( $page = 0, $items = 10 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['page']  = $page;
        $parameter['items'] = $items;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get a single payment item.
     *
     * @param      int         $paymentId      payment item id
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            payment item or false
     */
    public function getPayment( $paymentId )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['paymentId']  = $paymentId;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get basic sales/leads report.
     *
     * @param      string      $fromDate       report start date
     * @param      string      $toDate         report end date
     * @param      string      $dateType       type of date to filter by (optional)
     *                                         (clickDate, trackingDate,
     *                                         modifiedDate)
     * @param      string      $currency       currency (optional)
     * @param      int         $programId      program id (optional)
     * @param      int         $admediumId     admedium id (optional)
     * @param      int         $admediumFormat admedium format id (optional)
     * @param      int         $adspaceId      adspace id (optional)
     * @param      string      $reviewState    filter by review status (optional)
     *                                         (confirmed, open, rejected or
     *                                         approved)
     * @param      string      $groupBy        group report by option (optional)
     *                                         (country, region, city, currency,
     *                                         admedium, program, adspace,
     *                                         linkFormat, reviewState,
     *                                         trackingCategory, month, day,
     *                                         hour, year, dayOfWeek)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            payment item or false
     */
    public function getReportBasic( $fromDate, $toDate, $dateType = NULL,
        $currency = NULL, $programId = NULL, $admediumId = NULL,
        $admediumFormat = NULL, $adspaceId = NULL, $reviewState = NULL,
        $groupBy = NULL )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['fromDate']       = $fromDate;
        $parameter['toDate']         = $toDate;
        $parameter['dateType']       = $dateType;
        $parameter['currency']       = $currency;
        $parameter['programId']      = $programId;
        $parameter['admediumId']     = $admediumId;
        $parameter['admediumFormat'] = $admediumFormat;
        $parameter['adspaceId']      = $adspaceId;
        $parameter['reviewState']    = $reviewState;
        $parameter['groupBy']        = $groupBy;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }


    /**
     * Get sales report.
     *
     * Sample usage:
     * <code>
     *
     *     $zx = ZanoxAPI::factory(PROTOCOL_XML);
     *
     *     $zx->getSales('2009-09-01');
     *
     * </code>
     *
     * @param      string      $date           date of sales
     * @param      string      $dateType       type of date to filter by (optional)
     *                                         (clickDate, trackingDate,
     *                                         modifiedDate)
     * @param      int         $programId      filter by program id (optional)
     * @param      int         $adspaceId      filter by adspace id (optional)
     * @param      array       $reviewState    filter by review status (optional)
     *                                         (confirmed, open, rejected or
     *                                         approved)
     * @param      int         $page           page of result set (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            sales result set or false
     */
    public function getSales ( $date, $dateType = NULL, $programId = NULL,
        $adspaceId = NULL, $reviewState = NULL, $page = 0, $items = 10 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['fromDate']    = $programId;
        $parameter['adspaceId']   = $adspaceId;
        $parameter['reviewState'] = $reviewState;
        $parameter['date']        = $date;
        $parameter['dateType']    = $dateType;
        $parameter['page']        = $page;
        $parameter['items']       = $items;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }


    /**
     * Get single sale item.
     *
     * @param      int         $saleId         sale id
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            sales result set or false
     */
    public function getSale ( $saleId )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['saleId'] = $saleId;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get leads report.
     *
     * ---
     *
     * Sample usage:
     * <code>
     *
     *     $zx = ZanoxAPI::factory(PROTOCOL_XML);
     *
     *     $zx->getLeads('2009-09-01');
     *
     * </code>
     *
     * ---
     *
     * @param      string      $date           date of sales
     * @param      string      $dateType       type of date to filter by (optional)
     *                                         (clickDate, trackingDate,
     *                                         modifiedDate)
     * @param      int         $programId      filter by program id (optional)
     * @param      int         $adspaceId      filter by adspace id (optional)
     * @param      array       $reviewState    filter by review status (optional)
     *                                         (confirmed, open, rejected or
     *                                         approved)
     * @param      int         $page           page of result set (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            sales result set or false
     */
    public function getLeads ( $date, $dateType = NULL, $programId = NULL,
        $adspaceId = NULL, $reviewState = NULL, $page = 0, $items = 10 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['fromDate']    = $programId;
        $parameter['adspaceId']   = $adspaceId;
        $parameter['reviewState'] = $reviewState;
        $parameter['date']        = $date;
        $parameter['dateType']    = $dateType;
        $parameter['page']        = $page;
        $parameter['items']       = $items;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }


    /**
     * Get single sale item.
     *
     * @param      int         $leadId         lead id
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            sales result set or false
     */
    public function getLead ( $leadId )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['leadId'] = $leadId;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Returns new OAuth user session
     *
     * @param      string      $authToken      authentication token
     *
     * @access     public
     *
     * @return     object                      user session
     */
    public function getSession ( $authToken )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['authToken'] = $authToken;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_CONNECT, $method, $parameter);

        if ( $result )
        {
            return $result->session;
        }

        return false;
    }



    /**
     * Closes OAuth user session
     *
     * @param      string      $connectId      connect ID
     *
     * @access     public
     *
     * @return     bool                        returns true on success
     */
    public function closeSession ( $connectId )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['connectId'] = $connectId;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_CONNECT, $method, $parameter);

        if ( $result )
        {
            return true;
        }

        return false;
    }



    /**
     * Get zanox User Interface Url
     *
     * @param      string      $connectId      connect ID
     * @param      string      $sessionKey     session key
     *
     * @access     public
     * @category   signature
     *
     * @return     bool                        returns true on success
     */
    public function getUiUrl( $connectId, $sessionKey )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['connectId']  = $connectId;
        $parameter['sessionKey'] = $sessionKey;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_CONNECT, $method, $parameter);

        if ( $result )
        {
            return true;
        }

        return false;
    }


}

?>