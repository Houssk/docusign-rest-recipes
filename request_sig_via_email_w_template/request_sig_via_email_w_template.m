//
//  API Walkthrough 1 - Signature Request via Template (Objective-C)
//
//  To run this sample:
//      1.  Copy the below code into your iOS project
//      2.  Enter email, password, integrator key, name, templateId, and roleName and save
//      3.  Compile and Run
//

// Enter your info:
NSString *email = @"<#email#>";
NSString *password = @"<#password#>";
NSString *integratorKey = @"<#integratorKey#>";
NSString *name = @"<#recipientName#>";
// use same name as template role you saved through the Console UI
NSString *roleName = @"<#roleName#>";
// need to login to the console and copy a valid templateId into this string
NSString *templateId = @"<#templateId#>";

- (void)requestSignatureFromTemplate
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
        // STEP 2 - Request Signature via Template
        ///////////////////////////////////////////////////////////////////////////////////////
        
        // construct a JSON formatted signature request body (multi-line for readability)
        NSDictionary *signatureRequestData = @{@"accountId": accountId,
                                               @"emailSubject" : @"DocuSign API - Request Signature from Template",
                                               @"templateId" : templateId,
                                               @"templateRoles" : [NSArray arrayWithObjects: @{@"email":email, @"name": name, @"roleName": roleName }, nil ],
                                               @"status" : @"sent"
                                               };
        
        // convert request body into an NSData object
        NSData* data = [[self jsonStringFromObject:signatureRequestData] dataUsingEncoding:NSUTF8StringEncoding];
        
        // append "/envelopes" URI to your baseUrl and use as endpoint for signature request call
        NSString *envelopesURL = [NSMutableString stringWithFormat:@"%@/envelopes",baseUrl];
        
        // initialize a request object with the following url, method, and body (no body for login api call)
        NSMutableURLRequest *signatureRequest = [self initializeRequest:envelopesURL setMethod:@"POST" setBody:data];
        
        // add content-type and authorization headers
        [self addRequestHeaders:signatureRequest];
        
        //--> make the signature request!
        [NSURLConnection sendAsynchronousRequest:signatureRequest queue:[NSOperationQueue mainQueue] completionHandler:^(NSURLResponse *envelopeResponse, NSData *envelopeData, NSError *envelopeError) {
            
            NSDictionary *responseDictionary = [self getHttpResponse:signatureRequest response:envelopeResponse data:envelopeData error:envelopeError];
            NSLog(@"\nEnvelope sent!  Response is:\n%@\n", responseDictionary);
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
    return [NSJSONSerialization JSONObjectWithData:data options:kNilOptions error:&jsonError];
}
/////////////////////////////////////////////////////////////////////////////////////////////////
- (NSString *)jsonStringFromObject:(id)object {
    NSString *string = [[NSString alloc] initWithData:[NSJSONSerialization dataWithJSONObject:object options:0 error:nil] encoding:NSUTF8StringEncoding];
    return string;
}