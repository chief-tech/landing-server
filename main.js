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


// processes error messages
sql.events.addListener('warning', function(data) {
  var socket = data.socket;
  delete data.socket; //make sure socket it not sent
  console.error(data);

  if (data.data) {
    socket.emit('warning', data);
  } else {
    socket.emit('warning', data.name);
  }
});

// processes info messages
sql.events.addListener('info', function(data) {
  var socket = data.socket;
  delete data.socket; //make sure that socket is not sent
  console.log(data);

  if (data.data) {
    socket.emit('info', data);
  } else {
    socket.emit('info', data.name);
  }
});


// when we establish a new connection
io.sockets.on('connection', function (socket)
{
  socket.emit('request-add-user'); // tests the add user functionality

  socket.on('add-user', function (data)
  {
    sql.addUser(socket, data);
  });

  socket.on('change-photo', function(data)
  {
    sql.changePhoto(socket, data);
  });
});
