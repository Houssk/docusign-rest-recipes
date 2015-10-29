// DocuSign API Walkthrough 04 in Java - Request Signature on Document
import java.io.*;
import java.net.URL;
import java.net.HttpURLConnection;

import javax.xml.transform.*;
import javax.xml.transform.stream.*;
import javax.xml.xpath.*;
import org.xml.sax.InputSource;

public class requestSignatureDocument
{	
	public static void main(String[] args) throws Exception
	{	
		//------------------------------------------------------------------------------------
		// ENTER VALUES FOR THE FOLLOWING 7 VARIABLES:
		//------------------------------------------------------------------------------------
		String integratorKey = "***";			// integrator key (found on Preferences -> API page)	
		String username = "***";			// account email (or your API userId)
		String password = "***";			// account password
		String recipientName = "***";			// recipient (signer) name
		String recipientEmail = "***";			// recipient (signer) email	
		String documentName = "***";			// copy file with same name into project directory!
		String docContentType = "application/pdf";	// content type for above document (defaults to pdf)
		//------------------------------------------------------------------------------------
		
		// construct the DocuSign authentication header
		String authenticationHeader = 
					"<DocuSignCredentials>" + 
						"<Username>" + username + "</Username>" +
						"<Password>" + password + "</Password>" + 
						"<IntegratorKey>" + integratorKey + "</IntegratorKey>" + 
					"</DocuSignCredentials>";
		
		// additional variable declarations
		String baseURL = "";			// we will retrieve this through the Login API call
		String accountId = "";			// we will retrieve this through the Login API call
		String envelopeId = "";			// generated from signature request API call
		String url = "";			// end-point for each api call
		String body = "";			// request body
		String response = "";			// response body
		int status;				// response status
		HttpURLConnection conn = null;		// connection object used for each request
		
		//============================================================================
		// STEP 1 - Make the Login API call to retrieve your baseUrl and accountId
		//============================================================================
		
		url = "https://demo.docusign.net/restapi/v2/login_information";
		body = "";	// no request body for the login call
		
		// create connection object, set request method, add request headers
		conn = InitializeRequest(url, "GET", body, authenticationHeader);
		
		// send the request
		System.out.println("STEP 1:  Sending Login request...\n");
		status = conn.getResponseCode();
		if( status != 200 )	// 200 = OK
		{
			errorParse(conn, status);
			return;
		}
		
		// obtain baseUrl and accountId values from response body 
		response = getResponseBody(conn);
		baseURL = parseXMLBody(response, "baseUrl");
		accountId = parseXMLBody(response, "accountId");
		System.out.println("-- Login response --\n\n" + prettyFormat(response, 2) + "\n");
		
		//============================================================================
		// STEP 2 - Signature Request on Document API Call
		//============================================================================
		
		url = baseURL + "/envelopes";	// append "/envelopes" to baseUrl for signature request call
		
		// this example uses XML formatted requests, JSON format is also accepted
		// following body will place one signature tab 100 pixels right and 100 down from top left corner of document
		body = "<envelopeDefinition xmlns=\"http://www.docusign.com/restapi\">" +  
					"<emailSubject>API Call for adding signature request to document and sending</emailSubject>" + 
					"<status>sent</status>" + 
					// add document(s)
					"<documents>" +
						"<document>" + 
							"<documentId>1</documentId>" + 
							"<name>" + documentName + "</name>" + 
						"</document>" + 
					"</documents>" +
					// add recipient(s)
					"<recipients>" + 
						"<signers>" + 
							"<signer>" + 
								"<recipientId>1</recipientId>" + 	
								"<name>" + recipientName + "</name>" +
								"<email>" + recipientEmail + "</email>" +								
								"<tabs>" + 
									"<signHereTabs>" + 
									"<signHere>" + 
									"<xPosition>100</xPosition>" + 
									"<yPosition>100</yPosition>" + 
									"<documentId>1</documentId>" + 
									"<pageNumber>1</pageNumber>" + 
									"</signHere>" +
									"</signHereTabs>" + 
								"</tabs>" + 
							"</signer>" + 
						"</signers>" + 
					"</recipients>" +  
				"</envelopeDefinition>";
		
		// re-use connection object for second request...
		conn = InitializeRequest(url, "POST", body, authenticationHeader);
		
		// read document content into byte array
		File file = new File("./" + documentName);
		InputStream inputStream = new FileInputStream(file); 
		byte[] bytes = new byte[(int) file.length()];
		inputStream.read(bytes);
		inputStream.close();
		
		// start constructing the multipart/form-data request...
		String requestBody = "\r\n\r\n--BOUNDARY\r\n" + 
				"Content-Type: application/xml\r\n" + 
				"Content-Disposition: form-data\r\n" + 
				"\r\n" + 
				body + "\r\n\r\n--BOUNDARY\r\n" + 	// our xml formatted request body
				"Content-Type: " + docContentType + "\r\n" + 
				"Content-Disposition: file; filename=\"" + documentName + "\"; documentid=1\r\n" + 
				"\r\n";
			// we break this up into two string since the PDF doc bytes go here and are not in string format.
			// see further below where we write to the outputstream...
		String reqBody2 = "\r\n" + "--BOUNDARY--\r\n\r\n";
		
		// write the body of the request...
		DataOutputStream dos = new DataOutputStream( conn.getOutputStream() );
		dos.writeBytes(requestBody.toString()); 
		dos.write(bytes);
		dos.writeBytes(reqBody2.toString()); 
		dos.flush(); dos.close();
		
		System.out.println("STEP 2:  Sending signature request on document...\n");
		
		status = conn.getResponseCode(); // triggers the request
		if( status != 201 )	// 201 = Created
		{
			errorParse(conn, status);
			return;
		}
		
		// display the response body 
		response = getResponseBody(conn);
		envelopeId = parseXMLBody(response, "envelopeId");
		System.out.println("-- Signature Request response --\n\n" + prettyFormat(response, 2));
		System.out.println("Signature request has been sent to " + recipientEmail + "!\nEnvelopeId is:  " + envelopeId  + "\n");
	} //end main()
	
	//***********************************************************************************************
	//***********************************************************************************************
	// --- HELPER FUNCTIONS ---
	//***********************************************************************************************
	//***********************************************************************************************
	public static HttpURLConnection InitializeRequest(String url, String method, String body, String httpAuthHeader) {
		HttpURLConnection conn = null;
		try {
			conn = (HttpURLConnection)new URL(url).openConnection();
			
			conn.setRequestMethod(method);
			conn.setRequestProperty("X-DocuSign-Authentication", httpAuthHeader);
			conn.setRequestProperty("Accept", "application/xml");
			if (method.equalsIgnoreCase("POST"))
			{
				conn.setRequestProperty("Content-Type", "multipart/form-data; boundary=BOUNDARY");
				conn.setDoOutput(true);
			}
			else {
				conn.setRequestProperty("Content-Type", "application/xml");
			}
			return conn;
			
		} catch (Exception e) {
	        	throw new RuntimeException(e); // simple exception handling, please review it
	    }
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////
	public static String parseXMLBody(String body, String searchToken) {
		String xPathExpression;
		try {
	        	// we use xPath to parse the XML formatted response body
			xPathExpression = String.format("//*[1]/*[local-name()='%s']", searchToken);
 			XPath xPath = XPathFactory.newInstance().newXPath();
 			return (xPath.evaluate(xPathExpression, new InputSource(new StringReader(body))));
		} catch (Exception e) {
	        	throw new RuntimeException(e); // simple exception handling, please review it
	    }
	}	
	
	///////////////////////////////////////////////////////////////////////////////////////////////
	public static String getResponseBody(HttpURLConnection conn) {
		BufferedReader br = null;
		StringBuilder body = null;
		String line = "";
		try {
	        // we use xPath to get the baseUrl and accountId from the XML response body
 			br = new BufferedReader(new InputStreamReader( conn.getInputStream()));
 			body = new StringBuilder();
 			while ( (line = br.readLine()) != null)
 				body.append(line);
 			return body.toString();
		} catch (Exception e) {
	        	throw new RuntimeException(e); // simple exception handling, please review it
	    }
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////
	public static void errorParse(HttpURLConnection conn, int status) { 
		BufferedReader br;
		String line;
		StringBuilder responseError;
		try {
			System.out.print("API call failed, status returned was: " + status);
			InputStreamReader isr = new InputStreamReader( conn.getErrorStream() );
			br = new BufferedReader(isr);
			responseError = new StringBuilder();
			line = null;
			while ( (line = br.readLine()) != null)
				responseError.append(line);
			System.out.println("\nError description:  \n" + prettyFormat(responseError.toString(), 2));
			return;
		}
		catch (Exception e) {
			throw new RuntimeException(e); // simple exception handling, please review it
		}
	}
	
	///////////////////////////////////////////////////////////////////////////////////////////////
	public static String prettyFormat(String input, int indent) { 
		try {
	    		Source xmlInput = new StreamSource(new StringReader(input));
	        	StringWriter stringWriter = new StringWriter();
	        	StreamResult xmlOutput = new StreamResult(stringWriter);
	        	TransformerFactory transformerFactory = TransformerFactory.newInstance();
	        	transformerFactory.setAttribute("indent-number", indent);
	        	Transformer transformer = transformerFactory.newTransformer(); 
	        	transformer.setOutputProperty(OutputKeys.INDENT, "yes");
	        	transformer.transform(xmlInput, xmlOutput);
	        	return xmlOutput.getWriter().toString();
	    	} catch (Exception e) {
	        	throw new RuntimeException(e); // simple exception handling, please review it
	    }
	}
} // end class