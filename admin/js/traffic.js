jQuery(document).ready( function($) {
	$( ".traffic-about-logo" ).css({opacity:1});
	$( ".traffic-select" ).each(
		function() {
			var chevron  = 'data:image/svg+xml;base64,PHN2ZwogIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIKICB3aWR0aD0iMjQiCiAgaGVpZ2h0PSIyNCIKICB2aWV3Qm94PSIwIDAgMjQgMjQiCiAgZmlsbD0ibm9uZSIKICBzdHJva2U9IiM3Mzg3OUMiCiAgc3Ryb2tlLXdpZHRoPSIyIgogIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIKICBzdHJva2UtbGluZWpvaW49InJvdW5kIgo+CiAgPHBvbHlsaW5lIHBvaW50cz0iNiA5IDEyIDE1IDE4IDkiIC8+Cjwvc3ZnPgo=';
			var classes  = $( this ).attr( "class" ),
			id           = $( this ).attr( "id" ),
			name         = $( this ).attr( "name" );
			var template = '<div class="' + classes + '">';
			template    += '<span class="traffic-select-trigger">' + $( this ).attr( "placeholder" ) + '&nbsp;<img style="width:18px;vertical-align:top;" src="' + chevron + '" /></span>';
			template    += '<div class="traffic-options">';
			$( this ).find( "option" ).each(
				function() {
					template += '<span class="traffic-option ' + $( this ).attr( "class" ) + '" data-value="' + $( this ).attr( "value" ) + '">' + $( this ).html().replace("~-", "<br/><span class=\"traffic-option-subtext\">").replace("-~", "</span>") + '</span>';
				}
			);
			template += '</div></div>';

			$( this ).wrap( '<div class="traffic-select-wrapper"></div>' );
			$( this ).after( template );
		}
	);
	$( ".traffic-option:first-of-type" ).hover(
		function() {
			$( this ).parents( ".traffic-options" ).addClass( "option-hover" );
		},
		function() {
			$( this ).parents( ".traffic-options" ).removeClass( "option-hover" );
		}
	);
	$( ".traffic-select-trigger" ).on(
		"click",
		function() {
			$( 'html' ).one(
				'click',
				function() {
					$( ".traffic-select" ).removeClass( "opened" );
				}
			);
			$( this ).parents( ".traffic-select" ).toggleClass( "opened" );
			event.stopPropagation();
		}
	);
	$( ".traffic-option" ).on(
		"click",
		function() {
			$(location).attr("href", $( this ).data( "value" ));
		}
	);
	$( "#traffic-chart-button-calls" ).on(
		"click",
		function() {
			$( "#traffic-chart-calls" ).addClass( "active" );
			$( "#traffic-chart-data" ).removeClass( "active" );
			$( "#traffic-chart-uptime" ).removeClass( "active" );
			$( "#traffic-chart-button-calls" ).addClass( "active" );
			$( "#traffic-chart-button-data" ).removeClass( "active" );
			$( "#traffic-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#traffic-chart-button-data" ).on(
		"click",
		function() {
			$( "#traffic-chart-calls" ).removeClass( "active" );
			$( "#traffic-chart-data" ).addClass( "active" );
			$( "#traffic-chart-uptime" ).removeClass( "active" );
			$( "#traffic-chart-button-calls" ).removeClass( "active" );
			$( "#traffic-chart-button-data" ).addClass( "active" );
			$( "#traffic-chart-button-uptime" ).removeClass( "active" );
		}
	);
	$( "#traffic-chart-button-uptime" ).on(
		"click",
		function() {
			$( "#traffic-chart-calls" ).removeClass( "active" );
			$( "#traffic-chart-data" ).removeClass( "active" );
			$( "#traffic-chart-uptime" ).addClass( "active" );
			$( "#traffic-chart-button-calls" ).removeClass( "active" );
			$( "#traffic-chart-button-data" ).removeClass( "active" );
			$( "#traffic-chart-button-uptime" ).addClass( "active" );
		}
	);
} );
