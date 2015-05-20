<?php
/*
Plugin Name: Real-Time Crowd
Plugin URI: http://www.realtimecrowd.net/
Description: Enables Real-Time Crowd tracking and generation on your WordPress site.
Version: 1.4
Author: RealTimeCrowd.net
Author URI: http://www.realtimecrowd.net/
License: GPL2
*/
function rtc_get_the_ID() 
{
	//$postid = url_to_postid($url);
	if (in_the_loop())
	{
		$post_id = get_the_ID();
	}
	else
	{
		global $wp_query;
		$post_id = $wp_query->get_queried_object_id();
	}
	return $post_id;
}

function rtc_install() 
{
	//Creates new database field
	add_option("rtc_account_name", '', '', 'yes');
	add_option("rtc_display_widget", '', '', 'yes');
}
function rtc_uninstall() 
{
	//Deletes the database field
	//delete_option('rtc_account_name');
	//delete_option('rtc_display_widget');
}

function rtc_admin_action_links( $links ) 
{
   // add Settings link
   $links[] = '<a href="'. get_admin_url(null, 'admin.php?page=real-time-crowd') .'">Settings</a>';
   //$links[] = '<a href="http://www.realtimecrowd.net" target="_blank">More info</a>';
   return $links;
}

function rtc_admin_left_menu() 
{
	add_menu_page('Real-Time Crowd plugin', 'Real-Time Crowd', 'manage_options', 'real-time-crowd', 'rtc_admin_html_page');
}

function rtc_admin_html_page() 
{
?>
<div class="wrap">
  <div id="icon-plugins" class="icon32"></div>
  <h2>Real-Time Crowd</h2>
  <form method="POST" action="options.php">
    <?php wp_nonce_field('update-options'); ?>
	<?php if( isset($_GET['settings-updated']) ) { ?>
    <div id="message" class="updated fade">
        <p><strong><?php _e('Settings saved.') ?></strong></p>
    </div>
<?php } ?>
	<p style="width: 80%;">
		First you need to register with <b><a target="_blank" href="http://www.realtimecrowd.net/">RealTimeCrowd.net</a></b>
		<br/>
		Then please input your RealTimeCrowd <b>account alias</b>, and check <b>Display Widget</b> box below.
		<br/>
		Visit <b><a target="_blank" href="http://www.realtimecrowd.net/Partner/Account">http://www.realtimecrowd.net/Partner/Account</a></b> to see a list of all your accounts.
		<br/>
		<span style="color:#B50C0C;">Please Note: Real-Time Crowd works only with "Pretty Permalinks". It does not work with ugly permalinks based on query strings (like those http://example.com/?p=N). <a href="<?php echo get_admin_url(null, 'options-permalink.php')?>">Permalink settings</a></span>
	</p>
    <table class="form-table">
      <tr valign="top">
        <th scope="row">
          <label for="rtc_account_name">Account alias</label>
        </th>
        <td>
          <input name="rtc_account_name" value="<?php echo get_option('rtc_account_name'); ?>" class="regular-text" />
          <span class="description">(ex: superblogger)</span>
        </td>
      </tr>
	  <tr valign="top">
        <th scope="row">
          <label for="rtc_display_widget">Display Widget</label>
        </th>
        <td>
          <input name="rtc_display_widget" type="checkbox" value="1" <?php checked( '1', get_option( 'rtc_display_widget' ) ); ?> />
          <span class="description">Checking this will display the widget on every post and page of the site. (The widget also contains a small icon linking to RealTimeCrowd.net)</span>
        </td>
      </tr>
    </table>   
    <input type="hidden" name="action" value="update" />
    <input type="hidden" name="page_options" value="rtc_account_name,rtc_display_widget" />
    <p class="submit">
      <input class="button-primary" type="submit" name="Save" value="<?php _e('Save'); ?>" />
    </p>
	<p style="width: 80%;">
		<br/>
		Send us <a target="_blank" href="http://www.realtimecrowd.net/Contact/">Feedback</a>
	</p>
  </form>
	<?php 
		$accountAlias = get_option("rtc_account_name");
		if (!empty($accountAlias))
		{
		?>
			<div>
				<h4>Real-Time data for account: <?php echo $accountAlias ?></h4>
				<iframe src="http://rtc.realtimecrowd.net/viewer/v1-1/<?php echo $accountAlias ?>/" style="border:none;width:100%;height:580px;"></iframe>
			</div>
		<?php
		}
		else
		{
		?>
			<div>
				<h4>Save your Account Alias to display Real-Time data from your website</h4>
			</div>
		<?php
		}
		?>
  	
</div>
<?php
}
function rtc_tracking_code() 
{
	$accountAlias = get_option("rtc_account_name");
	$displayWidget = get_option("rtc_display_widget");
	$pageTitle = "";
	$pageImageUrl = "";
	$postId = rtc_get_the_ID();
	if (empty($postId))
	{
		$postId = 0;
	}
	
	if ($displayWidget == "1")
	{
		$displayWidget = "true";
	}
	else	
	{
		$displayWidget = "false";
	}

	if ($postId > 0)
	{
		$pageTitle = get_the_title($postId);
		if (has_post_thumbnail($postId))
		{
			$image = wp_get_attachment_image_src(get_post_thumbnail_id($postId), array(100, 100));
			if (count($image)>0)
			{
				$pageImageUrl = $image[0];
			}
		}
		else if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))) &&
				 class_exists('Woocommerce'))
		{
			//if WooCommerce is active
			global $product;
			if ($product)
			{
				$attachment_ids = $product->get_gallery_attachment_ids();
				if (count($attachment_ids)>0)
				{
					$pageImageUrl = wp_get_attachment_url($attachment_ids[0]);
				}
			}
		}
	}
	else if (is_front_page())
	{
		$pageTitle = get_bloginfo('name');
	}

	if (!empty($accountAlias)) 
	{
		echo "<!--rtc code-->
		<div id='rtc-div-main'>
		</div>
		<script>
			!function (rtcObj, topElem)
			{
				function embedRtcLauncher(oUrl)
				{
					var scriptElem = topElem.createElement('script');
					scriptElem.type = 'text/javascript';
					scriptElem.async = !0;
					scriptElem.src = ('https:' == topElem.location.protocol ? 'https' : 'http') + ':' + oUrl + '/Scripts/RteVisitorLauncher-2.0.js';
					topElem.body.appendChild(scriptElem);
				}
				rtcObj.Account = '".$accountAlias."';
				rtcObj.ContainerId = 'rtc-div-main';
				rtcObj.RtcUrl = '//rtc.realtimecrowd.net';
				rtcObj.HideWidget = !".$displayWidget.";
				rtcObj.PageTitle = '".$pageTitle."';
				rtcObj.PageImageUrl = '".$pageImageUrl."';
				embedRtcLauncher(rtcObj.RtcUrl);
			}
			(window.RTC = {}, document);
		</script>
		<!--/rtc code-->";
	}
}
/* Puts code on Wordpress pages */
add_action('wp_footer', 'rtc_tracking_code');

/* Runs when plugin is activated */
register_activation_hook(__FILE__, 'rtc_install');

/* Runs on plugin deactivation*/
register_deactivation_hook(__FILE__, 'rtc_uninstall' );

add_action('admin_menu', 'rtc_admin_left_menu');
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'rtc_admin_action_links' ); 
?>
