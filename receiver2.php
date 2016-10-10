<?php
require_once('MultiIO.class.php');
$card=new MultiIO();
$kod=$card->parse();
echo $kod;
?>