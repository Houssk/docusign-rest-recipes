# DocuSign API Walkthrough 06 (PYTHON) - Retrieve Envelope's Document List and Download documents
import sys, httplib2, json;

#enter your info:
username = "***";
password = "***";
integratorKey = "***";
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
# STEP 2 - Get Envelope Document(s) Info and Download Documents
#

# append envelopeUri to baseURL and use in the request
url = baseUrl + envelopeUri + "/documents";
headers = {'X-DocuSign-Authentication': authenticateStr, 'Accept': 'application/json'};
http = httplib2.Http();
response, content = http.request(url, 'GET', headers=headers);
status = response.get('status');
if (status != '200'): 
    print("Error calling webservice, status is: %s" % status); sys.exit();
data = json.loads(content);

envelopeDocs = data.get('envelopeDocuments');
uriList = [];
for docs in envelopeDocs:
    # print document info
    uriList.append(docs.get("uri"));
    print("Document Name = %s, Uri = %s" % (docs.get("name"), uriList[len(uriList)-1]));
    
    # download each document
    url = baseUrl + uriList[len(uriList)-1];
    headers = {'X-DocuSign-Authentication': authenticateStr};
    http = httplib2.Http();
    response, content = http.request(url, 'GET', headers=headers);
    status = response.get('status');
    if (status != '200'): 
        print("Error calling webservice, status is: %s" % status); sys.exit();
    fileName = "doc_" + str(len(uriList)) + ".pdf";
    file = open(fileName, 'w');
    file.write(content);
    file.close();

print ("\nDone downloading document(s)!\n");
