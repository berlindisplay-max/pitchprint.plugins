<?php echo $header; ?>
<div id="content">
  <div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
  </div>
  <div class="box">
    <div class="heading">
      <h1><img src="view/image/module.png" alt="" /> <?php echo $heading_title; ?></h1>
      <div class="buttons"><a onclick="$('#form').submit();" class="button"><?php echo $button_save; ?></a><a href="<?php echo $cancel; ?>" class="button"><?php echo $button_cancel; ?></a></div>
    </div>
    <div class="content">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
			<table class="form">
			  <tr>
				<td><span class="required">*</span> <?php echo $api_label; ?></td>
				<td><input style="width:270px" name="api_value" value="<?php echo $api_key; ?>" ></td>
			  </tr>
			  
			  <tr>
				<td><span class="required">*</span> <?php echo $secret_label; ?></td>
				<td><input style="width:270px" name="secret_value" value="<?php echo $secret_key; ?>" ></td>
			  </tr>
			  
			  <tr>
				<td><?php echo $inline_label; ?></td>
				<td><input style="width:270px" name="pitchprint_inline_value" value="<?php echo $pitchprint_inline_value; ?>" ></td>
			  </tr>
			  
			  <tr>
				<td><?php echo $show_onstartup_label; ?></td>
				<td><select name="pitchprint_showonstartup_value" style="width:270px" class="form-control" >
								<option <?php echo ($pitchprint_showonstartup_value == 'true' ? 'selected="selected"' : '') ?> value="false"><?php echo $disabled_label; ?></option>
								<option <?php echo ($pitchprint_showonstartup_value == 'true' ? 'selected="selected"' : '') ?> value="true"><?php echo $enabled_label; ?></option>
							</select></td>
			  </tr>
			  
			  <tr>
				<td><?php echo $maintain_images_label; ?></td>
				<td><select name="pitchprint_maintain_images_value" style="width:270px" class="form-control" >
								<option <?php echo ($pitchprint_maintain_images_value == 'true' ? 'selected="selected"' : '') ?> value="false"><?php echo $disabled_label; ?></option>
								<option <?php echo ($pitchprint_maintain_images_value == 'true' ? 'selected="selected"' : '') ?> value="true"><?php echo $enabled_label; ?></option>
							</select></td>
			  </tr>
			  
			  <tr>
				<td><?php echo $custom_js_label; ?></td>
				<td><textarea name="pitchprint_custom_js_value" rows="5" style="width:500px" class="form-control" ><?php echo $pitchprint_custom_js_value; ?></textarea></td>
			  </tr>
			  
			  <tr>
				<td><span class="required"> </span></td>
				<td><i><p>Access your <a target="_blank" href="https://pitchprint.net" >PitchPrint Panel</a></p></i></td>
			  </tr>
			</table>
		</form>
		
    </div>
  </div>
</div>
<?php echo $footer; ?>