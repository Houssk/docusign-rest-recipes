//
//  API Walkthrough 4 - Request Signature on a Document (Objective-C)
//
//  To run this sample:
//      1.  Copy the below code into your iOS project
//      2.  Enter email, password, integrator key, name, and document name into variables and save
//      3.  Compile and Run
//

// Enter your info:
NSString *email = @"<#email#>";
NSString *password = @"<#password#>";
NSString *integratorKey = @"<#integratorKey#>";
NSString *name = @"<#recipientName#>";

// provide filename for document and make sure you copy file with same name into app directory
NSString *documentName = @"<#test.pdf#>";


- (void)requestSignatureOnDocument
{

    ///////////////////////////////////////////////////////////////////////////////////////
    // STEP 1 - Login (retrieves accountId and baseUrl)
    ///////////////////////////////////////////////////////////////////////////////////////
    
    // endpoint for the Login api
    NSString *loginURL = @"https://demo.docusign.net/restapi/v2/login_information";
    
    // initialize a request object with the following url, method, and body (no body for login api call)
    NSMutableURLRequest *loginRequest = [self initializeRequest:loginURL setMethod:@"GET" setBody:nil];
    
    // add content-type and authorization headers
    [self addRequestHeaders:loginRequest];
    
    //--> make an asynchronous web request
    [NSURLConnection sendAsynchronousRequest:loginRequest queue:[NSOperationQueue mainQueue] completionHandler:^(NSURLResponse *loginResponse, NSData *loginData, NSError *loginError) {
        
        // create NSDictionary out of JSON data that is returned
        NSDictionary *responseDictionary = [self getHttpResponse:loginRequest response:loginResponse data:loginData error:loginError];
        if( responseDictionary == nil)
            return;
        
        // parse the accountId and baseUrl from the response and use in the next request
        NSString *accountId = responseDictionary[@"loginAccounts"][0][@"accountId"];
        NSString *baseUrl = responseDictionary[@"loginAccounts"][0][@"baseUrl"];
        
        //--- display results
        NSLog(@"\naccountId = %@\nbaseUrl = %@\n", accountId, baseUrl);
        
        ///////////////////////////////////////////////////////////////////////////////////////
        // STEP 2 - Request Signature on Document
        ///////////////////////////////////////////////////////////////////////////////////////
        
        // append "/envelopes" URI to your baseUrl and use as endpoint for signature request call
        NSString *signatureURL = [NSMutableString stringWithFormat:@"%@/envelopes",baseUrl];
        
        // construct a JSON formatted signature request body (multi-line for readability)
        NSDictionary *signatureRequestData =
        @{@"accountId": accountId,
          @"emailSubject" : @"Signature Request on Document API call",
          @"emailBlurb" : @"email body",
          @"documents" : [NSArray arrayWithObjects: @{@"documentId":@"1", @"name": documentName}, nil ],
          @"recipients" : @{ @"signers": [NSArray arrayWithObjects:
                                          @{@"email": email,
                                            @"name": name,
                                            @"recipientId": @"1",
                                            @"tabs": @{ @"signHereTabs": [NSArray arrayWithObjects:
                                                                          @{@"xPosition": @"100",
                                                                            @"yPosition": @"100",
                                                                            @"documentId": @"1",
                                                                            @"pageNumber": @"1"}, nil ]}}, nil ] },
          @"status" : @"sent"
          };
        
        // convert dictionary object to JSON formatted string
        NSString *sigRequestDataString = [self jsonStringFromObject:signatureRequestData];
        
        // read document bytes and place in the request
        NSString *appDirectory = [[[NSBundle mainBundle] bundlePath] stringByDeletingLastPathComponent];
        NSString *fullFilePath = [NSString stringWithFormat:@"%@/%@", appDirectory, documentName];
        
        // make sure document exists in local app directory
        if( !([[NSFileManager defaultManager] fileExistsAtPath: fullFilePath]) )
        {
            NSLog(@"\n\nCould not find test document \"%@\".  Please copy this file into following app directory: %@\n\n", documentName, fullFilePath);
            return;
        }
        
        // use an NSData object to store the document bytes
        NSData *filedata = [NSData dataWithContentsOfFile:fullFilePath];
        
        // create the boundary separated request body...
        NSMutableData *body = [NSMutableData data];
        [body appendData:[[NSString stringWithFormat:
                           @"\r\n"
                           "\r\n"
                           "--AAA\r\n"
                           "Content-Type: application/json\r\n"
                           "Content-Disposition: form-data\r\n"
                           "\r\n"
                           "%@\r\n"
                           "--AAA\r\n"
                           "Content-Type: application/pdf\r\n"
                           "Content-Disposition: file; filename=\"%@\"; documentid=1; fileExtension=\"pdf\" \r\n"
                           "\r\n",
                           sigRequestDataString, documentName] dataUsingEncoding:NSUTF8StringEncoding]];
        
        // next append the document bytes
        [body appendData:filedata];
        
        // append closing boundary and CRLFs to the request
        [body appendData:[[NSString stringWithFormat:
                           @"\r\n"
                           "--AAA--\r\n"
                           "\r\n"] dataUsingEncoding:NSUTF8StringEncoding]];
        
        // initialize a request object with the following url, method, and body (no body for login api call)
        NSMutableURLRequest *signatureRequest = [self initializeRequest:signatureURL setMethod:@"POST" setBody:body];
        
        // add content-type and authorization headers
        [self addRequestHeaders:signatureRequest];
        
        // this is the only call where we set the content type to multipart/form-data and set a request boundary:
        [signatureRequest setValue:@"multipart/form-data; boundary=AAA" forHTTPHeaderField:@"Content-Type"];
        
        //*** make the signature request!
        [NSURLConnection sendAsynchronousRequest:signatureRequest queue:[NSOperationQueue mainQueue] completionHandler:^(NSURLResponse *envelopeResponse, NSData *envelopeData, NSError *envelopeError) {
            
            NSError *jsonError = nil;
            NSDictionary *responseDictionary = [NSJSONSerialization JSONObjectWithData:envelopeData options:kNilOptions error:&jsonError];
            
            //--- display results
            NSLog(@"Envelope Sent!  Response is: %@\n", responseDictionary);
        }];
    }];
}

//***********************************************************************************************
// --- HELPER FUNCTIONS ---
//***********************************************************************************************
- (NSMutableURLRequest *) initializeRequest:(NSString *) url setMethod:(NSString *) method setBody:(NSData *) body
{
    // create a request using the passed parameters for url, method, and body
    NSMutableURLRequest *request = [[NSMutableURLRequest alloc] init];
    [request setHTTPMethod:method];
    [request setHTTPBody:body];
    [request setURL:[NSURL URLWithString:url]];
    return request;
}
/////////////////////////////////////////////////////////////////////////////////////////////////
- (void) addRequestHeaders:(NSMutableURLRequest *) request
{
    // set JSON formatted X-DocuSign-Authentication header (XML format also accepted)
    NSDictionary *authenticationHeader = @{ @"Username": email, @"Password" : password, @"IntegratorKey" : integratorKey };
    
    // add the auth header to the request object
    [request setValue:[self jsonStringFromObject:authenticationHeader] forHTTPHeaderField:@"X-DocuSign-Authentication"];
    
    // also set the Content-Type header (other accepted type is application/xml)
    [request setValue:@"application/json" forHTTPHeaderField:@"Content-Type"];
}
/////////////////////////////////////////////////////////////////////////////////////////////////
- (NSDictionary *) getHttpResponse:(NSMutableURLRequest *) request response:(NSURLResponse *) response data:(NSData *) data error:(NSError *) error
{
    if (error) {
        NSLog(@"Error sending request %@\n. Got Response %@\n Error is: %@\n", request, response, error);
        return nil;
    }
    // we use NSJSONSerialization to parse the JSON formatted response
    NSError *jsonError = nil;
    NSDictionary *resp = [NSJSONSerialization JSONObjectWithData:data options:kNilOptions error:&jsonError];
    return resp;
}
/////////////////////////////////////////////////////////////////////////////////////////////////
- (NSString *)jsonStringFromObject:(id)object {
    NSString *string = [[NSString alloc] initWithData:[NSJSONSerialization dataWithJSONObject:object options:0 error:nil] encoding:NSUTF8StringEncoding];
    return string;
}