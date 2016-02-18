<?php
/**
 *
 *
 * @package CTE_Engine_Expression_Compilable
 * @since 2006-08-12
 * @version 2007-02-10
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
interface CTE_Engine_Expression_Compilable_Interface
{
  /**
   * Compiles the expression and returns the resulting string. This method is
   * not expected to cache its output; that should be done by the caller or not
   * at all.
   *
   * Having $concatContext set to true, means we're required to generate a
   * result that is allowed to be concatenated with a string, i.e: 1+2 is not
   * allowed, but (1+2) is. Those expressions that can't be put in a string
   * concatenating context should throw an exception.
   * 
   * @param bool $concatContext
   * @throws CTE_Engine_Template_Contextual_Exception if the compilation cannot
   *         be done for string concatenation context.
   * @return string
   */
  public function compile($concatContext = false);
}
?>