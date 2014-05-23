// ======================================================================================
// 	Nterchange Javascript Class 
// 
//		- Base Class for Default Nterchange Admin Functionality
//		- Add Methods to initialize function for global execution
// ======================================================================================


var Nterchange = Class.create();

Nterchange.prototype = {
	initialize: function(){
		this.initDateChooser();
		this.hiliteUserNotices();
	},
	
	initDateChooser: function(){
		
		// Grab all Selects with a name attribute
		var selects = $$('select[name]');
		if(selects && selects.length > 0){
			// Get All Date Fields
			var year_fields = selects.findAll(function(elem){return elem.name.include('[Y]')});
			var month_fields = selects.findAll(function(elem){return elem.name.include('[m]')});
			var day_fields = selects.findAll(function(elem){return elem.name.include('[d]')});
			
			// Init Array of Datechoosers
			DateChoosers = [];
			
			// Make sure we have the selects we are expecting.
			if(year_fields.length > 0 && month_fields.length > 0 && day_fields.length > 0){
			
				// Run through each set of fields
				year_fields.each(function(elem, i){
					if(elem){
						var year = null, month = null, day = null;	
						// Grab the field names
						var year = year_fields[i].name, month = month_fields[i].name, day = day_fields[i].name;
						// Assign ids to field via name
						year_fields[i].id = year, month_fields[i].id = month, day_fields[i].id = day;
						// Get Parent Table Cell
						cell = year_fields[i].parentNode;
						// Insert Link for triggering date chooser
						new Insertion.Top(cell, "<a href='#' id='date_chooser_link_"+i+"' class='date_chooser'><span>Date</span></a>");
					
						// Attach new instance of DateChooser
						DateChoosers[i] = new DateChooser();
						// Assign fields to update
						DateChoosers[i].setUpdateField([month, day, year], ['n', 'j', 'Y']);
						// Assign OnClick function to trigger date chooser
						$('date_chooser_link_'+i+'').onclick = function(e){DateChoosers[i].display(e); return false;}
					}
				});
				
			}
		}
	},
	
	
	hiliteUserNotices: function(){

  		var notices = $$('.notice');
	  		if(notices && notices.length > 0){
	  			notices.each(function(notice){
	  				new Effect.Highlight(notice);
	  			});
	  		}
  		
	  		var actiontracker = $('actiontrack');
	  		if(actiontracker){
	  			new Effect.Highlight(actiontracker);
	  		}
  		
		}
	}






Event.observe(window, "load", function(){Nterchange.prototype.initialize();});

function externalLinks() { 
	if (!document.getElementsByTagName) return;
	var anchors = document.getElementsByTagName("a");
	for (var i=0; i<anchors.length; i++) {
		var anchor = anchors[i];
		if (anchor.getAttribute("href") &&
		anchor.getAttribute("rel") == "blank")
		anchor.target = "_blank";
	}
} 
window.onload = externalLinks;

// extend the Element object with a handy activator
Element.activate = function(element, activate) {
	element = $(element)

	var inactive_text = element['bc:inactive_text']
	if(!inactive_text) {
		element['bc:inactive_text'] = element.innerHTML
		inactive_text = element.innerHTML
	}

	var active_text = element.getAttribute('bc:active_text') || element.innerHTML
	var active_class = element.getAttribute('bc:active_class') || element.className

	if(!activate) {
		element.innerHTML = inactive_text
		if(active_class) Element.removeClassName(element, active_class)
		element['bc:active'] = false
	} else {
		element.innerHTML = active_text
		if(active_class) Element.addClassName(element, active_class)
		element['bc:active'] = true
	}
}



