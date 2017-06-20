var mysql = require('mysql');
var fs = require('fs');
var sqlCredentials = require('/var/node/passwords/sql.json');
var eventLibrary = require('events');
var phone = require('/var/node/phone.js');
var https = require('https');
var facebook = require("/var/node/facebook.js");
var verify = require("/var/node/verify.js")

// setup event factory
var events = new eventLibrary.EventEmitter();

// connect to the mysql server
var connection = mysql.createConnection({
  host     : sqlCredentials['host'],
  user     : sqlCredentials['user'],
  password : sqlCredentials['password'],
  database : 'landing'
});

// connect to the server
connection.connect();

function randomInt(min, max) {
  min = Math.ceil(min);
  max = Math.floor(max);
  return Math.floor(Math.random() * (max - min)) + min;
}

var getVerificationCode = function(socket, database, phoneNumber, callback) {
  connection.query('SELECT VerificationCode FROM ' + database + ' WHERE PhoneNumber = ?', [phoneNumber], function(error, results, fields) {
    if (error) {
      callback(error, null);
      return;
    }

    if (results.length == 0) {
      callback(new Error("no phone number found matching the one sent"));
      return;
    }

    callback(null, results[0].VerificationCode);
    return;
  });
}

// we need to pass PhoneNumber and VerificationCode
var verifyNumber = function(socket, database, data) {
  connection.query('SELECT VerificationCode FROM ' + database + ' WHERE UserId = ?', [data.UserId], function(error, results, fields) {
    if (error) {
      socket.emit('warning', error.message);
      return;
    }

    if (results.length == 1) {
      // the user was successfully verified
      if (results[0].VerificationCode == data.VerificationCode)
      {
        console.log("looks like verification worked?");

        connection.query('UPDATE ' + database + ' SET Verified = ? WHERE UserId = ?', [true, data.UserId], function(error, results, fields) {
          // if there was an error updating the table
          if (error) {
            socket.emit('warning', error.message);
            return;
          }

          // the phone number was verified!
          socket.emit('phone-verified');
          return;
        });
      } // if there was an invalid verification code
      else {
        socket.emit('invalid-verification-code');
        return;
      }
    // since there were no results, the phone number does not exist
    } else {
      socket.emit('invalid-number');
      return;
    }
  });
};

var verifyUser = function(socket, database, data) {
  connection.query('SELECT Verified FROM ' + database + ' WHERE UserId = ?', [data.UserId], function(error, results, fields) {
    if (results.length == 1) {
      console.log(JSON.stringify(results));
      // if the phone number has been verified
      if (results[0].Verified == 1) {
        socket.emit('user-registered'); // tell the client that the user is registered
        return;
      // otherwise, the user is not registered
      } else {
        socket.emit('number-not-verified');
        return;
      }
    } else {
      socket.emit('incomplete-registration');
      return;
    }
  });
}

var sendVerificationCode = function(socket, database, data) {
  connection.query('SELECT PhoneNumber FROM ' + database + ' WHERE UserId = ?', [data.UserId], function(error, results, fields) {
    if (error) {
      socket.emit('warning', error.message);
      return;
    }

    if (results.length == 1) {
      var phoneNumber = results[0].PhoneNumber;

      // assume that the number is valid
      internationalPhoneNumber = phone.format(phoneNumber);
      e164PhoneNumber = phone.formatE164(phoneNumber);

      getVerificationCode(socket, database, internationalPhoneNumber, function(error, verificationCode) {
        if (error) {
          socket.emit('warning', error.message);
          return;
        }

        verify.sendMessage(e164PhoneNumber, "Thanks for creating a " + database + " account with Chief! Your verification code is: " + verificationCode);
      });
    } else {
      socket.emit('warning', 'a user with the given ID was not found.');
    }
  });
}

// calls back true if data was added, false if phone # was already present
var addUser = function(socket, database, data) {
  // make sure that the data contains the right keys
  ['PhoneNumber', 'LastName', 'FirstName', 'Birthday'].forEach(function(element){
    if (!(element in data))
    {
      socket.emit('warning', 'not all of the correct user data was sent');
      return;
    }
  });

  // the birthday can have hyphens in it, or no spaces
  if (!data.Birthday.match('^[0-9]{4}-[0-9]{2}-[0-9]{2}$')) {
    socket.emit('warning', 'birthday was incorrectly formatted');
    return;
  }

  // modify birthday to fit SQL format
  data.Birthday += " 00:00:00";

  data.ProfilePicturePath = "default.png"; // set the default user photo

  // if the phone number passed is not valid
  if (!phone.validate(data.PhoneNumber))
  {
    socket.emit('invalid-number');
    return;
  }

  // format the phone number to the international standard
  data.PhoneNumber = phone.format(data.PhoneNumber);

  // add a verification code to the phone number
  data.VerificationCode = (randomInt(100000, 999999)).toString();

  // note that the code has not been verified yet
  data.Verified = false;

  connection.query('INSERT INTO ' + database + ' SET ?', data, function(error, results, fields) {
    // if there was an error inserting, check to see if the phone number already exists
    if (error)
    {
      // check to see if the error was caused by lack of
      connection.query('SELECT PhoneNumber FROM ' + database + ' WHERE PhoneNumber = ?', [data.PhoneNumber], function(error, results, fields) {
        if (error)
        {
          socket.emit('warning', error.message);
          return;
        }

        if (results.length == 1) // if there was already an entry
        {
          socket.emit('number-unavailable'); //notify the user that there was already an entry
          return;
        }
        else { // there was some really serious problem
          socket.emit('warning', 'server-side problem with add user query');
          return;
        }
      });

    // if there was no error inserting
    } else {
      socket.emit('user-added'); //it all worked
      sendVerificationCode(socket, database, data);

      return;
    }
  });
}

// module.exports.changePhoto = changePhoto;
module.exports.addUser = addUser;
module.exports.events = events;
module.exports.verifyNumber = verifyNumber;
module.exports.getVerificationCode = getVerificationCode;
module.exports.verifyUser = verifyUser;
module.exports.sendVerificationCode = sendVerificationCode;
