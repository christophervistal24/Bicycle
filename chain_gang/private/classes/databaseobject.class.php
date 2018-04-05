<?php
class DatabaseObject{
    protected static $db;
    protected static $table_name = "";
    protected static $columns = [];
    public $errors = [];

  /*star of the active record code*/
  public static function set_database($database)
  {
    self::$db = $database;
  }

  public static function find_by_sql($sql)
  {
      $result = self::$db->query($sql);
      if(!$result)
      {
        exit('Database Query Failed');
      }
      $object_array = [];
      while($record = $result->fetch(PDO::FETCH_ASSOC))
      {
        $object_array[] = static::instantiate($record);
      }

      return $object_array;
  }

  public static function find_all()
  {
      return static::find_by_sql('SELECT * FROM ' . static::$table_name);
  }

  public static function find_by_id($id)
  {
      $obj_array = static::find_by_sql('SELECT * FROM ' . static::$table_name . ' WHERE id = '. htmlspecialchars($id));
      return $obj_array = array_shift($obj_array) ?? false;
  }

  protected static function instantiate($record)
  {
    $object = new static;
    foreach ($record as $property => $value)
    {
        if(property_exists($object,$property))
        {
          $object->$property = $value;
        }
    }
    return $object;
  }

  protected function validate()
  {
    $this->errors = [];
    return $this->errors;
  }

  protected function create()
  {
    $this->validate();
    if(!empty($this->errors)){ return false; }
    $attributes = $this->sanitized_attributes();
    $sql =
    "
    INSERT INTO " . static::$table_name . "(".join(",",array_keys($attributes)).")
    VALUES (".join(",",array_values($attributes)).") ";
    $result = self::$db->query($sql);
    if($result) {
      $this->id = self::$db->lastInsertId();
    }
    return $result;
  }

  protected function update()
  {
    $this->validate();
    if(!empty($this->errors)){ return false; }
    $attributes = $this->sanitized_attributes();
    $attribute_pairs = [];
    foreach ($attributes as $key => $value) {
      $attribute_pairs[] = "{$key}={$value}";
    }
    $sql =
    "
    UPDATE " . static::$table_name . " SET " . join(',',$attribute_pairs) . " WHERE id =" . self::$db->quote($this->id) . " LIMIT 1
    ";
    $result = self::$db->query($sql);
    return $result;
  }

  public function save()
  {
    if(isset($this->id)){
      return $this->update();
    }else{
      return $this->create();
    }
  }

  public function merge_attributes($args = [])
  {
    foreach ($args as $key => $value) {
      if(property_exists($this,$key) and !is_null($value)){
        $this->$key = $value;
      }
    }
  }

  public function attributes()
  {
    $attributes = [];
    foreach (static::$db_columns as $column) {
      if($column == 'id'){ continue; }
      $attributes[$column] = $this->$column;
    }
    return $attributes;
  }

  protected function sanitized_attributes() {
    $sanitized = [];
    foreach($this->attributes() as $key => $value) {
      $sanitized[$key] = self::$db->quote($value);
    }
    return $sanitized;
  }

  public function delete()
  {
    $sql = "DELETE FROM " .static::$table_name . " WHERE id = "  . self::$db->quote($this->id) . " LIMIT 1";
    $result = self::$db->query($sql);
    return $result;
    //after deleting , the instance of the object  will still
    //exists, even though the database record does not.
    //this can be useful , as in:
    // echo $user->first_name . "was deleted";
    //but, for example , we can't call $user->update() after
    //calling user->delete().

  }
  /*end of active record core*/
}