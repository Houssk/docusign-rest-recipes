# DocuSign-Recipes
Source files for the recipes

Notice a bug or issue? Please submit an issue report. Or even better, quality pull requests are always appreciated!

## Sending
### Send a signing request via email
Requests a signature on your document. An email is sent from the DocuSign platform to start the signing process. The signer does not need an account from DocuSign.

* core_recipes directory for Java, C#, and Objective-C files
* request_sig_via_email directory for other languages

### Send a signing request with a template via email
This recipe uses a template to send a signing request. A specific signer will be substituted for the *role name* that was used in the template.

* core_recipes directory for Java, C#, and Objective-C files
* request_sig_via_email_w_template directory for other languages

### Signing from within your app
This recipe enables your app’s user to sign a specific envelope’s document(s) from within your app.

* core_recipes directory for Java, C#, and Objective-C files
* signing_from_within_your_app directory for other languages

## Get status and documents
### Get Envelope status
This recipe retrieves an envelope’s current status. The Envelope::get method returns all current information about the envelope including its status (Created, Deleted, Sent, Delivered, Signed, Completed, Declined, etc), the time and date of the most recent action on the envelope, and other information.

* core_recipes directory for Java, C#, and Objective-C files
* get_envelope_status directory for other languages

### Recipients’ statuses (including signers’ statuses)
This recipe retrieves an envelope’s current recipient status. Envelopes have statuses and so do Recipients. For example, an envelope has two recipients, the first has signed the document, but the second recipient has declined to sign. In this case the first recipient’s status would be signed, the second recipient’s status would be declined, and the envelope’s status is declined (since one recipient has declined).

* core_recipes directory for Java, C#, and Objective-C files
* get_envelope_recipient_status directory for other languages

### Tracking status changes from behind the firewall
You’ve sent some documents for signing. How should your app automatically track the envelope’s (or envelopes’) progress and status changes from behind your firewall? You can poll the DocuSign platform or use DocuSign Connect to be notified whenever an event occurs. This recipe shows how to poll for status changes.

* core_recipes directory for Java, C#, and Objective-C files
* polling_status_changes directory for other languages

### Downloading documents
This recipe retrieves a list of an envelope’s documents and then downloads the documents.

* core_recipes directory for Java, C#, and Objective-C files
* download_documents directory for other languages

## Embedding the DocuSign User Experience
### Embed the Tag and Send UX
This recipe enables your app’s user to “tag and send” a specific envelope’s document(s) from within your app.

Use this technique when your app users want to use the DocuSign graphical user interface to place tags on the envelope’s document or documents.

* core_recipes directory for Java, C#, and Objective-C files
* embed_the_tag_and_send_ux directory for other languages

### The “Open DocuSign” button: embed the console
This recipe enables your app’s user to open the DocuSign console from within your app. As an option, the console can be opened focused on a specific envelope.

This recipe enables you to easily add an “Open DocuSign” button to your app, providing easy access to the console’s features for your users.

* core_recipes directory for Java, C#, and Objective-C files
* embed_the_console directory for other languages


