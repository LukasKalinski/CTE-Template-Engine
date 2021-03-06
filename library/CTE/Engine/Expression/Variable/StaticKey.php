<?php
/**
 *
 *
 * @package CTE_Engine_Expression.Variable
 * @since 2007-01-02
 * @version 2007-01-03
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
class CTE_Engine_Expression_Variable_StaticKey
  implements CTE_Engine_Expression_Variable_Component_Interface
{
  /**
   * @var string
   */
  private $name = null;

  /**
   * @param string $name
   */
  public function __construct($name)
  {
    $this->name = $name;
  }

  /**
   * Returns the name of this static key, without its wrappers.
   * 
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  
  /**
   * @see CTE_Engine_Expression_Variable_Component_Interface::assemble()
   * 
   * $doEval will be ignored since we're always static in this context.
   */
  public function assemble($doEval = false)
  {
    return '['
         . (is_numeric($this->name) ? $this->name :
                                      CTE_Util_String::squote($this->name))
         . ']';
  }
}
?>