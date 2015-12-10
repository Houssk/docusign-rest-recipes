<?php
    
// Input your info:
$email = "***";			// your account email
$password = "***";		// your account password
$integratorKey = "***";		// your account integrator key, found on (Preferences -> API page)
$recipientName = "***";		// provide a recipient (signer) name
$templateId = "***";		// provide a valid templateId of a template in your account
$templateRoleName = "***";	// use same role name that exists on the template in the console
$clientUserId = "***";		// to add an embedded recipient you must set their clientUserId property in addition to
				// the recipient name and email.  Whatever you set the clientUserId to you must use the same
				// value when requesting the signing URL

// construct the authentication header:
$header = "<DocuSignCredentials><Username>" . $email . "</Username><Password>" . $password . "</Password><IntegratorKey>" . $integratorKey . "</IntegratorKey></DocuSignCredentials>";

/////////////////////////////////////////////////////////////////////////////////////////////////
// STEP 1 - Login (retrieves baseUrl and accountId)
/////////////////////////////////////////////////////////////////////////////////////////////////
$url = "https://demo.docusign.net/restapi/v2/login_information";
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_HEADER, false);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array("X-DocuSign-Authentication: $header"));

$json_response = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

if ( $status != 200 ) {
	echo "error calling webservice, status is:" . $status;
	exit(-1);
}

$response = json_decode($json_response, true);
$accountId = $response["loginAccounts"][0]["accountId"];
$baseUrl = $response["loginAccounts"][0]["baseUrl"];
curl_close($curl);

//--- display results
echo "accountId = " . $accountId . "\nbaseUrl = " . $baseUrl . "\n";
    
/////////////////////////////////////////////////////////////////////////////////////////////////
// STEP 2 - Create an envelope with an Embedded recipient (uses the clientUserId property)
/////////////////////////////////////////////////////////////////////////////////////////////////
$data = array("accountId" => $accountId, 
	"emailSubject" => "DocuSign API - Embedded Signing Example",
	"templateId" => $templateId, 
	"templateRoles" => array(
		array( "roleName" => $templateRoleName, "email" => $email, "name" => $recipientName, "clientUserId" => $clientUserId )),
	"status" => "sent");                                                                    

$data_string = json_encode($data);  
$curl = curl_init($baseUrl . "/envelopes" );
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);                                                                  
curl_setopt($curl, CURLOPT_HTTPHEADER, array(                                                                          
	'Content-Type: application/json',                                                                                
	'Content-Length: ' . strlen($data_string),
	"X-DocuSign-Authentication: $header" )                                                                       
);

$json_response = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if ( $status != 201 ) {
	echo "error calling webservice, status is:" . $status . "\nerror text is --> ";
	print_r($json_response); echo "\n";
	exit(-1);
}

$response = json_decode($json_response, true);
$envelopeId = $response["envelopeId"];
curl_close($curl);

//--- display results	
echo "Envelope created! Envelope ID: " . $envelopeId . "\n"; 

/////////////////////////////////////////////////////////////////////////////////////////////////
// STEP 3 - Get the Embedded Signing View 
/////////////////////////////////////////////////////////////////////////////////////////////////
$data = array("returnUrl" => "http://www.docusign.com/devcenter",
	"authenticationMethod" => "None", "email" => $email, 
	"userName" => $recipientName, "clientUserId" => $clientUserId
);                                                                    

$data_string = json_encode($data);    
$curl = curl_init($baseUrl . "/envelopes/$envelopeId/views/recipient" );
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);                                                                  
curl_setopt($curl, CURLOPT_HTTPHEADER, array(                                                                          
	'Content-Type: application/json',                                                                                
	'Content-Length: ' . strlen($data_string),
	"X-DocuSign-Authentication: $header" )                                                                       
);

$json_response = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if ( $status != 201 ) {
	echo "error calling webservice, status is:" . $status . "\nerror text is --> ";
	print_r($json_response); echo "\n";
	exit(-1);
}

$response = json_decode($json_response, true);
$url = $response["url"];

//--- display results
echo "Embedded URL is: \n\n" . $url . "\n\nNavigate to this URL to start the embedded signing view of the envelope\n"; 

