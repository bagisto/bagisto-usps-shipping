<?php

namespace Webkul\UspsShipping\Repositories;

class UspsRepository
{
    /**
     * Get the sellerAdmin Product
     *
     * @param $cartItems
     * 
     * @return mixed
     */
    public function getValidCartItems($cartItems) 
    {
        $adminProducts = array_filter($cartItems, function ($item) {
            
            return !in_array($item->product->type, ['virtual', 'downloadable', 'booking']);
        });
    
        return $adminProducts;
    }

    /**
     * Get the Allowde Services
     * @param $allowedServices
     * @param $service
     * 
     * @return $secvices
     */
    public function validateAllowedMethods($service, $allowedServices)
    {
        $count = 0;
        $totalCount = count($allowedServices);

        foreach ($allowedServices as $sellerMethods) {
            if (in_array($service, $sellerMethods)) {
                $count += 1;
            }
        }

        if ($count != $totalCount) {
            return false;
        }

        return true;
    }

    /**
     * Get the Common Services for all the cartProduct
     * @param $allServices
     * 
     * @return $secvices
     */
    public function getAllowedMethods($allServices) 
    {
        $allowedServices = explode(",", core()->getConfigData('sales.carriers.mpusps.services'));

        foreach ($allServices as $service) {
            $allowedMethod =[]; {
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
            }
            $allowedMethods[] = $allowedMethod;    
        }

        if (! isset($allowedMethods)) {
            return false;
        }

        return $this->getCommonMethods($allowedMethods);
    }


    /**
     * get the Common method
     *
     * @param $Methods
     * 
     * @return $finalServices
     */
    public function getCommonMethods($methods)
    {
        if (! is_null($methods)) {
            $countMethods = count($methods);

            foreach ($methods as $key => $fedexMethod) {
                $avilableServicesArray[] = $key;
            }
        }  

        if (isset($avilableServicesArray)) {
            $countServices = array_count_values($avilableServicesArray);

            $finalServices = [];

            foreach ($countServices as $serviceType => $servicesCount) {

                foreach ($methods as $type => $fedexMethod) {

                    if ($serviceType == $type && $servicesCount == $countMethods) {
                        $finalServices[$serviceType][] =$fedexMethod;
                    }
                }

                if ($finalServices == null) {
                    continue;
                }
            }

            if (! empty($finalServices)) {
                return $finalServices;
            }

            return null;
        }
    }
}

