var workflowManager = {
	toggleAddUser: function(workflow_id) {
		Element.toggle('add_person_'+workflow_id+'_link');
		Element.toggle('add_person_form_container_'+workflow_id);
		if ($('add_person_form_container_'+workflow_id).style.display == 'none') {
			form = $('add_person_form_'+workflow_id);
			form.user_id.selectedIndex = 0;
			form.role.selectedIndex = 0;
		}
	},

	addUserLoading: function(request, workflow_id) {
		Element.toggle('add_user_indicator_'+workflow_id);
	},
	onAddUser: function(request, workflow_id) {
		Element.toggle('add_user_indicator_'+workflow_id);
		users = $('workflowusers'+workflow_id);
		eval(request.responseText);
		if (typeof(result) == 'object') {
			user_id = result.id;
			html = result.data;
			users.innerHTML = html + users.innerHTML;
			new Effect.Highlight('workflowuser'+user_id);
		}
		// toggle the form and link
		workflowManager.toggleAddUser(workflow_id);
	},

	zzz_placeholder: false // so we don't have to remember to add a comma
}