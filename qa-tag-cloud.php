<?php

	class qa_log_tag_cloud {
		
		function option_default($option)
		{
			switch ($option) {
				case 'log_tag_cloud_header':
					return qa_lang_html('main/popular_tags');
				case 'log_tag_cloud_count_tags':
					return 100;
				case 'log_tag_cloud_min_count':
					return 1;
				case 'log_tag_cloud_font_size':
					return 24;
				case 'log_tag_cloud_min_font_size':
					return 8;
				case 'log_tag_cloud_sort_type':
					return 'alphabetical';
				case 'log_tag_cloud_size_popular':
					return true;
				default:
					return false;
			}
		}
		
		function admin_form()
		{
			$saved=false;
			
			if (qa_clicked('log_tag_cloud_save_button')) {
				qa_opt('log_tag_cloud_header', (int)qa_post_text('log_tag_cloud_header'));
				qa_opt('log_tag_cloud_count_tags', (int)qa_post_text('log_tag_cloud_count_tags_field'));
				qa_opt('log_tag_cloud_min_count', (int)qa_post_text('log_tag_cloud_min_count'));
				qa_opt('log_tag_cloud_font_size', (int)qa_post_text('log_tag_cloud_font_size_field'));
				qa_opt('log_tag_cloud_min_font_size', (int)qa_post_text('log_tag_cloud_min_font_size_field'));
				qa_opt('log_tag_cloud_sort_type', ((int)qa_post_text('log_tag_cloud_sort_type') == 0?'alphabetical':'numerical'));
				qa_opt('log_tag_cloud_size_popular', (int)qa_post_text('log_tag_cloud_size_popular_field'));
				$saved=true;
			}
			
			return array(
				'ok' => $saved ? 'Tag cloud settings saved' : null,
				
				'fields' => array(
					array(
						'label' => 'Tag cloud header (blank to disable):',
						'type' => 'text',
						'value' => qa_opt('log_tag_cloud_header'),
						'tags' => 'NAME="log_tag_cloud_header"',
					),
					array(
						'label' => 'Number of tags to show:',
						'type' => 'number',
						'value' => (int)qa_opt('log_tag_cloud_count_tags'),
						'tags' => 'NAME="log_tag_cloud_count_tags_field"',
					),
					array(
						'label' => 'Minimum count required to show:',
						'type' => 'number',
						'value' => (int)qa_opt('log_tag_cloud_min_count'),
						'tags' => 'NAME="log_tag_cloud_min_count"',
					),

					array(
						'label' => 'Max font size (in pixels):',
						'type' => 'number',
						'value' => (int)qa_opt('log_tag_cloud_font_size'),
						'tags' => 'NAME="log_tag_cloud_font_size_field"',
					),
					

					array(
						'label' => 'Min font size (in pixels):',
						'type' => 'number',
						'value' => (int)qa_opt('log_tag_cloud_min_font_size'),
						'tags' => 'NAME="log_tag_cloud_min_font_size_field"',
					),
					

					array(
						'label' => 'Tag cloud sort type:',
						'type' => 'select-radio',
						'value' => qa_opt('log_tag_cloud_sort_type'),
						'options' => array('alphabetical','numerical'),
						'tags' => 'NAME="log_tag_cloud_sort_type"',
					),
					
					array(
						'label' => 'Font size represents tag popularity',
						'type' => 'checkbox',
						'value' => qa_opt('log_tag_cloud_size_popular'),
						'tags' => 'NAME="log_tag_cloud_size_popular_field"',
					),
				),
				
				'buttons' => array(
					array(
						'label' => 'Save Changes',
						'tags' => 'NAME="log_tag_cloud_save_button"',
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
			
			$populartags=qa_db_single_select(
				array(
					'columns' => array('word' => 'BINARY word', 'tagcount'),
					'source' => '^words JOIN (SELECT wordid FROM ^words WHERE tagcount># ORDER BY tagcount DESC LIMIT #,#) y ON ^words.wordid=y.wordid',
					'arguments' => array((int)qa_opt('log_tag_cloud_min_count'),0, (int)qa_opt('log_tag_cloud_count_tags')),
					'arraykey' => 'word',
					'arrayvalue' => 'tagcount',
					'sortdesc' => 'tagcount',
				)
			);
			
			$maxsize=(int)qa_opt('log_tag_cloud_font_size');
			$minsize=(int)qa_opt('log_tag_cloud_min_font_size');		


			$scale=qa_opt('log_tag_cloud_size_popular');
			
			if($scale) {	
				// convert from linear to log
				
				$populartags = $this->FromParetoCurve($populartags, $minsize, $maxsize);
			}	
			
					
			if(qa_opt('log_tag_cloud_sort_type') == 'alphabetical') {
				
				// sort alphabetical
				
				ksort($populartags);
			}
			
			if(qa_opt('log_tag_cloud_header')) {
				$themeobject->output(
					'<DIV CLASS="qa-nav-cat-list qa-nav-cat-link" STYLE="margin:0;">',
					qa_lang_html('main/popular_tags'),
					'</DIV>'
				);
			}
			
			$themeobject->output('<DIV STYLE="font-size:10px;">');
			
			foreach ($populartags as $tag => $count) {
				$size=number_format(($scale ? $count : $maxsize), 1);
				
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
	
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/
