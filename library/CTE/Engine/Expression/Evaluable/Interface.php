<?php
/**
 *
 *
 * @package CTE_Engine_Expression
 * @since 2006-09-04
 * @version 2007-02-10
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
interface CTE_Engine_Expression_Evaluable_Interface
{
  /**
   * Evaluates the expression and returns it either as is or prepared for usage
   * inline in the php code.
   * 
   * @param bool $inline
   * @return mixed
   */
  public function evaluate($inline);
  
  /**
   * Returns true if current expression is static, i.e. contains no variables
   * or other expressions that may change value over time.
   * 
   * @return bool
   */
  public function isStatic();
}
?>