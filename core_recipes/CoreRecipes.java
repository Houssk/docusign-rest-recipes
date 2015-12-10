/* DocuSignRecipes.java
 * @author: Ergin Dervisoglu
 * 
 * Test Class that demonstrates how to accomplish various REST API use-cases.  
 */

// DocuSign imports
import DocuSign.Core.Api.*;
import DocuSign.Core.Model.*;
import DocuSign.Core.Client.*;

// additional imports
import java.util.List;
import java.util.ArrayList;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.io.IOException;
import java.io.File;
import java.util.Base64;

public class CoreRecipes {

    // TODO: Enter valid DocuSign credentials 
    public static final String UserName = "[EMAIL]";
    public static final String Password = "[PASSWORD]";
    public static final String IntegratorKey = "[INTEGRATOR_KEY]";
    
    // for production environment update to "www.docusign.net"
    public static final String BaseUrl = "https://demo.docusign.net/restapi";
    
    /*****************************************************************************************************************
     * Recipe01_RequestSignatureOnDocument() 
     * 
     * This recipe demonstrates how to request a signature on a document by first making the 
     * Login API call then the Create Envelope API call.  
     ******************************************************************************************************************/
    public void Recipe01_RequestSignatureOnDocument() {
        
        // TODO: Enter signer information and path to a test file
        String signerName = "[SIGNER_NAME]";
        String signerEmail = "[SIGNER_EMAIL]";
        // point to a local document for testing
        final String SignTest1File = "[PATH/TO/DOCUMENT/TEST.PDF]";
        
        // list of user account(s)
        List<DocuSign.Core.Model.LoginAccount> loginAccounts = null;
        
        // initialize the api client
        DocuSign.Core.Client.ApiClient apiClient = new DocuSign.Core.Client.ApiClient();
        apiClient.setBasePath(BaseUrl);
        
        // create JSON formatted auth header
        String creds = "{\"Username\":\"" +  UserName + "\",\"Password\":\"" +  Password + "\",\"IntegratorKey\":\"" +  IntegratorKey + "\"}";
        apiClient.addDefaultHeader("X-DocuSign-Authentication", creds);
        
        // assign api client to the Configuration object
        Configuration.setDefaultApiClient(apiClient);
        
        //===============================================================================
        // Step 1:  Login() API
        //===============================================================================
        // The Get Login method has 3 optional parameters which return additional information 
        // in the response: |api_password|, |account_id_guid|, and |login_settings|.  
        // |login settings| value can be "none" or "all"
        //===============================================================================
        try
        {
            // Login method resides in the UsersApi
            UsersApi usersApi = new UsersApi();
            
            // Call the getLogin() API
            LoginInformation loginInfo = usersApi.getLogin(Boolean.TRUE, Boolean.FALSE, "none");
         
            // note that a given user may be a member of multiple accounts
            loginAccounts = loginInfo.getLoginAccounts();
            
            System.out.println("LoginInformation: " + loginAccounts);
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }

        //===============================================================================
        // Step 2:  Create Envelope API (AKA Signature Request)
        //===============================================================================
        // A given Envelope can have multiple documents, multiple signing and data fields, and
        // multiple recipients.  Setting envelope |status| to "created" saves the Envelope to your 
        // draft folder, "sent" sends the request immediately. This minimal example sends one  
        // document with one signature tab located 100 pixels right, 100 pixels down from the top 
        // left corner of page 1, to just one recipient (signer).
        //===============================================================================
        
        // create a byte array that will hold our document bytes
        byte[] fileBytes = null;
        
        try
        {
            String currentDir = System.getProperty("user.dir");
            
            // read file from a local directory
            Path path = Paths.get(currentDir + SignTest1File);
            fileBytes = Files.readAllBytes(path);
        }
        catch (IOException ioExcp)
        {
            // TODO: handle error
            System.out.println("Exception: " + ioExcp);
            return;
        }
        
        // create an envelope that will store the document(s), field(s), and recipient(s)
        EnvelopeDefinition envDef = new EnvelopeDefinition();
        envDef.setEmailSubject("Please sign this document sent from Java SDK)");
        
        // add a document to the envelope
        Document doc = new Document();  
        String base64Doc = Base64.getEncoder().encodeToString(fileBytes);
        doc.setDocumentBase64(base64Doc);
        doc.setName("TestFile.pdf");    // can be different from actual file name
        doc.setDocumentId("1");
        
        List<Document> docs = new ArrayList<Document>();
        docs.add(doc);
        envDef.setDocuments(docs);
        
        // add a recipient to sign the document, identified by name and email we used above
        Signer signer = new Signer();
        signer.setEmail(signerEmail);
        signer.setName(signerName);
        signer.setRecipientId("1");
        
        // create a |signHere| tab somewhere on the document for the signer to sign
        // default unit of measurement is pixels, can be mms, cms, inches also
        SignHere signHere = new SignHere();
        signHere.setDocumentId("1");
        signHere.setPageNumber("1");
        signHere.setRecipientId("1");
        signHere.setXPosition("100");
        signHere.setYPosition("100");
        
        // can have multiple tabs, so need to add to envelope as a single element list
        List<SignHere> signHereTabs = new ArrayList<SignHere>();      
        signHereTabs.add(signHere);
        Tabs tabs = new Tabs();
        tabs.setSignHereTabs(signHereTabs);
        signer.setTabs(tabs);
        
        // add recipients (in this case a single signer) to the envelope
        envDef.setRecipients(new Recipients());
        envDef.getRecipients().setSigners(new ArrayList<Signer>());
        envDef.getRecipients().getSigners().add(signer);
        
        // send the envelope by setting |status| to "sent". To save as a draft set to "created"
        envDef.setStatus("sent");
        
        try
        {
            // use the |accountId| we retrieved through the Login API to create the Envelope
            String accountId = loginAccounts.get(0).getAccountId();
            
            // instantiate a new EnvelopesApi object
            EnvelopesApi envelopesApi = new EnvelopesApi();
            
            // Call the createEnvelope() API
            EnvelopeSummary envelopeSummary = envelopesApi.create(accountId, null, null, envDef);
            
            System.out.println("EnvelopeSummary: " + envelopeSummary);
             
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        } 
        
    } // end Recipe01_RequestSignature()
    
    /*****************************************************************************************************************
     * Recipe02_RequestSignatureFromTemplate() 
     * 
     * This recipe demonstrates how to request a signature from a template in your account.  Templates are design-time
     * objects that contain documents, tabs, routing, and recipient roles.  To run this recipe you need to provide a 
     * valid templateId from your account along with a role name that the template has configured. 
     ******************************************************************************************************************/
    public void Recipe02_RequestSignatureFromTemplate() {
        
        // TODO: Enter signer information and template info from a template in your account
        String signerName = "[SIGNER_NAME]";
        String signerEmail = "[SIGNER_EMAIL]";
        String templateId = "[TEMPLATE_ID]"; 
        String templateRoleName = "[TEMPLATE_ROLE_NAME]";
        
        // list of user account(s)
        List<DocuSign.Core.Model.LoginAccount> loginAccounts = null;
        
        // initialize the api client
        DocuSign.Core.Client.ApiClient apiClient = new DocuSign.Core.Client.ApiClient();
        apiClient.setBasePath(BaseUrl);
        
        // create JSON formatted auth header
        String creds = "{\"Username\":\"" +  UserName + "\",\"Password\":\"" +  Password + "\",\"IntegratorKey\":\"" +  IntegratorKey + "\"}";
        apiClient.addDefaultHeader("X-DocuSign-Authentication", creds);
        
        // assign api client to the Configuration object
        Configuration.setDefaultApiClient(apiClient);
        
        //===============================================================================
        // Step 1:  Login() API
        //===============================================================================
        // The Get Login method has 3 optional parameters which return additional information 
        // in the response: |api_password|, |account_id_guid|, and |login_settings|.  
        // |login settings| value can be "none" or "all"
        //===============================================================================
        try
        {
            // Login method resides in the UsersApi
            UsersApi usersApi = new UsersApi();
            
            // Call the getLogin() API
            LoginInformation loginInfo = usersApi.getLogin(Boolean.TRUE, Boolean.FALSE, "none");
         
            // note that a given user may be a member of multiple accounts
            loginAccounts = loginInfo.getLoginAccounts();
            
            System.out.println("LoginInformation: " + loginAccounts);
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
        
        //===============================================================================
        // Step 2:  Create Envelope API (AKA Signature Request) from a Template
        //===============================================================================
        // The following recipe demonstrates how to send a signature request from an 
        // account (server) Template.  Templates are design-time objects that contain 
        // documents, tabs, routing and workflow.  To create an envelope from a template
        // you must specify a valid |templateId| from your account as well as the name
        // of a template role contained in that template.  
        //===============================================================================
        
        // create a new envelope object that we will manage the signature request through
        EnvelopeDefinition envDef = new EnvelopeDefinition();
        envDef.setEmailSubject("Please sign this document sent from Java SDK)");
        
        // assign template information including ID and role(s)
        envDef.setTemplateId(templateId);
        
        // create a template role with a valid templateId and roleName and assign signer info
        TemplateRole tRole = new TemplateRole();
        tRole.setRoleName(templateRoleName);
        tRole.setName(signerName);
        tRole.setEmail(signerEmail);
        
        // create a list of template roles and add our newly created role
        List<TemplateRole> templateRolesList = new ArrayList<TemplateRole>();
        templateRolesList.add(tRole);
        
        // assign template role(s) to the envelope 
        envDef.setTemplateRoles(templateRolesList);
        
        // send the envelope by setting |status| to "sent". To save as a draft set to "created"
        envDef.setStatus("sent");
        
        try
        {
            // use the |accountId| we retrieved through the Login API to create the Envelope
            String accountId = loginAccounts.get(0).getAccountId();
            
            // instantiate a new EnvelopesApi object
            EnvelopesApi envelopesApi = new EnvelopesApi();
            
            // Call the createEnvelope() API
            EnvelopeSummary envelopeSummary = envelopesApi.create(accountId, null, null, envDef);
            
            System.out.println("EnvelopeSummary: " + envelopeSummary);
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        } 
        
    } // end Recipe02_RequestSignatureFromTemplate()
    
    /*****************************************************************************************************************
     * Recipe03_GetEnvelopeInformation() 
     * 
     * This recipe demonstrates how to retrieve real-time envelope information for an existing envelope.  Note that 
     * DocuSign has certain platform rules in place which limit how frequently you can poll for status on a given 
     * envelope.  As of this writing the current limit is once every 15 minutes for a given envelope. 
     ******************************************************************************************************************/
    public void Recipe03_GetEnvelopeInformation() {
        
        // TODO: Enter envelopeId of an envelope you have access to (i.e. you sent the envelope or you're an account admin)
        String envelopeId = "[ENVELOPE_ID]";
        
        // list of user account(s)
        List<DocuSign.Core.Model.LoginAccount> loginAccounts = null;
        
        // initialize the api client
        DocuSign.Core.Client.ApiClient apiClient = new DocuSign.Core.Client.ApiClient();
        apiClient.setBasePath(BaseUrl);
        
        // create JSON formatted auth header
        String creds = "{\"Username\":\"" +  UserName + "\",\"Password\":\"" +  Password + "\",\"IntegratorKey\":\"" +  IntegratorKey + "\"}";
        apiClient.addDefaultHeader("X-DocuSign-Authentication", creds);
        
        // assign api client to the Configuration object
        Configuration.setDefaultApiClient(apiClient);
        
        //===============================================================================
        // Step 1:  Login() API
        //===============================================================================
        // The Get Login method has 3 optional parameters which return additional information 
        // in the response: |api_password|, |account_id_guid|, and |login_settings|.  
        // |login settings| value can be "none" or "all"
        //===============================================================================
        try
        {
            // Login method resides in the UsersApi
            UsersApi usersApi = new UsersApi();
            
            // Call the getLogin() API
            LoginInformation loginInfo = usersApi.getLogin(Boolean.TRUE, Boolean.FALSE, "none");
         
            // note that a given user may be a member of multiple accounts
            loginAccounts = loginInfo.getLoginAccounts();
            
            System.out.println("LoginInformation: " + loginAccounts);
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
        
        //===============================================================================
        // Step 2:  Get Envelope API 
        //===============================================================================
        // The following recipe demonstrates how to retrieve real-time envelope information 
        // for an existing envelope.
        //===============================================================================
        
        try 
        {
            // use the |accountId| we retrieved through the Login API to create the Envelope
            String accountId = loginAccounts.get(0).getAccountId();
            
            // instantiate a new EnvelopesApi object
            EnvelopesApi envelopesApi = new EnvelopesApi();
            
            // Call the createEnvelope() API
            Envelope env = envelopesApi.get(accountId, envelopeId, creds);
            
            System.out.println("Envelope: " + env);
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
        
        
    } // end Recipe03_GetEnvelopeInformation() 
    
    
    /*****************************************************************************************************************
     * Recipe04_GetEnvelopeRecipientInformation() 
     * 
     * This recipe demonstrates how to retrieve real-time envelope recipient information for an existing draft or sent  
     * envelope.  The call will return information on all recipients that are part of the envelope's routing order.  
     ******************************************************************************************************************/
    public void Recipe04_GetEnvelopeRecipientInformation() {
        
        // TODO: Enter envelopeId of an envelope you have access to (i.e. you sent the envelope or you're an account admin)
        String envelopeId = "[ENVELOPE_ID]";
        
        // list of user account(s)
        List<DocuSign.Core.Model.LoginAccount> loginAccounts = null;
        
        // initialize the api client
        DocuSign.Core.Client.ApiClient apiClient = new DocuSign.Core.Client.ApiClient();
        apiClient.setBasePath(BaseUrl);
        
        // create JSON formatted auth header
        String creds = "{\"Username\":\"" +  UserName + "\",\"Password\":\"" +  Password + "\",\"IntegratorKey\":\"" +  IntegratorKey + "\"}";
        apiClient.addDefaultHeader("X-DocuSign-Authentication", creds);
        
        // assign api client to the Configuration object
        Configuration.setDefaultApiClient(apiClient);
        
        //===============================================================================
        // Step 1:  Login() API
        //===============================================================================
        // The Get Login method has 3 optional parameters which return additional information 
        // in the response: |api_password|, |account_id_guid|, and |login_settings|.  
        // |login settings| value can be "none" or "all"
        //===============================================================================
        try
        {
            // Login method resides in the UsersApi
            UsersApi usersApi = new UsersApi();
            
            // Call the getLogin() API
            LoginInformation loginInfo = usersApi.getLogin(Boolean.TRUE, Boolean.FALSE, "none");
         
            // note that a given user may be a member of multiple accounts
            loginAccounts = loginInfo.getLoginAccounts();
            
            System.out.println("LoginInformation: " + loginAccounts);
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
        
        //===============================================================================
        // Step 2:  Get Envelope Recipient API 
        //===============================================================================
        // The following recipe demonstrates how to retrieve real-time envelope information 
        // for an existing envelope.
        //===============================================================================
        
        try 
        {
            // use the |accountId| we retrieved through the Login API to create the Envelope
            String accountId = loginAccounts.get(0).getAccountId();
            
            // instantiate a new EnvelopesApi object
            EnvelopeRecipientsApi envelopeRecipsApi = new EnvelopeRecipientsApi();
            
            // Call the createEnvelope() API
            Recipients recips = envelopeRecipsApi.list(accountId, envelopeId);
            
            System.out.println("Recipients: " + recips);
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
        
    } // end Recipe04_GetEnvelopeRecipientInformation()
    
    /*****************************************************************************************************************
     * Recipe05_ListEnvelopes() 
     * 
     * This recipe demonstrates how to retrieve real-time envelope status and information for an existing envelopes in  
     * your account.  The returned set of envelopes can be filtered by date, status, or other properties.  
     ******************************************************************************************************************/
    public void Recipe05_ListEnvelopes() {
        
        // list of user account(s)
        List<DocuSign.Core.Model.LoginAccount> loginAccounts = null;
        
        // initialize the api client
        DocuSign.Core.Client.ApiClient apiClient = new DocuSign.Core.Client.ApiClient();
        apiClient.setBasePath(BaseUrl);
        
        // create JSON formatted auth header
        String creds = "{\"Username\":\"" +  UserName + "\",\"Password\":\"" +  Password + "\",\"IntegratorKey\":\"" +  IntegratorKey + "\"}";
        apiClient.addDefaultHeader("X-DocuSign-Authentication", creds);
        
        // assign api client to the Configuration object
        Configuration.setDefaultApiClient(apiClient);
        
        //===============================================================================
        // Step 1:  Login() API
        //===============================================================================
        // The Get Login method has 3 optional parameters which return additional information 
        // in the response: |api_password|, |account_id_guid|, and |login_settings|.  
        // |login settings| value can be "none" or "all"
        //===============================================================================
        try
        {
            // Login method resides in the UsersApi
            UsersApi usersApi = new UsersApi();
            
            // Call the getLogin() API
            LoginInformation loginInfo = usersApi.getLogin(Boolean.TRUE, Boolean.FALSE, "none");
         
            // note that a given user may be a member of multiple accounts
            loginAccounts = loginInfo.getLoginAccounts();
            
            System.out.println("LoginInformation: " + loginAccounts);
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
        
        //===============================================================================
        // Step 2:  List Envelopes API 
        //===============================================================================
        // The following recipe demonstrates how to retrieve real-time envelope information 
        // for a set of existing envelopes in your account.
        //===============================================================================
        
        try 
        {
            // use the |accountId| we retrieved through the Login API to create the Envelope
            String accountId = loginAccounts.get(0).getAccountId();
            
            // instantiate a new EnvelopesApi object
            EnvelopesApi envelopesApi = new EnvelopesApi(); 
            
            // Call the createEnvelope() API
            EnvelopesInformation envelopes = envelopesApi.listStatusChanges(accountId, null, null, null, null, null, null, null, null, null, "6/1/2015", null, null, null, null, null, null, null, null, null, null, null, null, null, null);
            
            System.out.println("EnvelopesInformation: " + envelopes);
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
        
    } // end Recipe05_ListEnvelopes()
    
    /*****************************************************************************************************************
     * Recipe06_GetEnvelopeDocuments() 
     * 
     * This recipe demonstrates how to retrieve the documents from a given envelope.  Note that if the envelope is in 
     * completed status that you have the option of downloading just the signed documents or a combined PDF that contains
     * the envelope documents as well as the envelope's auto-generated Certificate of Completion (CoC).   
     ******************************************************************************************************************/
    public void Recipe06_GetEnvelopeDocuments() {
        
        // TODO: THIS RECIPE IS CURRENTLY INCOMPLETE, AWAITING BUG FIX
        
        // TODO: Enter envelopeId of an envelope you have access to (i.e. you sent the envelope or you're an account admin)
        String envelopeId = "E124673A1A774BD9A66EE7FB056EB22D";
        String documentId = "1";
        
        // list of user account(s)
        List<DocuSign.Core.Model.LoginAccount> loginAccounts = null;
        
        // initialize the api client
        DocuSign.Core.Client.ApiClient apiClient = new DocuSign.Core.Client.ApiClient();
        apiClient.setBasePath(BaseUrl);
        
        // create JSON formatted auth header
        String creds = "{\"Username\":\"" +  UserName + "\",\"Password\":\"" +  Password + "\",\"IntegratorKey\":\"" +  IntegratorKey + "\"}";
        apiClient.addDefaultHeader("X-DocuSign-Authentication", creds);
        
        // assign api client to the Configuration object
        Configuration.setDefaultApiClient(apiClient);
        
        //===============================================================================
        // Step 1:  Login() API
        //===============================================================================
        // The Get Login method has 3 optional parameters which return additional information 
        // in the response: |api_password|, |account_id_guid|, and |login_settings|.  
        // |login settings| value can be "none" or "all"
        //===============================================================================
        try
        {
            // Login method resides in the UsersApi
            UsersApi usersApi = new UsersApi();
            
            // Call the getLogin() API
            LoginInformation loginInfo = usersApi.getLogin(Boolean.TRUE, Boolean.FALSE, "none");
         
            // note that a given user may be a member of multiple accounts
            loginAccounts = loginInfo.getLoginAccounts();
            
            System.out.println("LoginInformation: " + loginAccounts);
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
        
        //===============================================================================
        // Step 2:  Get Envelope Document API 
        //===============================================================================
        // The following recipe demonstrates how to download envelope document(s). 
        //===============================================================================
        
        try 
        {
            // use the |accountId| we retrieved through the Login API to create the Envelope
            String accountId = loginAccounts.get(0).getAccountId();
            
            // instantiate a new EnvelopeDocumentsApi object
            EnvelopeDocumentsApi envelopeDocsApi = new EnvelopeDocumentsApi();
            
            // Call the Get Envelope Document API
            File document = envelopeDocsApi.get(accountId, envelopeId, documentId);
            
            System.out.println("Document " + documentId + " from envelope " + envelopeId + " has been downloaded to " + document.getAbsolutePath());
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
        
    } // end Recipe06_GetEnvelopeDocuments()    
    
    
    /*****************************************************************************************************************
     * Recipe07_EmbeddedSending() 
     * 
     * This recipe demonstrates how to open the Embedded Sending view of a given envelope (AKA the Sender View).  While
     * in the sender view the user can edit the envelope by adding/deleting documents, tabs, and/or recipients before 
     * sending the envelope (signature request) out.   
     ******************************************************************************************************************/
    public void Recipe07_EmbeddedSending() {
        
        // TODO: Enter signer info and path to a test file
        String signerName = "[SIGNER_NAME]";
        String signerEmail = "[SIGNER_EMAIL]";
        
        // point to a local document for testing
        final String SignTest1File = "[PATH/TO/DOCUMENT/TEST.PDF]";
        
        // we will generate this from the second API call we make
        StringBuffer envelopeId = new StringBuffer();
        
        // list of user account(s)
        List<DocuSign.Core.Model.LoginAccount> loginAccounts = null;
        
        // initialize the api client
        DocuSign.Core.Client.ApiClient apiClient = new DocuSign.Core.Client.ApiClient();
        apiClient.setBasePath(BaseUrl);
        
        // create JSON formatted auth header
        String creds = "{\"Username\":\"" +  UserName + "\",\"Password\":\"" +  Password + "\",\"IntegratorKey\":\"" +  IntegratorKey + "\"}";
        apiClient.addDefaultHeader("X-DocuSign-Authentication", creds);
        
        // assign api client to the Configuration object
        Configuration.setDefaultApiClient(apiClient);
        
        //===============================================================================
        // Step 1:  Login() API
        //===============================================================================
        // The Get Login method has 3 optional parameters which return additional information 
        // in the response: |api_password|, |account_id_guid|, and |login_settings|.  
        // |login settings| value can be "none" or "all"
        //===============================================================================
        try
        {
            // Login method resides in the UsersApi
            UsersApi usersApi = new UsersApi();
            
            // Call the getLogin() API
            LoginInformation loginInfo = usersApi.getLogin(Boolean.TRUE, Boolean.FALSE, "none");
         
            // note that a given user may be a member of multiple accounts
            loginAccounts = loginInfo.getLoginAccounts();
            
            System.out.println("LoginInformation: " + loginAccounts);
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
        
        //===============================================================================
        // Step 2:  Create Envelope API (AKA Signature Request)
        //===============================================================================
        // A given Envelope can have multiple documents, multiple signing and data fields, and
        // multiple recipients.  Make sure you set the envelope's status to "created" so that
        // we can open the embedded sending view (you cannot generate the sending view on an
        // envelope that has already been sent). 
        //===============================================================================
        
        // create a byte array that will hold our document bytes
        byte[] fileBytes = null;
        
        try
        {
            String currentDir = System.getProperty("user.dir");
            
            // read file from a local directory
            Path path = Paths.get(currentDir + SignTest1File);
            fileBytes = Files.readAllBytes(path);
        }
        catch (IOException ioExcp)
        {
            // TODO: handle error
            System.out.println("Exception: " + ioExcp);
            return;
        }
        
        // create an envelope that will store the document(s), field(s), and recipient(s)
        EnvelopeDefinition envDef = new EnvelopeDefinition();
        envDef.setEmailSubject("Please sign this document sent from Java SDK)");
        
        // add a document to the envelope
        Document doc = new Document();  
        String base64Doc = Base64.getEncoder().encodeToString(fileBytes);
        doc.setDocumentBase64(base64Doc);
        doc.setName("TestFile.pdf");    // can be different from actual file name
        doc.setDocumentId("1");
        
        List<Document> docs = new ArrayList<Document>();
        docs.add(doc);
        envDef.setDocuments(docs);
        
        // add a recipient to sign the document, identified by name and email we used above
        Signer signer = new Signer();
        signer.setEmail(signerEmail);
        signer.setName(signerName);
        signer.setRecipientId("1");
        
        // create a |signHere| tab somewhere on the document for the signer to sign
        // default unit of measurement is pixels, can be mms, cms, inches also
        SignHere signHere = new SignHere();
        signHere.setDocumentId("1");
        signHere.setPageNumber("1");
        signHere.setRecipientId("1");
        signHere.setXPosition("100");
        signHere.setYPosition("100");
        
        // can have multiple tabs, so need to add to envelope as a single element list
        List<SignHere> signHereTabs = new ArrayList<SignHere>();      
        signHereTabs.add(signHere);
        Tabs tabs = new Tabs();
        tabs.setSignHereTabs(signHereTabs);
        signer.setTabs(tabs);
        
        // add recipients (in this case a single signer) to the envelope
        envDef.setRecipients(new Recipients());
        envDef.getRecipients().setSigners(new ArrayList<Signer>());
        envDef.getRecipients().getSigners().add(signer);
        
        // set envelope's |status| to "created" so we can open the embedded sending view next
        envDef.setStatus("created");
        
        try
        {
            // use the |accountId| we retrieved through the Login API to create the Envelope
            String accountId = loginAccounts.get(0).getAccountId();
            
            // instantiate a new EnvelopesApi object
            EnvelopesApi envelopesApi = new EnvelopesApi();
            
            // Call the createEnvelope() API
            EnvelopeSummary envelopeSummary = envelopesApi.create(accountId, null, null, envDef);
            envelopeId.append( envelopeSummary.getEnvelopeId() );
            
            System.out.println("EnvelopeSummary: " + envelopeSummary);
            
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
        
        //===============================================================================
        // Step 3:  Create SenderView API 
        //===============================================================================
        // The following recipe demonstrates how to create the Embedded Sending View 
        // (AKA Sender View) of a given envelope.  The user requesting the sender view
        // must be the sender of the envelope or an account administrator in the same account.
        // Note that the view can only be generated for an envelope that's in draft state.
        //===============================================================================
        
        try 
        {
            // use the |accountId| we retrieved through the Login API to create the Envelope
            String accountId = loginAccounts.get(0).getAccountId();
            
            // instantiate a new EnvelopeViewsApi object
            EnvelopeViewsApi envelopeView = new EnvelopeViewsApi();
            
            // set the url where you want the sender to go once they are done editing the envelope
            ReturnUrlRequest returnUrl = new ReturnUrlRequest();
            returnUrl.setReturnUrl("https://www.docusign.com/devcenter");
            
            // Call the createEnvelope() API
            ViewUrl senderView = envelopeView.createSender(accountId, envelopeId.toString(), returnUrl);
            
            System.out.println("ViewUrl: " + senderView);
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
        
    } // end Recipe07_EmbeddedSending()
    
    /*****************************************************************************************************************
     * Recipe08_EmbeddedSigning() 
     * 
     * This recipe demonstrates how to open the Embedded Signing view of a given envelope (AKA the Recipient View).  The
     * Recipient View can be used to sign document(s) directly through your UI without having to context-switch and sign
     * through the DocuSign Website.  This is done by opening the Recipient View in an iFrame for web applications or 
     * a webview for mobile apps.
     ******************************************************************************************************************/
    public void Recipe08_EmbeddedSigning() {
        
        // TODO: Enter signer info and path to a test file
        String signerName = "[SIGNER_NAME]";
        String signerEmail = "[SIGNER_EMAIL]";
        // point to a local document for testing
        final String SignTest1File = "[PATH/TO/DOCUMENT/TEST.PDF]";
        
        // we will generate this from the second API call we make
        StringBuffer envelopeId = new StringBuffer();
        
        // list of user account(s)
        List<DocuSign.Core.Model.LoginAccount> loginAccounts = null;
        
        // initialize the api client
        DocuSign.Core.Client.ApiClient apiClient = new DocuSign.Core.Client.ApiClient();
        apiClient.setBasePath(BaseUrl);
        
        // create JSON formatted auth header
        String creds = "{\"Username\":\"" +  UserName + "\",\"Password\":\"" +  Password + "\",\"IntegratorKey\":\"" +  IntegratorKey + "\"}";
        apiClient.addDefaultHeader("X-DocuSign-Authentication", creds);
        
        // assign api client to the Configuration object
        Configuration.setDefaultApiClient(apiClient);
        
        //===============================================================================
        // Step 1:  Login() API
        //===============================================================================
        // The Get Login method has 3 optional parameters which return additional information 
        // in the response: |api_password|, |account_id_guid|, and |login_settings|.  
        // |login settings| value can be "none" or "all"
        //===============================================================================
        try
        {
            // Login method resides in the UsersApi
            UsersApi usersApi = new UsersApi();
            
            // Call the getLogin() API
            LoginInformation loginInfo = usersApi.getLogin(Boolean.TRUE, Boolean.FALSE, "none");
         
            // note that a given user may be a member of multiple accounts
            loginAccounts = loginInfo.getLoginAccounts();
            
            System.out.println("LoginInformation: " + loginAccounts);
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
        
        //===============================================================================
        // Step 2:  Create Envelope API (AKA Signature Request)
        //===============================================================================
        // A given Envelope can have multiple documents, multiple signing and data fields, and
        // multiple recipients.  Note that you must set the |clientUseId| property to a non-null
        // value for any recipient that will sign the document(s) through embedded signing.    
        //===============================================================================
        
        // create a byte array that will hold our document bytes
        byte[] fileBytes = null;
        
        try
        {
            String currentDir = System.getProperty("user.dir");
            
            // read file from a local directory
            Path path = Paths.get(currentDir + SignTest1File);
            fileBytes = Files.readAllBytes(path);
        }
        catch (IOException ioExcp)
        {
            // TODO: handle error
            System.out.println("Exception: " + ioExcp);
            return;
        }
        
        // create an envelope that will store the document(s), field(s), and recipient(s)
        EnvelopeDefinition envDef = new EnvelopeDefinition();
        envDef.setEmailSubject("Please sign this document sent from Java SDK)");
        
        // add a document to the envelope
        Document doc = new Document();  
        String base64Doc = Base64.getEncoder().encodeToString(fileBytes);
        doc.setDocumentBase64(base64Doc);
        doc.setName("TestFile.pdf");    // can be different from actual file name
        doc.setDocumentId("1");
        
        List<Document> docs = new ArrayList<Document>();
        docs.add(doc);
        envDef.setDocuments(docs);
        
        // add a recipient to sign the document, identified by name and email we used above
        Signer signer = new Signer();
        signer.setEmail(signerEmail);
        signer.setName(signerName);
        signer.setRecipientId("1");
        
        // Must set |clientUserId| for embedded recipients and provide the same value when requesting
        // the recipient view URL in the next step
        signer.setClientUserId("1001");
        
        // create a |signHere| tab somewhere on the document for the signer to sign
        // default unit of measurement is pixels, can be mms, cms, inches also
        SignHere signHere = new SignHere();
        signHere.setDocumentId("1");
        signHere.setPageNumber("1");
        signHere.setRecipientId("1");
        signHere.setXPosition("100");
        signHere.setYPosition("100");
        
        // can have multiple tabs, so need to add to envelope as a single element list
        List<SignHere> signHereTabs = new ArrayList<SignHere>();      
        signHereTabs.add(signHere);
        Tabs tabs = new Tabs();
        tabs.setSignHereTabs(signHereTabs);
        signer.setTabs(tabs);
        
        // add recipients (in this case a single signer) to the envelope
        envDef.setRecipients(new Recipients());
        envDef.getRecipients().setSigners(new ArrayList<Signer>());
        envDef.getRecipients().getSigners().add(signer);
        
        // send the envelope by setting |status| to "sent". To save as a draft set to "created"
        envDef.setStatus("sent");
        
        try
        {
            // use the |accountId| we retrieved through the Login API to create the Envelope
            String accountId = loginAccounts.get(0).getAccountId();
            
            // instantiate a new EnvelopesApi object
            EnvelopesApi envelopesApi = new EnvelopesApi();
            
            // Call the createEnvelope() API
            EnvelopeSummary envelopeSummary = envelopesApi.create(accountId, null, null, envDef);
            envelopeId.append( envelopeSummary.getEnvelopeId() );
            
            System.out.println("EnvelopeSummary: " + envelopeSummary);
            
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
        
        //===============================================================================
        // Step 3:  Create RecipientView API 
        //===============================================================================
        // Now that we've created a new envelope with an embedded recipient (since we set
        // their |clientUserId| property in step #2) we can now request the recipient view 
        //  - i.e. the embedded signing URL.   
        //===============================================================================
        
        try 
        {
            // use the |accountId| we retrieved through the Login API to create the Envelope
            String accountId = loginAccounts.get(0).getAccountId();
            
            // instantiate a new EnvelopeViewsApi object
            EnvelopeViewsApi envelopeView = new EnvelopeViewsApi();
            
            // set the url where you want the recipient to go once they are done signing
            RecipientViewRequest returnUrl = new RecipientViewRequest();
            returnUrl.setReturnUrl("https://www.docusign.com/devcenter");
            returnUrl.setAuthenticationMethod("email");
            
            // recipient information must match embedded recipient info we provided in step #2
            returnUrl.setEmail(signerEmail);
            returnUrl.setUserName(signerName);
            returnUrl.setRecipientId("1");
            returnUrl.setClientUserId("1001");
            
            // Call the Create RecipientView API
            ViewUrl recipientView = envelopeView.createRecipient(accountId, envelopeId.toString(), returnUrl);
            
            System.out.println("ViewUrl: " + recipientView);
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
        
    } // end Recipe08_EmbeddedSigning()

    /*****************************************************************************************************************
     * Recipe09_EmbeddedConsole() 
     * 
     * This recipe demonstrates how to open the DocuSign Console in an embedded view.  DocuSign recommends you use an 
     * iFrame for web applications and a webview for mobile apps.   
     ******************************************************************************************************************/
    public void Recipe09_EmbeddedConsole() {
        
        // list of user account(s)
        List<DocuSign.Core.Model.LoginAccount> loginAccounts = null;
        
        // initialize the api client
        DocuSign.Core.Client.ApiClient apiClient = new DocuSign.Core.Client.ApiClient();
        apiClient.setBasePath(BaseUrl);
        
        // create JSON formatted auth header
        String creds = "{\"Username\":\"" +  UserName + "\",\"Password\":\"" +  Password + "\",\"IntegratorKey\":\"" +  IntegratorKey + "\"}";
        apiClient.addDefaultHeader("X-DocuSign-Authentication", creds);
        
        // assign api client to the Configuration object
        Configuration.setDefaultApiClient(apiClient);
        
        //===============================================================================
        // Step 1:  Login() API
        //===============================================================================
        // The Get Login method has 3 optional parameters which return additional information 
        // in the response: |api_password|, |account_id_guid|, and |login_settings|.  
        // |login settings| value can be "none" or "all"
        //===============================================================================
        try
        {
            // Login method resides in the UsersApi
            UsersApi usersApi = new UsersApi();
            
            // Call the getLogin() API
            LoginInformation loginInfo = usersApi.getLogin(Boolean.TRUE, Boolean.FALSE, "none");
         
            // note that a given user may be a member of multiple accounts
            loginAccounts = loginInfo.getLoginAccounts();
            
            System.out.println("LoginInformation: " + loginAccounts);
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
        
        //===============================================================================
        // Step 2:  Create ConsoleView API 
        //===============================================================================
        // The following recipe demonstrates how to create an Embedded DocuSign Console
        // view that you can open in an iFrame for web applications or a webview for
        // mobile apps.  
        //===============================================================================
        try 
        {
            // use the |accountId| we retrieved through the Login API
            String accountId = loginAccounts.get(0).getAccountId();
            
            // instantiate a new envelopeViewsApi object
            EnvelopeViewsApi viewApi = new EnvelopeViewsApi();
            
            // set the url where you want the sender to go once they are done editing the envelope
            ConsoleViewRequest returnUrl = new ConsoleViewRequest();
            returnUrl.setReturnUrl("https://www.docusign.com/devcenter");
            
            // Call the createEnvelope() API
            ViewUrl consoleView = viewApi.createConsole(accountId, returnUrl);
            
            System.out.println("ConsoleView: " + consoleView);
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
        
    } // end Recipe09_EmbeddedConsole()
    
    
    /*****************************************************************************************************************
     * getLoginApi() 
     * 
     * Demonstrates how to make the get Login API call to retrieve your |accountId|, which is needed to create
     * envelopes and make other API calls.
     ******************************************************************************************************************/
    public List<DocuSign.Core.Model.LoginAccount> getLoginApi(String username, String password, String integratorKey) {
    
    	// initialize the api client
        DocuSign.Core.Client.ApiClient apiClient = new DocuSign.Core.Client.ApiClient();
        apiClient.setBasePath(BaseUrl);
        
        // create JSON formatted auth header
        String creds = "{\"Username\":\"" +  UserName + "\",\"Password\":\"" +  Password + "\",\"IntegratorKey\":\"" +  IntegratorKey + "\"}";
        apiClient.addDefaultHeader("X-DocuSign-Authentication", creds);
        
        // assign api client to the Configuration object
        Configuration.setDefaultApiClient(apiClient);
        
        //===============================================================================
        // The Get Login method has 3 optional parameters which return additional information 
        // in the response: |api_password|, |account_id_guid|, and |login_settings|.  
        // |login settings| value can be "none" or "all"
        //===============================================================================
        List<DocuSign.Core.Model.LoginAccount> loginAccounts = null;
        
        try
        {
            // Login method resides in the UsersApi
            UsersApi usersApi = new UsersApi();
            
            // call the getLogin() API
            LoginInformation loginInfo = usersApi.getLogin(Boolean.TRUE, Boolean.FALSE, "none");
            
            loginAccounts = loginInfo.getLoginAccounts();
            
            System.out.println("LoginInformation: " + loginInfo);
        }
        catch (DocuSign.Core.Client.ApiException ex)
        {
            System.out.println("Exception: " + ex);
        }
    	
    	return loginAccounts;
    }
    
    
    //*****************************************************************
    //*****************************************************************
    // main() - 
    //*****************************************************************
    //*****************************************************************
    public static void main(String args[]) {
        
        DocuSignRecipes tc = new DocuSignRecipes();
        
        // Test #1
//        System.out.println("Running test #1...\n");
//        tc.Recipe01_RequestSignatureOnDocument();
//        System.out.println("\nTest #1 Complete.\n-----------------");
        
        // Test #2
//        System.out.println("Running test #2...\n");
//        tc.Recipe02_RequestSignatureFromTemplate();
//        System.out.println("\nTest #2 Complete.\n-----------------");
        
        // Test #3
//        System.out.println("Running test #3...\n");
//        tc.Recipe03_GetEnvelopeInformation();
//        System.out.println("\nTest #3 Complete.\n-----------------");
        
        // Test #4
//        System.out.println("Running test #4...\n");
//        tc.Recipe04_GetEnvelopeRecipientInformation();
//        System.out.println("\nTest #4 Complete.\n-----------------");
        
        // Test #5
        System.out.println("Running test #5...\n");
        tc.Recipe05_ListEnvelopes();
        System.out.println("\nTest #5 Complete.\n-----------------");
        
        // Test #6
//        System.out.println("Running test #6...\n");
//        tc.Recipe06_GetEnvelopeDocuments();
//        System.out.println("\nTest #6 Complete.\n-----------------");        
        
        // Test #7
//        System.out.println("Running test #7...\n");
//        tc.Recipe07_EmbeddedSending();
//        System.out.println("\nTest #7 Complete.\n-----------------");

        // Test #8
//        System.out.println("Running test #8...\n");
//        tc.Recipe08_EmbeddedSigning();
//        System.out.println("\nTest #8 Complete.\n-----------------");
        
        // Test #9
//        System.out.println("Running test #9...\n");
//        tc.Recipe09_EmbeddedConsole();
//        System.out.println("\nTest #9 Complete.\n-----------------");
        
    } // end main()
    
} // end class
