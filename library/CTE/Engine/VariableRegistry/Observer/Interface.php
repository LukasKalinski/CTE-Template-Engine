<?php
/**
 *
 *
 * @package CTE_Engine.VariableRegistry.Observer
 * @since 2006-10-08
 * @version 2006-10-08
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
interface CTE_Engine_VariableRegistry_Observer_Interface
{
  /**
   *
   * @param CTE_Engine_VariableRegistry $registry
   * @param int $action
   * @return void
   */
  public function varRegUpdate(CTE_Engine_VariableRegistry $registry, $action);
}
?>
