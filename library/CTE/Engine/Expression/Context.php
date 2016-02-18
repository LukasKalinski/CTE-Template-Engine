<?php
/**
 *
 *
 * @package CTE_Engine_Expression
 * @since 2006-08-13
 * @version 2007-02-05
 * @author Lukas Kalinski
 */

/**
 * Helper class for expressions that need a collective treatement of their
 * sub expressions.
 *
 * @access private
 */
class CTE_Engine_Expression_Context
  implements CTE_Engine_Expression_Compilable_Interface
{
  /**
   * @var CTE_Engine_Expression_Abstract
   */
  private $firstExpr = null;

  /**
   * @var CTE_Engine_Expression_Abstract
   */
  private $lastExpr = null;

  /**
   * @var bool
   */
  private $integrated = false;
  
  /**
   * Integrators sorted by their bind strength.
   *
   * @var array($strength:int => array())
   */
  private $integrators = array();

  /**
   * The creator of current context.
   * 
   * @var CTE_Engine_Expression_Context_Creator_Interface
   */
  private $creator;
  
  /**
   * @var string
   */
  private $compiled = null;
  
  /**
   * @param CTE_Engine_Expression_Context_Creator_Interface $creator
   */
  public function __construct(
      CTE_Engine_Expression_Context_Creator_Interface $creator
    )
  {
    $this->creator = $creator;
  }
  
  /**
   * Inserts an expression in current context.
   * 
   * @param CTE_Engine_Expression_Abstract $expr
   * @return void
   */
  public function insert(CTE_Engine_Expression_Abstract $expr)
  {
    $this->assureNotIntegrated();
    
    $expr->registerContext($this);
    if (is_null($this->firstExpr)) {
      $this->firstExpr = $this->lastExpr = $expr;
    } else {
      $this->link($this->lastExpr, $expr);
      $this->lastExpr = $expr;
    }

    // Check if integrator and store it properly if that's the case:
    if ($expr instanceof CTE_Engine_Expression_Context_Integrator_Interface) {
      $priority = $expr->integrationPriority();
      if (!isset($this->integrators[$priority])) {
        $this->integrators[$priority] = array();
      }
      array_push($this->integrators[$priority], $expr);
    }
  }

  /**
   * Makes sure current context isn't integrated.
   * 
   * @throws CTE_Engine_Expression_Exception
   * @return void
   */
  private function assureNotIntegrated()
  {
    if ($this->integrated) {
      throw new CTE_Engine_Expression_Exception(
        'context has already been integrated'
      );
    }
  }
  
  /**
   * Links two expressions together, making them neighbors with eachother. One
   * expression may be null, meaning that the other expression either is a left-
   * or right-side end expression.
   * 
   * @param CTE_Engine_Expression_Abstract $left
   * @param CTE_Engine_Expression_Abstract $right
   * @return void
   */
  private function link(CTE_Engine_Expression_Abstract $left = null,
                        CTE_Engine_Expression_Abstract $right = null)
  {
    if (!is_null($left)) {
      $left->setRightNeighbor($right);
    }

    if (!is_null($right)) {
      $right->setLeftNeighbor($left);
    }
  }

  /**
   * Tries to remove expression $expr from list and then returns $expr.
   *
   * @param CTE_Engine_Expression_Abstract $expr
   * @throws CTE_Engine_Expression_Exception if $expr isn't registered
   *         in this context.
   * @return CTE_Engine_Expression_Abstract
   */
  public function remove(CTE_Engine_Expression_Abstract $expr)
  {
    $this->assureNotIntegrated();
    
    if ($expr->getContext() !== $this) {
      throw new CTE_Engine_Expression_Exception(
        'expression not found in context'
      );
    }

    $left = $expr->getLeftNeighbor();
    $right = $expr->getRightNeighbor();

    // Link both sides of $expr together, supressing $expr:
    $this->link($left, $right);

    // If we're removing the first expression, then firstExpr should point
    // at the removed expression's right neighbor:
    if ($expr === $this->firstExpr) {
      $this->firstExpr = $right;
    }

    // If we're removing the last expression, then lastExpr should point
    // at the removed expression's left neighbor:
    if ($expr === $this->lastExpr) {
      $this->lastExpr = $left;
    }

    $expr->unregisterContext();
    
    return $expr;
  }

  /**
   * Integrates all elements in list. If there is more than one element left
   * when integration is done, an exception will be thrown.
   * 
   * @throws CTE_Engine_Expression_Exception
   * @return void
   */
  private function integrate()
  {
    // Only integrate once:
    if ($this->integrated) {
      return;
    }
    
    // Do integration from highest to lowest strength (if not already
    // integrated):
    krsort($this->integrators);
    foreach ($this->integrators as &$integrators) {
      for ($i=0, $ii=count($integrators); $i<$ii; $i++) {
        $integrators[$i]->integrate();
      }
    }
    
    // Make sure only one expression is left after integration:
    if (!is_null($this->firstExpr->getLeftNeighbor()) ||
        !is_null($this->firstExpr->getRightNeighbor())) {
    
      $misplaced = $this->firstExpr->getLeftNeighbor();
      
      // Reassign $misplaced to right if it was null or was an integrator.
      if (is_null($misplaced) ||
          $misplaced instanceof
            CTE_Engine_Expression_Context_Integrator_Interface) {
        $misplaced = $this->firstExpr->getRightNeighbor();
      }
      
      // Reassign $misplaced to first if it was null or was an integrator.
      if (is_null($misplaced) ||
          $misplaced instanceof
            CTE_Engine_Expression_Context_Integrator_Interface) {
        $misplaced = $this->firstExpr;
      }
      
      $this->creator->handleContextNotIntegratedExpr();
        
      // If creator doesn't throw any exception, we do it instead:
      throw new CTE_Engine_Expression_Exception('context integration failed');
    }
    
    // Unregister context for the remaining expression:
    $this->firstExpr->unregisterContext();
    
    $this->integrated = true;
  }
  
  /**
   * Resolves and returns resulting expression for current context members.
   * 
   * @return CTE_Engine_Expression_Abstract
   */
  public function resolveExpression()
  {
    $this->integrate();
    
    // Now firstExpr should be the only expression left:
    return $this->firstExpr;
  }
  
  /**
   * Integrates and compiles all contained expressions into
   * one result string. Caches the result for future calls.
   *
   * @throws CTE_Engine_Template_Contextual_Exception
   * @return string or null if context is empty.
   */
  public function compile($concatContext = false)
  {
    // Compile if not done before:
    if (is_null($this->compiled)) {
      
      // Return null if context is empty:
      if (is_null($this->firstExpr)) {
        return null;
      }
      
      // Do integration:
      $this->integrate();
      
      // Compile result:
      $this->compiled = '';
      $expr = $this->firstExpr;
      do {
        $this->compiled .= $expr->compile();
      } while(($expr = $expr->getRightNeighbor()) !== null);
    }

    return $this->compiled;
  }
}
?>