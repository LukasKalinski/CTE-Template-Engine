<?php
/**
 *
 *
 * @package CTE_Engine
 * @since 2006-08-15
 * @version 2007-01-27
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * Plugin Manager Class
 * 
 * @access private
 */
class CTE_Engine_PluginManager
{
  const P_MODIFIER = 1;
  
  /**
   * @var CTE_Config
   */
  private $config;
  
  /**
   * Contains all PHP instructions to be inserted into the beginning of the
   * compiled template.
   * 
   * @var array
   */
  private $phpInstructions = array();
  
  /**
   * Contains list of classnames of used modifiers.
   * 
   * @var array
   */
  private $registry = array(
    self::P_MODIFIER => array()
  );
  
  /**
   * Constructor
   * 
   * @param CTE_Config $config
   */
  public function __construct(CTE_Config $config)
  {
    $this->config = $config;
  }
  
  /**
   * Returns list of PHP instructions created by current instance.
   * 
   * @return array
   */
  public function getFlushPhpInstructions()
  {
    $ret = $this->phpInstructions;
    $this->phpInstructions = array();
    return $ret;
  }
  
  /**
   * Returns true if plugin of type $type and name $name is registered.
   * 
   * @param int $type
   * @param string $name
   * @return bool
   */
  private function pluginRegistered($type, $name)
  {
    return isset($this->registry[$type][$name]);
  }
  
  /**
   * Loads plugin class file.
   * 
   * @param string $className
   * @throws CTE_Engine_Exception
   * @return void
   */
  private function loadPluginClass($className)
  {
    // Try to resolve class and throw CTE exception on failure:
    try {
      Cylab::loadClass($className);
    } catch (Cylab_Exception $e) {
      throw new CTE_Engine_Exception(
        "failed to resolve class for plugin: $className"
      );
    }
  }
  
  /**
   * Appends a PHP instruction ($instr) on instructions stack. $instr will be
   * wrapped inside PHP tags.
   * 
   * @param string $instr
   * @return void
   */
  private function addPhpInstr($instr)
  {
    array_push($this->phpInstructions, "<?php $instr?>");
  }
  
  /**
   * Registers a modifier plugin.
   * 
   * @param string $name
   * @param bool $runtime Whether the plugin is to be used at runtime or not.
   * @return void
   */
  private function registerModifier($name, $runtime = true)
  {
    // Increment plugin counter and return modifier object variable if modifier
    // is already registered:
    if ($this->pluginRegistered(self::P_MODIFIER, $name)) {
      
      // Change runtime property to true if necessary:
      if (!$this->registry[self::P_MODIFIER][$name]['runtime'] && $runtime) {
        $this->registry[self::P_MODIFIER][$name]['runtime'] = true;
      }
    }
    
    // Create variable to hold modifier object:
    $varExpr = CTE_Engine_Expression_Variable::createInstance(
      $name,
      CTE_Engine_Expression_Variable::SOURCE_RUNTIME_MODPLUGIN
    );
    
    // Build variable reference string:
    $varRef = $varExpr->compile(); // replace with "generate variable" (see ticket #19) ...  ###
    
    // Build plugin class name:
    $className = $this->config->getPluginClassPrefix()
               . ucwords(strtolower($name));
    
    // Build and add PHP class file inclusion instruction:
    $this->addPhpInstr(
      'include_once(\'' . str_replace('_', '/', $className) . '.php\');'
    );
    
    // Build PHP class instantiation instruction:
    $this->addPhpInstr("$varRef=new $className();");
    
    $this->loadPluginClass($className);
    
    $this->registry[self::P_MODIFIER][$name] = array(
      // Store modifier call template:
      'template' => "{$varRef}->apply(&T,&A)",
      
      // Get and store plugin object:
      'object' => new $className(),
      
      // Runtime or not:
      'runtime' => $runtime
    );
  }
  
  /**
   * Prepares modifier plugin argument list.
   * 
   * @param array $format The format with required and default argument values.
   * @param array &$args Argument list.
   * @throws CTE_Engine_Exception if arguments are missing.
   * @return void
   */
  private function modPrepareArgs(array $format, array &$args)
  {
    for ($i=0, $ii=count($format); $i<$ii; $i++) {
      
      // Set argument defaults if argument not set:
      if (!isset($args[$i])) {
        
        if (!is_null($format[$i])) {
          $args[$i] = $format[$i];
        } else {
          // Missed default value, throwing exception.
          throw new CTE_Engine_Exception(
            'missing required modifier plugin argument: ' . ($i+1)
          );
        }
      }
    }
  }
  
  /**
   * Executes modifier on $target and returns the resulting output.
   * 
   * @param string $modifier Name of modifier.
   * @param string $target
   * @param array $args Array with arguments as expression objects.
   * @throws CTE_Engine_Exception if modifier is not registered.
   * @return string Result of applying modifier to $target.
   */
  public function modExec($modifier, $target, array $args)
  {
    $this->registerModifier($modifier, false);
    
    // Fetch modifier object instance:
    $mod = $this->registry[self::P_MODIFIER][$modifier]['object'];
    
    $this->modPrepareArgs($mod->argFormat(), $args);
    
    return $mod->apply($target, $args);
  }
  
  /**
   * Compiles modifier call string and returns it.
   * 
   * @param string $modifier Name of modifier.
   * @param string $target
   * @param array $args Array with arguments as expression objects.
   * @return string Compiled modifier call string.
   */
  public function modCompile($modifier, $target, array $args)
  {
    $this->registerModifier($modifier);
    $result = $this->registry[self::P_MODIFIER][$modifier]['template'];
    
    // Fetch modifier object instance:
    $mod = $this->registry[self::P_MODIFIER][$modifier]['object'];
    
    // Insert target:
    $result = str_replace('&T', $target, $result);
    
    // Prepare args:
    $this->modPrepareArgs($mod->argFormat(), $args);
    $args = 'array(' . implode(',', $args) . ')';
    
    // Insert args:
    $result = str_replace('&A', $args, $result);
    
    return $result;
  }
}
?>