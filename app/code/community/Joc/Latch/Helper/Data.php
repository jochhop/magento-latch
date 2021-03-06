<?php

class Joc_Latch_Helper_Data extends Mage_Core_Helper_Abstract {
    
    const LATCH_ENABLED = 'latch/general/enable';
    
    const LATCH_APPLICATION_ID_PATH = 'latch/api_settings/app_id';
    const LATCH_SECRET_KEY_PATH     = 'latch/api_settings/app_secret_key';
    const LATCH_API_URL_PATH        = 'latch/api_settings/app_api_url';
    
    public function getApplicationId() {
        return $this->_getConfig(self::LATCH_APPLICATION_ID_PATH);
    }
    
    public function getSecretKey() {
        return $this->_getConfig(self::LATCH_SECRET_KEY_PATH);
    }
    
    public function getApiUrl() {
        return $this->_getConfig(self::LATCH_API_URL_PATH);
    }
    
    public function getIfEnabled() {
        return $this->_getConfig(self::LATCH_ENABLED);
    }
    
    protected function _getConfig($path) {
        return Mage::getStoreConfig($path);
    }
    
    /**
     * Invoke Latch library for pair a customer account with Latch app
     * 
     * @return array with status and message of the api response
     */
    public function pairCustomer($token) {
        $appId = $this->getApplicationId();
        $appSecret = $this->getSecretKey();
        $apiUrl = $this->getApiUrl();

        if(!empty($appId) && !empty($appSecret) && !empty($token)){
            require_once(Mage::getBaseDir('lib') . '/Latch/latch.php');

            if($apiUrl) {
                $api = new Latch($appId, $appSecret, $apiUrl);
            } else {
                $api = new Latch($appId, $appSecret);
            }

            $apiResponse = $api->pair($token);
            $responseData = $apiResponse->getData();

            if(!empty($responseData)) {
                $accountId = $responseData->{"accountId"};
            }

            if(!empty($accountId)) {
                /* @var $customer Mage_Customer_Model_Customer */
                $customer = Mage::getSingleton('customer/session')->getCustomer();

                try {
                    $customer->setData('latch_id', $accountId);
                    $customer->save();

                    return array("status" => 1, "message" => $this->__("Your account was linked with Latch successfully."));
                } catch (Exception $ex) {
                    return array("status" => 0, "message" => $this->__("Couldn't link the given token with Latch: ") . $this->__($ex->getMessage()));
                }
            } elseif($apiResponse->getError() == NULL) {
                return array("status" => 0, "message" => $this->__("Latch pairing error: Cannot connect to the server. Please, try again later."));
            } else {
                return array("status" => 0, "message" => $this->__("Couldn't link the given token with Latch: ") . $this->__($apiResponse->getError()->getMessage()));
            }
        } else {
            return array("status" => 0, "message" => $this->__("Latch pairing error: Invalid parameters."));
        }
    }
    
    /**
     * Invoke Latch library for pair an admin account with Latch app
     * 
     * @param string $token
     * @param Mage_Admin_Model_User $user Administrator user object
     * @return array with status and message of the api response
     */
    public function pairAdmin($token, $user = null) {
        $appId = $this->getApplicationId();
        $appSecret = $this->getSecretKey();
        $apiUrl = $this->getApiUrl();

        if(!empty($appId) && !empty($appSecret) && !empty($token)){
            require_once(Mage::getBaseDir('lib') . '/Latch/latch.php');

            if($apiUrl) {
                $api = new Latch($appId, $appSecret, $apiUrl);
            } else {
                $api = new Latch($appId, $appSecret);
            }

            $apiResponse = $api->pair($token);
            $responseData = $apiResponse->getData();

            if(!empty($responseData)) {
                $accountId = $responseData->{"accountId"};
            }

            if(!empty($accountId)) {
                if($user) {
                    /* @var $user Mage_Admin_Model_User */
                    $user->setData('latch_id', $accountId);
                    $mustSave = Mage::getSingleton('core/session')->getAdminMustSave();
                    
                    if($mustSave) {
                        $user->save();
                    }
                    
                    return array("status" => 1, "message" => $this->__("The account was linked with Latch successfully."));
                } else {
                    return array("status" => 0, "message" => $this->__("Can't link non admin user with Latch."));
                }
            } elseif($apiResponse->getError() == NULL) {
                return array("status" => 0, "message" => $this->__("Latch pairing error: Cannot connect to the server. Please, try again later."));
            } else {
                return array("status" => 0, "message" => $this->__("Couldn't link the given token with Latch: ") . $this->__($apiResponse->getError()->getMessage()));
            }
        } else {
            return array("status" => 0, "message" => $this->__("Latch pairing error: Invalid parameters."));
        }
    }
    
    /**
     * Invoke Latch lib for unpair customer account with Latch
     * 
     * @return array with the status of the api response
     */
    public function unpairCustomer() {
        $appId = $this->getApplicationId();
        $appSecret = $this->getSecretKey();
        $apiUrl = $this->getApiUrl();
        
        if(!empty($appId) && !empty($appSecret)) {
            require_once(Mage::getBaseDir('lib') . '/Latch/latch.php');
            
            if($apiUrl) {
                $api = new Latch($appId, $appSecret, $apiUrl);
            } else {
                $api = new Latch($appId, $appSecret);
            }
            
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $latchId = $customer->getData('latch_id');
            
            if($latchId) {
                $apiResponse = $api->unpair($latchId);

                if($apiResponse->getError() == NULL) { 
                    try {
                        $customer->setData('latch_id', '');
                        $customer->save();
                    } catch (Exception $ex) {
                        return array("status" => 0, "message" => $this->__("Couldn't unlink with Latch: ") . $this->__($ex->getMessage()));
                    }
                    return array("status" => 1, "message" => $this->__("Your account was unlinked with Latch successfully."));
                } else {
                    return array("status" => 0, "message" => $this->__("Couldn't unlink your account with Latch: ") . $this->__($apiResponse->getError()->getMessage()));
                }
            } else {
                return array("status" => 0, "message" => $this->__("There is no Latch Id to unlink."));
            }
        } else {
            return array("status" => 0, "message" => $this->__("Your account wasn't unlinked with Latch. Please try again later."));
        }
    }
    
    /**
     * Invoke Latch lib for unpair admin account with Latch
     * 
     * @param Mage_Admin_Model_User $user
     * @return array
     */
    public function unpairAdmin($user = null) {
        $appId = $this->getApplicationId();
        $appSecret = $this->getSecretKey();
        $apiUrl = $this->getApiUrl();
        
        if(!empty($appId) && !empty($appSecret)) {
            require_once(Mage::getBaseDir('lib') . '/Latch/latch.php');
            
            if($apiUrl) {
                $api = new Latch($appId, $appSecret, $apiUrl);
            } else {
                $api = new Latch($appId, $appSecret);
            }

            $latchId = Mage::getModel('admin/user')->load($user->getId())->getData('latch_id');
            $apiResponse = $api->unpair($latchId);
            
            if($latchId) {
                if($apiResponse->getError() == NULL) { 
                    $user->setData('latch_id', '');
                    
                    $mustSave = Mage::getSingleton('core/session')->getAdminMustSave();
                    
                    if($mustSave) {
                        $user->save();
                    }
                    
                    return array("status" => 1, "message" => $this->__("The account was unlinked with Latch successfully."));
                } else {
                    return array("status" => 0, "message" => $this->__("Couldn't unlink the account with Latch: ") . $this->__($apiResponse->getError()->getMessage()));
                }
            } else {
                return array("status" => 0, "message" => $this->__("There is no Latch Id to unlink."));
            }
        } else {
            return array("status" => 0, "message" => $this->__("The account wasn't unlinked with Latch. Please try again later."));
        }
    }
    
    /**
     * Check if customer has Latch enabled
     * 
     * @param string $latchId
     * @param int $userId
     * @return array
     */
    public function getIfLatchEnabled($latchId, $userId) {
        $appId = $this->getApplicationId();
        $appSecret = $this->getSecretKey();
        $apiUrl = $this->getApiUrl();
        
        if(!empty($latchId) && !empty($appId) && !empty($appSecret)) {
            require_once(Mage::getBaseDir('lib') . '/Latch/latch.php');
            
            if($apiUrl) {
                $api = new Latch($appId, $appSecret, $apiUrl);
            } else {
                $api = new Latch($appId, $appSecret);
            }

            $apiResponse = $api->status($latchId);
            $responseData = $apiResponse->getData();
            $responseError = $apiResponse->getError();
            
            if (empty($apiResponse) || (empty($responseData) && empty($responseError))) {
                return array("status" => 0, "message" => $this->__("Latch is not ready. Please try to log out and log in again."));
            } else {
                if (!empty($responseError)) {
                    if ($responseError->getCode() == 201) {
                        $customer = Mage::getModel('customer/customer')->load($userId);
                        $customer->setData('latch_id', $latchId);
                        
                        try{
                            $customer->save();
                        } catch (Exception $ex) {
                            return array("status" => 0, "message" => $this->__("Something was wrong, please try to log in again later: ") . $this->__($ex->getMessage()));
                        }
                    } else {
                        return array("status" => 0, "message" => $this->__("Something was wrong, please try to log in again later."));
                    }
                }
            }
            
            if (!empty($responseData) && $responseData->{"operations"}->{$appId}->{"status"} === "on") {
                return array("status" => 0, "message" => "");
            } else {
                return array("status" => 1, "message" => $this->__("Invalid login or password"));
            }
        }
    }
    
    /**
     * Check if admin has Latch enabled
     * 
     * @param string $latchId
     * @param Mage_Admin_Model_User $user
     * @return array
     */
    public function getIfAdminLatchEnabled($latchId, $user) {
        $appId = $this->getApplicationId();
        $appSecret = $this->getSecretKey();
        $apiUrl = $this->getApiUrl();
        
        if(!empty($latchId) && !empty($appId) && !empty($appSecret)) {
            require_once(Mage::getBaseDir('lib') . '/Latch/latch.php');
            
            if($apiUrl) {
                $api = new Latch($appId, $appSecret, $apiUrl);
            } else {
                $api = new Latch($appId, $appSecret);
            }

            $apiResponse = $api->status($latchId);
            $responseData = $apiResponse->getData();
            $responseError = $apiResponse->getError();
            
            if (empty($apiResponse) || (empty($responseData) && empty($responseError))) {
                return array("status" => 0, "message" => $this->__("Latch is not ready. Please try to log out and log in again."));
            } else {
                if (!empty($responseError)) {
                    if ($responseError->getCode() == 201) {
                        $user->setData('latch_id', $latchId);
                        
                        try{
                            $user->save();
                        } catch (Exception $ex) {
                            return array("status" => 0, "message" => $this->__("Something was wrong, please try to log in again later: ") . $this->__($ex->getMessage()));
                        }
                    } else {
                        return array("status" => 0, "message" => $this->__("Something was wrong, please try to log in again later."));
                    }
                }
            }
            
            if (!empty($responseData) && $responseData->{"operations"}->{$appId}->{"status"} === "on") {
                return array("status" => 0, "message" => "");
            } else {
                return array("status" => 1, "message" => $this->__("Invalid login or password"));
            }
        }
    }
    
}
