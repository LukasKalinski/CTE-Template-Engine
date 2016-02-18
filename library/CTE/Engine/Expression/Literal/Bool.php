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
 * Literal representations of boolean values.
 *
 * @access private
 */
class CTE_Engine_Expression_Literal_Bool
  extends CTE_Engine_Expression_Literal_Abstract
{
  /**
   * @var bool
   */
  private $value;

  /**
   * @see CTE_Engine_Expression_Abstract::requestInstance()
   */
  public static function requestInstance(CTE_Engine_Parser_Tag $tagParser)
  {
    $line = $tagParser->getTemplateLineNum();
    
    switch ($tagParser->currentToken()) {
      
      case 'on':
      case 'true':
      case 'yes':
      case '1':
        $tagParser->nextToken();
        return new self(true, $line);
      
      case 'off':
      case 'false':
      case 'no':
      case '0':
        $tagParser->nextToken();
        return new self(false, $line);
      
      default:
        return null;
    }
  }

  /**
   * @param bool $value
   * @param int $line
   */
  private function __construct($value, $line)
  {
    $this->setLineNum($line);
    $this->value = $value;
  }

  /**
   * @see CTE_Engine_Expression_Abstract::getName()
   */
  public function getName()
  {
    return 'boolean literal';
  }

  /**
   * @see CTE_Engine_Expression_Evaluable_Interface::evaluate()
   * @return bool
   */
  public function evaluate($inline)
  {
    return ($inline) ? ($this->value ? 'true': 'false') : $this->value;
  }

  /**
   * @see CTE_Engine_Expression_Compilable_Interface::compile()
   */
  public function compile($concatContext = false)
  {
    return $this->assemble($this->evaluate(true));
  }
}
?>