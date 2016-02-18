<?php
/**
 *
 *
 * @package CTE_Engine_Expression_Literal
 * @since 2006-08-12
 * @version 2007-02-10
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
abstract class CTE_Engine_Expression_Literal_Abstract
  extends CTE_Engine_Expression_Abstract
    implements CTE_Engine_Expression_Compilable_Interface,
               CTE_Engine_Expression_Evaluable_Interface
{
  /**
   * @see CTE_Engine_Expression_Evaluable_Interface::isStatic()
   */
  public function isStatic()
  {
    return true;
  }
}
?>