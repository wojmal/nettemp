<?php
$root=$_SERVER["DOCUMENT_ROOT"];
require_once($root.'/modules/multiIO/MultiIO.class.php');
$card=new MultiIO();
$kod=$card->parse();
echo $kod;
?>