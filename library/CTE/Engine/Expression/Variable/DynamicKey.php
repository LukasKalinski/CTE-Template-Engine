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
class CTE_Engine_Expression_Variable_DynamicKey
  implements CTE_Engine_Expression_Variable_Component_Interface
{
  /**
   * @var CTE_Engine_Expression_Variable
   * @var string
   */
  private $key;

  /**
   * @param CTE_Engine_Expression_Variable $key Variable key
   * @param string $key Section id key
   */
  public function __construct($key)
  {
    // Make sure we're only dealing with variable expressions:
    if (is_object($key) && !$key instanceof CTE_Engine_Expression_Variable) {
      throw new CTE_Engine_Expression_Exception('invalid key type');
    }
    
    $this->key = $key;
  }

  /**
   * @see CTE_Engine_Expression_Variable_Component_Interface::assemble()
   */
  public function assemble($doEval = false)
  {
    $key = $this->key;
    
    if (is_object($key)) {
      if ($doEval) {
        $key = $key->evaluate(false);
        if (!is_numeric($key)) {
          $key = CTE_Util_String::squote($key);
        }
      } else {
        $key = $key->compile();
      }
    }
    
    return "[$key]";
  }
}
?>