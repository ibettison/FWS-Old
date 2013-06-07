<?php
/* DataLayer.php*/
class dl {
   static public $link;  
   static public $errors = array();
   static public $debug = false;
   static private $mysqli;
   static private $database;
   static public $noRows;
 
   function __construct( ) {
   }
   
   public static function connect( $server, $uId, $pass, $db ) {
		self::$mysqli = new mysqli( $server, $uId, $pass, $db);
		if( self::$mysqli->connect_error) {
			die('Connection Error: '. self::$mysqli->connect_errno. "(".self::$mysqli->connect_error.")");
		}
		self::$database = $db;
		 return true;  
   }
 
   static function getError( ) {
     return self::$errors[count(self::errors)-1];
   }
 
   static function setError( $str ) {
     array_push( self::$errors, $str );
   }
  
    static function _query( $query ) {
      if ( self::$mysqli->connect_errno != 0) {
        self::setError("No active db connection");
        return false;
      }
      $result = self::$mysqli->query( $query );
      if ( ! $result )
        self::setError("error: No result from query");
      return $result;
    }
  
    public static function setQuery( $query ) {
        if (! $result = self::_query( $query ) ) 
        return false;  
      return $result;
    }
  
    public static function getQuery( $query ) {
        if (! $result = self::_query( $query ) ) 
        return false;  
      $ret = array();
	  self::$noRows = 0;
      while ( $row = $result->fetch_assoc() ) {
        $ret[] = $row;
		self::$noRows++;
		}
		$result->free();
      return $ret;
    }
  
    static function getResource( ) {
      return self::$link;
    }
  
    public static function select( $table, $condition="", $sort="" ) {
          $query = "SELECT * FROM $table";
      $query .= self::_makeWhereList( $condition );  
      if ( $sort != "" )
        $query .= " order by $sort";
      self::debug( $query );
          return self::getQuery( $query, self::$errors );
    }
  
    public static function insert( $table, $add_array ) {
          $add_array = self::_quote_vals( $add_array );
          $keys = "(".implode( array_keys( $add_array ), ", ").")";
          $values = "values (".implode( array_values( $add_array ), ", ").")";
          $query = "INSERT INTO $table $keys $values";
		  self::debug( $query );
          return self::setQuery( $query );
    }
  
    public static function update( $table, $update_array, $condition="" ) {
      $update_pairs=array();
      foreach( $update_array as $field=>$val )
        array_push( $update_pairs, "$field=".self::_quote_val( $val ) );
          $query = "UPDATE $table set ";
		 $query .= implode( ", ", $update_pairs );
		 $query .= self::_makeWhereList( $condition );  
		 self::debug( $query );
          return self::setQuery( $query );
    }
  
    public static function delete( $table, $condition="" ) {
          $query = "DELETE FROM $table";
		 $query .= self::_makeWhereList( $condition );  
		 self::debug( $query );
          return self::setQuery( $query, $error );
    }
	
   public static function debug( $msg ) {
     if ( self::$debug )
       print "$msg<br>";
   }

   static function _makeWhereList( $condition ) {
     if ( empty( $condition ) )
		 return "";
     $retstr = " WHERE ";
     if ( is_array( $condition ) ) {
       $cond_pairs=array();
       foreach( $condition as $field=>$val )
         array_push( $cond_pairs, "$field=".self::_quote_val( $val ) );
       $retstr .= implode( " and ", $cond_pairs );
     } elseif ( is_string( $condition ) && ! empty( $condition ) )
       $retstr .= $condition;
     return $retstr;
   }
 
   public static function getId(){
	  return self::$mysqli->insert_id;
   }
   
   static function _quote_val( $val ) {
     if ( is_numeric( $val ) and substr($val,0,1) !== "0" )
       return $val;
     return "'".addslashes($val)."'";
   }
 
   static function _quote_vals( $array ) {
     foreach( $array as $key=>$val )
       $ret[$key]=self::_quote_val( $val );
     return $ret;
   }
   
	static function _exists( $table, $database ) {
   		$tables = mysql_list_tables( $database );
		while ( list($temp) = mysql_fetch_array( $tables )) {
			if( $temp == $table ) {
				return true;
			}
		}
		return false;	
   	}
   
   public static function closedb() {
   	self::$mysqli->close();
   }	
 }
 ?>
