var mysql = require('mysql');
var fs = require('fs');

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

function keyExists(key, connection, callback) {
  // "SELECT PhoneNumber FROM table WHERE PhoneNumber = \'" + key + "\'"
  connection.query('SELECT PhoneNumber FROM Drivers WHERE PhoneNumber = ?', ['206 915 2316'], function(err, rows, fields) {
    if (err) {
      console.error('unable to make query', err);
    }

    // if there is a key that corresponds to this phone #
    if (rows.length >= 1)
    {
      callback(true);
    }
    callback(false);
  });

}

sqlConnect(function(connection, err) {
  if (err) {
    return;
  }

  keyExists('206 915 2306', connection);

  

  connection.query('SELECT * FROM Drivers', function(err, rows, fields) {
    if (!err)
      console.log('The solution is: ', rows);
    else
      console.log('Error while performing Query.');
  });

  connection.end();
});
