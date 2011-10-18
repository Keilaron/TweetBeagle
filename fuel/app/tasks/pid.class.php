<?php      
/**
 * Determines in Windows or other OS whether the script is already running or not
 * @package CLI - process check
 * @author unreal4u - Camilo Sperberg
 * @author http://www.electrictoolbox.com/check-php-script-already-running/
 * @version 1.1
 * @license BSD
 */           
 
namespace Fuel\Tasks;
          
class pid {
  /**
   * The filename of the PID
   * @var string $filename   
   */     
  protected $filename = '';
  /**
   * Value of script already running or not
   * @var boolean $already_running      
   */     
  public $already_running = FALSE;
  /**
   * Returns the PID of the script
   * @var integer $pid   
   */     
  public $pid = 0;
  
  /**
   * The main function that does it all
   * @param string $directory The directory where the PID file goes to
   */    
  public function __construct($directory = '.') {
    $this->filename = $directory . '/' . basename($_SERVER['PHP_SELF']) . '.pid';
    if(is_writable($this->filename) || is_writable($directory)) {
      if(file_exists($this->filename)) {
        $this->pid = (int)trim(file_get_contents($this->filename));
        if (strtolower(substr(PHP_OS,0,3)) == 'win') {
          $wmi = new COM('winmgmts://');
          $processes = $wmi->ExecQuery('SELECT ProcessId FROM Win32_Process WHERE ProcessId = \''.$this->pid.'\''); 
          if (count($processes) > 0) {
            $i = 0;
            foreach($processes AS $a) $i++;
            if ($i > 0) $this->already_running = TRUE;
          }
        }
        else if(posix_kill($this->pid,0)) $this->already_running = TRUE;
      }
    }
    else die('Cannot write to pid file "'.$this->filename.'". Program execution halted.'."\n");
       
    if(!$this->already_running) {
      $this->pid = getmypid();
      file_put_contents($this->filename, $this->pid);
    }
    return $this->pid;
  }
  
  /**
   * Destroys the file if we must
   */
  public function __destruct() {
    if (is_writable($this->filename) AND !$this->already_running) unlink($this->filename);
    return TRUE;
  }
}
