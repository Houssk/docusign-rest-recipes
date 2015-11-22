# DocuSign API Walkthrough 01 (PYTHON) - Request Signature from Template
import sys, httplib2, json;

# Enter your info:
username = "***";
password = "***";
integratorKey = "***";
templateId = "***";

authenticateStr = "<DocuSignCredentials>" \
                    "<Username>" + username + "</Username>" \
                    "<Password>" + password + "</Password>" \
                    "<IntegratorKey>" + integratorKey + "</IntegratorKey>" \
                    "</DocuSignCredentials>";
#
# STEP 1 - Login
#
url = 'https://demo.docusign.net/restapi/v2/login_information'   
headers = {'X-DocuSign-Authentication': authenticateStr, 'Accept': 'application/json'}
http = httplib2.Http()
response, content = http.request(url, 'GET', headers=headers)

status = response.get('status');
if (status != '200'): 
    print("Error calling webservice, status is: %s" % status); sys.exit();

# get the baseUrl and accountId from the response body
data = json.loads(content);
loginInfo = data.get('loginAccounts');
D = loginInfo[0];
baseUrl = D['baseUrl'];
accountId = D['accountId'];

#--- display results
print ("baseUrl = %s\naccountId = %s" % (baseUrl, accountId));

#
# STEP 2 - Create an Envelope with a Recipient and Send...
#

#construct the body of the request in JSON format  
requestBody = "{\"accountId\": \"" + accountId + "\"," + \
                "\"status\": \"sent\"," + \
                "\"emailSubject\": \"API Call for sending signature request from template\"," + \
                "\"emailBlurb\": \"This comes from Python\"," + \
                "\"templateId\": \"" + templateId + "\"," + \
                "\"templateRoles\": [{" + \
                "\"email\": \"" + username + "\"," + \
                "\"name\": \"Name\"," + \
                "\"roleName\": \"Role\" }] }";

# append "/envelopes" to baseURL and use in the request
url = baseUrl + "/envelopes";
headers = {'X-DocuSign-Authentication': authenticateStr, 'Accept': 'application/json'}
http = httplib2.Http()
response, content = http.request(url, 'POST', headers=headers, body=requestBody);
status = response.get('status');
if (status != '201'): 
    print("Error calling webservice, status is: %s" % status); sys.exit();
data = json.loads(content);
envId = data.get('envelopeId');

#--- display results
print ("Signature request sent!  EnvelopeId is: %s\n" % envId);
