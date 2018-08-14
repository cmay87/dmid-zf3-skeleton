<?php

namespace Application\Authentication;

//NOTE :: DO NOT STORE THIS IN THE SESSION - IT INCLUDES THE DECRYPTED COOKIE CONTENTS
//NOTE :: This class assumes the existance of Class_Encryption with an encryption key for 'cookie'

/**
 * CHANGELOG
 * 
 * v1.0 - Initial release
 * v1.1 - Switch authentication to ERS API instead of Registration Service
 */

class ErsSSO
{
    //Constants representing the possible environments,
    //which correspond to authentication service environments
    const ENVIRONMENT_PRODUCTION    = 'production';
    const ENVIRONMENT_PRODUCTION_TEST = 'test';
    const ENVIRONMENT_DEVMAST       = 'devmast';
    const ENVIRONMENT_DEVSYS        = 'devsys';

    const INVALIDATE_ON_ERROR = TRUE;

    //Minimum version of SSO allowed
    const MIN_SSO_VERSION       = 1.0;
    const CURRENT_SSO_VERSION   = 1.1;

    //Constants representing the system options
    //in the registration service
    const SYSTEM_REGISTRATION_EE    = 'EE';
    const SYSTEM_REGISTRATION_GDTS  = 'GDTS';
    const SYSTEM_REGISTRATION_HDDS  = 'HDDS';
    const SYSTEM_REGISTRATION_LDCM  = 'LDCM';
    const SYSTEM_REGISTRATION_NONE  = '';

    private $_authenticated = FALSE;
    private $_authToken = NULL;
    private $_clientSecret = null;
    private $_contactId = 0;
    private $_cookiePayload = NULL;
    private $_encryptionClass = NULL;
    private $_environment;
    private $_ersApiUrl;
    private $_errors = array();
    private $_registrationSystemId = '';
    private $_user;

    public function __construct($clientSecret, $environment = 'production', $registrationSystemId = 'EE', $encryptionClass = null)
    {
        $this->_clientSecret = $clientSecret;
        
        //Check for valid environments
        switch ($environment) {
            case self::ENVIRONMENT_PRODUCTION:
                $this->_ersApiUrl = 'https://ers.cr.usgs.gov/api/';
                break;
            case self::ENVIRONMENT_PRODUCTION_TEST:
                $this->_ersApiUrl = 'https://erstest.cr.usgs.gov/api/';
                break;
            case self::ENVIRONMENT_DEVMAST:
                $this->_ersApiUrl = 'https://ersdevmast.cr.usgs.gov/api/';

                break;
            case self::ENVIRONMENT_DEVSYS:
                $this->_ersApiUrl = 'https://ersdev.cr.usgs.gov/devsys/api/';

                break;
            default:
                throw new Exception("Environment '{$environment}' is not supported");
        }
        
        $this->_environment = $environment;

        //Check for valid systems
        switch ($registrationSystemId) {
            case self::SYSTEM_REGISTRATION_EE:
            case self::SYSTEM_REGISTRATION_GDTS:
            case self::SYSTEM_REGISTRATION_HDDS:
            case self::SYSTEM_REGISTRATION_LDCM:
            case self::SYSTEM_REGISTRATION_NONE:
                //cmay - Apparently there is a 'feature' of the registration service that allows an empty systemId to be based so that roles from
                //all system are acquired.
                
                $this->_registrationSystemId = $registrationSystemId;
                break;
            default:
                throw new Exception("Registration System ID '{$registrationSystemId}' is not supported");
        }

        if ($encryptionClass === null && !class_exists('Class_Encryption')) {
            throw new Exception('Encryption class has not been defined');
        }

        $this->_encryptionClass = ($encryptionClass !== null) ? $encryptionClass : new Class_Encryption();
    }

    //Attempt to authenticate from the cookie
    public function authenticateCookie()
    {
        //If it's already authenticated, don't do it again
        if ($this->_authenticated === TRUE) {
            return TRUE;
        }

        if ($this->isCookiePresent() === FALSE) {
            throw new Exception('Cookie is Missing');
        }

        //If the cookie hasn't been validated, do it
        if ($this->_cookiePayload === NULL) {
            if ($this->_validateCookie() === FALSE) {
                $this->_authenticated = FALSE;
                $this->_cookiePayload = NULL;

                $this->_errors[] = 'Cookie appears to be invalid';

                if (self::INVALIDATE_ON_ERROR === TRUE) {
                    $this->invalidateCookie();
                }

                return FALSE;
            }
        }

        //Now attempt to login the user
        try {
            if ($this->_loginUser() === FALSE) {
                throw new Exception('Authentication Failed');
            }
        } catch (Exception $e) {
            $this->_errors[] = 'User credential verification failed';

            $this->_authenticated = FALSE;
            $this->_cookiePayload = NULL;

            if (self::INVALIDATE_ON_ERROR === TRUE) {
                $this->invalidateCookie();
            }

            return FALSE;
        }

        //Mark the authentication
        $this->_authenticated = TRUE;

        //Call getUserInfo and store user object
        try {
            $this->_user = $this->_getUserInfo();
            $this->_contactId = $this->_user->contact_id;
            
            if ($this->_user === NULL) {
                throw new Exception('User information lookup failed');
            }
        } catch (Exception $e) {
            $this->_errors[] = 'User information lookup failed';

            $this->_authenticated = FALSE;
            $this->_cookiePayload = null;

            if (self::INVALIDATE_ON_ERROR === TRUE) {
                $this->invalidateCookie();
            }

            return FALSE;
        }

        return TRUE;
    }

    public function getAuthType()
    {
        //Make sure there is a payload
        if ($this->_cookiePayload === NULL) {
            throw new Exception('Cookie payload missing');
        }

        return $this->_cookiePayload->authType;
    }
    
    public function getAuthService()
    {
        //Make sure there is a payload
        if ($this->_cookiePayload === NULL) {
            throw new Exception('Cookie payload missing');
        }

        return $this->_cookiePayload->authService;
    }

    public function getCookieCreationTime()
    {
        return $this->_cookiePayload->created;
    }

    public function getContactId()
    {
        //Make sure the user is authenticated
        if ($this->_authenticated === FALSE) {
            throw new Exception('User has not been authenticated yet');
        }

        return $this->_contactId;
    }

    private function _getCookieName()
    {
        return "EROS_SSO_{$this->_environment}_secure";
    }

    public function getErrors($clear = FALSE)
    {
        //Do we clear the errors when grabbing them?
        if ($clear === TRUE) {
            $errors = $this->_errors;
            $this->_errors = array();

            return $errors;

        } else {
            return $this->_errors;
        }
    }

    public function getLastProfileUpdateTime()
    {
        return $this->_cookiePayload->updated;
    }

    public function getPassword()
    {
        //Make sure there is a payload
        if ($this->_cookiePayload === NULL) {
            throw new Exception('Cookie payload missing');
        }

        return $this->_encryptionClass->decrypt($this->_cookiePayload->secret, 'cookie');
    }

    public function getUser()
    {
        if ($this->_authenticated !== TRUE) {
            throw new Exception('User has not been authenticated');
        }

        return $this->_user;
    }

    private function _getUserInfo()
    {
        //Calls the registration service and builds a user object
        try {
            return $this->_getRequest("me/{$this->_registrationSystemId}");
        } catch (Exception $e) {
            $this->_errors[] = 'User lookup failed';
            return NULL;
        }
    }

    public function getUsername()
    {
        //Make sure there is a payload
        if ($this->_cookiePayload === NULL) {
            throw new Exception('Cookie payload missing');
        }

        return $this->_encryptionClass->decrypt($this->_cookiePayload->id, 'cookie');
    }

    public function invalidateCookie()
    {
        setcookie($this->_getCookieName(), '', 1, '/', 'usgs.gov', TRUE, FALSE);
    }

    public function isAuthenticated()
    {
        return $this->_authenticated;
    }

    public function isCookiePresent()
    {
        return array_key_exists($this->_getCookieName(), $_COOKIE);
    }

    private function _loginUser()
    {
        //Calls the ERS API auth
        try {
            //Login and return the contact id
            $data = array(
                'username' => $this->getUsername(),
                'password' => $this->getPassword(),
                'auth_type' => $this->getAuthType(),
                'client_secret' => $this->_clientSecret
            );

            try {
                //Submit auth request - exception thrown on error
                $result = $this->_postRequest('auth', $data);

                //Store authToken
                if (isset($result->authToken)) {
                    $this->_authToken = $result->authToken;
                } else {
                    throw new Exception('authToken was not found in the response');
                }
                
                return TRUE;
            } catch (Exception $e) {
                $this->_errors[] = $e->getMessage();
                return FALSE;
            }
            
        } catch (SoapFault $e) {
            $this->_errors[] = $e->getMessage();
            return FALSE;
        } catch (Exception $e) {
            $this->_errors[] = 'Login request failed';
            return FALSE;
        }
    }

    private function _getRequest($apiMethod)
    {
        $ch = curl_init($this->_ersApiUrl . "{$apiMethod}/");
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if ($this->_environment !== self::ENVIRONMENT_PRODUCTION) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        
        if ($this->_authenticated && $this->_authToken !== NULL && $apiMethod != 'auth') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-AuthToken: {$this->_authToken}"));
        }        
        
        $output = curl_exec($ch);
        if ($output === false) {
            throw new Exception('API Request Failed: ' . curl_error($ch));
        }

        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        try {
            if ($httpStatus == 404) {
                throw new Exception('404 Not Found');
            } elseif ($httpStatus == 401) {
                throw new Exception('401 Unauthorized');
            } elseif ($httpStatus >= 400) {
                throw new Exception('Error Code: ' . $httpStatus);
            }
        } catch (Exception $e) {
            throw new Exception("Unable to complete '{$apiMethod}' request - {$e->getMessage()}");
        }

        curl_close($ch);        
        $json = json_decode($output);

        if ($json === false) {
            throw new Exception("Unable to complete '{$apiMethod}' request - HTTP Status {$httpStatus}: {$output}");
        }
        
        return $this->_parseApiResponse($json);
    }
    
    private function _parseApiResponse($json)
    {
        if (!isset($json->status)) {
            throw new Exception('Response status not found - unable to validate');
        }
        
        if ($json->status != 10) {
            if (!isset($json->errors)) {
                throw new Exception('Response errors array not found - unable to validate');
            }

            if (count($json->errors) > 0) {
                throw new Exception(implode(', ', $json->errors));
            } else {
                throw new Exception("API did not respond with errors but an unsuccessful status code {$json->status} was found");
            }
        }
        
        if (!isset($json->data)) {
            throw new Exception('Response data not found - unable to validate');
        }
        
        return $json->data;
    }
    
    private function _postRequest($apiMethod, $data = array())
    {
        $ch = curl_init($this->_ersApiUrl . "{$apiMethod}/");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if ($this->_environment !== self::ENVIRONMENT_PRODUCTION) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }        
        
        if ($this->_authenticated && $this->_authToken !== NULL && $apiMethod != 'auth') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-AuthToken: {$this->_authToken}"));
        }        
        
        $output = curl_exec($ch);
        if ($output === false) {
            throw new Exception('API Request Failed: ' . curl_error($ch));
        }
        
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        try {
            if ($httpStatus == 404) {
                throw new Exception('404 Not Found');
            } elseif ($httpStatus == 401) {
                throw new Exception('401 Unauthorized');
            } elseif ($httpStatus >= 400) {
                throw new Exception('Error Code: ' . $httpStatus);
            }
        } catch (Exception $e) {
            throw new Exception("Unable to complete '{$apiMethod}' request - {$e->getMessage()}");
        }

        curl_close($ch);        
        $json = json_decode($output);

        if ($json === false) {
            throw new Exception("Unable to complete '{$apiMethod}' request - HTTP Status {$httpStatus}: {$output}");
        }
        
        return $this->_parseApiResponse($json);
    }
    
    public function validateCookie()
    {
        return $this->_validateCookie();
    }

    private function _validateCookie()
    {
        if ($this->isCookiePresent()) {
            try {
                $cookieContents = base64_decode($_COOKIE[$this->_getCookieName()], TRUE);

                //If the contents cannot be decoded then it is probably a forgery attempt
                //The sign on point will use the base64_encode, which ensures valid values
                if ($cookieContents === FALSE) {
                    throw new Exception('Cookie decoding failed');
                }

                //The decode worked, so now get it out of JSON format
                $cookieContents = json_decode($cookieContents);
                if ($cookieContents === NULL) {
                    throw new Exception('Cookie format is invalid');
                }

                if (!isset($cookieContents->id) || !isset($cookieContents->secret)
                    || !isset($cookieContents->authType) || !isset($cookieContents->state)
                ) {
                    throw new Exception('Cookie contents are invalid');
                }

                //Save the cookie payload, this will be the only username/password storage
                $this->_cookiePayload = $cookieContents;
                $authType = $cookieContents->authType;
                $version = $cookieContents->version;

                //Finally check for anti-forgery
                $hash = hash('sha256', "{$cookieContents->id}.{$cookieContents->secret}.{$authType}.{$version}");

                if ($cookieContents->state != $hash) {
                    throw new Exception('Cookie is not authentic');
                }

                //Is the SSO Version of the cookie compatible?
                if (self::MIN_SSO_VERSION > $cookieContents->version) {
                    throw new Exception('Single Sign-On Cookie Version Outdated');
                }

                return TRUE;
            } catch (Exception $e) {
                $this->_cookiePayload = NULL;
                $this->_errors[] = $e->getMessage();

                if (self::INVALIDATE_ON_ERROR === TRUE) {
                    $this->invalidateCookie();
                }

                return FALSE;
            }
        } else {
            $this->_errors[] = 'Cookie does not exist';
            return FALSE;
        }
    }
}
