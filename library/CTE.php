<?php
/**
 * CyLab Template Engine (CTE)
 *
 * @package CTE
 * @since 2005-01-11
 * @version 4.0 alpha / 2007-07-04
 * @copyright Cylab 2005-2007
 * @author Lukas Kalinski
 */

/**
 * Cylab Template Engine
 * Main class for template handling.
 * 
 * @access public
 * 
 * @todo CACHING: send headers telling "not modified" if a cache is used.
 */
class CTE
{
  /**
   * Current CTE version.
   * 
   * @var string
   */
  const VERSION = '4.0 alpha'; // Remember to change this .....................  ###

  /**
   * The config file to use in the whole CTE context.
   * 
   * @var CTE_Config
   */
  private $config;

  /**
   * Caching time in seconds, default is set to whatever
   * {@link CTE_Config::$defaultCacheMode} is set to.
   * 
   * @var int
   */
  private $cache;
  
  /**
   * Section data will be stored here.
   * 
   * @var array
   */
  private $v_section = array();

  /**
   * Plugin data will be stored here.
   * 
   * @var array
   */
  private $v_plugin = array();

  /**
   * Template variables will be stored here.
   *
   * @var aArray
   */
  private $v_initial = array();

  /**
   * Creates a new instance of the CTE main class. To use an own subclass of 
   * CTE_Config, an instance of that class may be supplied here, otherwise 
   * the default CTE_Config class will be used.
   * 
   * @param CTE_Config $config (null)
   */
  public function __construct(CTE_Config $config = null)
  {
    // Initialize automatic class loading:
    require 'CTE/ClassLoader.php';
    spl_autoload_register(array('CTE_ClassLoader', 'load'));
    
    if(!is_null($config)) {
      $this->config = $config;
    } else {
      $this->config = new CTE_Config('sample_project');
    }
    
    $this->cache = $config->getDefaultCache();
  }

  /**
   * Compiles if required and includes template with path $template.
   * 
   * @param string $template
   * @throws CTE_Exception
   * @return bool
   */
  private function loadTemplate($template, $forceCompile = false)
  {
    // Build raw template file path:
    $tpl_path = $this->config->getTplRoot()
              . $template;

    // Build compiled file path:
    $tplc_path = $this->config->getTplcRoot()
               . implode($this->config->createCompiledFilePath($template));

    // Recompile if ...
    // - We're forcing a compile or ...
    // - There is no compiled file or ...
    // - The template has been changed.
    if ($forceCompile ||
        $this->config->doForceCompile() ||
        !file_exists($tplc_path) ||
        filectime($tplc_path) < filectime($tpl_path)) {
      $engine = new CTE_Engine($this->config, $this->v_initial);
      $engine->setCache($this->cache);
      try {
        $resource = $engine->createFileResource();
        $engine->build($resource, $template);
      } catch (CTE_Exception $e) {
        $error = new CTE_Error($this->config, $e);
        $error->trigger();
      }
      
      // Cache PHP file, if caching not turned off:
      if ($this->cache != CTE_Config::CACHE_OFF) {
        include $this->doCache($template);
      } else {
        include $tplc_path;
      }
    // No compilation was needed:
    } else {
      $cacheFile = $this->config->getCacheRoot()
                 . $this->config->createCacheFilePath($template);
      
      // Start output buffering (we want be able to discard an expired
      // cache file):
      ob_start();
      
      // Check if cache file is missing:
      if (!file_exists($cacheFile)) {
        // Include compiled template file and get it's return value:
        $inc_ret = include $tplc_path;
        
        // Check if we got a cache file return value:
        if (is_string($inc_ret) && is_numeric($inc_ret)) {
          // Since the cache file is gone, we're creating a new one:
          ob_end_clean();
          include($this->doCache($template));
        } else {
          // This file wasn't supposed to be cached.
          ob_end_flush();
        }
        
        return;
      }
      
      // A cache file has been found, now determine the file's cache value
      // and do the right stuff.
      
      $inc_ret = include $cacheFile;
      
      // Check that we got the expected return value:
      if (is_string($inc_ret) && is_numeric($inc_ret)) {
        $fileCache = (int) $inc_ret;
        
        // If file cache differs from our cache value, clean output buffer and
        // make a recursive call with $forceCompile set to true:
        if ($fileCache != $this->cache) {
          // Cache mode changed, recompile template:
          ob_end_clean();
          $this->loadTemplate($template, true);
        // Check if file is cached forever:
        } else if ($fileCache == CTE_Config::CACHE_FOREVER) {
          // Flush output buffer and return:
          ob_end_flush();
        // Check if we have a cache expire time:
        } else if ($fileCache > 0) {
          // Check if the cached file has expired:
          if (time() > (filectime($cacheFile) + $fileCache)) {
            // Clean output buffer, recache file, include it again and return:
            ob_end_clean();
            $this->doCache($template);
            include($cacheFile);
          // Otherwise no expiration:
          } else {
            // Flush the output buffer and return:
            ob_end_flush();
          }
        } else {
          throw new CTE_Exception('file returned invalid cache mode');
        }
      // Otherwise an invalid value was returned from cache file:
      } else {
        throw new CTE_Exception(
          'inclusion of template gave unexpected return value'
        );
      }
    }
  }

  /**
   * Sets caching time in seconds, or the special values
   * {@link CTE_Config::CACHE_OFF} or {@link CTE_Config::CACHE_FOREVER}.
   * Other values are invalid.
   * 
   * @param int $cache Cache time in seconds, or special value, as defined in 
   *                   {@link CTE_Config}.
   * @throws CTE_Exception on invalid time value.
   * @return void
   */
  public function setCache($cache)
  {
    if ($cache < 1 &&
        $cache != CTE_Config::CACHE_FOREVER &&
        $cache != CTE_Config::CACHE_OFF) {
      throw new CTE_Exception("invalid cache value: $cache");
    }
    
    $this->cache = $cache;
  }
  
  /**
   * Removes cache for template $template.
   * 
   * @param string $template Relative path to template.
   * @throws CTE_Exception if the file permissions are insufficient.
   * @return bool True if a file was removed, false otherwise.
   */
  public function clearCache($template)
  {
    $cacheFile = $this->config->getCacheRoot()
               . $this->config->createCacheFilePath($template);
    
    if (!file_exists($cacheFile)) {
      return false;
    }
    
    // Make sure we're allowed to remove the cached file:
    if (!is_writeable($cacheFile)) {
      throw new CTE_Exception(
        'insufficient permissions for cache file removal'
      );
    }
    
    unlink($cacheFile);
    
    return true;
  }
  
  /**
   * Caches a compiled template file. That means that all PHP will be evaluated
   * into a cache file (except for explicitly non-cache instructions though).
   * That cache file will then be included instead of the compiled template.
   * 
   * @param string $template
   * @return string Full path to cached file.
   */
  private function doCache($template)
  {
    // Build compiled file path:
    $tplc_path = $this->config->getTplcRoot()
               . implode(DIRECTORY_SEPARATOR,
                         $this->config->createCompiledFilePath($template));
    
    $cache_root = $this->config->getCacheRoot();
    
    ob_start();
    include($tplc_path);
    $cached = ob_get_clean();
    
    // Path to cache file(will evolve below):
    $path = $this->config->getCacheRoot();
    
    // Make sure we're allowed to write to cache root:
    if (!is_writeable($path)) {
      throw new CTE_Exception("failed to write to cache root");
    }
    
    $path .= $this->config->createCacheFilePath($template);
    
    // Make sure we're allowed to overwrite an existing file:
    if (file_exists($path) && !is_writeable($path)) {
      throw new CTE_Exception("failed to write to cache file");
    }
    
    file_put_contents($path, $cached);
    
    return $path;
  }
  
  /**
   * Validates a variable name and throws different exceptions if the name is
   * invalid for some reason.
   * 
   * @param string $name Name of variable.
   * @throws CTE_Exception
   * @return void
   */
  private function validateTplVarName($name)
  {
    // Check name validity:
    if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $name)) {
      throw new CTE_Exception(
        "invalid characters found in variable name: \"$name\""
      );
    }

    // Check that we're not overwriting reserved variable names:
    if($name == $this->config->getTplVarNameSys()) {
      throw new CTE_Exception(
        "invalid variable name: name \"$name\" is reserved"
      );
    }
  }

  /**
   * Creates a template variable with value $value.
   *
   * @param string $name Name of the variable.
   * @param mixed $value Value of the variable.
   * @throws CTE_Exception
   * @return void
   */
  public function createVar($name, $value)
  {
    $this->validateTplVarName($name);
    $this->v_initial[$name] = $value;
  }

  /**
   * Creates a template variable storing a reference of some other variable.
   *
   * @param string $name
   * @param mixed &var_ref
   * @throws CTE_Exception
   * @return void
   */
  public function registerVar($name, &$var_ref)
  {
    $this->validateTplVarName($name);
    $this->v_initial[$name] = &$var_ref;
  }

  /**
   * Displays a template.
   *
   * @param string $template
   * @return void
   */
  public function display($template)
  {
    $this->loadTemplate($template);
  }
}
?>