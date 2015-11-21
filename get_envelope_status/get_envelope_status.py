# DocuSign API Walkthrough 02 (PYTHON) - Get Envelope Information
import sys, httplib2, json;

#enter your info:
username = "***";
password = "***";
integratorKey = "***";
templateId = "***";
envelopeId = "***";
envelopeUri = "/envelopes/" + envelopeId;

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

# append envelopeUri to baseURL and use in the request
url = baseUrl + envelopeUri;
headers = {'X-DocuSign-Authentication': authenticateStr, 'Accept': 'application/json'};
http = httplib2.Http();
response, content = http.request(url, 'GET', headers=headers);
status = response.get('status');
if (status != '200'): 
    print("Error calling webservice, status is: %s" % status); sys.exit();
data = json.loads(content);
status = data.get('status');
sentTime = data.get('sentDateTime');

#--- display results
print ("Status is: %s\nSent Time is: %s" % (status, sentTime));
