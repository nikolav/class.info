<?php namespace info;

use Exception;
use ReflectionClass;
use stdClass;


/**
 * example:
 * 
 *   use info\cls;
 * 
 *   cls::info('Illuminate\Foundation\Application', 
 *     function ($error, $info) { print_r($info); });
 */

class cls {
  
  static protected $asserts = "isAbstract isCloneable isFinal isInstantiable isInterface isInternal isIterateable isTrait isUserDefined";
  static protected $cached  = [];
  
  /**
   * Builds debug tree for a class
   * 
   * @param  string   $cname      required, class to reflect, required
   * @param  Callable $callback   required, error/null as 1st and info{} as 2nd argument
   * @return void                 runs given callback()
   */
  static public function info ( $cname, Callable $callback ) {
    
    $i   = null;
    $err = null;
    
    try {
      
      // caches info here
      $i =  isset(static::$cached[$cname]) 
              ?   static::$cached[$cname]
              :  (static::$cached[$cname] = static::get_info($cname));
      
    } catch (Exception $x) {
      $err = $x;
    }
    
    $callback($err, $i);
  }
  
  static protected function get_info ( $cname ) {
    
    $i  = new stdClass;
    $rc = new ReflectionClass($cname);
    
    $i->{"name"}       = $rc->getName();
    $i->{"extends"}    = $rc->getParentClass() ? $rc->getParentClass()->getName() : "";
    $i->{"implements"} = $rc->getInterfaceNames();
    $i->{"traits"}     = $rc->getTraitNames();
    $i->{"constants"}  = $rc->getConstants();
    
    static::load_properties($i, $rc);
    static::load_methods($i, $rc);
    static::rig_asserts($i, $rc);
    
    $i->{"path"} = $rc->getFileName();
    
    return $i;
  }
  
  static protected function rig_asserts ( $i, $rc ) {
    foreach ( explode(" ", static::$asserts) as $atest )
      $i->{$atest} = $rc->$atest();
  }
  
  static protected function describe_members ( $members ) {
    
    $ls = [];
    
    foreach ($members as $mrefl)
      $ls[static::build_name($mrefl)] = (object) [
        "owner"   => $mrefl->getDeclaringClass()->getName(),
        "comment" => $mrefl->getDocComment(),
      ];
    
    ksort($ls);
    return $ls;
  }
  
  static protected function load_properties ( $i, $rc ) {
    $i->{"properties"} = 
      static::describe_members( $rc->getProperties() );
  }
  
  static protected function load_methods ( $i, $rc ) {
    $i->{"methods"} = 
      static::describe_members( $rc->getMethods() );
  }
  
  static protected function build_name ( $rf ) {
    return ( $rf->isPublic() ? "  +" : ( $rf->isProtected() ? " #" : "-" ) ) 
      . ( $rf->isStatic() ? "::" : " " )
      . $rf->name
      ;
  }
  
}


// eof
