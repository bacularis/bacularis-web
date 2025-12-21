<com:BClientScript ScriptUrl=<%~ ../../../../../vendor/npm-asset/opentip/downloads/opentip-jquery.min.js %> />
<com:BStyleSheet StyleSheetUrl=<%~ ../../../../../vendor/npm-asset/opentip/css/opentip.css %> />
<div id="welcome_modal" class="w3-modal" style="display: none">
	<div class="w3-modal-content w3-card-4 w3-animate-zoom" style="width: 830px">
		<header class="w3-container">
		</header>
		<div class="w3-margin-left w3-margin-right w3-padding">
			<h2><%[ Welcome to Bacularis ]%></h2>
			<span><%[ Bacularis is a free and open-source web interface for managing Bacula environments. ]%></span>
			<br />
			<span><%[ It helps you monitor jobs, inspect configuration, and perform restores using a safe and predictable workflow. ]%></span>
			<h4><%[ Get started in a few simple steps ]%></h4>
			<h5 style="margin-bottom: 0;">1️⃣  <%[ Check system status ]%></h5>
			<span><%[ Verify the connection to Bacula Director, Storage, and Client. ]%></span>
			<h5 style="margin-bottom: 0;">2️⃣ <%[ Review recent jobs ]%></h5>
			<span><%[ See recent backup jobs, their status, and detailed logs. ]%></span>
			<h5 style="margin-bottom: 0;">3️⃣ <%[ Try a restore (safe) ]%></h5>
			<span><%[ Explore the restore workflow. Nothing will be restored until you explicitly confirm the operation. ]%></span>
			<h4><%[ Helpful links ]%></h4>
			<ul>
				<li>
					<h5 style="margin-bottom: 0;"><%[ Bacula basics ]%></h5>
					<span><%[ Read how to perform basic Bacula tasks in Bacularis ]%></span>
					<br />
					<span>→ <a href="https://bacularis.app/doc/bacula-basics/start.html" target="_blank">Bacula basics</a></span>
				</li>
				<li>
					<h5 style="margin-bottom: 0;"><%[ Restore documentation ]%></h5>
					<span><%[ How restores work in Bacularis ]%></span>
					<br />
					<span>→ <a href="https://bacularis.app/doc/bacula-basics/run-restore.html" target="_blank">Restore docs</a></span>
				</li>
				<li>
					<h5 style="margin-bottom: 0;"><%[ Community & support ]%></h5>
					<span><%[ Ask questions and share feedback ]%></span>
					<br />
					<span>→ <a href="https://group.bacularis.app" target="_blank">Bacularis User Group</a></span>
				</li>
			</ul>
			<h4><%[ Safe by default ]%></h4>
			<span><%[ Bacularis uses read-only access during initial setup and does not modify your Bacula configuration unless you explicitly enable write access. ]%></span>
		</div>
		<footer class="w3-border-top" style="display: flex">
			<div style="width: 25%;">&nbsp;</div>
			<div class="w3-center" style="width: 50%;">
				<button type="button" class="w3-button w3-section w3-green" onclick="oWelcomeModal.show(false); oGuidedTour.start_tour();"><i class="fa-solid fa-map-location"></i> &nbsp;<%[ Show guided tour ]%></button>
				<button type="button" class="w3-button w3-section w3-green" onclick="oWelcomeModal.show(false);"><i class="fa-solid fa-play"></i> &nbsp;<%[ Start using Bacularis ]%></button>
			</div>
			<div style="width: 25%; align-self: center;">
				<input type="checkbox" id="welcome_modal_dont_show_again" value="1" /> <label for="welcome_modal_dont_show_again" class="pointer"><%[ Don't show this again ]%></label>
			</div>
		</footer>
	</div>
</div>
<div id="guided_tour_overlay" class="boverlay"></div>
<com:TCallback ID="EndWelcome" OnCallback="endWelcome" />
<script>
const oWelcomeModal = {
	ids: {
		win: 'welcome_modal',
		dont_show_again: 'welcome_modal_dont_show_again'
	},
	referrer_pages: [
		'<%=$this->Service->constructUrl('LoginPage')%>',
		'<%=$this->Service->constructUrl('SelectAPIHost')%>'
	],
	is_page_supported: function() {
		const url = document.referrer;
		let pattern;
		let found = false;
		for (let i = 0; i < this.referrer_pages.length; i++) {
			pattern = new RegExp(this.referrer_pages[i] + '$');
			if (pattern.test(url)) {
				found = true;
				break;
			}
		}
		return found;
	},
	show: function(show) {
		if (!this.is_page_supported()) {
			// page not supported to display welcome screen
			return;
		}
		const win = document.getElementById(this.ids.win);
		win.style.display = show ? 'block' : 'none';
		if (!show) {
			this.close_welcome();
		}
	},
	close_welcome: function() {
		const dsa = document.getElementById(this.ids.dont_show_again).checked;
		if (dsa) {
			const cb = <%=$this->EndWelcome->ActiveControl->Javascript%>;
			cb.setCallbackParameter({dsa: dsa});
			cb.dispatch();
		}
	}
};
Opentip.styles.guide_tour = {
	"extends": "standard",
	background: '#fefefe',
	borderColor: '#4f4b47',
	stemBase: 36,
	stemLength: 58,
	autoOffset: true
};
const oGuidedTour = {
	ids: {
		overlay: 'guided_tour_overlay'
	},
	current_step: 0,
	current_tip: null,
	tip_opts: {
		className: 'guide_tour',
		style: 'guide_tour'
	},
	steps: [
		{
			id: 'dashboard_btn',
			title: '<%[ Dashboard ]%>',
			desc: '<%[ This is the main overview of your Bacula environment. ]%><br /><%[ Check system status, recent jobs, and potential issues at a glance. ]%>'
		},
		{
			id: 'jobs_btn',
			title: '<%[ Jobs ]%>',
			desc: '<%[ Here you can browse all backup jobs, review their status, and inspect detailed logs. ]%>'
		},
		{
			id: 'msg_envelope',
			title: '<%[ Messages ]%>',
			desc: '<%[ View Bacula messages, warnings, and errors. ]%> <%[ This is usually the first place to check when something goes wrong. ]%>'
		},
		{
			id: 'restore_btn',
			title: '<%[ Restore ]%>',
			desc: '<%[ Explore the restore workflow and browse available backups. ]%> <%[ No data will be restored until you explicitly confirm the operation. ]%>'
		},
		{
			id: 'security_btn',
			title: '<%[ Settings ]%>',
			desc: '<%[ Manage users, access permissions, and advanced security options. ]%>'
		}
	],
	start_tour: function() {
		this.show_overlay(true);
		this.step(1);
	},
	end_tour: function() {
		this.show_overlay(false);
		this.current_step = 1;
	},
	step: function(step_number) {
		const is_next = (step_number > this.current_step);
		this.current_step = step_number;
		this.hide_tip();
		const step = this.steps.indexOf(this.current_step - 1) ? this.steps[this.current_step - 1] : null;
		if (step) {
			const is_visible = $('#' + step.id).is(':visible');
			if (is_visible) {
				this.show_tip(step.id, step.title, step.desc);
			} else {
				if (is_next) {
					this.step(step_number + 1);
				} else {
					this.step(step_number - 1);
				}
			}
		} else if (this.current_step == 0 || this.current_step > this.steps.length) {
			this.end_tour();
		}
	},
	show_tip: function(id, title, desc) {
		const desc_wb = desc + this.get_btns(this.current_step).outerHTML;
		this.current_tip = showTip('#' + id, title, desc_wb, this.tip_opts);
	},
	hide_tip: function() {
		if (this.current_tip) {
			this.current_tip.hide();
		}
	},
	show_overlay: function(show) {
		const overlay = document.getElementById(this.ids.overlay);
		overlay.style.display = show ? 'block' : 'none';
	},
	get_btns: function (step_number) {
		const container = document.createElement('DIV');
		container.classList.add('w3-right', 'w3-padding');
		if (step_number < this.steps.length) {
			const end_btn = this.get_btn(0, '<%[ End ]%>');
			container.appendChild(end_btn);
		}
		if (step_number > 1) {
			const prev_btn = this.get_btn(step_number - 1, '<%[ Prev ]%>');
			container.appendChild(prev_btn);
		}
		if (step_number < this.steps.length) {
			const next_btn = this.get_btn(step_number + 1, '<%[ Next ]%>');
			container.appendChild(next_btn);
		}
		if (step_number >= this.steps.length) {
			const finish_btn = this.get_btn(step_number + 1, '<%[ Finish ]%>');
			container.appendChild(finish_btn);
		}
		return container;
	},
	get_btn: function(step_number, label) {
		const btn = document.createElement('BUTTON');
		btn.type = 'button';
		btn.classList.add('w3-button', 'w3-green');
		btn.style.padding = '4px 14px';
		btn.style.margin = '0 4px';
		btn.setAttribute('onclick', 'oGuidedTour.step(' + (step_number) + ');');
		const text = document.createTextNode(label);
		btn.appendChild(text);
		return btn;
	}
};
oWelcomeModal.show(true);
</script>
