<?php
/**
 *
 *
 * @package CTE_Engine_Expression.Variable.Component
 * @since 2007-01-02
 * @version 2007-01-02
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
interface CTE_Engine_Expression_Variable_Component_Interface
{
  /**
   * Assembles all sub-components of this component and returns a PHP valid
   * substring, meaning that the returned result needs a context to be in.
   * 
   * @param bool $doEval Whether to return dynamic or static result.
   * @return string
   */
  public function assemble($doEval = false);
}
?>
