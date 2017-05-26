var http = require('http');
var querystring = require('querystring');

var queryParameters = {
  "client_id": "767877510042378",
  "redirect_uri": "https://driver.letschief.com/driverSignUp.html",
  "scope": "public_profile"
}

var options = {
  host: 'https://graph.facebook.com',
  path: '/oauth/authorize?' + querystring.stringify(queryParameters)
};

console.log(options.host + options.path);

http.get(options, function(resp){
  resp.on('data', function(chunk){
    console.log(chunk);
  });
}).on("error", function(e){
  console.log("Got error: " + e.message);
});

// ?
//    client_id=123456789
//    &redirect_uri=http://example.com/
//    &scope=publish_stream,share_item,offline_access,manage_pages
