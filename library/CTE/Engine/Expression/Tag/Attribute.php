<?php
/**
 * @deprecated
 *
 * @package CTE_Engine_Expression_Tag
 * @since 2007-01-09
 * @version 2007-01-22
 * @copyright Cylab 2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 * 
 * @deprecated on 5/2-2007 ... unnecessary complexity ...
 */
class CTE_Engine_Expression_Tag_Attribute
  implements CTE_Engine_Expression_Context_Creator_Interface,
             CTE_Engine_Template_Monitor_Interface
{
  /**
   * Expression mapper for tag attributes.
   * 
   * @var CTE_Engine_ExpressionMapper
   */
  private static $exprMapper;

  /**
   * @var int
   */
  private $lineNum;
  
  /**
   * The name of current attribute. This will always be lowercase.
   * 
   * @var string
   */
  private $attrName;
  
  /**
   * The value of this attribute.
   * 
   * @var CTE_Engine_Expression_Abstract
   */
  private $attrValue;
  
  /**
   * Returns a new instance of this class if it thinks that it
   * can handle whatever the $tagParser is pointing at.
   * 
   * @see CTE_Engine_Expression_Abstract::requestInstance()
   */
  public static function requestInstance(CTE_Engine_Parser_Tag $tagParser)
  {
    $name = $tagParser->currentToken();
    
    if (preg_match('/^[a-z_][a-z0-9_]*$/i', $name)) {
      // Make tag parser remember this position, in case we
      // would like to roll back our parsing steps:
      $tagParser->savePosition();
      
      // Return null if we have no more tokens left:
      if (!$tagParser->nextToken()) {
        return null;
      }
      
      // Check if current token is an equality sign (meaning it's an attribute):
      if ($tagParser->currentToken() == '=') {
        $tagParser->discardSavedPosition();
        return new self($name, $tagParser);
      } else {
        $tagParser->restoreSavedPosition();
        return null;
      }
    } else {
      return null;
    }
  }

  /**
   * 
   * @param string $name The name of the attribute.
   * @param CTE_Engine_Parser_Tag $tagParser
   */
  private function __construct($name, CTE_Engine_Parser_Tag $tagParser)
  {
    $this->lineNum = $tagParser->getTemplateLineNum();
    
    $this->attrName = $name;
    
    // Leave the equality sign behind:
    $this->nextIdentifier($tagParser);
    
    // Initiate attribute expression mapper if not set:
    if (!isset(self::$exprMapper)) {
      self::$exprMapper = new CTE_Engine_ExpressionMapper();
      self::$exprMapper->registerExpression('Literal_Numeric');
      self::$exprMapper->registerExpression('Literal_Bool');
      self::$exprMapper->registerExpression('Literal_String');
      self::$exprMapper->registerExpression('Literal_ParseableString');
      self::$exprMapper->registerExpression('Variable');
      self::$exprMapper->registerExpression('Arithmetic');
    }
    
    // Map first expression:
    $firstExpr = self::$exprMapper->mapExpression($tagParser);
    
    // Try to map complex expression (i.e. an arithmetic or something):
    $expr = null;
    if ($tagParser->valid()) {
      $expr = self::$exprMapper->mapExpression($tagParser);
    }
    
    if (!is_null($expr)) {
      // We have a complex value expression.
      
      // Setup a context for the expression:
      $context = new CTE_Engine_Expression_Context($this);
      $context->insert($firstExpr);
      
      // Map remaining complex expression components:
      do {
        $context->insert($expr);
        
        if (!$tagParser->valid()) {
          break;
        }
        
        $expr = self::$exprMapper->mapExpression($tagParser);
        
      } while (!is_null($expr));
      
      $this->attrValue = $context->resolveExpression();
    } else {
      $this->attrValue = $firstExpr;
    }
    
    // Handle invalid expression:
    if (is_null($this->attrValue)) {
      throw new CTE_Engine_Template_Contextual_Exception(
        $this,
        "invalid value for {$this->attrName}-attribute"
      );
    }
  }
  
  /**
   * Tries to move on to the next identifier. Throws parsing exception if 
   * $require is set to true and there aren't any identifiers left.
   * 
   * @param CTE_Engine_Parser_Tag $tagParser
   * @param bool $require Whether to require a next identifier or not.
   * @throws CTE_Engine_Template_Parsing_Exception if there are no more
   *         identifiers.
   * @return void
   */
  final protected function nextIdentifier(CTE_Engine_Parser_Tag $tagParser,
                                          $require = true)
  {
    // If we haven't any more tokens and we required them, throw exception:
    // (Note that $require must be the second condition here.)
    if (!$tagParser->nextToken() && $require) {
      throw new CTE_Engine_Template_Parsing_Exception(
        $this,
        'unexpected end of expression'
      );
    }
  }
  
  /**
   * Returns true if the value of the current attribute is an instance of any
   * of the expressions specified in $exprNames.
   * 
   * @param aArray $exprNames Names of valid expressions.
   * @return bool
   */
  public function isInstanceOf(array $exprNames)
  {
    return isset($exprNames[get_class($this->attrValue)]);
  }
  
  /**
   * Handles the context situation when an expression wasn't integrated.
   * 
   * @see CTE_Engine_Expression_Context_Creator_Interface::
   *      handleContextNotIntegratedExpr()
   * @throws CTE_Engine_Template_Contextual_Exception
   * @return void
   */
  public function handleContextNotIntegratedExpr()
  {
    throw new CTE_Engine_Template_Contextual_Exception(
      $this,
      'syntax error'
    );
  }
  
  /**
   * @see CTE_Engine_Template_Monitor_Interface::getTemplateLineNum()
   */
  public function getTemplateLineNum()
  {
    return $this->lineNum;
  }

  /**
   * Returns the name of this attribute.
   * 
   * @return string
   */
  public function getAttrName()
  {
    return $this->attrName;
  }
  
  /**
   * Returns the value of this attribute.
   * 
   * @return CTE_Engine_Expression_Abstract
   */
  public function getAttrValue()
  {
    return $this->attrValue;
  }
}
?>