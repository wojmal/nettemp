<?php if(!isset($_SESSION['user'])){ header("Location: denied"); } ?>

<?php $db = new PDO('sqlite:dbf/nettemp.db');
	$sth = $db->prepare("select * from settings ");
	$sth->execute();
	$result = $sth->fetchAll();
	foreach ($result as $a) {
	$kwh=$a["kwh"];
	$gpio=$a["gpio"];
	}
	?>
<p>
<a href="index.php?id=devices&type=scan" ><button class="btn btn-xs btn-default <?php if (!file_exists('tmp/scan')) { echo "btn-danger"; } else { echo $art == 'scan' ? 'active ' : ''; } ?> ">Scan</button></a>
<a href="index.php?id=devices&type=sensors" ><button class="btn btn-xs btn-default <?php echo $art == 'sensors' ? 'active' : ''; ?>">Devices</button></a>
<?php if ( $gpio == 'on' ) { ?>
<a href="index.php?id=devices&type=gpio" ><button class="btn btn-xs btn-default <?php echo $art == 'gpio' ? 'active' : ''; ?>">GPIO</button></a>
	<?php } ?>
<a href="index.php?id=devices&type=snmp" ><button class="btn btn-xs btn-default <?php echo $art == 'snmp' ? 'active' : ''; ?>">SNMP</button></a>
<a href="index.php?id=devices&type=ups" ><button class="btn btn-xs btn-default <?php echo $art == 'ups' ? 'active' : ''; ?>">UPS</button></a>
<a href="index.php?id=devices&type=hosts" ><button class="btn btn-xs btn-default <?php echo $art == 'hosts' ? 'active' : ''; ?>">Host monitoring</button></a>
<a href="index.php?id=devices&type=ipcam" ><button class="btn btn-xs btn-default <?php echo $art == 'ipcam' ? 'active' : ''; ?>">IP Cam</button></a>
<a href="index.php?id=devices&type=usb" ><button class="btn btn-xs btn-default <?php echo $art == 'usb' ? 'active' : ''; ?>">USB/Serial</button></a>
<a href="index.php?id=devices&type=lcd" ><button class="btn btn-xs btn-default <?php echo $art == 'lcd' ? 'active' : ''; ?>">LCD</button></a>
<a href="index.php?id=devices&type=i2c" ><button class="btn btn-xs btn-default <?php echo $art == 'i2c' ? 'active' : ''; ?>">I2C</button></a>
<a href="index.php?id=devices&type=rs485" ><button class="btn btn-xs btn-default <?php echo $art == 'rs485' ? 'active' : ''; ?>">RS485</button></a>
<a href="index.php?id=devices&type=multiIO" ><button class="btn btn-xs btn-default <?php echo $art == 'multiIO' ? 'active' : ''; ?>">MULTI IO</button></a>
</p>


<?php  
switch ($art)
{ 
default: case '$art': include('modules/sensors/html/sensors.php'); break;
case 'scan': include('modules/devices/html/scan.php'); break;
case 'gpio': include('modules/gpio/html/gpio.php'); break;
case 'snmp': include('modules/sensors/snmp/html/snmp.php'); break;
case 'sensors': include('modules/sensors/html/sensors.php'); break;
case 'ups': include('modules/ups/html/ups.php'); break;
case 'hosts': include('modules/hosts/html/hosts.php'); break;
case 'ipcam' : include('modules/ipcam/ipcam.php'); break;
case 'usb' : include('modules/devices/html/usb.php'); break;
case 'i2c' : include('modules/devices/html/i2c.php'); break;
case 'lcd' : include('modules/devices/html/lcd.php'); break;
case 'rs485' : include('modules/devices/html/rs485.php'); break;
case 'multiIO' : include('modules/devices/html/multiIO.php'); break;
}
?>

