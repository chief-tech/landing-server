var Nexmo = require('nexmo');
var eventLibrary = require('events');
var nexmoCredentials = require('./passwords/nexmo.json');

// setup event factory
var events = new eventLibrary.EventEmitter();

// setup up nexmo texting
var nexmo = new Nexmo({
  apiKey: nexmoCredentials.key,
  apiSecret: nexmoCredentials.secret
});

function sendMessage(phoneNumber, message) {
  // convert phone number from international standard to

  nexmo.message.sendSms('12014646820', phoneNumber, message, function(error, results) {
    if (error) {
      events.emit('warning', error.message);
      return;
    }

    // status 0 means that the message was sent successfully
    if (results.messages[0].status == '0')
    {
      events.emit('message-sent');
      return;
    } else // if the status was not 0, something bad happened
    {
      events.emit('warning', 'unexpected message server response status');
      return;
    }
  });
}

events.addListener('warning', function(error) {
  console.log('error: ' + error);
});

events.addListener('message-sent', function(){
  console.log('message sent successfully!');
});


module.exports.sendMessage = sendMessage;
