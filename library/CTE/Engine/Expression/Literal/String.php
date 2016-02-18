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
class CTE_Engine_Expression_Literal_String
  extends CTE_Engine_Expression_Literal_Abstract
{
  /**
   * @var string
   */
  private $identifier;

  /**
   * @see CTE_Engine_Expression_Abstract::requestInstance()
   */
  public static function requestInstance(CTE_Engine_Parser_Tag $tagParser)
  {
    if ($tagParser->tokenMatches('/^\'.*?(?<!\\\)\'$/')) {
      return new self($tagParser);
    } else {
      return null;
    }
  }

  /**
   * @param CTE_Engine_Parser_Tag $tagParser
   */
  private function __construct(CTE_Engine_Parser_Tag $tagParser)
  {
    $this->setLineNum($tagParser->getTemplateLineNum());
    $this->identifier = $tagParser->currentToken();
    $tagParser->nextToken();
  }

  /**
   * @see CTE_Engine_Expression_Abstract::getName()
   */
  public function getName()
  {
    return 'string literal';
  }

  /**
   * @see CTE_Engine_Expression_Evaluable_Interface::evaluate()
   * @return string
   */
  public function evaluate($inline)
  {
    return ($inline) ?
             $this->identifier :
             stripslashes(mb_substr($this->identifier, 1, -1));
  }

  /**
   * @see CTE_Engine_Expression_Compilable_Interface::compile()
   */
  public function compile($concatContext = false)
  {
    return $this->assemble($this->identifier);
  }
}
?>