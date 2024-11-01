/**
 * WP Archive Plugin display JS.
 *
 * @package WP Archive Plugin
 */

(function( $, DOMPurify, _, nestedDateSorter ) {

	var monthNames = ["January", "February", "March", "April", "May", "June",
		"July", "August", "September", "October", "November", "December"
	];

	function nth(d) {
		if (d > 3 && d < 21) {
			return 'th';
		}
		switch (d % 10) {
			case 1:  return "st";
			case 2:  return "nd";
			case 3:  return "rd";
			default: return "th";
		}
	}

	$( document ).ready(function() {

		var wp_archive_plugin_wrapper = $( '#wp_archive_plugin_wrapper' );

		if ( typeof wp_archive_plugin_display_settings.data === 'undefined' ) {
			console.log("wp_archive_plugin_display_settings was not defined");
			wp_archive_plugin_wrapper
				.append( DOMPurify.sanitize( '<p>Sorry, unable to find your blog posts.</p>' ) );

			return;
		}

		var parsed_data  = JSON.parse( wp_archive_plugin_display_settings.data );
		var archive_data = nestedDateSorter(parsed_data);

		if ( 0 === archive_data.length ) {
			wp_archive_plugin_wrapper
				.append( DOMPurify.sanitize( '<p>No blog posts found!</p>' ) );

			return;
		}

		archive_data
		.map(function (year_data) {
			var month_desc = _.sortBy(year_data.months, 'number').reverse();

			return {
				year: year_data.year,
				months: month_desc
			};
		});

		$.map( archive_data, function( year_data ) {
			if ( typeof  year_data === 'undefined' ) {
				return;
			}

			wp_archive_plugin_wrapper
			.append(DOMPurify.sanitize('<h1>' + year_data.year + '</h1>'));

			$.map( year_data.months, function( month_data ) {

				if ( typeof  month_data === 'undefined' ) {
					return;
				}

				var month_name = monthNames[month_data.number];

				wp_archive_plugin_wrapper
				.append(DOMPurify.sanitize('<h2>' + month_name + '</h2>'));

				var list_class = year_data.year + '-' + month_data.number;
				var list       = wp_archive_plugin_wrapper
				.append('<ul class="' + list_class + '"></ul>')
				.find('ul.' + list_class);

				$.map( month_data.posts, function( post_data ) {

					if ( typeof  post_data === 'undefined' ) {
						return;
					}

					var formattedDate        = post_data.datetime.getDate() + nth( post_data.datetime.getDate() );
					var wrappedFormattedDate = DOMPurify.sanitize( '<span class="wp_archive_plugin_wrapper-date-nth">' + formattedDate + '</span>' );

					var a = DOMPurify.sanitize( wrappedFormattedDate + ' - <a href="' + post_data.permalink + '" title="' + post_data.title + '">' + post_data.title + '</a>');

					list.append(DOMPurify.sanitize('<li>' + a + '</li>'));

				});

			});

		})

	});

}( jQuery, DOMPurify, _, nestedDateSorter ));
