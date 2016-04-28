<?php

// Request Signature on a Document [and include Notification Request to get webhook to your server] (PHP)

// To run this sample
//  1. Copy the file to your local machine and give it a .php extension (app.php)
//  2. Change "***" to appropriate values
//  3. Install Composer (PHP package manager: https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
//  4. Install DocuSign's PHP SDK using composer:
//     composer require docusign/esign-client
//  5. Ensure the 'documentFileName' variable references a file in the same directory
//  6. Execute
//     php app.php
//

require_once('vendor/docusign/esign-client/autoload.php');

$username = $_ENV["DOCUSIGN_LOGIN_EMAIL"] or "***";       // Account email address
$password = $_ENV["DOCUSIGN_LOGIN_PASSWORD"] or "***";      // Account password
$integrator_key = $_ENV["DOCUSIGN_INTEGRATOR_KEY"] or "***";  // Integrator Key (found on the Preferences -> API page)

$apiEnvironment = 'demo';

$recipientName = '***';
$recipientEmail = '***';

$webhookUrl = 'https://***.ngrok.io/'; // ngrok is great for testing

$documentFileName = '/blank.pdf';

class DocuSignSample
{

  public $apiClient;
  public $accountId;

  /////////////////////////////////////////////////////////////////////////////////////
  // Step 1: Login (used to retrieve your accountId and setup base Url in apiClient)
  /////////////////////////////////////////////////////////////////////////////////////
  public function login($username, $password, $integrator_key, $apiEnvironment)
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
  // Step 2: Create Envelope and Request Signature 
  /////////////////////////////////////////////////////////////////////////////////////
  public function signatureRequestOnDocument(
    $apiClient, 
    $accountId, 
    $documentFileName, 
    $recipient, 
    $webhookUrl,
    $status = "sent")
  {

    $documentName = "PHPSignTest1";
    $envelope_summary = null;
  
    $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi($apiClient);

    // Add a document to the envelope
    $document = new DocuSign\eSign\Model\Document();
    $document->setDocumentBase64(base64_encode(file_get_contents(__DIR__ . $documentFileName)));
    $document->setName($documentName);
    $document->setDocumentId("1");

    // Create a |SignHere| tab somewhere on the document for the recipient to sign
    $signHere = new \DocuSign\eSign\Model\SignHere();
    $signHere->setXPosition("100");
    $signHere->setYPosition("100");
    $signHere->setDocumentId("1");
    $signHere->setPageNumber("1");
    $signHere->setRecipientId("1");
    $tabs = new DocuSign\eSign\Model\Tabs();
    $tabs->setSignHereTabs(array($signHere));
    $signer = new \DocuSign\eSign\Model\Signer();
    $signer->setEmail($recipient->email);
    $signer->setName($recipient->name);
    $signer->setRecipientId("1");
    
    $signer->setTabs($tabs);

    // Add a recipient to sign the document
    $recipients = new DocuSign\eSign\Model\Recipients();
    $recipients->setSigners(array($signer));
    $envelope_definition = new DocuSign\eSign\Model\EnvelopeDefinition();
    $envelope_definition->setEmailSubject("[DocuSign PHP SDK] - Please sign this doc");

    // set envelope status to "sent" to immediately send the signature request
    $envelope_definition->setStatus($status);
    $envelope_definition->setRecipients($recipients);
    $envelope_definition->setDocuments(array($document));

    // Setup EventNotification settings
    $EventNotification = new DocuSign\eSign\Model\EventNotification();
    $EventNotification->setUrl($webhookUrl);
    $EventNotification->setLoggingEnabled('true');
    $EventNotification->setRequireAcknowledgment('true');
    $EventNotification->setUseSoapInterface('false');
    $EventNotification->setIncludeCertificateWithSoap('false');
    $EventNotification->setSignMessageWithX509Cert('false');
    $EventNotification->setIncludeDocuments('true');
    $EventNotification->setIncludeEnvelopeVoidReason('true');
    $EventNotification->setIncludeTimeZone('true');
    $EventNotification->setIncludeSenderAccountAsCustomField('true');
    $EventNotification->setIncludeDocumentFields('true');
    $EventNotification->setIncludeCertificateOfCompletion('true');

    // Add states to get notified on (Envelope and Recipient-level)
    $envelopeEvents = array();
    forEach(array('sent','delivered','completed','declined','voided') as $ev){
      $statusCode = new DocuSign\eSign\Model\EnvelopeEvent();
      $statusCode->setEnvelopeEventStatusCode($ev);
      array_push($envelopeEvents, $statusCode);
    }

    $recipientEvents = array();
    forEach(array('Sent','Delivered','Completed','Declined','AuthenticationFailed','AutoResponded') as $ev){
      $statusCode = new DocuSign\eSign\Model\RecipientEvent();
      $statusCode->setRecipientEventStatusCode($ev);
      array_push($recipientEvents, $statusCode);
    }

    $EventNotification->setEnvelopeEvents($envelopeEvents);
    $EventNotification->setRecipientEvents($recipientEvents);
    $envelope_definition->setEventNotification($EventNotification);

    $options = new \DocuSign\eSign\Api\EnvelopesApi\CreateEnvelopeOptions();
    $options->setCdseMode(null);
    $options->setMergeRolesOnDraft(null);
    $envelope_summary = $envelopeApi->createEnvelope($accountId, $envelope_definition, $options);
    if(!empty($envelope_summary))
    {
      if($status == "created")
      {
        var_dump('Created Envelope!');
      }
      else
      {
        var_dump('Created and SENT Envelope:', $envelope_summary);
      }
    }

    return;
  }

}

class DocuSignRecipient
{
  public $name;
  public $email;

  function __construct($name, $email)
  {
    $this->name = $name;
    $this->email = $email;
  }
}

$sample = new DocuSignSample();
$recipient = new DocuSignRecipient($recipientName, $recipientEmail);

// Login
$sample->login($username, $password, $integrator_key, $apiEnvironment);

// Create and Send Envelope
$sample->signatureRequestOnDocument($sample->apiClient, $sample->accountId, $documentFileName, $recipient,$webhookUrl);

?>