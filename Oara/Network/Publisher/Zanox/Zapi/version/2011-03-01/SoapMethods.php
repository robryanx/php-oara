<?php

require_once DIR . '/version/2011-03-01/model/IMethods.php';
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
 * @version     2011-03-01
 * @copyright   Copyright (c) 2007-2011 zanox.de AG
 */
class SoapMethods extends ApiMethods implements IMethods
{

    /**
     * done
     *
     * Get a single product.
     *
     * @param      string      $productId      product id hash
     * @param      int         $adspaceId      adspace id (optional)
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            single product item or false
     */
    public function getProduct ( $productId, $adspaceId = NULL )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['zupId']     = $productId;
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
     * done
     *
     * Search for products.
     *
     * @param      string      $query          search string
     * @param      string      $searchType     search type (optional)
     *                                         (contextual or phrase)
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
    public function searchProducts ( $query, $searchType = 'phrase',
        $region = NULL, $categoryId = NULL, $programId = array(),
        $hasImages = true, $minPrice = 0, $maxPrice = NULL, $adspaceId = NULL,
        $page = 0, $items = 10 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['query']      = $query;
        $parameter['searchType'] = $searchType;
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
     * done
     *
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
    public function getProductCategories ( $rootCategory = 0, $includeChilds = false )
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
     * Get single incentive.
     *
     * @param       int         $incentiveId    incentive id (mandatory)
     * @param       int         $adspaceId      adspace id (optional)
     *
     * @access      public
     * @category    nosignature
     *
     * @return      object or string            incentive or false
     */
    public function getIncentive ( $incentiveId, $adspaceId = NULL )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['incentiveId']   = $incentiveId;
        $parameter['adspaceId']     = $adspaceId;

        $this->setSecureApiCall(false);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get single exclusive incentive.
     *
     * @param       int         $incentiveId    incentive id (mandatory)
     * @param       int         $adspaceId      adspace id (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string            incentive or false
     */
    public function getExclusiveIncentive ( $incentiveId, $adspaceId = NULL )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['incentiveId']   = $incentiveId;
        $parameter['adspaceId']     = $adspaceId;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Search for incentives.
     *
     * @param       int         $programId      limit search to program list of
     *                                          programs (optional)
     * @param       int         $adspaceId      adspace id (optional)
     * @param       string      $incentiveType  type of incentive (optional)
     *                                          (coupons, samples, bargains,
     *                                          freeProducts, noShippingCosts,
     *                                          lotteries)
     * @param       string      $region         program region (optional)
     * @param       int         $page           page of result set (optional)
     * @param       int         $items          items per page (optional)
     *
     * @access      public
     * @category    nosignature
     *
     * @return      object or string            list of incentives or false
     */
    public function searchIncentives ( $programId = NULL, $adspaceId = NULL,
        $incentiveType = NULL, $region = NULL, $page = 0, $items = 10 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['programId']     = $programId;
        $parameter['adspaceId']     = $adspaceId;
        $parameter['incentiveType'] = $incentiveType;
        $parameter['region']        = $region;
        $parameter['page']          = $page;
        $parameter['items']         = $items;

        $this->setSecureApiCall(false);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Search for exclusive incentives.
     *
     * @param       int         $programId      limit search to program list of
     *                                          programs (optional)
     * @param       int         $adspaceId      adspace id (optional)
     * @param       string      $incentiveType  type of incentive (optional)
     *                                          (coupons, samples, bargains,
     *                                          freeProducts, noShippingCosts,
     *                                          lotteries)
     * @param       string      $region         program region (optional)
     * @param       int         $page           page of result set (optional)
     * @param       int         $items          items per page (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string            list of incentives or false
     */
    public function searchExclusiveIncentives ( $programId = NULL, $adspaceId = NULL,
        $incentiveType = NULL, $region = NULL, $page = 0, $items = 10  )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['programId']     = $programId;
        $parameter['adspaceId']     = $adspaceId;
        $parameter['incentiveType'] = $incentiveType;
        $parameter['region']        = $region;
        $parameter['page']          = $page;
        $parameter['items']         = $items;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * done
     *
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
    public function getAdmedium ( $admediumId, $adspaceId = NULL )
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
     * done
     *
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
    public function getAdmedia ( $programId = NULL, $region = NULL,
        $format = NULL, $partnerShip = NULL, $purpose = NULL,
        $admediumType = NULL, $categoryId = NULL, $adspaceId = NULL, $page = 0,
        $items = 10 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['programId']    = $programId;
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
     * done
     *
     * Get admedium categories.
     *
     * @param      int         $programId      program admedium categories
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            list of admedium categories
     */
    public function getAdmediumCategories ( $programId )
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
     * Get single application.
     *
     * @param       int         $applicationId      application id (mandatory)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                application item or false
     */
    public function getApplication ( $applicationId )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['applicationId']   = $applicationId;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get applications.
     *
     * @param       string      $name               name of the application (optional)
     * @param       int         $width              width of application (optional)
     * @param       int         $height             height of application (optional)
     * @param       string      $format             format of application (optional)
     * @param       string      $role               role of the application (optional)
     *                                              (developer, customer, tester)
     * @param       string      $applicationType    type of application (optional)
     *                                              (widget, saas, software)
     * @param       int         $page               page of result set (optional)
     * @param       int         $items              items per page (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string            application item or false
     */
    public function getApplications ( $name = NULL, $width = NULL,
        $height = NULL, $format = NULL, $role = NULL, $applicationType = NULL,
        $page = 0, $items = 0 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['name']              = $name;
        $parameter['roletype']          = $role;
        $parameter['applicationType']   = $applicationType;
        $parameter['width']             = $width;
        $parameter['height']            = $height;
        $parameter['format']            = $format;
        $parameter['page']              = $page;
        $parameter['items']             = $items;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Create an application
     *
     * @param       string      $name                   name (optional)
     * @param       string      $version                version (optional)
     * @param       int         $adrank                 adrank (optional)
     * @param       string      $tags                   tags (optional)
     * @param       int         $status                 status (optional)
     * @param       boolean     $mediaSlotCompatible    compatible to media slot (optional)
     * @param       boolean     $inline
     * @param       string      $integrationCode        integration code (optional)
     * @param       string      $integrationNotes       integration notes (optional)
     * @param       string      $description            description (optional)
     * @param       string      $terms                  terms of service (optional)
     * @param       string      $connectRole            role of the application (optional)
     *                                                  (developer, customer, tester)
     * @param       string      $connectId              connect id (optional)
     * @param       string      $connectStatus          connect status (optional)
     *                                                  (active, inactive)
     * @param       string      $connectUrl             connect url (optional)
     * @param       string      $cancelUrl              cancel url (optional)
     * @param       string      $documentationUrl       documentation url (optional)
     * @param       string      $companyUrl             company url (optional)
     * @param       string      $developer              developer (optional)
     * @param       float       $pricingShare           price for share model (optional)
     * @param       float       $pricingSetup           price for setup (optional)
     * @param       float       $pricingMonthly         price for monthly usage (optional)
     * @param       string      $pricingCurrency        pricing currency (optional)
     * @param       string      $pricingDescription     pricing description (optional)
     * @param       string      $startDate              start date (optional)
     * @param       string      $modifiedDate           modification date (optional)
     * @param       string      $installableTo          who can install the (optional)
     *                                                  application
     *                                                  (advertiser, publisher)
     * @param       string      $applicationType        type of application (optional)
     *                                                  (widget, saas, software)
     * @param       int         $width                  width of application (optional)
     * @param       int         $height                 height of application (optional)
     * @param       string      $format                 format of application (optional)
     * @param       string      $technique              technique (optional)
     * @param       string      $logoUrl                logo url (optional)
     * @param       string      $previewUrl             preview url (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string            application item or false
     */
    public function createApplication ( $name = NULL, $version = NULL, $adrank = 0,
        $tags = NULL, $status = 0, $mediaSlotCompatible = false, $inline = false,
        $integrationCode = NULL, $integrationNotes = NULL, $description = NULL,
        $terms = NULL, $connectRole = NULL, $connectId = NULL,
        $connectStatus = NULL, $connectUrl = NULL, $cancelUrl = NULL,
        $documentationUrl = NULL, $companyUrl = NULL, $developer = NULL,
        $pricingShare = 0, $pricingSetup = 0, $pricingMonthly = 0,
        $pricingCurrency = NULL, $pricingDescription = NULL, $startDate = NULL,
        $modifiedDate = NULL, $installableTo = NULL, $applicationType = NULL,
        $width = NULL, $height = NULL, $format = NULL, $technique = NULL,
        $logoUrl = NULL, $previewUrl = NULL )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['applicationItem']['name']                   = $name;
        $parameter['applicationItem']['version']                = $version;
        $parameter['applicationItem']['adrank']                 = $adrank;
        $parameter['applicationItem']['tags']                   = $tags;
        $parameter['applicationItem']['status']                 = $status;
        $parameter['applicationItem']['mediaSlotCompatible']    = $mediaSlotCompatible;
        $parameter['applicationItem']['inline']                 = $inline;
        $parameter['applicationItem']['integrationCode']        = $integrationCode;
        $parameter['applicationItem']['integrationNotes']       = $integrationNotes;
        $parameter['applicationItem']['description']            = $description;
        $parameter['applicationItem']['terms']                  = $terms;
        $parameter['applicationItem']['connect']['role']        = $connectRole;
        $parameter['applicationItem']['connect']['connectId']   = $connectId;
        $parameter['applicationItem']['connect']['status']      = $connectStatus;
        $parameter['applicationItem']['cancelUrl']              = $cancelUrl;
        $parameter['applicationItem']['documentationUrl']       = $documentationUrl;
        $parameter['applicationItem']['companyUrl']             = $companyUrl;
        $parameter['applicationItem']['developer']              = $developer;
        $parameter['applicationItem']['pricing']['share']       = $pricingShare;
        $parameter['applicationItem']['pricing']['setup']       = $pricingSetup;
        $parameter['applicationItem']['pricing']['monthly']     = $pricingMonthly;
        $parameter['applicationItem']['pricing']['currency']    = $pricingCurrency;
        $parameter['applicationItem']['pricing']['description'] = $pricingDescription;
        $parameter['applicationItem']['startDate']              = $startDate;
        $parameter['applicationItem']['modifiedDate']           = $modifiedDate;
        $parameter['applicationItem']['installableTo']          = $installableTo;
        $parameter['applicationItem']['applicationType']        = $applicationType;
        $parameter['applicationItem']['size']['width']          = $width;
        $parameter['applicationItem']['size']['height']         = $height;
        $parameter['applicationItem']['size']['format']         = $format;
        $parameter['applicationItem']['technique']              = $technique;
        $parameter['applicationItem']['logoUrl']                = $logoUrl;
        $parameter['applicationItem']['previewUrl']             = $previewUrl;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }
    }



    /**
     * Update an application
     *
     * @param       int         $applicationId          application id (mandatory)
     * @param       string      $name                   name (optional)
     * @param       string      $version                version (optional)
     * @param       int         $adrank                 adrank (optional)
     * @param       string      $tags                   tags (optional)
     * @param       int         $status                 status (optional)
     * @param       boolean     $mediaSlotCompatible    compatible to media slot (optional)
     * @param       boolean     $inline
     * @param       string      $integrationCode        integration code (optional)
     * @param       string      $integrationNotes       integration notes (optional)
     * @param       string      $description            description (optional)
     * @param       string      $terms                  terms of service (optional)
     * @param       string      $connectRole            role of the application (optional)
     *                                                  (developer, customer, tester)
     * @param       string      $connectId              connect id (optional)
     * @param       string      $connectStatus          connect status (optional)
     *                                                  (active, inactive)
     * @param       string      $connectUrl             connect url (optional)
     * @param       string      $cancelUrl              cancel url (optional)
     * @param       string      $documentationUrl       documentation url (optional)
     * @param       string      $companyUrl             company url (optional)
     * @param       string      $developer              developer (optional)
     * @param       float       $pricingShare           price for share model (optional)
     * @param       float       $pricingSetup           price for setup (optional)
     * @param       float       $pricingMonthly         price for monthly usage (optional)
     * @param       string      $pricingCurrency        pricing currency (optional)
     * @param       string      $pricingDescription     pricing description (optional)
     * @param       string      $startDate              start date (optional)
     * @param       string      $modifiedDate           modification date (optional)
     * @param       string      $installableTo          who can install the (optional)
     *                                                  application
     *                                                  (advertiser, publisher)
     * @param       string      $applicationType        type of application (optional)
     *                                                  (widget, saas, software)
     * @param       int         $width                  width of application (optional)
     * @param       int         $height                 height of application (optional)
     * @param       string      $format                 format of application (optional)
     * @param       string      $technique              technique (optional)
     * @param       string      $logoUrl                logo url (optional)
     * @param       string      $previewUrl             preview url (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string            application item or false
     */
    public function updateApplication ( $applicationId, $name = NULL,
        $version = NULL, $adrank = 0, $tags = NULL, $status = 0,
        $mediaSlotCompatible = false, $inline = false, $integrationCode = NULL,
        $integrationNotes = NULL, $description = NULL, $terms = NULL,
        $connectRole = NULL, $connectId = NULL, $connectStatus = NULL,
        $connectUrl = NULL, $cancelUrl = NULL, $documentationUrl = NULL,
        $companyUrl = NULL, $developer = NULL, $pricingShare = 0,
        $pricingSetup = 0, $pricingMonthly = 0, $pricingCurrency = NULL,
        $pricingDescription = NULL, $startDate = NULL, $modifiedDate = NULL,
        $installableTo = NULL, $applicationType = NULL, $width = NULL,
        $height = NULL, $format = NULL, $technique = NULL, $logoUrl = NULL,
        $previewUrl = NULL )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['applicationItem']['id']                     = $applicationId;
        $parameter['applicationItem']['name']                   = $name;
        $parameter['applicationItem']['version']                = $version;
        $parameter['applicationItem']['adrank']                 = $adrank;
        $parameter['applicationItem']['tags']                   = $tags;
        $parameter['applicationItem']['status']                 = $status;
        $parameter['applicationItem']['mediaSlotCompatible']    = $mediaSlotCompatible;
        $parameter['applicationItem']['inline']                 = $inline;
        $parameter['applicationItem']['integrationCode']        = $integrationCode;
        $parameter['applicationItem']['integrationNotes']       = $integrationNotes;
        $parameter['applicationItem']['description']            = $description;
        $parameter['applicationItem']['terms']                  = $terms;
        $parameter['applicationItem']['connect']['role']        = $connectRole;
        $parameter['applicationItem']['connect']['connectId']   = $connectId;
        $parameter['applicationItem']['connect']['status']      = $connectStatus;
        $parameter['applicationItem']['cancelUrl']              = $cancelUrl;
        $parameter['applicationItem']['documentationUrl']       = $documentationUrl;
        $parameter['applicationItem']['companyUrl']             = $companyUrl;
        $parameter['applicationItem']['developer']              = $developer;
        $parameter['applicationItem']['pricing']['share']       = $pricingShare;
        $parameter['applicationItem']['pricing']['setup']       = $pricingSetup;
        $parameter['applicationItem']['pricing']['monthly']     = $pricingMonthly;
        $parameter['applicationItem']['pricing']['currency']    = $pricingCurrency;
        $parameter['applicationItem']['pricing']['description'] = $pricingDescription;
        $parameter['applicationItem']['startDate']              = $startDate;
        $parameter['applicationItem']['modifiedDate']           = $modifiedDate;
        $parameter['applicationItem']['installableTo']          = $installableTo;
        $parameter['applicationItem']['applicationType']        = $applicationType;
        $parameter['applicationItem']['size']['width']          = $width;
        $parameter['applicationItem']['size']['height']         = $height;
        $parameter['applicationItem']['size']['format']         = $format;
        $parameter['applicationItem']['technique']              = $technique;
        $parameter['applicationItem']['logoUrl']                = $logoUrl;
        $parameter['applicationItem']['previewUrl']             = $previewUrl;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }
    }



    /**
     * Delete application.
     *
     * @param       int         $applicationId      application id (mandatory)
     *
     * @access      public
     * @category    signature
     *
     * @return      boolean                true on success
     */
    public function deleteApplication ( $applicationId )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['applicationId']  = $applicationId;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get single setting.
     *
     * @param       int         $applicationId  application id (mandatory)
     * @param       int         $mediaslotId    media slot id (optional)
     * @param       key         $key            application specific key
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string            application item or false
     */
    public function getSetting ( $applicationId, $mediaslotId = NULL, $key )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['applicationId']     = $applicationId;
        $parameter['mediaslotId']       = $mediaslotId;
        $parameter['key']               = $key;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get settings.
     *
     * @param       int         $applicationId      application id (mandatory)
     * @param       int         $mediaslotId        media slot id (optional)
     * @param       int         $page               page of result set (optional)
     * @param       int         $items              items per page (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                list of settings or false
     */
    public function getSettings ( $applicationId, $mediaslotId = NULL,
        $page = 0, $items = 0 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['applicationId']     = $applicationId;
        $parameter['mediaslotId']       = $mediaslotId;
        $parameter['page']              = $page;
        $parameter['items']             = $items;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Create setting
     *
     * @param       int         $applicationId      application id (mandatory)
     * @param       int         $mediaslotId        media slot id (optional)
     * @param       string      $key                settings key (mandatory)
     * @param       string      $value              settings value (optional)
     * @param       string      $customValue        settings custom value (optional)
     * @param       string      $type               settings type (optional)
     *                                              (boolean, color, number,
     *                                               string, date)
     * @param       string      $name               settings name (optional)
     * @param       string      $description        settings description (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                setting
     */
    public function createSetting ( $applicationId, $mediaslotId = NULL, $key,
        $value, $customValue, $type = NULL, $name = NULL, $description = NULL )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['settingItem']['application']['id']      = $applicationId;
        $parameter['settingItem']['application']['_']       = "";
        $parameter['settingItem']['mediaslot']['id']        = $mediaslotId;
        $parameter['settingItem']['mediaslot']['_']         = "";
        $parameter['settingItem']['key']                    = $key;
        $parameter['settingItem']['value']                  = $value;
        $parameter['settingItem']['customValue']            = $customValue;
        $parameter['settingItem']['type']                   = $type;
        $parameter['settingItem']['name']                   = $name;
        $parameter['settingItem']['description']            = $description;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Update setting
     *
     * @param       int         $applicationId      application id (mandatory)
     * @param       int         $mediaslotId        media slot id (optional)
     * @param       string      $key                settings key (mandatory)
     * @param       string      $value              settings value (optional)
     * @param       string      $customValue        settings custom value (optional)
     * @param       string      $type               settings type (optional)
     *                                              (boolean, color, number,
     *                                               string, date)
     * @param       string      $name               settings name (optional)
     * @param       string      $description        settings description (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                setting
     */
    public function updateSetting ( $applicationId, $mediaslotId = NULL, $key,
        $value, $customValue, $type = NULL, $name = NULL, $description = NULL )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['settingItem']['application']['id']      = $applicationId;
        $parameter['settingItem']['application']['_']       = "";
        $parameter['settingItem']['mediaslot']['id']        = $mediaslotId;
        $parameter['settingItem']['mediaslot']['_']         = "";
        $parameter['settingItem']['key']                    = $key;
        $parameter['settingItem']['value']                  = $value;
        $parameter['settingItem']['customValue']            = $customValue;
        $parameter['settingItem']['type']                   = $type;
        $parameter['settingItem']['name']                   = $name;
        $parameter['settingItem']['description']            = $description;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Delete setting
     *
     * @param       int         $applicationId      application id (mandatory)
     * @param       string      $mediaslotId        mediaslot id (optional)
     * @param       string      $key                settings key (mandatory)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                setting
     */
    public function deleteSetting ( $applicationId, $mediaslotId, $key )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['applicationId']     = $applicationId;
        $parameter['key']               = $key;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get media slots.
     *
     * @param       int         $mediaslotId            media slot id (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                    media slot object or false
     */
    public function getMediaSlot ( $mediaSlotId )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['mediaslotId']       = $mediaslotId;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Create media slots.
     *
     * @param       int         $adspaceId              advertising space id (optional)
     * @param       int         $width                  width of application (optional)
     * @param       int         $height                 height of application (optional)
     * @param       string      $format                 format of application (optional)
     * @param       int         $page                   page of result set (optional)
     * @param       int         $items                  items per page (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                    list of media slot objects or false
     */
    public function getMediaSlots ( $adspaceId, $width = 0, $height = 0,
        $format = NULL, $page = 0, $items = 0 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['adspaceId']         = $adspaceId;
        $parameter['width']             = $width;
        $parameter['height']            = $height;
        $parameter['format']            = $format;
        $parameter['page']              = $page;
        $parameter['items']             = $items;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Get media slot.
     *
     * @param       string      $name                   media slot name (mandatory)
     * @param       int         $adspaceId              adspace id (mandatory)
     * @param       string      $applicationId          application id (mandatory)
     * @param       string      $status                 media slot status (mandatory)
     *                                                  (active, deleted)
     * @param       int         $width                  width of application (optional)
     * @param       int         $height                 height of application (optional)
     * @param       string      $format                 format of application (optional)
     * @param       string      $createDate             create date (optional)
     * @param       string      $modifiedDate           modified date (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                    list of media slot objects or false
     */
    public function createMediaSlot ( $name, $adspaceId, $applicationId, $status,
        $width = 0, $height = 0, $format = NULL, $createDate = NULL,
        $modifiedDate = NULL )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['mediaSlotItem']['name']                 = $name;
        $parameter['mediaSlotItem']['adspace']['id']        = $adspaceId;
        $parameter['mediaSlotItem']['adspace']['_']         = "";
        $parameter['mediaSlotItem']['applications']['id']   = $applicationId;
        $parameter['mediaSlotItem']['applications']['_']    = "";
        $parameter['mediaSlotItem']['status']               = $status;
        $parameter['mediaSlotItem']['size']['width']        = $width;
        $parameter['mediaSlotItem']['size']['height']       = $height;
        $parameter['mediaSlotItem']['size']['format']       = $format;
        $parameter['mediaSlotItem']['createDate']           = $createDate;
        $parameter['mediaSlotItem']['modifiedDate']         = $modifiedDate;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Update media slot.
     *
     * @param       int         $mediaslotId            media slot id (mandatory)
     * @param       string      $name                   media slot name (mandatory)
     * @param       int         $adspaceId              adspace id (mandatory)
     * @param       string      $adspaceName            name of the adspace (optional)
     * @param       string      $applicationId          application id (mandatory)
     * @param       string      $applicationName        name of the application (optional)
     * @param       string      $status                 media slot status (mandatory)
     *                                                  (active, deleted)
     * @param       int         $width                  width of application (optional)
     * @param       int         $height                 height of application (optional)
     * @param       string      $format                 format of application (optional)
     * @param       string      $createDate             create date (optional)
     * @param       string      $modifiedDate           modified date (optional)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                    list of media slot objects or false
     */
    public function updateMediaSlot ( $mediaslotId, $name, $adspaceId,
        $adspaceName = NULL, $applicationId, $applicationName = NULL,
        $status = NULL, $width = 0, $height = 0, $format = NULL,
        $createDate = NULL, $modifiedDate = NULL )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['mediaSlotItem']['mediaslotId']          = $mediaslotId;
        $parameter['mediaSlotItem']['name']                 = $name;
        $parameter['mediaSlotItem']['adspace']['id']        = $adspaceId;
        $parameter['mediaSlotItem']['adspace']['_']         = "";
        $parameter['mediaSlotItem']['application']['id']    = $applicationId;
        $parameter['mediaSlotItem']['application']['_']     = "";
        $parameter['mediaSlotItem']['status']               = $status;
        $parameter['mediaSlotItem']['size']['width']        = $width;
        $parameter['mediaSlotItem']['size']['height']       = $height;
        $parameter['mediaSlotItem']['size']['format']       = $format;
        $parameter['mediaSlotItem']['createDate']           = $createDate;
        $parameter['mediaSlotItem']['modifiedDate']         = $modifiedDate;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * Delete media slot.
     *
     * @param       int         $mediaslotId            media slot id (mandatory)
     *
     * @access      public
     * @category    signature
     *
     * @return      object or string                    true if success
     */
    public function deleteMediaSlot ( $mediaslotId )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['mediaslotid']  = $mediaslotId;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_PUBLISHER, $method, $parameter);

        if ( $result )
        {
            return $result;
        }

        return false;
    }



    /**
     * done
     *
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
     * done
     *
     * Get advertiser program categories.
     *
     * @access     public
     * @category   nosignature
     *
     * @return     object or string            category result set or false
     */
    public function getProgramCategories ()
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
     * TODO: probably needs to be removed
     *
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
    public function getProgramsByAdspace ( $adspaceId, $page = 0, $items = 10 )
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
     * done
     *
     * Search zanox advertiser programs.
     *
     * @param      string      $query          search string
     * @param      string      $startDate      program start date (optional)
     * @param      string      $partnerShip    partnership status (optional)
     *                                         (direct or indirect)
     * @param      boolean     $hasProducts    program has product data
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
    public function searchPrograms ( $query = NULL, $startDate = NULL,
        $partnerShip = NULL, $hasProducts = false, $region = NULL,
        $categoryId = NULL, $page = 0, $items = 10 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['q']           = $query;
        $parameter['startDate']   = $startDate;
        $parameter['partnerShip'] = $partnerShip;
        $parameter['hasProducts'] = $hasProducts;
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
     * done
     *
     * Get advertiser program applications for a user.
     *
     * @param      int         $programId       restrict results to applications (optional)
     *                                          to the id of this program (optional)
     * @param      int         $adspaceId       advertising space id (optional)
     * @param      string      $status          restrict results to program applications
     *                                          with this status:
     *                                          "open", "confirmed", "rejected",
     *                                          "deferred", "waiting", "blocked",
     *                                          "terminated", "canceled", "called",
     *                                          "declined", "deleted"
     * @param      int         $page            page of result set (optional)
     * @param      int         $items           items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            program result set or false
     */
    public function getProgramApplications ( $adspaceId = NULL,
        $programId = NULL, $status = NULL, $page = 0, $items = 10 )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['adspaceId']    = $adspaceId;
        $parameter['programId']    = $programId;
        $parameter['status']       = $status;
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
    public function createProgramApplication ( $programId, $adspaceId )
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
     * Returns a single advertising spaces.
     *
     * @param      int         $adspaceId      advertising space id
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            adspace item or false
     */
    public function getAdspace ( $adspaceId )
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
     * @param      string      $language       language of adspace (e.g. en)
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
     * @param      int         $checkNumber
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            adspace item or false
     */
    public function createAdspace ( $name, $language, $url, $contact, $description,
        $adspaceType, $scope, $visitors, $impressions, $keywords = NULL,
        $regions = array(), $categories = array(), $checkNumber )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['adspaceItem']['name']        = $name;
        $parameter['adspaceItem']['language']    = $language;
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
        $parameter['adspaceItem']['checkNumber'] = $checkNumber;

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
     * @param      string      $language       language of adspace (e.g. en)
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
     * @param      int         $checkNumber
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            adspace item or false
     */
    public function updateAdspace ( $adspaceId, $name, $language, $url, $contact,
        $description, $adspaceType, $scope, $visitors, $impressions,
        $keywords = NULL, $regions = array(), $categories = array(), $checkNumber )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['adspaceItem']['id']          = $adspaceId;
        $parameter['adspaceItem']['name']        = $name;
        $parameter['adspaceItem']['language']    = $language;
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
        $parameter['adspaceItem']['checkNumber'] = $checkNumber;

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
    public function deleteAdspace ( $adspaceId )
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
    public function getProfile ()
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
     * @param      array        $profileId      user profile id
     * @param      string       $loginName      login name
     * @param      string       $userName       user name
     * @param      string       $firstName      first name
     * @param      string       $lastName       last name
     * @param      string       $email          email address
     * @param      string       $country        country or residence
     * @param      string       $street1        street 1
     * @param      string       $street2        street 2 (optional)
     * @param      string       $city           city
     * @param      string       $company        name of company (optional)
     * @param      string       $phone          phone number (optional)
     * @param      string       $mobile         mobile number (optional)
     * @param      string       $fax            fax number (optional)
     * @param      boolean      $isAdvertiser   is Advertiser account
     * @param      boolean      $isSublogin     is Sublogin account
     *
     * @access     public
     * @category   signature
     *
     * @return     boolean                     true on success
     */
    public function updateProfile ( $profileId, $loginName, $userName,
        $firstName = NULL, $lastName = NULL, $email = NULL, $country = NULL,
        $street1 = NULL, $street2 = NULL, $city = NULL, $zipcode = NULL,
        $company = NULL, $phone = NULL, $mobile = NULL, $fax = NULL,
        $isAdvertiser, $isSublogin )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['profileItem']['id']             = $profileId;
        $parameter['profileItem']['loginName']      = $loginName;
        $parameter['profileItem']['userName']       = $userName;
        $parameter['profileItem']['firstName']      = $firstName;
        $parameter['profileItem']['lastName']       = $lastName;
        $parameter['profileItem']['email']          = $email;
        $parameter['profileItem']['country']        = $country;
        $parameter['profileItem']['street1']        = $street1;
        $parameter['profileItem']['street2']        = $street2;
        $parameter['profileItem']['city']           = $city;
        $parameter['profileItem']['zipcode']        = $zipcode;
        $parameter['profileItem']['company']        = $company;
        $parameter['profileItem']['phone']          = $phone;
        $parameter['profileItem']['mobile']         = $mobile;
        $parameter['profileItem']['fax']            = $fax;
        $parameter['profileItem']['isAdvertiser']   = $isAdvertiser;
        $parameter['profileItem']['isSublogin']     = $isSublogin;

        // stupid bug fix ;-) its not really getting updated to 10 ;-)
        $parameter['profileItem']['adrank']         = 10;

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
    public function getBankAccounts ( $page = 0, $items = 10 )
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
    public function getBankAccount ( $bankAccountId )
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
    public function getBalance ( $currency )
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
    public function getBalances ( $page = 0, $items = 10 )
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
    public function getPayments ( $page = 0, $items = 10 )
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
    public function getPayment ( $paymentId )
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
    public function getReportBasic ( $fromDate, $toDate, $dateType = NULL,
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

        $parameter['programId']    = $programId;
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

        $parameter['programId']    = $programId;
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
     * Returns new offline user session
     *
     * @param      string      $authToken      authentication token
     *
     * @access     public
     *
     * @return     object                      user session
     */
    public function getOfflineSession ( $offlineToken )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['offlineToken'] = $offlineToken;

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
     * @return     string                        URL to the UI
     */
    public function getUiUrl ( $connectId, $sessionKey )
    {
        $method = ucfirst(__FUNCTION__);

        $parameter['connectId']  = $connectId;
        $parameter['sessionKey'] = $sessionKey;

        $this->setSecureApiCall(true);

        $result = $this->doSoapRequest(SERVICE_CONNECT, $method, $parameter);

        if ( $result )
        {
            return $result->url;
        }

        return false;
    }

    /**
     * Get tracking categories for ad space; if not program member, returns program's default categories
     *
     * @param      int         $adspaceId      adspace id (mandatory)
     * @param      int         $programId      advertiser program id (mandatory)     
     * @param      int         $page           result set page (optional)
     * @param      int         $items          items per page (optional)
     *
     * @access     public
     * @category   signature
     *
     * @return     object or string            program result set of trackingCategoryItems
     */
    public function getTrackingCategories ( $adspaceId, $programId, $page = 0, $items = 50 )
    {
          $method = ucfirst(__FUNCTION__);

				$parameter['adspaceId'] = $adspaceId;
				$parameter['programId'] = $programId;
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



}

?>
