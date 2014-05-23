/*
*  text-shadow for MSIE
*  MSIE(7～9）にCSS3のtext-shadow（擬き）を適用させるJavaScript
*  Copyright (c) 2011-2013 Kazz
*  http://asamuzak.jp
*  Dual licensed under MIT or GPL
*  http://asamuzak.jp/license
*/

var isMSIE = (navigator.userAgent.match(/MSIE (\d+)/)) ? true : false,
    ieVersion = (function(_doc, reg) {
      return _doc.documentMode ? _doc.documentMode : isMSIE && navigator.userAgent.match(reg) ? RegExp.$1 * 1 : null;
    })(document, /MSIE\s([0-9]+[\.0-9]*)/),
    cNum = (function(n) { return function() { return n++; }})(0);

function textShadowForMSIE(eObj) {
  var _win = window, _doc = document;
  var ieShadowSettings = function() {
    if(isMSIE) {
      var arr = [];
      if(!eObj) {
        arr = [
          // ここ（arr = [];内）にtext-shadowを適用させるセレクタの配列を記述
          // セレクタ毎に「カンマ区切り」で配列を追加（カンマを忘れるとエラー発生）
          // Write your text-shadow settings here, like below.
          // { sel : 'h1', shadow : '2px 2px 2px gray' },
          // { sel : 'em', shadow : '1px 1px 1px rgb(0, 100, 100) !important' }
        ];
        for(var sReg = /text\-shadow\s*:\s*([0-9a-zA-Z\s\-\+\*&#\.\(\)%,!"'><\\]+);?/, aTag = _doc.getElementsByTagName('*'), oId = cNum(), i = 0, l = aTag.length; i < l; i++) {
          if(aTag[i].style && aTag[i].style.cssText.match(sReg)) {
            aTag[i].id == '' && (aTag[i].id = 'objId' + oId, oId++);
            arr[arr.length] = { sel : '#' + aTag[i].id, shadow : RegExp.$1 };
          }
        }
        return cssShadowValues().concat(arr);
      }
      else {
        _doc.querySelector(eObj.sel) && _doc.querySelector(eObj.sel).getAttribute('data-pseudo') && eObj.shadow.match(/(none)/) && eObj.shadow.replace(RegExp.$1, '0 0 transparent');
        arr[arr.length] = eObj;
        return arr;
      }
    }
    else {
      return;
    }
  };
  /*  general functions  */
  var getCompStyle = function(elm, pseudo) {
    return (isMSIE && ieVersion < 9) ? elm.currentStyle : pseudo ? _doc.defaultView.getComputedStyle(elm, pseudo) : _doc.defaultView.getComputedStyle(elm, '');
  };
  var setEventAttr = function(obj, attr, func, bool) {
    if(attr.match(/^on/i)) {
      var tAttr = obj.getAttribute(attr);
      if(!isMSIE || (isMSIE && ieVersion > 7)) {
        obj.setAttribute(attr, (!tAttr || bool ? func : removeDupFunc(tAttr.replace(/;$/, '') + ';' + func)));
      }
      else {
        !tAttr || bool ? obj.setAttribute(attr, new Function(func)) : (tAttr = removeDupFunc(tAttr.toString().replace(/\n/g, '').replace(/^function\s+[a-z]+\s*\(\)\s*\{/i, '').replace(/\}$/, '').replace(/^\s*/, '').replace(/\s*$/, '').replace(/;$/, '') + ';' + func), obj.setAttribute(attr, new Function(tAttr)));
      }
    }
  };
  var getAncestObj = function(pElm) {
    var arr = [];
    if(pElm = pElm.parentNode) {
      for(arr[arr.length] = pElm; pElm.nodeName.toLowerCase() != _doc.documentElement.nodeName.toLowerCase();) {
        (pElm = pElm.parentNode) && (arr[arr.length] = pElm);
      }
    }
    return arr;
  };
  var getGeneralObj = function(pElm) {
    var arr = [];
    for((pElm = pElm.previousSibling) && pElm.nodeType == 1 && (arr[arr.length] = pElm); pElm;) {
      (pElm = pElm.previousSibling) && pElm.nodeType == 1 && (arr[arr.length] = pElm);
    }
    return arr;
  };
  var convUnitToPx = function(sUnit, obj) {
    var getUnitRatio = function(sUnit) {
      var elm, val, dId = cNum(), dDiv = _doc.createElement('div'), dBody = _doc.getElementsByTagName('body')[0];
      dDiv.id = 'dummyDiv' + dId;  dId++;
      dDiv.style.height = 0;
      dDiv.style.width = sUnit;
      dDiv.style.visibility = 'hidden';
      dBody.appendChild(dDiv);
      elm = _doc.getElementById(dDiv.id);
      val = elm.offsetWidth;
      dBody.removeChild(elm);
      return val;
    };
    if(sUnit.match(/^0(em|ex|px|cm|mm|in|pt|pc)?$/)) {
      return 0;
    }
    else if(sUnit.match(/^(\-?[0-9\.]+)px$/)) {
      return RegExp.$1 * 1;
    }
    else if(sUnit.match(/^(\-?[0-9\.]+)(cm|mm|in|pt|pc)$/)) {
      return RegExp.$1 * 1 >= 0 ? getUnitRatio(sUnit) : getUnitRatio((RegExp.$1 * -1) + RegExp.$2) * -1;
    }
    else if(sUnit.match(/^(\-?[0-9\.]+)(em|ex)$/)) {
      var sVal = RegExp.$1 * 1 >= 0 ? (getUnitRatio(sUnit) / getUnitRatio('1em')) : (getUnitRatio(sUnit) / getUnitRatio('1em') * -1), arr = getAncestObj(obj), dRoot = _doc.documentElement, fSize = [];
      arr.unshift(obj);  arr[arr.length] = dRoot;
      for(var i = 0, l = arr.length; i < l; i++) {
        fSize[fSize.length] = getCompStyle(arr[i]).fontSize;
      }
      for(i = 0, l = fSize.length; i < l; i++) {
        if(fSize[i].match(/^([0-9\.]+)%$/)) {
          sVal *= (RegExp.$1 / 100);
        }
        else if(fSize[i].match(/^[0-9\.]+(em|ex)$/)) {
          sVal *= (getUnitRatio(fSize[i]) / getUnitRatio('1em'));
        }
        else if(fSize[i].match(/^smaller$/)) {
          sVal /= 1.2;
        }
        else if(fSize[i].match(/^larger$/)) {
          sVal *= 1.2;
        }
        else {
          sVal *= fSize[i].match(/^([0-9\.]+)(px|cm|mm|in|pt|pc)$/) ? getUnitRatio(fSize[i]) :
              fSize[i].match(/^xx\-small$/) ? getUnitRatio(getCompStyle(dRoot).fontSize) / 1.728 :
              fSize[i].match(/^x\-small$/) ? getUnitRatio(getCompStyle(dRoot).fontSize) / 1.44 :
              fSize[i].match(/^small$/) ? getUnitRatio(getCompStyle(dRoot).fontSize) / 1.2 :
              fSize[i].match(/^medium$/) ? getUnitRatio(getCompStyle(dRoot).fontSize) :
              fSize[i].match(/^large$/) ? getUnitRatio(getCompStyle(dRoot).fontSize) * 1.2 :
              fSize[i].match(/^x\-large$/) ? getUnitRatio(getCompStyle(dRoot).fontSize) * 1.44 :
              fSize[i].match(/^xx\-large$/) ? sgetUnitRatio(getCompStyle(dRoot).fontSize) * 1.728 :
              (fSize[i].match(/^([0-9\.]+)([a-z]+)/) && getUnitRatio(fSize[i]));
          break;
        }
      }
      return Math.round(sVal);
    }
  };
  var convPercentTo256 = function(cProf) {
    if(cProf.match(/(rgba?)\(\s*([0-9\.]+%?\s*,\s*[0-9\.]+%?\s*,\s*[0-9\.]+%?)\s*(,\s*[01]?[\.0-9]*)?\s*\)/)) {
      for(var cType = RegExp.$1, arr = RegExp.$2.split(/,/), aChannel = (RegExp.$3 || ''), rgbArr = [], i = 0, l = arr.length; i < l; i++) {
        arr[i].match(/([0-9\.]+)%/) && (arr[i] = Math.round(RegExp.$1 * 255 / 100));
        rgbArr[rgbArr.length] = arr[i];
      }
      return cType + '(' + rgbArr.join(',') + aChannel + ')';
    }
  };
  var revArr = function(arr) {
    for(var rArr = [], i = 0, l = arr.length; i < l; i++) {
      rArr.unshift(arr[i]);
    }
    return rArr;
  };
  var removeDupFunc = function(fStr) {
    for(var arr = fStr.split(/;/), fArr = [], bool, i = 0, l = arr.length; i < l; i++) {
      bool = true;
      arr[i] = arr[i].replace(/^\s+/, '').replace(/\s+$/, '');
      for(var j = i; j < l; j++) {
        arr[j] = arr[j].replace(/^\s+/, '').replace(/\s+$/, '');
        i != j && arr[i].replace(/\s+/g, '') == arr[j].replace(/\s+/g, '') && (bool = false);
      }
      bool && arr[i] != '' && (fArr[fArr.length] = arr[i]);
    }
    return fArr.join(';') + ';';
  };
  /*  end general functions  */
  var getCssValues = function(prop) {
    var sReg = prop.match(/(\-)/) ? prop.replace(RegExp.$1, '\\\-') : prop;
    sReg += '\\s*:\\s*([0-9a-zA-Z\\s\\-\\+\\*&#\\.\\(\\)%,!"\'><\\\\]+);?';
    var getCssRules = function(sSheet) {
      for(var arr = [], sRules = sSheet.cssRules || sSheet.rules, i = 0, l = sRules.length; i < l; i++) {
        var sSelText = sRules[i].selectorText;
        if(sRules[i].type) {
          var sType = sRules[i].type, sStyle = sRules[i].style;
          sType == 3 && (arr = arr.concat(getCssRules(sRules[i].styleSheet)));
          if(sType == 4) {
            /*
            *  matchMedia() polyfill - test whether a CSS media type or media query applies
            *  authors: Scott Jehl, Paul Irish, Nicholas Zakas
            *  Copyright (c) 2011 Scott, Paul and Nicholas.
            *  Dual MIT/BSD license
            *  Original Source matchMedia.js https://github.com/paulirish/matchMedia.js
            *  Revised by Kazz http://asamuzak.jp
            */
            _win.matchMedia = _win.matchMedia || (function(_doc) {
              return function(q) {
                var bool, dHead = _doc.getElementsByTagName('head')[0], dBody = _doc.getElementsByTagName('body')[0], dStyle = _doc.createElement('style'), dDiv = _doc.createElement('div'), dId = cNum();
                dDiv.id = 'dDiv' + dId;  dId++;
                dDiv.setAttribute('style', 'margin : 0; border : 0; padding : 0; height : 0; visibility : hidden;');
                dStyle.setAttribute('media', q);
                dStyle.appendChild(_doc.createTextNode('#' + dDiv.id + '{ width:42px; }'));
                dHead.appendChild(dStyle);
                dBody.appendChild(dDiv);
                bool = dDiv.offsetWidth == 42;
                dBody.removeChild(dDiv);
                dHead.removeChild(dStyle);
                return { matches: bool, media: q };
              };
            })(_doc);
            /*  end matchMedia.js  */
            _win.matchMedia(sRules[i].media.mediaText).matches && (arr = arr.concat(getCssRules(sRules[i])));
          }
          sType == 1 && sStyle.cssText.match(sReg) && (arr[arr.length] = { sel : sSelText, prop : prop, val : sStyle.getPropertyPriority(prop) ? RegExp.$1 + ' !important' : RegExp.$1 });
          sType == 1 && sSelText.match(pseudoReg) && (pArr[pArr.length] = { sel : sSelText, cText : sStyle.cssText });
          sType == 1 && sSelText.match(dynPseudoReg) && sStyle.cssText.match(sReg) && (dArr[dArr.length] = { sel : sSelText, cText : sStyle.getPropertyPriority(prop) ? RegExp.$1 + ' !important' : RegExp.$1 });
        }
        else {
          var sText = sRules[i].style.cssText || sRules[i].cssText;
          if(sText) {
            !sSelText.match(pseudoReg) && sText.match(sReg) && (arr[arr.length] = { sel : sSelText, prop : prop, val : RegExp.$1 });
            sSelText.match(pseudoReg) && (pArr[pArr.length] = { sel : sSelText, cText : sText });
            sSelText.match(dynPseudoReg) && sText.match(sReg) && (dArr[dArr.length] = { sel : sSelText, cText : RegExp.$1 });
            sText.match(countResetReg) && (crArr[crArr.length] = { sel : sSelText, cText : RegExp.$1 });
            sText.match(countIncrementReg) && (ciArr[ciArr.length] = { sel : sSelText, cText : RegExp.$1 });
          }
        }
      }
      return arr;
    };
    if(_doc.styleSheets) {
      for(var arr = [], sArr = _doc.styleSheets, i = 0, l = sArr.length; i < l; i++) {
        if(isMSIE && ieVersion < 9 && sArr[i].imports) {
          for(var iArr = sArr[i].imports, j = 0, k = iArr.length; j < k; j++) {
            iArr[j] != undefined && (arr = arr.concat(getCssRules(iArr[j])));
          }
        }
        arr = arr.concat(getCssRules(sArr[i]));
      }
    }
    for(var aTag = _doc.getElementsByTagName('*'), oId = cNum(), i = 0, l = aTag.length; i < l; i++) {
      if(aTag[i].style && aTag[i].style.cssText.match(sReg)) {
        aTag[i].id == '' && (aTag[i].id = 'objId' + oId, oId++);
        arr[arr.length] = { sel : '#' + aTag[i].id, prop : prop, val : RegExp.$1.match(/important/) ? RegExp.$1 : RegExp.$1 + ' !important' };
      }
    }
    return arr;
  };
  var cssShadowValues = function() {
    for(var arr = [], sArr = getCssValues('text-shadow'), revReg = /^(#[0-9a-fA-F]{3,6})\s+([0-9a-zA-Z\s\-\.\(\)%,!]+)$/, i = 0, l = sArr.length; i < l; i++) {
      arr[arr.length] = { sel : sArr[i].sel, shadow : sArr[i].val.match(revReg) ? RegExp.$2 + ' ' + RegExp.$1 : sArr[i].val };
    }
    return arr;
  };
  var setShadow = function(tObj) {
    var setShadowNodeColor = function(elm) {
      for(var cNode = elm.firstChild; cNode; cNode = cNode.nextSibling) {
        if(cNode.nodeType == 1) {
          !cNode.hasChildNodes() ? cNode.style.visibility = 'hidden' : (!cNode.className.match(/quasiPseudo/) && (cNode.style.color = elm.style.color), setShadowNodeColor(cNode));
        }
      }
    };
    var hideAncestShadow = function(oElm, pElm) {
      for(var cNode = pElm.firstChild; cNode; cNode = cNode.nextSibling) {
        if(cNode.hasChildNodes()) {
          cNode.nodeName.toLowerCase() == oElm.tagName.toLowerCase() && cNode.firstChild.nodeValue == oElm.firstChild.nodeValue ? cNode.style.visibility = 'hidden' : hideAncestShadow(oElm, cNode);
        }
      }
    };
    var boolShadowChild = function(elm) {
      for(var bool = true, arr = getAncestObj(elm), i = 0, l = arr.length; i < l; i++) {
        if(arr[i].tagName.toLowerCase() == 'span' && arr[i].className.match(/dummyShadow/)) {
          bool = false; break;
        }
      }
      return bool;
    };
    if(tObj.shadow != 'invalid') {
      var tShadow = tObj.shadow, tElm = tObj.elm;
      for(var nArr = tElm.childNodes, bool = false, i = 0; i < nArr.length; i++) {
        if(nArr[i].nodeType == 1 && nArr[i].nodeName.toLowerCase() == 'span' && nArr[i].className.match(/dummyShadow/)) {
          if(nArr[i].className.match(/hasImp/)) {
            bool = true;
          }
          else {
            tElm.removeChild(nArr[i]);
            --i;
          }
        }
      }
      if(!bool || tObj.hasImp) {
        for(var aBg, arr = getAncestObj(tElm), i = 0, l = arr.length; i < l; i++) {
          !aBg && (getCompStyle(arr[i]).backgroundColor != 'transparent' || getCompStyle(arr[i]).backgroundImage != 'none') && (aBg = arr[i]);
          for(var cNode = arr[i].firstChild; cNode; cNode = cNode.nextSibling) {
            cNode.nodeType == 1 && cNode.nodeName.toLowerCase() == 'span' && cNode.className.match(/dummyShadow/) && hideAncestShadow(tElm, _doc.getElementById(cNode.id));
          }
        }
        tShadow != 'none' && tShadow.length > 1 && (getCompStyle(tElm).backgroundColor != 'transparent' || getCompStyle(tElm).backgroundImage != 'none') && (tShadow = revArr(tShadow));
        if(tShadow == 'none') {
          for(var cNode = tElm.parentNode.firstChild; cNode; cNode = cNode.nextSibling) {
            if(cNode.nodeName.toLowerCase() == 'span' && cNode.className == 'dummyShadow') {
              getCompStyle(tElm).display == 'inline-block' && (tElm.style.display = 'inline');
              getCompStyle(tElm).position == 'relative' && (tElm.style.position = 'static');
              break;
            }
          }
          if(!eObj && tElm.getAttribute('data-dynpseudo')) {
            var dAttr = tElm.getAttribute('data-dynpseudo') ? unescape(tElm.getAttribute('data-dynpseudo')) : '';
            dAttr.match(/(\|;\|default.+)$/) && (dAttr = dAttr.replace(RegExp.$1, ''));
            dAttr += ('|;|default||0 0 transparent' + (tObj.hasImp ? ' !important;' : ';'));
            tElm.setAttribute('data-dynpseudo', escape(dAttr));
          }
        }
        if(tShadow != 'none' && tElm.hasChildNodes() && boolShadowChild(tElm)) {
          for(var sNode = _doc.createDocumentFragment(), cNode = tElm.firstChild; cNode; cNode = cNode.nextSibling) {
            !(cNode.nodeType == 1 && cNode.nodeName.toLowerCase() == 'span' && cNode.className.match(/dummyShadow/)) && sNode.appendChild(cNode.cloneNode(true));
          }
          ieVersion == 8 && (tElm.innerHTML = tElm.innerHTML);
          for(var pxRad, xPos, yPos, sColor, sOpacity = 0.6, sBox, i = 0, l = tShadow.length; i < l; i++) {
            pxRad = convUnitToPx(tShadow[i].z, tElm);
            xPos = convUnitToPx(tShadow[i].x, tElm) - pxRad + convUnitToPx(getCompStyle(tElm).paddingLeft, tElm);
            getCompStyle(tElm).textAlign == 'center' && (xPos -= ((convUnitToPx(getCompStyle(tElm).paddingLeft, tElm) + convUnitToPx(getCompStyle(tElm).paddingRight, tElm)) / 2));
            getCompStyle(tElm).textAlign == 'right' && (xPos -= convUnitToPx(getCompStyle(tElm).paddingRight, tElm));
            yPos = convUnitToPx(tShadow[i].y, tElm) - pxRad + convUnitToPx(getCompStyle(tElm).paddingTop, tElm);
            ieVersion == 7 && pxRad == 0 && (xPos >= 0 && (xPos -= 1), yPos >= 0 && (yPos -= 1));
            sColor = tShadow[i].cProf || getCompStyle(tElm).color;
            sOpacity = 0.6;
            tShadow[i].cProf && tShadow[i].cProf.match(/rgba\(\s*([0-9]+\s*,\s*[0-9]+\s*,\s*[0-9]+)\s*,\s*([01]?[\.0-9]*)\)/) && (sColor = 'rgb(' + RegExp.$1 + ')', sOpacity = (RegExp.$2 * 1));
            sBox = _doc.createElement('span');
            sBox.id = 'dummyShadow' + sId;  sId++;
            sBox.className = (tObj.hasImp) ? 'dummyShadow hasImp' : 'dummyShadow';
            sBox.style.display = 'block';
            sBox.style.position = 'absolute';
            sBox.style.left = xPos + 'px';
            sBox.style.top = yPos + 'px';
            sBox.style.width = '100%';
            sBox.style.color = sColor;
            sBox.style.filter = 'progid:DXImageTransform.Microsoft.Blur(PixelRadius=' + pxRad + ', MakeShadow=false, ShadowOpacity=' + sOpacity + ')';
            sBox.style.zIndex = -(i + 1);
            getCompStyle(tElm).display == 'inline' && (tElm.style.display = 'inline-block');
            if(getCompStyle(tElm).display == 'table-cell') {
              if(getCompStyle(tElm).verticalAlign == 'middle') {
                if(ieVersion == 9) {
                  tElm.clientHeight >= convUnitToPx(getCompStyle(tElm).height, tElm) && (sBox.style.top = yPos + ((tElm.clientHeight - convUnitToPx(getCompStyle(tElm).height, tElm)) / 2) + 'px');
                }
                else {
                  var getActualHeight = function(cNode, fSize) {
                    var elm, val, dId = cNum(), dDiv = _doc.createElement('div'), dBody = _doc.getElementsByTagName('body')[0];
                    dDiv.id = 'dummyDiv' + dId;  dId++;
                    dDiv.style.fontSize = fSize + 'px';
                    dDiv.style.visibility = 'hidden';
                    dDiv.appendChild(cNode);
                    dBody.appendChild(dDiv);
                    elm = _doc.getElementById(dDiv.id);
                    val = elm.offsetHeight;
                    dBody.removeChild(elm);
                    return val;
                  };
                  var aHeight = getActualHeight(sNode.cloneNode(true), convUnitToPx(getCompStyle(tElm).fontSize, tElm));
                  tElm.clientHeight >= aHeight && (sBox.style.top = yPos + ((tElm.clientHeight - aHeight) / 2) + 'px');
                }
              }
              getCompStyle(tElm).verticalAlign == 'bottom' && (sBox.style.top = '', sBox.style.bottom = yPos + 'px');
            }
            if(!(getCompStyle(tElm).position == 'absolute' || getCompStyle(tElm).position == 'fixed')) {
              tElm.style.position = 'relative';
              ieVersion == 7 && (tElm.style.top = getCompStyle(tElm).paddingTop);
            }
            if(getCompStyle(tElm).backgroundColor != 'transparent' || getCompStyle(tElm).backgroundImage != 'none') {
              getCompStyle(tElm).zIndex != ('auto' || null) ? (sBox.style.zIndex = tElm.style.zIndex) : (tElm.style.zIndex = sBox.style.zIndex = -1);
              ieVersion == 7 && (tElm.style.zIndex = 1, sBox.style.zIndex = -1);
            }
            aBg && aBg.tagName.toLowerCase() != 'body' && (tElm.style.zIndex = 1, sBox.style.zIndex = -1);
            ieVersion == 7 && getCompStyle(tElm).lineHeight.match(/^([0-9\.]+)(em|ex|px|cm|mm|in|pt|pc|%)?$/) && (tElm.style.minHeight = !RegExp.$2 ? convUnitToPx(RegExp.$1 + 'em', tElm) : RegExp.$2 == '%' ? convUnitToPx((RegExp.$1 / 100) + 'em', tElm) : convUnitToPx(RegExp.$1 + RegExp.$2, tElm));
            if(!eObj && tElm.getAttribute('data-dynpseudo')) {
              var dAttr = unescape(tElm.getAttribute('data-dynpseudo'));
              dAttr.match(/(\|;\|default.+)$/) && (dAttr = dAttr.replace(RegExp.$1, ''));
              dAttr += ('|;|default||' + convUnitToPx(tShadow[i].x, tElm) + 'px ' + convUnitToPx(tShadow[i].y, tElm) + 'px ' + pxRad + 'px ' + (tShadow[i].cProf && tShadow[i].cProf.match(/rgba/) ? tShadow[i].cProf : sColor) + ';');
              tElm.setAttribute('data-dynpseudo', escape(dAttr));
            }
            if(ieVersion > 7 && tElm.getAttribute('data-pseudo')) {
              var cloneCSS = unescape(tElm.getAttribute('data-pseudo')).match(/\|;\|/) ? unescape(tElm.getAttribute('data-pseudo')).split('|;|') : [unescape(tElm.getAttribute('data-pseudo'))];
              var setPseudoCSS = function(dynPseudo) {
                var convPseudoUnitToPx = function(pUnit) {
                  if(pUnit.match(/^([0-9\.]+)(em|ex|%)$/)) {
                    pUnit = RegExp.$2 == '%' ? (RegExp.$1 * 1) / 100 : RegExp.$2 == 'ex' ? (RegExp.$1 * 1) * convUnitToPx('1ex', tElm) / convUnitToPx('1em', tElm) : RegExp.$1 * 1;
                    pUnit *= convUnitToPx(getCompStyle(tElm).fontSize, tElm);
                  }
                  else if(pUnit.match(/^([0-9\.]+[a-z]+)$/)) {
                    pUnit = convUnitToPx(RegExp.$1);
                  }
                  return Math.round(pUnit);
                };
                var convPseudoPropVal = function(cText) {
                  for(var propArr = cText.match(/((margin|padding)(\-(top|bottom|left|right))?\s*:\s*([0-9\.]+(em|ex|%|px|cm|mm|in|pt|pc)?\s*){1,4}\s*;)/ig), i = 0, l = propArr.length; i < l; i++) {
                    var pProp = propArr[i].match(/^((margin|padding)(\-(top|bottom|left|right))?)/i) && RegExp.$1,
                      pUnit = propArr[i].match(/(([0-9\.]+(em|ex|%|px|cm|mm|in|pt|pc)?\s*){1,4})/i) && RegExp.$1.replace(/\s+/, ' ').replace(/\s+$/,'');
                    for(var arr = [], aLength, pUnitArr = pUnit.match(/\s+/) ? pUnit.split(/' '/) : [pUnit], j = 0, k = pUnitArr.length; j < k; j++) {
                      arr[arr.length] = convPseudoUnitToPx(pUnitArr[j], cText) + 'px';
                    }
                    aLength = arr.length;
                    cText += pProp.match(/\-(top|bottom|left|right)/i) ? (pProp + ' : ' + arr[0] + ';') :
                      aLength == 1 ? (pProp + '-top : ' + arr[0] + ';' + pProp + '-bottom : ' + arr[0] + ';' + pProp + '-left : ' + arr[0] + ';' + pProp + '-right : ' + arr[0] + ';') :
                      aLength == 2 ? (pProp + '-top : ' + arr[0] + ';' + pProp + '-bottom : ' + arr[0] + ';' + pProp + '-left : ' + arr[1] + ';' + pProp + '-right : ' + arr[1] + ';') :
                      aLength == 3 ? (pProp + '-top : ' + arr[0] + ';' + pProp + '-left : ' + arr[1] + ';' + pProp + '-right : ' + arr[1] + ';' + pProp + '-bottom : ' + arr[2] + ';') :
                      (aLength == 4 && (pProp + '-top : ' + arr[0] + ';' + pProp + '-right : ' + arr[1] + ';' + pProp + '-bottom : ' + arr[2] + ';' + pProp + '-left : ' + arr[3] + ';'));
                  }
                  return cascadeCText(cText);
                };
                var setPseudoCssText = function(cText) {
                  cText = cText.replace(/background(\-[a-z]+?)?\s*:\s*.+?;/ig, '').replace(/[^\-]color\s*:\s*.+?;/i, '');
                  cText.match(/border/i) && (cText += 'border-color : transparent !important; border-image : none !important;');
                  cText.match(/(margin|padding)(\-(top|bottom|left|right))?\s*:\s*([0-9\.]+(em|ex|%|px|cm|mm|in|pt|pc)?\s*){1,4}\s*;/i) && (cText = convPseudoPropVal(cText));
                  if(ieVersion == 8 && cText.match(/font\-size\s*:\s*(.+?)\s*;/i)) {
                    var fSize = RegExp.$1.replace(/^\s*/, '').replace(/\s*$/, '');
                    if(fSize.match(/[0-9\.]+(px|cm|mm|in|pt|pc)/)) {
                      fSize = convUnitToPx(fSize, tElm);
                    }
                    else {
                      if(fSize.match(/[0-9\.]+(em|ex)/)) {
                        fSize = convUnitToPx(fSize, _doc.getElementsByTagName('body')[0]);
                      }
                      else {
                        var dSize = convUnitToPx(getCompStyle(_doc.getElementsByTagName('body')[0]).fontSize, _doc.getElementsByTagName('body')[0]);
                        fSize = fSize.match(/^xx\-small$/) ? dSize / 1.728 :
                            fSize.match(/^x\-small$/) ? dSize / 1.44 :
                            fSize.match(/^small(er)?$/) ? dSize / 1.2 :
                            fSize.match(/^medium$/) ? dSize :
                            fSize.match(/^large(r)?$/) ? dSize * 1.2 :
                            fSize.match(/^x\-large$/) ? dSize * 1.44 :
                            fSize.match(/^xx\-large$/) ? dSize * 1.728 : fSize;
                      }
                    }
                    cText += ('font\-size : ' + Math.round(fSize) + 'px;');
                  }
                  return cascadeCText(cText);
                }
                var getPseudoShadowValue = function(pShadow, i) {
                  var top = left = color = filt = '', rad = 0, opac = 0.6;
                  rad = convPseudoUnitToPx(pShadow[i].z);
                  pShadow[i].cProf.match(/^(rgb\(\s*[0-9]+\s*,\s*[0-9]+\s*,\s*[0-9]+\s*(,\s*[01]?[\.0-9]*\s*)?\)|#[0-9a-fA-F]{3,6}|[a-zA-Z]+)?$/) && (color = 'color : ' + RegExp.$1 + ';');
                  pShadow[i].cProf.match(/rgba\(\s*([0-9]+\s*,\s*[0-9]+\s*,\s*[0-9]+)\s*,\s*([01]?[\.0-9]*)\)/) && (color = 'color : rgb(' + RegExp.$1 + ');', opac = (RegExp.$2 * 1));
                  filt = '-ms-filter : "progid:DXImageTransform.Microsoft.Blur(PixelRadius=' + rad + ', MakeShadow=false, ShadowOpacity=' + opac + ')"; display : inline-block;';
                  left = convPseudoUnitToPx(pShadow[i].x) - rad - xPos - pxRad;
                  top = convPseudoUnitToPx(pShadow[i].y) - rad -yPos - pxRad;
                  return { top : top, left : left, rad : rad, color : color , filt : filt, opac : opac };
                };
                var convertEntityToStr = function(qStr) {
                  for(var arr = qStr.match(/\\[0-9a-f]{1,4}/ig) || [qStr], i = 0, l = arr.length; i < l; i++) {
                    arr[i].match(/\\([0-9a-f]{1,4})/i) && (arr[i] = String.fromCharCode(parseInt(RegExp.$1, 16)));
                    arr[i].match(/&/) && (arr[i] = '&amp;');
                    arr[i].match(/"/) && (arr[i] = '&quot;');
                    arr[i].match(/</) && (arr[i] = '&lt;');
                    arr[i].match(/>/) && (arr[i] = '&gt;');
                  }
                  return arr.join('');
                };
                var getListStyleType = function(style, n) {
                  var getPredefinedStyle = function(style) {
                    var type = '', glyphs = [];
                    /*   Predefined Repeating Styles  */
                    if(style == 'circle') {
                      type = 'repeating';  glyphs = ['25E6'];
                    }
                    if(style == 'disc') {
                      type = 'repeating';  glyphs = ['2022'];
                    }
                    if(style == 'square') {
                      type = 'repeating';  glyphs = ['25FE'];
                    }
                    /*  Predefined Alphabetic Styles  */
                    if(style == ('lower-alpha' || 'lower-latin')) {
                      type = 'alphabetic';
                      glyphs = ['61', '62', '63', '64', '65', '66', '67', '68', '69', '6A', '6B', '6C', '6D', '6E', '6F', '70', '71', '72', '73', '74', '75', '76', '77', '78', '79', '7A'];
                    }
                    if(style == 'lower-greek') {
                      type = 'alphabetic';
                      glyphs = ['3B1', '3B2', '3B3', '3B4', '3B5', '3B6', '3B7', '3B8', '3B9', '3BA', '3BB', '3BC', '3BD', '3BE', '3BF', '3C0', '3C1', '3C3', '3C4', '3C5', '3C6', '3C7', '3C8', '3C9'];
                    }
                    if(style == ('upper-alpha' || 'upper-latin')) {
                      type = 'alphabetic';
                      glyphs = ['41', '42', '43', '44', '45', '46', '47', '48', '49', '4A', '4B', '4C', '4D', '4E', '4F', '50', '51', '52', '53', '54', '55', '56', '57', '58', '59', '5A'];
                    }
                    return { style : style, type : type, glyphs : glyphs };
                  };
                  var glph = '';
                  if(style == 'decimal-leading-zero') {
                    glph = n >= 0 && n < 10 ? '0' + n : n < 0 && n >= -9 ? '-0' + Math.abs(n) : n;
                    return glph;
                  }
                  else if(style.match(/(upper|lower)\-roman/)) {
                    var caseI = RegExp.$1,
                      M = ['', 'M', 'MM', 'MMM', 'MMMM'],
                      C = ['', 'C', 'CC', 'CCC', 'CD', 'D', 'DC', 'DCC', 'DCCC', 'CM'],
                      X = ['', 'X', 'XX', 'XXX', 'XL', 'L', 'LX', 'LXX', 'LXXX', 'XC'],
                      I = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX'];
                    if(n > 0 && n <= 4999) {
                      glph += M[Math.floor(n / 1000)];
                      n %= 1000;
                      glph += C[Math.floor(n / 100)];
                      n %= 100;
                      glph += X[Math.floor(n / 10)];
                      n %= 10;
                      glph += I[n];
                    }
                    return caseI == 'lower' ? glph.toLowerCase() : glph;
                  }
                  else if(style == 'armenian') {
                    var M = ['', '54C', '54D', '54E', '54F', '550', '551', '552', '553', '554'],
                      C = ['', '543', '544', '545', '546', '547', '548', '549', '54A', '54B'],
                      X = ['', '53A', '53B', '53C', '53D', '53E', '53F', '540', '541', '542'],
                      I = ['', '531', '532', '533', '534', '535', '536', '537', '538', '539'];
                    if(n > 0 && n <= 9999) {
                      glph += String.fromCharCode(parseInt(M[Math.floor(n / 1000)], 16));
                      n %= 1000;
                      glph += String.fromCharCode(parseInt(C[Math.floor(n / 100)], 16));
                      n %= 100;
                      glph += String.fromCharCode(parseInt(X[Math.floor(n / 10)], 16));
                      n %= 10;
                      glph += String.fromCharCode(parseInt(I[n], 16));
                    }
                    return glph;
                  }
                  else if(style == 'georgian') {
                    var G = ['', '10F5'],
                      M = ['', '10E9', '10EA', '10EB', '10EC', '10ED', '10EE', '10F4', '10EF', '10F0'],
                      C = ['', '10E0', '10E1', '10E2', '10F3', '10E4', '10E5', '10E6', '10E7', '10E8'],
                      X = ['', '10D8', '10D9', '10DA', '10DB', '10DC', '10F2', '10DD', '10DE', '10DF'],
                      I = ['', '10D0', '10D1', '10D2', '10D3', '10D4', '10D5', '10D6', '10F1', '10D7'];
                    if(n > 0 && n <= 19999) {
                      glph += String.fromCharCode(parseInt(G[Math.floor(n / 10000)], 16));
                      n %= 10000;
                      glph += String.fromCharCode(parseInt(M[Math.floor(n / 1000)], 16));
                      n %= 1000;
                      glph += String.fromCharCode(parseInt(C[Math.floor(n / 100)], 16));
                      n %= 100;
                      glph += String.fromCharCode(parseInt(X[Math.floor(n / 10)], 16));
                      n %= 10;
                      glph += String.fromCharCode(parseInt(I[n], 16));
                    }
                    return glph;
                  }
                  else {
                    var obj = getPredefinedStyle(style), str = '';
                    if(obj.type == 'alphabetic') {
                      n -= 1;
                      str = n % obj.glyphs.length + '';
                      for(n = Math.floor( n / obj.glyphs.length); n >= obj.glyphs.length;) {
                        str = n % obj.glyphs.length + ' ' + str, n = Math.floor(n / obj.glyphs.length);
                        str = n % obj.glyphs.length + ' ' + str;
                      }
                      for(var arr = str.split(' '), i = 0, l = arr.length; i < l; i++) {
                        glph += String.fromCharCode(parseInt(obj.glyphs[arr[i] * 1], 16));
                      }
                      return glph;
                    }
                    else if(obj.type == 'repeating') {
                      glph = String.fromCharCode(parseInt(obj.glyphs[(n - 1) % obj.glyphs.length], 16));
                      return glph;
                    }
                  }
                };
                var getContentValue = function(obj, qContent, pseudo) {
                  var oCIncrement = ieVersion == 9 ? getCompStyle(obj).counterIncrement : obj.style.counterIncrement;
                  var getAncestCountOrder = function(obj, countName) {
                    var incrementReg = countName + '\s+([0-9\-]+)$', incrementVal;
                    for(var orderNum = 1, arr = [], gArr = getGeneralObj(obj), i = 0, l = gArr.length; i < l; i++) {
                      gArr[i].tagName.toLowerCase() == obj.tagName.toLowerCase() && (orderNum += 1);
                    }
                    if(oCIncrement && oCIncrement.match(countName)) {
                      incrementVal = (oCIncrement && oCIncrement.match(incrementReg)) ? RegExp.$1 * 1 : 1;
                      orderNum *= incrementVal;
                    }
                    for(var aArr = getAncestObj(obj), i = 0, l = aArr.length; i < l; i++) {
                      var oCReset = ieVersion == 9 ? getCompStyle(aArr[i]).counterReset : aArr[i].style.counterReset;
                      if(oCReset && oCReset.match(countName)) {
                        oCReset.match(RegExp(countName + '\s([0-9\-]+)')) && (orderNum += (RegExp.$1 * 1));
                        arr[arr.length] = orderNum;
                      }
                      if(aArr[i].tagName.toLowerCase() == obj.tagName.toLowerCase()) {
                        for(var orderNum = 1, gArr = getGeneralObj(aArr[i]), j = 0, k = gArr.length; j < k; j++) {
                          gArr[j].tagName.toLowerCase() == obj.tagName.toLowerCase() && (orderNum += 1);
                        }
                        if(oCIncrement && oCIncrement.match(countName)) {
                          incrementVal = oCIncrement.match(incrementReg) ? RegExp.$1 * 1 : 1;
                          orderNum *= incrementVal;
                        }
                      }
                    }
                    return revArr(arr);
                  };
                  qContent != '' && qContent.match(/^(normal|none|''|"")/) && (qContent = '');
                  if(qContent == '') { return; }
                  if(qContent.match(/attr\([a-z\-]+\)/)) {
                    for(var arr = qContent.match(/attr\(([a-z\-]+)\)/g), i = 0, l = arr.length; i < l; i++) {
                      arr[i] = arr[i].match(/attr\(([a-z\-]+)\)/) && RegExp.$1;
                      obj.getAttribute(arr[i]) && (qContent = qContent.replace(RegExp('\\s*attr\\(' + arr[i] + '\\)\\s*'), obj.getAttribute(arr[i])));
                    }
                  }
                  if(qContent.match(/counters\(\s*.+?\s*,\s*["'].+?["']\s*(,\s*[^\s]+?)?\s*\)/)) {
                    for(var arr = qContent.match(/counters\(\s*.+?\s*,\s*["'].+?["']\s*(,\s*[^\s]+?)?\s*\)/g), i = 0, l = arr.length; i < l; i++) {
                      if(arr[i].match(/counters\(\s*(.+?)\s*,\s*["'](.+?)["']\s*(,\s*[^\s]+?)?\s*\)/)) {
                        var countName = RegExp.$1, joinMk = RegExp.$2, countStyle = RegExp.$3 || '';
                        countStyle != '' && (countStyle = countStyle.match(/^,\s*([^\s]+?)\s*$/) && RegExp.$1);
                        if((oCIncrement && oCIncrement == 'none') || countStyle == 'none') {
                          qContent = qContent.replace(RegExp('\\s*counters\\(\\s*' + countName + '\\s*,\\s*["\']' + joinMk + '["\']\\s*(,\\s*' + countStyle + '?)?\\s*\\)\\s*'), '');
                        }
                        else {
                          var countArr = getAncestCountOrder(obj, countName);
                          if(countStyle != '' && countStyle != 'decimal') {
                            for(var j = 0, k = countArr.length; j < k; j++) {
                              countArr[j] = getListStyleType(countStyle, countArr[j]);
                            }
                          }
                          qContent = qContent.replace(RegExp('\\s*counters\\(\\s*' + countName + '\\s*,\\s*["\']' + joinMk + '["\']\\s*(,\\s*' + countStyle + '?)?\\s*\\)\\s*'), countArr.join(joinMk));
                        }
                      }
                    }
                  }
                  if(qContent.match(/counter\(.+?\)/)) {
                    for(var arr = qContent.match(/counter\(.+?\)/g), i = 0, l = arr.length; i < l; i++) {
                      if(arr[i].match(/counter\(\s*([^\s]+?)\s*(,\s*[^\s]+?\s*)?\)/)) {
                        var countName = RegExp.$1, countStyle = RegExp.$2 || '';
                        countStyle != '' && (countStyle = countStyle.match(/^,\s*([^\s]+?)\s*$/) && RegExp.$1);
                        if((oCIncrement && oCIncrement == 'none') || countStyle == 'none') {
                          qContent = qContent.replace(RegExp('\\s*counter\\(\\s*' + countName + '\\s*(,\\s*' + countStyle + '\\s*)?\\)\\s*'), '');
                        }
                        else {
                          for(var orderNum = 1, oArr = getGeneralObj(obj), j = 0, k = oArr.length; j < k; j++) {
                            var oCReset = ieVersion == 9 ? getCompStyle(oArr[j]).counterReset : oArr[j].style.counterReset;
                            if(oCReset && oCReset.match(countName)) {
                              oCReset.match(RegExp(countName + '\\s+([0-9\\\-]+)?')) && (orderNum += (RegExp.$1 * 1));
                              break;
                            }
                            oArr[j].tagName.toLowerCase() == obj.tagName.toLowerCase() && (orderNum += 1);
                          }
                          if(oCIncrement && oCIncrement.match(countName)) {
                            orderNum *= (oCIncrement.match(RegExp(countName + '\s+([0-9\-]+)$')) ? RegExp.$1 * 1 : 1);
                            countStyle != '' && countStyle != 'decimal' && (orderNum = getListStyleType(countStyle, orderNum));
                          }
                          qContent = qContent.replace(RegExp('counter\\(\\s*' + countName + '\\s*(,\\s*' + countStyle + '\\s*)?\\)'), orderNum);
                        }
                      }
                    }
                  }
                  if(qContent.match(/['"].+?['"]/)) {
                    for(var arr = qContent.match(/['"].+?['"]/g), i = 0, l = arr.length; i < l; i++) {
                      if(arr[i].match(/['"](.+?)['"]/)) {
                        var qStr = qReg = RegExp.$1;
                        qReg = qReg.replace(/\\/g, '\\\\');
                        if(qStr.match(/(\\([0-9a-f]{1,4}){1,})/i)) {
                          for(var qEntArr = qStr.match(/(\\([0-9a-f]{1,4}){1,})/ig), j = 0, k = qEntArr.length; j < k; j++) {
                            qStr = qStr.replace(qEntArr[j], convertEntityToStr(qEntArr[j]));
                          }
                        }
                        qContent = qContent.replace(RegExp('\\s*[\'"]' + qReg + '[\'"]\\s*'), qStr.replace(/\s/g, '&#160;'));
                      }
                    }
                  }
                  if(qContent.match(/url\(.+?\)/)) {
                    for(var arr = qContent.match(/url\(.+?\)/g), i = 0, l = arr.length; i < l; i++) {
                      arr[i] = arr[i].match(/url\((.+?)\)/) && RegExp.$1;
                      qContent = qContent.replace(RegExp('\\s*url\\(' + arr[i] + '\\)\\s*'), '||<img src="' + arr[i].replace(/["']/g, '') + '" />||').replace(/^\|\|/, '').replace(/\|\|$/, '');
                    }
                  }
                  if(qContent.match(/(open|close)\-quote/) && getCompStyle(obj).quotes) {
                    for(var arr = qContent.match(/(open|close)\-quote/g), i = 0, l = arr.length; i < l; i++) {
                      arr[i] = arr[i].match(/((no\-)?(open|close)\-quote)/) && RegExp.$1;
                      var nestLv = 0, quotArr = getCompStyle(obj).quotes.split(' ') || ['', ''];
                      if(quotArr.length > 2) {
                        for(var pArr = getAncestObj(obj), j = 0, k = pArr.length; j < k; j++) {
                          if(pArr[j].tagName.toLowerCase() == obj.tagName.toLowerCase()) {
                            if(ieVersion == 9 && getCompStyle(pArr[j], pseudo).content.match(/no\-(open|close)\-quote/)) {
                              arr[i].match(/open\-quote/) && (nestLv += 2);
                              arr[i].match(/close\-quote/) && (nestLv -= 2);
                            }
                            else {
                              nestLv += 2;
                            }
                          }
                        }
                      }
                      arr[i].match(/open\-quote/) && (qContent = qContent.replace(/\s*open\-quote\s*/g, quotArr[0 + nestLv].replace(/^["']/, '').replace(/["']$/, '')));
                      arr[i].match(/close\-quote/) && (qContent = qContent.replace(/\s*close\-quote\s*/g, quotArr[1 + nestLv].replace(/^["']/, '').replace(/["']$/, '')));
                      arr[i].match(/no\-(open|close)\-quote/) && (qContent = '');
                    }
                  }
                  for(var qNode = _doc.createDocumentFragment(), arr = qContent.match(/\|\|/) ? qContent.split('||') : [qContent], i = 0, l = arr.length; i < l; i++) {
                    if(arr[i].match(/^<([a-z]+)\s/)) {
                      var elm = _doc.createElement(RegExp.$1);
                      elm.src = arr[i].match(/src="(.+?)"/) && RegExp.$1;
                      elm.style.visibility = 'hidden';
                      qNode.appendChild(elm);
                    }
                    else {
                      qNode.appendChild(_doc.createTextNode(arr[i].replace(/&#160;/g, '\u00a0')));
                    }
                  }
                  return qNode;
                };
                var setQuasiFirstLetter = function(cNode, cText, n) {
                  var setFirstLetter = function(dNode) {
                    var puncArr = [33, 34, 35, 37, 38, 39, 40, 41, 42, 44, 46, 47, 58, 59, 63, 64, 91, 92, 93, 123, 125, 161, 167, 171, 182, 183, 187, 191, 894, 903, 1370, 1371, 1372, 1373, 1374, 1375, 1417, 1472, 1475, 1478, 1523, 1524, 1545, 1546, 1548, 1549, 1563, 1566, 1567, 1642, 1643, 1644, 1645, 1748, 1792, 1793, 1794, 1795, 1796, 1797, 1798, 1799, 1800, 1801, 1802, 1803, 1804, 1805, 2039, 2040, 2041, 2096, 2097, 2098, 2099, 2100, 2101, 2102, 2103, 2104, 2105, 2106, 2107, 2108, 2109, 2110, 2142, 2404, 2405, 2416, 2800, 3572, 3663, 3674, 3675, 3844, 3845, 3846, 3847, 3848, 3849, 3850, 3851, 3852, 3853, 3854, 3855, 3856, 3857, 3858, 3860, 3898, 3899, 3900, 3901, 3973, 4048, 4049, 4050, 4051, 4052, 4057, 4058, 4170, 4171, 4172, 4173, 4174, 4175, 4347, 4960, 4961, 4962, 4963, 4964, 4965, 4966, 4967, 4968, 5741, 5742, 5787, 5788, 5867, 5868, 5869, 5941, 5942, 6100, 6101, 6102, 6104, 6105, 6106, 6144, 6145, 6146, 6147, 6148, 6149, 6151, 6152, 6153, 6154, 6468, 6469, 6686, 6687, 6816, 6817, 6818, 6819, 6820, 6821, 6822, 6824, 6825, 6826, 6827, 6828, 6829, 7002, 7003, 7004, 7005, 7006, 7007, 7008, 7164, 7165, 7166, 7167, 7227, 7228, 7229, 7230, 7231, 7294, 7295, 7360, 7361, 7362, 7363, 7364, 7365, 7366, 7367, 7379, 8214, 8215, 8216, 8217, 8218, 8219, 8220, 8221, 8222, 8223, 8224, 8225, 8226, 8227, 8228, 8229, 8230, 8231, 8240, 8241, 8242, 8243, 8244, 8245, 8246, 8247, 8248, 8249, 8250, 8251, 8252, 8253, 8254, 8257, 8258, 8259, 8261, 8262, 8263, 8264, 8265, 8266, 8267, 8268, 8269, 8270, 8271, 8272, 8273, 8275, 8277, 8278, 8279, 8280, 8281, 8282, 8283, 8284, 8285, 8286, 8317, 8318, 8333, 8334, 9001, 9002, 10088, 10089, 10090, 10091, 10092, 10093, 10094, 10095, 10096, 10097, 10098, 10099, 10100, 10101, 10181, 10182, 10214, 10215, 10216, 10217, 10218, 10219, 10220, 10221, 10222, 10223, 10627, 10628, 10629, 10630, 10631, 10632, 10633, 10634, 10635, 10636, 10637, 10638, 10639, 10640, 10641, 10642, 10643, 10644, 10645, 10646, 10647, 10648, 10712, 10713, 10714, 10715, 10748, 10749, 11513, 11514, 11515, 11516, 11518, 11519, 11632, 11776, 11777, 11778, 11779, 11780, 11781, 11782, 11783, 11784, 11785, 11786, 11787, 11788, 11789, 11790, 11791, 11792, 11793, 11794, 11795, 11796, 11797, 11798, 11800, 11801, 11803, 11804, 11805, 11806, 11807, 11808, 11809, 11810, 11811, 11812, 11813, 11814, 11815, 11816, 11817, 11818, 11819, 11820, 11821, 11822, 11824, 11825, 11826, 11827, 11828, 11829, 11830, 11831, 11832, 11833, 12289, 12290, 12291, 12296, 12297, 12298, 12299, 12300, 12301, 12302, 12303, 12304, 12305, 12308, 12309, 12310, 12311, 12312, 12313, 12314, 12315, 12317, 12318, 12319, 12349, 12539, 42238, 42239, 42509, 42510, 42511, 42611, 42622, 42738, 42739, 42740, 42741, 42742, 42743, 43124, 43125, 43126, 43127, 43214, 43215, 43256, 43257, 43258, 43310, 43311, 43359, 43457, 43458, 43459, 43460, 43461, 43462, 43463, 43464, 43465, 43466, 43467, 43468, 43469, 43486, 43487, 43612, 43613, 43614, 43615, 43742, 43743, 43760, 43761, 44011, 64830, 64831, 65040, 65041, 65042, 65043, 65044, 65045, 65046, 65047, 65048, 65049, 65072, 65077, 65078, 65079, 65080, 65081, 65082, 65083, 65084, 65085, 65086, 65087, 65088, 65089, 65090, 65091, 65092, 65093, 65094, 65095, 65096, 65097, 65098, 65099, 65100, 65104, 65105, 65106, 65108, 65109, 65110, 65111, 65113, 65114, 65115, 65116, 65117, 65118, 65119, 65120, 65121, 65128, 65130, 65131, 65281, 65282, 65283, 65285, 65286, 65287, 65288, 65289, 65290, 65292, 65294, 65295, 65306, 65307, 65311, 65312, 65339, 65340, 65341, 65371, 65373, 65375, 65376, 65377, 65378, 65379, 65380, 65381, 65792, 65793, 65794, 66463, 66512, 67671, 67871, 67903, 68176, 68177, 68178, 68179, 68180, 68181, 68182, 68183, 68184, 68223, 68409, 68410, 68411, 68412, 68413, 68414, 68415, 69703, 69704, 69705, 69706, 69707, 69708, 69709, 69819, 69820, 69822, 69823, 69824, 69825, 69952, 69953, 69954, 69955, 70085, 70086, 70087, 70088, 74864, 74865, 74866, 74867];
                    for(var qNode = _doc.createElement('span'), rNode = _doc.createDocumentFragment(), str = '', isPunc = followPunc = false, eNode = dNode.nodeValue, i = 0, l = eNode.length; i < l; i++) {
                      for(var j = 0, k = puncArr.length; j < k; j++) {
                        isPunc = false;
                        if(eNode.charCodeAt(i) == puncArr[j]) {
                          isPunc = true;
                          break;
                        }
                      }
                      if(isPunc) {
                        str += eNode.charAt(i);
                      }
                      else {
                        if(followPunc) { break; }
                        !followPunc && (str += eNode.charAt(i));
                        for(var j = 0, k = puncArr.length; j < k; j++) {
                          followPunc = false;
                          if(eNode.charCodeAt(i + 1) == puncArr[j]) {
                            followPunc = true; break;
                          }
                        }
                        if(!followPunc) { break; }
                      }
                    }
                    qNode.appendChild(_doc.createTextNode(str));
                    qNode.className = 'qFLetter quasiPseudo';
                    if(cText.match(/text\-shadow\s*:\s*((.+?)+?(\s*!\s*important)?);/i)) {
                      var pseudoShadow = getShadowValue(RegExp.$1);
                      if(pseudoShadow.length > n) {
                        var qStyle = getPseudoShadowValue(pseudoShadow, n);
                        cText = cText.replace(/text\-shadow\s*:\s*((.+?)+?(!\s*important)?);/i, '');
                        if(dNode.parentNode && dNode.parentNode.className.match(/qBefore/)) {
                          qStyle.left -= convUnitToPx(dNode.parentNode.style.left);
                          qStyle.top -= convUnitToPx(dNode.parentNode.style.top);
                        }
                        qNode.setAttribute('style', cascadeCText(cText + 'top : ' + qStyle.top + 'px; left : ' + qStyle.left + 'px;' + qStyle.color + qStyle.filt + 'position : relative;'));
                      }
                      else {
                        cText += 'visibility : hidden !important;';
                        qNode.setAttribute('style', cascadeCText(cText));
                      }
                    }
                    else {
                      qNode.setAttribute('style', cascadeCText(cText));
                    }
                    if(dNode.parentNode && dNode.parentNode.className.match(/quasiPseudo/)) {
                      rNode.appendChild(qNode);
                      rNode.appendChild(_doc.createTextNode(dNode.nodeValue.replace(str, '')));
                    }
                    else {
                      var pNode = _doc.createElement('span');
                      pNode.className = 'quasiPseudo';
                      pNode.setAttribute('style', '-ms-filter : none; display : inline; position : relative;');
                      pNode.appendChild(qNode);
                      rNode.appendChild(pNode);
                      rNode.appendChild(_doc.createTextNode(dNode.nodeValue.replace(str, '')));
                    }
                    return { qNode : rNode, isPunc : isPunc };
                  };
                  var cType = cNode.nodeType, qNode;
                  if(cType == 1) {
                    for(var dNode = cNode.firstChild; dNode; dNode = dNode.firstChild) {
                      if(dNode.nodeType == 3) {
                        qNode = setFirstLetter(dNode);
                        dNode.parentNode.replaceChild(qNode.qNode, dNode);
                      }
                    }
                  }
                  else if(cType == 3) {
                    qNode = setFirstLetter(cNode);
                    cNode = _doc.createDocumentFragment();
                    cNode.appendChild(qNode.qNode);
                  }
                  return { qNode : cNode, isPunc : (cText.match(/float\s*:\s*(left|right)/i) ? false : qNode.isPunc) };
                };
                var getFirstLineHeight = function(qNode) {
                  var elm, fSize = convUnitToPx(getCompStyle(tElm, ':first-line').fontSize, tElm), cWidth = 0, fWidth = tElm.offsetWidth, dId = cNum(), dDiv = _doc.createElement('div'), dBody = _doc.getElementsByTagName('body')[0];
                  dDiv.id = 'dummyDiv' + dId;  dId++;
                  dDiv.style.visibility = 'hidden';
                  dDiv.appendChild(qNode);
                  dBody.appendChild(dDiv);
                  elm = _doc.getElementById(dDiv.id);
                  if(elm) {
                    for(var cSize, cNode = elm.firstChild; cNode; cNode = cNode.nextSibling) {
                      var cType = cNode.nodeType;
                      if(cType == 1 && cNode.hasChildNodes()) {
                        if(((cWidth += cNode.offsetWidth) <= fWidth) && !cNode.className.match(/quasiPseudo/)) {
                          (cSize = convUnitToPx(getCompStyle(cNode).fontSize, cNode)) > fSize && (fSize = cSize);
                          for(var dNode = _doc.createDocumentFragment(), arr = cNode.childNodes, i = 0, l = arr.length; i < l; i++) {
                            dNode.appendChild(arr[i]);
                          }
                          (cSize = getFirstLineHeight(dNode)) > fSize && (fSize = cSize);
                        }
                      }
                      else if(cType == 1 && !cNode.hasChildNodes()) {
                        (cWidth += cNode.offsetWidth) <= fWidth &&
                        (cSize = cNode.offsetHeight) > fSize && (fSize = cSize);
                        cNode.style.visibility = 'hidden;';
                      }
                      else if(cType == 3) {
                        cWidth += (cNode.length * convUnitToPx(getCompStyle(elm).fontSize, elm));
                      }
                      if(cWidth >= fWidth) {
                        break;
                      }
                    }
                  }
                  dBody.removeChild(elm);
                  return fSize;
                };
                for(var cText = '', j = 0, k = cloneCSS.length; j < k; j++) {
                  var pseudoSel = cloneCSS[j].split('||')[0];
                  if(!pseudoSel.match(dynPseudoReg) && pseudoSel.match(/(::?(before|after))/)) {
                    var pseudo = RegExp.$1.replace(/:/g, '');
                    cText = setPseudoCssText(cascadeCText(cloneCSS[j].split('||')[1].replace(/;$/, '') + ';' + (dynPseudo ? dynPseudo.pText : '')));
                    var qContent = getContentValue(tElm, (cText.match(/content\s*:\s*((.+?)+?(!\s*important)?);/i) ? RegExp.$1.replace(/^\s*/, '').replace(/\s*$/, '') : ''), (pseudoSel.match(/(::?(before|after))/) && RegExp.$1));
                    if(qContent) {
                      cText = cText.replace(/content\s*:\s*(.+?)\s*(!\s*important)?;/i, '');
                      var qNode = _doc.createElement('span'), rNode = _doc.createElement('span');
                      qNode.className = pseudo == 'before' ? 'qBefore quasiPseudo' : (pseudo == 'after' && 'qAfter quasiPseudo');
                      if(cText.match(/text\-shadow\s*:\s*((.+?)+?(\s*!\s*important)?);/i)) {
                        var pseudoShadow = getShadowValue(RegExp.$1);
                        if(pseudoShadow.length > i) {
                          var qStyle = getPseudoShadowValue(pseudoShadow, i);
                          qNode.setAttribute('style', cascadeCText('position : relative; top : ' + qStyle.top + 'px; left : ' + qStyle.left + 'px;' + qStyle.color + qStyle.filt));
                          cText += '-ms-filter : none; display : inline;';
                        }
                        else {
                          cText += 'visibility : hidden !important;';
                        }
                      }
                      qNode.appendChild(qContent);
                      cText = cText.replace(/text\-shadow\s*:\s*((.+?)+?(!\s*important)?);/i, '');
                      rNode.className = 'quasiPseudo';
                      rNode.setAttribute('style', cascadeCText(cText + 'position : relative;'));
                      rNode.appendChild(qNode);
                      pseudo == 'before' ? sNode.firstChild.className == 'quasiPseudo' ? sNode.replaceChild(rNode, sNode.firstChild) : sNode.insertBefore(rNode, sNode.firstChild) : (pseudo == 'after' && (sNode.lastChild.className == 'quasiPseudo' ? sNode.replaceChild(rNode, sNode.lastChild) : sNode.appendChild(rNode)));
                    }
                  }
                  if(!pseudoSel.match(dynPseudoReg) && pseudoSel.match(/(::?first\-letter)/)) {
                    cText = setPseudoCssText(cascadeCText(cloneCSS[j].split('||')[1].replace(/;$/, '') + ';' + (dynPseudo ? dynPseudo.fLetter : '')));
                    var qNode = _doc.createDocumentFragment(), fLetter;
                    if(sNode.firstChild.nodeType == 1) {
                      fLetter = setQuasiFirstLetter(sNode.firstChild.cloneNode(true), cText, i);
                      qNode.appendChild(fLetter.qNode);
                      if(fLetter.isPunc) {
                        fLetter = setQuasiFirstLetter(sNode.childNodes[1].cloneNode(true), cText, i);
                        qNode.appendChild(fLetter.qNode);
                        sNode.replaceChild(qNode, sNode.childNodes[1]);
                        sNode.removeChild(sNode.firstChild);
                      }
                      else {
                        sNode.replaceChild(qNode, sNode.firstChild);
                      }
                    }
                    else if(sNode.firstChild.nodeType == 3) {
                      fLetter = setQuasiFirstLetter(sNode.firstChild.cloneNode(true), cText, i);
                      qNode.appendChild(fLetter.qNode);
                      sNode.replaceChild(qNode, sNode.firstChild);
                    }
                  }
                }
                if(ieVersion == 9) {
                  for(var cText = '', j = 0, k = cloneCSS.length; j < k; j++) {
                    var pseudoSel = cloneCSS[j].split('||')[0];
                    if(!pseudoSel.match(dynPseudoReg) && pseudoSel.match(/(::?first\-line)/)) {
                      for(var qNode = sNode.cloneNode(true), rNode = _doc.createElement('span'), cNode = qNode.firstChild; cNode; cNode = cNode.nextSibling) {
                        if(cNode.nodeType == 1 && cNode.className == 'quasiPseudo') {
                          var attr = cNode.getAttribute('style') ? cNode.getAttribute('style') : '';
                          cNode.setAttribute('style', cascadeCText(attr + 'visibility : hidden !important;'));
                        }
                      }
                      cText = setPseudoCssText(cascadeCText(cloneCSS[j].split('||')[1].replace(/;$/, '') + ';' + (dynPseudo ? dynPseudo.fLine : '')));
                      var fHeight = getCompStyle(tElm, ':first-line').lineHeight;
                      fHeight = fHeight.match(/^[0-9\.]+$/) ? (fHeight + 'em') : fHeight.match(/^([0-9]+)%$/) ? ((RegExp.$1 * 1 / 100) + 'em') : fHeight;
                      fHeight = (convUnitToPx(fHeight, tElm) - convUnitToPx(getCompStyle(tElm, ':first-line').fontSize, tElm)) / 2;
                      cText += 'height : ' + (getFirstLineHeight(qNode.cloneNode(true)) + fHeight) + 'px; overflow-y : hidden;';
                      if(cText.match(/text\-shadow\s*:\s*((.+?)+?(\s*!\s*important)?);/i)) {
                        var pseudoShadow = getShadowValue(RegExp.$1);
                        if(pseudoShadow.length > i) {
                          var qStyle = getPseudoShadowValue(pseudoShadow, i);
                          cText = cText.replace(/text\-shadow\s*:\s*((.+?)+?(!\s*important)?);/i, '');
                          rNode.setAttribute('style', cascadeCText(cText + 'top : ' + qStyle.top + 'px; left : ' + qStyle.left + 'px;' + qStyle.color + qStyle.filt + 'position : absolute; display : block;'));
                        }
                        else {
                          cText += 'visibility : hidden !important;';
                          cText = cText.replace(/text\-shadow\s*:\s*((.+?)+?(!\s*important)?);/i, '');
                          rNode.setAttribute('style', cascadeCText(cText + 'position : absolute; display : block;'));
                        }
                      }
                      else {
                        rNode.setAttribute('style', cascadeCText(cText + 'position : absolute; display : block;'));
                      }
                      rNode.className = 'qFLine quasiPseudo';
                      rNode.appendChild(qNode);
                      sNode.insertBefore(rNode, sNode.firstChild);
                    }
                  }
                }
              };
              var getDynCSS = function(dynPseudo) {
                var obj = { pText : '', fLetter : '', fLine : '' }, pseudoMatchReg = '';
                for(var pseudoMatchReg = '', i = 0, l = dynPseudo.length; i < l; i++) {
                  var dyn = dynPseudo[i].match(/_/) ? dynPseudo[i].split('_') : [dynPseudo[i]];
                  if(dyn.length == 1) {
                    pseudoMatchReg = '(((::?(before|after))|(:' + dyn[0] + ')){2})';
                  }
                  else if(dyn.length > 1) {
                    pseudoMatchReg = '(((::?(before|after))|(:(';
                    for(var j = 0, k = dyn.length; j < k; j++) {
                      pseudoMatchReg += (dyn[j] + '|');
                    }
                    pseudoMatchReg = pseudoMatchReg.replace(/\|$/, '') + '){' + dyn.length + '})){2})';
                  }
                  for(var j = 0, k = cloneCSS.length; j < k; j++) {
                    if(cloneCSS[j].split('||')[0].match(pseudoMatchReg)) {
                      obj.pText = cloneCSS[j].split('||')[1].replace(/;$/, '') + ';';
                      break;
                    }
                  }
                  if(obj.pText != '') { break; }
                }
                for(var pseudoMatchReg = '', i = 0, l = dynPseudo.length; i < l; i++) {
                  if(dyn.length == 1) {
                    pseudoMatchReg = '(((::?first\-letter)|(:' + dyn[0] + ')){2})';
                  }
                  else if(dyn.length > 1) {
                    pseudoMatchReg = '(((::?first\-letter)|(:(';
                    for(var j = 0, k = dyn.length; j < k; j++) {
                      pseudoMatchReg += (dyn[j] + '|');
                    }
                    pseudoMatchReg = pseudoMatchReg.replace(/\|$/, '') + '){' + dyn.length + '})){2})';
                  }
                  for(var j = 0, k = cloneCSS.length; j < k; j++) {
                    if(cloneCSS[j].split('||')[0].match(pseudoMatchReg)) {
                      obj.fLetter = cloneCSS[j].split('||')[1].replace(/;$/, '') + ';';
                      break;
                    }
                  }
                  if(obj.fLetter != '') { break; }
                }
                for(var pseudoMatchReg = '', i = 0, l = dynPseudo.length; i < l; i++) {
                  if(dyn.length == 1) {
                    pseudoMatchReg = '(((::?first\-line)|(:' + dyn[0] + ')){2})';
                  }
                  else if(dyn.length > 1) {
                    pseudoMatchReg = '(((::?first\-line)|(:(';
                    for(var j = 0, k = dyn.length; j < k; j++) {
                      pseudoMatchReg += (dyn[j] + '|');
                    }
                    pseudoMatchReg = pseudoMatchReg.replace(/\|$/, '') + '){' + dyn.length + '})){2})';
                  }
                  for(var j = 0, k = cloneCSS.length; j < k; j++) {
                    if(cloneCSS[j].split('||')[0].match(pseudoMatchReg)) {
                      obj.fLine = cloneCSS[j].split('||')[1].replace(/;$/, '') + ';';
                      break;
                    }
                  }
                  if(obj.fLine != '') { break; }
                }
                return obj;
              };
              if(eObj) {
                var evt = this.event, eType = evt.type, x = evt.clientX, y = evt.clientY, cRect = tElm.getBoundingClientRect(), isHover = (cRect.left <= x && cRect.right >= x && cRect.top <= y && cRect.bottom >= y), isActive = tElm == _doc.activeElement;
                setPseudoCSS(
                  eType == 'mouseover' ? isActive ? getDynCSS(['focus_hover', 'hover']) : getDynCSS(['hover']) :
                  eType == 'mouseout' && isActive ? getDynCSS(['focus']) :
                  eType == 'mousedown' ? isActive ? getDynCSS(['active_focus_hover', 'active_hover', 'active_focus', 'active', 'focus_hover', 'hover', 'focus']) : getDynCSS(['active_hover', 'active', 'hover']) :
                  eType == 'mouseup' ? isHover ? getDynCSS(['focus_hover', 'hover', 'focus']) : getDynCSS(['focus']) :
                  eType == 'keydown' ? isHover ? getDynCSS(['active_focus_hover', 'active_hover', 'active_focus', 'active', 'focus_hover', 'hover', 'focus']) : getDynCSS(['active_focus', 'active', 'focus']) :
                  eType == 'keyup' ? isHover ? getDynCSS(['focus_hover', 'hover', 'focus']) : getDynCSS(['focus']) :
                  eType == 'focus' ? isHover ? getDynCSS(['active_focus_hover', 'active_hover', 'active_focus', 'active', 'focus_hover', 'hover', 'focus']) : getDynCSS(['focus']) : ''
                );
              }
              else {
                setPseudoCSS();
              }
            }
            sBox.appendChild(sNode.cloneNode(true));
            tElm.appendChild(sBox);
            setShadowNodeColor(_doc.getElementById(sBox.id));
          }
        }
      }
    }
  };
  var getTargetObj = function(sObj) {
    var arr = _doc.querySelectorAll(sObj.sel);
    if(arr.length > 0) {
      for(var i = 0, l = arr.length; i < l; i++) {
        var aAttr = arr[i].getAttribute('data-pseudo');
        if(ieVersion > 8 || (ieVersion == 7 && (!aAttr || (aAttr && !unescape(aAttr).match(/::?first\-(letter|line)/)))) || (ieVersion == 8 && (!aAttr || (aAttr && !unescape(aAttr).match(/(counters\(.+?\)|no\-(open|close)\-quote)/))))) {
          sObj.elm = arr[i];
          setShadow(sObj);
        }
      }
    }
  };
  var getShadowValue = function(shadow) {
    if(shadow.match(/none/)) {
      return 'none';
    }
    else {
      for(var val = [], arr = shadow.match(/((rgba?\(\s*[0-9\.]+%?\s*,\s*[0-9\.]+%?\s*,\s*[0-9\.]+%?\s*(,\s*[01]?[\.0-9]*\s*)?\)|#[0-9a-fA-F]{3,6}|[a-zA-Z]+)\s)?(\-?[0-9\.]+(em|ex|px|cm|mm|in|pt|pc)?\s*){2,3}(rgba?\(\s*[0-9\.]+%?\s*,\s*[0-9\.]+%?\s*,\s*[0-9\.]+%?\s*(,\s*[01]?[\.0-9]*\s*)?\)|#[0-9a-fA-F]{3,6}|[a-zA-Z]+)?/g), i = 0, l = arr.length; i < l; i++) {
        val[i] = { x : '0', y : '0', z : '0', cProf : null };
        var uArr = arr[i].match(/\-?[0-9\.]+(em|ex|px|cm|mm|in|pt|pc)?\s+\-?[0-9\.]+(em|ex|px|cm|mm|in|pt|pc)?(\s+[0-9\.]+(em|ex|px|cm|mm|in|pt|pc)?)?/);
        if(uArr = uArr[0].split(/\s+/), uArr[0].match(/^(\-?[0-9\.]+(em|ex|px|cm|mm|in|pt|pc)?)$/) && uArr[1].match(/^(\-?[0-9\.]+(em|ex|px|cm|mm|in|pt|pc)?)$/)) {
          uArr.length >= 2 && (val[i].x = uArr[0], val[i].y = uArr[1]);
          uArr.length == 3 && uArr[2].match(/^([0-9\.]+(em|ex|px|cm|mm|in|pt|pc)?)$/) && (val[i].z = uArr[2]);
          arr[i].match(/%/) && (arr[i] = convPercentTo256(arr[i]));
          arr[i].match(/^(rgba?\(\s*[0-9]+\s*,\s*[0-9]+\s*,\s*[0-9]+\s*(,\s*[01]?[\.0-9]*\s*)?\)|[a-zA-Z]+)/) ? (val[i].cProf = RegExp.$1) :
          arr[i].match(/\s(rgba?\(\s*[0-9]+\s*,\s*[0-9]+\s*,\s*[0-9]+\s*(,\s*[01]?[\.0-9]*\s*)?\)|#[0-9a-fA-F]{3,6}|[a-zA-Z]+)$/) && (val[i].cProf = RegExp.$1);
        }
        else {
          val = 'invalid'; break;
        }
      }
      return val;
    }
  };
  var pseudoElmShadow = function(obj, objReg, arr) {
    for(var i = arr.length - 1, l = 0; i >= l; i--) {
      for(var eArr = arr[i].sel.split(','), j = 0, k = eArr.length; j < k; j++) {
        eArr[j].replace(/^\s*/, '').replace(/\s*$/, '') == obj.replace(objReg, '') && arr[i].shadow.match(/none/) && (arr[arr.length] = { sel : obj.replace(objReg, ''), shadow : '0 0 transparent' });
      }
    }
  };
  var setDataAttr = function(obj, dataAttr, objReg) {
    for(var arr = obj.sel.split(','), i = 0, l = arr.length; i < l; i++) {
      arr[i] = arr[i].replace(/^\s+/, '').replace(/\s+$/, '');
      if(arr[i].match(objReg)) {
        for(var qArr = _doc.querySelectorAll(arr[i].replace(pseudoReg, '').replace(dynPseudoReg, '')), j = 0, k = qArr.length; j < k; j++) {
          var dAttr = qArr[j].getAttribute(dataAttr) ? unescape(qArr[j].getAttribute(dataAttr)) : '';
          dAttr != '' && dAttr.match(/(\|;\|default.+)$/) && (dAttr = dAttr.replace(RegExp.$1, ''));
          dAttr = dAttr != '' ? dAttr + '|;|' + arr[i] + '||' + obj.cText : arr[i] + '||' + obj.cText;
          if(dAttr.match(/:(focus|hover|active)/)) {
            var dFunc = 'triggerDynPseudoShadow(event,this);';
            qArr[j].id == '' && (qArr[j].id = 'dynId' + gId, gId++);
            dAttr += ('|;|default||0 0 transparent;');
            dAttr.match(/:focus/) && (setEventAttr(qArr[j], 'onfocus', dFunc), setEventAttr(qArr[j], 'onblur', dFunc));
            dAttr.match(/:hover/) && (setEventAttr(qArr[j], 'onmouseover', dFunc), setEventAttr(qArr[j], 'onmouseout', dFunc));
            dAttr.match(/:active/) && (setEventAttr(qArr[j], 'onmousedown', dFunc), setEventAttr(qArr[j], 'onmouseup', dFunc), setEventAttr(qArr[j], 'onkeydown', dFunc), setEventAttr(qArr[j], 'onkeyup', dFunc));
          }
          qArr[j].setAttribute(dataAttr, escape(dAttr));
        }
      }
    }
  };
  var cascadeSel = function(arr) {
    for(var sArr = [], bool, i = 0, l = arr.length; i < l; i++) {
      bool = true;
      for(var j = i; j < l; j++) {
        i != j && arr[i].sel == arr[j].sel && !arr[i].shadow.match(/important/) && (bool = false);
      }
      bool && (sArr[sArr.length] = arr[i]);
    }
    return sArr;
  };
  var cascadeCText = function(cText) {
    for(var arr = [], cArr = (cText != '' ? cText.replace(/;$/, '').split(';') : []), bool, i = 0, l = cArr.length; i < l; i++) {
      bool = true;
      for(var j = i; j < l; j++) {
        var ci0 = cArr[i].split(':')[0].replace(/^\s+/, '').replace(/\s+$/, ''),
          ci1 = cArr[i].split(':')[1],
          cj0 = cArr[j].split(':')[0].replace(/^\s+/, '').replace(/\s+$/, ''),
          cj1 = cArr[j].split(':')[1];
        i != j && ci0 == cj0 && (ci1.match(/important/) || !(ci1.match(/important/) && cj1.match(/important/))) && (bool = false);
      }
      bool && (arr[arr.length] = cArr[i]);
    }
    return (arr.length > 0 ? (arr.join(';') + ';') : '');
  };
  var filterEnabled = function() {
    try {
      if(_doc.documentElement.filters) {
        var elm, bool, dId = cNum(), dDiv = _doc.createElement('div'), dBody = _doc.getElementsByTagName('body')[0];
        dDiv.id = 'dummyDiv' + dId;  dId++;
        dDiv.style.width = 0;
        dDiv.style.height = 0;
        dDiv.style.visibility = 'hidden';
        dDiv.style.filter = 'progid:DXImageTransform.Microsoft.Blur()';
        dBody.appendChild(dDiv);
        elm = _doc.getElementById(dDiv.id);
        try {
          bool = elm.filters.item('DXImageTransform.Microsoft.Blur').Enabled;
        }
        catch(e) {
          bool = false;
        }
        dBody.removeChild(elm);
        return bool;
      }
      else {
        return false;
      }
    }
    catch(e) {
      return false;
    }
  };
  var sId = cNum();
  if(filterEnabled()) {
    var pArr = [], pseudoReg = /(::?(before|after|first\-(letter|line)))/, dArr = [], dynPseudoReg = /((:(focus|hover|active))+)/, ciArr = [], countIncrementReg = /(counter\-increment\s*:\s*(.+?)\s*;)/i, crArr = [], countResetReg = /(counter\-reset\s*:\s*(.+?)\s*;)/i, arr = cascadeSel(ieShadowSettings()), gId = cNum();
    for(var i = 0, l = pArr.length; i < l; i++) {
      setDataAttr(pArr[i], 'data-pseudo', pseudoReg);
      for(var qArr = pArr[i].sel.split(','), j = 0, k = qArr.length; j < k; j++) {
        qArr[j].match(pseudoReg) && pseudoElmShadow(qArr[j].replace(/^\s*/, '').replace(/\s*$/, ''), pseudoReg, arr);
      }
    }
    for(var i = 0, l = dArr.length; i < l; i++) {
      setDataAttr(dArr[i], 'data-dynpseudo', dynPseudoReg);
      for(var qArr = dArr[i].sel.split(','), j = 0, k = qArr.length; j < k; j++) {
        qArr[j].match(dynPseudoReg) && pseudoElmShadow(qArr[j].replace(/^\s*/, '').replace(/\s*$/, ''), dynPseudoReg, arr);
      }
    }
    if(ieVersion == 8) {
      for(var val, i = 0, l = crArr.length; i < l; i++) {
        val = crArr[i].cText.match(/counter\-reset\s*:\s*(.+?)\s*;/i) && (RegExp.$1);
        for(var qArr = _doc.querySelectorAll(crArr[i].sel.replace(/::?(before|after)/, '')), j = 0, k = qArr.length; j < k; j++) {
          qArr[j].style.counterReset = val;
        }
      }
      for(var val, i = 0, l = ciArr.length; i < l; i++) {
        val = ciArr[i].cText.match(/counter\-increment\s*:\s*(.+?)\s*;/i) && (RegExp.$1);
        for(var qArr = _doc.querySelectorAll(ciArr[i].sel.replace(/::?(before|after)/, '')), j = 0, k = qArr.length; j < k; j++) {
          qArr[j].style.counterIncrement = val;
        }
      }
    }
    arr = cascadeSel(arr);
    for(var i = 0, l = arr.length; i < l; i++) {
      for(var sSel = arr[i].sel.split(/,/), sReg = /^\s*([a-zA-Z0-9#\.:_\-\s>\+~]+)\s*$/, j = 0, k = sSel.length; j < k; j++) {
        sSel[j].match(sReg) && (sSel[j] = RegExp.$1);
        var sObj = { sel : sSel[j], shadow : getShadowValue(arr[i].shadow), hasImp : arr[i].shadow.match(/\s*\!\s*important/) ? true : false };
        getTargetObj(sObj);
      }
    }
  }
}

function triggerDynPseudoShadow(evt, obj) {
  if(isMSIE) {
    for(var evt = (evt || window.event), eType = evt.type, x = evt.clientX, y = evt.clientY, cRect = obj.getBoundingClientRect(), isHover = (cRect.left <= x && cRect.right >= x && cRect.top <= y && cRect.bottom >= y), isActive = (obj == document.activeElement), hasImp = false, dynCSS = [], dynAttr = unescape(obj.getAttribute('data-dynpseudo')).split('|;|'), i = 0, l = dynAttr.length; i < l; i++) {
      if(dynAttr[i].split('||')[0].match(/(((:(focus|hover|active))+)|default)/)) {
        var dynSel = RegExp.$1;
        dynCSS[dynCSS.length] = { dynSel : dynSel.replace(/^:/, '').split(':').sort().join('_'), shadow : dynAttr[i].split('||')[1].replace(/none/, '0 0 transparent') };
        dynSel == 'default' && dynAttr[i].match(/important/) && (hasImp = true);
      }
    }
    var checkPseudoElm = function(dynPseudo) {
      if(obj.getAttribute('data-pseudo')) {
        for(var bool = false, pseudoAttr = unescape(obj.getAttribute('data-pseudo')).split('|;|'), i = 0, l = pseudoAttr.length; i < l; i++) {
          for(var arr = dynPseudo.match(/_/) ? dynPseudo.split('_') : [dynPseudo], j = 0, k = arr.length; j < k; j++) {
            arr[j] == 'default' ? !pseudoAttr[i].match(/default/) && !pseudoAttr[i].match(/:(focus|hover|active)/) && (bool = true) : pseudoAttr[i].match(':' + arr[j]) && (bool = true);
            if(bool) { break; }
          }
          if(bool) { break; }
        }
        return bool;
      }
      else {
        return true;
      }
    };
    var fireTrigger = function(arr) {
      for(var bool = false, i = 0, l = arr.length; i < l; i++) {
        for(var j = 0, k = dynCSS.length; j < k; j++) {
          if(arr[i] == dynCSS[j].dynSel && checkPseudoElm(arr[i])) {
            textShadowForMSIE({sel : '#' + obj.id, shadow : dynCSS[j].shadow.replace(/none/, '0 0 transparent')});
            bool = true;
            break;
          }
        }
        if(bool) { break; }
      }
    };
    if(!hasImp) {
      eType == 'mouseover' && (isActive ? fireTrigger(['focus_hover', 'hover']) : fireTrigger(['hover']));
      eType == 'mouseout' && (isActive ? fireTrigger(['focus', 'default']) : fireTrigger(['default']));
      eType == 'mousedown' && (isActive ? fireTrigger(['active_focus_hover', 'active_hover', 'active_focus', 'active', 'focus_hover', 'hover', 'focus']) : fireTrigger(['active_hover', 'active', 'hover']));
      eType == 'mouseup' && (isHover ? fireTrigger(['focus_hover', 'hover', 'focus']) : fireTrigger(['focus']));
      eType == 'keydown' && (isHover ? fireTrigger(['active_focus_hover', 'active_hover', 'active_focus', 'active', 'focus_hover', 'hover', 'focus']) : fireTrigger(['active_focus', 'active', 'focus']));
      eType == 'keyup' && (isHover ? fireTrigger(['focus_hover', 'hover', 'focus']) : fireTrigger(['focus']));
      eType == 'focus' && (isHover ? fireTrigger(['active_focus_hover', 'active_hover', 'active_focus', 'active', 'focus_hover', 'hover', 'focus']) : fireTrigger(['focus']));
      eType == 'blur' && fireTrigger(['default']);
    }
  }
}

function addEvent(obj, type, listener, capture) {
  var _win = window, _doc = document;
  type == 'DOMContentLoaded' ? obj.addEventListener ? obj.addEventListener(type, listener, (capture ? capture : false)) :
  _win.attachEvent ? function() {
    /*
    *  doScroll polyfill originally devised by Diego Perini.
    *  IEContentLoaded - An alternative for DOMContenloaded on Internet Explorer
    *  http://javascript.nwbox.com/IEContentLoaded/
    *  Author: Diego Perini (diego.perini at gmail.com) NWBOX S.r.l.
    *  License: GPL
    *  Copyright (C) 2007 Diego Perini & NWBOX S.r.l.
    *  http://javascript.nwbox.com/IEContentLoaded/GNU_GPL.txt
    */
    try {
      _doc.documentElement.doScroll('left');
    }
    catch(e) {
      setTimeout(arguments.callee, 1);
      return;
    }
    /*  end doScroll polyfill  */
    listener.call(obj, _win.event);
  }() :
  _win.onload = function(e) { listener.call(_win, e || _win.event) } :
  obj.addEventListener ? obj.addEventListener(type, listener, (capture ? capture : false)) :
  obj.attachEvent ? obj.attachEvent('on' + type, function() { listener.call(obj, _win.event) }) :
  obj['on' + type] = function(e) { listener.call(obj, e || _win.event) };
}

/*
*  quasi querySelectorAll and querySelector for MSIE7
*  IE7にdocument.querySelectorAllとdocument.querySelectorを適用
*  Original source from
*  Adding document.querySelectorAll support to IE7 - Code Couch
*  http://www.codecouch.com/2012/05/adding-document-queryselectorall-support-to-ie-7/
*  Terms of use - Code Couch http://www.codecouch.com/terms/
*/
(function(_doc) {
  _doc.querySelectorAll = _doc.querySelectorAll || (function(_doc) {
    var sSheet = _doc.createStyleSheet();
    return function(sel) {
      var dAll = _doc.all, arr = [], sel = sel.replace(/\[for\b/ig, '[htmlFor').split(',');
      for (var i = sel.length; i--;) {
        sSheet.addRule(sel[i], 'k:v');
        for (var j = dAll.length; j--;) {
          dAll[j].currentStyle.k && (arr[arr.length] = dAll[j]);
        }
        sSheet.removeRule(0);
      }
      return arr;
    };
  })(_doc);
  _doc.querySelector = _doc.querySelector || (function(_doc) {
    return function(sel) {
      return _doc.querySelectorAll(sel).length > 0 ? _doc.querySelectorAll(sel)[0] : null;
    }
  })(_doc);
})(document);

addEvent(document, 'DOMContentLoaded', function() {
//addEvent(window, 'load', function() {
  (document.documentMode || ieVersion >= 7) && ieVersion <= 9 && textShadowForMSIE();
});

/*  Sample to change shadow(s) at interactive events (eg: onclick)  
addEvent(window, 'load', function() {
  var eObj = { sel : '#someId', shadow : 'green 2px 2px 2px !important' };
  var elm = document.getElementById(eObj.sel.replace('#', ''));
  elm && addEvent(elm, 'click', function() {
    if(ieVersion >= 7 && ieVersion <= 9) {
      textShadowForMSIE(eObj);
    }
    else if(ieVersion > 9 || !isMSIE) {
      eObj.shadow.match(/(\s*\!\s*important)/) ?
      elm.style.setProperty('text-shadow', eObj.shadow.replace(RegExp.$1, ''), 'important') :
      elm.style.setProperty('text-shadow', eObj.shadow, '');
    }
  });
});
*/

