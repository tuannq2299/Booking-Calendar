<!-- main container of all the page elements -->
<div id="wrapper services">
	<!-- header of the page --> 
	<?php include( CBSB_BASE_DIR . 'admin/connected/header.php' ); ?>

	<main id="main">
		<!-- twocolumns -->
		<div style="width:300px;margin:50px auto;">
			<svg style="display: \'block\'; margin: \'auto\'"
				version="1.1"
				xmlns="http://www.w3.org/2000/svg"
				xmlnsXlink="http://www.w3.org/1999/xlink"
				enableBackground="new 0 0 0 0"
				xmlSpace="preserve"
			>
				<circle fill="#888" stroke="none" cx="100" cy="50" r="6">
					<animate
						attributeName="opacity"
						dur="2s"
						values="0;1;0"
						repeatCount="indefinite"
						begin="0"
					/>
				</circle>
				<circle fill="#888" stroke="none" cx="150" cy="50" r="6">
					<animate
						attributeName="opacity"
						dur="2s"
						values="0;1;0"
						repeatCount="indefinite"
						begin="0.5"
					/>
				</circle>
				<circle fill="#888" stroke="none" cx="200" cy="50" r="6">
					<animate
						attributeName="opacity"
						dur="2s"
						values="0;1;0"
						repeatCount="indefinite"
						begin="1"
					/>
				</circle>
			</svg>
		</div>
	</main>

</div>