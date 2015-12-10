//
// to run this sample
//  1. copy the file in your own directory - say, example.js
//  2. change "***" to appropriate values
//  3. install async and request packages
//     npm install async
//     npm install request
//  4. execute
//     node example.js 
//

var 	async = require("async"),		// async module
    	request = require("request"),		// request module
    	email = "***",				// your account email
    	password = "***",			// your account password
    	integratorKey = "***",			// your account Integrator Key (found on Preferences -> API page)
    	baseUrl = ""; 				// we will retrieve this

async.waterfall(
[
	//////////////////////////////////////////////////////////////////////
	// Step 1 - Login (used to retrieve accountId and baseUrl)
	//////////////////////////////////////////////////////////////////////
	function(next) {
		var url = "https://demo.docusign.net/restapi/v2/login_information";
		var body = "";	// no request body for login api call
		
		// set request url, method, body, and headers
		var options = initializeRequest(url, "GET", body, email, password);
		
		// send the request...
		request(options, function(err, res, body) {
			if(!parseResponseBody(err, res, body)) {
				return;
			}
			baseUrl = JSON.parse(body).loginAccounts[0].baseUrl;
			next(null); // call next function
		});
	},
	
	//////////////////////////////////////////////////////////////////////
	// Step 2 - Get Statuses of a set of Envelopes (for last month)
	//////////////////////////////////////////////////////////////////////
	function(next) {
		// determine date for 1 month back in time.  we will get statuses of 
		// all envelopes during this time...
		var date = new Date();
		var curr_day = date.getDate();
		var curr_month = date.getMonth();
		var curr_year = date.getFullYear();
		if( curr_month != 0 ) 
			curr_month--;
		else { // special case if in January
			curr_month = 12;
			curr_year -= 1;
		}

		var url  = baseUrl + "/envelopes?from_date=" + curr_month + "%2F" + curr_day + "%2F" + curr_year;
		var body = "";	// no request body needed for this api call
		
		// set request url, method, body, and headers
		var options = initializeRequest(url, "GET", body, email, password);
		
		// send the request...
		request(options, function(err, res, body) {
			parseResponseBody(err, res, body);
		});
    }]
);

//***********************************************************************************************
// --- HELPER FUNCTIONS ---
//***********************************************************************************************
function initializeRequest(url, method, body, email, password) {	
	var options = {
		"method": method,
		"uri": url,
		"body": body,
		"headers": {}
	};
	addRequestHeaders(options, email, password);
	return options;
}

///////////////////////////////////////////////////////////////////////////////////////////////
function addRequestHeaders(options, email, password) {	
	// JSON formatted authentication header (XML format allowed as well)
	dsAuthHeader = JSON.stringify({
		"Username": email,
		"Password": password, 
		"IntegratorKey": integratorKey	// global
	});
	// DocuSign authorization header
	options.headers["X-DocuSign-Authentication"] = dsAuthHeader;
}

///////////////////////////////////////////////////////////////////////////////////////////////
function parseResponseBody(err, res, body) {
	console.log("\r\nAPI Call Result: \r\n", JSON.parse(body));
	if( res.statusCode != 200 && res.statusCode != 201)	{ // success statuses
		console.log("Error calling webservice, status is: ", res.statusCode);
		console.log("\r\n", err);
		return false;
	}
	return true;
}
