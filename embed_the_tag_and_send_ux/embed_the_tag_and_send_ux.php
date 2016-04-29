<?php

// Embed the Tag and Send (User Experience) (PHP)

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

$username = "***";          // Account email address
$password = "***";          // Account password
$integrator_key = "***";    // Integrator Key (found on the Preferences -> API page)

$apiEnvironment = 'demo';

$recipientName = '***';
$recipientEmail = '***';

$documentFileName = '/blank.pdf';

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
    // Step 2: Create Envelope
    /////////////////////////////////////////////////////////////////////////////////////
    public function createEnvelope(
        $apiClient, 
        $accountId, 
        $documentFileName, 
        $recipient, 
        $status = "created")
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

        
            // send the envelope by setting |status| to "sent". To save as a draft set to "created"
            // - note that the envelope will only be 'sent' when it reaches the DocuSign server with the 'sent' status (not in the following call)
        $envelope_definition->setStatus($status);
        $envelope_definition->setRecipients($recipients);
        $envelope_definition->setDocuments(array($document));
        $options = new \DocuSign\eSign\Api\EnvelopesApi\CreateEnvelopeOptions();
        $options->setCdseMode(null);
        $options->setMergeRolesOnDraft(null);
        $envelope_summary = $envelopeApi->createEnvelope($accountId, $envelope_definition, $options);
        if(!empty($envelope_summary))
        {
            $this->envelopeId = $envelope_summary['envelope_id'];
            if($status == "created")
            {
                var_dump('created Envelope!!!');
            }
            else
            {
                var_dump('created and SENT Envelope:', $envelope_summary);
            }
        }

        return;
    }

    //////////////////////////////////////////////////////////////////////
    // Step 3 - Get the Embedded Sending View
    //////////////////////////////////////////////////////////////////////
    function createSenderView(
        $apiClient,
        $accountId,
        $envelopeId,
        $recipient) 
    {

        // instantiate a new EnvelopesApi object
        $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi($apiClient);

        // set the url where you want the recipient to go once they are done signing
        // - this can be used by your app to watch the URL and detect when signing has completed (or was canceled) 
        $returnUrl = new \DocuSign\eSign\Model\RecipientViewRequest();
        $returnUrl->setReturnUrl('https://www.docusign.com/devcenter');
        $returnUrl->setAuthenticationMethod('email');

        // recipient information must match embedded recipient info we provided in step #2
        $returnUrl->setEmail($recipient->email);
        $returnUrl->setUserName($recipient->name);
        $returnUrl->setRecipientId('1');

        // call the CreateRecipientView API
        $recipientView = $envelopeApi->createSenderView($accountId, $envelopeId, $returnUrl);
        if(!empty($recipientView)){
            print_r("\n\nVisit Sending URL: " . $recipientView->getUrl() . "\n\n");
        }

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
$login = $sample->login($username, $password, $integrator_key, $apiEnvironment);
if($login == false){
    return;
}

// Create and Send Envelope
$sample->createEnvelope($sample->apiClient, $sample->accountId, $documentFileName, $recipient);

// Request for embedded signing (getting a URL) 
$sample->createSenderView($sample->apiClient, $sample->accountId, $sample->envelopeId, $recipient);

?>