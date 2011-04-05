<div id="content" class="page">
	<div class="typography">
		<% if Level(2) %>
		<div class="breadcrumbs">
			$Breadcrumbs
		</div>
		<% end_if %>
		
		<h2>$Title</h2>
		
		<% if CurrentMember %>
		<p>Thanks, <strong>$CurrentMember.FirstName</strong>! &nbsp;Your current contact details have been pre-filled in the form below.<br>
		This may be a good opportunity to review and update any inaccurate contact details.</p>
		
		<% else %>
		<div id="existing_member">
			<h3>Already a member?</h3>
			<p>Enter your log in details below. This will make the order process quicker by pre-filling your contact details.</p>
			$LoginForm
		</div>
		
		<div id="new_member">
			<h3>Not a member?</h3>
			<p>We'll email you with log in details once you've submitted this form.</p>
		</div>
		<% end_if %>
		
		$Form
		$PageComments
	</div>
</div>