<div id="content" class="page">
	<div class="typography">
		<% if Level(2) %>
		<div class="breadcrumbs">
			$Breadcrumbs
		</div>
		<% end_if %>
		
		<h2>$Title</h2>
	
		<div id="product_left">
			$Content
		</div>
		
		<div id="product_right">
			<div id="product_image">
				<a class="popup" href="$Image.Large.URL">
					$Image.Thumbnail
					<span>Enlarge</span>
				</a>
			</div>
			
			<div id="product_info">
				<p>Item #{$ID}</p>
				
				<% include VariationsTable %>
			</div>
		</div>
		
		$Form
		$PageComments
	</div>
</div>