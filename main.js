var express = require('express');
var path = require('path');
var app = express();
var io = require('socket.io');
app.use(express.static(path.join(__dirname, 'public')));




io.on('connection', function (socket) {
  socket.emit('news', { hello: 'world' });
  socket.on('my other event', function (data) {
    console.log(data);
  });
});
