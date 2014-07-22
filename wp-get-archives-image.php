<?php

/**

* Retrieve archive link content based on predefined or custom code.

*

* The format can be one of four styles. The 'link' for head element, 'option'

* for use in the select element, 'html' for use in list (either ol or ul HTML

* elements). Custom content is also supported using the before and after

* parameters.

*

* The 'link' format uses the link HTML element with the <em>archives</em>

* relationship. The before and after parameters are not used. The text

* parameter is used to describe the link.

*

* The 'option' format uses the option HTML element for use in select element.

* The value is the url parameter and the before and after parameters are used

* between the text description.

*

* The 'html' format, which is the default, uses the li HTML element for use in

* the list HTML elements. The before parameter is before the link and the after

* parameter is after the closing link.

*

* The custom format uses the before parameter before the link ('a' HTML

* element) and the after parameter after the closing link tag. If the above

* three values for the format are not used, then custom format is assumed.

*

* @since 1.0.0

*

* @param string $url URL to archive.

* @param string $img Archive formatted img tag.

* @param string $format Optional, default is 'html'. Can be  'html', or custom.

* @param string $before Optional.

* @param string $after Optional.

* @return string HTML link content for archive.

*/

function get_archives_link_image($url, $img, $format = 'html', $before = '', $after = '') {

	//$img = esc_url($img);

	$url = esc_url($url);


	if ('html' == $format)

		$link_html = "\t<li>$before<a href='$url'>$img</a>$after</li>\n";

	else // custom

		$link_html = "\t$before<a href='$url'>$img</a>$after\n";


	/**

	 * Filter the archive link content.

	 *

	 * @since 2.6.0

	 *

	 * @param string $link_html The archive HTML link content.

	 */

	$link_html = apply_filters( 'get_archives_link', $link_html );


	return $link_html;

}


/**

* Display archive links based on type and format.

*

* @since 1.2.0

*

* @see get_archives_link()

*

* @param string|array $args {

* Default archive links arguments. Optional.

*

* @type string $type Type of archive to retrieve. Accepts 'daily', 'weekly', 'monthly',

* 'yearly', 'postbypost', or 'alpha'. Both 'postbypost' and 'alpha'

* display the same archive link list as well as post titles instead

* of displaying dates. The difference between the two is that 'alpha'

* will order by post title and 'postbypost' will order by post date.

* Default 'monthly'.

* @type string|int $limit Number of links to limit the query to. Default empty (no limit).

* @type string $format Format each link should take using the $before and $after args.

* Accepts 'link' (`<link>` tag), 'option' (`<option>` tag), 'html'

* (`<li>` tag), or a custom format, which generates a link anchor

* with $before preceding and $after succeeding. Default 'html'.

* @type string $before Markup to prepend to the beginning of each link. Default empty.

* @type string $after Markup to append to the end of each link. Default empty.

* @type bool $show_post_count Whether to display the post count alongside the link. Default false.

* @type bool $echo Whether to echo or return the links list. Default 1|true to echo.

* @type string $order Whether to use ascending or descending order. Accepts 'ASC', or 'DESC'.

* Default 'DESC'.

* }

* @return string|null String when retrieving, null when displaying.

*/

function wp_get_archives_image( $args = '' ) {

	global $wpdb, $wp_locale;


	$defaults = array(

		'type' => 'monthly', 'limit' => '',

		'format' => 'html', 'before' => '',

		'after' => '', 'show_post_count' => false,

		'echo' => 1, 'order' => 'DESC',
		
		'html' => '<img src="%1$s" alt="%2$s">', 
		

	);


	$r = wp_parse_args( $args, $defaults );


	if ( '' == $r['type'] ) {

		$r['type'] = 'monthly';

	}


	if ( ! empty( $r['limit'] ) ) {

		$r['limit'] = absint( $r['limit'] );

		$r['limit'] = ' LIMIT ' . $r['limit'];

	}


	$order = strtoupper( $r['order'] );

	if ( $order !== 'ASC' ) {

		$order = 'DESC';

	}


	// this is what will separate dates on weekly archive links

	$archive_week_separator = '&#8211;';


	// over-ride general date format ? 0 = no: use the date format set in Options, 1 = yes: over-ride

	$archive_date_format_over_ride = 0; //0


	// options for daily archive (only if you over-ride the general date format)

	$archive_day_date_format = 'Y/m/d';


	// options for weekly archive (only if you over-ride the general date format)

	$archive_week_start_date_format = 'Y/m/d';

	$archive_week_end_date_format	= 'Y/m/d';


	if ( ! $archive_date_format_over_ride ) {

		$archive_day_date_format = get_option( 'date_format' );

		$archive_week_start_date_format = get_option( 'date_format' );

		$archive_week_end_date_format = get_option( 'date_format' );

	}


	/**

	 * Filter the SQL WHERE clause for retrieving archives.

	 *

	 * @since 2.2.0

	 *

	 * @param string $sql_where Portion of SQL query containing the WHERE clause.

	 * @param array $r An array of default arguments.

	 */

	$where = apply_filters( 'getarchives_where', "WHERE post_type = 'post' AND post_status = 'publish'", $r );


	/**

	 * Filter the SQL JOIN clause for retrieving archives.

	 *

	 * @since 2.2.0

	 *

	 * @param string $sql_join Portion of SQL query containing JOIN clause.

	 * @param array $r An array of default arguments.

	 */

	$join = apply_filters( 'getarchives_join', '', $r );


	$output = '';


	$last_changed = wp_cache_get( 'last_changed', 'posts' );

	if ( ! $last_changed ) {

		$last_changed = microtime();

		wp_cache_set( 'last_changed', $last_changed, 'posts' );

	}


	$limit = $r['limit'];


	if ( 'monthly' == $r['type'] ) {

		$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date) ORDER BY post_date $order $limit";

		$key = md5( $query );

		$key = "wp_get_archives:$key:$last_changed";

		if ( ! $results = wp_cache_get( $key, 'posts' ) ) {

			$results = $wpdb->get_results( $query );

			wp_cache_set( $key, $results, 'posts' );

		}

		if ( $results ) {

			$after = $r['after'];

			foreach ( (array) $results as $result ) {

				$url = get_month_link( $result->year, $result->month );

				/* translators: 1: month name, 2: 4-digit year */

				$text = sprintf( __( '%1$s %2$d' ), $wp_locale->get_month( $result->month ), $result->year );
				
				$img = sprintf( $r['html'] , sprintf( '%1$d%2$02d', $result->year,$result->month) , $text );

				if ( $r['show_post_count'] ) {

					$r['after'] = '&nbsp;(' . $result->posts . ')' . $after;

				}

				$output .= get_archives_link_image( $url, $img, $r['format'], $r['before'], $r['after'] );

			}

		}

	} elseif ( 'yearly' == $r['type'] ) {

		$query = "SELECT YEAR(post_date) AS `year`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date) ORDER BY post_date $order $limit";

		$key = md5( $query );

		$key = "wp_get_archives:$key:$last_changed";

		if ( ! $results = wp_cache_get( $key, 'posts' ) ) {

			$results = $wpdb->get_results( $query );

			wp_cache_set( $key, $results, 'posts' );

		}

		if ( $results ) {

			$after = $r['after'];

			foreach ( (array) $results as $result) {

				$url = get_year_link( $result->year );

				$text = sprintf( '%d', $result->year );
				
				$img = sprintf( $r['html'] , $result->year , $text );


				if ( $r['show_post_count'] ) {

					$r['after'] = '&nbsp;(' . $result->posts . ')' . $after;

				}

				$output .= get_archives_link_image( $url, $img, $r['format'], $r['before'], $r['after'] );

			}

		}

	} elseif ( 'daily' == $r['type'] ) {

		$query = "SELECT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, DAYOFMONTH(post_date) AS `dayofmonth`, count(ID) as posts FROM $wpdb->posts $join $where GROUP BY YEAR(post_date), MONTH(post_date), DAYOFMONTH(post_date) ORDER BY post_date $order $limit";

		$key = md5( $query );

		$key = "wp_get_archives:$key:$last_changed";

		if ( ! $results = wp_cache_get( $key, 'posts' ) ) {

			$results = $wpdb->get_results( $query );

			$cache[ $key ] = $results;

			wp_cache_set( $key, $results, 'posts' );

		}

		if ( $results ) {

			$after = $r['after'];

			foreach ( (array) $results as $result ) {

				$url = get_day_link( $result->year, $result->month, $result->dayofmonth );

				$date = sprintf( '%1$d-%2$02d-%3$02d 00:00:00', $result->year, $result->month, $result->dayofmonth );

				$text = mysql2date( $archive_day_date_format, $date );
				
				$img = sprintf( $r['html'] , mysql2date( "Ymd" , $date ) , $text );


				if ( $r['show_post_count'] ) {

					$r['after'] = '&nbsp;(' . $result->posts . ')' . $after;

				}

				$output .= get_archives_link_image( $url, $img, $r['format'], $r['before'], $r['after'] );

			}

		}

	} elseif ( 'weekly' == $r['type'] ) {

		$week = _wp_mysql_week( '`post_date`' );

		$query = "SELECT DISTINCT $week AS `week`, YEAR( `post_date` ) AS `yr`, DATE_FORMAT( `post_date`, '%Y-%m-%d' ) AS `yyyymmdd`, count( `ID` ) AS `posts` FROM `$wpdb->posts` $join $where GROUP BY $week, YEAR( `post_date` ) ORDER BY `post_date` $order $limit";

		$key = md5( $query );

		$key = "wp_get_archives:$key:$last_changed";

		if ( ! $results = wp_cache_get( $key, 'posts' ) ) {

			$results = $wpdb->get_results( $query );

			wp_cache_set( $key, $results, 'posts' );

		}

		$arc_w_last = '';

		if ( $results ) {

			$after = $r['after'];

			foreach ( (array) $results as $result ) {

				if ( $result->week != $arc_w_last ) {

					$arc_year = $result->yr;

					$arc_w_last = $result->week;

					$arc_week = get_weekstartend( $result->yyyymmdd, get_option( 'start_of_week' ) );

					$arc_week_start = date_i18n( $archive_week_start_date_format, $arc_week['start'] );

					$arc_week_end = date_i18n( $archive_week_end_date_format, $arc_week['end'] );

					$url = sprintf( '%1$s/%2$s%3$sm%4$s%5$s%6$sw%7$s%8$d', home_url(), '', '?', '=', $arc_year, '&amp;', '=', $result->week );

					$text = $arc_week_start . $archive_week_separator . $arc_week_end;
					
					$img = sprintf( $r['html'] , $arc_week['start'].$arc_week['end'] , $text );


					if ( $r['show_post_count'] ) {

						$r['after'] = '&nbsp;(' . $result->posts . ')' . $after;

					}

					$output .= get_archives_link_image( $url, $img, $r['format'], $r['before'], $r['after'] );

				}

			}

		}

	} elseif ( ( 'postbypost' == $r['type'] ) || ('alpha' == $r['type'] ) ) {

		$orderby = ( 'alpha' == $r['type'] ) ? 'post_title ASC ' : 'post_date DESC ';

		$query = "SELECT * FROM $wpdb->posts $join $where ORDER BY $orderby $limit";

		$key = md5( $query );

		$key = "wp_get_archives:$key:$last_changed";

		if ( ! $results = wp_cache_get( $key, 'posts' ) ) {

			$results = $wpdb->get_results( $query );

			wp_cache_set( $key, $results, 'posts' );

		}

		if ( $results ) {

			foreach ( (array) $results as $result ) {

				if ( $result->post_date != '0000-00-00 00:00:00' ) {

					$url = get_permalink( $result );

					if ( $result->post_title ) {

						/** This filter is documented in wp-includes/post-template.php */

						$text = strip_tags( apply_filters( 'the_title', $result->post_title, $result->ID ) );

					} else {

						$text = $result->ID;

					}
					
					$img = sprintf( $r['html'] , $result->post_name , $text );


					$output .= get_archives_link_image( $url, $img, $r['format'], $r['before'], $r['after'] );

				}

			}

		}

	}

	if ( $r['echo'] ) {

		echo $output;

	} else {

		return $output;

	}

}