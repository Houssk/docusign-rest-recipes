<?php

// Request Envelope Recipient Statuses (PHP)

// To run this sample
//  1. Copy the file to your local machine and give it a .php extension (app.php)
//  2. Change "***" to appropriate values
//  3. Install Composer (PHP package manager: https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
//  4. Install DocuSign's PHP SDK using composer:
//     composer require docusign/esign-client
//  5. Execute
//     php app.php
//

require_once('vendor/docusign/esign-client/autoload.php');

$username = "***";          // Account email address
$password = "***";          // Account password
$integrator_key = "***";    // Integrator Key (found on the Preferences -> API page)

$envelopeId = '';

$apiEnvironment = 'demo';

class DocuSignSample
{

    public $apiClient;
    public $accountId;
    public $envelopeId;

    /////////////////////////////////////////////////////////////////////////////////////
    // Step 1: Login (used to retrieve your accountId and setup base Url in apiClient)
    /////////////////////////////////////////////////////////////////////////////////////
    public function login(
        $username, 
        $password, 
        $integrator_key, 
        $apiEnvironment)
    {

        // change to production before going live
        $host = "https://{$apiEnvironment}.docusign.net/restapi";

        // create configuration object and configure custom auth header
        $config = new DocuSign\eSign\Configuration();
        $config->setHost($host);
        $config->addDefaultHeader("X-DocuSign-Authentication", "{\"Username\":\"" . $username . "\",\"Password\":\"" . $password . "\",\"IntegratorKey\":\"" . $integrator_key . "\"}");

        // instantiate a new docusign api client
        $this->apiClient = new DocuSign\eSign\ApiClient($config);
        $accountId = null;

        try 
        {

            $authenticationApi = new DocuSign\eSign\Api\AuthenticationApi($this->apiClient);
            $options = new \DocuSign\eSign\Api\AuthenticationApi\LoginOptions();
            $loginInformation = $authenticationApi->login($options);
            if(isset($loginInformation) && count($loginInformation) > 0)
            {
                $this->loginAccount = $loginInformation->getLoginAccounts()[0];
                if(isset($loginInformation))
                {
                    $accountId = $this->loginAccount->getAccountId();
                    if(!empty($accountId))
                    {
                        $this->accountId = $accountId;
                    }
                }
            }
        }
        catch (DocuSign\eSign\ApiException $ex)
        {
            echo "Exception: " . $ex->getMessage() . "\n";
            echo "API Response: " . $ex->getResponseBody() . "\n";
            return false;
        }

        return $this->apiClient;

    }

    /////////////////////////////////////////////////////////////////////////////////////
    // Step 2: Get Envelope Status
    /////////////////////////////////////////////////////////////////////////////////////
    function listRecipients(
        $apiClient,
        $accountId,
        $envelopeId) 
    {

        // instantiate a new EnvelopesApi object
        $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi($apiClient);

        // call the listDocuments API to list all recipients
        $recipients = $envelopeApi->listRecipients($accountId, $envelopeId);
        if(!empty($recipients)){
            print_r($recipients->__toString());
        }

    }

}

$sample = new DocuSignSample();

// Login
$login = $sample->login($username, $password, $integrator_key, $apiEnvironment);
if($login == false){
    return;
}

// Request status of Envelope
$sample->listRecipients($sample->apiClient, $sample->accountId, $envelopeId);


?>