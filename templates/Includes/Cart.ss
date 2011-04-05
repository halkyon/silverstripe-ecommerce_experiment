<% control Cart %>
	<h4>My cart</h4>
	<% if Items %>
		<ul id="cart_items">
		<% control Items %>
			<li>
				<span><a href="$InternalProduct.Link">$InternalProduct.Title</a> ($Quantity @ $UnitPrice ea.)</span>
				<a href="cart/add/$InternalProduct.ID">Add</a>
				<a href="cart/deduct/$InternalProduct.ID">Deduct</a>
				<a href="cart/remove/$InternalProduct.ID">Remove</a>
			</li>
		<% end_control %>
		</ul>
		<p>Total: <strong>$Subtotal</strong></p>
		<p><a href="order">Order these items</a></p>
	<% else %>
		<p><% _t('NOITEMS','No items in your cart') %>.</p>
	<% end_if %>
<% end_control %>