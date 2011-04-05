<% if Variations %>
<table>
	<thead>
		<tr>
		<% control Variations.First %>
			<% control FrontEndFields %>
			<td>$Name</td>
			<% end_control %>
		<% end_control %>
		</tr>
	</thead>
	<tbody>
	<% control Variations %>
		<tr>
		<% control FrontEndFields %>
			<td>$Value</td>
		<% end_control %>
			<td>
				<% if canPurchase(1) %>
					<% if isInCart %>
						<a href="cart/remove/{$ID}"><% _t('REMOVE','Remove from your cart') %></a>
					<% else %>
						<a href="cart/add/{$ID}/{$class}"><% _t('ADD','Add to your cart') %></a>
					<% end_if %>
				<% else %>
					&nbsp;
				<% end_if %>
			</td>
		</tr>
	<% end_control %>
	</tbody>
</table>
<% end_if %>
