var casper = require("casper").create();

var fs = require('fs');
var utils = require('utils');
casper.userAgent('Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)');
phantom.cookiesEnabled = true;

var domain = ".amazon"+casper.cli.get("extension");


var length = Object.keys(casper.cli.options).length;
//casper.echo(Object.keys(casper.cli.options));
for (var i=0;i<length;i++)
{
  var key = Object.keys(casper.cli.options)[i];
  if (key != 'url' && key != 'extension' && key != 'casper-path'){ 
	  phantom.addCookie({
	    'name':     key,   
	    'value':    casper.cli.get(key),  
	    'domain':   domain,           
	    'expires':  (new Date()).getTime() + (1000 * 60 * 60)   
	  });
  }
}

//casper.echo(JSON.stringify(phantom.cookies));
//casper.echo(casper.cli.get("url"));
casper.start(casper.cli.get("url"), function() {
   casper.echo(this.getHTML());
});


casper.run();
