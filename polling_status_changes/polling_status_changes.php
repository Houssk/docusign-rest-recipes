<?php

// Input your info here:
$email = "***";			// your account email
$password = "***";		// your account password
$integratorKey = "***";		// your account integrator key, found on (Preferences -> API page)

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
echo "\naccountId = " . $accountId . "\nbaseUrl = " . $baseUrl . "\n";

/////////////////////////////////////////////////////////////////////////////////////////////////
// STEP 2 - status retrieval using filters
/////////////////////////////////////////////////////////////////////////////////////////////////
echo "Performing status retrieval using filters...\n";
date_default_timezone_set('America/Los_Angeles');
$from_date  = date("m") . "%2F" . (date("d")-7) . "%2F". date("Y");

$curl = curl_init($baseUrl . "/envelopes?from_date=$from_date&status=created,sent,delivered,signed,completed" );
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(                                                                          
	'Accept: application/json',
	"X-DocuSign-Authentication: $header" )                                                                       
);

$json_response = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if ( $status != 200 ) {
	echo "error calling webservice, status is:" . $status . "\nerror text is --> ";
	print_r($json_response); echo "\n";
	exit(-1);
}

$response = json_decode($json_response, true);

//--- display results
echo "Received " . count( $response["envelopes"]) . " envelopes\n";
foreach ($response["envelopes"] as $envelope) {
	echo "envelope: " . $envelope["envelopeId"] . " " . $envelope["status"] . " " . $envelope["statusChangedDateTime"] . "\n";
}    

