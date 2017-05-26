var fs = require('fs');
var facebook = require('./facebook');

var options = {
  key: fs.readFileSync('./passwords/ssl.key'),
  cert: fs.readFileSync('./passwords/ssl.crt'),
  ca: fs.readFileSync('./passwords/ssl-chain.crt')
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
var sql = require('./sql.js');
var verify = require('./verify.js');
var phone = require('./phone.js');


function processRequest(socket, data, callback) {
  // make sure that a token was sent with the data
  if (!("Token" in data)) {
    socket.emit('warning', 'token object not sent to server for user verification');
    console.log("request " + data + " failed because there was no token");
    return;
  }

  facebook.tokenToUserId(data.Token, function(error, userId) {
    if (error) {
      socket.emit('not-registered');
      console.log(error.message);
      return;
    }

    // change the token into the userId
    delete data.Token;
    data.UserId = userId;

    callback(socket, data);
  });
}

// when we establish a new connection
io.sockets.on('connection', function (socket)
{
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
