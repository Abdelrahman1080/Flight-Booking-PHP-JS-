(function($) {
	const defaults = { name: 'SkyWings', role: 'Administrator' };

	function initialsFromName(name) {
		return (name.match(/\b\w/g) || []).join('').substring(0, 2).toUpperCase() || '--';
	}

	function setUserProfile(user) {
		const name = user?.name || defaults.name;
		const role = user?.role || defaults.role;
		const initials = initialsFromName(name);

		$('#companyName').text(name);
		$('#companyRole').text(role);
		$('#company-avatar-initials').text(initials);

		$('#user-name').text(name);
		$('#user-membership').text(role);
		$('#user-avatar-initials').text(initials);

		return { name, role, initials };
	}

	function setActiveSidebar(pageKey) {
		if (!pageKey) return;
		$('.sidebar-item').removeClass('active');
		$(`.sidebar-item[data-page="${pageKey}"]`).addClass('active');
	}

	function initNavigation(pageKey, user) {
		setActiveSidebar(pageKey);
		setUserProfile(user);
	}

	function bindSidebarClicks() {
		$('.sidebar-item').on('click', function() {
			$('.sidebar-item').removeClass('active');
			$(this).addClass('active');
		});
	}

	window.SkyWingsUI = {
		initNavigation,
		setUserProfile,
		bindSidebarClicks
	};
})(jQuery);
