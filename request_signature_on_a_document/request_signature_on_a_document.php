<?php
    
function request_signature_on_a_document(
	$recipientEmail, // signer's email
	$recipientName,  // signer's name -- first name last name
    $documentName    // including directory information
	) {
	// RETURNS
	// Associative array with elements:
	//	ok -- true for success
	//  errMsg -- only if !ok
	//  The following are valid only if ok:
	//  envelopeId
	//	accountId
	//	baseUrl

	
	// Set Authentication information
	// Set via a config file or just set here using constants.
	$email = DS_ACCOUNT_EMAIL;	// your account email.
	$password = DS_ACCOUNT_PW;		// your account password
	$integratorKey = DS_INTEGRATOR"integrator_key";	// your account integrator key, found on (Preferences -> API page)

	// api service point
	$url = "https://demo.docusign.net/restapi/v2/login_information"; // change for production

	///////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////
	//
	// Start...

	// construct the authentication header:
	$header = "<DocuSignCredentials><Username>" . $email . "</Username><Password>" . $password . "</Password><IntegratorKey>" . $integratorKey . "</IntegratorKey></DocuSignCredentials>";

	/////////////////////////////////////////////////////////////////////////////////////////////////
	// STEP 1 - Login (to retrieve baseUrl and accountId)
	/////////////////////////////////////////////////////////////////////////////////////////////////
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array("X-DocuSign-Authentication: $header"));

	$json_response = curl_exec($curl);
	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

	if ( $status != 200 ) {
		return (['ok': false, 'errMsg' : "Error calling DocuSign, status is: " . $status]);
	}

	$response = json_decode($json_response, true);
	$accountId = $response["loginAccounts"][0]["accountId"];
	$baseUrl = $response["loginAccounts"][0]["baseUrl"];
	curl_close($curl);

	/////////////////////////////////////////////////////////////////////////////////////////////////
	// STEP 2 - Create and send envelope with one recipient, one tab, and one document
	/////////////////////////////////////////////////////////////////////////////////////////////////

	// the following envelope request body will place 1 signature tab on the document, located
	// 100 pixels to the right and 100 pixels down from the top left of the document
	$data = 
		array (
			"emailSubject" => "DocuSign API - Please sign " . $documentName,
			"documents" => array( 
				array("documentId" => "1", "name" => $documentName)
				),
			"recipients" => array( 
				"signers" => array(
					array(
						"email" => $recipientEmail,
						"name" => $recipientName,
						"recipientId" => "1",
						"tabs" => array(
							"signHereTabs" => array(
								array(
									"xPosition" => "100",
									"yPosition" => "100",
									"documentId" => "1",
									"pageNumber" => "1"
								)
							)
						)
					)
				)
			),
		"status" => "sent"
	);
	$data_string = json_encode($data);  
	$file_contents = file_get_contents($documentName);

	// Create a multi-part request. First the form data, then the file content
	$requestBody = 
		 "\r\n"
		."\r\n"
		."--myboundary\r\n"
		."Content-Type: application/json\r\n"
		."Content-Disposition: form-data\r\n"
		."\r\n"
		."$data_string\r\n"
		."--myboundary\r\n"
		."Content-Type:application/pdf\r\n"
		."Content-Disposition: file; filename=\"$documentName\"; documentid=1 \r\n"
		."\r\n"
		."$file_contents\r\n"
		."--myboundary--\r\n"
		."\r\n";

	// Send to the /envelopes end point, which is relative to the baseUrl received above. 
	$curl = curl_init($baseUrl . "/envelopes" );
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $requestBody);                                                                  
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(                                                                          
		'Content-Type: multipart/form-data;boundary=myboundary',
		'Content-Length: ' . strlen($requestBody),
		"X-DocuSign-Authentication: $header" )                                                                       
	);
	$json_response = curl_exec($curl); // Do it!

	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	if ( $status != 201 ) {
		echo "Error calling DocuSign, status is:" . $status . "\nerror text: ";
		print_r($json_response); echo "\n";
		exit(-1);
	}

	$response = json_decode($json_response, true);
	$envelopeId = $response["envelopeId"];
	
	return [
	            'ok' : true,
	    'envelopeId' : $envelopeId,
	     'accountId' : $accountId,
	       'baseUrl' : $baseUrl
    ];
	
}
