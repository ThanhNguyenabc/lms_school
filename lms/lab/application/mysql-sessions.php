<?php

class SysSession implements SessionHandlerInterface
{
  private $conn;

  public function open($savePath, $sessionName): bool
  {
    $systemConfig = parse_ini_file("partners/default/system.cfg",true);
	$host = $systemConfig["database"]["host"];
    $username = $systemConfig["database"]["username"];
    $password = $systemConfig["database"]["password"];
    $schema = $systemConfig["database"]["schema"];
    $connection = new PDO("mysql:host=$host;dbname=$schema;charset=utf8", $username, $password, array(PDO::ATTR_PERSISTENT => true));
    $connection->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);

    $this->conn = $connection;
    if($this->conn) return true;
    else return false;
  }
  
  public function close() : bool
  {
    $this->conn = null;
    return true;
  }

  public function read($id): string
  {
		$stmt = $this->conn->prepare('SELECT data FROM sessions WHERE id = :id');
		$stmt->bindParam(':id', $id);

    if($stmt->execute()){
      $row = $stmt->fetch(PDO::FETCH_ASSOC);

      if($row) return $row['data'];
      else return "";

    }
    
    return "";
  }

  public function write($id, $data): bool
  {
    $access = time();

    $stmt = $this->conn->prepare('REPLACE INTO sessions VALUES (:id, :access, :data)');

    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':access', $access);  
    $stmt->bindParam(':data', $data);

    if($stmt->execute()){
      return true;
    }
    
    return false;
  }

  public function destroy($id): bool
  {
    $stmt =$this->conn->prepare('DELETE FROM sessions WHERE id = :id');

    $stmt->bindParam(':id', $id);

    if($stmt->execute())
    {
      return true;
    }

    return false;
  }

  public function gc($max): int|false
  {
    $old = time() - $max;
    
    $stmt = $this->conn->prepare('DELETE FROM sessions WHERE access < :old');
    
    $stmt->bindParam(':old', $old);
    
    if($stmt->execute())
    {
      return true;
    }
    
    return false;
  }
}

$handler = new SysSession();

session_set_save_handler($handler,true);
register_shutdown_function('session_write_close');
?>