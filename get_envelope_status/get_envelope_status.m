//
//  API Walkthrough 2 - Get Envelope Information (Objective-C)
//
//  To run this sample:
//      1.  Copy the below code into your iOS project
//      2.  Enter email, password, integrator key, and the envelopeId of an existing envelope in your account
//      3.  Compile and Run
//

// Enter your info:
NSString *email = @"<#email#>";
NSString *password = @"<#password#>";
NSString *integratorKey = @"<#integratorKey#>";
NSString *envelopeId = @"<#envelopeId#>";

- (void)getEnvelopeInfo
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
        // STEP 2 - Get Envelope Info
        ///////////////////////////////////////////////////////////////////////////////////////
        
        // append "/envelopes/{envelopeId}" URI to your baseUrl and use as endpoint for next request
        NSString *envelopesURL = [NSMutableString stringWithFormat:@"%@/envelopes/%@",baseUrl, envelopeId];
        
        // initialize a request object with the following url, method, and body (no body for login api call)
        NSMutableURLRequest *envelopeRequest = [self initializeRequest:envelopesURL setMethod:@"GET" setBody:nil];
        
        // add content-type and authorization headers
        [self addRequestHeaders:envelopeRequest];

        //*** make the request!
        [NSURLConnection sendAsynchronousRequest:envelopeRequest queue:[NSOperationQueue mainQueue] completionHandler:^(NSURLResponse *envelopeResponse, NSData *envelopeData, NSError *envelopeError) {
            
            NSError *jsonError = nil;
            NSDictionary *responseDictionary = [NSJSONSerialization JSONObjectWithData:envelopeData options:kNilOptions error:&jsonError];
            NSLog(@"Envelope info received is: %@\n", responseDictionary);
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