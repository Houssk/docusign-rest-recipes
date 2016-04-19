// Request Signature on a Document (Node.js)

// To run this sample
//  1. Copy the file to your local machine and give .js extension (i.e. example.js)
//  2. Change "***" to appropriate values
//  3. Install docusign-esign, async, and fs packages
//     npm install docusign-esign
//     npm install async
//     npm install fs
//  4. Ensure sure 'blank.pdf' exists in the same directory, or create a blank pdf yourself
//  5. Execute
//     node example.js 
//

var docusign = require('docusign-esign'),
	async = require('async'),
	fs = require('fs'),
	path = require('path');

var integratorKey = '***',	// Integrator Key associated with your DocuSign Integration
	email = '***',			// Email for your DocuSign Account
	password = '***',		// Password for your DocuSign Account
	recipientName = '***',	// Recipient's Full Name
	recipientEmail = '***', // Recipient's Email
	docusignEnv = 'demo',	// DocuSign Environment generally demo for testing purposes ('www' == production)
	fileToSign = 'blank.pdf',
	baseUrl = 'https://' + docusignEnv + '.docusign.net/restapi';


// relative path to PDF (can also be many other filetypes) 

async.waterfall(
  [
	/////////////////////////////////////////////////////////////////////////////////////
	// Step 1: Login (used to retrieve your accountId and account baseUrl)
	/////////////////////////////////////////////////////////////////////////////////////

	function login(next) {

		// initialize the api client
		var apiClient = new docusign.ApiClient();
		apiClient.setBasePath(baseUrl);

		// create JSON formatted auth header
		var creds = JSON.stringify({
		  Username: email,
		  Password: password,
		  IntegratorKey: integratorKey
		});
		apiClient.addDefaultHeader('X-DocuSign-Authentication', creds);

		// assign api client to the Configuration object
		docusign.Configuration.default.setDefaultApiClient(apiClient);

		// login call available off the AuthenticationApi
		var authApi = new docusign.AuthenticationApi();

		// login has some optional parameters we can set
		var loginOps = new authApi.LoginOptions();
		loginOps.setApiPassword('true');
		loginOps.setIncludeAccountIdGuid('true');
		authApi.login(loginOps, function (err, loginInfo, response) {
			if (err) {
				console.error(err.response ? err.response.error : err);
				return;
			}
			console.log('Login Info');
			console.log(JSON.stringify(loginInfo,null,2));
			if (loginInfo) {
				// list of user account(s)
				// note that a given user may be a member of multiple accounts
				var loginAccounts = loginInfo.getLoginAccounts();
				console.log('LoginInformation: ' + JSON.stringify(loginAccounts));
				next(null, loginAccounts);
			}
		});
	},
	
	/////////////////////////////////////////////////////////////////////////////////////
	// Step 2: Request Signature on a PDF Document
	/////////////////////////////////////////////////////////////////////////////////////

	function requestSignature(loginAccounts, next){
		console.log('requestSignature');
		// create a byte array that will hold our document bytes
		var fileBytes = null;
		try {
			// read file from a local directory
			fileBytes = fs.readFileSync(path.resolve([__filename, '..', fileToSign].join('/')));
		} catch (ex) {
			// handle error
			console.log('Exception: ' + ex);
			return;
		}

		// create an envelope that will store the document(s), field(s), and recipient(s)
		var envDef = new docusign.EnvelopeDefinition();
		envDef.setEmailSubject('Please sign this document sent from the DocuSign Node SDK)');

		// add a document to the envelope
		var doc = new docusign.Document();
		var base64Doc = new Buffer(fileBytes).toString('base64');
		doc.setDocumentBase64(base64Doc);
		doc.setName('DocuSign API - Signature Request on Document Call'); // can be different from actual file name
		doc.setDocumentId('1'); // hardcode so we can easily refer to this document later

		var docs = [];
		docs.push(doc);
		envDef.setDocuments(docs);

		// add a recipient to sign the document, identified by name and email we used above
		var signer = new docusign.Signer();
		signer.setEmail(recipientEmail);
		signer.setName(recipientName);
		signer.setRecipientId('1');

		// create a signHere tab somewhere on the document for the signer to sign
		// default unit of measurement is pixels, can be mms, cms, inches also
		var signHere = new docusign.SignHere();
		signHere.setDocumentId('1');
		signHere.setPageNumber('1');
		signHere.setRecipientId('1');
		signHere.setXPosition('100');
		signHere.setYPosition('100');

		// can have multiple tabs, so need to add to envelope as a single element list
		var signHereTabs = [];
		signHereTabs.push(signHere);
		var tabs = new docusign.Tabs();
		tabs.setSignHereTabs(signHereTabs);
		signer.setTabs(tabs);

		// add recipients (in this case a single signer) to the envelope
		envDef.setRecipients(new docusign.Recipients());
		envDef.getRecipients().setSigners([]);
		envDef.getRecipients().getSigners().push(signer);

		// send the envelope by setting |status| to "sent". To save as a draft set to "created"
		// - note that the envelope will only be 'sent' when it reaches the DocuSign server with the 'sent' status (not in the following call)
		envDef.setStatus('sent');

		// use the |accountId| we retrieved through the Login API to create the Envelope
		var loginAccount = new docusign.LoginAccount();
		loginAccount = loginAccounts[0];
		var accountId = loginAccount.accountId;

		// instantiate a new EnvelopesApi object
		var envelopesApi = new docusign.EnvelopesApi();

		// call the createEnvelope() API
		envelopesApi.createEnvelope(accountId, envDef, null, function (error, envelopeSummary, response) {
			if (error) {
				console.error('Error: ' + error);
				return;
			}

			if (envelopeSummary) {
				console.log('EnvelopeSummary: ' + JSON.stringify(envelopeSummary,null,2));
			}
		});
	}

]);
