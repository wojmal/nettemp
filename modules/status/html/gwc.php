<style type="text/css">
.temp1 {
	position: absolute;
	color: #fff;
	font-size: 22px;
	z-index:2;
	margin-left: -280px;
	margin-top: 250px;
}

.temp2 {
	position: absolute;
	color: #fff;
	font-size: 22px;
	z-index:2;
	margin-left: -470px;
	margin-top: 250px;
}

.temp3 {
	position: absolute;
	color: #fff;
	font-size: 22px;
	z-index:2;
	margin-left: -230px;
	margin-top: 390px;
}

.temp4 {
	position: absolute;
	color: #fff;
	font-size: 22px;
	z-index:2;
	margin-left: -500px;
	margin-top: 390px;
}



</style>
<span class="belka">&nbsp GWC Geoglik-net<span class="okno"> 
 <span class="content"><img src="media/images/content1.png" width="820" /></span>
<?php					
  $db = new PDO('sqlite:dbf/nettemp.db');
    $sth = $db1->prepare("select * from sensors where id='1'");
    $sth->execute();
    $result = $sth->fetchAll(); 
  foreach ($result as $a) { 	?>		<span class="temp1"><?php echo $a[tmp]; ?></span> <?php } 
      $sth = $db1->prepare("select * from sensors where id='2'");
    $sth->execute();
    $result = $sth->fetchAll(); 
  foreach ($result as $a) { 	?>		<span class="temp2"><?php echo $a[tmp]; ?></span> <?php } 
      $sth = $db1->prepare("select * from sensors where id='3'");
    $sth->execute();
    $result = $sth->fetchAll(); 
  foreach ($result as $a) { 	?>		<span class="temp3"><?php echo $a[tmp]; ?></span> <?php } 
      $sth = $db1->prepare("select * from sensors where id='4'");
    $sth->execute();
    $result = $sth->fetchAll(); 
  foreach ($result as $a) { 	?>		<span class="temp4"><?php echo $a[tmp]; ?></span> <?php } ?>


				