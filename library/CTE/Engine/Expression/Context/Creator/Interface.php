<?php
/**
 *
 *
 * @package CTE_Engine_Expression_Context_Creator
 * @since 2007-01-16
 * @version 2007-01-16
 * @copyright Cylab 2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
interface CTE_Engine_Expression_Context_Creator_Interface
{
  /**
   * Handles the context situation when an expression wasn't integrated.
   * 
   * @throws CTE_Engine_Template_Contextual_Exception
   * @return void
   */
  public function handleContextNotIntegratedExpr();
}
?>