<?php
class MultiIO
{
	private $card_id=-1;
	private $card_name='';
	private $medium='';
	private $device='';
	private $address='';
	private $ports_cnt=0;
	private $sensors_cnt=0;
	private $actors_cnt=0;
	private $access='';
	
	private $registered_status=false;
	private $server_key='';
	private $type='';
	private $ports_type=array();
	private $value=array();
	private $mode='';
	private $table_exists=false;
	
	
	
	private $db='';
	
	
	protected $sql = array(
	'getCardList' => "SELECT * FROM multiIO WHERE adres=:adres",
	'getServerKey' => "SELECT server_key FROM settings",
	'checkTable' => "SELECT name FROM sqlite_master WHERE type='table' AND name= :table",
	'checkCard' => "SELECT * FROM multiIO WHERE address = :address",
	'getSensorList' => "SELECT type FROM types WHERE mode like'%r%'",
	'getActorList' => "SELECT type FROM types WHERE mode like '%w%'",
	'addCard' => "INSERT OR IGNORE INTO multiIO(medium,address,device,ports,sensors, actors, unknown, key, access) VALUES (:medium, :address, :device, :ports,0,0,0,'', :access)",
	'addPort' => "INSERT OR IGNORE INTO multiIO_ports(cardid,port,port_type,value,reference) VALUES(:cardid, :port, :port_type, '', 0)",
	'getInsertedId' => "SELECT last_insert_rowid()",
	'setSensorsCount' => "UPDATE multiIO SET sensors= :sensors, actors= :actors, unknown= :unknown WHERE id=:id",
	'addSensorToNewDevice' => "INSERT OR IGNORE INTO newdev(list) VALUES(:rom)"
	);
	
	protected $sensors = array();
	protected $actors = array();
	
	function __construct()
	{
		//echo 'constructor';
		$root=$_SERVER["DOCUMENT_ROOT"];
		$this->db = new PDO("sqlite:$root/dbf/nettemp.db") or die("cannot open the database");
		$srvk=$this->db->prepare($this->sql['getServerKey']);
		$srvk->execute();
		foreach ($srvk as $row)
		{
			$this->server_key=$row['server_key'];
		}
		$table=$this->db->prepare($this->sql['checkTable']);
		$table->bindValue(':table','multiIO');
		$table->execute();
		$this->table_exists=false;
		foreach ($table as $row)
		{
			if($row['name']=='multiIO')
			{
				$this->table_exists=true;
			}
		}
	}
	
	function parse()
	{
		//echo 'parse';
		if(isset($_GET['key']))
		{
			$key=$_GET['key'];
			if($key!=$this->server_key)
			{
				//echo "Wrong key";
				error_log("multiIO - wrong key");
				return -1;
			}
		}
		else
		{
			echo "No key";
			error_log("multiIO - no key");
			return -2;
		}
		if(isset($_GET['type']))
		{
			$this->type=$_GET['type'];
			if($this->type!='multi')
			{
				echo 'no multi device';
				error_log("multiIO - no multi device");
				return -3;
			}
		}
		if(isset($_GET['mode']))
		{
			$this->mode=$_GET['mode'];
		}
		if(isset($_GET['ports_type']))
		{
			$type_str=$_GET['ports_type'];
			$this->ports_type=preg_split("/;/",$type_str);
			$this->ports_cnt=count($this->ports_type);
			//var_dump($this->ports_type);
		}
		if(isset($_GET['device']))
		{
			$this->device=$_GET['device'];
		}
		if(isset($_GET['ip']) || isset($_GET['address']))
		{
			if(isset($_GET['ip']))
			{
				$this->address=$_GET['ip'];
			}
			else
			{
				$this->address=$_GET['address'];
			}
			if($this->table_exists==true)
			{
				$card=$this->db->prepare($this->sql['checkCard']);
				$card->bindValue(':address',$this->address);
				$card->execute();
				foreach($card as $row)
				{
					if($row['address']==$this->address)
					{
						$this->card_id=$row['id'];
						$this->card_name=$row['name'];
						$this->medium=$row['medium'];
						$this->device=$row['device'];
						$this->address=$row['address'];
						$this->ports_cnt=$row['ports'];
						$this->sensor_cnt=$row['sensors'];
						$this->actors_cnt=$row['actors'];
						$this->access=$row['access'];
						$this->registered_status=true;
					}
				}
			}
		}
		if(isset($_GET['value']))
		{
			$value_str=$_GET['value'];
			$this->value=preg_split("/;/",$value_str);
			//var_dump($this->value);
		}
		if(isset($_GET['medium']))
		{
			$this->medium=$_GET['medium'];
		}
		if(isset($_GET['access']))
		{
			$this->access=$_GET['access'];
		}
		
		if($this->table_exists==false && $this->mode=='register')
		{
			//registering card
			//echo "registering request but no table";
			error_log("multiIO - registering request but no table");
		}
		elseif($this->table_exists==false)
		{
			//echo 'table not exists';
			error_log("multiIO - table not exists");
		}
		elseif ($this->table_exists==true)
		{
			//echo "table exists";
			if($this->mode=='register' && $this->registered_status==false)
			{
				//echo "register request";
				$this->register();
			}
		}
		
	}
	
	
	function register()
	{
		//echo " register";
		//error_log("multiIO - register");
		if($this->address!='' && $this->access!='' && $this->medium!='' && $this->ports_type!='' && count($this->ports_type) > 0)
		{
			$this->register_card();
			$this->register_ports();
		}
		else
		{
			echo "insufficient data to register";
			error_log("multiIO - insufficient data to register");
		}
	}
	
	function register_card()
	{
		$card=$this->db->prepare($this->sql['addCard']);
		$card->bindValue(":medium", $this->medium);
		$card->bindValue(":address",$this->address);
		$card->bindValue(":device", $this->device);
		$card->bindValue(":ports", $this->ports_cnt);
		$card->bindValue(":access",$this->access);
		$card->execute();
		$card=$this->db->prepare($this->sql['getInsertedId']);
		$card->execute();
		foreach($card as $row)
		{
			$this->card_id=intval($row[0],10);
			//echo $row[0];
			echo intval($this->card_id,10);
		}
		//var_dump($this);
	}
	
	function register_ports()
	{
		//first calculate ports sensors/actors
			$sensors=$this->db->prepare($this->sql['getSensorList']);
			$sensors->execute();
			$this->sensors=array();
			$portcnt=1;
			foreach($sensors as $row)
			{
				//var_dump($row);
				array_push($this->sensors,$row[0]);
			}			
			$actors=$this->db->prepare($this->sql['getActorList']);
			$actors->execute();
			$this->actors=array();
			foreach($actors as $row)
			{
				//var_dump($row);
				array_push($this->actors,$row[0]);
			}
			//try compare sensors and actors
			$sensor_cnt=0; //counting sensors
			$actor_cnt=0; //counting actors
			$unknown_cnt=0; //counting not matched -> clasified as sensor
			foreach($this->ports_type as $port)
			{
				$port_type='unknown';
				
				if(in_array($port, $this->sensors))
				{
					$port_type='sensor';
					$sensor_cnt++;
				}
				elseif(in_array($port,$this->actors))
				{
					$port_type='actor';
					$actor_cnt++;
				}
				else
				{
					$sensor_cnt++; //not matched clasified as sensor
					$unknown_cnt++;
				}
				
				$add_port=$this->db->prepare($this->sql['addPort']);
				$add_port->bindValue(':cardid',$this->card_id);
				$add_port->bindValue(':port',$portcnt);
				$add_port->bindValue(':port_type',$port_type);
				$add_port->execute();
				//add sensor to new sensors list
				$newdev=$this->db->prepare($this->sql['addSensorToNewDevice']);
				$newdev->bindValue(":rom",$this->device."_".$this->address."_".$port."_".$portcnt);
				$newdev->execute();
				$portcnt++;
			}
			$card=$this->db->prepare($this->sql['setSensorsCount']);
			$card->bindValue(":sensors", $sensor_cnt);
			$card->bindValue(":actors", $actor_cnt);
			$card->bindValue(":unknown", $unknown_cnt);
			$card->bindValue(":id",$this->card_id);
			$card->execute();
			//var_dump($this->sensors);
			//var_dump($this->actors);
	}
	
	
	
	
	
	
	protected function execute($name, $arguments=array())
	{
		if (!array_key_exists($name, $this->sql)) throw new Exception('Execute of undefined sql ' . $name);
		if (!array_key_exists($name, $this->sth)) $this->sth[$name] = self::$db->prepare($this->sql[$name]);
		foreach ($arguments as $key => $value)
		{
			switch (gettype($value))
			{
				case 'boolean':
					$type = PDO::PARAM_BOOL;
					break;
				case 'integer':
					$type = PDO::PARAM_INT;
					break;
				case 'NULL':
					$type = PDO::PARAM_NULL;
					break;
				default:
					$type = PDO::PARAM_STR;
			}
			$this->sth[$name]->bindValue($key, $value, $type);
		}
		$this->sth[$name]->execute();
		$result = array();
		if (preg_match('/^[^A-Z_]*(SELECT|SHOW)[^A-Z_]/i', $this->sql[$name]))
		{
			while (($object = $this->className ? $this->sth[$name]->fetchObject($this->className) : $this->sth[$name]->fetchObject())) $result[] = $object;
		}
		else
		{
			$object = (object)array('count' => $this->sth[$name]->rowCount());
			if (preg_match('/^[^A-Z_]*(INSERT|REPLACE)[^A-Z_]/i', $this->sql[$name])) $object->id = self::$db->lastInsertId();
			$result[] = $object;
		}
		return $result;
	}
	
	function __call($name, $arguments)
	{
		if (!array_key_exists($name, $this->sql)) throw new Exception('Call to undefined method ' . get_class($this) . '::' . $name . '()');
		return $this->execute($name, array_key_exists(0, $arguments) ? $arguments[0] : array());
	}
}
?>
