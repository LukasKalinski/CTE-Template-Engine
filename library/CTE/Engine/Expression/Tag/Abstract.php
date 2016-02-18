<?php
/**
 *
 *
 * @package CTE_Engine_Expression_Tag
 * @since 2006-08-12
 * @version 2007-02-05
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
abstract class CTE_Engine_Expression_Tag_Abstract
  extends CTE_Engine_Expression_Abstract
    implements CTE_Engine_Expression_Compilable_Interface,
               CTE_Engine_Expression_Context_Creator_Interface
{
  /**
   * Expression mapper for tag attributes.
   * 
   * @var CTE_Engine_ExpressionMapper
   */
  private static $attrValMapper;
  
  /**
   * Tokenizes $tagParser into the following identifiers:
   * 
   * - Variable property access: ->.
   * - Any name string starting with a letter or an underscore.
   * - Any number, integer or float.
   * - Any single quoted string: 'foobar'.
   * - Any double quoted string: "foobar".
   * - Any single character.
   * 
   * @param CTE_Engine_Parser_Tag $tagParser
   * @param string $additionalRegex
   * @return void
   */
  final protected static function tokenizeTag(CTE_Engine_Parser_Tag $tagParser,
                                              $additionalRegex = null)
  {
    if (!is_null($additionalRegex)) {
      $additionalRegex = "(?:$additionalRegex)|";
    }
    
    $tagParser->tokenize('->|'
                        .'[a-zA-Z_][a-zA-Z0-9_]*|'
                        .'[0-9]+(?:\.[0-9]+)?|'
                        .'\'.*?(?<!\\\)\'|'
                        .'".*?(?<!\\\)"|'
                        . $additionalRegex
                        .'.');
  }
  
  /**
   * Sets up attribute value mapper if not already set.
   * 
   * @return void
   */
  private static function setupArgMapping()
  {
    if (!isset(self::$attrValMapper)) {
      self::$attrValMapper = new CTE_Engine_ExpressionMapper();
      self::$attrValMapper->registerExpression('Literal_Numeric');
      self::$attrValMapper->registerExpression('Literal_Bool');
      self::$attrValMapper->registerExpression('Literal_String');
      self::$attrValMapper->registerExpression('Literal_ParseableString');
      self::$attrValMapper->registerExpression('Variable');
      self::$attrValMapper->registerExpression('Arithmetic');
    }
  }
  
  /**
   * Parses all tag attributes and returns them in an associative array,
   * having attribute name as array key and attribute value as array value.
   * 
   * @param CTE_Engine_Parser_Tag $tagParser
   * @throws CTE_Engine_Template_Parsing_Exception if an attribute occurs more 
   *         than once.
   * @return array (attr_name => attr_value)
   */
  final protected function parseAttrs(CTE_Engine_Parser_Tag $tagParser)
  {
    self::setupArgMapping();
    
    $attrs = array();
    while ($tagParser->valid()) {
      
      // Fetch what we expect to be an attribute name:
      $attr_name = $tagParser->currentToken();
      
      // Break if name isn't a valid attribute name:
      if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $attr_name)) {
        break;
      }
      
      $tagParser->savePosition();
      
      // Restore tag parser position and break if we're out of tokens:
      if (!$tagParser->nextToken()) {
        $tagParser->restoreSavedPosition();
        break;
      }
      
      // Restore tag parser position and break if we're missing attribute
      // assignment operator:
      if ($tagParser->currentToken() != '=') {
        $tagParser->restoreSavedPosition();
        break;
      }
      
      /* From now on we're requiring a valid attribute to exist. */
      
      // Leave assignment operator behind and require another one:
      $this->nextIdentifier($tagParser);
      
      // 2007-02-05
      // @optimize if it's too heavy to create new context objects for every
      //           single attribute ...........................................  ###
      
      $context = new CTE_Engine_Expression_Context($this);
      
      do {
        $expr = self::$attrValMapper->mapExpression($tagParser);
        
        if (is_null($expr)) {
          break;
        }
        
        $context->insert($expr);
      } while ($tagParser->valid());
      
      // Try to resolve attribute value from context:
      $attr_value = $context->resolveExpression();
      
      // Handleinvalid expression:
      if (is_null($attr_value)) {
        throw new CTE_Engine_Template_Contextual_Exception(
          $this,
          "invalid value for $attr_name-attribute"
        );
      }
      
      // Make sure the attribute haven't been set before:
      if (isset($attrs[$attr_name])) {
        throw new CTE_Engine_Template_Contextual_Exception(
          $this,
          "$attr_name-attribute already exists"
        );
      }
      
      $attrs[$attr_name] = $attr_value;
    }
    
    return $attrs;
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
   * Returns $code wrapped in php tags.
   * 
   * @param string $code The PHP code to wrap.
   * @return string
   */
  final protected function wrapPhpCode($code)
  {
    return "<?php $code?>";
  }
  
  /**
   * @see CTE_Engine_Expression_Compilable_Interface::compile()
   */
  public function compile($concatContext = false)
  {
    if ($concatContext) {
      throw new CTE_Engine_Template_Contextual_Exception(
        $this,
        'invalid context, cannot exist inline'
      );
    }
  }
}
?>