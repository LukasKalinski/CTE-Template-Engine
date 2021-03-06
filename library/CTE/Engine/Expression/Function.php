<?php
/**
 *
 *
 * @package CTE_Engine_Expression
 * @since 2006-08-28
 * @version 2007-01-23
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
class CTE_Engine_Expression_Function
  extends CTE_Engine_Expression_Context_Integrator_Abstract
    implements CTE_Engine_Expression_Compilable_Interface
{
  /**#@+
   * @access private
   * @var int
   */
  const TYPE_VARIABLE = 1;
  const TYPE_LITERAL_NUMERIC = 2;
  const TYPE_LITERAL_STRING = 4;
  /*#@-*/

  /**
   * Function translation table (a function template).
   * 
   * The order is preserved!
   *
   * Operand types are specified combining TYPE_* constants, i.e.
   * a variable and a literal numeric are represented by $3.
   * R and L indicate which side of the function operator the
   * operand appears.
   *
   * @var aArray
   */
  private static $functions = array(
    /**
     * PHP-like ...
     * (The negation case is covered by LogicalNot expression.)
     */
    'isset' => array('isset(&R1)'),
    'empty' => array('empty(&R1)'),
    'strlen' => array('strlen(&R5)'),
    'count' => array('count(&R1)'),
    
    /**
     * English-like (short) ...
     */
    'set' => array('isset(&L1)'),
    'length' => array(
      'strlen(&L5)',
      'of' => array('strlen(&R5)')
    ),
    'size' => array(
      'count(&L1)',
      'of' => array('count(&R2)')
    ),
    'div' => array(
      'by' => array('&L3%&R3==0')
    ),
    'empty' => array('empty(&L1)'),
    'even' => array('&L3%2==0'),
    'not' => array(
      'set' => array('!isset(&L1)'),
      'empty' => array('!empty(&L1)'),
      'even' => array('&L3%2!=0'),
      'div' => array(
        'by' => array('&L3%&R3!=0')
      )
    ),
    
    /**
     * English-like (long) ...
     */
    'is' => array(
      'set' => array('isset(&L1)'),
      'empty' => array('empty(&L1)'),
      'even' => array('&L3%2==0'),
      'div' => array(
        'by' => array('&L3%&R3==0')
      ),
      'divisible' => array(
        'by' => array('&L3%&R3==0')
      ),
      'not' => array(
        'even' => array('&L3%2!=0'),
        'set' => array('!isset(&L1)'),
        'empty' => array('!empty(&L1)'),
        'divisible' => array(
          'by' => array('&L3%&R3!=0')
        )
      )
    )
  );

  /**
   * Type translation table for resolving allowed types.
   * 
   * @var aArray
   */
  private static $availOpTypes = array(
    'CTE_Engine_Expression_Variable'
      => self::TYPE_VARIABLE,
    'CTE_Engine_Expression_Literal_Numeric'
      => self::TYPE_LITERAL_NUMERIC,
    'CTE_Engine_Expression_Literal_String'
      => self::TYPE_LITERAL_STRING,
    'CTE_Engine_Expression_Literal_ParseableString'
      => self::TYPE_LITERAL_STRING
  );

  /**
   * @var bool
   */
  private $validated = false;

  /**
   * Allowed operand types in bitwise form. Index 0 = left, index 1 = right.
   * 
   * @var array
   */
  private $allowedTypeBits = array(0, 0);

  /**
   * @var bool
   */
  private $leftOpRequired = false;

  /**
   * @var bool
   */
  private $rightOpRequired = false;

  /**
   * Function template with $1 and/or $2 symbolizing operands.
   *
   * @var string
   */
  private $functionTemplate;

  /**
   * @see CTE_Engine_Expression_Abstract::requestInstance()
   */
  public static function requestInstance(CTE_Engine_Parser_Tag $tagParser)
  {
    $line_no = $tagParser->getTemplateLineNum();

    if (($phpOp = $tagParser->mapAssoc(self::$functions)) != null) {
      return new self($phpOp, $line_no);
    }

    return null;
  }

  /**
   * @param string $functionTemplate
   * @param int $line_no
   */
  private function __construct($functionTemplate, $line_no)
  {
    $this->setLineNum($line_no);
    
    $this->functionTemplate = $functionTemplate;
    $this->leftOpRequired = strpos($functionTemplate, '&L') !== false;
    $this->rightOpRequired = strpos($functionTemplate, '&R') !== false;

    // Fetch allowed left operand types:
    if ($this->leftOpRequired) {
      
      // Fetch type integer from function template:
      preg_match('/&L([0-9]+)/', $this->functionTemplate, $match);

      // Make sure we've got a match:
      if (!is_numeric($match[1])) {
        throw new CTE_Engine_Expression_Exception(
          'operand placeholder did not contain a numeric type hint'
        );
      }

      $this->allowedTypeBits[0] = (int) $match[1];
    }

    // Fetch allowed right operand types:
    if ($this->rightOpRequired) {
      
      // Fetch type integer from function template:
      preg_match('/&R([0-9]+)/', $this->functionTemplate, $match);

      // Make sure we've got a match:
      if (!is_numeric($match[1])) {
        throw new CTE_Engine_Expression_Exception(
          'operand placeholder did not contain a numeric type hint'
        );
      }

      $this->allowedTypeBits[1] = (int) $match[1];
    }
  }

  /**
   * @see CTE_Engine_Expression_Abstract::getName()
   */
  public function getName()
  {
    return 'function expression';
  }

  /**
   * Validates neighbors.
   * 
   * @throws CTE_Engine_Template_Contextual_Exception
   * @return void
   */
  private function validate()
  {
    if ($this->validated) {
      return;
    }

    $leftValid = false;
    $rightValid = false;

    if ($this->leftOpRequired) {
      
      $class = get_class($this->getLeftNeighbor());
      
      // Throw exception if we've got an invalid left neighbor:
      if (!isset(self::$availOpTypes[$class]) ||
          (self::$availOpTypes[$class] & $this->allowedTypeBits[0]) == 0) {
        throw new CTE_Engine_Template_Contextual_Exception(
          $this,
          "invalid left operand for {$this->getName()}"
        );
      }
    }

    if ($this->rightOpRequired) {
      
      $class = get_class($this->getRightNeighbor());
      
      // Throw exception if we've got an invalid right neighbor:
      if (!isset(self::$availOpTypes[$class]) ||
          (self::$availOpTypes[$class] & $this->allowedTypeBits[1]) == 0) {
        throw new CTE_Engine_Template_Contextual_Exception(
          $this,
          "invalid right operand for {$this->getName()}"
        );
      }
    }

    $this->validated = true;
  }

  /**
   * @see CTE_Engine_Expression_Context_Integrator_Interface::
   *      integrationPriority()
   */
  public function integrationPriority()
  {
    return CTE_Engine_Expression_Context_Integrator_Interface::
           PRIORITY_FUNCTION;
  }

  /**
   * @see CTE_Engine_Expression_Context_Integrator_Interface::integrate()
   */
  public function integrate()
  {
    parent::integrate();
    $this->validate();
    
    if ($this->leftOpRequired) {
      $this->vacuumLeft(true);
    }
    
    if ($this->rightOpRequired) {
      $this->vacuumRight(true);
    }

    $this->finalizeIntegration();
  }
  
  /**
   * @see CTE_Engine_Expression_Compilable_Interface::compile()
   */
  public function compile($concatContext = false)
  {
    $result = $this->functionTemplate;
    
    if (!is_null($this->leftOperand)) {
      $result = preg_replace('/&L[0-9]+/',
                             $this->leftOperand->compile(),
                             $result,
                             1);
    }
    
    if (!is_null($this->rightOperand)) {
      $result = preg_replace('/&R[0-9]+/',
                             $this->rightOperand->compile(),
                             $result,
                             1);
    }
    
    return $this->assemble($result);
  }
}
?>