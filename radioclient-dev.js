// Copyright 2009 Srdjan Rosic  All Rights Reserved.

/**
 * This is an AJAX radio ticker client.
 * It fetches a json object from the server and generates and updates 
 * a song ticker gui object.
 * @author Srdjan Rosic (srdjan.rosic@gmail.com)
 */

var radioclient = radioclient || {};

radioclient.parent = null;

radioclient.getParent = function() {
  if (radioclient.parent) return radioclient.parent;
  radioclient.parent = document.getElementById('radioclient_div');
  return radioclient.parent;
};

radioclient.xhr = new window.XMLHttpRequest() || new window.ActiveXObject("Microsoft.XMLHTTP");

radioclient.refreshRadio = function() {
  radioclient.xhr.open("GET", "/radioserver-dev.php", true);
  radioclient.xhr.onreadystatechange = radioclient.handler;
  radioclient.xhr.send(null);
};


radioclient.handler = function() {
  if (this.readyState == 4) {
    radioclient.refreshTicker(jsonParse(radioclient.xhr.responseText));
    window.setTimeout(radioclient.refreshRadio, 10000);
  }
};


radioclient.refreshTicker = function(newradio) {
  if (window.console && window.console.log)
    window.console.log(newradio);

  // Is this the initial refresh ?
  if (radioclient.radio === undefined) {
    // Yes this is an initial refresh.
    radioclient.radio = newradio;
    if (newradio.online) {
      var newText = newradio.streamName;
      if (newradio.song != '')
        newText+= ' - ' + newradio.song;
      radioclient.currentTicker = radioclient.getOnlineTicker(newText);
    } else {
      radioclient.currentTicker = radioclient.getOfflineTicker();
    }
    radioclient.getParent().appendChild(radioclient.currentTicker);
    radioclient.scrollMax = radioclient.currentTicker.firstChild.scrollWidth - 200;
  } else {
    // If the radio was silent last time and still is, do nothing.
    if ((radioclient.radio.online == false) && 
        (newradio.online == false))
      return;

    // If the radio is still online and it's the same song, do nothing.
    if ((radioclient.radio.online) &&
        (newradio.online) &&
        (radioclient.radio.streamName == newradio.streamName) &&
        (radioclient.radio.song == newradio.song))
      return;

    // Constructs a new replacement ticker.
    var newTicker;
    if (newradio.online) {
      var newText = newradio.streamName;
      if (newradio.song != '')
        newText+= ' - ' + newradio.song;
      newTicker = radioclient.getOnlineTicker(newText);
    } else {
      newTicker = radioclient.getOfflineTicker();
    }

    // Replaces current with new
    radioclient.scrollMax = 0;
    radioclient.getParent().replaceChild(
         newTicker, radioclient.currentTicker);
    radioclient.currentTicker = newTicker;
    radioclient.scrollMax = newTicker.firstChild.scrollWidth;
  }
};


radioclient.getOnlineTicker = function(text) {
  return radioclient.buildOnlineTicker(text);
};


radioclient.getOfflineTicker = function() {
  if (radioclient.offlineTickerCache != null)
    return radioclient.offlineTickerCache;
  radioclient.offlineTickerCache = radioclient.buildOfflineTicker();
  return radioclient.offlineTickerCache;
};


radioclient.buildTickerBase = function() {
  var mainDiv = document.createElement('div');
  mainDiv.style.width = '200px';
  mainDiv.style.margin = 'auto';
  mainDiv.style.height = '16px';
  mainDiv.style.backgroundImage = 'url(/tabdown200.png)';
  mainDiv.style.overflow = 'hidden';
  mainDiv.style.whiteSpace = 'nowrap';
  mainDiv.style.fontSize = 'x-small';
  mainDiv.style.fontFamily = 'sans-serif';
  mainDiv.style.fontWeight = 'bold';
  mainDiv.style.paddingTop = '4px';
  mainDiv.style.textAlign = 'center';
  mainDiv.style.color = 'white';
  return mainDiv;
}


radioclient.buildOfflineTicker = function() {
  var mainDiv = radioclient.buildTickerBase();
  mainDiv.style.borderTop = '3px red solid';
  var content = document.createElement('a');
  content.appendChild(document.createTextNode('stdout is silent'));
  content.setAttribute('href', 'http://stdout.sietf.org/program.php');
  content.style.color = 'white';
  content.style.textDecoration = 'none';
  mainDiv.appendChild(content);
  return mainDiv;
};


radioclient.buildOnlineTicker = function(text) {
  var mainDiv = radioclient.buildTickerBase();
  mainDiv.style.borderTop = '3px #0f0 solid';
  mainDiv.setAttribute('title', 'Click to listen ' + text);
  var content = document.createElement('a');
  content.appendChild(document.createTextNode(text));
  content.setAttribute('href', 'http://stdout.sietf.org/listen.php');
  content.style.color = 'white';
  content.style.textDecoration = 'none';
  content.style.position = 'relative';
  content.style.left = '0px'
  mainDiv.appendChild(content);
  return mainDiv;
};

radioclient.offlineTickerCache = null;

radioclient.listen = function (evnt, elem, func) {
    if (elem.addEventListener)  // W3C DOM
        elem.addEventListener(evnt,func,false);
    else if (elem.attachEvent) { // IE DOM
         var r = elem.attachEvent("on"+evnt, func);
    return r;
    };
    //else window.alert('I\'m sorry Dave, I\'m afraid I can\'t do that.');
};


radioclient.scrollPosition = 0;
radioclient.scrollMax = 0;

radioclient.scrollStartDelay = 30;
radioclient.scrollStartDelayTimer = 30;
radioclient.scrollEndDelay = 30;
radioclient.scrollEndDelayTimer = 30;


/**
 * Moves the currentTickers child left
 */
radioclient.scrollTimerHandler = function() {
  if ((radioclient.scrollMax > 0) && radioclient.radio.online) {
    if ((radioclient.scrollPosition == 0) && 
        (radioclient.scrollStartDelayTimer > 0)) {
      radioclient.scrollStartDelayTimer--;
      return;
    }
    radioclient.scrollStartDelayTimer = radioclient.scrollStartDelay;
    radioclient.scrollPosition++;
    if ((radioclient.scrollPosition >= radioclient.scrollMax) &&
        (radioclient.scrollEndDelayTimer > 0)) {
      radioclient.scrollEndDelayTimer--;
      radioclient.scrollPosition--;
      return;
    }
    radioclient.scrollEndDelayTimer = radioclient.scrollEndDelay;
    radioclient.scrollPosition %= radioclient.scrollMax;
    radioclient.currentTicker.firstChild.style.left = '-' + 
        radioclient.scrollPosition + 'px';
  }

};

radioclient.listen('load', window, radioclient.refreshRadio);

window.setInterval(radioclient.scrollTimerHandler, 50);
