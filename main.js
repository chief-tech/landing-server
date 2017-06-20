var fs = require('fs');
var facebook = require('/var/node/facebook');

var options = {
  key: fs.readFileSync('/var/node/passwords/ssl.key'),
  cert: fs.readFileSync('/var/node/passwords/ssl.crt'),
  ca: fs.readFileSync('/var/node/passwords/ssl-chain.crt')
};

var app = require('https').createServer(options, handler)
  , io = require('socket.io').listen(app);

app.listen(4433);

//for testing
function handler (req, res) {
    res.writeHead(200);
    res.end("welcome to ebidnsave\n");
}

// var fs = require('fs');
var sql = require('/var/node/sql.js');
var verify = require('/var/node/verify.js');
var phone = require('/var/node/phone.js');


function processRequest(socket, data, callback) {
  // make sure that a token was sent with the data
  if (!("Token" in data)) {
    socket.emit('warning', 'token object not sent to server for user verification');
    console.log("request " + data + " failed because there was no token");
    return;
  }

  if (!("Database" in data)) {
    socket.emit('warning', 'no destination database was specified');
    return;
  }

  facebook.tokenToUserId(data.Token, function(error, userId) {
    if (error) {
      socket.emit('not-registered');
      console.log(error.message);
      return;
    }

    database = data.Database;
    delete data.Database;

    // change the token into the userId
    delete data.Token;
    data.UserId = userId;

    callback(socket, database, data);
  });
}

// when we establish a new connection
io.sockets.on('connection', function (socket)
{
  console.log("connected")

  socket.on('add-user', function (data)
  {
    processRequest(socket, data, sql.addUser);
  });

  socket.on('verify-number', function(data)
  {
    processRequest(socket, data, sql.verifyNumber);
  });

  socket.on('verify-user', function(data)
  {
    processRequest(socket, data, sql.verifyUser);
  });

  socket.on('send-verification-code', function(data)
  {
    processRequest(socket, data, sql.sendVerificationCode);
  });
});
