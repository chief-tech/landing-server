// Require `PhoneNumberFormat`.
var PNF = require('google-libphonenumber').PhoneNumberFormat;
var phoneUtil = require('google-libphonenumber').PhoneNumberUtil.getInstance();

// returns null if the phone number is invalid
var format = function(phoneNumber)
{
  // parse the phone number as a US number
  var parsedPhoneNumber = phoneUtil.parse(phoneNumber, 'US')

  if (phoneUtil.isValidNumber(parsedPhoneNumber)) // check to see if we can dial this number from the US
  {
    // convert it into a standard international number
    return phoneUtil.format(parsedPhoneNumber, PNF.INTERNATIONAL);
  }
  else // otherwise the number is invalid
  {
    return null;
  }
}

// checks is a phone number is valid
var validate = function(phoneNumber)
{
  // if the phone number is null
  if (!format(phoneNumber))
  {
    return false;
  }
  return true;
}

// export the functions
exports.validate = validate;
exports.format = format;
