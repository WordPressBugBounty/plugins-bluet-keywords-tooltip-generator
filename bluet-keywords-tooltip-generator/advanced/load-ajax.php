<?php
////
add_action( 'wp_ajax_tltpy_load_keywords', 'tltpy_load_keywords' );
add_action( 'wp_ajax_nopriv_tltpy_load_keywords', 'tltpy_load_keywords' );

function tltpy_load_keywords() {
	// Verify nonce for security
	if (!wp_verify_nonce($_POST['nonce'] ?? '', 'tltpy_load_keywords_nonce')) {
		wp_die('Security check failed');
	}
	
	global $tooltip_post_types, $tooltipy_cat_name;

	//init added classes
	$options = get_option( 'bluet_kw_style' ); //to get the ['bt_kw_fetch_mode']
												
	(!empty($options['bt_kw_add_css_classes']['keyword'])) 	? $css_classes_added_inline_keywords=$options['bt_kw_add_css_classes']['keyword'] 	: $css_classes_added_inline_keywords="";
	(!empty($options['bt_kw_add_css_classes']['popup'])) 	? $css_classes_added_popups=$options['bt_kw_add_css_classes']['popup'] 				: $css_classes_added_popups="";

	// récupération du mot tapé dans la recherche

	$options=get_option('bluet_kw_advanced');    
    if(empty($options['kttg_fetch_all_keywords'])){
        $kttg_fetch_all_keywords=false; 
    }else if($options['kttg_fetch_all_keywords']=="on"){
        $kttg_fetch_all_keywords=true;
    }

	$keyword_ids = array();

	if(empty($_POST['keyword_ids'])){
		die();
	}else{
		foreach ($_POST['keyword_ids'] as $keyword_id) {
			array_push($keyword_ids, sanitize_key($keyword_id) );
		}
		$keyword_ids = array_unique($keyword_ids);
	}

	//glossary link
    $glossary_options = get_option('bluet_glossary_options');
	
	$args = array(
	    /*'post__in' => $keyword_ids,*/
		'post_type' => $tooltip_post_types,
		'posts_per_page'=>-1
	);

	if(!$kttg_fetch_all_keywords){
		$args['post__in']=$keyword_ids;
	}

	$options = get_option( 'bluet_kw_settings' );	

	if(!empty($options['bt_kw_hide_title']) and $options['bt_kw_hide_title']=='on'){
		$hide_title=true;
	}else{
		$hide_title=false;
	}
	
	$ajax_query = new WP_Query($args);

	if ( $ajax_query->have_posts() ) {
		while ( $ajax_query->have_posts() ){
			$ajax_query->the_post();

			$tooltipy_kw_id=get_the_id();
 
            //get youtube class to integrate it on the class
            $tooltipy_youtube=get_post_meta($tooltipy_kw_id,'bluet_youtube_video_id',true);
            $tooltipy_youtube_class="";
            if(strlen($tooltipy_youtube)>5){
                $tooltipy_youtube_class="tooltipy-pop-youtube";
            }
 
            //get families ids to integrate them on the class
            $tooltipy_families_arr = wp_get_post_terms($tooltipy_kw_id, $tooltipy_cat_name,array("fields" => "ids"));
 
            foreach ($tooltipy_families_arr as $key => $value) {
                $tooltipy_families_arr[$key]="tooltipy-pop-cat-".$value;
            }
            $tooltipy_families_class=implode(" ",$tooltipy_families_arr);

			?>
			<span class="bluet_block_to_show tooltipy-pop tooltipy-pop-<?php echo($tooltipy_kw_id.' '.$tooltipy_families_class.' '.$tooltipy_youtube_class .' '.$css_classes_added_popups);?>" data-tooltip="<?php echo($tooltipy_kw_id); ?>">
				
				<div class="bluet_hide_tooltip_button">×</div>
				
				<div class="bluet_block_container">
				<?php
				if($tooltipy_youtube==""){		
				?>				
					<div class="bluet_img_in_tooltip">
						<?php echo(get_the_post_thumbnail($tooltipy_kw_id,'medium')); ?>
					</div>
					<?php
				}else{
					?>				
					<div class="bluet_img_in_tooltip">
						<iframe src="https://www.youtube.com/embed/<?php echo esc_attr($tooltipy_youtube); ?>?rel=0&showinfo=0" frameborder="0" allowfullscreen width="100%">
						</iframe>						
					</div>
					<?php
				}
				?>
						<div class="bluet_text_content">
						
							<?php
							if(!$hide_title){
								echo('<span class="bluet_title_on_block">'.get_the_title().'</span>');
							}
							the_content(); 
							?>
						</div>
					<div class="bluet_block_footer">
					 <?php
                        if(!empty($glossary_options['bluet_kttg_show_glossary_link']) and $glossary_options['bluet_kttg_show_glossary_link']=='on'){
                        ?>
                            <p class="bluet_block_glossary_link">
                                <a href="<?php echo esc_url($glossary_options['kttg_link_glossary_page_link']); ?>">
                                    <?php
                                    if(strlen($glossary_options['kttg_link_glossary_label'])){
                                        echo esc_html($glossary_options['kttg_link_glossary_label']);
                                    }else{
                                        echo("View glossary");
                                    }
                                    ?>
                                </a>
                            </p>
                        <?php
                        }
                        ?> 
					</div>
				</div>
			</span>
			<?php
		}
	}
	?>
	<script type="text/javascript">
		//add listeners to tooltips 
		jQuery(".bluet_block_to_show").mouseover(function(){
			//.show()
			jQuery(this).show();
		});
		jQuery(".bluet_block_to_show").mouseout(function(){
			//leave it like that .css("display","none"); for Safari navigator issue
			jQuery(this).css("display","none");
		});
		
		jQuery(".bluet_hide_tooltip_button").click(function(){
			//leave it like that .css("display","none"); for Safari navigator issue
			jQuery(".bluet_block_to_show").css("display","none");
			//jQuery(".bluet_block_to_show");
		});
	</script>
	<?php

	die();
}

add_action('wp_footer', function(){
	?>
	<script>
	var currentHoveredKeyword = false;

	//once keywords fetched (highlihted)
	jQuery(document).on("keywordsFetched",function() {
		var keyw=[];
		jQuery("body .bluet_tooltip").each(function(){
			keyw.push(jQuery(this).data('tooltip'));
		});
		
		jQuery.post(
			'<?php echo admin_url('admin-ajax.php'); ?>',
			{
				'action': 'tltpy_load_keywords',
				'keyword_ids': keyw,
				'nonce': '<?php echo wp_create_nonce('tltpy_load_keywords_nonce'); ?>'
			},
			function(response){
				jQuery('#tooltip_blocks_to_show .bluet_block_to_show').remove(':not(#loading_tooltip)');

			
				jQuery('#tooltip_blocks_to_show').append(response);
				
				jQuery.event.trigger("keywordsLoaded");
			}
		);
	});

	jQuery(document).on("keywordsLoaded",function() {
		jQuery('#loading_tooltip').remove();

		if(currentHoveredKeyword && currentHoveredKeyword?.trigger && typeof currentHoveredKeyword.trigger === 'function' ){
			// To show the current tooltip if a kayword is hevered
			currentHoveredKeyword.trigger('mouseover');
			currentHoveredKeyword = 'done';
		}

		<?php if ( wp_script_is( 'wp-mediaelement', 'enqueued' ) ) { ?>
			//for [audio] and [video] shortcodes to generate audio after keywords load
			jQuery('.tooltipy-pop .wp-audio-shortcode[style*="visibility:hidden"], .tooltipy-pop .wp-video-shortcode[style*="visibility:hidden"]').mediaelementplayer();
			jQuery('.tooltipy-pop .wp-audio-shortcode[style*="visibility: hidden"], .tooltipy-pop .wp-video-shortcode[style*="visibility: hidden"]').mediaelementplayer();
		<?php } ?>	
		
		//to prevent empty div on the top
		/*if(jQuery("#tooltip_blocks_to_show").find(".bluet_block_to_show").length==0){
			jQuery("#tooltip_blocks_to_show").remove();
		}*/
	});
	</script>
	<?php
});