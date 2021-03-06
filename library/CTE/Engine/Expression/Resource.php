<?php
/**
 *
 *
 * @package CTE_Engine_Expression
 * @since 2007-01-27
 * @version 2007-02-10
 * @copyright Cylab 2007
 * @author Lukas Kalinski
 */

/**
 * @access private
 */
class CTE_Engine_Expression_Resource
  extends CTE_Engine_Expression_Abstract
    implements CTE_Engine_Expression_Evaluable_Interface,
               CTE_Engine_Process_Creator_Interface
{
  /**
   * @var CTE_Engine_ExpressionMapper
   */
  private static $exprMapper = null;

  /**
   * Caches resource handler instances.
   * 
   * @var aArray
   */
  private static $handlerCache = array();
  
  /**
   * Tracks currently active resources (used to prevent recursion).
   * 
   * @var aArray
   */
  private static $openResources = array();
  
  /**
   * Data Source Name
   * 
   * @var string
   */
  private $dsn;
  
  /**
   * Data Source Path
   * 
   * @var string
   */
  private $path;
  
  /**
   * Template Parameters
   * 
   * @var CTE_Engine_Expression_Compilable_Interface[]
   */
  private $params = array();
  
  /**
   * Render mode of current path.
   * 
   * @see CTE_DataAccess_Interface
   * @var int
   */
  private $renderMode;
  
  /**
   * Data Source Handler
   * 
   * @var CTE_DataAccess_Interface
   */
  private $handler;
  
  /**
   * This will be set to tag start and end delimiters if we're going to parse
   * our data, i.e: array('{', '}') etc.
   * 
   * @var array
   */
  private $tagDelims = null;
  
  /**
   * Fetched data from handler.
   * 
   * @var string
   */
  private $data;
  
  /**
   * @var bool
   */
  private $isStatic = true;
  
  /**
   * Will be true during possible compilation of template data.
   * 
   * @var bool
   */
  private $processActive = false;
  
  /**
   * Does static setup only once.
   * 
   * @return void
   */
  private static function setup()
  {
    if (is_null(self::$exprMapper)) {
      self::$exprMapper = new CTE_Engine_ExpressionMapper();
      self::$exprMapper->registerExpression('Tag_Comment');
      self::$exprMapper->registerExpression('Tag_Print');
      self::$exprMapper->registerExpression('Tag_Literal');
      self::$exprMapper->registerExpression('Tag_If');
      self::$exprMapper->registerExpression('Tag_Foreach');
      self::$exprMapper->registerExpression('Tag_Section');
    }
  }
  
  /**
   * @see CTE_Engine_Expression_Abstract::requestInstance()
   */
  public static function requestInstance(CTE_Engine_Parser_Tag $tagParser)
  {
    if ($tagParser->currentToken() == '@') {
      return new self($tagParser);
    }
    
    return null;
  }
  
  /**
   * Resource Class Constructor. Does necessary stuff and creates/enters a new 
   * external template process.
   * 
   * @param CTE_Engine_Parser_Tag $tagParser
   */
  private function __construct(CTE_Engine_Parser_Tag $tagParser)
  {
    self::setup();
    
    $this->setLineNum($tagParser->getTemplateLineNum());
    
    // Leave '@' or '#' behind and require more tokens:
    $this->nextIdentifier($tagParser);
    
    // Fetch data source name:
    $this->dsn = strtolower($tagParser->currentToken());
    
    // Validate data source name:
    if (!preg_match('/^[a-z_][a-z0-9_]*$/', $this->dsn)) {
      throw new CTE_Engine_Template_Parsing_Exception(
        $this,
        "invalid data source name: //{$this->dsn}//"
      );
    }
    
    // Require a '[' operator:
    $this->nextIdentifier($tagParser);
    $this->requireIdentifier($tagParser, '[');
    
    $this->nextIdentifier($tagParser);
    
    // Fetch resource id:
    $this->path = '';
    while ($tagParser->valid() && $tagParser->currentToken() != ']') {
      
      $this->path .= $tagParser->currentToken();
      $this->nextIdentifier($tagParser);
    }
    
    // Require a ']' operator:
    $this->requireIdentifier($tagParser, ']');
    
    // Leave ending operator behind:
    $tagParser->nextToken();
    
    $this->loadHandler();
    
    // Get render mode for current path:
    $this->renderMode = $this->handler->getRenderMode($this->path);
    
    $this->data = $this->handler->fetch($this->path);
    
    // Load tag delims and check if static if we're going to parse our data:
    if ($this->renderMode == CTE_DataAccess_Interface::RENDER_TPL) {
      
      // Get delimiters specified by current resource handler:
      $this->tagDelims = $this->handler->getTplDelims();
      
      // Check wheter delimiters were returned or if we should use the standard
      // ones instead:
      if (is_null($this->tagDelims)) {
        $config = CTE_Engine_Environment::getInstance()->getConfig();
        $this->tagDelims = array(
          $config->getTagStartDelim(),
          $config->getTagEndDelim()
        );
      }
      
      // We're static only if no tag delimiters are found:
      $this->isStatic = (strpos($this->data, $this->tagDelims[0]) === false);
    }
  }
  
  /**
   * Registers current resource as active.
   * 
   * @return void
   */
  private function register()
  {
    $key = $this->dsn . '%' . $this->path;
    
    // Make sure resource isn't already open:
    if (isset(self::$openResources[$key])) {
      throw new CTE_Engine_Template_Contextual_Exception(
        $this,
        "recursion detected: //{$this->dsn}[{$this->path}]// already opened"
      );
    }
    
    self::$openResources[$key] = true;
  }
  
  /**
   * Unregisters current resource from being active.
   * 
   * @return void
   */
  private function unregister()
  {
    unset(self::$openResources[$this->dsn . '%' . $this->path]);
  }
  
  /**
   * Loads (if not cached) and caches a data access handler for current DSN.
   * 
   * @return void
   */
  private function loadHandler()
  {
    // Load and cache handler if not cached:
    if (!isset(self::$handlerCache[$this->dsn])) {
      
      // Get resource class name from config:
      $className = CTE_Engine_Environment::
        getInstance()->
        getConfig()->
        resolveResourceClassName($this->dsn);
      
      // Make sure resolving succeeded:
      if (is_null($className)) {
        throw new CTE_Engine_Template_Existence_Exception(
          $this,
          "unknown resource name: {$this->dsn}"
        );
      }
      
      // Try to resolve class and throw CTE exception on failure:
      try {
        Cylab::loadClass($className);
      } catch (Cylab_Exception $e) {
        throw new CTE_Engine_Template_Existence_Exception(
          $this,
          "data access class not found: $className"
        );
      }
      
      self::$handlerCache[$this->dsn] = new $className(
        CTE_Engine_Environment::getInstance()->getConfig()
      );
    }
    
    $this->handler = self::$handlerCache[$this->dsn];
  }
  
  /**
   * Adds a parameter for the current resource.
   *
   * @param string $name
   * @param CTE_Engine_Expression_Compilable_Interface $value
   * @throws CTE_Engine_Expression_Exception if current resource isn't a
   *         template.
   * @throws CTE_Engine_Expression_Exception if a parameter already is set.
   * @return void
   */
  public function setParam(
      $name,
      CTE_Engine_Expression_Compilable_Interface $value
    )
  {
    // Throw exception if we're not going to parse requested data,
    // and therefore don't want any parameters:
    if ($this->renderMode != CTE_DataAccess_Interface::RENDER_TPL) {
      throw new CTE_Engine_Expression_Exception(
        'template parameters not accepted for non-template data'
      );
    }
    
    // Make sure we're not overwriting any existing parameter:
    if (isset($this->params[$name])) {
      throw new CTE_Engine_Expression_Exception(
        "parameter already set"
      );
    }
    
    $this->params[$name] = $value;
  }
  
  /**
   * @see CTE_Engine_Expression_Abstract::getName()
   */
  public function getName()
  {
    return 'resource';
  }

  /**
   * @see CTE_Engine_Process_Creator_Interface::notifyProcessUpdated()
   * @throws CTE_Engine_Expression_Exception if our process is left without us
   *         being finished.
   */
  public function notifyProcessUpdated(CTE_Engine_Process_Abstract $process)
  {
    // Throw exception if our process was forced to close:
    if ($process->isClosed() && $this->processActive) {
      throw new CTE_Engine_Expression_Exception(
        'illegal close of external template process'
      );
    }
  }
  
  /**
   * Runs external compiler with mode $mode on our data and returns the result.
   * 
   * @param int $mode Compile mode {@see CTE_Engine_Compiler::MODE_*}
   * @return string
   */
  private function runExtCompiler($mode)
  {
    $env = CTE_Engine_Environment::getInstance();
    
    // Initialise a parser for our content:
    $parser = new CTE_Engine_Parser($this->data, $this->tagDelims);
    
    // Create a new compiler for us:
    $compiler = $env->createCompiler(self::$exprMapper, $mode);
    
    // Enter a new external template process:
    $process = $env->
      getProcessManager()->
      enterProcess($this, 'Template_External');
    $this->processActive = true;
    
    // Register usage of current dsn+path combination:
    $this->register();
    
    // Import parameters (now that we've entered the external template
    // process):
    foreach ($this->params as $name => $value) {
      $env->getVariableRegistry()->associate($value, $name);
    }
    
    try {
      $result = $compiler->compile($parser);
    } catch (CTE_Engine_Template_Exception $e) {
      $error = new CTE_Error_Template($env->getConfig(), $e, $this->path);
      $error->trigger();
    }
    
    // Release DSN+path (so that it is allowed to use again):
    $this->unregister();
    
    $this->processActive = false;
    
    // Leave our external template process:
    $env->getProcessManager()->leaveProcess($process);
    
    // Restore old compiler:
    $env->dropCompiler($compiler);
    
    return $result;
  }
  
  /**
   * Evaluation means _not_ wrapping inside single quotes. I.e. no code will be
   * evaluated; it will be returned as is, without any wrappers.
   * 
   * @see CTE_Engine_Expression_Evaluable_Interface::evaluate()
   */
  public function evaluate($inline)
  {
    if ($inline) {
      return $this->runExtCompiler(CTE_Engine_Compiler::MODE_EVAL_INLINE);
    } else {
      return $this->runExtCompiler(CTE_Engine_Compiler::MODE_EVAL);
    }
  }
  
  /**
   * @see CTE_Engine_Expression_Evaluable_Interface::isStatic()
   */
  public function isStatic()
  {
    return $this->isStatic;
  }
  
  /**
   * Compilation means wrapping the fetched data inside single quotes, ready to
   * use in php expression context. The external template process will be closed
   * in here.
   * 
   * @see CTE_Engine_Expression_Compilable_Interface::compile()
   * @return mixed Integer or string.
   */
  public function compile($concatContext = false)
  {
    if ($concatContext) {
      return $this->runExtCompiler(CTE_Engine_Compiler::MODE_COMPILE_CONCAT);
    } else {
      return $this->runExtCompiler(CTE_Engine_Compiler::MODE_COMPILE);
    }
  }
}
?>