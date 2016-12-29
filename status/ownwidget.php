<?php
$root=$_SERVER["DOCUMENT_ROOT"];
if(file_exists("$root/tmp/ownwidget".$ow.".php")) {
	if ( '0' != filesize("$root/tmp/ownwidget".$ow.".php") )
	{ ?>
	<div class="grid-item <?php echo "ow".$ow; ?>">
   	<div class="panel panel-default">
		<?php include("$root/tmp/ownwidget".$ow.".php"); ?>
    	</div>
	</div>
	<?php 
	} 
}
?>
