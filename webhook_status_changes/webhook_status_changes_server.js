// Server for responding to [and include Notification Request] (Node.js)
// 
// To run this sample
//  1. Copy the file to your local machine and give .js extension (i.e. server.js)
//  2. Change "***" to appropriate values
//  3. Install packages: 
//     npm install express
//     npm install express-xml-bodyparser 
//     npm install fs
//  4. Execute
//     node server.js 


var express = require('express'),
	fs = require('fs'),
	path = require('path'),
    xmlparser = require('express-xml-bodyparser');

var app = express();

app.set('port', (process.env.PORT || 5000));

app.post('/', xmlparser({trim: false, explicitArray: false}), function(req, res, next) {

	console.log(JSON.stringify(req.body,null,2)); // converted using xml2js

	var envelopeId = req.body.docusignenvelopeinformation.envelopestatus.envelopeid;
	var filename = [envelopeId,'xml'].join('.');

	// Save raw XML to file
	var writePath = [__dirname,filename].join('/');
	fs.writeFile(writePath, req.rawBody, function (err,data) {
		if (err) {
			return console.error(err);
		}
		console.log('Envelope XML data written to:', writePath);
	});
		
	// Logs for your EventNotification webhooks are visible from Admin -> Connect (left menu, under Integrations) -> Logs (button in the top-right)
	// https://admindemo.docusign.com/connect-logs
 	res.send('DocuSign webhook endpoint reached!'); 
});

app.listen(app.get('port'), function() {
  console.log('DocuSign webhook listener app is running on port', app.get('port'));
});

