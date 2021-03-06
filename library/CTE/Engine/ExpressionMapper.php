<?php
/**
 *
 *
 * @package CTE_Engine
 * @since 2006-08-15
 * @version 2007-01-15
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * Expression Mapping Class
 * 
 * This class automates the mapping of Tag_Parser instances into
 * CTE_Engine_Expression_* objects. The mapped objects must be instances of the
 * {@link CTE_Engine_Expression_Abstract} class, ignoring this will result in an
 * exception being thrown.
 * 
 * @access private
 */
class CTE_Engine_ExpressionMapper
{
  /**
   * @var string
   */
  private static $exprNamespace = 'CTE_Engine_Expression_';

  /**
   * Names of mappable expressions for current mapper.
   * 
   * @var string[]
   */
  private $expressions = array();

  /**
   * Registers an expression name in current mapper.
   * 
   * @param string $exprName
   * @throws CTE_Engine_Exception if expression isn't found.
   * @return void
   */
  public function registerExpression($exprName)
  {
    // Build full expression class name:
    $expr = self::$exprNamespace . $exprName;
    
    try {
      Cylab::loadClass($expr);
    } catch (Cylab_Exception $e) {
      throw new CTE_Engine_Exception("expression handler not found: $exprName");
    }

    array_push($this->expressions, $expr);
  }

  /**
   * Tries to find a expression which is able to handle the current position in
   * the supplied tag parser. If found it will be returned and the required
   * identifier(s) in the parser will be removed.
   *
   * @param CTE_Engine_Parser_Tag $tagParser
   * @throws CTE_Engine_Exception if the mapped object is of wrong type.
   * @return CTE_Engine_Expression_Abstract The mapped expression object.
   * @return null if no expression could be mapped.
   */
  public function mapExpression(CTE_Engine_Parser_Tag $tagParser)
  {
    for ($i=0, $ii=count($this->expressions); $i<$ii; $i++) {
      
      // Make sure the requestInstance method is available:
      if (!is_callable($this->expressions[$i], 'requestInstance')) {
        throw new CTE_Engine_Exception('required object method not found');
      }
      
      // Request instance for a registered expression class names:
      $expr = call_user_func(
        array($this->expressions[$i], 'requestInstance'),
        $tagParser
      );
      
      // Require expression to be an object and be a parser interface instance:
      if (is_object($expr)) {
        if (!$expr instanceof CTE_Engine_Expression_Abstract) {
          throw new CTE_Engine_Exception(
            'mapped object is not an expression abstract instance'
          );
        }
        return $expr;
      }
    }
    
    // No object mapped:
    return null;
  }
}
?>