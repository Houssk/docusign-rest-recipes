// Request Signature on a Document [using a Template] (Node.js)
// 
// To run this sample
//  1. Copy the file in your own directory - say, example.js
//  2. Change "***" to appropriate values
//  3. Install async and request packages
//     npm install async
//     npm install request
//  4. Execute
//     node example.js 


var docusign = require('docusign-esign'),
	async = require('async');

var integratorKey = '***',	// Integrator Key associated with your DocuSign Integration
	email = '***',			// Email for your DocuSign Account
	password = '***',		// Password for your DocuSign Account
	recipientName = '***',	// Recipient's Full Name
	recipientEmail = '***', // Recipient's Email
	templateId = "***",		// valid templateId from a template in your account
	templateRoleName = "***",	// template role that exists on above template
	docusignEnv = 'demo',	// DocuSign Environment generally demo for testing purposes ('www' == production)
	baseUrl = 'https://' + docusignEnv + '.docusign.net/restapi'; // will be updated after login

async.waterfall(
[
	//////////////////////////////////////////////////////////////////////
	// Step 1 - Login (used to retrieve accountId and baseUrl)
	//////////////////////////////////////////////////////////////////////
	
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
			if (loginInfo) {
				// list of user account(s)
				// note that a given user may be a member of multiple accounts
				var loginAccounts = loginInfo.getLoginAccounts();
				console.log('LoginInformation: ' + JSON.stringify(loginAccounts));
				next(null, loginAccounts);
			}
		});
	},
	
	//////////////////////////////////////////////////////////////////////
	// Step 2 - Request Signature via Template
	//////////////////////////////////////////////////////////////////////
	function requestSignatureViaTemplate(loginAccounts, next) {

		// create a new envelope object that we will manage the signature request through
		var envDef = new docusign.EnvelopeDefinition();
		envDef.setEmailSubject('Please sign this document sent from Node SDK)');
		envDef.setTemplateId(templateId);

		// create a template role with a valid templateId and roleName and assign signer info
		var tRole = new docusign.TemplateRole();
		tRole.setRoleName(templateRoleName);
		tRole.setName(recipientName);
		tRole.setEmail(recipientEmail);

		// create a list of template roles and add our newly created role
		var templateRolesList = [];
		templateRolesList.push(tRole);

		// assign template role(s) to the envelope
		envDef.setTemplateRoles(templateRolesList);

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
				console.log('Error: ' + error);
				return;
			}

			if (envelopeSummary) {
				// Envelope created and sent successfully!
				console.log('EnvelopeSummary: ' + JSON.stringify(envelopeSummary));
			}
		});

	}
]);
