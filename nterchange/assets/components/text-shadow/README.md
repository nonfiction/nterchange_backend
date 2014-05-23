CSS text-shadow in IE7-9
========================

I didn't write [text-shadow.js](http://asamuzak.jp/test/textshadow_test_ie_en) 
but I did want to easily install it using [bower](http://twitter.github.io/bower/).

Usage
-----
Include the `text-shadow.js` in your HTML. That's all. text-shadow values will be loaded from stylesheets automatically.

Note: Due to MSIE bugs, '!important' rules will be ignored (MSIE drops it). And MSIE won't recognize @media queries correctly in some cases.

Changes were made from the original
-----------------------------------
  - Changed IE detection (from `@cc_on`) to allow uglifyjs minification
  - Commented out "sample" addEvent at bottom of source

Credits
-------
Copyright (c) 2011-2013 Kazz  
http://asamuzak.jp  
Dual licensed under MIT or GPL  
<http://asamuzak.jp/license>
