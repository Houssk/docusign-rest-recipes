# DocuSign API Walkthrough 04 (PYTHON) - Add Signature Request to Document and Send
import sys, httplib2, json;

# Enter your info:
username = "***";
password = "***";
integratorKey = "***";

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
# STEP 2 - Add Signature Request to Document and Send
#

#construct the body of the request in JSON format
envelopeDef =   "{\"emailBlurb\":\"This comes from Python\"," + \
                "\"emailSubject\":\"API Call for adding signature request to document and sending\"," + \
                "\"documents\":[{" + \
                "\"documentId\":\"1\"," + \
                "\"name\":\"test_doc.txt\"}]," + \
                "\"recipients\":{" + \
                "\"signers\":[{" + \
                "\"email\":\"" + username + "\"," + \
                "\"name\":\"Name\"," + \
                "\"recipientId\":\"1\"," + \
                "\"tabs\":{" + \
                "\"signHereTabs\":[{" + \
                "\"xPosition\":\"100\"," + \
                "\"yPosition\":\"100\"," + \
                "\"documentId\":\"1\"," + \
                "\"pageNumber\":\"1\"" + "}]}}]}," + \
                "\"status\":\"sent\"}";

# convert the file into a string and add to the request body
fileContents = open("test_doc.txt", "r").read();

requestBody = "\r\n\r\n--BOUNDARY\r\n" + \
                "Content-Type: application/json\r\n" + \
                "Content-Disposition: form-data\r\n" + \
                "\r\n" + \
                envelopeDef + "\r\n\r\n--BOUNDARY\r\n" + \
                "Content-Type: text/plain\r\n" + \
                "Content-Disposition: file; filename=\"test_doc.txt\"; documentId=1\r\n" + \
                "\r\n" + \
                fileContents + "\r\n" + \
                "--BOUNDARY--\r\n\r\n";

# append "/envelopes" to the baseUrl and use in the request
url = baseUrl + "/envelopes";
headers = {'X-DocuSign-Authentication': authenticateStr, 'Content-Type': 'multipart/form-data; boundary=BOUNDARY', 'Accept': 'application/json'};
http = httplib2.Http();
response, content = http.request(url, 'POST', headers=headers, body=requestBody);
status = response.get('status');
if (status != '201'): 
    print("Error calling webservice, status is: %s\nError description - %s" % (status, content)); sys.exit();
data = json.loads(content);
envId = data.get('envelopeId');

#--- display results
print ("Document sent!  EnvelopeId is: %s\n" % envId);