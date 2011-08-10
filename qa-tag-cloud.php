<?php

/*
	Question2Answer 1.4.1 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/tag-cloud-widget/qa-tag-cloud.php
	Version: 1.4.1
	Date: 2011-07-10 06:58:57 GMT
	Description: Widget module class for tag cloud plugin


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/

	class qa_tag_cloud {
		
		function option_default($option)
		{
			if ($option=='tag_cloud_count_tags')
				return 100;
			elseif ($option=='tag_cloud_font_size')
				return 24;
			elseif ($option=='tag_cloud_min_font_size')
				return 8;
			elseif ($option=='tag_cloud_sort_type')
				return 'alphabetical';
			elseif ($option=='tag_cloud_size_popular')
				return true;
		}
		
		function admin_form()
		{
			$saved=false;
			
			if (qa_clicked('tag_cloud_save_button')) {
				qa_opt('tag_cloud_count_tags', (int)qa_post_text('tag_cloud_count_tags_field'));
				qa_opt('tag_cloud_font_size', (int)qa_post_text('tag_cloud_font_size_field'));
				qa_opt('tag_cloud_min_font_size', (int)qa_post_text('tag_cloud_min_font_size_field'));
				qa_opt('tag_cloud_sort_type', ((int)qa_post_text('tag_cloud_sort_type') == 0?'alphabetical':'numerical'));
				qa_opt('tag_cloud_size_popular', (int)qa_post_text('tag_cloud_size_popular_field'));
				$saved=true;
			}
			
			return array(
				'ok' => $saved ? 'Tag cloud settings saved' : null,
				
				'fields' => array(
					array(
						'label' => 'Number of tags to show:',
						'type' => 'number',
						'value' => (int)qa_opt('tag_cloud_count_tags'),
						'tags' => 'NAME="tag_cloud_count_tags_field"',
					),

					array(
						'label' => 'Max font size (in pixels):',
						'type' => 'number',
						'value' => (int)qa_opt('tag_cloud_font_size'),
						'tags' => 'NAME="tag_cloud_font_size_field"',
					),
					

					array(
						'label' => 'Min font size (in pixels):',
						'type' => 'number',
						'value' => (int)qa_opt('tag_cloud_min_font_size'),
						'tags' => 'NAME="tag_cloud_min_font_size_field"',
					),
					

					array(
						'label' => 'Min font size (in pixels):',
						'type' => 'select-radio',
						'value' => qa_opt('tag_cloud_sort_type'),
						'options' => array('alphabetical','numerical')
						'tags' => 'NAME="tag_cloud_sort_type"',
					),
					
					array(
						'label' => 'Font size represents tag popularity',
						'type' => 'checkbox',
						'value' => qa_opt('tag_cloud_size_popular'),
						'tags' => 'NAME="tag_cloud_size_popular_field"',
					),
				),
				
				'buttons' => array(
					array(
						'label' => 'Save Changes',
						'tags' => 'NAME="tag_cloud_save_button"',
					),
				),
			);
		}
		
		function allow_template($template)
		{
			$allow=false;
			
			switch ($template)
			{
				case 'activity':
				case 'qa':
				case 'questions':
				case 'hot':
				case 'ask':
				case 'categories':
				case 'question':
				case 'tag':
				case 'tags':
				case 'unanswered':
				case 'user':
				case 'users':
				case 'search':
				case 'admin':
					$allow=true;
					break;
			}
			
			return $allow;
		}
		
		function allow_region($region)
		{
			return ($region=='side');
		}
		
		function output_widget($region, $place, $themeobject, $template, $request, $qa_content)
		{
			require_once QA_INCLUDE_DIR.'qa-db-selects.php';
			
			$populartags=qa_db_single_select(qa_db_popular_tags_selectspec(0, qa_opt('tag_cloud_count_tags')));
			
			$max = max(array_values($populartags));
			$min = min(array_values($populartags));			
			
			// convert from linear to log
			
			$populartags = FromParetoCurve($populartags, $min, $max);
			
			// sort alphabetical
			
			ksort($populartags);
			
			$themeobject->output(
				'<DIV CLASS="qa-nav-cat-list qa-nav-cat-link" STYLE="margin:0;">',
				qa_lang_html('main/popular_tags'),
				'</DIV>'
			);
			
			$themeobject->output('<DIV STYLE="font-size:10px;">');
			
			$maxsize=qa_opt('tag_cloud_font_size');
			$minsize=qa_opt('tag_cloud_min_font_size');
			$scale=qa_opt('tag_cloud_size_popular');
			
			foreach ($populartags as $tag => $count) {
				$size=number_format(($scale ? ($maxsize-$minsize*$count/$maxcount)-$minsize : $maxsize), 1);
				
				$themeobject->output('<A HREF="'.qa_path_html('tag/'.$tag).'" STYLE="font-size:'.$size.'px; vertical-align:baseline;">'.qa_html($tag).'</A>');
			}
			
			$themeobject->output('</DIV>');
		}
		
		// the magic

		function FromParetoCurve($weights, $minSize, $maxSize)
		{

			$logweights = array(); // array of log value of counts
			$output = array(); // output array of linearized count values

			 // Convert each weight to its log value.
			foreach ($weights AS $tagname => $w)
			{
				// take each weight from input, convert to log, put into new array called logweights
				$logweights[$tagname] = log($w);
			}

			 // MAX AND MIN OF logweights ARRAY
			$max = max(array_values($logweights));
			$min = min(array_values($logweights));

			foreach($logweights AS $lw)
			{     
				if($lw < $min) {
					$min = $lw;
				}
				if($lw > $max)
				{
					$max = $lw;
				}
			}

			// Now calculate the slope of a straight line, from min to max.
			if($max > $min)
			{
				 $slope = ($maxSize - $minSize) / ($max - $min);
			}

			$middle = ($minSize + $maxSize) / 2;

			foreach($logweights AS $tagname => $w)
			{
				if($max <= $min)           {     
					//With max=min all tags have the same weight.     
					$output[$tagname] = $middle;    
				}    
				else    {     
					// Calculate the distance from the minimum for this weight.     
					$distance = $w - $min;
					//Calculate the position on the slope for this distance.
					$result = $slope * $distance + $minSize;
					// If the tag turned out too small, set minSize.
					if( $result < $minSize) {
						$result = $minSize;     
					}     //If the tag turned out too big, set maxSize.
					if( $result > $maxSize)
					{
						$result = $maxSize;
					}
					$output[$tagname] = $result;
				}
			}
			return $output;
		}
	
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
