# AjaxFileUpload Field

Author: Jeremy Shipman <jeremy@burnbright.net>

## About

Ajax image uploading via javascript.

Rolls back to an image upload field, to be submitted on form submission.

Currently requires jQuery

## TODO

 * Write tests
 * XmlHttpRequest uploading should be incorporated into SilverStripe core at some stage. (see XHRUpload_Validator)
 * Ability to reference via javascript
 * Allow setting custom configs params eg minSizeLimit
 * override showMessage
 * display errors in validation span
 * require jquery, or remove dependency
 
 Handle scenarios
 
  * Upload to location...allowing for repeated uploads
  * multiple file upload