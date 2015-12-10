<?php

  // Input your info here:
$email = "***";			// your account email
$password = "***";		// your account password
$integratorKey = "***";		// your account integrator key, found on (Preferences -> API page)

// copy the envelopeId from an existing envelope in your account that you want
// to download documents from
$envelopeId = "***";

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
// STEP 2 - Get document information
/////////////////////////////////////////////////////////////////////////////////////////////////                                                                                  
$curl = curl_init($baseUrl . "/envelopes/" . $envelopeId . "/documents" );
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(                                                                          
	"X-DocuSign-Authentication: $header" )                                                                       
);

$json_response = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
if ( $status != 200 ) {
	echo "error calling webservice, status is:" . $status;
	exit(-1);
}

$response = json_decode($json_response, true);
curl_close($curl);

//--- display results
echo "Envelope has following document(s) information...\n";
print_r($response);	echo "\n";

/////////////////////////////////////////////////////////////////////////////////////////////////
// STEP 3 - Download the envelope's documents
/////////////////////////////////////////////////////////////////////////////////////////////////
foreach( $response["envelopeDocuments"] as $document ) {
	$docUri = $document["uri"];
	
	$curl = curl_init($baseUrl . $docUri );
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);  
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(                                                                          
		"X-DocuSign-Authentication: $header" )                                                                       
	);
	
	$data = curl_exec($curl);
	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	if ( $status != 200 ) {
		echo "error calling webservice, status is:" . $status;
		exit(-1);
	}

	file_put_contents($envelopeId . "-" . $document["name"], $data);
	curl_close($curl);
	
	//*** Documents should now be downloaded in the same folder as you ran this program
}

//--- display results
echo "Envelope document(s) have been downloaded, check your local directory.\n";

