// DocuSign API Walkthrough 03 in Java - Get Envelope Recipient Status
import java.io.*;
import java.net.URL;
import java.net.HttpURLConnection;

import javax.xml.transform.*;
import javax.xml.transform.stream.*;
import javax.xml.xpath.*;
import org.xml.sax.InputSource;

public class getRecipientStatus
{	
	public static void main(String[] args) throws Exception
	{	
		//------------------------------------------------------------------------------------
		// ENTER VALUES FOR THE FOLLOWING 4 VARIABLES:
		//------------------------------------------------------------------------------------
		String integratorKey = "***";		// integrator key (found on Preferences -> API page)	
		String username = "***";		// account email (or your API userId)
		String password = "***";		// account password
		String envelopeId = "***";		// enter envelopeId of an envelope in your account	
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
		String url = "";			// end-point for each api call
		String body = "";			// request body
		String response = "";			// response body
		int status;				// response status
		HttpURLConnection conn = null;		// connection object used for each request
		
		//============================================================================
		// STEP 1 - Make the Login API Call to retrieve your baseUrl and accountId
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
		// STEP 2 - Get Envelope Recipient Status API call
		//============================================================================
		
		// two optional query parameters available for this call
		String qParam1 = "?include_tabs=true"; String qParam2 = "&extended_info=true";
		
		// append "/envelopes/{envelopeId}/recipients" plus optional query params to baseUrl and use in request
		url = baseURL + "/envelopes/" + envelopeId + "/recipients/" + qParam1 + qParam2;
		body = "";	// no request body for this call
		
		// re-use connection object for second request...
		conn = InitializeRequest(url, "GET", body, authenticationHeader);
		
		System.out.println("STEP 2:  Retrieving envelope recipient status for envelope " + envelopeId + ".\n");
		status = conn.getResponseCode(); // triggers the request
		if( status != 200 )	// 200 = OK
		{
			errorParse(conn, status);
			return;
		}
		
		// display results 
		response = getResponseBody(conn);
		System.out.println("-- Get Envelope Recipient response --\n\n" + prettyFormat(response, 2));
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
			conn.setRequestProperty("Content-Type", "application/xml");
			conn.setRequestProperty("Accept", "application/xml");
			if (method.equalsIgnoreCase("POST"))
			{
				conn.setRequestProperty("Content-Length", Integer.toString(body.length()));
				conn.setDoOutput(true);
				// write body of the POST request 
				DataOutputStream dos = new DataOutputStream( conn.getOutputStream() );
				dos.writeBytes(body); dos.flush(); dos.close();
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