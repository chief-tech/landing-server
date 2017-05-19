var mysql = require('mysql');
var fs = require('fs');
var sqlCredentials = require('./passwords/sql.json');
var eventLibrary = require('events');
var phone = require('./phone.js');

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


// creates an object from some data and a socket
function toEventObject(socket, data) {
  return {"socket": socket, "data": data};
}

// calls back true if data was added, false if phone # was already present
var addUser = function(socket, data) {
  // make sure that the data contains the right keys
  ['PhoneNumber', 'LastName', 'FirstName', 'Birthday'].forEach(function(element){
    if (!(element in data))
    {
      events.emit('warning', toEventObject(socket, 'not all of the correct user data was sent'));
      return;
    }
  });

  // make sure that birthday is 8 numbers long
  if (!data.Birthday.match('^[0-9]{8}$')) {
    events.emit('warning', toEventObject(socket, 'birthday was incorrectly formatted'));
    return;
  }


  data["ProfilePicturePath"] = "default.png"; // set the default user photo

  // if the phone number passed is not valid
  if (!phone.validate(data.PhoneNumber))
  {
    events.emit('invalid-number', toEventObject(socket, null));
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
          events.emit('warning', toEventObject(socket, error.message));
          return;
        }

        if (results.length == 1) // if there was already an entry
        {
          events.emit('number-unavailable', toEventObject(socket, null)); //notify the user that there was already an entry
          return;
        }
        else { // there was some really serious problem
          events.emit('warning', toEventObject(socket, 'server-side problem with add user query'));
          return;
        }
      });

    // if there was no error inserting
    } else {
      events.emit('user-added', toEventObject(socket, phone.formatE164(data.PhoneNumber))); //it all worked
      return;
    }
  });
}

// returns true if photo number exists
var changePhoto = function(socket, data)
{
  // make sure the correct values are in the data
  if (!("ProfilePicturePath" in data && "PhoneNumber" in data))
  {
    events.emit('warning', toEventObject(socket, 'not all of the correct data was sent to change the photo'));
  }

  // if the phone number passed is not valid
  if (!phone.validate(data.PhoneNumber))
  {
    events.emit('invalid-number', toEventObject(socket, null));
    return;
  }

  // format the phone number to the international standard
  phoneNumber = phone.format(data.PhoneNumber);
  filepath = data.ProfilePicturePath;

  connection.query('UPDATE Drivers SET ? WHERE PhoneNumber = ?', [{ProfilePicturePath: filepath}, phoneNumber], function(error, results, fields) {
    // if there was an error inserting, check to see if the phone number already exists
    if (error)
    {
      events.emit('warning', toEventObject(socket, 'was unable to connect to the mysql database'));
      return;
    }

    if (results.affectedRows == 0) // if no rows were effected, the phone number does not exist
    {
      events.emit('invalid-number', toEventObject(socket, null));
      return;
    }
    else // if some rows were effected, the phone number does exist
    {
      events.emit('photo-changed', toEventObject(socket, null));
      return;
    }
  });
}

module.exports.changePhoto = changePhoto;
module.exports.addUser = addUser;
module.exports.events = events;
