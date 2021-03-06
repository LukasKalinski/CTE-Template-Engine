<?php
/**
 *
 *
 * @package CTE_Engine_Expression
 * @since 2006-08-12
 * @version 2007-02-10
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * Abstract class for all expressions.
 *
 * NOTE:
 * - This is not constrained, but the constructor must still be private!
 *
 * @access private
 */
abstract class CTE_Engine_Expression_Abstract
  implements CTE_Engine_Template_Monitor_Interface,
             CTE_Engine_Expression_Compilable_Interface
{
  /**
   * Line number on which expression started.
   *
   * @var int
   */
  private $line_no = -1;

  /**
   * Possible left components for current expression.
   * 
   * @var array
   */
  private $exprCompsLeft = array();
  
  /**
   * Possible right components for current expression.
   * 
   * @var array
   */
  private $exprCompsRight = array();
  
  /**
   * The context of this expression.
   * 
   * @var CTE_Engine_Expression_Context
   */
  protected $context = null;

  /**
   * The left neighbor-expression to this expression.
   * 
   * @var CTE_Engine_Expression_Abstract
   */
  private $leftNeighbor = null;

  /**
   * The right neighbor-expression to this expression.
   * 
   * @var CTE_Engine_Expression_Abstract
   */
  private $rightNeighbor = null;

  /**
   * Returns a new instance of this class if it knows how to handle
   * the string or its 0..n substring found in $input.
   *
   * @param CTE_Engine_Parser_Tag $tagParser
   * @return CTE_Engine_Expression_Abstract
   */
  abstract public static function requestInstance(
    CTE_Engine_Parser_Tag $tagParser
  );
  
  /**
   * Returns an identifying name for this expression. Used for error messages.
   *
   * @return string
   */
  abstract public function getName();

  /**
   * @see CTE_Engine_Template_Monitor_Interface::getTemplateLineNum()
   */
  final public function getTemplateLineNum()
  {
    return $this->line_no;
  }

  /**
   * Assembles the final compile output. This may involve wrapping inside
   * parentheses for example.
   * 
   * @param string $compiled Output from the leaf's compile() method.
   * @return void
   */
  protected function assemble($compiled)
  {
    // Wrap left:
    $left = implode($this->exprCompsLeft);
    
    // Wrap right:
    $right = implode($this->exprCompsRight);
    
    return $left . $compiled . $right;
  }
  
  /**
   * Inserts the expression string $comp before the current expression. That
   * component might for example be a left or right parenthesis (assuming that
   * current expression is a part of an arithmetic expression, for example).
   * 
   * @param CTE_Engine_Expression_Abstract $comp
   * @return void
   */
  public function insertLeftComponent(CTE_Engine_Expression_Abstract $comp)
  {
    array_push($this->exprCompsLeft, $comp->compile());
  }
  
  /**
   * Inserts the expression string $comp after the current expression. That
   * component might for example be a left or right parenthesis (assuming that
   * current expression is a part of an arithmetic expression, for example).
   * 
   * @param CTE_Engine_Expression_Abstract $comp
   * @return void
   */
  public function insertRightComponent(CTE_Engine_Expression_Abstract $comp)
  {
    array_push($this->exprCompsRight, $comp->compile());
  }
  
  /**
   * Detaches (i.e. clears) left-side components for current expression and
   * returns them.
   *
   * @return CTE_Engine_Expression_Abstract[]
   */
  public function detachLeftComponents()
  {
    $comps = $this->exprCompsLeft;
    $this->exprCompsLeft = array();
    
    return $comps;
  }
  
  /**
   * Detaches (i.e. clears) right-side components for current expression and
   * returns them.
   *
   * @return CTE_Engine_Expression_Abstract[]
   */
  public function detachRightComponents()
  {
    $comps = $this->exprCompsRight;
    $this->exprCompsRight = array();
    
    return $comps;
  }

  /**
   * Transfers components from an other expression object into current object.
   * 
   * @param CTE_Engine_Expression_Abstract $from
   * @return void
   */
  public function transferComponents(CTE_Engine_Expression_Abstract $from)
  {
    $this->exprCompsLeft = array_merge(
      $this->exprCompsLeft,
      $from->detachLeftComponents()
    );
    $this->exprCompsRight = array_merge(
      $this->exprCompsRight,
      $from->detachRightComponents()
    );
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
   * Requires current identifier in $tagParser to be equal to
   * $identifier, throws exception if that isn't the case.
   *
   * @param CTE_Engine_Parser_Tag $tagParser
   * @param string $identifier Expected identifier.
   * @throws CTE_Engine_Template_Parsing_Exception
   * @return void
   */
  final protected function requireIdentifier(CTE_Engine_Parser_Tag $tagParser,
                                             $identifier)
  {
    if ($tagParser->currentToken() != $identifier) {
      throw new CTE_Engine_Template_Parsing_Exception(
        $this,
        "expected //$identifier// but got //{$tagParser->currentToken()}//"
      );
    }
  }

  /**
   * Sets start line of current expression.
   *
   * @param int $line
   * @return void
   */
  final protected function setLineNum($line)
  {
    $this->line_no = $line;
  }

  /**
   * Initiates current expression as a context holder (an if tag, for example).
   *
   * @throws CTE_Engine_Expression_Exception if we're not an instance of 
   *         context creator interface.
   * @return void
   */
  final protected function initMainContext()
  {
    if (!$this instanceof CTE_Engine_Expression_Context_Creator_Interface) {
      throw new CTE_Engine_Expression_Exception(
        'failed to initiate context: not a context creator'
      );
    }
    
    $this->context = new CTE_Engine_Expression_Context($this);
  }

  /**
   * Registers a new context for this expression. If we already have a context
   * an exception will be thrown. This should only be called from the Context
   * class while inserting non-context expressions in a context.
   * 
   * Example: An if tag initiates a context for itself and then inserts all
   * operators and operands in that context.
   *
   * @param CTE_Engine_Expression_Context $context
   * @throws CTE_Engine_Expression_Exception
   * @return void
   */
  final public function registerContext(CTE_Engine_Expression_Context $context)
  {
    if (!is_null($this->context)) {
      throw new CTE_Engine_Expression_Exception('context change not allowed');
    }
    $this->context = $context;
  }

  /**
   * Unassociates current expression from its context.
   * 
   * @return void
   */
  public function unregisterContext()
  {
    $this->context = null;
  }
  
  /**
   * Returns the context of this expression.
   * 
   * @return CTE_Engine_Expression_Context
   * @return null if there is no context.
   */
  final public function getContext()
  {
    return $this->context;
  }

  /**
   * Returns the left neighbor of this expression if in a context, otherwise
   * null is returned.
   * 
   * @return CTE_Engine_Expression_Abstract or null
   */
  final public function getLeftNeighbor()
  {
    return $this->leftNeighbor;
  }

  /**
   * Returns the right neighbor of this expression if in a context, otherwise
   * null is returned.
   * 
   * @return CTE_Engine_Expression_Abstract or null
   */
  final public function getRightNeighbor()
  {
    return $this->rightNeighbor;
  }

  /**
   * Sets left neighbor and notifies it about its new neighbor.
   *
   * @param CTE_Engine_Expression_Abstract $expr
   * @return void
   */
  final public function setLeftNeighbor(
      CTE_Engine_Expression_Abstract $expr = null
    )
  {
    $this->leftNeighbor = $expr;
  }

  /**
   * Sets right neighbor and notifies it about its new neighbor.
   *
   * @param CTE_Engine_Expression_Abstract $expr
   * @return void
   */
  final public function setRightNeighbor(
      CTE_Engine_Expression_Abstract $expr = null
    )
  {
    $this->rightNeighbor = $expr;
  }
}
?>