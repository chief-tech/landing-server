var mysql = require('mysql');
var fs = require('fs');
var sqlCredentials = require('./passwords/sql.json');
var events = require('events');
var phone = require('./phone.js');

var errorEvents = new events.EventEmitter();
var successEvents = new events.EventEmitter();

// setup all of the fun listners
errorEvents.addListener('invalid-number', function() {
  console.log("invalid phone number");
});

errorEvents.addListener('invalid-data', function() {
  console.log("invalid data");
});

errorEvents.addListener('number-unavailable', function() {
  console.log("the number you tried to register for has already been registered.");
});

errorEvents.addListener('general-error', function(description) {
  console.log("error: " + description);
});

successEvents.addListener('user-added', function() {
  console.log("user was added successfully");
});

successEvents.addListener('photo-changed', function() {
  console.log("photo was changed successfully");
});

// connect to the mysql server
sqlConnect();

// connect to the sql table
function sqlConnect()
{
    // setup connection specifics
    var connection = mysql.createConnection({
      host     : sqlCredentials['host'],
      user     : sqlCredentials['user'],
      password : sqlCredentials['password'],
      database : 'landing'
    });

    // connect to the server
    connection.connect();
}

// checks an object to make sure that it is valid
function validateData(data)
{
  // make sure that the data contains all the right keys
  ['PhoneNumber', 'LastName', 'FirstName', 'Birthday'].forEach(function(element){
    if (!(element in data))
    {
      return false;
    }
  });

  // make sure that all values are in the correct format

  return true;
}

// calls back true if data was added, false if phone # was already present
function addUser(connection, data) {
  // make sure that the data is valid
  if(!validateData(data)) // if the data is invalid
  {
    errorEvents.emit('invalid-data');
    return;
  }

  data["ProfilePicturePath"] = "default.png"; // set the default user photo

  // if the phone number passed is not valid
  if (!phone.validate(data.PhoneNumber))
  {
    errorEvents.emit('invalid-number');
    return;
  }

  // format the phone number to the international standard
  data.PhoneNumber = phone.format(data.PhoneNumber);

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
          errorEvents.emit('number-unavailable'); //notify the user that there was already an entry
          return;
        }
        else { // there was some really serious problem
          errorEvents.emit('general-error', 'server-side problem with add user query');
          return;
        }
      });

    // if there was no error inserting
    } else {
      successEvents.emit('user-added'); //it all worked
      return;
    }
  });
}

// returns true if photo number exists
function changePhoto(connection, phoneNumber, filepath)
{
  // if the phone number passed is not valid
  if (!phone.validate(phoneNumber))
  {
    errorEvents.emit('invalid-number');
    return;
  }

  // format the phone number to the international standard
  phoneNumber = phone.format(phoneNumber);

  connection.query('UPDATE Drivers SET ? WHERE PhoneNumber = ?', [{ProfilePicturePath: filepath}, phoneNumber], function(error, results, fields) {
    // if there was an error inserting, check to see if the phone number already exists
    if (error)
    {
      errorEvents.emit('general-error', 'query to the database failed');
      return;
    }

    if (results.affectedRows == 0) // if no rows were effected, the phone number does not exist
    {
      errorEvents.emit('general-error', 'the specified phone number has not been registered.');
      return;
    }
    else // if some rows were effected, the phone number does exist
    {
      successEvents.emit('photo-changed');
      return;
    }
  });
}


// sqlConnect(function(connection, err) {
//   if (err) {
//     return;
//   }
//
//   changePhoto(connection, "206 917 2306", "helloworld.txt");
//
//   // data = { PhoneNumber: "206 915 2366", FirstName: 'Isaac', LastName: 'Zinda', Birthday: '1998-05-02' };
//   // addUser(connection, data);
// });
