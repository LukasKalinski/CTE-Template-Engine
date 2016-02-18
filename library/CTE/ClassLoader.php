<?php
/**
 * Class Loader
 *
 * @package CTE
 * @since 2007-07-04
 * @version 2007-07-04
 * @copyright Cylab 2007
 * @author Lukas Kalinski
 */

/**
 * CTE Class Loading Class
 */
class CTE_ClassLoader
{
  /**
   * Private Constructor
   */
  private function __construct()
  {
  }
  
  /**
   * Autoloader, exits on failure.
   * 
   * @param string $className
   * @return void
   */
  public static function load($className)
  {
    @include str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
    
    // Handle load failure:
    if (!class_exists($className)) {
      if (!interface_exists($className)) {
        exit('class loader error: class '.$className.' not found');
      }
    }
  }
}
?>