var pagecontent = {
	item_reorder_url: '/nterchange/page_content/reorder',

	reorder: {
		toggle: function(id) {
			// activate the link (changes the text and class of the link)
			var link = $('reorder_list_' + id + '_link')
			Element.activate(link, !link['bc:active']);

			if(link['bc:active']) {
				pagecontent.makeDraggable(id)
			}

			pagecontent.childrenOf('page_content_list_' + id, 'li', function(item) {
				var id = pagecontent.idOf(item)
				if($('drag_' + id)) Element.toggle('drag_' + id);
				if($('edit_links_for_' + id)) Element.toggle('edit_links_for_' + id);
				if($('pointer_for_' + id)) Element.toggle('pointer_for_' + id);
				})

			// toggle the "Add Content" link for the list
			pagecontent.toggleAddContentLink(id)
		}
	},

	toggleAddContentLink: function(id) {
		if($('add_content_links_for_' + id)) Element.toggle('add_content_links_for_' + id);
	},

	makeDraggable: function(id) {
		Sortable.create($('page_content_list_'+id),
		{handle: 'dragger_'+id})
		this.installDragObserver(id)
	},

	installDragObserver: function(id) {
		if(!this.observer_installed) {
			this.observer_installed = true
			Draggables.addObserver({
				onStart: function(name, draggable, event) {},
				onEnd: function(name, draggable, event) {
					var sib = draggable.element.nextSibling
					var parms = 'id=' + pagecontent.idOf(draggable.element) +
						'&before=' + pagecontent.idOf(sib)
						new Ajax.Request(
						pagecontent.item_reorder_url,
						{ parameters: parms, asynchronous: true, onComplete: function(){pagecontent.rehighlightRows(id)} }) 
				}
			})
		}
	},

	rehighlightRows: function(id) {
		pagecontent.childrenOf('page_content_list_' + id, 'li', function(item, i) {
				item.className = (i%2 == 1)?'odditem':'evenitem';
				})
	},

	childrenOf: function(element, tag, callback) {
		var nodes = $(element).childNodes
		var result

		if(!callback) result = new Array()
		tag = tag.toUpperCase()
		for(var i = 0; i < nodes.length; i++) {
			if(nodes[i].tagName && nodes[i].tagName == tag) {
				if(callback) callback(nodes[i], i)
				else result.push(nodes[i])
			}
		}

		if(!callback) return result
	},

	idOf: function(element) {
		return element ? element.id.split("_")[1] : ""
	},

	zzz_placeholder: false // so we don't have to remember to add a comma
}