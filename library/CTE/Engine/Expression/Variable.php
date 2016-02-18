<?php
/**
 *
 *
 * @package CTE_Engine_Expression
 * @since 2006-08-09
 * @version 2007-02-07
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 *
 * History:
 * [2006-08-29]
 * Optimization from using subclasses to doing everything in this class
 * gave a 19% faster generation on 50 x:
 * {$cte.plugin.foo[hej].LoadJs->getBaseFile('hej', $bar, true)}
 *
 * Things to think about:
 * - Just because we're implementing the Evaluable Interface here doesn't mean
 *   that any variable can be evaluated. Exception will be thrown if a variable
 *   isn't evaluable wile calling evaluate().
 * 
 * @access private
 */
class CTE_Engine_Expression_Variable
  extends CTE_Engine_Expression_Abstract
    implements CTE_Engine_Expression_Compilable_Interface,
               CTE_Engine_Expression_Evaluable_Interface
{
  /**
   * The source of all "magic" variables, for example {$cte.time}, which is
   * translated to time() instead of an actual variable path.
   * 
   * Note that the main purpose of this is to explain the exceptional cases, 
   * not to offer some "magic source".
   * 
   * @var int
   */
  const SOURCE_MAGIC = 0;
  
  /**
   * The source of all initially available user-defined variables.
   * 
   * @var int
   */
  const SOURCE_INITIAL = 1;

  /**
   * Source of all runtime available section-defined variables.
   * 
   * @var int
   */
  const SOURCE_RUNTIME_SECTION = 2;
  
  /**
   * Source of all runtime available foreach-defined variables.
   * 
   * @var int
   */
  const SOURCE_RUNTIME_FOREACH = 3;
  
  /**
   * Source of all runtime available plugin-defined variables.
   * 
   * @var int
   */
  const SOURCE_RUNTIME_PLUGIN = 4;
  
  /**
   * Source of all runtime available modifier plugin related variables.
   * 
   * @var int
   */
  const SOURCE_RUNTIME_MODPLUGIN = 5;
  
  /**
   * Tells whether the static setup has been done or not.
   * 
   * @var bool
   */
  private static $isSetUp = false;
  
  /**
   * @var CTE_Engine_ExpressionMapper
   */
  private static $propArgMapper;

  /**
   * The system variable name regex. Note that this will always be lower-cased.
   * 
   * @var string
   */
  private static $systemVarName;
  
  /**
   * Matches variable name, without the $-prefix.
   *
   * @var aArray
   */
  private static $basenameRegex = '[a-zA-Z_][a-zA-Z0-9_]*';

  /**
   * Matches a static key, i.e. bar in $foo.bar.
   * 
   * @var string
   */
  private static $staticKeyNameRegex = '(?:[a-zA-Z0-9_]+)';

  /**
   * Matches a dynamic key, i.e. bar in $foo[bar].
   *
   * @var string
   */
  private static $dynamicKeySectionRegex = '(?:[a-zA-Z0-9_]+)';

  /**
   * Matches an property name, i.e. bar in $foo->bar.
   * 
   * @var string
   */
  private static $propertyNameRegex = '(?:[a-zA-Z_][a-zA-Z0-9_]*)';

  /**
   * Cache for current section holder.
   *
   * @var string
   */
  private $sourcePath = null;

  /**
   * The source this variable should be attached to. See source constants
   * for more information.
   * 
   * @var int
   */
  private $source;
  
  /**
   * The name of the variable.
   * 
   * Currently (070103) the two instance-factory methods make sure that 
   * no instance is created without a valid name.
   * 
   * @var string
   */
  private $basename = null;
  
  /**
   * Contains all variable path components, such as static/dynamic keys
   * and property accesses.
   * 
   * @var CTE_Engine_Expression_Variable_Component_Interface[]
   */
  private $components = array();
  
  /**
   * Cache of evaluated variable result.
   * 
   * @var string
   */
  private $evaluated = null;
  
  /**
   * Whether it is possible to evaluate current variable or not.
   * 
   * @var bool
   */
  private $evaluable = null;

  /**
   * Cache of compiled variable result.
   * 
   * @var string
   */
  private $compiled = null;
  
  /**
   * If current variable is an alias to another expression, then that aliased
   * expression will be stored here.
   * 
   * @var CTE_Engine_Expression_Abstract
   */
  private $aliasedExpression = null;

  /**
   * Returns a new instance of this class if it thinks that it
   * can handle whatever the $tagParser is pointing at.
   * 
   * @see CTE_Engine_Expression_Abstract::requestInstance()
   */
  public static function requestInstance(CTE_Engine_Parser_Tag $tagParser)
  {
    if ($tagParser->currentToken() == '$') {
      $o = new self($tagParser->getTemplateLineNum());
      $o->parse($tagParser);
      return $o;
    } else {
      return null;
    }
  }

  /**
   * Returns a new instance of this class with variable basename set to $name.
   * This manual-create function also allows setting an other source than the 
   * default SOURCE_INITIAL.
   * 
   * @param string $name
   * @param int $source The real source of the created variable, i.e.
   *                    SOURCE_INITIAL or SOURCE_RUNTIME_SECTION. If set to null
   *                    default source will be used (SOURCE_INITIAL).
   * @throws CTE_Engine_Expression_Exception
   * @return CTE_Engine_Expression_Variable
   */
  public static function createInstance($name, $source = self::SOURCE_INITIAL)
  {
    // If we have source initial, the official naming rules apply, otherwise we
    // just have to match the static key naming rule:
    if (($source == self::SOURCE_INITIAL &&
         !preg_match('/^' . self::$basenameRegex . '$/', $name)) ||
        ($source != self::SOURCE_INITIAL &&
        !preg_match('/^' . self::$staticKeyNameRegex . '$/', $name))) {
      throw new CTE_Engine_Expression_Exception("invalid variable name: $name");
    }
    
    return new self(-1, $source, $name);
  }
  
  /**
   * Does static setup for this class.
   * 
   * @return void
   */
  private static function setup()
  {
    if (!self::$isSetUp) {
      $config = CTE_Engine_Environment::getInstance()->getConfig();
      
      // Setup valid expressions for property method arguments:
      self::$propArgMapper = new CTE_Engine_ExpressionMapper();
      self::$propArgMapper->registerExpression('Variable');
      self::$propArgMapper->registerExpression('Arithmetic');
      self::$propArgMapper->registerExpression('Literal_Bool');
      self::$propArgMapper->registerExpression('Literal_Numeric');
      self::$propArgMapper->registerExpression('Literal_String');
      self::$propArgMapper->registerExpression('Literal_ParseableString');
      
      // Setup system variable name regex (the case will be ignored):
      self::$systemVarName = strtolower($config->getTplVarNameSys());
      
      self::$isSetUp = true;
    }
  }
  
  /**
   * Constructor
   * Does static setup on first instantiation.
   * 
   * @param int $line_no
   * @param int $source See SOURCE_* constants.
   * @param string $name
   */
  private function __construct($line_no,
                               $source = self::SOURCE_INITIAL,
                               $name = null)
  {
    self::setup();
    $this->setLineNum($line_no);
    $this->source = $source;
    
    if (!is_null($name)) {
      $this->setName($name);
    }
  }

  /**
   * Returns true if current variable is an alias for another variable.
   * 
   * @return bool
   */
  private function isVariableAlias()
  {
    return (!is_null($this->aliasedExpression) &&
            $this->aliasedExpression instanceof self);
  }
  
  /**
   * Returns true if current variable is an alias for another expression.
   * 
   * @return bool
   */
  private function isExpressionAlias()
  {
    return !is_null($this->aliasedExpression);
  }
  
  /**
   * Parses relevant data into self using $tagParser.
   * 
   * @param CTE_Engine_Parser_Tag $tagParser
   * @return void
   */
  private function parse(CTE_Engine_Parser_Tag $tagParser)
  {
    $this->nextIdentifier($tagParser);

    $name = $tagParser->currentToken();
    $this->nextIdentifier($tagParser, false);
    
    // Validate variable name:
    if (!preg_match('/' . self::$basenameRegex . '/', $name)) {
      throw new CTE_Engine_Template_Parsing_Exception(
        $this,
        "invalid variable name: $name"
      );
    }

    $varRegistry = CTE_Engine_Environment::getInstance()->getVariableRegistry();
    
    // Check if our name is an alias in the variable registry:
    if (($var = $varRegistry->resolveAssociation($name)) != null) {
      $this->aliasedExpression = $var;
    }
    
    // Read and set variable name:
    $this->setName($name);
    
    $this->parseComponents($tagParser);
  }

  /**
   * Parses the variable's components, such as static and dynamic keys and 
   * property accesses.
   * 
   * @param CTE_Engine_Parser_Tag $tagParser
   * @return void
   */
  private function parseComponents(CTE_Engine_Parser_Tag $tagParser)
  {
    // Keys are only allowed until we've had a property method access:
    $keysAllowed = true;
    
    // Loop until we're not recognizing the identifier anymore:
    while ($tagParser->valid()) {
      if ($tagParser->currentToken() == '.') {
        if (!$keysAllowed) {
          throw new CTE_Engine_Template_Contextual_Exception(
            $this,
            'keys not allowed after object method call'
          );
        }
        $this->parseStaticKey($tagParser);
      } else if ($tagParser->currentToken() == '[') {
        if (!$keysAllowed) {
          throw new CTE_Engine_Template_Contextual_Exception(
            $this,
            'keys not allowed after object method call'
          );
        }
        $this->parseDynamicKey($tagParser);
      } else if ($tagParser->currentToken() == '->') {
        $this->parseProperty($tagParser);
        
        // Fetch the most recently added component:
        end($this->components);
        $propAccess = current($this->components);
        
        // Make sure we have a proper instance:
        if (!$propAccess instanceof 
            CTE_Engine_Expression_Variable_PropertyAccess) {
          throw new CTE_Engine_Expression_Exception(
            'invalid object instance or not an object'
          );
        }
        
        // We're not allowing keys if property access was a method:
        $keysAllowed = !$propAccess->isMethod();
      } else {
        break;
      }
    }
    
    // Check that we haven't aliased a non-variable expression and have
    // components at the same time:
    if (count($this->components) > 0 &&
        $this->isExpressionAlias() &&
        !$this->isVariableAlias()) {
      // Since the circumstances in the template will be totally different,
      // we have to write this not so obvious error message:
      throw new CTE_Engine_Template_Contextual_Exception(
        $this,
        'undefined keys and/or properties'
      );
    }
  }

  /**
   * Returns true if this variable is a system variable. Requires $this->source
   * to be set properly.
   * 
   * @return bool
   */
  private function isSystemVar()
  {
    return ($this->source == self::SOURCE_MAGIC);
  }
  
  /**
   * Sets the basename and checks if name is valid if requested. If the name
   * isn't a system variable name and is associated with something else
   * 
   * @param string $name
   * @param bool $doCheck Whether to check name validity or not.
   * @throws CTE_Engine_Expression_Exception on name validity failure.
   * @return void
   */
  private function setName($name, $doCheck = false)
  {
    if ($doCheck && !preg_match('/^' . self::$basenameRegex . '$/', $name)) {
      throw new CTE_Engine_Expression_Exception(
        "invalid variable basename: $name"
      );
    }
    
    // Lower-case $name if it's a system variable:
    if (strtolower($name) == self::$systemVarName) {
      $name = strtolower($name);
      // System variables are magic:
      $this->source = self::SOURCE_MAGIC;
    }
    
    $this->basename = $name;
  }
  
  /**
   * Adds a static key to the variable path. A static key 
   * may be a number or a string matching the static key regex.
   * 
   * @param string $name
   * @param bool $doCheck Whether to check name validity or not.
   * @throws CTE_Engine_Expression_Exception on name validity failure.
   * @return void
   */
  private function _addStaticKeyComponent($name, $doCheck = false)
  {
    if ($doCheck &&
        !preg_match('/^' . self::$staticKeyNameRegex . '$/', $name)) {
      throw new CTE_Engine_Expression_Exception(
        "invalid static key name: $name"
      );
    }
    
    // Append result onto components stack:
    array_push($this->components,
               new CTE_Engine_Expression_Variable_StaticKey($name));
  }
  
  /**
   * Public wrapper for private function, forcing name checking for all
   * outside calls.
   * 
   * @see self::_addStaticKeyComponent()
   * @param mixed $name
   */
  public function addStaticKeyComponent($name)
  {
    $this->assureComponentAddOk();
    $this->clearResultCache();
    $this->_addStaticKeyComponent($name, true);
  }
  
  /**
   * Imports Component Interface objects into the components array.
   * 
   * @param array $components
   * @throws CTE_Engine_Expression_Exception if component is of wrong type.
   * @return void
   */
  public function importComponentList(array $components)
  {
    for ($i=0, $ii=count($components); $i<$ii; $i++) {
      if (!$components[$i] instanceof
          CTE_Engine_Expression_Variable_Component_Interface) {
        throw new CTE_Engine_Expression_Exception('invalid component');
      }
      
      $this->components[] = $components[$i];
    }
  }
  
  /**
   * Adds a dynamic key to the variable path. A dynamic key is expected
   * to be another variable, therefore the argument restriction.
   * 
   * Note that section keys must be converted to a variable expression.
   * 
   * @param string $name
   * @return void
   */
  private function _addDynamicKeyComponent(CTE_Engine_Expression_Variable $key)
  {
    // Prepare and append result onto components stack:
    array_push($this->components,
               new CTE_Engine_Expression_Variable_DynamicKey($key));
  }
  
  /**
   * Public wrapper for private function.
   * 
   * @see self::_addDynamicKeyComponent()
   * @param CTE_Engine_Expression_Variable $key
   * @return void
   */
  public function addDynamicKeyComponent(CTE_Engine_Expression_Variable $key)
  {
    $this->assureComponentAddOk();
    $this->clearResultCache();
    $this->_addDynamicKeyComponent($key);
  }
  
  /**
   * Adds a property access in the variable path. If $args are null, the 
   * access will be considered a property access, if $args are an array
   * counting from 0 to n elements, it will be considered a method call.
   * 
   * @param string $name
   * @param array $args An array of Expression_Literal_*, Expression_Variable
   *                    or Expression_Resource objects.
   * @param bool $doCheck Whether to check name validity or not.
   * @throws CTE_Engine_Expression_Exception on name validity failure.
   * @return void
   */
  private function _addPropertyComponent($name,
                                         array $args = null,
                                         $doCheck = false)
  {
    if ($doCheck &&
        !preg_match('/^' . self::$propertyNameRegex . '$/', $name)) {
      throw new CTE_Engine_Template_Parsing_Exception(
        $this,
        "invalid object property name: $name"
      );
    }
    
    // Append result onto components stack:
    array_push($this->components,
               new CTE_Engine_Expression_Variable_PropertyAccess($name, $args));
  }
  
  /**
   * Public wrapper for private function, forcing name checking for all
   * outside calls.
   * 
   * @see self::_addPropertyComponent()
   * @param string $name
   * @param array $args
   */
  public function addPropertyComponent($name, array $args = null)
  {
    $this->assureComponentAddOk();
    $this->clearResultCache();
    
    $this->_addPropertyComponent($name, $args, true);
  }

  /**
   * Assures that we're not adding components to a non-variable expression
   * alias.
   * 
   * @throws CTE_Engine_Expression_Exception if we're a non-variable alias.
   * @return void
   */
  private function assureComponentAddOk()
  {
    if ($this->isExpressionAlias() && !$this->isVariableAlias()) {
      throw new CTE_Engine_Expression_Exception(
        'cannot add components to aliased non-variable expression'
      );
    }
  }
  
  /**
   * Clears the result cache (i.e. evaluated and compiled cache).
   * 
   * @return void
   */
  private function clearResultCache()
  {
    $this->compiled = null;
    $this->evaluated = null;
  }
  
  /**
   * Transforms the values of an array into an array path. Empty array gives
   * empty string output.
   *
   * Example:
   *   input:  array('a','b',2)
   *   output: ['a']['b'][2]
   *
   * @param string[] $path
   * @return string
   *
   * @deprecated public availability of this function is deprecated: see
   *             CTE_Engine_Compiler::insertVarDeclaration(...)
   */
  private function buildArrayPath(array $path)
  {
    $result = '';
    for ($i=0, $ii=count($path); $i<$ii; $i++) {
      $result .= '['
               . (is_numeric($path[$i]) ?
                   $path[$i] :
                   CTE_Util_String::squote($path[$i]))
               . ']';
    }
    
    return $result;
  }

  /**
   * Returns current reference string to our source. If $source is set to 
   * an integer greater than -1, that source will be used instead and will 
   * not affect the source of this object.
   * 
   * For example:
   * $this->sections[<process_id>][<section_id>]
   * $this->tplvars[<process_id>][<var_name>]
   *
   * @param string $name
   * @param int $source
   * @return string
   */
  private function buildSourcePath($name, $source = -1)
  {
    // Only do this once per variable object or if we submit a custom source:
    if (is_null($this->sourcePath) || $source > -1) {
      $env = CTE_Engine_Environment::getInstance();
      
      // Get config instance:
      $config = $env->getConfig();
      
      // If $source isn't set to a valid value we're using the object's
      // source instead:
      if ($source == -1) {
        $source = $this->source;
      }
      
      // Get current template process id if we're not dealing with user-defined
      // imported vars (which don't use any scope):
      $tpid = $env->getCurrentTemplateProcess()->getId();
      
      // Set source string (note that we're not allowing SOURCE_MAGIC here):
      switch ($source) {
        case self::SOURCE_INITIAL:
          // Check that we're not accessing an user-defined variable from 
          // a subtemplate, having strict variable scope set to true, no
          // guarantee from the template process that the variable is checked
          // and no association for this variable is found:
          if ($config->strictVarScopeOn() &&
              $tpid != CTE_Engine_Process_Abstract::PID_ROOT &&
              !$env->getCurrentTemplateProcess()->hasFlag(
                CTE_Engine_Process_Template_Abstract::FLAG_VARS_CHECKED
              ) &&
              !$env->getVariableRegistry()->isAssociated($this)) {
            throw new CTE_Engine_Template_VariableNotFoundException(
              $this,
              'cannot access user-defined variables from subtemplates when '.
              'strictVarScope is on'
            );
          }
          
          $sourcePath = $config->getInitVarSourceString();
          break;
        case self::SOURCE_RUNTIME_SECTION:
          $sourcePath = $config->getSectionSourceString();
          break;
        case self::SOURCE_RUNTIME_FOREACH:
          $sourcePath = $config->getForeachSourceString();
          break;
        case self::SOURCE_RUNTIME_PLUGIN:
        case self::SOURCE_RUNTIME_MODPLUGIN:
          $sourcePath = $config->getPluginSourceString();
          break;
        default:
          throw new CTE_Engine_Expression_Exception(
            "failed to resolve source: id=$source"
          );
      }
      
      // If we're not dealing with an initially defined variable nor a modifier 
      // plugin variable, we have to add a process_id key too:
      if ($source != self::SOURCE_INITIAL &&
          $source != self::SOURCE_RUNTIME_MODPLUGIN) {
        $sourcePath = $sourcePath . $this->buildArrayPath(array($tpid, $name));
      } else {
        $sourcePath = $sourcePath . $this->buildArrayPath(array($name));
      }
      
      // Just return the generated source path if using a custom source:
      if ($source > -1) {
        return $sourcePath;
      }
      
      // Build array path out of process id and variable name:
      $this->sourcePath = $sourcePath;
    }

    return $this->sourcePath;
  }

  /**
   * Parses object properties off the $tagParser.
   * 
   * @param CTE_Engine_Parser_Tag $tagParser
   * @throws CTE_Engine_Template_Parsing_Exception
   * @return void
   */
  private function parseProperty(CTE_Engine_Parser_Tag $tagParser)
  {
    $this->nextIdentifier($tagParser);
    
    // Fetch property name from parser:
    $name = $tagParser->currentToken();
    
    // Fetch next identifier, but not require it (we may have a non-method 
    // access and therefore not having any parenthesis):
    $this->nextIdentifier($tagParser, false);

    // Validate name:
    if (!preg_match('/^' . self::$propertyNameRegex . '$/', $name)) {
      throw new CTE_Engine_Template_Parsing_Exception(
        $this,
        "invalid property name: {$tagParser->currentToken()}"
      );
    }
    
    // Store property access and return, only if we have a property access:
    if ($tagParser->currentToken() != '(') {
      $this->_addPropertyComponent($name, null);
      return;
    }
    
    $this->nextIdentifier($tagParser);
    
    // Open-parenthesis found; we have a method property.
    
    $args = array();
    $requireComma = false;
    while ($tagParser->valid() && $tagParser->currentToken() != ')') {
      // Require argument separator (,) between arguments:
      if ($requireComma) {
        $this->requireIdentifier($tagParser, ',');
        $this->nextIdentifier($tagParser);
      } else {
        $requireComma = true;
      }

      $expression = self::$propArgMapper->mapExpression($tagParser);

      if (is_null($expression)) {
        throw new CTE_Engine_Template_Parsing_Exception(
          $this,
          "invalid property method argument: {$tagParser->currentToken()}"
        );
      }

      array_push($args, $expression);
    }

    // Require right parenthesis:
    $this->requireIdentifier($tagParser, ')');
    
    $this->nextIdentifier($tagParser, false);
    
    // Store result:
    $this->_addPropertyComponent($name, $args);
  }

  /**
   *
   * @param CTE_Engine_Parser_Tag $tagParser
   * @throws CTE_Engine_Template_Parsing_Exception
   * @return void
   */
  private function parseStaticKey(CTE_Engine_Parser_Tag $tagParser)
  {
    $this->requireIdentifier($tagParser, '.');
    $this->nextIdentifier($tagParser);
    
    $name = $tagParser->currentToken();
    $this->nextIdentifier($tagParser, false);

    if (!preg_match('/^' . self::$staticKeyNameRegex . '$/', $name)) {
      throw new CTE_Engine_Template_Parsing_Exception(
        $this,
        "invalid static key name: $name"
      );
    }

    $this->_addStaticKeyComponent($name);
  }

  /**
   *
   * @param CTE_Engine_Parser_Tag $tagParser
   * @throws CTE_Engine_Template_Parsing_Exception
   * @return void
   */
  private function parseDynamicKey(CTE_Engine_Parser_Tag $tagParser)
  {
    $this->requireIdentifier($tagParser, '[');
    $this->nextIdentifier($tagParser);
    
    if (($var = self::requestInstance($tagParser)) != null) {
      // We have a variable key, store and return:
      $this->addDynamicKeyComponent($var);
    } else {
      $sectionId = $tagParser->currentToken();
      $this->nextIdentifier($tagParser);
      
      if (preg_match('/^' . self::$dynamicKeySectionRegex . '$/', $sectionId)) {
        /* We have a section key. */
        
        // Fetch template process id:
        $pId = CTE_Engine_Environment::
          getInstance()->
          getCurrentTemplateProcess()->
          getId();
        
        // Make sure the id is active:
        if (!CTE_Engine_Expression_Tag_Section::idIsAvail($pId, $sectionId)) {
          throw new CTE_Engine_Template_Contextual_Exception(
            $this,
            "invalid section id: $sectionId"
          );
        }
        
        // Create a variable expression named as $sectionId with source
        // in section vars:
        $sectionVar = self::createInstance($sectionId,
                                           self::SOURCE_RUNTIME_SECTION);
        // Add index key (that's what we want when writing [section_id]
        // after a section loop-var).
        $sectionVar->addStaticKeyComponent('index');
        
        $this->addDynamicKeyComponent($sectionVar);
      } else {
        throw new CTE_Engine_Template_Parsing_Exception(
          $this,
          "unexpected identifier in dynamic key wrapper: $sectionId"
        );
      }
    }

    $this->requireIdentifier($tagParser, ']');
    $this->nextIdentifier($tagParser, false);
  }

  /**
   * @see CTE_Engine_Expression_Abstract::getName()
   */
  public function getName()
  {
    return "variable //\${$this->basename}//";
  }

  /**
   * Tries to interpret this instance as a system variable and either 
   * returns the reference string to it ($evaluate = false) or it's 
   * real value ($evaluate = true).
   * 
   * @param bool $evaluate
   * @throws CTE_Engine_Expression_Exception if we're not a system variable.
   * @return string or null if requested evaluation failed.
   */
  private function interpretSystemVariable($evaluate = false)
  {
    // Make sure we're a system variable:
    if (!$this->isSystemVar()) {
      throw new CTE_Engine_Expression_Exception(
        'variable is not a system variable'
      );
    }
    
    // Get config instance:
    $config = CTE_Engine_Environment::getInstance()->getConfig();
    
    // Result container:
    $result = '';
    
    // Exiting the loop below with to few components will result in an error:
    $componentsSatisfied = false;
    
    // When set to false, additional components will result in an error:
    $componentsAllowed = true;
    
    // The index of $this->components to do simple key interpretion from:
    $simpleFrom = 0;
    
    // Number of components:
    $components_n = count($this->components);
    
    // Interpret components:
    for ($i=0; $i<$components_n; $i++) {
      
      // Make sure we're dealing with a static key:
      if (!$this->components[$i] instanceof
          CTE_Engine_Expression_Variable_StaticKey) {
        throw new CTE_Engine_Template_Contextual_Exception(
          $this,
          'only static keys allowed in this path level'
        );
      }
      
      // Fetch current component name:
      $component = $this->components[$i]->getName();
      
      // Make sure we're allowed to have more components:
      if (!$componentsAllowed) {
        throw new CTE_Engine_Template_Contextual_Exception(
          $this,
          "invalid path component: $component"
        );
      }
      
      switch ($component) {
        case 'now':
        case 'time':
          return ($evaluate ? (string) time() : 'time()');
        
        case 'version':
          return ($evaluate ? CTE::VERSION : 'self::VERSION');
        
        case 'plugin': // Fix evaluation ??                                      ###
                       // must check if plugin is a runtime one or not           !!!
          // Go to next index, which should be pointing at a plugin name:
          $i++;
          
          // Try to fetch plugin name or throw exception if that fails:
          if (isset($this->components[$i])) {
            $name = $this->components[$i]->getName();
          } else {
            throw new CTE_Engine_Template_Contextual_Exception(
              $this,
              'missing plugin name'
            );
          }
          
          $result = $this->buildSourcePath($name, self::SOURCE_RUNTIME_PLUGIN);
          
          // We're expecting keys for the plugin properties too:
          $simpleFrom = $i+1;
          $componentsSatisfied = false;
          
          break 2;
        
        case 'foreach':
        case 'section': // Fix evaluation ??                                     ###
          // Go to next index, which should be pointing at a section/foreach id:
          $i++;
          
          // Try to fetch section/foreach id or throw exception if that fails:
          if (isset($this->components[$i])) {
            $id = $this->components[$i]->getName();
          } else {
            throw new CTE_Engine_Template_Contextual_Exception(
              $this,
              "missing $component id"
            );
          }
          
          // Fetch current template process id:
          $pId = CTE_Engine_Environment::
            getInstance()->
            getCurrentTemplateProcess()->
            getId();
          
          // Check if ID exists and throw exception if not:
          if ($component == 'foreach') {
            $idOk = CTE_Engine_Expression_Tag_Foreach::idIsAvail($pId, $id);
          } else {
            $idOk = CTE_Engine_Expression_Tag_Section::idIsAvail($pId, $id);
          }
          if (!$idOk) {
            throw new CTE_Engine_Template_Contextual_Exception(
              $this,
              "invalid $component id: $id"
            );
          }
          
          $result = $this->buildSourcePath(
            $id,
            constant('self::SOURCE_RUNTIME_'.strtoupper($component))
          );
          
          // We're expecting keys for the section properties too:
          $simpleFrom = $i+1;
          $componentsSatisfied = false;
          
          break 2;
          
        default:
          throw new CTE_Engine_Template_Contextual_Exception(
            $this,
            "invalid variable path component: $component"
          );
      }
    }
    
    // Number of remaining components to interpret:
    $remaining = $components_n - $simpleFrom;
    
    // Push on additional keys:
    if ($remaining > 0) {
      for ($i=$simpleFrom; $i<$components_n; $i++) {
        $result .= $this->components[$i]->assemble($evaluate);
      }
    } else if(!$componentsSatisfied) {
      // We have no components remaining and we aren't satisfied:
      throw new CTE_Engine_Template_Contextual_Exception(
        $this,
        'missing required key(s)'
      );
    }
    
    return $result;
  }

  /**
   * Returns variable value if it is of the imported type or a system var,
   * otherwise an empty string will be returned.
   * 
   * Works great, although we have to handle compile-time plugins too..........  ### 2007-01-03
   * 
   * @see CTE_Engine_Expression_Evaluable_Interface::evaluate()
   * @return string
   * 
   * @todo test functionality, especially when aliasing something (070110).
   */
  public function evaluate($inline)
  {
    // Just evaluate once:
    if (!is_null($this->evaluated)) {
      return $this->evaluated;
    }
    
    // Check if we're aliasing something and return the evaluated expression
    // in here:
    if ($this->isExpressionAlias()) {
      
      // Check if we're aliasing a variable:
      if ($this->isVariableAlias()) {
        // Push all our components onto the variable:
        $this->aliasedExpression->importComponentList($this->components);
      }
      
      return $this->aliasedExpression->evaluate($inline);
    }
    
    // We can't evaluate non-initial sources, unless it's an evaluable magic
    // source:
    if ($this->source != self::SOURCE_INITIAL) {
      
      // It might be a system variable:
      if ($this->isSystemVar()) {
        return $this->interpretSystemVariable(true);
      }
      
      // Evaluation is illegal for other vars:
      throw new CTE_Engine_Template_Contextual_Exception(
        $this,
        'runtime variables cannot be evaluated during compilation'
      );
    }
    
    // Get variable registry instance:
    $varReg = CTE_Engine_Environment::getInstance()->getVariableRegistry();
    
    // Assemble possible components:
    $path = '';
    for ($i=0, $ii=count($this->components); $i<$ii; $i++) {
      $path .= $this->components[$i]->assemble(true);
    }
    
    // Check that the variable exists:
    if (!$varReg->varExists($this->basename)) {
      throw new CTE_Engine_Template_VariableNotFoundException(
        $this,
        "variable //{$this->basename}// does not exist"
      );
    }
    
    // Get variable root reference:
    $var = $varReg->getVarRef($this->basename);
    
    $evalCode = "return \$var$path;";
    
    // Evaluate result:
    $result = @eval($evalCode);
    
    // Check that everything went ok:
    if ($result === false) {
      throw new CTE_Engine_Expression_Exception("eval() of '$evalCode' failed");
    }
    
    // Cache result:
    $this->evaluated = $result;
    
    return $result;
  }

  /**
   * @see CTE_Engine_Expression_Evaluable_Interface::isStatic()
   */
  public function isStatic()
  {
    return ($this->isExpressionAlias() && $this->aliasedExpression->isStatic());
  }
  
  /**
   * @see CTE_Engine_Expression_Compilable_Interface::compile()
   */
  public function compile($concatContext = false)
  {
    // Only compile once:
    if (!is_null($this->compiled)) {
      return $this->compiled;
    }
    
    // The compiled result:
    $compiled = '';
    
    if ($this->isSystemVar()) {
      // We're compiling a system variable (the "$cte" template var):
      $compiled = $this->interpretSystemVariable(false);
    } else {
      
      // Check if we've aliased any external expression:
      if ($this->isExpressionAlias()) {
        $compiled = $this->aliasedExpression->compile($concatContext);
        
        // If we're not aliasing a variable expression, just compile it
        // and return it wrapped.
        if (!$this->isVariableAlias()) {
          // Cache and return compiled expression:
          $this->compiled = $this->assemble($compiled);
          return $this->compiled;
        }
        
        // ... otherwise use the base of the aliased variable instead of ours.
      
      // No special treatment; just a simple variable:
      } else {
        $compiled = $this->buildSourcePath($this->basename);
      }
      
      // Assemble possible components:
      for ($i=0, $ii=count($this->components); $i<$ii; $i++) {
        $compiled .= $this->components[$i]->assemble();
      }
    }
    
    // Cache result:
    $this->compiled = $this->assemble($compiled);
    
    return $this->compiled;
  }
}
?>