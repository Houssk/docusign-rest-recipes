<?php

// Request Signature on Multiple Documents (PHP)

// To run this sample
//  1. Copy the file to your local machine and give it a .php extension (app.php)
//  2. Change "***" to appropriate values
//  3. Install Composer (PHP package manager: https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
//  4. Install DocuSign's PHP SDK using composer:
//     composer require docusign/esign-client
//  5. Ensure that 'documentFileName1' and 'documentFileName2' variables reference files in the same directory
//  6. Execute
//     php app.php
//

require_once('vendor/docusign/esign-client/autoload.php');

$username = "***";          // Account email address
$password = "***";          // Account password
$integrator_key = "***";    // Integrator Key (found on the Preferences -> API page)

$signerName = '***';
$signerEmail = '***';

$ccName = '***';
$ccEmail = '***';

$documentFileName1 = '/NDA.pdf';
$documentFileName2 = '/House.pdf';

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
    public function signatureRequestOnMultipleDocuments($apiClient, 
        $accountId, 
        $fileToSign1,
        $fileToSign2,
        $signerRecipient, 
        $ccRecipient, 
        $status = "sent")
    {

        $envelopeApi = new DocuSign\eSign\Api\EnvelopesApi($apiClient);
        $envelope_summary = null;

        $envelope_definition = new DocuSign\eSign\Model\EnvelopeDefinition();
        $envelope_definition->setEmailSubject("[DocuSign PHP SDK] - Please sign these docs");

        // Add documents to the envelope
        $doc1 = new DocuSign\eSign\Model\Document();
        $doc1->setDocumentBase64(base64_encode(file_get_contents(__DIR__ . $fileToSign1)));
        $doc1->setName('NDA-Request.pdf');
        $doc1->setDocumentId("1");

        $doc2 = new DocuSign\eSign\Model\Document();
        $doc2->setDocumentBase64(base64_encode(file_get_contents(__DIR__ . $fileToSign2)));
        $doc2->setName('House-Doc.pdf');
        $doc2->setDocumentId("2");

        $envelope_definition->setDocuments(array($doc1,$doc2));

        // Add Recipients (Signer and CC)
        $signer = new \DocuSign\eSign\Model\Signer();
        $signer->setEmail($signerRecipient->email);
        $signer->setName($signerRecipient->name);
        $signer->setRecipientId("1");

        $cc = new \DocuSign\eSign\Model\CarbonCopy();
        $cc->setEmail($ccRecipient->email);
        $cc->setName($ccRecipient->name);
        $cc->setRecipientId("2");
    
        // Add signing tags and additional (comany name, etc.) tags
        // - using "anchor" (soon to be called AutoPlace) tags 

        // Create |SignHere| tabs
        $signHere1 = new \DocuSign\eSign\Model\SignHere();
        $signHere1->setName('Please Sign Here');
        $signHere1->setDocumentId('1');
        $signHere1->setAnchorString('signer1sig');
        // $signHere1->setPageNumber('1'); // PageNumber not necessary for Anchor Text!
        $signHere1->setRecipientId('1');
        $signHere1->setAnchorXOffset('0');
        $signHere1->setAnchorYOffset('0');
        $signHere1->setAnchorUnits('mms');
        $signHere1->setOptional('false');
        $signHere1->setTabLabel('signer1sig');

        $signHere2 = new \DocuSign\eSign\Model\SignHere();
        $signHere2->setName('Please Sign Here');
        $signHere2->setDocumentId('2');
        $signHere2->setPageNumber('1');
        $signHere2->setRecipientId('1');
        $signHere2->setXPosition('89');
        $signHere2->setYPosition('40');
        $signHere2->setAnchorUnits('mms');
        $signHere2->setOptional('false');
        $signHere2->setTabLabel('signer1_doc2');


        $textTab1 = new \DocuSign\eSign\Model\Text();
        $textTab1->setName('Company');
        $textTab1->setDocumentId('1');
        $textTab1->setAnchorString('signer1company');
        $textTab1->setRecipientId('1');
        $textTab1->setAnchorXOffset('0');
        $textTab1->setAnchorYOffset('-8');
        $textTab1->setAnchorUnits('mms');
        $textTab1->setRequired('true');
        $textTab1->setTabLabel('Company');


        $dateSigned1 = new \DocuSign\eSign\Model\DateSigned();
        $dateSigned1->setName('Date Signed');
        $dateSigned1->setAnchorString('signer1date');
        $dateSigned1->setDocumentId('1');
        $dateSigned1->setRecipientId('1');
        $dateSigned1->setAnchorXOffset('0');
        $dateSigned1->setAnchorYOffset('-6');
        $dateSigned1->setFontSize('Size12');
        $dateSigned1->setTabLabel('date_signed');

        $dateSigned2 = new \DocuSign\eSign\Model\DateSigned();
        $dateSigned2->setName('Date Signed');
        $dateSigned2->setDocumentId('2');
        $dateSigned2->setPageNumber('1');
        $dateSigned2->setRecipientId('1');
        $dateSigned2->setXPosition('89');
        $dateSigned2->setYPosition('100');
        $dateSigned2->setFontSize('Size12');
        $dateSigned2->setTabLabel('doc2_date_signed');


        // can have multiple tabs, so need to add to envelope as a single element list
        $signHereTabs = array();
        array_push($signHereTabs, $signHere1);
        array_push($signHereTabs, $signHere2);

        $textTabs = array();
        array_push($textTabs, $textTab1);

        $dateSignedTabs = array();
        array_push($dateSignedTabs, $dateSigned1);
        array_push($dateSignedTabs, $dateSigned2);

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
        $recipients->setCarbonCopies(array($cc));

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
$cc = new DocuSignRecipient($ccName, $ccEmail);

// Login
$login = $sample->login($username, $password, $integrator_key, $apiEnvironment);
if($login == false){
    return;
}

// Create and Send Envelope
$sample->signatureRequestOnMultipleDocuments($sample->apiClient, $sample->accountId, $documentFileName1, $documentFileName2, $signer, $cc);

?>