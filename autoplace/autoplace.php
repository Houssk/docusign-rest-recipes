<?php

// Using AutoPlace (AnchorText) tabs on an Envelope (PHP)

// To run this sample
//  1. Copy the file to your local machine and give it a .php extension (app.php)
//  2. Change "***" to appropriate values
//  3. Install Composer (PHP package manager: https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
//  4. Install DocuSign's PHP SDK using composer:
//     composer require docusign/esign-client
//  5. Ensure that the 'documentFileName' variable references a file in the same directory
//  6. Execute
//     php app.php
//

require_once('vendor/docusign/esign-client/autoload.php');

$username = "***";          // Account email address
$password = "***";          // Account password
$integrator_key = "***";    // Integrator Key (found on the Preferences -> API page)

$signerName = '***';
$signerEmail = '***';

$documentFileName = '/NDA.pdf';

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
    // Step 2: Create Envelope and Request Signature 
    /////////////////////////////////////////////////////////////////////////////////////
    public function signatureRequestWithAutoplace($apiClient, 
        $accountId, 
        $fileToSign,
        $signerRecipient, 
        $status = "sent")
    {

        $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi($apiClient);
        $envelope_summary = null;

        $envelope_definition = new DocuSign\eSign\Model\EnvelopeDefinition();
        $envelope_definition->setEmailSubject("[DocuSign PHP SDK] - Please sign this doc");

        // Add documents to the envelope
        $document = new DocuSign\eSign\Model\Document();
        $document->setDocumentBase64(base64_encode(file_get_contents(__DIR__ . $fileToSign)));
        $document->setName('NDA-Request.pdf');
        $document->setDocumentId("1");

        $envelope_definition->setDocuments(array($document));

        // Add Recipients (Signer and CC)
        $signer = new \DocuSign\eSign\Model\Signer();
        $signer->setEmail($signerRecipient->email);
        $signer->setName($signerRecipient->name);
        $signer->setRecipientId("1");
    
        // Add signing tags and additional (comany name, etc.) tags
        // - using "anchor" (soon to be called AutoPlace) tags 

        // Create a |SignHere| tab
        $signHere = new \DocuSign\eSign\Model\SignHere();
        $signHere->setName('Please Sign Here');
        $signHere->setDocumentId('1');
        $signHere->setAnchorString('signer1sig');
        // $signHere->setPageNumber('1'); // PageNumber not necessary for Anchor Text!
        $signHere->setRecipientId('1');
        $signHere->setAnchorXOffset('0');
        $signHere->setAnchorYOffset('0');
        $signHere->setAnchorUnits('mms');
        $signHere->setOptional('false');
        $signHere->setTabLabel('signer1sig');


        $textTab1 = new \DocuSign\eSign\Model\Text();
        $textTab1->setName('Company');
        $textTab1->setDocumentId('1');
        $textTab1->setAnchorString('Company:');
        $textTab1->setRecipientId('1');
        $textTab1->setAnchorXOffset('16');
        $textTab1->setAnchorYOffset('-2');
        $textTab1->setAnchorUnits('mms');
        $textTab1->setRequired('true');
        $textTab1->setTabLabel('Company');


        $textTab2 = new \DocuSign\eSign\Model\Text();
        $textTab2->setName('Name:');
        $textTab2->setDocumentId('1');
        $textTab2->setAnchorString('Name:');
        $textTab2->setRecipientId('1');
        $textTab2->setAnchorXOffset('16');
        $textTab2->setAnchorYOffset('-2');
        $textTab2->setAnchorUnits('mms');
        $textTab2->setRequired('true');
        $textTab2->setTabLabel('Name');


        $dateSigned = new \DocuSign\eSign\Model\DateSigned();
        $dateSigned->setName('Date Signed');
        $dateSigned->setAnchorString('Date:');
        $dateSigned->setDocumentId('1');
        $dateSigned->setRecipientId('1');
        $dateSigned->setAnchorXOffset('16');
        $dateSigned->setAnchorYOffset('-2');
        $dateSigned->setFontSize('Size12');
        $dateSigned->setTabLabel('date_signed');


        // can have multiple tabs, so need to add to envelope as a single element list
        $signHereTabs = array();
        array_push($signHereTabs, $signHere);

        $textTabs = array();
        array_push($textTabs, $textTab1);
        array_push($textTabs, $textTab2);

        $dateSignedTabs = array();
        array_push($dateSignedTabs, $dateSigned);

        // add tabs
        $tabs = new DocuSign\eSign\Model\Tabs();
        $tabs->setSignHereTabs($signHereTabs);
        // $tabs->setFullNameTabs($fullNameTabs);
        $tabs->setTextTabs($textTabs);
        $tabs->setDateSignedTabs($dateSignedTabs);

        $signer->setTabs($tabs);

        // add recipients to the envelope (signer and carbon copy)
        $recipients = new DocuSign\eSign\Model\Recipients();
        $recipients->setSigners(array($signer));

        $envelope_definition->setRecipients($recipients);

        // set envelope status to "sent" to immediately send the signature request
        $envelope_definition->setStatus($status);

        $options = new \DocuSign\eSign\Api\EnvelopesApi\CreateEnvelopeOptions();
        $options->setCdseMode(null);
        $options->setMergeRolesOnDraft(null);

        $envelope_summary = $envelopeApi->createEnvelope($accountId, $envelope_definition);
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
$signer = new DocuSignRecipient($signerName, $signerEmail);

// Login
$login = $sample->login($username, $password, $integrator_key, $apiEnvironment);
if($login == false){
    return;
}

// Create and Send Envelope
$sample->signatureRequestWithAutoplace($sample->apiClient, $sample->accountId, $documentFileName, $signer);

?>