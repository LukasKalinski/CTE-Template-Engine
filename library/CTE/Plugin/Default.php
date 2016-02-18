<?php
/**
 *
 *
 * @package CTE_Plugin_Modifier
 * @since 2007-01-23
 * @version 2007-01-24
 * @copyright Cylab 2007
 * @author Lukas Kalinski
 */

/**
 * Modifier Plugin: Default
 * 
 * If $target is set and not empty it will be returned, otherwise 
 * first argument will be returned.
 * 
 * Usage:
 * $foo|default:'bar' -> if $foo not empty: $foo, otherwise: 'bar'
 * 
 * @access public
 */
class CTE_Plugin_Default
  extends CTE_Plugin_Modifier_Abstract
{
  /**
   * @see CTE_Plugin_Modifier_Interface::argFormat()
   */
  public function argFormat()
  {
    return array(null);
  }
  
  /**
   * @see CTE_Plugin_Modifier_Interface::execute()
   */
  public function apply($target, array $args)
  {
    return (!empty($target) ? $target : $args[0]);
  }
}
?>