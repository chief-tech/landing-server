var io = require('socket.io').listen(8080);
var fs = require('fs');


io.sockets.on('connection', function (socket) {
  socket.emit('news', { hello: 'world' });



  socket.on('add-user', function (data)
  {
    console.log(data);
  });
});
