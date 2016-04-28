<?php

// Request Embedded Console View (PHP)

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

$username = $_ENV["DOCUSIGN_LOGIN_EMAIL"] or "***";       // Account email address
$password = $_ENV["DOCUSIGN_LOGIN_PASSWORD"] or "***";      // Account password
$integrator_key = $_ENV["DOCUSIGN_INTEGRATOR_KEY"] or "***";  // Integrator Key (found on the Preferences -> API page)

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
    }

    return $this->apiClient;

  }

  //////////////////////////////////////////////////////////////////////
  // Step 2 - Get the Embedded Console View
  //////////////////////////////////////////////////////////////////////
  function createConsoleView(
    $apiClient,
    $accountId) 
  {

    // instantiate a new EnvelopesApi object
    $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi($apiClient);

    // set the url where you want the recipient to go once they are done signing
    // - this can be used by your app to watch the URL and detect when signing has completed (or was canceled) 
    $returnUrl = new \DocuSign\eSign\Model\ConsoleViewRequest();
    $returnUrl->setReturnUrl('https://www.docusign.com/devcenter');

    // call the CreateRecipientView API
    $consoleView = $envelopeApi->createConsoleView($accountId, $returnUrl);
    if(!empty($consoleView)){
      print_r("\n\nVisit Console URL: " . $consoleView->getUrl() . "\n\n");
    }

  }

}

$sample = new DocuSignSample();

// Login
$sample->login($username, $password, $integrator_key, $apiEnvironment);

// Request for embedded signing (getting a URL) 
$sample->createConsoleView($sample->apiClient, $sample->accountId);

?>