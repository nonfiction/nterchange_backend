<?php
class DashboardHelper{
	function function_version_check() {
		$vcheck = NController::factory('version_check');
		$vcheck->dashboardVersionCheck();
	}
	
	function function_dashboard_client_content() {
		$dashboard = NController::factory('dashboard');
		$dashboard->dashboardClientContent();
	}
}
?>