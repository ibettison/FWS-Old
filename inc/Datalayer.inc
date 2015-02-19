<?php
 class DataLayer {
   var $link;  
   var $errors = array();
   public $debug = false;
   var $database;
 
   function DataLayer( ) {
   }
   
   function connect( $host, $name, $pass, $db ) {
     $link = mysql_connect( $host, $name, $pass );
     if ( ! $link ) {
       $this->setError("Couldn't connect to database server");
       return false;
     }
 
       if ( ! mysql_select_db( $db, $link ) ) { 
       $this->setError("Couldn't select database: $db");
       return false;
     }
	 $database = $db;
     $this->link = $link;
     return true;  
   }
 
   function getError( ) {
     return $this->errors[count($this->errors)-1];
   }
 
   function setError( $str ) {
     array_push( $this->errors, $str );
   }
  
    function _query( $query ) {
      if ( ! $this->link ) {
        $this->setError("No active db connection");
        return false;
      }
      $result = mysql_query( $query, $this->link );
      if ( ! $result )
        $this->setError("error: ".mysql_error());
      return $result;
    }
  
    function setQuery( $query ) {
        if (! $result = $this->_query( $query ) ) 
        return false;  
      return mysql_affected_rows( $this->link );
    }
  
    function getQuery( $query ) {
        if (! $result = $this->_query( $query ) ) 
        return false;  
      $ret = array();
      while ( $row = mysql_fetch_assoc( $result ) )
        $ret[] = $row;
      return $ret;
    }
  
    function getResource( ) {
      return $this->link;
    }
  
    function select( $table, $condition="", $sort="" ) {
          $query = "SELECT * FROM $table";
      $query .= $this->_makeWhereList( $condition );  
      if ( $sort != "" )
        $query .= " order by $sort";
      $this->debug( $query );
          return $this->getQuery( $query, $error );
    }
  
    function insert( $table, $add_array ) {
          $add_array = $this->_quote_vals( $add_array );
          $keys = "(".implode( array_keys( $add_array ), ", ").")";
          $values = "values (".implode( array_values( $add_array ), ", ").")";
          $query = "INSERT INTO $table $keys $values";
		  $this->debug( $query );
          return $this->setQuery( $query );
    }
  
    function update( $table, $update_array, $condition="" ) {
      $update_pairs=array();
      foreach( $update_array as $field=>$val )
        array_push( $update_pairs, "$field=".$this->_quote_val( $val ) );
  
          $query = "UPDATE $table set ";
      $query .= implode( ", ", $update_pairs );
      $query .= $this->_makeWhereList( $condition );  
      $this->debug( $query );
          return $this->setQuery( $query );
    }
  
    function delete( $table, $condition="" ) {
          $query = "DELETE FROM $table";
      $query .= $this->_makeWhereList( $condition );  
      $this->debug( $query );
          return $this->setQuery( $query, $error );
    }
    
   function debug( $msg ) {
    if ( $this->debug )
       echo "<br>$msg";
   }
 
   function _makeWhereList( $condition ) {
     if ( empty( $condition ) )
       return "";
     $retstr = " WHERE ";
     if ( is_array( $condition ) ) {
       $cond_pairs=array();
       foreach( $condition as $field=>$val )
         array_push( $cond_pairs, "$field=".$this->_quote_val( $val ) );
       $retstr .= implode( " and ", $cond_pairs );
     } elseif ( is_string( $condition ) && ! empty( $condition ) )
       $retstr .= $condition;
     return $retstr;
   }
 
   function _quote_val( $val ) {
     if ( is_numeric( $val ) )
       return $val;
     return "'".addslashes($val)."'";
   }
 
   function _quote_vals( $array ) {
     foreach( $array as $key=>$val )
       $ret[$key]=$this->_quote_val( $val );
     return $ret;
   }
   
	function _exists( $table, $database ) {
   		$tables = mysql_list_tables( $database );
		while ( list($temp) = mysql_fetch_array( $tables )) {
			if( $temp == $table ) {
				return true;
			}
		}
		return false;	
   	}
   
   function closedb() {
   	mysql_close( $this->link );
   }	
 }  

