<?php 
	$banner = cbsb_get_banner();

	if ($banner && !property_exists($banner, 'error') && $banner->data->enabled) {
		
		$data = $banner->data;
?>

<div id="cbsb-notification-banner" style="background-image:none;text-align:center; background-color: <?php echo $data->background_color; ?>!important;" class="notification-panel">
	<?php if ( $data->has_link ) { ?>
		<a target="_blank" href="<?php echo $data->link; ?>"><span style="color: <?php echo $data->text_color; ?>!important;"><?php echo $data->copy; ?></span></a>
	<?php } else { ?>
		<span style="color: <?php echo $data->text_color; ?>!important;"><?php echo $data->copy; ?></span>
	<?php } ?>
	<a onClick="document.getElementById('cbsb-notification-banner').style.display = 'none'; localStorage.setItem('hide_banner_<?php echo $data->banner_id; ?>', true)" class="close"><i style="color: <?php echo $data->text_color; ?>!important;" class="icon-close"></i></a>
</div>

<script>
	var banner_setting = localStorage.getItem('hide_banner_<?php echo $data->banner_id; ?>');

	if (banner_setting === null) {
		document.getElementById("cbsb-notification-banner").style.display = "block";
	} else {
		document.getElementById("cbsb-notification-banner").style.display = "none";
	}
</script>

<?php
	}
?>