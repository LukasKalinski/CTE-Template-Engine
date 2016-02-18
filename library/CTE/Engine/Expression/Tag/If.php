<?php
/**
 *
 *
 * @package CTE_Engine_Expression_Tag
 * @since 2006-08-25
 * @version 2007-02-04
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
class CTE_Engine_Expression_Tag_If
  extends CTE_Engine_Expression_Tag_Abstract
    implements CTE_Engine_Parser_Tag_Handler_Interface,
               CTE_Engine_Process_Creator_Interface,
               CTE_Engine_Expression_Context_Creator_Interface
{
  /**
   * Tells whether the static setup has been done or not.
   * 
   * @var bool
   */
  private static $isSetup = false;
  
  /**
   * @var CTE_Engine_ExpressionMapper
   */
  private static $exprMapper;

  /**
   * Additional tokens to split into.
   * 
   * @var string
   */
  private static $additionalTokenRegex = '\|\||&&|==|!=|!==|===|>=|<=';
  
  /**
   * @var CTE_Engine_Process_Block
   */
  private $process;

  /**
   * @var bool
   */
  private $hadElse = false;

  /**
   * @var bool
   */
  private $isClosed = false;

  /**
   * @var bool
   */
  private $isElseif = false;

  /**
   * @see CTE_Engine_Expression_Abstract::requestInstance()
   * 
   * @throws CTE_Engine_Template_Parsing_Exception
   */
  public static function requestInstance(CTE_Engine_Parser_Tag $tagParser)
  {
    if ($tagParser->beginsWith('if')) {
      self::tokenizeTag($tagParser, self::$additionalTokenRegex);
      return new self($tagParser);
    } else if ($tagParser->beginsWith('elseif')) {
      self::tokenizeTag($tagParser, self::$additionalTokenRegex);
      return new self($tagParser, true);
    } else {
      return null;
    }
  }

  /**
   * @param CTE_Engine_Parser_Tag $tagParser
   * @param bool $elseif Wheter it's a elseif or not.
   * @throws CTE_Engine_Template_Parsing_Exception
   */
  private function __construct(CTE_Engine_Parser_Tag $tagParser,
                               $elseif = false)
  {
    self::setup();
    
    $this->setLineNum($tagParser->getTemplateLineNum());
    
    // Skip past the "if" string:
    $this->nextIdentifier($tagParser);

    $env = CTE_Engine_Environment::getInstance();
    
    $this->isElseif = $elseif;

    // If we're not an elseif tag:
    if (!$this->isElseif) {
      // Enter a new block process and save it (so we know what to exit later):
      $this->process = $env->getProcessManager()->enterProcess($this, 'Block');
      
      // Register this object as a tag parser handler in current compiler:
      $env->getCompiler()->registerTagParserHandler($this);
    }

    // Initiate expression context object:
    parent::initMainContext();

    // Prepare parenthesis class:
    CTE_Engine_Expression_Operator_Parenthesis::begin();
    
    // Interpret expression:
    while ($tagParser->valid()) {
      $expression = self::$exprMapper->mapExpression($tagParser);

      // Handle null expression (i.e. unknown token):
      if (is_null($expression)) {
        throw new CTE_Engine_Template_Parsing_Exception(
          $this,
          "unknown identifier: {$tagParser->currentToken()}"
        );
      }

      // Insert our (sub) expression into the current context object:
      $this->context->insert($expression);
    }
    
    // Make sure that parentheses are balanced:
    if (!CTE_Engine_Expression_Operator_Parenthesis::isBalanced()) {
      throw new CTE_Engine_Template_Parsing_Exception(
        $this,
        'unbalanced parentheses'
      );
    }
    
    // Reset parenthesis class:
    CTE_Engine_Expression_Operator_Parenthesis::end();
  }

  /**
   * Sets up all static data for this class.
   */
  protected static function setup()
  {
    if (!self::$isSetup) {
      self::$exprMapper = new CTE_Engine_ExpressionMapper();
      self::$exprMapper->registerExpression('Literal_Numeric');
      self::$exprMapper->registerExpression('Literal_String');
      self::$exprMapper->registerExpression('Literal_ParseableString');
      self::$exprMapper->registerExpression('Literal_Bool');
      self::$exprMapper->registerExpression('Variable');
      self::$exprMapper->registerExpression('Modifier');
      self::$exprMapper->registerExpression('Operator_Parenthesis');
      self::$exprMapper->registerExpression('Function');
      self::$exprMapper->registerExpression('Comparison');
      self::$exprMapper->registerExpression('LogicalNot');
      self::$exprMapper->registerExpression('Logical');
      self::$exprMapper->registerExpression('Arithmetic');
      
      self::$isSetup = true;
    }
  }
  
  /**
   * Catches elseif, else and end tags before they're mapped into new expression
   * objects.
   * 
   * @see CTE_Engine_Parser_Tag_Handler_Interface::CTE_Engine_Parser_Tag()
   * @param CTE_Engine_Parser_Tag $tagParser
   * @return string The compiled output.
   */
  public function handleTagParser(CTE_Engine_Parser_Tag $tagParser)
  {
    // Check if end tag:
    if ($tagParser->__toString() == '/if') {
      $tagParser->skip();
      $this->isClosed = true;

      $env = CTE_Engine_Environment::getInstance();

      // Unregister this object from compiler:
      $compiler = $env->getCompiler();
      $compiler->unregisterTagParserHandler($this);

      // Close our process:
      $env->getProcessManager()->leaveProcess($this->process);

      return $this->wrapPhpCode('}');
    // Check if elseif tag:
    } else if (($elseif = self::requestInstance($tagParser)) != null) {
      if ($this->hadElse) {
        throw new CTE_Engine_Template_Contextual_Exception(
          $this,
          'elseif cannot appear after an else clause'
        );
      }
      
      return $elseif->compile();
    // Check if else tag:
    } else if ($tagParser->__toString() == 'else') {
      // Make sure we're not having another else tag:
      if ($this->hadElse) {
        throw new CTE_Engine_Template_Contextual_Exception(
          $this,
          'cannot have more than one else clause'
        );
      }
      
      // Move parser pointer to next tag:
      $tagParser->skip();
      
      $this->hadElse = true;
      
      return $this->wrapPhpCode('}else{');
    } else {
      // We're not handling whatever the $tagParser is pointing at.
      return $tagParser;
    }
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
   * @see CTE_Engine_Expression_Abstract::getName()
   */
  public function getName()
  {
    return 'if block';
  }

  /**
   * @see CTE_Engine_Process_Creator_Interface::notifyProcessUpdated()
   */
  public function notifyProcessUpdated(CTE_Engine_Process_Abstract $process)
  {
    if (!$this->isClosed && $process->isClosed()) {
      // We can't close process without an end tag, throw exception:
      throw new CTE_Engine_Template_Contextual_Exception(
        $this,
        'missing end tag'
      );
    }
  }

  /**
   * @see CTE_Engine_Expression_Compilable_Interface::compile()
   */
  public function compile($concatContext = false)
  {
    parent::compile($concatContext);
    
    return $this->wrapPhpCode(
             ($this->isElseif ? '}elseif' : 'if')
             . "({$this->context->compile()}){"
           );
  }
}
?>