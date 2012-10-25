<!--
		/*
		// +----------------------------------------------------------------------+
		// | Copyright (c) 2004 Bitflux GmbH                                      |
		// +----------------------------------------------------------------------+
		// | Licensed under the Apache License, Version 2.0 (the "License");      |
		// | you may not use this file except in compliance with the License.     |
		// | You may obtain a copy of the License at                              |
		// | http://www.apache.org/licenses/LICENSE-2.0                           |
		// | Unless required by applicable law or agreed to in writing, software  |
		// | distributed under the License is distributed on an "AS IS" BASIS,    |
		// | WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or      |
		// | implied. See the License for the specific language governing         |
		// | permissions and limitations under the License.                       |
		// +----------------------------------------------------------------------+
		// | Author: Bitflux GmbH <devel@bitflux.ch>                              |
		// +----------------------------------------------------------------------+
		
		// Edited By: Lauren Bradford, 02/04/05
		// Edited By: Brian Markham, 01/01/06
		*/
        
        var mode;
		var liveSearchReq = false;
		var t = null;
		var liveSearchLast = "";
		var isIE = false;

		// on !IE we only have to initialize it once
		if (window.XMLHttpRequest) {
			liveSearchReq = new XMLHttpRequest();
		}

		function liveSearchInit() {
			
			if (navigator.userAgent.indexOf("Safari") > 0) {
				// do nothing
			} else if (navigator.product == "Gecko") {
				
				// do nothing
			} else {
				isIE = true;
			}
			document.getElementById('name').setAttribute("autocomplete","off");
		}

		function liveSearchHideDelayed(mode) {
			window.setTimeout("liveSearchHide(mode)",400);
		}
			
		function liveSearchHide(mode) {
			document.getElementById("LSResult").style.display = "none";
			var highlight = document.getElementById("LSHighlight");
			if (highlight) {
				highlight.removeAttribute("id");
			}
			
			if(document.forms[1].mode.value == "search_papers"){
                mode = 'author';
            } else {
                mode = 'editor';
            }
            
			/*-- turn form elements back on for IE --*/
			if(is_ie && mode == "author") {
                document.search.published_from_Month.style.visibility  = 'visible';
                document.search.published_from_Day.style.visibility  = 'visible';
                
                document.search.published_to_Month.style.visibility  = 'visible';
                document.search.published_to_Day.style.visibility  = 'visible';
                
                document.search.review_time.style.visibility  = 'visible';
                
                document.search.filter.style.visibility  = 'visible';
            }
		}

		function liveSearchStart(mode ) {

			if (t) {
				window.clearTimeout(t);
			}
			t = window.setTimeout("liveSearchDoSearch()",200);
		}

		function liveSearchDoSearch() {
        
            if(document.forms[1].mode.value == "search_papers"){
                mode = 'author';
            } else {
                mode = 'editor';
            }

		    /*-- turn form elements off on for IE --*/
            if(is_ie && mode == "author") {
                document.search.published_from_Month.style.visibility  = 'hidden';
                document.search.published_from_Day.style.visibility  = 'hidden';
                
                document.search.published_to_Month.style.visibility  = 'hidden';
                document.search.published_to_Day.style.visibility  = 'hidden';
                
                document.search.review_time.style.visibility  = 'hidden';
                
                document.search.filter.style.visibility  = 'hidden';
            }
            
			if (typeof liveSearchRoot == "undefined") {
				liveSearchRoot = "/";
			}
			if (typeof liveSearchRootSubDir == "undefined") {
				liveSearchRootSubDir = "";
			}
			if (typeof liveSearchParams == "undefined") {
				liveSearchParams = "";
			}
			if (liveSearchLast != document.getElementById('name').getAttribute('value')) {
				if (liveSearchReq && liveSearchReq.readyState < 4) {
					liveSearchReq.abort();
				}
				if ( document.getElementById('name').value == "") {
					liveSearchHide();
					return false;
				}
				if (window.XMLHttpRequest) {
				// branch for IE/Windows ActiveX version
				} else if (window.ActiveXObject) {
					liveSearchReq = new ActiveXObject("Microsoft.XMLHTTP");
				}

				liveSearchReq.onreadystatechange= liveSearchProcessReqChange;
				liveSearchReq.open("GET", liveSearchRoot + "livesearch.php?q=" + document.getElementById('name').value.replace(' ','%20') + "&mode=" + mode);
				
				o = document.getElementById('name').value;
				liveSearchReq.send(null);
			}
		}

		function liveSearchProcessReqChange() {
			
			if (liveSearchReq.readyState == 4) {
				var  res = document.getElementById("LSResult");
				res.style.display = "block";
				var  sh = document.getElementById("LSShadow");
				
				sh.innerHTML = liveSearchReq.responseText;
				if (sh.innerHTML == "no results") {
					fillName(document.getElementById('name').value,'');
				}
				 
			}
		}
		
		function fillName(name,people_id) {
			document.getElementById('name').value = name;
			document.getElementById('people_id').value = people_id;
			liveSearchHide();
		}
// -->