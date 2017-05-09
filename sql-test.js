var mysql = require('mysql');
var fs = require('fs');

// Require `PhoneNumberFormat`.
var PNF = require('google-libphonenumber').PhoneNumberFormat;
var phoneUtil = require('google-libphonenumber').PhoneNumberUtil.getInstance();


function sqlConnect(callback)
{
  fs.readFile('sql.json', 'utf8', function (err, data) {
    if (err) {
      console.error('unable to open sql passwords file', err);
      return;
    }

    // setup connection specifics
    var sqlInfo = JSON.parse(data);
    var connection = mysql.createConnection({
      host     : sqlInfo['host'],
      user     : sqlInfo['user'],
      password : sqlInfo['password'],
      database : 'landing'
    });

    // connect to the server
    connection.connect(function (err) {
      if (err) {
        console.error('unable to connect to mySql', err);
        return;
      }

      // if everything has gone well, callback with the open connection
      callback(connection);
    });
  });
}

// returns null if the phone number is invalid
function condensePhoneNumber(phoneNumber)
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
function checkPhoneNumber(phoneNumber)
{
  // if the phone number is null
  if (!condensePhoneNumber(phoneNumber))
  {
    return false;
  }
  return true;
}

// calls back true if data was added, false if phone # was already present
function addData(data, connection, callback) {
  connection.query('INSERT INTO Drivers SET ?', data, function(error, results, fields) {
    // if there was an error inserting, check to see if the phone number already exists
    if (error)
    {
      // check to see if the error was caused by lack of
      connection.query('SELECT PhoneNumber FROM Drivers WHERE PhoneNumber = ?', [data.PhoneNumber], function(error, results, fields) {
        if (error)
        {
          callback(error);
          return;
        }

        if (results.length == 1) // if there was already an entry
        {
          callback(null, false); //notify the user that there was already an entry
          return;
        }
        else { // there was some really serious problem
          throw new Error("there was a really serious problem");
          return;
        }
      });

    // if there was no error inserting
    } else {
      callback(null, true); //it all worked
      return;
    }
  });
}


//etSupportedRegions();
console.log(checkPhoneNumber('+49 206 915 2306'));
console.log(checkPhoneNumber('+4 206 915 2306'));
console.log(checkPhoneNumber('206 915 2306'));
console.log(checkPhoneNumber('206-915-2306'));
console.log(checkPhoneNumber('12069152306'));


// sqlConnect(function(connection, err) {
//   if (err) {
//     return;
//   }
//
//   data = { PhoneNumber: "206 915 2436", FirstName: 'Isaac', LastName: 'Zinda', Birthday: '1998-05-02' };
//
//   addData(data, connection, function(err, added) {
//     console.log(err);
//     console.log(added);
//
//     // if (err)
//     // {
//     //   console.error(err);
//     //   return;
//     // }
//     // else
//     // {
//     //   console.log(added);
//     // }
//   });
// });
