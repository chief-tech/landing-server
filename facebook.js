var request = require('request');

var tokenToUserId = function(token, callback) {
  request('https://graph.facebook.com/me?access_token=' + token, function (error, response, body) {
    if (error) {
      console.log(error.message)
      callback(error, null);
      return;
    }

    console.log(body);

    responseObject = JSON.parse(body);

    if ("id" in responseObject) {
      callback(null, responseObject.id);
      return;
    } else {
      // there was an error
      callback(new Error("the token was invalid"), null);
      return;
    }
  });
}

module.exports.tokenToUserId = tokenToUserId;
