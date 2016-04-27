<?php

// Request Signature on a Document (PHP)

// To run this sample
//  1. Copy the file to your local machine and give it a .php extension (app.php)
//  2. Change "***" to appropriate values
//  3. Install Composer (PHP package manager: https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
//  4. Install DocuSign's PHP SDK using composer:
//     composer require docusign/esign-client
//  6. Execute
//     php app.php
//

require_once('vendor/docusign/esign-client/autoload.php');

$username = $_ENV["DOCUSIGN_LOGIN_EMAIL"] or "***";       // Account email address
$password = $_ENV["DOCUSIGN_LOGIN_PASSWORD"] or "***";      // Account password
$integrator_key = $_ENV["DOCUSIGN_INTEGRATOR_KEY"] or "***";  // Integrator Key (found on the Preferences -> API page)

$recipientName = '';
$recipientEmail = '';

$templateId = '';
$templateRoleName = 'signer1'; // the role name on your template

$apiEnvironment = 'demo';

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
            var_dump('accountId',$accountId);
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
  // Step 2: Create Envelope from Template (no document required locally) and Request Signature 
  /////////////////////////////////////////////////////////////////////////////////////
  public function signatureRequestFromTemplate(
    $apiClient, 
    $accountId, 
    $recipient, 
    $templateId,
    $templateRole,
    $status = "sent")
  {

    $envelope_summary = null;

    $envelope_definition = new DocuSign\eSign\Model\EnvelopeDefinition();
    $envelope_definition->setEmailSubject("[DocuSign PHP SDK] - Please sign this doc sent via Template");
    $envelope_definition->setTemplateId($templateId);

    $tRole = new DocuSign\eSign\Model\TemplateRole();
    $tRole->setName($recipient->name);
    $tRole->setEmail($recipient->email);
    $tRole->setRoleName($templateRole);

    $templateRolesList = array();
    array_push($templateRolesList, $tRole);

      // assign template role(s) to the envelope
      $envelope_definition->setTemplateRoles($templateRolesList);

    // set envelope status to "sent" to immediately send the signature request
    $envelope_definition->setStatus($status);

    $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi($apiClient);
    $envelope_summary = $envelopeApi->createEnvelope($accountId, $envelope_definition);
    if(!empty($envelope_summary))
    {
      if($status == "created")
      {
        var_dump('Created Envelope');
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

// Create Envelope from Template and send
$sample->signatureRequestFromTemplate($sample->apiClient, $sample->accountId, $recipient, $templateId, $templateRoleName);

?>