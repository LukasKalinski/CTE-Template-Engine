<?php
/**
 *
 *
 * @package CTE_Engine_Expression_Context_Integrator
 * @since 2006-08-18
 * @version 2007-01-19
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * Expression Integrator
 * An integrator object either dominates its neighbors, integrating them (one or
 * both) into itself, or is dominated by one of them resulting in a
 * "self-repressive" integration into the neighbor. To make integration more
 * flexible, a theoretical priority is implemented (with class constants
 * representing them). This means that if we have two integrators, 'A' with
 * priority 1 and 'B' with priority 3, 'B' will be integrated before 'A'.
 *
 * @access private
 */
interface CTE_Engine_Expression_Context_Integrator_Interface
{
  /**
   * Integration Priority Categories
   * 
   * Small number means low priority and vice versa.
   */
  const PRIORITY_PARENTHESIS        = 7; // Parenthesis (integrating itself into
                                         // neighbors).
  const PRIORITY_MODIFIER           = 6; // Modifiers
  const PRIORITY_FUNCTION           = 5; // Functions currently used in if tags.
  const PRIORITY_ARITHMETIC         = 4; // +, -, /, ...
  const PRIORITY_COMPARE            = 3; // $foo equals $bar, ...
  const PRIORITY_LOGIC_NOT          = 2; // The logical not operator.
  const PRIORITY_LOGIC              = 1; // $foo and $bar, ...

  /**
   * Performs the integration. Invalid neighbors may
   * cause a Contextual Exception to be thrown.
   *
   * @throws CTE_Engine_Template_Contextual_Exception
   * @return void
   */
  public function integrate();

  /**
   * Returns binding strenght of this integrator.
   *
   * @return int
   */
  public function integrationPriority();
  
  /**
   * Returns true if the integration has been done.
   * 
   * @return bool
   */
  public function isIntegrated();
}
?>