var Nexmo = require('nexmo');
var nexmoCredentials = require('./passwords/nexmo.json');

// setup up nexmo texting
var nexmo = new Nexmo({
    apiKey: nexmoCredentials.key,
    apiSecret: nexmoCredentials.secret
  });

  

nexmo.message.sendSms('12014646820', '16179550175', 'Sidd is good.', function(error, results) {
  if (error)
  {
    console.log(error);
    return;
  }

  // status 0 means that the message was sent successfully
  if (results.messages[0].status == '0')
  {
    console.log("message send success");
    return;
  }
  // if the status was not 0, something bad happened
  else
  {
    console.log("error sending message");
    return;
  }
});
