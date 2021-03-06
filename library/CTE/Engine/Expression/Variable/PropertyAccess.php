<?php
/**
 *
 *
 * @package CTE_Engine_Expression_Variable
 * @since 2007-01-02
 * @version 2007-02-10
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
class CTE_Engine_Expression_Variable_PropertyAccess
    implements CTE_Engine_Expression_Variable_Component_Interface
{
  /**
   * @var string
   */
  private $propertyName;

  /**
   * Arguments of this property. Null means this isn't a method property and
   * an empty array means zero arguments.
   * 
   * @var CTE_Engine_Expression_Abstract[]
   */
  private $propertyArgs = null;

  /**
   * @param string $name
   * @param CTE_Engine_Expression_Abstract[] $args
   */
  public function __construct($name, array $args = null)
  {
    $this->propertyName = $name;
    $this->propertyArgs = $args;
  }

  /**
   * Returns true if this property is a method access.
   *
   * @return bool
   */
  public function isMethod()
  {
    return !is_null($this->propertyArgs);
  }
  
  /**
   * @see CTE_Engine_Expression_Variable_Component_Interface::assemble()
   * 
   * Assembles all components and returns a PHP valid property access string.
   * 
   * @param bool $doEval
   * @return string
   */
  public function assemble($doEval = false)
  {
    if (!$this->isMethod()) {
      return "->{$this->propertyName}";
    }
    
    $compiledArgs = array();
    for ($i=0, $ii=count($this->propertyArgs); $i<$ii; $i++) {
      // Make sure we're handling an expression object (failure is blamed
      // on the system):
      if (!$this->propertyArgs[$i] instanceof CTE_Engine_Expression_Abstract) {
        throw new CTE_Engine_Expression_Exception(
          'argument was not an expression object'
        );
      }
      
      if ($doEval) {
        $compiledArgs[] = $this->propertyArgs[$i]->evaluate(true);
      } else {
        $compiledArgs[] = $this->propertyArgs[$i]->compile();
      }
    }
    
    return '->' . $this->propertyName . '(' . implode(',', $compiledArgs) . ')';
  }
}
?>