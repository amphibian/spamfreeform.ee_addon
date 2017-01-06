**Spam-Freeform** is an ExpressionEngine add-on that runs submissions from Solspace's [Freeform module](http://www.solspace.com/software/detail/freeform/) through the [Akismet anti-spam service](https://akismet.com/).

### Requirements

* ExpressionEngine 1, 2, or 3 with Freeform installed
* PHP 5+ and cURL support
* An [Akismet API key](https://akismet.com/signup/)

### Usage

After activating Spam-Freeform and adding your Akismet API key to the Extension Settings, you need to add some hidden fields to your Freeform forms. These hidden fields tell the extension which content should be sent to Akismet for screening.

* **spamfreeform_name**: the field name who's content should be used as the submitter's name when sent to Akismet.
* **spamfreeform_email**: the field name who's content should be used as the submitter's email address when sent to Akismet.
* **spamfreeform_fields**: a pipe-delimited list of field names who's content should be used as the submitter's "comment" when sent to Akismet. This can be the cumulative contents of one or more fields.

If a submission is found to be spam by Akismet, the user will receive an error message indicating that their submission was deemed to be spam, and the form will not be submitted.

Any Freeform forms which don't have the **spamfreeform_fields** hidden field will simply bypass Akismet screening.

Here's an example Freeform form with Spam-Freeform fields added:

	{exp:freeform:form form_name="Contact Form" notify="user@domain.com" required="name|email|phone|comments|how-did-you-hear" return="contact/thanks" template="contact"}		
		<div>
			<label for="name">Your Full Name:</label>
			<input type="text" name="name" id="name" />
		</div>
		<div>
			<label for="email-address">Your Email Address:</label>
			<input type="text" name="email" id="email-address" />
		</div>
		<div>
			<label for="phone">Your Phone Number:</label>
			<input type="text" name="phone" id="phone" />
		</div>						
		<div>
			<label for="comments">Comments:</label>
			<textarea name="comments" id="comments"></textarea>
		</div>
		<div>
			<label for="how-did-you-hear">How did you hear about us?</label>
			<textarea name="how-did-you-hear" id="how-did-you-hear"></textarea>
		</div>
		<div>
			<input type="submit" value="Submit" />
			<input type="hidden" name="spamfreeform_name" value="name" />
			<input type="hidden" name="spamfreeform_email" value="email" />
			<input type="hidden" name="spamfreeform_fields" value="comments|how-did-you-hear" />
		</div>						
	{/exp:freeform:form}