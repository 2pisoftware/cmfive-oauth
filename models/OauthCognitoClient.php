<?php

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient as AwsCognitoClient;
use Aws\Sts\StsClient as AwsStsClient;
use Aws\Exception\AwsException as AwsException;

class OauthCognitoClient extends DbService
{
    public $_system;
    public $_logging = [];
    public $_critical = false;
    public $_tokenIssuer;
    public $_user_pool;

    public function makeWarningsCritical()
    {
        if (!$this->_critical) {
            set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) {
                throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
            });
        }
        $this->_critical = true;
    }

    public function makeWarningsSafe()
    {
        if ($this->_critical) {
            restore_error_handler();
        }
        $this->_critical = false;
    }

    public function getTokenIssuer($provider, $appAuth = null)
    {
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
        if (!empty($appAuth)) {
            $headers['Authorization'] = "Basic " . $appAuth;
        }
        try {
            $this->_tokenIssuer = new GuzzleHttp\Client([
                'base_uri' => $provider,
                'headers' => $headers,
            ]);

            return $this->_tokenIssuer;
        } catch (Exception $ex) {
            if (is_a($ex, "GuzzleHttp\Exception\ClientException")) {
                $results = "Exception occurred, " . $ex->getMessage();
            } else $results = "Internal or network error";
            $this->failHandler(['From' => "Cognito Token Issuer", 'Failed' => "Connection", 'Info' => $results]);
            return null;
        }
    }

    public function getIssuedToken($param = [])
    {
        if (is_null($this->_tokenIssuer)) {
            $this->failHandler(['From' => "Cognito Token Issuer", 'Failed' => "Requesting", 'Info' => "Connection non-existent"]);
            return null;
        }

        try {
            $connection = $this->_tokenIssuer;
            $response = $connection->request(
                'POST',
                '',
                [
                    'form_params' => $param
                ]
            );

            return $response->getBody()->getContents();
        } catch (Exception $ex) {
            if (is_a($ex, "GuzzleHttp\Exception\ClientException")) {
                $results = "Exception occurred, " . $ex->getMessage();
            } else {
                $results = "Internal or network error";
            }
            $this->failHandler(['From' => "Cognito Token Issuer", 'Failed' => "Reading", 'Info' => $results]);
            return null;
        }
    }

    // This is an user-token action for 'own' details
    // As such, it proxies as a token validator
    public function getUserByAccessToken($accessToken)
    {
        try {
            $this->makeWarningsCritical();

            $results = $this->_system->getUser([
                'AccessToken' => $accessToken, // REQUIRED
            ]);

            $this->makeWarningsSafe();

            if (empty($results)) {
                return null;
            }

            return $results;
        } catch (Exception $ex) {
            $this->makeWarningsSafe();
            $results = "Internal, network or access error";
            $this->failHandler(['From' => "Cognito", 'Failed' => "User By Token", 'Info' => $results]);
            return null;
        }
    }


    // This is an user-token action for 'own' details
    // As such, it proxies as a token validator
    public function putNewUserWithGlobalId($userDetails)
    {
        if (empty($userDetails['username']) || empty($userDetails['email']) || empty($userDetails['id'])) {
            return null;
        }

        try {
            $this->makeWarningsCritical();

            $results = $this->_system->adminCreateUser([
                'UserPoolId' => $this->_user_pool,
                'DesiredDeliveryMediums' => ["EMAIL"], // make sure no SMS!
                'MessageAction' => "SUPPRESS", // block email anyway

                'Username' => $userDetails['username'],
                'UserAttributes' => [
                    ['Name' => "custom:global_user_id", 'Value' => strval($userDetails['id'])],
                    ['Name' => "email", 'Value' => $userDetails['email']],
                ],
            ]);

            $this->makeWarningsSafe();

            if (empty($results)) {
                return null;
            }

            return $results;
        } catch (Exception $ex) {
            $this->makeWarningsSafe();
            $results = "Internal, network or access error";
            $this->failHandler(['From' => "Cognito", 'Failed' => "User Creation", 'Info' => $results]);
            return null;
        }
    }


    public function setUserPasswordByUserName($username, $setPassword)
    {
        if (empty($username) || empty($setPassword)) {
            return null;
        }

        try {
            $this->makeWarningsCritical();

            $results = $this->_system->adminSetUserPassword([
                'Password' => $setPassword, // REQUIRED
                'Permanent' => true,
                'UserPoolId' => $this->_user_pool, // REQUIRED
                'Username' => $username, // REQUIRED
            ]);

            $this->makeWarningsSafe();

            if (empty($results)) {
                return null;
            }

            return $results;
        } catch (Exception $ex) {
            $this->makeWarningsSafe();
            $results = "Internal, network or access error";
            $this->failHandler(['From' => "Cognito", 'Failed' => "Set User Password", 'Info' => $results]);
            return null;
        }
    }


    // This is an admin action, by role, to retrieve user (if exists)
    public function getUserByUsername($userName)
    {
        try {
            $this->makeWarningsCritical();

            $results = $this->_system->adminGetUser([
                'UserPoolId' => $this->_user_pool, // REQUIRED
                'Username' => $userName, // REQUIRED
            ]);
            $this->makeWarningsSafe();

            if (empty($results)) {
                return null;
            }

            return $results;
        } catch (AwsException $ex) {
            $this->makeWarningsSafe();
            if ($ex->getAwsErrorCode() == "UserNotFoundException") {
                return null;
            }

            $results = "Internal, network or access error";
            $this->failHandler(['From' => "Cognito", 'Failed' => "Details By User", 'Info' => $results]);
            return null;
        }
    }

    public function getSystem($forUserPool = null)
    {
        try {
            $this->makeWarningsCritical();

            $args =
                [
                   
                    'region' => 'ap-southeast-2',
                    'version' => 'latest',

                    // we don't expect to need these, as can specify on further SDK/API calls as made
                    // 'app_client_id' => 'xxxyyyzzz',
                    // 'app_client_secret' => 'xxxyyyzzz',
                    // 'user_pool_id' => 'userPool',
                ];

            // get aws client system
            $CognitoClient =  new AwsCognitoClient($args);

            $this->makeWarningsSafe();

            if (empty($CognitoClient)) {
                return null;
            }
            $this->_system = $CognitoClient;
            $this->_user_pool = $forUserPool;
            return $this->_system;
        } catch (Exception $ex) {
            $this->makeWarningsSafe();
            $results = "Internal, network or access error";
            $this->failHandler(['From' => "Cognito", 'Failed' => "Configuration", 'Info' => $results]);
            return null;
        }
    }


    public function failHandler($details = null)
    {
        $message = "Unascribed failure occured in AWS Cognito service.";

        if (is_array($details)) {
            $From = isset($details['From']) ? $details['From'] : "";
            $Failed = isset($details['Failed']) ? $details['Failed'] : "";
            $Info = isset($details['Info']) ? $details['Info'] : "";

            $message = "Oauth Module (" . $From . ") failed for " . $Failed . ": " . $Info;
        }
        $this->w->Log->error($message);
        $this->_logging[] = $message;
    }

    public function failCount()
    {
        return count($this->_logging);
    }

    public function failMailer($email)
    {
        $to = $email;
        $replyto = Config::get('main.company_support_email');
        $subject = "Cognito oauth system error.";
        $message = "The following problems were logged: <br> \n";
        foreach ($this->_logging as $logged) {
            $message .= $logged . " <br> \n";
        }
        $this->w->Mail->sendMail($to, $replyto, $subject, $message);
    }
}
