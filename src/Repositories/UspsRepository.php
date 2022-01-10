<?php

namespace Webkul\UspsShipping\Repositories;

/**
 * USPS Reposotory
 */
class UspsRepository
{
    /**
     * Get the sellerAdmin Product
     *
     * @return mixed
     */
    public function getValidCartItems($cartItems) {

        $adminProducts = [];

        foreach ($cartItems as $item) {
            if ($item->product->type != 'virtual' && $item->product->type != 'downloadable' && $item->product->type != 'booking') {

                array_push($adminProducts, $item);
            }
        }

        return $adminProducts;
    }

    /**
     * Get the Allowde Services
     * @param $allServices
     * @return $secvices
     */
    public function validateAllowedMethods($service, $allowedServices)
    {
        $count      = 0;
        $totalCount = count($allowedServices);

        foreach ($allowedServices as $sellerMethods) {
            if ( in_array($service, $sellerMethods) ) {
                $count += 1;
            }
        }

        if ( $count == $totalCount ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the Common Services for all the cartProduct
     * @param $allServices
     * @return $secvices
     */
    public function getAllowedMethods($allServices) {

        $allowedServices = explode(",", core()->getConfigData('sales.carriers.mpusps.services'));

        foreach ($allServices as $services) {
            $allowedMethod =[];
            foreach ($services as $service) {

                foreach ($service as $serviceType =>$fedexService) {
                    if (in_array($serviceType , $allowedServices)) {
                        $allowedMethod[] = [
                            $serviceType => $fedexService
                        ];
                    } else {
                        $notAllowed[] = [
                            $serviceType => $fedexService
                        ];
                    }
                }
            }

            if ($allowedMethod == null) {
                continue;
            } else {
                $allowedMethods[] = $allowedMethod;
            }

        }

        if (isset($allowedMethods)) {

            return $this->getCommonMethods($allowedMethods);
        } else {
            return false;
        }
    }


    /**
     * get the Common method
     *
     * @param $Methods
     * @return $finalServices
     */
    public function getCommonMethods($methods)
    {
        if (! $methods == null) {
            $countMethods = count($methods);

            foreach ($methods as $fedexMethods) {
                foreach ($fedexMethods as $key => $fedexMethod) {
                    $avilableServicesArray[] = $key;
                }
            }
        }

        if( isset($avilableServicesArray) ) {
            $countServices = array_count_values($avilableServicesArray);
            $finalServices = [];

            foreach ($countServices as $serviceType => $servicesCount) {

                foreach ($methods as $fedexMethods) {
                    foreach ($fedexMethods as $type => $fedexMethod) {
                        if ($serviceType == $type && $servicesCount == $countMethods) {
                            $finalServices[$serviceType][] =$fedexMethod;
                        }
                    }
                }

                if ($finalServices == null) {
                    continue;
                }
            }

            if (!empty($finalServices)) {
                return $finalServices;
            } else {
                return null;
            }
        }
    }
}

