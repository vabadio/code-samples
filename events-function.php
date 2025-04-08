<?php

/**
* Function to return events to a calendar (Javascript based), with filters set by the user.
*
* Client: an online art magazine
*
*/
	function unaghii_get_events(
		$filters = array(),
		$return_type = 'array',
		$number = -1,
		$events_to_exclude = array(),
		$get_total_posts_number = false,
		$page = 1
	){

		global $theme_prefix;		

		$events = array();

		$query_args = array (
			'post_type' => 'agenda',
			'meta_key' 	=> $theme_prefix . 'date_start_sort',
			'orderby' 	=> 'meta_value',			
		);

		// Brazil's LatLng
		$latlng = array(
			"lat" => "-13.7266235",
			"lng" => "-56.4143366",
		);

		// Filters
		$current_period = $filters['time-spam'];
		$current_city 	= $filters['city'];
		$current_type 	= $filters['type'];
		$current_search = $filters['search'];

		if ( !empty ( $current_period ) ) {
			switch ( $current_period ) {

				// Today
				case 'today':
					$query_args['meta_query'] = array (
						'relation' => 'AND',
						array(
							'key' 		=> $theme_prefix . 'date_start_sort',							
							'value'		=> date( "Y-m-d" ),
							'type'		=> 'DATE',
							'compare'	=> '<='
						),
						array(
							'key' 		=> $theme_prefix . 'date_end_sort',							
							'value'		=> date( "Y-m-d" ),
							'type'		=> 'DATE',
							'compare'	=> '>='
						)
					);
					break;

				// This week
				case 'this-week':
					$today_day_of_week = date('w');

					// First day of the week
					if ( $today_day_of_week == 0 ) {
						$week_begining = strtotime("today");
					} else {
						$week_begining = strtotime("last Sunday");
					}

					// Last day of the week
					if ( $today_day_of_week == 6 ) {
						$week_end = strtotime("today");
					} else {
						$week_end = strtotime("next Saturday");
					}

					$week_begining_formatted	= strftime("%Y-%m-%d", $week_begining);
					$week_end_formatted			= strftime("%Y-%m-%d", $week_end);

					$query_args['meta_query'] = array (
						'relation' => 'AND',
						array(
							'key' 		=> $theme_prefix . 'date_start_sort',
							'value'		=> $week_begining_formatted,
							'type'		=> 'DATE',
							'compare'	=> '<='
						),
						array(
							'key' 		=> $theme_prefix . 'date_end_sort',
							'value'		=> $week_end_formatted,
							'type'		=> 'DATE',
							'compare'	=> '>='
						)
					);
					break;

				// This weekend
				case 'this-weekend':
					$today = date("N");
					if ( $today == 6 ) {
						$sat = date("Y-m-d");
						$sun = strtotime("+1 day");
						$sun = strftime("%Y-%m-%d", $sun);
					} elseif ( $today == 7 ) {
						$sun = date("Y-m-d");
						$sat = strtotime("-1 day");
						$sat = strftime("%Y-%m-%d", $sat);
					} else {
						$sat = strtotime("next Saturday");
						$sat = strftime("%Y-%m-%d", $sat);
						$sun = strtotime("next Sunday");
						$sun = strftime("%Y-%m-%d", $sun);
					}

					$query_args['meta_query'] = array (
						'key' 		=> $theme_prefix . 'date_sort',
						'value'		=> array($sat, $sun),
						'type'		=> 'DATE',
						'compare'	=> 'IN',
					);
					break;

				// Next week
				case 'next-week':
					$today_day_of_week = date('w');

					// First day of the weekend
					if ( $today_day_of_week == 0 ) {
						$weekend_begining 	= strtotime("yesterday");
						$weekend_end 		= strtotime("today");
					} elseif ( $today_day_of_week == 6 ) {
						$weekend_begining 	= strtotime("today");
						$weekend_end 		= strtotime("tomorrow");
					} else {
						$weekend_begining	= strtotime("next Saturday");
						$weekend_end		= strtotime("next Sunday");
					}

					$weekend_begining_formatted	= strftime("%Y-%m-%d", $weekend_begining);
					$weekend_end_formatted		= strftime("%Y-%m-%d", $weekend_end);

					$query_args['meta_query'] = array(
						'relation' => 'AND',
						array(
							'key' 		=> $theme_prefix . 'date_start_sort',
							'value'		=> $weekend_begining_formatted,
							'type'		=> 'DATE',
							'compare'	=> '<='
						),
						array(
							'key' 		=> $theme_prefix . 'date_end_sort',
							'value'		=> $weekend_end_formatted,
							'type'		=> 'DATE',
							'compare'	=> '>='
						)
					);
					break;

				// This month
				case 'this-month':
					$first_day_month = new DateTime('first day of this month');
					$first_day_month_formatted = $first_day_month->Format('Y-m-d');

					$last_day_month = new DateTime('last day of this month');
					$last_day_month_formatted = $last_day_month->Format('Y-m-d');

					$query_args['meta_query'] = array (
						'relation' => 'AND',
						array(
							'key' 		=> $theme_prefix . 'date_start_sort',
							'value'		=> $first_day_month_formatted,
							'type'		=> 'DATE',
							'compare'	=> '<='
						),
						array(
							'key' 		=> $theme_prefix . 'date_end_sort',
							'value'		=> $last_day_month_formatted,
							'type'		=> 'DATE',
							'compare'	=> '>='
						)
					);
					break;

				// Future
				case 'future':
					$query_args['meta_query'] = array (
						'key' 		=> $theme_prefix . 'date_start_sort',
						'value'		=> date( "Y-m-d" ),
						'type'		=> 'DATE',
						'compare'	=> '>'
					);
					break;
				
				// Past
				case 'past':
					$query_args['meta_query'] = array (
						'key' 		=> $theme_prefix . 'date_end_sort',
						'value'		=> date( "Y-m-d" ),
						'type'		=> 'DATE',
						'compare'	=> '<'
					);
					break;

				// >
				default:
					$query_args['meta_query'] = array (
						'key' 		=> $theme_prefix . 'date_end_sort',
						'value'		=> date( "Y-m-d" ),
						'type'		=> 'DATE',
						'compare'	=> '>'
					);
					break;
			}
		}

		// City
		if ( !empty ( $current_city ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' 	=> 'cidade',
				'field' 	=> 'slug',
				'terms' 	=> $current_city,
			);

			if ( !is_array ( $current_city ) ) {
				$city_obj = get_term_by('slug', $current_city, 'cidade');
				if(!empty($city_obj) && !is_wp_error($city_obj)){
					if(function_exists('get_tax_meta')){
						$city_lat = get_tax_meta($city_obj->term_id, $theme_prefix . 'lat');
						$city_lng = get_tax_meta($city_obj->term_id, $theme_prefix . 'lng');

						if(!empty($city_lat) && !empty($city_lng)){
							$city_lat = str_replace(",", ".", $city_lat);
							$city_lng = str_replace(",", ".", $city_lng);
							$latlng = array("lat" => $city_lat, "lng" => $city_lng);
						}
					}
				}
			}
		}

		// Type
		if ( !empty ( $current_type ) ) {
			$query_args['tipo'] = $current_type;
		}

		// Search
		if ( !empty ( $current_search ) ) {
			$query_args['s'] = $current_search;
		}

		// Posts per page
		if ( !empty ( $number ) ) {
			$query_args['posts_per_page'] = $number;
		}

		// Exclude events
		if ( !empty ( $events_to_exclude ) ) {
			$query_args['exclude'] = $events_to_exclude;
		}

		// Page		
		$query_args['paged'] = $page;

		// Add LatLng
		$events['latlng'] = $latlng;

		$events_posts = get_posts($query_args);		

		// Total number of posts in the query
		if ( $get_total_posts_number == true ) {
			$query_args['posts_per_page']	= -1;
			$query_args['paged']			= 1;
			$query_args['fields']			= 'ids';
			
			$events['total_number']			= count ( get_posts ( $query_args ) );
		}		

		if ( !empty( $events_posts ) && !is_wp_error ( $events_posts ) ) {
			if($return_type == "array"){
				$events = array_merge($events, $events_posts);
			} else {
				foreach ($events_posts as $event) {
					$date 		= (function_exists("unaghii_get_date") ? unaghii_get_date($event->ID, 'date') : '');
					$date_end	= (function_exists("unaghii_get_date") ? unaghii_get_date($event->ID, 'date_end') : '');
					$venue 		= get_post_meta($post->ID, $theme_prefix . 'venues', true);
					if ( !empty ( $venue ) ) {
						$venue_obj = get_post($venue);
						if ( !empty ( $venue_obj ) && !is_wp_error ( $venue_obj ) ) {
							$venue = $venue_obj->post_title;
						}
					}

					$lat = get_post_meta($event->ID, $theme_prefix . 'lat', true);
					$lat = str_replace(",", ".", $lat);
					$lng = get_post_meta($event->ID, $theme_prefix . 'lng', true);
					$lng = str_replace(",", ".", $lng);

					$events[$event->ID]['id'] = $event->ID;
					$events[$event->ID]['lat'] = $lat;
					$events[$event->ID]['lng'] = $lng;
					$events[$event->ID]['title'] = $event->post_title;
					$events[$event->ID]['content'] = '<div class="event" id="event-' . $event->ID . '">
						<div class="event-wrapper">
							<h1><a href="' . get_permalink($event->ID) . '" title="' . $event->post_title . '">' . $event->post_title . '</a></h1>
							' . (!empty($date) ? '<p class="event-meta event-date"><span class="dashicons dashicons-calendar-alt"></span> <strong>'. date("d/m/y", $date) : '') . (!empty($date_end) && $date != $date_end ? ' à '. date("d/m/y", $date_end) : '' ) . (!empty($date) ? '</strong></p>' : '') . '
							' . (!empty($venue) ? '<p class="event-meta event-date"><span class="dashicons dashicons-location"></span> ' . $venue . '</p>' : '') . '
							' . apply_filters("the_content", $event->post_excerpt) . '
							<a class="event-more-btn" href="' . get_permalink($event->ID) . '" title="' . __('Mais detalhes', 'lang') . '">' . __('Mais detalhes', 'lang') . '</a>
							<a class="close-btn" href="javascript:;" title="' . __('Fechar', 'lang') . '"><span class="dashicons dashicons-no"></span></a>
						</div>
					</div>';
				}
			}
		}

		return $events;
	}