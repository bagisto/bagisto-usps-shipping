<?php

namespace Webkul\UspsShipping\Helpers;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Webkul\Checkout\Facades\Cart;
use Webkul\Core\Repositories\CountryRepository as Country;
use Webkul\Checkout\Repositories\CartAddressRepository as CartAddress;
use Webkul\UspsShipping\Repositories\UspsRepository as UspsRepository;
use Illuminate\Support\Str;

class ShippingMethodHelper
{
    /**
     * Contains route related configuration
     *
     * @var array
     */
    protected $_config;

    /**
     * Cart Address Object
     *
     * @var object
     */
    protected $cartAddress;

    /**
     * Usps Repository Object
     *
     * @var object
     */
    protected $uspsRepository;

    /**
     * RateServiceWsdl
     *
     * @var string
     */
    protected $rateServiceWsdl;

    /**
     * ShipServiceWsdl
     *
     * @var string
     */
    protected $shipServiceWsdl;

    /**
     * country object
     *
     * @var string
     */
    protected $country;


    /**
     * Create a new controller instance.
     *
     * @param
     * @return void
     */
    public function __construct(
        CartAddress $cartAddress,
        UspsRepository $uspsRepository,
        Country $country
    )
    {
        $this->_config = request('_config');

        $this->cartAddress = $cartAddress;

        $this->uspsRepository = $uspsRepository;

        $this->country = $country;
    }

    /**
     * display methods
     *
     * @return array
    */
    public function getAllCartProducts()
    {
        $data = $this->_createSoapClient();

        return $data;
    }

    /**
     * Soap client for wsdl
     *
     * @param string $wsdl
     * @param bool|int $trace
     * @return \SoapClient
     */
    protected function _createSoapClient()
    {
        $cart                   = Cart::getCart();
        $address                = $cart->shipping_address;
        $errorResponse          = [];
        $sellerAdminServices    = []; 
        $allServices            = [];
        
        if ('DEVELOPMENT' ==  core()->getConfigData('sales.carriers.usps.mode')) {
            $gatewayUrl = 'http://production.shippingapis.com/ShippingAPI.dll';
        } else {
            $gatewayUrl = 'https://secure.shippingapis.com/ShippingAPI.dll';
        }
        
        $sellerAdminServices[0] = explode(",", core()->getConfigData('sales.carriers.usps.services'));

        foreach ($cart->items as $cartProduct)
        {
            if ($cartProduct->product->getTypeInstance()->isStockable()) {
                $weightInPound  = $this->getPoundWeight($cartProduct->weight);
                $weightInOunce  = $this->getOunceWeight($cartProduct->weight);
                $userId         = core()->getConfigData('sales.carriers.usps.user_id');
                $sellerId       = 0;

                if ( $this->_isUSCountry($address['country']) ) {
                    $xmlNode = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><RateV4Request/>');
                    $xmlNode->addAttribute('USERID', $userId);
                    $xmlNode->addChild('Revision', '2');
                    $childNode = $xmlNode->addChild('Package');
                    $childNode->addAttribute('ID', 0);

                    $service    = 'All';
                    if (! $service ) {
                        $service = core()->getConfigData('sales.carriers.usps.services');
                    }

                    if (
                        core()->getConfigData('sales.carriers.usps.services') == 'FLAT RATE BOX' ||
                        core()->getConfigData('sales.carriers.usps.services') == 'FLAT RATE ENVELOPE'
                    ) {
                        $service = 'Priority';
                    }
                    
                    $childNode->addChild('Service', $service);

                    if (
                        core()->getConfigData('sales.carriers.usps.services') == 'FIRST CLASS' ||
                        core()->getConfigData('sales.carriers.usps.services') == 'FIRST CLASS COMMERCIAL' ||
                        core()->getConfigData('sales.carriers.usps.services') == 'FIRST CLASS HFP COMMERCIAL'
                    ) {
                        $childNode->addChild('FirstClassMailType', 'PARCEL');
                    }

                    $childNode->addChild('ZipOrigination', core()->getConfigData('sales.shipping.origin.zipcode'));

                    $childNode->addChild('ZipDestination', $address['postcode']);
                    $childNode->addChild('Pounds', $weightInPound);
                    $childNode->addChild('Ounces', $weightInOunce);
                    $childNode->addChild('Container', core()->getConfigData('sales.carriers.usps.container'));
                    $childNode->addChild('Size', core()->getConfigData('sales.carriers.usps.size'));

                    if (core()->getConfigData('sales.carriers.usps.size') == 'LARGE') {
                        $childNode->addChild('Width', core()->getConfigData('sales.carriers.usps.width'));
                        $childNode->addChild('Length', core()->getConfigData('sales.carriers.usps.length'));
                        $childNode->addChild('Height', core()->getConfigData('sales.carriers.usps.height'));
                    }

                    $childNode->addChild('Machinable', core()->getConfigData('sales.carriers.usps.machinable'));

                    $api = 'RateV4';
                } else {
                    $xmlNode = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><IntlRateV2Request/>');

                    $xmlNode->addAttribute('USERID', $userId);
                    $xmlNode->addChild('Revision', '2');

                    $childNode = $xmlNode->addChild('Package');
                    $childNode->addAttribute('ID', '1ST');
                    $childNode->addChild('Pounds', $weightInPound);
                    $childNode->addChild('Ounces', $weightInOunce);
                    $childNode->addChild('MailType', 'All');
                    $childNode->addChild('ValueOfContents', $cartProduct->price);

                    //destination country incase of intl rate request.
                    $country = $this->country->findOneWhere(['code' => $address['country']]);
                    $childNode->addChild('Country', $country['name']);

                    $childNode->addChild('Container', core()->getConfigData('sales.carriers.usps.container'));
                    $childNode->addChild('Size', core()->getConfigData('sales.carriers.usps.size'));
                    
                    if ( core()->getConfigData('sales.carriers.usps.size') == 'LARGE' ) {
                        $childNode->addChild('Width', core()->getConfigData('sales.carriers.usps.width'));
                        $childNode->addChild('Length', core()->getConfigData('sales.carriers.usps.length'));
                        $childNode->addChild('Height', core()->getConfigData('sales.carriers.usps.height'));
                        $childNode->addChild('Girth', '');
                    }

                    date_default_timezone_set('America/Los_Angeles');
                    $childNode->addChild('OriginZip', core()->getConfigData('sales.shipping.origin.zipcode'));
                    $childNode->addChild('AcceptanceDateTime', date('c'));
                    $childNode->addChild('DestinationPostalCode', $address['postcode']);

                    $api = 'IntlRateV2';
                }

                $xml        = $xmlNode->saveXML();
                $request    = "API=" . $api . "&XML=" . $xml;
                
                try {
                    $url    = $gatewayUrl;
                    $ch     = curl_init();
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        "Content-type: text/xml",
                        "Accept: text/xml",
                        "Cache-Control: no-cache",
                        "Pragma: no-cache",
                    ));
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    
                    $uspsServiceArray   = simplexml_load_string($response);
                    
                    $uspsServices       = json_decode(json_encode($uspsServiceArray));
                    
                    if ( isset($cartProduct->marketplace_seller_id) ) {
                        $sellerId = $cartProduct->marketplace_seller_id;
                    }
                    
                    if ( isset( $uspsServices->Package->Error ) && $uspsServices->Package->Error ) {
                        $this->getErrorLog($uspsServices->Package->Error, $sellerId);
                        $errorResponse[] = $uspsServices->Package->Error;
                    } else {
                        foreach ($uspsServices as $services) {
                            if ( isset($services->Prohibitions) && isset($services->Service) ) {
                                //INTL Rate Request
                                $classId = 0;
                                foreach ($services->Service as $key => $id) {
                                    if ($key == '@attributes')
                                        $classId = 'INT_' . $id->ID;
                                }
                                
                                $manipulateafter    = Str::before($services->Service->SvcDescription , '&lt');
                                $manipulatebefore   = Str::after($services->Service->SvcDescription , '/sup&gt;');
                                $serviceType        = $manipulateafter . $manipulatebefore;

                                if ( isset($sellerAdminServices[$sellerId]) && in_array($classId, $sellerAdminServices[$sellerId]) ) {
                                    $cartProductServices[$serviceType] = [
                                        'classId'       => $classId,
                                        'rate'          => $services->Service->Postage,
                                        'pounds'        => $services->Service->Pounds,
                                        'ounces'        => $services->Service->Ounces,
                                        'country'       => $services->Service->Country,
                                        'marketplace_seller_id' => $sellerId,
                                        'itemQuantity' => $cartProduct->quantity
                                    ];
                                }
                            } else {
                                foreach ($services->Postage as $reply) {
                                    $classId = 0;
                                    foreach ($reply as $key => $id) {
                                        if ($key == '@attributes')
                                            $classId = $id->CLASSID;
                                    }

                                    $manipulateafter    = Str::before($reply->MailService , '&lt');
                                    $manipulatebefore   = Str::after($reply->MailService , '/sup&gt;');
                                    $serviceType        = $manipulateafter . $manipulatebefore;

                                    if ( isset($sellerAdminServices[$sellerId]) && in_array($classId, $sellerAdminServices[$sellerId]) ) {
                                        $cartProductServices[$serviceType] = [
                                            'classId'           => $classId,
                                            'rate'              => $reply->Rate,
                                            'originationZip'    => $services->ZipOrigination,
                                            'destinationZip'    => $services->ZipDestination,
                                            'originZip'         => $services->ZipOrigination,
                                            'destinationZip'    => $services->ZipDestination,
                                            'pounds'            => $services->Pounds,
                                            'ounces'            => $services->Ounces,
                                            'machnable'         => $services->Machinable,
                                            'zone'              => $services->Zone,
                                            'marketplace_seller_id' => $sellerId,
                                            'itemQuantity'      => $cartProduct->quantity
                                        ];
                                    }
                                }
                            }
                        }

                        if ( !empty($cartProductServices)) {
                            $allServices[] = $cartProductServices;
                        }
                    }
                } catch (\Exception $e) {
                    $this->getErrorLog($e->getMessage(), $sellerId);
                    $errorResponse[] = $e->getMessage();
                }
            }
        }
        
        $responses = [
            'response'      => $allServices,
            'errorResponse' => $errorResponse
        ];

        return $responses;
    }

    /**
     * define the country code
     *
     * @param $countryId
     */
    protected function _isUSCountry($countryId)
    {
        switch ($countryId) {
            case 'AS': // Samoa American
            case 'GU': // Guam
            case 'MP': // Northern Mariana Islands
            case 'PW': // Palau
            case 'PR': // Puerto Rico
            case 'VI': // Virgin Islands US
            case 'US': // United States
            return true;
        }

        return false;
    }

    /**
     * convert currrent weight unit to
     *
     * @param string $weight
     **/
    public function getPoundWeight($weight)
    {
        $convertedWeight    = '';
        $coreWeightUnit     = strtoupper(core()->getConfigData('general.general.locale_options.weight_unit'));

        if ($coreWeightUnit == 'LBS') {
            $convertedWeight = $weight;
        } else {
            //kg to lb
            $convertedWeight = $weight/0.45359237;
        }

        return $convertedWeight;
    }

    /**
     * convert current weight unit to ounce
     *
     * @param string $weight
     **/
    public function getOunceWeight($weight)
    {
        $convertedWeight    = '';
        $coreWeightUnit     = strtoupper(core()->getConfigData('general.general.locale_options.weight_unit'));

        if ($coreWeightUnit == 'LBS') {
            //lb to ounce
            $convertedWeight = $weight * 16;
        } else {
            //kg to ounce
            $convertedWeight = $weight * 35.274;
        }

        return $convertedWeight;
    }

    /**
     * Get The Current Error
     *
     * @param string $error
     **/
    public function getErrorLog($errors ,$sellerId)
    {
        $status         = 'ERROR';
        $exception[]    = isset($errors->Description) ? $errors->Description : '';
        $log            = [
                            'status'        => $status,
                            'description'   => $exception,
                            'sellerId'      => $sellerId
                        ];
        
        $shippingLog    = new Logger('shipping');
        $shippingLog->pushHandler(new StreamHandler(storage_path('logs/usps.log')), Logger::INFO);
        $shippingLog->info('shipping', $log);

        return true;
    }
}