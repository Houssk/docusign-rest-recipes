# DocuSign API Walkthrough 07 (PYTHON) - Embedded Sending
import sys, httplib2, json;

#enter your info:
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
url = 'https://demo.docusign.net/restapi/v2/login_information';   
headers = {'X-DocuSign-Authentication': authenticateStr, 'Accept': 'application/json'};
http = httplib2.Http();
response, content = http.request(url, 'GET', headers=headers);

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
# STEP 2 - Request Envelope Info
#

#construct the body of the request in JSON format  
requestBody = "{\"accountId\": \"" + accountId + "\"," + \
                "\"status\": \"created\"," + \
                "\"emailSubject\": \"API Call for sending signature request from template\"," + \
                "\"emailBlurb\": \"This comes from Python\"," + \
                "\"templateId\": \"" + templateId + "\"," + \
                "\"templateRoles\": [{" + \
                "\"email\": \"" + username + "\"," + \
                "\"name\": \"John Doe\"," + \
                "\"roleName\": \"Role\" }] }";

# append "/envelopes" to baseURL and use in the request
url = baseUrl + "/envelopes";
headers = {'X-DocuSign-Authentication': authenticateStr, 'Accept': 'application/json'};
http = httplib2.Http();
response, content = http.request(url, 'POST', headers=headers, body=requestBody);
status = response.get('status');
if (status != '201'): 
    print("Error calling webservice, status is: %s" % status); sys.exit();
data = json.loads(content);

# store the uri for next request
uri = data.get('uri');

#
# STEP 3 - Get the Embedded Send View
#

#construct the body of the request in JSON format  
requestBody =   "{ \"authenticationMethod\": \"email\"," + \
                "\"email\": \"" + username + "\"," + \
                "\"returnUrl\": \"http://www.docusign.com\"," + \
                "\"userName\": \"John Doe\"," + \
                "\"clientUserId\": \"1\" }";

# append uri + "/views/sender" to the baseUrl and use in the request
url = baseUrl + uri + "/views/sender";
headers = {'X-DocuSign-Authentication': authenticateStr, 'Accept': 'application/json'};
http = httplib2.Http();
response, content = http.request(url, 'POST', headers=headers, body=requestBody);
status = response.get('status');
if (status != '201'): 
    print("Error calling webservice, status is: %s" % status); sys.exit();
data = json.loads(content);
viewUrl = data.get('url');

#--- display results
print ("View URL = %s\n" % viewUrl)
