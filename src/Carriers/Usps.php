<?php

namespace Webkul\UspsShipping\Carriers;

use Webkul\Checkout\Models\CartShippingRate;
use Webkul\Shipping\Carriers\AbstractShipping;
use Webkul\UspsShipping\Helpers\ShippingMethodHelper;
use Webkul\UspsShipping\Repositories\UspsRepository;

class Usps extends AbstractShipping
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $code  = 'usps';

    /**
     * Payment method services
     *
     * @var string
     */
    protected $services  = [
        '0_FCLE' => 'First-Class Mail Large Envelope',
        '0_FCL'  => 'First-Class Mail Letter',
        '0_FCP'  => 'First-Class Package Service - Retail',
        '0_FCPC' => 'First-Class Mail Postcards',
        '1'      => 'Priority Mail',
        '2'      => 'Priority Mail Express Hold For Pickup',
        '3'      => 'Priority Mail Express 1-Day',
        '4'      => 'Retail Ground',
        '6'      => 'Media Mail',
        '7'      => 'Library Mail',
        '13'     => 'Priority Mail Express Flat Rate Envelope',
        '15'     => 'First-Class Mail Large Postcards',
        '16'     => 'Priority Mail Flat Rate Envelope',
        '17'     => 'Priority Mail Medium Flat Rate Box',
        '22'     => 'Priority Mail Large Flat Rate Box',
        '23'     => 'Priority Mail Express Sunday/Holiday Delivery',
        '25'     => 'Priority Mail Express Sunday/Holiday Delivery Flat Rate Envelope',
        '27'     => 'Priority Mail Express Flat Rate Envelope Hold For Pickup',
        '28'     => 'Priority Mail Small Flat Rate Box',
        '29'     => 'Priority Mail Padded Flat Rate Envelope',
        '30'     => 'Priority Mail Express Legal Flat Rate Envelope',
        '31'     => 'Priority Mail Express Legal Flat Rate Envelope Hold For Pickup',
        '32'     => 'Priority Mail Express Sunday/Holiday Delivery Legal Flat Rate Envelope',
        '33'     => 'Priority Mail Hold For Pickup',
        '34'     => 'Priority Mail Large Flat Rate Box Hold For Pickup',
        '35'     => 'Priority Mail Medium Flat Rate Box Hold For Pickup',
        '36'     => 'Priority Mail Small Flat Rate Box Hold For Pickup',
        '37'     => 'Priority Mail Flat Rate Envelope Hold For Pickup',
        '38'     => 'Priority Mail Gift Card Flat Rate Envelope',
        '39'     => 'Priority Mail Gift Card Flat Rate Envelope Hold For Pickup',
        '40'     => 'Priority Mail Window Flat Rate Envelope',
        '41'     => 'Priority Mail Window Flat Rate Envelope Hold For Pickup',
        '42'     => 'Priority Mail Small Flat Rate Envelope',
        '43'     => 'Priority Mail Small Flat Rate Envelope Hold For Pickup',
        '44'     => 'Priority Mail Legal Flat Rate Envelope',
        '45'     => 'Priority Mail Legal Flat Rate Envelope Hold For Pickup',
        '46'     => 'Priority Mail Padded Flat Rate Envelope Hold For Pickup',
        '47'     => 'Priority Mail Regional Rate Box A',
        '48'     => 'Priority Mail Regional Rate Box A Hold For Pickup',
        '49'     => 'Priority Mail Regional Rate Box B',
        '50'     => 'Priority Mail Regional Rate Box B Hold For Pickup',
        '53'     => 'First-Class Package Service Hold For Pickup',
        '57'     => 'Priority Mail Express Sunday/Holiday Delivery Flat Rate Boxes',
        '58'     => 'Priority Mail Regional Rate Box C',
        '59'     => 'Priority Mail Regional Rate Box C Hold For Pickup',
        '61'     => 'First-Class Package Service',
        '62'     => 'Priority Mail Express Padded Flat Rate Envelope',
        '63'     => 'Priority Mail Express Padded Flat Rate Envelope Hold For Pickup',
        '64'     => 'Priority Mail Express Sunday/Holiday Delivery Padded Flat Rate Envelope',
        'INT_1'  => 'Priority Mail Express International',
        'INT_2'  => 'Priority Mail International',
        'INT_4'  => 'Global Express Guaranteed (GXG)',
        'INT_5'  => 'Global Express Guaranteed Document',
        'INT_6'  => 'Global Express Guaranteed Non-Document Rectangular',
        'INT_7'  => 'Global Express Guaranteed Non-Document Non-Rectangular',
        'INT_8'  => 'Priority Mail International Flat Rate Envelope',
        'INT_9'  => 'Priority Mail International Medium Flat Rate Box',
        'INT_10' => 'Priority Mail Express International Flat Rate Envelope',
        'INT_11' => 'Priority Mail International Large Flat Rate Box',
        'INT_12' => 'USPS GXG Envelopes',
        'INT_13' => 'First-Class Mail International Letter',
        'INT_14' => 'First-Class Mail International Large Envelope',
        'INT_15' => 'First-Class Package International Service',
        'INT_16' => 'Priority Mail International Small Flat Rate Box',
        'INT_17' => 'Priority Mail Express International Legal Flat Rate Envelope',
        'INT_18' => 'Priority Mail International Gift Card Flat Rate Envelope',
        'INT_19' => 'Priority Mail International Window Flat Rate Envelope',
        'INT_20' => 'Priority Mail International Small Flat Rate Envelope',
        'INT_21' => 'First-Class Mail International Postcard',
        'INT_22' => 'Priority Mail International Legal Flat Rate Envelope',
        'INT_23' => 'Priority Mail International Padded Flat Rate Envelope',
        'INT_24' => 'Priority Mail International DVD Flat Rate priced box',
        'INT_25' => 'Priority Mail International Large Video Flat Rate priced box',
        'INT_27' => 'Priority Mail Express International Padded Flat Rate Envelope',
    ];

    /**
     * Returns rate for flatrate
     *
     * @return array
     */
    public function calculate()
    {
        if (! $this->isAvailable()) {
            return false;
        }

        $uspsMethod = '';

        $shippingMethods = $rates = [];

        $cartProducts = app(ShippingMethodHelper::class)->getAllCartProducts();

        $serviceData  = app(UspsRepository::class)->getCommonMethods($cartProducts['response']);

        if (! $cartProducts) {
            return false;
        }

        $marketplaceShipping = session()->get('marketplace_shipping_rates');

        if (! is_null($cartProducts['response']) 
            && $cartProducts['errorResponse'] == null
            && isset($serviceData)
        ) {
            foreach ($serviceData as $key => $uspsServices) {
                $rate = $totalShippingCost = 0;

                $uspsMethod = ucwords(strtolower(str_replace('_', ' ', $key)));

                $classId = '';

                foreach ($uspsServices as  $uspsRate) {
                    $classId = $uspsRate['classId'];

                    $rate += $uspsRate['rate'] * $uspsRate['itemQuantity'] ;

                    $sellerId = $uspsRate['marketplace_seller_id'];

                    $itemShippingCost =  $uspsRate['rate'] * $uspsRate['itemQuantity'];

                    $rates[$classId][$sellerId] = [
                        'amount'        => core()->convertPrice($itemShippingCost),
                        'base_amount'   => $itemShippingCost,
                    ];

                    if (isset($rates[$classId][$sellerId])) {
                        $rates[$classId][$sellerId] = [
                            'amount'        => core()->convertPrice($rates[$classId][$sellerId]['amount'] + $itemShippingCost),
                            'base_amount'   => $rates[$classId][$sellerId]['base_amount'] + $itemShippingCost,
                        ];
                    }

                    $totalShippingCost += $itemShippingCost;
                }

                $object = new CartShippingRate;
                
                $object->carrier = 'usps';
                $object->carrier_title = $this->getConfigData('title');
                $object->method = 'usps_'.''.$classId;
                $object->method_title = $this->getConfigData('title').' - '.$uspsMethod;
                $object->method_description = $this->getConfigData('description');
                $object->price = core()->convertPrice($totalShippingCost);
                $object->base_price = $totalShippingCost;

                if (! is_array($marketplaceShipping)) {
                    $marketplaceShippingRates['usps'] = $rates;
                    session()->put('marketplace_shipping_rates', $marketplaceShippingRates);
                } else {
                    session()->put('marketplace_shipping_rates.usps', $rates);
                }

                array_push($shippingMethods, $object);
            }

            return $shippingMethods;
        } 

        if ($cartProducts['response']) {
            foreach ($cartProducts['response'] as $keys => $services) {
                $totalShippingCost = $rate = 0;

                $classId = '';

                foreach ($services as  $uspsRate) {
                    $classId = $uspsRate['classId'];

                    $rate += $uspsRate['rate'] * $uspsRate['itemQuantity'] ;

                    $sellerId = $uspsRate['marketplace_seller_id'];

                    $itemShippingCost =  $uspsRate['rate'] * $uspsRate['itemQuantity'];

                    $rates[$classId][$sellerId] = [
                        'amount'      => core()->convertPrice($itemShippingCost),
                        'base_amount' => $itemShippingCost,
                    ];

                    if (isset($rates[$classId][$sellerId])) {
                        $rates[$classId][$sellerId] = [
                            'amount'      => core()->convertPrice($rates[$classId][$sellerId]['amount'] + $itemShippingCost),
                            'base_amount' => $rates[$classId][$sellerId]['base_amount'] + $itemShippingCost,
                        ];
                    }

                    $totalShippingCost += $itemShippingCost;
                }

                $marketplaceShippingRates = session()->get('marketplace_shipping_rates');

                if (! is_array($marketplaceShipping)) {
                    $marketplaceShippingRates['usps'] =  $rates;

                    session()->put('marketplace_shipping_rates', $marketplaceShippingRates);
                } else {
                    session()->put('marketplace_shipping_rates.usps', $rates);
                }
            }
        }

        return null;
    }

    /**
     * get the allowed services
     *
     * @return $allowed_services
     */
    public function getServices()
    {
        $allowed_services = [];

        $config_services = core()->getConfigData('sales.carriers.usps.services');
        
        $services = explode(",", $config_services);

        foreach ($services as $service_code) {
            if (isset($this->services[$service_code])) {
                $allowed_services[$service_code] = $this->services[$service_code];
            }
        }
        
        return $allowed_services;
    }

    /**
     * Checks if payment method is available
     *
     * @return array
     */
    public function isAvailable()
    {
        return core()->getConfigData('sales.carriers.usps.active');
    }
}
