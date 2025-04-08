<?php
/**
* These are a few pieces of code I find helpful and usually
* have it loaded on my custom themes.
*/

// Custom numeric pagination function
	function unaghii_get_numeric_pagination() {
		global $wp_query, $numpages;
		$total_pages	= $wp_query->max_num_pages;
		$big			= 999999999; // an unlikely integer
		if ( $total_pages > 1 ) {
			echo '<div class="numeric-pagination full-width-wrapper">';
			echo paginate_links(
				array(
					'base'		=> str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
					'format'	=> '/page/%#%',
					'current'	=> max(1, get_query_var('paged')),
					'total'		=> $wp_query->max_num_pages,
					'type'		=> 'plain',
					'prev_text'	=> '«',
					'next_text'	=> '»'
				)
			);
			echo '</div>';
		}
	}

// Get share buttons
	function unaghii_get_share_buttons( $link = "", $title = "" ) {
		global $post;
		if(empty($link))	$link = get_permalink($post->ID);
		if(empty($title))	$title = get_the_title($post->ID);
		echo '<div class="addthis_toolbox addthis_default_style"><a class="addthis_button_facebook_like" fb:like:layout="button_count" fb:like:href="'.$link.'"></a><a class="addthis_button_tweet" tw:url="'.$link.'" tw:text="'.$title.'"></a><a class="addthis_counter addthis_pill_style"></a></div>';
	}

// Video functions
	// Get video thumb
	function unaghii_get_video_thumb( $video_link ) {
		$is_vimeo = strpos($video_link, "vimeo");

		if ( $is_vimeo === false ) {
			// Youtube
			if(function_exists("unaghii_get_youtube_thumb")) return unaghii_get_youtube_thumb($video_link);
		} else {
			// Vimeo
			if(function_exists("unaghii_get_vimeo_thumb")) return unaghii_get_vimeo_thumb($video_link);
		}
	}

	// Get video iframe
	function unaghii_get_video_iframe( $video_link ) {
		$is_vimeo = strpos($video_link, "vimeo");

		if($is_vimeo === false){
			// Youtube
			return '<iframe class="video-iframe youtube-video" width="100%" height="480" src="http://www.youtube.com/embed/' . unaghii_get_youtube_ID($video_link) . '?wmode=transparent&rel=0" frameborder="0" allowfullscreen></iframe>';
		} else {
			// Vimeo
			return '<iframe class="video-iframe vimeo-video" width="100%" height="480" src="http://player.vimeo.com/video/' . unaghii_get_vimeo_ID($video_link) . '?title=1&amp;byline=0&amp;portrait=0&amp;color=7a7a7a" frameborder="0"></iframe>';
		}
	}

	// YouTube
	function unaghii_get_youtube_ID( $url ) {
		$is_short_url = strpos($url, "youtu.be");
		if($is_short_url === false){
			$string_to_divide = 'v=';
			$initial_position = 0;
		} else {
			$string_to_divide = 'youtu.be';
			$initial_position = 1;
		}
		$data = explode($string_to_divide, $url);
		$code = substr($data[1], $initial_position, 11);
		return $code;
	}
	function unaghii_get_youtube_thumb( $url ) {
		$code 	= (function_exists("unaghii_get_youtube_ID") ? unaghii_get_youtube_ID($url) : '');
		$thumb 	= '<img class="youtube-thumb video-thumb" src="http://img.youtube.com/vi/' . $code . '/0.jpg">';
		return $thumb;
	}
	
	// Vimeo
	function unaghii_get_vimeo_ID( $url ) {
		$result = preg_match('/(\d+)/', $url, $matches);
		return $matches[0];
	}
	function unaghii_get_vimeo_thumb( $video_link, $info = 'thumbnail_medium' ) {
		$video_ID = (function_exists("unaghii_get_vimeo_ID") ? unaghii_get_vimeo_ID($video_link) : '');
		if (!function_exists('curl_init')) die('CURL is not installed!');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://vimeo.com/api/v2/video/$video_ID.php");
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$output = unserialize(curl_exec($ch));
		$output = $output[0][$info];
		curl_close($ch);
		$output = '<img class="vimeo-thumb video-thumb" src="' . $output . '" />';
		return $output;
	}

// If post has children or siblings
	function unaghii_has_children_or_siblings( $post_id ) {
		$parent		= get_post( $post_id );
		$children	= get_posts( array(
			'post_type'		=> $parent->post_type,
			'post_parent'	=> $post_id,
		));
		if($parent->post_parent != 0){
			$siblings	= get_posts( array(
				'post_type'		=> $parent->post_type,
				'post_parent'	=> $parent->post_parent,
			));
		}

		if ( !empty( $children ) ) {
			return "children";
		} elseif ( !empty( $siblings ) ) {
			return "siblings";
		} else {
			return false;
		}
	}
	
// Get the first category ID
	function unaghii_get_first_category_ID() {
		$category = get_the_category();
		return $category[0]->cat_ID;
	}

// Breadcrumbs
	function unaghii_get_breadcrumbs() {
		$delimiter = '&raquo;';
		$name = 'Home';
		$currentBefore = '<span class="current">';
		$currentAfter = '</span>';
		echo '<p class="breadcrumbs">';
		if ( is_home() ) {
			echo '<span class="current">Home</span>';
		}
		if( !is_home() && !is_front_page() || is_paged() ) {
			global $post, $wp_query;
			$home = get_bloginfo('url');
			echo '<a href="' . $home . '" title="Home">' . $name . '</a> ' . $delimiter . ' ';
			if( is_category() ) {
				$cat_obj = $wp_query->get_queried_object();
				$thisCat = $cat_obj->term_id;
				$thisCat = get_category($thisCat);
				$parentCat = get_category($thisCat->parent);
				if($thisCat->parent != 0) echo(get_category_parents($parentCat, TRUE, ' ' . $delimiter . ' '));
				echo $currentBefore;
				single_cat_title();
				echo $currentAfter;
			} elseif ( is_post_type_archive() ) {
				$post_type = $wp_query->query_vars['post_type'];
				$post_type = get_post_type_object($post_type);
				echo $currentBefore . $post_type->labels->name . $currentAfter;
			} elseif ( is_tax() ) {
				$tax = $wp_query->query_vars['taxonomy'];
				$tax = get_taxonomy($tax);
				echo $currentBefore . $tax->labels->name . $currentAfter;
			} elseif ( is_day() ) {
				echo '<a href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a> ' . $delimiter . ' ';
				echo '<a href="' . get_month_link(get_the_time('Y'),get_the_time('m')) . '">' . get_the_time('F') . '</a> ' . $delimiter . ' ';
				echo $currentBefore . get_the_time('d') . $currentAfter;
			} elseif ( is_month() ) {
				echo '<a href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a> ' . $delimiter . ' ';
				echo $currentBefore . get_the_time('F') . $currentAfter;
			} elseif ( is_year() ) {
				echo $currentBefore . get_the_time('Y') . $currentAfter;
			} elseif ( is_single() && !is_attachment() ) {
				if("post" == $post->post_type){
					$cat = get_the_category(); $cat = $cat[0];
					echo get_category_parents($cat, TRUE, ' ' . $delimiter . ' ');
				} else {
					$post_type 		= get_post_type_object($post->post_type);
					$post_parent 	= $post->post_parent;
					echo '<a href="' . $home . "/" . $post_type->name . '/" title="'. $post_type->labels->name .'">' . $post_type->labels->name . '</a> ' . $delimiter;
					if($post_parent != 0){
						echo '<a href="' . get_permalink($post_parent) . '">' . get_the_title($post_parent) . '</a> ' . $delimiter . ' ';
					}
				}
				echo $currentBefore;
				the_title();
				echo $currentAfter;
			} elseif ( is_attachment() ) {
				$parent = get_post($post->post_parent);
				$cat = get_the_category($parent->ID); $cat = $cat[0];
				echo get_category_parents($cat, TRUE, ' ' . $delimiter . ' ');
				echo '<a href="' . get_permalink($parent) . '">' . $parent->post_title . '</a> ' . $delimiter . ' ';
				echo $currentBefore;
				the_title();
				echo $currentAfter;
			} elseif ( is_page() && !$post->post_parent ) {
				echo $currentBefore;
				the_title();
				echo $currentAfter;
			} elseif ( is_page() && $post->post_parent ) {
				$parent_id  = $post->post_parent;
				$breadcrumbs = array();
				while ( $parent_id ) {
					$page = get_page($parent_id);
					$breadcrumbs[] = '<a href="' . get_permalink($page->ID) . '">' . get_the_title($page->ID) . '</a>';
					$parent_id  = $page->post_parent;
				}
				$breadcrumbs = array_reverse($breadcrumbs);
				foreach ($breadcrumbs as $crumb) echo $crumb . ' ' . $delimiter . ' ';
				echo $currentBefore;
				the_title();
				echo $currentAfter;
			} elseif ( is_search() ) {
				echo $currentBefore . 'Resultados da busca por &#39;' . get_search_query() . '&#39;' . $currentAfter;
			} elseif ( is_tag() ) {
				echo $currentBefore . 'Posts com a tag &#39;';
				single_tag_title();
				echo '&#39;' . $currentAfter;
			} elseif ( is_author() ) {
				global $author;
				$userdata = get_userdata($author);
				echo $currentBefore . 'Posts postados por ' . $userdata->display_name . $currentAfter;
			} elseif ( is_404() ) {
				echo $currentBefore . 'Erro 404' . $currentAfter;
			}
			if( get_query_var('paged') ) {
				if(is_category() || is_day() || is_month() || is_year() || is_search() || is_tag() || is_author() ) echo ' (';
				echo __('Página') . ' ' . get_query_var('paged');
				if(is_category() || is_day() || is_month() || is_year() || is_search() || is_tag() || is_author() ) echo ')';
			}
		}
		echo '</p>';
	}
?>