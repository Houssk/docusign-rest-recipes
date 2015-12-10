<?php

// Input your info:
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
echo "accountId = " . $accountId . "\nbaseUrl = " . $baseUrl . "\n";

/////////////////////////////////////////////////////////////////////////////////////////////////
// STEP 2 - Get the Embedded Console view
/////////////////////////////////////////////////////////////////////////////////////////////////
$data = array("accountId" => $accountId);                                                                    
$data_string = json_encode($data);                                                                                   
$curl = curl_init($baseUrl . "/views/console" );
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
echo "Embedded URL is: \n\n" . $url . "\n\nNavigate to this URL to open the DocuSign Member Console...\n"; 

