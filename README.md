# be.werkmetzin.wpcivi
Native CiviCRM extension for Werk met Zin to receive Wordpress form data. Code is specific to customer needs but framework can be copied and fitted to new situation.
Based on API call WpCivi Submit from Wordpress with form data as key/value pairs.

# Wordpress part
This extension expects to receive an CiviCRM API call (Entity = WpCivi, Action = submit). This will probably be a REST call from a Wordpress site. 
That part is **not** required but is the use case it is developed for. The customer for whom it was developed (Werk met Zin, thank you for the funding!) has
a Wordpress site with a form created with Contact Form 7 and uses the _Contact Form 7 CiviCRM integration_ plugin to link with CiviCRM. You can
find this plugin on https://wordpress.org/plugins/contact-form-7-civicrm-integration/

#CiviCRM part
The API WpCivi Submit expects an incoming array _($params)_ with an element _form_type_. It also expects to find a handler class with the name of this
formtype in the extension. So currenty it can deal with a form which has form_type CoachingIndividual and the extension has the handler class
CRM_Wpcivi_CoachingIndividual. This class extends the abstract class CRM_Wpcivi_ApiHandler and has to implement the method _processParams_.
In this method the actual form data is processed. In our example, a contact is created and an activity of a specific type (with custom data) is created.
If you would like to use this approach as a basis you could remove the CoachingIndividual handler and add your own handler(s).
 
#Note
In this extension you will also find some classes with configuration items as the activity types, option groups and custom groups/fields are created/updtaed based on JSON files.

