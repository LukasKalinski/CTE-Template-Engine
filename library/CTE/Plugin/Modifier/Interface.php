<?php
/**
 *
 *
 * @package CTE_Plugin_Modifier
 * @since 2006-07-19
 * @version 2007-01-23
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access public
 */
interface CTE_Plugin_Modifier_Interface
{
  /**
   * Returns array with required arguments and default values for those which
   * are not required.
   * 
   * Usage:
   * array(null, 'foo') means that the first argument is required and the second
   * has its default value set to 'foo'.
   * 
   * @return array
   */
  public function argFormat();
  
  /**
   * Applies the modifier to contents of $target and returns the result. Note
   * that $target won't be affected.
   * 
   * @param string $target
   * @param array $args
   * @return string
   */
  public function apply($target, array $args);
}
?>