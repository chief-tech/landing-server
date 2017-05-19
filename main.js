// for HTTP
var io = require('socket.io').listen(8080);

// for SSL
// var fs = require('fs');
// var https = require('https');
// var express = require('express');
// var app = express();
// var options = {
//   key: fs.readFileSync('./passwords/ssl/domain.pem'),
//   cert: fs.readFileSync('./passwords/ssl/domain.crt')
// };
// // var serverPort = 443;
// var server = https.createServer(options, app);
// var io = require('socket.io')(server);
//
// server.listen(443);
//
// app.get('/', function (req, res) {
//   res.send('hello world');
// });
// end

var fs = require('fs');
var sql = require('./sql.js');
var verify = require('./verify.js');


// add a socket.io emitter for each of the following events
['warning', 'invalid-number', 'number-unavailable', 'user-added', 'photo-changed'].forEach(function(eventName){
  // processes error messages
  sql.events.addListener(eventName, function(data) {
    console.log(eventName + " says " + data.data);
    data.socket.emit(eventName, data.data);
  });
});

sql.events.addListener('user-added', function(data) {
  verify.sendMessage(data.data, "thanks for creating an account with Chief!");
  
  console.log("phone number is " + data.data);
});

// when we establish a new connection
io.sockets.on('connection', function (socket)
{
  socket.on('add-user', function (data)
  {
    sql.addUser(socket, data);
  });

  socket.on('change-photo', function(data)
  {
    sql.changePhoto(socket, data);
  });
});
