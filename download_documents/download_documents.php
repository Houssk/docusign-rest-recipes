<?php

// Download Envelope Documents (PHP)
// 
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

$envelopeId = '***';

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

  /////////////////////////////////////////////////////////////////////////////////////
  // Step 2: Get Envelope Documents
  /////////////////////////////////////////////////////////////////////////////////////
  function listDocumentsForEnvelope(
    $apiClient,
    $accountId,
    $envelopeId) 
  {

    // instantiate a new EnvelopesApi object
    $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi($apiClient);

    // call the listDocuments API to get a list of documents
    $documents = $envelopeApi->listDocuments($accountId, $envelopeId);
    if(!empty($documents)){
      var_dump($documents);
      foreach($documents->getEnvelopeDocuments() as $document){
        // initiate download of each document
        $this->downloadEnvelopeDoc($apiClient, $accountId, $envelopeId, $document);
      }
    }

  }

  function downloadEnvelopeDoc(
    $apiClient, 
    $accountId, 
    $envelopeId,
    $document) {

    // instantiate a new EnvelopesApi object
    $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi($apiClient);

    // get the Document (arrives as SplFileObject in temp directory)
    $savedDoc = $envelopeApi->getDocument($accountId, $envelopeId, $document->getDocumentId());

    // move the document
    $oldName = $savedDoc->getPathname();
    $newName = 'dl/' . $envelopeId . "-" . $document->getName().'.pdf';
    rename($oldName, $newName);

  }

}

$sample = new DocuSignSample();

// Login
$sample->login($username, $password, $integrator_key, $apiEnvironment);

// Request documents for Envelope
$sample->listDocumentsForEnvelope($sample->apiClient, $sample->accountId, $envelopeId);


?>