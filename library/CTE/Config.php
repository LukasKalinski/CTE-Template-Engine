<?php
/**
 *
 *
 * @package CTE_Config
 * @since 2006-07-07
 * @version 2007-02-07
 * @copyright Cylab 2006-2007
 * @author Lukas Kalinski
 */

/**
 * CTE Config Class
 * 
 * Protected properties may be changed in sub-classes, private properties
 * must either be changed here or not at all. Some properties should be left
 * untouched.
 * 
 * It is important to remember that all directory paths are expected to end
 * with a directory separator. Ignoring this will cause unpredictable things
 * to happen.
 * 
 * @access public
 */
class CTE_Config
{
  const CACHE_FOREVER = -1;
  const CACHE_OFF = 0;
  
  /**
   * Debug is off: paranoid error messages.
   * 
   * @var int
   */
  const DEBUG_OFF = 0;
  
  /**
   * Debug is on: error messages will be descriptive and presented in HTML.
   * 
   * @var int
   */
  const DEBUG_BROWSER = 1;
  
  /**
   * Debug is on: error messages will be descriptive and formatted for command
   * line.
   * 
   * @var int
   */
  const DEBUG_CLI = 2;
  
  /**
   * Debug is on: an exception will be thrown instead of displaying an error
   * message.
   * 
   * @var int
   */
  const DEBUG_EXCEPTION = 3;
  
  /**
   * CTE Developer Mode
   * 
   * @var int
   */
  const DEVMODE_CTE = 1;
  
  /**
   * Template Developer Mode
   * 
   * @var int
   */
  const DEVMODE_TPL = 2;
  
  /**
   * Default caching time in seconds. Special values are 
   * self::CACHE_FOREVER and self::CACHE_OFF. Otherwise only
   * positive integers will be accepted.
   * 
   * @var int
   */
  protected $defaultCacheMode = self::CACHE_OFF;
  
  /**
   * Debug mode.
   * 
   * If other than self::DEBUG_OFF, we'll be in debug mode and all errors will
   * be displayed appropriately instead of logged.
   * 
   * @var int
   */
  protected $debugMode = self::DEBUG_BROWSER;
  
  /**
   * Developer Mode
   * 
   * @var int
   */
  protected $devMode = self::DEVMODE_CTE;
  
  /**
   * Max size of error log in bytes.
   * 
   * @var int
   */
  protected $maxErrorLogSize = 100000;
  
  /**
   * Absolute path to error presentation file. If changing this and replacing the file,
   * make sure you follow the specifications defined in the default
   * error file.
   * 
   * @var string
   */
  protected $errorPresFile;
  
  /**
   * Compiler output filters by name.
   * 
   * @var array
   */
  protected $outputFilters = array();
  
  /**
   * Name of the project associated with this config.
   * 
   * @var string
   */
  private $projectName;
  
  /**
   * The root of the CTE.php file and all relevant CTE root directories,
   * such as the system and the template directory.
   * 
   * @var string
   */
  private $cteRoot;
  
  /**
   * Absolute path to the CTE logs direcotry.
   * 
   * @var string
   */
  private $logsRoot;
  
  /**
   * Absolute path to the CTE resources directory (i.e. icons, etc.).
   * 
   * @var string
   */
  private $resourcesRoot;
  
  /**
   * The library root directory. Contains the whole class structure (the code).
   * 
   * @var string
   */
  private $libRoot;

  /**
   * Root to CTE plugins.
   * 
   * @var string
   */
  private $pluginRoot;
  
  /**
   * CTE Plugin class prefix.
   * 
   * @var string
   */
  private $pluginClassPrefix = 'CTE_Plugin_';

  /**
   * Templates root (file resources, see CTE_DataAccess_File).
   * 
   * @var string
   */
  protected $tplRoot;
  
  /**
   * Subtemplates root (file resources, see CTE_DataAccess_File).
   * 
   * @var string
   */
  protected $stplRoot;

  /**
   * Compiled templates root.
   * 
   * @var string
   */
  protected $tplcRoot;
  
  /**
   * Root of cached PHP files.
   * 
   * @var string
   */
  protected $cacheRoot;
  
  /**
   * Indicates wether we want a compile stamp on top of every template or not.
   *
   * @var bool
   */
  protected $compilerStamp = true;

  /**
   * Set to true if you want the template to be compiled every time it is
   * requested. This is only recommended during development.
   *
   * @var bool
   */
  protected $forceCompile = true;

  /**
   * If set to true, the user-imported variables will only be available in the 
   * root template. To make a variable available in a subtemplate it has to be
   * explicitly made using the tag arguments in a resource-print tag.
   * 
   * It is recommended to keep this on though.
   * 
   * @var bool
   */
  protected $strictVarScope = true;
  
  /**
   * Template tag start delimiter.
   * 
   * @var string
   */
  protected $exprLeftDelim = '{';
  
  /**
   * Template tag end delimiter.
   * 
   * @var string
   */
  protected $exprRightDelim = '}';

  /**
   * System template variable name
   * The value of this will represent a system variable in a template file.
   * Ie. value is set to 'SYS', then {$SYS.some_value_request} will be treated
   * as a system variable. Note that this is case-insensitive, so the names
   * Sys and sYs etc. will also be treated as a system var.
   *
   * @var string
   */
  protected $tplVarSys = 'cte';

  /**
   * Resource DSN <-> Class Name associations
   * 
   * @var array
   */
  private $resDsnClassAssoc = array(
    'file' => 'CTE_DataAccess_File'
  );

  /***************************************************************************\
   * Internal setup -->
   * DO NOT TOUCH UNLESS CTE DEVELOPER!
  \***************************************************************************/

  /**
   * Template variable source
   * The name of the variable containing all template variables.
   *
   * @var string
   */
  private $initVarsSource = '$this->v_initial';

  /**
   * Section source
   *
   * @var string
   */
  private $sectionSource = '$this->v_section';

  /**
   * Foreach holder
   *
   * @var string
   */
  private $foreachSource = '$this->v_foreach';

  /**
   * Plugin holder
   *
   * @var string
   */
  private $pluginSource = '$this->v_plugin';

  /**
   * Creates a new config object with default configuration.
   * This may be changed and used as the default configuration for a
   * specific project. Consider using child classes though when having multiple
   * projects though. Note that if $projectName is "My Project", the folder 
   * "my_project" will be assumed to exist in cte/projects.
   * 
   * @param string $projectName The name of the current project.
   */
  public function __construct($projectName)
  {
    $this->projectName = $projectName;
    
    // CTE Root, assuming this file is located 3 levels above it:
    $this->cteRoot        = dirname(dirname(dirname(__FILE__)))
                          . DIRECTORY_SEPARATOR;
    
    $this->logsRoot       = $this->cteRoot
                          . 'logs'
                          . DIRECTORY_SEPARATOR;
    
    $this->resourcesRoot  = $this->cteRoot
                          . 'resources'
                          . DIRECTORY_SEPARATOR;
    
    $this->libRoot        = $this->cteRoot
                          . DIRECTORY_SEPARATOR
                          . 'library'
                          . DIRECTORY_SEPARATOR;
    
    $this->pluginRoot     = $this->libRoot
                          . 'CTE'
                          . DIRECTORY_SEPARATOR
                          . 'Plugin'
                          . DIRECTORY_SEPARATOR;
    
    // Default project root (so we don't have to write it 4 times):
    $projectRoot          = $this->cteRoot
                          . 'projects'
                          . DIRECTORY_SEPARATOR
                          . strtolower(
                              str_replace(' ', '_', $this->projectName)
                            )
                          . DIRECTORY_SEPARATOR;
    
    $this->tplRoot        = $projectRoot
                          . 'tpl'
                          . DIRECTORY_SEPARATOR;
    
    $this->stplRoot       = $projectRoot
                          . 'stpl'
                          . DIRECTORY_SEPARATOR;
    
    $this->cacheRoot      = $projectRoot
                          . '~cache'
                          . DIRECTORY_SEPARATOR;
    
    $this->tplcRoot       = $projectRoot
                          . '~tpl_c'
                          . DIRECTORY_SEPARATOR;
    
    $this->errorPresFile  = $this->resourcesRoot
                          . 'error.php';
  }

  /**
   * Creates the path and filename of a compiled template, using the template's
   * relative path (relative to the template root).
   * 
   * @param string $tpl_rpath Path of template file, relative to templates root.
   * @return array containing all path components, that is: directories first
   *               and the filename last.
   */
  public function createCompiledFilePath($tpl_rpath)
  {
    // Tokenize path components:
    $pathComponents = explode(DIRECTORY_SEPARATOR, $tpl_rpath);
    
    $lastIndex = count($pathComponents) - 1;
    
    // Pop off the file name from path components, replacing .tpl with .php:
    $pathComponents[$lastIndex] = preg_replace('/\.tpl$/i',
                                               '.php',
                                               $pathComponents[$lastIndex]);
    
    return $pathComponents;
  }
  
  /**
   * Creates the cache dir-relative path to a cached (and compiled) template
   * file, using the template's relative path (relative to the template root).
   * 
   * @param string $tpl_rpath Path of template file, relative to templates root.
   * @return string
   */
  public function createCacheFilePath($tpl_rpath)
  {
    // Replace directory separators with "%":
    $filename = str_replace(DIRECTORY_SEPARATOR, '%', $tpl_rpath);
    
    // Replace the .tpl extension with .php extension:
    $filename = preg_replace('/\.tpl$/i', '.php', $filename);
    
    return $filename;
  }
  
  /**
   * Returns class name for a resource DSN.
   * 
   * @param string $dsn
   * @return string
   * @return null if no relation is found.
   */
  final public function resolveResourceClassName($dsn)
  {
    if (isset($this->resDsnClassAssoc[$dsn])) {
      return $this->resDsnClassAssoc[$dsn];
    }
    
    return null;
  }
  
  /**
   * Associates a resource DSN with a class name.
   * 
   * Example: 'lang' with 'Foo_Bar_Language', enabling use of {@lang[...]}.
   * 
   * @throws CTE_Exception if overwriting existing association.
   * @return void
   */
  final protected function setResourceClassNameAssoc($dsn, $className)
  {
    if (!is_null($this->resolveResourceClassName($dsn))) {
      throw new CTE_Exception(
        "resource-name-to-class association already exists for dsn=$dsn"
      );
    }
    
    $this->resDsnClassAssoc[$dsn] = $className;
  }
  
  /**
   * @return string
   */
  final public function getCacheRoot()
  {
    return $this->cacheRoot;
  }
  
  /**
   * @return bool
   */
  final public function inDebugMode()
  {
    return ($this->debugMode != self::DEBUG_OFF);
  }
  
  /**
   * Returns current debug mode, that is:
   * - self::DEBUG_OFF
   * - self::DEBUG_BROWSER
   * - self::DEBUG_CLI
   * - self::DEBUG_EXCEPTION
   * 
   * @return int
   */
  final public function getDebugMode()
  {
    return $this->debugMode;
  }
  
  /**
   * Returns current developer mode, that is:
   * - self::DEVMODE_TPL
   * - self::DEVMODE_CTE
   * 
   * @return int
   */
  final public function getDevMode()
  {
    return $this->devMode;
  }
  
  /**
   * @return int
   */
  final public function getDefaultCache()
  {
    return $this->defaultCacheMode;
  }
  
  /**
   * @return string
   */
  final public function getLogsRoot()
  {
    return $this->logsRoot;
  }
  
  /**
   * @return int
   */
  final public function getMaxErrorLogSize()
  {
    return $this->maxErrorLogSize;
  }
  
  /**
   * @return string
   */
  final public function getErrorPresFile()
  {
    return $this->errorPresFile;
  }
  
  /**
   * @return string
   */
  final public function getCteRoot()
  {
    return $this->cteRoot;
  }

  /**
   * @return string
   */
  final public function getPluginRoot()
  {
    return $this->pluginRoot;
  }

  /**
   * @return string
   */
  final public function getPluginClassPrefix()
  {
    return $this->pluginClassPrefix;
  }

  /**
   * Returns directory in which the compiled templates will be stored. If
   * we're implementing CTE_Config_LanguageInterface the compiled template
   * directory will have the language name appended.
   *
   * @return string
   */
  final public function getTplcRoot()
  {
    if ($this instanceof CTE_Config_LanguageInterface) {
      return $this->tplcRoot . $this->getLanguage() . DIRECTORY_SEPARATOR;
    } else {
      return $this->tplcRoot;
    }
  }

  /**
   * @return string
   */
  final public function getTplRoot()
  {
    return $this->tplRoot;
  }

  /**
   * @return string
   */
  final public function getStplRoot()
  {
    return $this->stplRoot;
  }

  /**
   * Returns true if strict variable scoping is on.
   *
   * @return bool
   */
  final public function strictVarScopeOn()
  {
    return $this->strictVarScope;
  }
  
  /**
   * @return string
   */
  final public function doAddCompilerStamp()
  {
    return $this->compilerStamp;
  }

  /**
   * @return string
   */
  final public function doForceCompile()
  {
    return $this->forceCompile;
  }

  /**
   * @return string
   */
  final public function getTagStartDelim()
  {
    return $this->exprLeftDelim;
  }

  /**
   * @return string
   */
  final public function getTagEndDelim()
  {
    return $this->exprRightDelim;
  }

  /**
   * @return string
   */
  final public function getTplVarNameSys()
  {
    return $this->tplVarSys;
  }

  /**
   * @return string
   */
  final public function getTplVarNameLang()
  {
    return $this->tplVarLang;
  }

  /**
   * Returns a string representation of the associative array in which all
   * initially created variables will be stored.
   *
   * Example: Vars are stored in $this->foo, then we return:
   * the string: $this->foo
   *
   * @return string
   */
  final public function getInitVarSourceString()
  {
    return $this->initVarsSource;
  }
  
  /**
   * @return string
   */
  final public function getSectionSourceString()
  {
    return $this->sectionSource;
  }

  /**
   * @return string
   */
  final public function getForeachSourceString()
  {
    return $this->foreachSource;
  }

  /**
   * @return string
   */
  final public function getPluginSourceString()
  {
    return $this->pluginSource;
  }
}
?>