<?php
    
	// Input your info here:
	$email = "***";			// your account email
	$password = "***";		// your account password
	$integratorKey = "***";		// your account integrator key, found on (Preferences -> API page)

	// copy the envelopeId from an existing envelope in your account that you want to query:
	$envelopeId = 'cbe279f6-199c-.................';
	
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
	// STEP 2 - Get envelope information
	/////////////////////////////////////////////////////////////////////////////////////////////////
	$data_string = json_encode($data);                                                                                   
	$curl = curl_init($baseUrl . "/envelopes/" . $envelopeId . "/recipients" );
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(                                                                          
		"X-DocuSign-Authentication: $header" )                                                                       
	);
	
	$json_response = curl_exec($curl);
	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	if ( $status != 200 ) {
		echo "error calling webservice, status is:" . $status . "\nError text --> ";
		print_r($json_response); echo "\n";
		exit(-1);
	}
	
	$response = json_decode($json_response, true);
	
	//--- display results
	echo "First signer = " . $response["signers"][0]["name"] . "\n";
	echo "First Signer's email = " . $response["signers"][0]["email"] . "\n";
	echo "Signing status = " . $response["signers"][0]["status"] . "\n\n";
?>