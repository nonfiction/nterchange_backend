// yepnope.js
// Version - 1.5.4pre
//
// by
// Alex Sexton - @SlexAxton - AlexSexton[at]gmail.com
// Ralph Holzmann - @ralphholzmann - ralphholzmann[at]gmail.com
//
// http://yepnopejs.com/
// https://github.com/SlexAxton/yepnope.js/
//
// Tri-license - WTFPL | MIT | BSD
//
// Please minify before use.
// Also available as Modernizr.load via the Modernizr Project
//
( function ( window, doc, undef ) {

var docElement            = doc.documentElement,
    sTimeout              = window.setTimeout,
    firstScript           = doc.getElementsByTagName( "script" )[ 0 ],
    toString              = {}.toString,
    execStack             = [],
    started               = 0,
    noop                  = function () {},
    // Before you get mad about browser sniffs, please read:
    // https://github.com/Modernizr/Modernizr/wiki/Undetectables
    // If you have a better solution, we are actively looking to solve the problem
    isGecko               = ( "MozAppearance" in docElement.style ),
    isGeckoLTE18          = isGecko && !! doc.createRange().compareNode,
    insBeforeObj          = isGeckoLTE18 ? docElement : firstScript.parentNode,
    // Thanks to @jdalton for showing us this opera detection (by way of @kangax) (and probably @miketaylr too, or whatever...)
    isOpera               = window.opera && toString.call( window.opera ) == "[object Opera]",
    isIE                  = !! doc.attachEvent && !isOpera,
    strJsElem             = isGecko ? "object" : isIE  ? "script" : "img",
    strCssElem            = isIE ? "script" : strJsElem,
    isArray               = Array.isArray || function ( obj ) {
      return toString.call( obj ) == "[object Array]";
    },
    isObject              = function ( obj ) {
      return Object(obj) === obj;
    },
    isString              = function ( s ) {
      return typeof s == "string";
    },
    isFunction            = function ( fn ) {
      return toString.call( fn ) == "[object Function]";
    },
    globalFilters         = [],
    scriptCache           = {},
    prefixes              = {
      // key value pair timeout options
      timeout : function( resourceObj, prefix_parts ) {
        if ( prefix_parts.length ) {
          resourceObj['timeout'] = prefix_parts[ 0 ];
        }
        return resourceObj;
      }
    },
    handler,
    yepnope;

  /* Loader helper functions */
  function isFileReady ( readyState ) {
    // Check to see if any of the ways a file can be ready are available as properties on the file's element
    return ( ! readyState || readyState == "loaded" || readyState == "complete" || readyState == "uninitialized" );
  }


  // Takes a preloaded js obj (changes in different browsers) and injects it into the head
  // in the appropriate order
  function injectJs ( src, cb, attrs, timeout, /* internal use */ err, internal ) {
    var script = doc.createElement( "script" ),
        done, i;

    timeout = timeout || yepnope['errorTimeout'];

    script.src = src;

    // Add our extra attributes to the script element
    for ( i in attrs ) {
        script.setAttribute( i, attrs[ i ] );
    }

    cb = internal ? executeStack : ( cb || noop );

    // Bind to load events
    script.onreadystatechange = script.onload = function () {

      if ( ! done && isFileReady( script.readyState ) ) {

        // Set done to prevent this function from being called twice.
        done = 1;
        cb();

        // Handle memory leak in IE
        script.onload = script.onreadystatechange = null;
      }
    };

    // 404 Fallback
    sTimeout(function () {
      if ( ! done ) {
        done = 1;
        // Might as well pass in an error-state if we fire the 404 fallback
        cb(1);
      }
    }, timeout );

    // Inject script into to document
    // or immediately callback if we know there
    // was previously a timeout error
    err ? script.onload() : firstScript.parentNode.insertBefore( script, firstScript );
  }

  // Takes a preloaded css obj (changes in different browsers) and injects it into the head
  function injectCss ( href, cb, attrs, timeout, /* Internal use */ err, internal ) {

    // Create stylesheet link
    var link = doc.createElement( "link" ),
        done, i;

    timeout = timeout || yepnope['errorTimeout'];

    cb = internal ? executeStack : ( cb || noop );

    // Add attributes
    link.href = href;
    link.rel  = "stylesheet";
    link.type = "text/css";

    // Add our extra attributes to the link element
    for ( i in attrs ) {
      link.setAttribute( i, attrs[ i ] );
    }

    if ( ! err ) {
      firstScript.parentNode.insertBefore( link, firstScript );
      sTimeout(cb, 0);
    }
  }

  function executeStack ( ) {
    // shift an element off of the stack
    var i   = execStack.shift();
    started = 1;

    // if a is truthy and the first item in the stack has an src
    if ( i ) {
      // if it's a script, inject it into the head with no type attribute
      if ( i['t'] ) {
        // Inject after a timeout so FF has time to be a jerk about it and
        // not double load (ignore the cache)
        sTimeout( function () {
          (i['t'] == "c" ?  yepnope['injectCss'] : yepnope['injectJs'])( i['s'], 0, i['a'], i['x'], i['e'], 1 );
        }, 0 );
      }
      // Otherwise, just call the function and potentially run the stack
      else {
        i();
        executeStack();
      }
    }
    else {
      // just reset out of recursive mode
      started = 0;
    }
  }

  function preloadFile ( elem, url, type, splicePoint, dontExec, attrObj, timeout ) {

    timeout = timeout || yepnope['errorTimeout'];

    // Create appropriate element for browser and type
    var preloadElem = doc.createElement( elem ),
        done        = 0,
        firstFlag   = 0,
        stackObject = {
          "t": type,     // type
          "s": url,      // src
        //r: 0,        // ready
          "e": dontExec,// set to true if we don't want to reinject
          "a": attrObj,
          "x": timeout
        };

    // The first time (common-case)
    if ( scriptCache[ url ] === 1 ) {
      firstFlag = 1;
      scriptCache[ url ] = [];
    }

    function onload ( first ) {
      // If the script/css file is loaded
      if ( ! done && isFileReady( preloadElem.readyState ) ) {

        // Set done to prevent this function from being called twice.
        stackObject['r'] = done = 1;

        ! started && executeStack();

        // Handle memory leak in IE
        preloadElem.onload = preloadElem.onreadystatechange = null;
        if ( first ) {
          if ( elem != "img" ) {
            sTimeout(function(){ insBeforeObj.removeChild( preloadElem ) }, 50);
          }

          for ( var i in scriptCache[ url ] ) {
            if ( scriptCache[ url ].hasOwnProperty( i ) ) {
              scriptCache[ url ][ i ].onload();
            }
          }
        }
      }
    }


    // Setting url to data for objects or src for img/scripts
    if ( elem == "object" ) {
      preloadElem.data = url;
    } else {
      preloadElem.src = url;

      // Setting bogus script type to allow the script to be cached
      preloadElem.type = elem;
    }

    // Don't let it show up visually
    preloadElem.width = preloadElem.height = "0";

    // Attach handlers for all browsers
    preloadElem.onerror = preloadElem.onload = preloadElem.onreadystatechange = function(){
      onload.call(this, firstFlag);
    };
    // inject the element into the stack depending on if it's
    // in the middle of other scripts or not
    execStack.splice( splicePoint, 0, stackObject );

    // The only place these can't go is in the <head> element, since objects won't load in there
    // so we have two options - insert before the head element (which is hard to assume) - or
    // insertBefore technically takes null/undefined as a second param and it will insert the element into
    // the parent last. We try the head, and it automatically falls back to undefined.
    if ( elem != "img" ) {
      // If it's the first time, or we've already loaded it all the way through
      if ( firstFlag || scriptCache[ url ] === 2 ) {
        insBeforeObj.insertBefore( preloadElem, isGeckoLTE18 ? null : firstScript );

        // If something fails, and onerror doesn't fire,
        // continue after a timeout.
        sTimeout( onload, timeout );
      }
      else {
        // instead of injecting, just hold on to it
        scriptCache[ url ].push( preloadElem );
      }
    }
  }

  function load ( resource, type, dontExec, attrObj, timeout ) {
    // If this method gets hit multiple times, we should flag
    // that the execution of other threads should halt.
    started = 0;

    // We'll do 'j' for js and 'c' for css, yay for unreadable minification tactics
    type = type || "j";
    if ( isString( resource ) ) {
      // if the resource passed in here is a string, preload the file
      preloadFile( type == "c" ? strCssElem : strJsElem, resource, type, this['i']++, dontExec, attrObj, timeout );
    } else {
      // Otherwise it's a callback function and we can splice it into the stack to run
      execStack.splice( this['i']++, 0, resource );
      execStack.length == 1 && executeStack();
    }

    // OMG is this jQueries? For chaining...
    return this;
  }

  // return the yepnope object with a fresh loader attached
  function getYepnope () {
    var y = yepnope;
    y['loader'] = {
      "load": load,
      "i" : 0
    };
    return y;
  }

  /* End loader helper functions */
  // Yepnope Function
  yepnope = function ( needs ) {

    var i,
        need,
        // start the chain as a plain instance
        chain = this['yepnope']['loader'];

    function satisfyPrefixes ( url ) {
      // split all prefixes out
      var parts   = url.split( "!" ),
      gLen    = globalFilters.length,
      origUrl = parts.pop(),
      pLen    = parts.length,
      res     = {
        "url"      : origUrl,
        // keep this one static for callback variable consistency
        "origUrl"  : origUrl,
        "prefixes" : parts
      },
      mFunc,
      j,
      prefix_parts;

      // loop through prefixes
      // if there are none, this automatically gets skipped
      for ( j = 0; j < pLen; j++ ) {
        prefix_parts = parts[ j ].split( '=' );
        mFunc = prefixes[ prefix_parts.shift() ];
        if ( mFunc ) {
          res = mFunc( res, prefix_parts );
        }
      }

      // Go through our global filters
      for ( j = 0; j < gLen; j++ ) {
        res = globalFilters[ j ]( res );
      }

      // return the final url
      return res;
    }

    function getExtension ( url ) {
        return url.split(".").pop().split("?").shift();
    }

    function loadScriptOrStyle ( input, callback, chain, index, testResult ) {
      // run through our set of prefixes
      var resource     = satisfyPrefixes( input ),
          autoCallback = resource['autoCallback'],
          extension    = getExtension( resource['url'] );

      // if no object is returned or the url is empty/0 just exit the load
      if ( resource['bypass'] ) {
        return;
      }

      // Determine callback, if any
      if ( callback ) {
        callback = isFunction( callback ) ?
          callback :
          callback[ input ] ||
          callback[ index ] ||
          callback[ ( input.split( "/" ).pop().split( "?" )[ 0 ] ) ];
      }

      // if someone is overriding all normal functionality
      if ( resource['instead'] ) {
        return resource['instead']( input, callback, chain, index, testResult );
      }
      else {
        // Handle if we've already had this url and it's completed loaded already
        if ( scriptCache[ resource['url'] ] ) {
          // don't let this execute again
          resource['noexec'] = true;
        }
        else {
          scriptCache[ resource['url'] ] = 1;
        }

        // Throw this into the queue
        chain.load( resource['url'], ( ( resource['forceCSS'] || ( ! resource['forceJS'] && "css" == getExtension( resource['url'] ) ) ) ) ? "c" : undef, resource['noexec'], resource['attrs'], resource['timeout'] );

        // If we have a callback, we'll start the chain over
        if ( isFunction( callback ) || isFunction( autoCallback ) ) {
          // Call getJS with our current stack of things
          chain['load']( function () {
            // Hijack yepnope and restart index counter
            getYepnope();
            // Call our callbacks with this set of data
            callback && callback( resource['origUrl'], testResult, index );
            autoCallback && autoCallback( resource['origUrl'], testResult, index );

            // Override this to just a boolean positive
            scriptCache[ resource['url'] ] = 2;
          } );
        }
      }
    }

    function loadFromTestObject ( testObject, chain ) {
        var testResult = !! testObject['test'],
            group      = testResult ? testObject['yep'] : testObject['nope'],
            always     = testObject['load'] || testObject['both'],
            callback   = testObject['callback'] || noop,
            cbRef      = callback,
            complete   = testObject['complete'] || noop,
            needGroupSize,
            callbackKey;

        // Reusable function for dealing with the different input types
        // NOTE:: relies on closures to keep 'chain' up to date, a bit confusing, but
        // much smaller than the functional equivalent in this case.
        function handleGroup ( needGroup, moreToCome ) {
          if ( ! needGroup ) {
            // Call the complete callback when there's nothing to load.
            ! moreToCome && complete();
          }
          // If it's a string
          else if ( isString( needGroup ) ) {
            // if it's a string, it's the last
            if ( !moreToCome ) {
              // Add in the complete callback to go at the end
              callback = function () {
                var args = [].slice.call( arguments );
                cbRef.apply( this, args );
                complete();
              };
            }
            // Just load the script of style
            loadScriptOrStyle( needGroup, callback, chain, 0, testResult );
          }
          // See if we have an object. Doesn't matter if it's an array or a key/val hash
          // Note:: order cannot be guaranteed on an key value object with multiple elements
          // since the for-in does not preserve order. Arrays _should_ go in order though.
          else if ( isObject( needGroup ) ) {
            // I hate this, but idk another way for objects.
            needGroupSize = (function(){
              var count = 0, i
              for (i in needGroup ) {
                if ( needGroup.hasOwnProperty( i ) ) {
                  count++;
                }
              }
              return count;
            })();

            for ( callbackKey in needGroup ) {
              // Safari 2 does not have hasOwnProperty, but not worth the bytes for a shim
              // patch if needed. Kangax has a nice shim for it. Or just remove the check
              // and promise not to extend the object prototype.
              if ( needGroup.hasOwnProperty( callbackKey ) ) {
                // Find the last added resource, and append to it's callback.
                if ( ! moreToCome && ! ( --needGroupSize ) ) {
                  // If this is an object full of callbacks
                  if ( ! isFunction( callback ) ) {
                    // Add in the complete callback to go at the end
                    callback[ callbackKey ] = (function( innerCb ) {
                      return function () {
                        var args = [].slice.call( arguments );
                        innerCb && innerCb.apply( this, args );
                        complete();
                      };
                    })( cbRef[ callbackKey ] );
                  }
                  // If this is just a single callback
                  else {
                    callback = function () {
                      var args = [].slice.call( arguments );
                      cbRef.apply( this, args );
                      complete();
                    };
                  }
                }
                loadScriptOrStyle( needGroup[ callbackKey ], callback, chain, callbackKey, testResult );
              }
            }
          }
        }

        // figure out what this group should do
        handleGroup( group, !!always );

        // Run our loader on the load/both group too
        // the always stuff always loads second.
        always && handleGroup( always );
    }

    // Someone just decides to load a single script or css file as a string
    if ( isString( needs ) ) {
      loadScriptOrStyle( needs, 0, chain, 0 );
    }
    // Normal case is likely an array of different types of loading options
    else if ( isArray( needs ) ) {
      // go through the list of needs
      for( i = 0; i < needs.length; i++ ) {
        need = needs[ i ];

        // if it's a string, just load it
        if ( isString( need ) ) {
          loadScriptOrStyle( need, 0, chain, 0 );
        }
        // if it's an array, call our function recursively
        else if ( isArray( need ) ) {
          yepnope( need );
        }
        // if it's an object, use our modernizr logic to win
        else if ( isObject( need ) ) {
          loadFromTestObject( need, chain );
        }
      }
    }
    // Allow a single object to be passed in
    else if ( isObject( needs ) ) {
      loadFromTestObject( needs, chain );
    }
  };

  // This publicly exposed function is for allowing
  // you to add functionality based on prefixes on the
  // string files you add. 'css!' is a builtin prefix
  //
  // The arguments are the prefix (not including the !) as a string
  // and
  // A callback function. This function is passed a resource object
  // that can be manipulated and then returned. (like middleware. har.)
  //
  // Examples of this can be seen in the officially supported ie prefix
  yepnope['addPrefix'] = function ( prefix, callback ) {
    prefixes[ prefix ] = callback;
  };

  // A filter is a global function that every resource
  // object that passes through yepnope will see. You can
  // of course conditionally choose to modify the resource objects
  // or just pass them along. The filter function takes the resource
  // object and is expected to return one.
  //
  // The best example of a filter is the 'autoprotocol' officially
  // supported filter
  yepnope['addFilter'] = function ( filter ) {
    globalFilters.push( filter );
  };

  // Default error timeout to 10sec - modify to alter
  yepnope['errorTimeout'] = 1e4;

  // Webreflection readystate hack
  // safe for jQuery 1.4+ ( i.e. don't use yepnope with jQuery 1.3.2 )
  // if the readyState is null and we have a listener
  if ( doc.readyState == null && doc.addEventListener ) {
    // set the ready state to loading
    doc.readyState = "loading";
    // call the listener
    doc.addEventListener( "DOMContentLoaded", handler = function () {
      // Remove the listener
      doc.removeEventListener( "DOMContentLoaded", handler, 0 );
      // Set it to ready
      doc.readyState = "complete";
    }, 0 );
  }

  // Attach loader &
  // Leak it
  window['yepnope'] = getYepnope();

  // Exposing executeStack to better facilitate plugins
  window['yepnope']['executeStack'] = executeStack;
  window['yepnope']['injectJs'] = injectJs;
  window['yepnope']['injectCss'] = injectCss;

})( this, document );
/**
 * YepNope Path Filter
 *
 * Usage:
 *   yepnope.paths = {
 *     'google': '//ajax.googleapis.com/ajax',
 *     'my-cdn': '//cdn.myawesomesite.com'
 *   };
 *   yepnope({
 *     load: ['google/jquery/1.7.2/jquery.min.js', 'google/jqueryui/1.8.18/jquery-ui.min.js', 'my-cdn/style.css', '/non/path/directory/file.js']
 *   });
 *
 * Official Yepnope Plugin
 *
 * WTFPL License
 *
 * by Kenneth Powers | mail@kenpowers.net
 */

(function () {
  var addPathFilter = function (yn) {
      // add each prefix
      yn.addFilter(function (resource) {
        // check each url for path
        for (path in yn.paths) {
          var pathRegExp = new RegExp('^'  + path);
          if (resource.url.match(pathRegExp)) {
            resource.url = resource.url.replace(pathRegExp, yn.paths[path]);
            return resource;
          }
        }
        // carry on my wayward, son
        return resource;
      });
    };
  if (yepnope) addPathFilter(yepnope);
  else if (modernizr.load) addPathFilter(modernizr.load);
})();
/*!
                                   ,...,,                  ,,
                                 .d' ""db           mm     db
                                 dM`                MM
`7MMpMMMb.  ,pW"Wq.`7MMpMMMb.   mMMmm`7MM  ,p6"bo mmMMmm `7MM  ,pW"Wq.`7MMpMMMb.
  MM    MM 6W'   `Wb MM    MM    MM    MM 6M'  OO   MM     MM 6W'   `Wb MM    MM
  MM    MM 8M     M8 MM    MM    MM    MM 8M        MM     MM 8M     M8 MM    MM
  MM    MM YA.   ,A9 MM    MM    MM    MM YM.    ,  MM     MM YA.   ,A9 MM    MM
.JMML  JMML.`Ybmd9'.JMML  JMML..JMML..JMML.YMbmd'   `Mbmo.JMML.`Ybmd9'.JMML  JMML.
*/


(function() {
  yepnope.paths = {
    'components': "/nterchange/assets/components",
    'javascripts': "/nterchange/assets/javascripts",
    'stylesheets': "/nterchange/assets/stylesheets"
  };

  yepnope({
    test: window.jQuery,
    nope: ['components/jquery/jquery.min.js'],
    complete: function() {
      yepnope({
        test: window.jQuery.ui,
        nope: ['javascripts/jquery-ui.js', 'components/jquery-ui/themes/smoothness/jquery-ui.min.css'],
        complete: function() {
          return window.n.init();
        }
      });
      yepnope({
        test: window.jQuery.noty,
        nope: ['javascripts/noty.js'],
        complete: function() {
          return window.n.init();
        }
      });
      return yepnope({
        test: window.CKEDITOR,
        nope: ['components/ckeditor/ckeditor.js', 'components/ckeditor/adapters/jquery.js'],
        complete: function() {
          CKEDITOR.config.customConfig = '/javascripts/ckeditor_config.js';
          return window.n.init();
        }
      });
    }
  });

  window.sm = 768;

  window.md = 992;

  window.lg = 1200;

  window.n = {};

  n.count = 0;

  n.init = function() {
    n.count++;
    if (n.count < 3) {
      return;
    }
    $(window).resize(function() {
      return n.breakpoints();
    });
    n.breakpoints();
    return n.surfToEdit();
  };

  n.grids = {
    'xs': ['sm', 'md', 'lg'],
    'sm': ['md', 'lg'],
    'md': ['lg'],
    'lg': []
  };

  n.surfToEdit = function() {
    if (!location.href.match(/nterchange/g)) {
      return false;
    }
    $('html').addClass('surf-to-edit');
    $('button.ntoolbar-toggle').each(function() {
      return n.toolbar(this);
    });
    $('div.ntoolbar-page').each(function() {
      return n.toolbarPage(this);
    });
    return $('.grid-block').each(function() {
      n.gb.classify(this);
      $(this).find('.ntoolbar .col-chooser a, .ntoolbar .row-chooser a').on('click', function(event) {
        var $a;
        event.preventDefault();
        $a = $(event.target);
        return n.gb.grid($a, true);
      });
      $(this).find(".ntoolbar button.pull-left, .ntoolbar button.pull-right").on('click', function(event) {
        return n.gb.pull(event, true);
      });
      return n.gb.resizable(this);
    });
  };

  n.columnToPercentage = function(col) {
    return "" + (Math.round((col / 12) * 100)) + "%";
  };

  n.id = function(element) {
    var $element, id;
    $element = $(element);
    if (!$element.attr('id')) {
      id = [$element.prop("tagName").toLowerCase(), $element.prop("className").split(' ').join('-'), new Date().getTime()].join('-');
      $element.attr('id', id);
    }
    return $element.attr('id');
  };

  n.cleanValue = function(element) {
    var value;
    if ($(element).data('value')) {
      value = $(element).data('value');
    } else {
      value = $(element).find('.dynamic').remove().end().html();
    }
    return value.replace(/[\n\r]/g, '').trim();
  };

  n.cleanLabel = function(element, count) {
    var value;
    if (count == null) {
      count = 2;
    }
    if (count <= 1) {
      return "";
    }
    if ($(element).data('label')) {
      value = $(element).data('label');
    } else if ($(element).data('field')) {
      value = $(element).data('field');
    } else if ($(element).data('type')) {
      value = $(element).data('type');
    } else {
      value = "";
    }
    return value.replace(/[\n\r]/g, '').trim();
  };

  n.breakpoints = function() {
    var grid, message, offset, width;
    offset = 15;
    width = $(window).width() + offset;
    if (width < window.lg) {
      $('html').removeClass('lg');
    }
    if (width < window.md) {
      $('html').removeClass('md');
    }
    if (width < window.sm) {
      $('html').removeClass('sm');
    }
    grid = 'xs';
    if (width >= window.sm) {
      grid = 'sm';
      $('html').addClass(grid);
    }
    if (width >= window.md) {
      grid = 'md';
      $('html').addClass(grid);
    }
    if (width >= window.lg) {
      grid = 'lg';
      $('html').addClass(grid);
    }
    $('html').data('grid', grid);
    message = "Grid Size: <b>" + grid + "</b>";
    $('.ntoolbar-main .grid-size').html(message);
    if (location.href.match(/nterchange/g) && grid !== window.grid) {
      noty({
        layout: 'bottomCenter',
        type: 'information',
        timeout: 3000,
        text: message
      });
    }
    return window.grid = grid;
  };

  n.toolbar = function(cog) {
    var $pageContent, $toolbar;
    $pageContent = $(cog).parent();
    $toolbar = $pageContent.find('.ntoolbar:first');
    if ($pageContent.parent().hasClass('grid-block')) {
      $(cog).data('usingGrid', true);
    } else {
      $(cog).data('usingGrid', false);
      $pageContent.find('div[data-grid]').remove();
    }
    $(cog).on('edit', function(event, editing) {
      var $asset, $gb, assetId;
      $asset = [];
      if ($(cog).data('usingGrid')) {
        $gb = $(event.currentTarget).closest('.grid-block');
        $asset = $gb.find('[data-asset]:first');
      }
      if ($asset.length < 1) {
        $asset = $(cog).parent();
      }
      assetId = n.id($asset);
      $asset.trigger('edit', editing);
      if (!editing) {
        $asset.find('[data-ui=popover]').popover('hide');
      }
      return $asset.find('[data-ui]').each(function() {
        return n.field(this, $asset, editing);
      });
    });
    $(cog).on('click', function(event) {
      $(cog).toggleClass('active');
      $(cog).trigger('edit', $(cog).hasClass('active'));
      $toolbar.toggle();
      if ($(cog).data('usingGrid')) {
        return $pageContent.parent().toggleClass('edit-mode');
      }
    });
    $toolbar.find('button.edit').on('click', function(event) {
      return location.href = $(this).data('href');
    });
    $toolbar.find('button.edit').on('contextmenu', function(event) {
      var url;
      url = $(this).data('popover-href');
      $.get(url, function(response) {
        var $form;
        $toolbar.append('<div class=nform></div>');
        $form = $toolbar.find('.nform').html(response);
        return $form.dialog({
          height: 'auto',
          width: 800,
          modal: true,
          title: 'Editing Asset'
        });
      });
      return false;
    });
    return $toolbar.find('button.remove').on('click', function(event) {
      if (confirm('Are you sure?')) {
        return location.href = $(this).data('href');
      }
    });
  };

  n.field = function(field, asset, editing) {
    var $asset, $f, $field, assetId, fieldId, _i, _len, _ref, _results;
    $field = $(field);
    $asset = $(asset);
    fieldId = n.id($field);
    assetId = n.id($asset);
    if ($field.data('ui') === 'inline') {
      if ($field.data('type') === 'textarea') {
        if (editing) {
          $field.attr('contenteditable', 'true');
          $asset.data(fieldId, window.CKEDITOR.inline(fieldId));
          $field.data('original-value', n.cleanValue($field));
        } else {
          $field.removeAttr('contenteditable');
          if ($asset.data(fieldId)) {
            $asset.data(fieldId).destroy();
          }
          if ($field.data('original-value') !== n.cleanValue($field)) {
            n.gb.updateField($field);
          }
        }
      }
      if ($field.data('type') === 'text') {
        if (editing) {
          $field.attr('contenteditable', 'true');
          $field.data('original-value', n.cleanValue($field));
        } else {
          $field.removeAttr('contenteditable');
          if ($field.data('original-value') !== n.cleanValue($field)) {
            n.gb.updateField($field);
          }
        }
      }
    }
    if ($field.data('ui') === 'popover') {
      if (editing) {
        return $field.data('popover', null).popover({
          placement: 'top',
          html: true,
          container: 'body',
          title: n.cleanLabel($field),
          content: "<div id='" + fieldId + "-popover' class='popover-form'>" + (n.popoverHTML($field)) + "</div>"
        }).on('shown.bs.popover', function() {
          return $("#" + fieldId + "-popover").html(n.popoverHTML($field));
        }).on('hide.bs.popover', function() {
          return $("#" + fieldId + "-popover .popover-value").each(function() {
            var $f;
            $f = $("#" + assetId + " [data-field=" + ($(this).prop('name')) + "]");
            return $f.data('value', $(this).prop('value'));
          });
        });
      } else {
        _ref = $field.data('fields');
        _results = [];
        for (_i = 0, _len = _ref.length; _i < _len; _i++) {
          $f = _ref[_i];
          if ($f.data('type') === 'text') {
            if ($f.data('original-value') !== n.cleanValue($f)) {
              _results.push(n.gb.updateField($field));
            } else {
              _results.push(void 0);
            }
          } else {
            _results.push(void 0);
          }
        }
        return _results;
      }
    }
  };

  n.popoverHTML = function($field) {
    var $f, $fields, popoverHTML, _i, _len, _ref;
    popoverHTML = '';
    if (!$field.data('fields')) {
      $fields = [];
      if ($field.data('type')) {
        $fields.push($field);
      }
      $field.find('[data-type]').each(function() {
        return $fields.push($(this));
      });
      $field.data('fields', $fields);
    }
    _ref = $field.data('fields');
    for (_i = 0, _len = _ref.length; _i < _len; _i++) {
      $f = _ref[_i];
      if ($f.data('type') === 'text') {
        $f.data('original-value', n.cleanValue($f));
        popoverHTML += "<label>\n  <span>" + (n.cleanLabel($f, $field.data('fields').length)) + "</span>\n  <input type='text' class='popover-value' name='" + ($f.data('field')) + "' value='" + (n.cleanValue($f)) + "'>\n</label>";
      }
    }
    return popoverHTML;
  };

  n.toolbarPage = function(toolbar) {
    var $toolbar;
    $toolbar = $(toolbar);
    $toolbar.find('button.add').on('click', function(event) {
      return location.href = $(this).data('href');
    });
    $toolbar.find('button.add').on('contextmenu', function(event) {
      window.open($(this).data('href'), '_blank', 'width=800,height=600,toolbar=yes,location=yes,directories=yes,status=yes,menubar=yes,scrollbars=yes,copyhistory=yes, resizable=yes');
      return false;
    });
    return $toolbar.find('button.order').on('click', function(event) {
      return window.open($(this).data('href'), 'sort', 'width=500, height=550, resizable, scrollbars');
    });
  };

  n.gb = {};

  n.gb.findInherited = function($a) {
    var $gb, grid, i, type;
    grid = $a.data('grid');
    $gb = $a.closest('.grid-block');
    if ($a.data('col') != null) {
      type = 'col';
    }
    if ($a.data('row') != null) {
      type = 'row';
    }
    if (grid === 'sm' || grid === 'md' || grid === 'lg') {
      if ($gb.data("" + type + "-xs") !== 'inherit') {
        i = $gb.data("" + type + "-xs");
      }
    }
    if (grid === 'md' || grid === 'lg') {
      if ($gb.data("" + type + "-sm") !== 'inherit') {
        i = $gb.data("" + type + "-sm");
      }
    }
    if (grid === 'lg') {
      if ($gb.data("" + type + "-md") !== 'inherit') {
        i = $gb.data("" + type + "-md");
      }
    }
    return $a.closest("." + type + "-chooser").find("a[data-" + type + "=" + i + "]");
  };

  n.gb.updateField = function($field, skipVersioning) {
    var $gb, asset, fieldHTML;
    if (skipVersioning == null) {
      skipVersioning = false;
    }
    noty({
      layout: 'bottomRight',
      type: 'alert',
      timeout: 2000,
      text: 'Saving...'
    });
    $gb = $field.closest('.grid-block');
    asset = {
      id: $gb.data('asset-id'),
      cms_headline: $gb.data('asset-headline'),
      __submit__: true,
      __skip_versioning__: skipVersioning
    };
    fieldHTML = $field.html().replace(/\/nterchange\/page\/surftoedit\//g, '/_page');
    if ($field.data('type') === 'textarea') {
      asset[$field.data('field')] = fieldHTML;
    }
    if ($field.data('type') === 'text') {
      asset[$field.data('field')] = fieldHTML;
    }
    return $.post("/nterchange/" + ($gb.data('asset')) + "/edit/" + ($gb.data('asset-id')), asset).done(function() {
      noty({
        layout: 'bottomRight',
        type: 'success',
        timeout: 1000,
        text: 'Changes Saved!'
      });
      return $field.trigger('update');
    });
  };

  n.gb.updatePageContent = function($gb) {
    var gb;
    gb = {
      id: $gb.data('id'),
      __submit__: true
    };
    gb['col_xs'] = $gb.data('col-xs');
    gb['col_sm'] = $gb.data('col-sm');
    gb['col_md'] = $gb.data('col-md');
    gb['col_lg'] = $gb.data('col-lg');
    gb['row_xs'] = $gb.data('row-xs');
    gb['row_sm'] = $gb.data('row-sm');
    gb['row_md'] = $gb.data('row-md');
    gb['row_lg'] = $gb.data('row-lg');
    gb['pull_xs'] = $gb.data('pull-xs');
    gb['pull_sm'] = $gb.data('pull-sm');
    gb['pull_md'] = $gb.data('pull-md');
    gb['pull_lg'] = $gb.data('pull-lg');
    gb['content_order'] = $gb.data('content-order');
    return $.post("/nterchange/page_content/edit/" + ($gb.data('id')), gb).done(function() {
      return noty({
        layout: 'bottomRight',
        type: 'success',
        timeout: 1000,
        text: 'Changes Saved!'
      });
    });
  };

  n.gb.classify = function(gb) {
    var $a, $gb, $ia, $left, $right, g, i, num, type, _i, _len, _ref, _ref1, _results;
    $gb = $(gb);
    _ref = n.grids;
    _results = [];
    for (g in _ref) {
      i = _ref[g];
      _ref1 = ['col', 'row'];
      for (_i = 0, _len = _ref1.length; _i < _len; _i++) {
        type = _ref1[_i];
        num = $gb.data("" + type + "-" + g);
        $a = $gb.find(".ntoolbar ." + type + "-chooser-" + g + " a[data-" + type + "=" + num + "]");
        $a.addClass('active');
        if (num === 'inherit') {
          $ia = n.gb.findInherited($a);
          $ia.addClass('inherit');
          num = "" + ($ia.data(type)) + " <i>(inherit)</i>";
        }
        $gb.find(".grid-block-dimensions-" + g + " .grid-block-" + type).html(num);
      }
      $left = $gb.find(".ntoolbar button.pull-left[data-grid=" + g + "]");
      $right = $gb.find(".ntoolbar button.pull-right[data-grid=" + g + "]");
      if ($gb.data("pull-" + g) === 'right') {
        $right.addClass('active');
        _results.push($left.addClass('inactive'));
      } else if ($gb.data("pull-" + g) === 'left') {
        $right.addClass('inactive');
        _results.push($left.addClass('active'));
      } else {
        $right.addClass('inactive');
        _results.push($left.addClass('inactive'));
      }
    }
    return _results;
  };

  n.gb.resizable = function(gb) {
    var $gb, col_width, row_height;
    $gb = $(gb);
    col_width = $gb.closest('.row').width() / 12;
    row_height = parseInt($gb.css('font-size'), 10);
    $gb.find('.grid-block-content').resizable({
      handles: 's, e',
      start: function(event, ui) {
        var resize;
        $gb = ui.element.closest('.grid-block');
        if ($(event.toElement).hasClass('ui-resizable-e')) {
          resize = 'horizontal';
        }
        if ($(event.toElement).hasClass('ui-resizable-s')) {
          resize = 'vertical';
        }
        return $gb.data('resize', resize).addClass('resizing');
      },
      resize: function(event, ui) {
        var col, grid, row;
        grid = $('html').data('grid');
        $gb = ui.element.closest('.grid-block');
        if ($gb.data('resize') === 'horizontal') {
          col = Math.round(ui.size.width / col_width);
          if (col < 1) {
            col = 0;
          }
          if (col > 12) {
            col = 12;
          }
          n.gb.grid($gb.find(".ntoolbar .col-chooser-" + grid + " a[data-col=" + col + "]"), false);
        }
        if ($gb.data('resize') === 'vertical') {
          row = Math.round(ui.size.height / row_height);
          if (row < 1) {
            row = 0;
          }
          if (row > 50) {
            row = 50;
          }
          n.gb.grid($gb.find(".ntoolbar .row-chooser-" + grid + " a[data-row=" + row + "]"), false);
        }
        return n.gb.grid($gb.find($gb.data('grid-selector')), false);
      },
      stop: function(event, ui) {
        $gb = ui.element.closest('.grid-block');
        $gb.removeClass('resizing');
        return n.gb.updatePageContent($gb);
      }
    });
    $gb.find('.ui-resizable-s').dblclick(function(event) {
      var grid, row;
      grid = $('html').data('grid');
      $gb = $(event.target).closest('.grid-block');
      row = 'auto';
      if ($gb.data("row-" + grid) === 'auto') {
        row = 'inherit';
      }
      $gb.data("row-" + grid, row);
      return n.gb.grid($gb.find(".ntoolbar .row-chooser-" + grid + " a[data-row=" + row + "]"), true);
    });
    return $gb.find('.ui-resizable-e').dblclick(function(event) {
      var col, grid;
      grid = $('html').data('grid');
      $gb = $(event.target).closest('.grid-block');
      col = '12';
      if (parseInt($gb.data("col-" + grid), 10) === 12) {
        col = 'inherit';
      }
      $gb.data("col-" + grid, col);
      return n.gb.grid($gb.find(".ntoolbar .col-chooser-" + grid + " a[data-col=" + col + "]"), true);
    });
  };

  n.gb.grid = function($a, update) {
    var $ch, $gb, grid, num, type, val;
    if (update == null) {
      update = false;
    }
    if ($a.data('col') != null) {
      type = 'col';
    }
    if ($a.data('row') != null) {
      type = 'row';
    }
    grid = $a.data('grid');
    val = $a.data(type);
    $gb = $a.closest('.grid-block');
    $ch = $a.closest("." + type + "-chooser");
    $gb.data("" + type + "-" + grid, val).attr("data-" + type + "-" + grid, val);
    $gb.find('.grid-block-content').attr('style', '');
    if (update) {
      n.gb.updatePageContent($gb);
    }
    $ch.find('a.active').removeClass('active');
    $a.addClass('active');
    num = $a.data(type);
    if (num === 'inherit') {
      num = $(n.gb.findInherited($a)).data(type) + ' <i>(inherit)</i>';
    }
    $gb.find(".grid-block-dimensions-" + grid + " .grid-block-" + type).html(num);
    $gb.find("." + type + "-chooser a.inherit").removeClass('inherit');
    return $gb.find("." + type + "-chooser a.active[data-" + type + "=inherit]").each(function() {
      return n.gb.findInherited($(this)).addClass('inherit');
    });
  };

  n.gb.pull = function(event) {
    var $clicked, $gb, $other, clicked, grid, other;
    $clicked = $(event.target).closest('button');
    if ($clicked.hasClass('pull-left')) {
      clicked = 'left';
      other = 'right';
    } else {
      clicked = 'right';
      other = 'left';
    }
    $other = $(event.target).closest('.pull-chooser').find("button.pull-" + other);
    $gb = $clicked.closest('.grid-block');
    grid = $clicked.data('grid');
    if ($clicked.hasClass('active')) {
      $clicked.removeClass('active').addClass('inactive');
      $gb.data("pull-" + grid, 'none').attr("data-pull-" + grid, 'none');
    } else {
      $other.removeClass('active').addClass('inactive');
      $clicked.removeClass('inactive').addClass('active');
      $gb.data("pull-" + grid, clicked).attr("data-pull-" + grid, clicked);
    }
    return n.gb.updatePageContent($gb);
  };

}).call(this);
