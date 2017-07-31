// Request Signature on a Document [creating Envelope using a local file] (Node.js)
// 
// To run this sample
//  1. Copy the file to your local machine and give .js extension (i.e. example.js)
//  2. Change "***" to appropriate values
//  3. Install docusign-esign, async, and fs packages
//     npm install docusign-esign
//     npm install async
//     npm install fs
//  4. Ensure the 'fileToSign' variable references a file in the same directory
//  5. Execute
//     node example.js 


var docusign = require('docusign-esign'),
  async = require('async'),
  fs = require('fs'),
  path = require('path');

var integratorKey = process.env.DOCUSIGN_INTEGRATOR_KEY || '***', // Integrator Key associated with your DocuSign Integration
  email = process.env.DOCUSIGN_LOGIN_EMAIL || '***',        // Email for your DocuSign Account
  password = process.env.DOCUSIGN_LOGIN_PASSWORD || '***',    // Password for your DocuSign Account
  recipientName = '***',  // Recipient's Full Name
  recipientEmail = '***', // Recipient's Email
  paymentGatewayId = process.env.DOCUSIGN_PAYMENT_GATEWAY_ID || '***',
  docusignEnv = 'demo', // DocuSign Environment generally demo for testing purposes ('www' == production)
  fileToSign = 'blank.pdf',
  baseUrl = 'https://' + docusignEnv + '.docusign.net/restapi';

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
    var loginOps = {};
    loginOps.apiPassword = 'true';
    loginOps.includeAccountIdGuid = 'true';
    authApi.login(loginOps, function (err, loginInfo, response) {
      if (err) {
        console.error(err.response ? err.response.error : err);
        return;
      }
      if (loginInfo) {
        // list of user account(s)
        // note that a given user may be a member of multiple accounts
        var loginAccounts = loginInfo.loginAccounts;
        console.log('LoginInformation: ' + JSON.stringify(loginAccounts));
        next(null, loginAccounts);
      }
    });
  },
  
  /////////////////////////////////////////////////////////////////////////////////////
  // Step 2: Request Signature on a PDF Document
  /////////////////////////////////////////////////////////////////////////////////////

  function requestPayment(loginAccounts, next){
    
    console.log('Requesting payment');

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

    console.log('Got file');

    // create an envelope to be signed
    var envDef = new docusign.EnvelopeDefinition();
    envDef.emailSubject = 'Please Pay in my Node SDK Envelope';
    envDef.emailBlurb = 'Hello, Please pay in my Node SDK Envelope.';

    // add a document to the envelope
    var doc = new docusign.Document();
    var base64Doc = new Buffer(fileBytes).toString('base64');
    doc.documentBase64 = base64Doc;
    doc.name = 'TestPayment.pdf';
    doc.documentId = '1';

    var docs = [];
    docs.push(doc);
    envDef.documents = docs;

    // Add a recipient to sign the document
    var signer = new docusign.Signer();
    signer.email = recipientEmail;
    signer.name = recipientName;
    signer.recipientId = '1';

    // create a signHere tab somewhere on the document for the signer to sign
    // - a SignHere Tab is ---NOT--- required for payments! 
    var signHere = new docusign.SignHere();
    signHere.documentId = '1';
    signHere.pageNumber = '1';
    signHere.recipientId = '1';
    signHere.xPosition = '100';
    signHere.yPosition = '200';


    // Create the NumberTab to hold the payment information
    var numberTab = new docusign.ModelNumber();
    numberTab.documentId = '1';
    numberTab.pageNumber = '1';
    numberTab.recipientId = '1';
    numberTab.xPosition = '100';
    numberTab.yPosition = '100';
    numberTab.tabLabel = 'tabvalue1';
    numberTab.value = '10.00';
    numberTab.locked = 'true';

    // FormulaTab with lineItems
    var formulaTab = new docusign.FormulaTab();
    formulaTab.required = 'true';
    formulaTab.documentId = '1';
    formulaTab.pageNumber = '1';
    formulaTab.recipientId = '1';
    formulaTab.xPosition = '1'; // placement doesnt really matter, it doesnt show up?
    formulaTab.yPosition = '1'; // placement doesnt really matter, it doesnt show up?
    formulaTab.tabLabel = 'tabpayment1';

    // formula-specific fields
    formulaTab.formula = '[tabvalue1] * 100';
    formulaTab.roundDecimalPlaces = '2';

    // payment-specific fields

    // Create LineItems 
    // - this is what will show up on receipts, credit card statements, and in your Payment Gateway 
    var lineItem = {};
    lineItem.name = 'Name1';
    lineItem.description = 'description1';
    lineItem.itemCode = 'ITEM1';
    lineItem.amountReference = 'tabvalue1';

    var lineItems = [];
    lineItems.push(lineItem);

    formulaTab.paymentDetails = {};
    formulaTab.paymentDetails.currencyCode = 'USD';
    formulaTab.paymentDetails.gatewayAccountId = paymentGatewayId;
    formulaTab.paymentDetails.lineItems = lineItems;


    // can have multiple tabs, so need to add to envelope as a single element list
    var formulaTabs = [];
    formulaTabs.push(formulaTab);

    var numberTabs = [];
    numberTabs.push(numberTab);

    var signHereTabs = [];
    signHereTabs.push(signHere);

    var tabs = new docusign.Tabs();
    tabs.formulaTabs = formulaTabs;
    tabs.numberTabs = numberTabs;
    tabs.signHereTabs = signHereTabs;
    signer.tabs = tabs;

    // Above causes issue
    envDef.recipients = new docusign.Recipients();
    envDef.recipients.signers = [];
    envDef.recipients.signers.push(signer);

    // send the envelope (otherwise it will be "created" in the Draft folder
    envDef.status = 'sent';

    // use the |accountId| we retrieved through the Login API to create the Envelope
    let loginAccount = loginAccounts[0];
    var accountId = loginAccount.accountId;

    // instantiate a new EnvelopesApi object
    var envelopesApi = new docusign.EnvelopesApi();

    // call the createEnvelope() API
    envelopesApi.createEnvelope(accountId, {'envelopeDefinition': envDef}, function (error, envelopeSummary, response) {
      if (error) {
        console.error('Error: ' + error);
        console.log(response);
        return;
      }

      if (envelopeSummary) {
        console.log('EnvelopeSummary: ' + JSON.stringify(envelopeSummary,null,2));
      }
    });
  }

]);
