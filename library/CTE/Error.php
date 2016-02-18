<?php
/**
 *
 *
 * @package CTE
 * @since 2007-01-04
 * @version 2007-01-27
 * @copyright Cylab 2007
 * @author Lukas Kalinski
 */

/**
 * Class for error representation and display.
 * 
 * @access private
 */
class CTE_Error
{
  /**
   * @var CTE_Config
   */
  private $config;
  
  /**
   * @var aArray
   */
  protected $data = array();
  
  /**
   * @param CTE_Config $config
   * @param CTE_Exception $e
   */
  public function __construct(CTE_Config $config, CTE_Exception $e)
  {
    $this->config = $config;
    $this->data['message'] = $e->getMessage();
    
    // Append CTE-related debug data if CTE developer mode:
    if ($config->getDevMode() == CTE_Config::DEVMODE_CTE) {
      $this->data['stack_trace'] = $e->getTraceAsString();
      $this->data['php_file'] = $e->getFile();
      $this->data['php_line'] = $e->getLine();
    }
  }
  
  /**
   * Triggers the error in an appropriate way and exits if $exit is true.
   * 
   * @param bool $exit
   * @return void
   */
  public function trigger($exit = true)
  {
    if ($this->config->inDebugMode()) {
      $this->display($exit);
    } else {
      $this->log($exit);
    }
  }
  
  /**
   * Logs error and exits if $exit is set to true. Failures of this method
   * will cause very paranoid error messages if it failes, revealing almost 
   * nothing. In case of an internal failure it will also exit, ignoring the 
   * $exit argument.
   * 
   * @return void
   */
  private function log($exit = true)
  {
    $entry = date('Y-m-d H:i:s') . "\n";
    foreach ($this->data as $key => $value) {
      $name = strtoupper(str_replace('_', ' ', $key));
      $entry .= "$name: $value\n";
    }
    $entry .= "\n";
    
    $errorFile = $this->config->getLogsRoot() . __CLASS__ . '__error.log';
    
    // Check that we're allowed to write to the file path and that we haven't
    // exceeded max log file size:
    if (!is_writeable($this->config->getLogsRoot())) {
      exit('ERROR: Failed to write CTE error to file: permission denied.');
    } else if(file_exists($errorFile) &&
              filesize($errorFile) > $this->config->getMaxErrorLogSize()) {
      exit('ERROR: Failed to write CTE error to file: file size exceeded.');
    }
    
    // Write error to end of file:
    $f = fopen($errorFile, 'a');
    fwrite($f, $entry);
    fclose($f);
    
    if ($exit) {
      exit('An error occured [logged].');
    }
  }
  
  /**
   * Displays the error and exits if $exit is set to true and $debugMode
   * isn't set to {@link CTE_Config::DEBUG_EXCEPTION}.
   * 
   * @param bool $exit (true)
   * @return void
   */
  private function display($exit = true)
  {
    $debugMode = $this->config->getDebugMode();
    
    // Make sure this method is called only once:
    if (defined('INCLUDED_CTE_MISC_ERRORPRES')) {
      throw new CTE_Exception(
        'cannot call CTE_Error::display() more than once'
      );
    }
    
    if ($debugMode == CTE_Config::DEBUG_BROWSER) {
      header('Content-type: text/html');
      
      // Set the variable which will be used in the included file:
      $error = $this->data;
      
      @include($this->config->getErrorPresFile());
      
      // Throw exception if the inclusion failed:
      if (!defined('INCLUDED_CTE_MISC_ERRORPRES')) {
        throw new CTE_Exception('inclusion of error-display file failed');
      }
    } else if ($debugMode == CTE_Config::DEBUG_CLI) {
      echo "CTE Error\n";
      foreach ($this->data as $key => $value) {
        echo "$key: $value\n";
      }
    } else if ($debugMode == CTE_Config::DEBUG_EXCEPTION) {
      throw new CTE_Error_Exception($this->data);
    } else {
      throw new CTE_Exception('invalid debug mode');
    }
    
    if ($exit) {
      exit();
    }
  }
}
?>