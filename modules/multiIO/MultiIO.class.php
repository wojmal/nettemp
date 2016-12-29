<?php
class MultiIO{
	private $card_id=-1;
	private $card_name='';
	private $medium='';
	private $device='';
	private $address='';
	private $ports_cnt=0;
	private $sensors_cnt=0;
	private $actors_cnt=0;
	private $unknown_cnt=0;
	private $access='';
	private $status='';
	
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
	'checkTable' => "SELECT name FROM sqlite_master WHERE type='table' AND name like '%' || :table || '%'",
	'checkCard' => "SELECT * FROM multiIO WHERE address = :address",
	'getSensorList' => "SELECT type FROM types WHERE mode like'r'",
	'getActorList' => "SELECT type FROM types WHERE mode like '%w%'",
	'addCard' => "INSERT OR IGNORE INTO multiIO(medium,address,device,ports,sensors, actors, unknown, key, access, status) 
				VALUES (:medium, :address, :device, :ports,0,0,0,'', :access, :status)",
	'addPort' => "INSERT OR IGNORE INTO multiIO_ports(card_id,port_nb,port_type,category, value) VALUES(:card_id, :port_nb, :port_type, :category,  0)",
	'getInsertedId' => "SELECT last_insert_rowid()",
	'getCardId' => "SELECT id FROM multiIO WHERE address = :address",
	'setSensorsCount' => "UPDATE multiIO SET sensors= :sensors, actors= :actors, unknown= :unknown WHERE id=:id",
	'addSensorToNewDevice' => "INSERT OR IGNORE INTO newdev(list) VALUES(:rom)",
	'getCardPorts' => "SELECT port_nb, port_type, category FROM multiIO_ports WHERE card_id = :card_id",
	'getCardSensorPorts' => "SELECT port_nb, port_type FROM multiIO_ports WHERE card_id = :card_id AND category != 'actor'",
	'deleteCardPorts' => "DELETE FROM multiIO_ports WHERE card_id = :card_id",
	'deleteCard' => "DELETE FROM multiIO WHERE id = :id",
	'deleteCardFromNew' => "DELETE FROM newdev WHERE list like '%' || :rom || '%'",
	'updateCardStatus' =? "UPDATE multiio SET status=:status WHERE id=:id"
	);
	
	protected $sensors = array();
	protected $actors = array();
	
	function __construct(){
		//echo 'constructor';
		$root=$_SERVER["DOCUMENT_ROOT"];
		$this->db = new PDO("sqlite:$root/dbf/nettemp.db") or die("cannot open the database");
		$srvk=$this->db->prepare($this->sql['getServerKey']);
		$srvk->execute();
		foreach ($srvk as $row){
			$this->server_key=$row['server_key'];
		}
		$table=$this->db->prepare($this->sql['checkTable']);
		$table->bindValue(':table','multi');
		$table->execute();
		$this->table_exists=false;
		$table_multiIO=false;
		$table_multiIO_ports=false;
		foreach ($table as $row){
			if($row['name']=='multiIO'){
				$table_multiIO=true;
			}
			elseif($row['name']=='multiIO_ports'){
				$table_multiIO_ports=true;
			}
		}
		if ($table_multiIO && $table_multiIO_ports){
			$this->table_exists=true;
		}
	}
	
	function parse(){
		//echo 'parse';
		if(isset($_GET['key'])){
			$key=$_GET['key'];
			if($key!=$this->server_key){
				//echo "Wrong key";
				error_log("multiIO - wrong key");
				return 0x101;
			}
		}
		else{
			echo "No key";
			error_log("multiIO - no key");
			return 0x102;
		}
		if(isset($_GET['type'])){
			$this->type=$_GET['type'];
			if($this->type!='multi'){
				echo 'no multi device';
				error_log("multiIO - no multi device");
				return 0x103;
			}
		}
		if(isset($_GET['mode'])){ //accepted mode: register, update
			$this->mode=$_GET['mode'];
		}
		if(isset($_GET['ports'])){ //types for ports used in register mode: portse="
			$type_str=$_GET['ports'];
			$this->ports_type=preg_split("/;/",$type_str);
			$this->ports_cnt=count($this->ports_type);
			//var_dump($this->ports_type);
		}
		if(isset($_GET['device'])){
			$this->device=$_GET['device'];
		}
		if(isset($_GET['ip']) || isset($_GET['address'])){
			if(isset($_GET['ip'])){
				$this->address=$_GET['ip'];
			}
			else{
				$this->address=$_GET['address'];
			}
			if($this->table_exists==true){
				$card=$this->db->prepare($this->sql['checkCard']);
				$card->bindValue(':address',$this->address);
				$card->execute();
				foreach($card as $row){
					if($row['address']==$this->address){
						$this->card_id=$row['id'];
						$this->card_name=$row['name'];
						$this->medium=$row['medium'];
						$this->device=$row['device'];
						$this->address=$row['address'];
						$this->ports_cnt=$row['ports'];
						$this->sensor_cnt=$row['sensors'];
						$this->actors_cnt=$row['actors'];
						$this->access=$row['access'];
						$this->status=$row['status'];
						if ($this->status ==  "registered"){
							$this->registered_status=true;
						}
					}
				}
			}
		}
		if(isset($_GET['value'])){
			$value_str=$_GET['value'];
			$this->value=preg_split("/;/",$value_str);
			//var_dump($this->value);
		}
		if(isset($_GET['medium'])){
			$this->medium=$_GET['medium'];
		}
		if(isset($_GET['access'])){
			$this->access=$_GET['access'];
		}
		if($this->table_exists==false){
			//echo 'table not exists';
			if ($this->mode=='register'){
				error_log("multiIO - register request but table doesn't exists");
			}
			else{
				error_log("multiIO - table doesn't exists");
			}
			return 0x104;
		}
		elseif ($this->table_exists==true){
			//echo "table exists";
			if($this->mode=='register'){
				//echo "register request";
				if ($this->registered_status==false && $this->card_id == '-1'){
					$this->register(); //@add to new devices
				}
				elseif($this->registered_status==false && $this->card_id != '-1'){
					//if card has status "new device" we delete and reregister card and ports
					$this->delete_card();
					$this->register();
					error_log("multiIO - card reregistered and added to new devices");
					//return 0x105; //card already in new devices
				}
				elseif($this->registered_status==true){
					error_log("multiIO - card already registered");
					//in future we need compare types of ports type when registering required mode
					return 0x106;//card already registered
				}
			}
			elseif($this->mode == 'update'){
				//TODO
				//call update method
				if ($this->registered_status==false && $this->card_id == '-1'){
					$ecode=0x108;
					return $ecode." register required";
				}
				error_log("multiIO - ToDo!!!");
			}
		}
	}
	
	
	function register(){
		//echo " register";
		//error_log("multiIO - register");
		if($this->address!='' && $this->access!='' && $this->medium!='' && $this->ports_type!='' && count($this->ports_type) > 0){
			$this->register_card();//add card to multiIO table
			$this->register_ports();//add ports to multiIO_ports table
			$this->add_new_devices();
		}
		else{
			echo "insufficient data to register";
			error_log("multiIO - insufficient data to register");
		}
	}
	
	function delete_card(){
		//get card id 
		$card=$this->db->prepare($this->sql['getCardId']);
		$card->bindValue(":address",$this->address);
		$card->execute();
		$cardid=$card->fetch();
		//delete ports for the card
		$delCardPorts=$this->db->prepare($this->sql['deleteCardPorts']);
		$delCardPorts->bindValue(":card_id", $cardid['id']);
		$delCardPorts->execute();
		//delete card
		$delCard=$this->db->prepare($this->sql['deleteCard']);
		$delCard->bindValue(":id",$cardid['id']);
		$delCard->execute();
		$deleteFromNew=$this->db->prepare($this->sql['deleteCardFromNew']);
		$deleteFromNew->bindValue(":rom", $this->device."_".$this->address."_multi_");
		$deleteFromNew->execute();
	}
	
	function register_card(){
		//add card to multiIO table
		$card=$this->db->prepare($this->sql['addCard']);
		$card->bindValue(":medium", $this->medium);
		$card->bindValue(":address",$this->address);
		$card->bindValue(":device", $this->device);
		$card->bindValue(":ports", $this->ports_cnt);
		$card->bindValue(":access",$this->access);
		$card->bindValue(":status","new");
		$card->execute();
		$card=$this->db->prepare($this->sql['getInsertedId']);
		/* alternative method to get id
		* $card=$this->db->prepare($this->sql['getCardId']);
		* $card->bindValue(":address",$this->address);
		*/
		$card->execute();
		foreach($card as $row){
			$this->card_id=intval($row[0],10);
			//echo $row[0];
			if (intval($this->card_id,10) == 0){
				error_log("multiIO - card not added to new devices");
				return 0x107;
			}
			echo intval($this->card_id,10);
		}
		//var_dump($this);
	}
	
	function register_ports(){
		//first calculate ports sensors/actors
			$sensors=$this->db->prepare($this->sql['getSensorList']);
			$sensors->execute();
			$this->sensors=array();
			$portcnt=1;
			foreach($sensors as $row){
				//var_dump($row);
				array_push($this->sensors,$row[0]);
			}			
			$actors=$this->db->prepare($this->sql['getActorList']);
			$actors->execute();
			$this->actors=array();
			foreach($actors as $row){
				//var_dump($row);
				array_push($this->actors,$row[0]);
			}
			//try compare sensors and actors
			$this->sensor_cnt=0; //counting sensors
			$this->actor_cnt=0; //counting actors
			$this->unknown_cnt=0; //counting not matched -> clasified as sensor
			foreach($this->ports_type as $ports){
				$port_type='unknown';
				$port=preg_split("/:/",$ports);
				
				if(in_array($port[1], $this->sensors)){
					$port_type='sensor';
					$this->sensor_cnt++;
				}
				elseif(in_array($port[1],$this->actors)){
					$port_type='actor';
					$this->actor_cnt++;
				}
				else{
					$this->sensor_cnt++; //not matched clasified as sensor
					$this->unknown_cnt++;
				}
				//add ports to multiIO_ports
				$add_port=$this->db->prepare($this->sql['addPort']);
				$add_port->bindValue(':card_id',$this->card_id);
				$add_port->bindValue(':port_nb',$portcnt);
				$add_port->bindValue(':port_type',$port[1]);
				$add_port->bindValue(':category',$port_type);
				$add_port->execute();

				$portcnt++;
			}
			$card=$this->db->prepare($this->sql['setSensorsCount']);
			$card->bindValue(":sensors", $this->sensor_cnt);
			$card->bindValue(":actors", $this->actor_cnt);
			$card->bindValue(":unknown", $this->unknown_cnt);
			$card->bindValue(":id",$this->card_id);
			$card->execute();
			//ToDo
			//update info about sensors/actors/uknown
			//var_dump($this->sensors);
			//var_dump($this->actors);
	}
	
	function titlePortsInfo($address){
		$cardid=$this->getCardId($address);
		$cardid=$card->fetch();
		$ports=$this->db->prepare($this->sql['getCardPorts']);
		$ports->bindValue(":card_id", $cardid['id']);
		$ports->execute();
		$title='';
		foreach ($ports as $port){
			$title .= "&#13 ".$port['port_nb'].":".$port['port_type']." ".$port['category'];
		}
		return $title;
		
	}
	
	function add_new_devices(){
		$newdev=$this->db->prepare($this->sql['addSensorToNewDevice']);
		$newdev->bindValue(":rom",$this->device."_".$this->address."_multi_(".$this->sensor_cnt."-".$this->actor_cnt."-".$this->unknown_cnt.")");
		$newdev->execute();
	}
	
	function confirm_new_devices(){
		if(isset($_GET['id_rom_new'])){
			$card_name_split=preg_split("/_/",$_GET['id_rom_new']);
			for ($i=0;i<sizeof($card_name_split);i++){
				if($card_name_split[$i]=='multi' && $i>0){
					$this->card_name=$card_name_split[$i-1];
				}
			}
			if ($this->card_name != ""){
				$card_id=$this->getCardId($this->card_name);
				$cardid=$card_id->fetch();
				if ($cardid != ""){
					$sensorPorts=$this->db->prepare($this->sql['getCardSensorPorts']);
					
				}
			}
		}
		
		
	}
	
	protected function getCardId($address){
		$card=$this->db->prepare($this->sql['getCardId']);
		$card->bindValue(":address",$address);
		$card->execute();
		return $card;
	}
	
	
	protected function execute($name, $arguments=array()){
		if (!array_key_exists($name, $this->sql)) throw new Exception('Execute of undefined sql ' . $name);
		if (!array_key_exists($name, $this->sth)) $this->sth[$name] = self::$db->prepare($this->sql[$name]);
		foreach ($arguments as $key => $value){
			switch (gettype($value)){
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
		if (preg_match('/^[^A-Z_]*(SELECT|SHOW)[^A-Z_]/i', $this->sql[$name])){
			while (($object = $this->className ? $this->sth[$name]->fetchObject($this->className) : $this->sth[$name]->fetchObject())) $result[] = $object;
		}
		else{
			$object = (object)array('count' => $this->sth[$name]->rowCount());
			if (preg_match('/^[^A-Z_]*(INSERT|REPLACE)[^A-Z_]/i', $this->sql[$name])) $object->id = self::$db->lastInsertId();
			$result[] = $object;
		}
		return $result;
	}
	
	function __call($name, $arguments){
		if (!array_key_exists($name, $this->sql)) throw new Exception('Call to undefined method ' . get_class($this) . '::' . $name . '()');
		return $this->execute($name, array_key_exists(0, $arguments) ? $arguments[0] : array());
	}
}
?>
