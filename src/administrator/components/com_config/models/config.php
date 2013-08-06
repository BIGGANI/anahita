<?php

/** 
 * LICENSE: ##LICENSE##
 * 
 * @category   Anahita
 * @package    Com_Config
 * @subpackage Controller
 * @author     Arash Sanieyan <ash@anahitapolis.com>
 * @author     Rastin Mehr <rastin@anahitapolis.com>
 * @copyright  2008 - 2010 rmdStudio Inc./Peerglobe Technology Inc
 * @license    GNU GPLv3 <http://www.gnu.org/licenses/gpl-3.0.html>
 * @version    SVN: $Id$
 * @link       http://www.anahitapolis.com
 */

/**
 * Config Controller
 * 
 * @category   Anahita
 * @package    Com_Config
 * @subpackage Controller
 * @author     Arash Sanieyan <ash@anahitapolis.com>
 * @author     Rastin Mehr <rastin@anahitapolis.com>
 * @license    GNU GPLv3 <http://www.gnu.org/licenses/gpl-3.0.html>
 * @link       http://www.anahitapolis.com
 */
class ComConfigModelConfig 
{
    /**
     * Site path
     *
     * @var string
     */
    protected $_site_path;
    
    /**
     * Configuration key map
     *
     * @var array
     */
    protected $_key_map = array(
            'database_type'      => 'dbtype',
            'database_host'      => 'host',
            'database_user'      => 'user',
            'database_password'  => 'password',
            'database_name'      => 'db',
            'database_prefix'    => 'dbprefix',
            'enable_debug'       => 'debug',
            'enable_caching'     => 'caching',
            'url_rewrite'        => 'sef_rewrite',
            'cache_lifetime'     => 'cachetime',
            'session_lifetime'   => 'lifetime',
            'offline',
            'secret',
            'error_reporting',
            'session_handler',
            'cache_handler'
    );
    
    /**
     * Configuration data
     *
     * @var array
    */
    protected $_data = array();
    
    /**
     * Configuration file
     *
     * @var string
    */
    protected $_configuration_file;
    
    /**
     * Creates a configuration from a configuration.php file
     *
     * @param string $site_path
     */
    public function __construct($site_path)
    {
        $this->_site_path = $site_path;
        $map = array();
        foreach($this->_key_map as $key => $value)
        {
            if ( is_numeric($key) ) {
                $key = $value;
            }
            $map[$key] = $value;
        }
        $this->_key_map = $map;
        $this->_data = array(                
                'debug_lang'   => 0,
                'mailer'       => 'mail',
                'mailfrom'     => '',
                'fromname'     => '',
                'sendmail'     => '/usr/sbin/sendmail',
                'smtpauth'     => '0',
                'smtpuser'     => '',
                'smtppass'     => '',
                'smtphost'     => 'localhost',
                'smtpsecure'   => 0,
                'smtpport'     => '',
                'force_ssl'    => 0,
                'log_path'     => $site_path.'/log',
                'tmp_path'     => $site_path.'/tmp',                
                'sitename'        => 'Anahita',
                'editor'          => 'tinymce',
                //'memcache_settings' => array(),
        );
    
        $this->set(array(
                'secret'           => '',
                'offline'          => 0,
                'enable_debug'     => 0,
                'cache_lifetime'   => 60,
                'session_lifetime' => 1440,
                'error_reporting' => 0,
                'enable_caching'  => 1,
                'url_rewrite'     => 0,
                'session_handler'    => function_exists('apc_fetch') ? 'apc' : 'database',
                'cache_handler'      => function_exists('apc_fetch') ? 'apc' : 'file'
        ));
    
        $this->_configuration_file = $site_path.'/configuration.php';
        if ( file_exists($this->_configuration_file) )
        {
            $classname = 'JConfig'.md5(uniqid());
            $content   = file_get_contents($this->_configuration_file);
            $content   = str_replace('JConfig', $classname, $content);
            $content   = str_replace(array('<?php',''), '', $content);
            $classname = '\\'.$classname;
            $return = @eval($content);
            if ( class_exists($classname) )
            {
                $config = new $classname;
                $this->_data = array_merge($this->_data, get_object_vars($config));
            }
        }
        $this->database_type = 'mysqli';
    }
    
    /**
     * Check if the configuation file exist
     *
     * @return boolean
     */
    public function isConfigured()
    {
        return file_exists($this->_configuration_file );
    }
    
    /**
     * Sets the keys that make the debug on for a site
     */
    public function enableDebug()
    {
        $this->set(array(
                'error_reporting' => E_ALL,
                'enable_debug'    => 1,
        ));
    }
    
    /**
     * Disable debug
     */
    public function disableDebug()
    {
        $this->set(array(
                'error_reporting' => 0,
                'enable_debug'    => 0,
        ));
    }
    
    /**
     * Set a configuration key/value
     *
     * @param string|array $key
     * @param string $value
     *
     * @return void.
     */
    public function set($key ,$value = null)
    {
        if ( is_array($key) )
        {
            foreach($key as $k => $v) {
                $this->$k = $v;
            }
        } else {
            $this->$key = $value;
        }
    }
    
    /**
     * Return a configuraiton value
     *
     * @param string $key
     *
     * @return Ambigous <NULL, multitype:>
     */
    public function __get($key)
    {
        if ( isset($this->_key_map[$key]) )
        {
            $key = $this->_key_map[$key];
        }
        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }
    
    /**
     * Set a configuration value. For setting array. We can use [val1,val2]
     *
     * @param string $key
     * @param string $value
     */
    public function __set($key , $value)
    {
        $matches = array();
        if ( preg_match('/^\[(.*?)\]$/', $value, $matches) ) {
            $value = explode(',', $matches[1]);
        }
    
        if ( isset($this->_key_map[$key]) ){
            $key = $this->_key_map[$key];
        }
        if ( $key == 'host' ) {
            $value = str_replace(':3306', '', $value);
        }
        if ( $key == 'dbprefix' ) {
            $value = str_replace('_', '', $value).'_';
        }
        $this->_data[$key] = $value;
    }
    
    /**
     * Set configuration database info
     *
     * @param array $data
     *
     * @return void
     */
    public function setDatabaseInfo($data)
    {
        $data['host'] = $data['host'].':'.$data['port'];
        unset($data['port']);
        $keys = array_map(function($key) {
            return 'database_'.$key;
        }, array_keys($data));
        $data = array_combine($keys, array_values($data));
        $this->set($data);
    }
    
    /**
     * Get the database info
     *
     * @return array
     */
    public function getDatabaseInfo()
    {
        $parts = explode(':', $this->database_host);
    
        return array(
                'host'      => $parts[0],
                'port'      => isset($parts[1]) ? $parts[1] : '3306',
                'user'      => $this->database_user,
                'password'  => $this->database_password,
                'name'      => $this->database_name,
                'prefix'    => $this->database_prefix
        );
    }
    
    /**
     * Retunr the data
     *
     * @return arary
     */
    public function toData()
    {
        return $this->_data;
    }
    
    /**
     * Save the configuration into the file
     *
     * @return string
     */
    public function save()
    {
        $data   = $this->toData();
        $content     = '';
        $f = fopen($this->_configuration_file, 'w') or die("can't open file");
        $fwrite       = function($string) use ($f) {
            fwrite($f, $string);
        };
        $fwrite("<?php\n");
        $fwrite("class JConfig {\n\n");
        $print_array = function($array) use (&$print_array) {
            if ( is_array($array) )
            {
                $values = array();
                $hash   = !is_numeric(key($array));
                foreach($array as $key => $value)
                {
                    if ( !is_numeric($key) ) {
                        $key = "'".addslashes($key)."'";
                    }
                    if ( !is_numeric($value) ) {
                        $value = "'".addslashes($value)."'";
                    }
                    $values[] = $hash ? "$key=>$value" : $value;
                }
                return 'array('.implode(',', $values).')';
            }
        };
        $write = function($data) use($fwrite, $print_array)
        {
            foreach($data as $key => $value)
            {
                if ( is_array($value) ) {
                    $value = $print_array($value);
                }
                elseif ( !is_numeric($value) ) {
                    $value = "'".addslashes($value)."'";
                }
                $fwrite("   var \$$key = $value;\n");
            }
        };
        $write_group = function($keys, $comment = null)
        use (&$data, $fwrite, $write)
        {
            $values = array();
            foreach($keys as $key)
            {
                if (isset($data[$key])) {
                    $values[$key] = $data[$key];
                    unset($data[$key]);
                }
            }
            if ( !empty($values) )
            {
                if ( !empty($comment) ) {
                    $fwrite("   /*$comment*/\n");
                }
                $write($values);
                $fwrite("\n");
            }
        };
        $write_group(array('offline','sitename','editor'), 'Site Settings');
        $write_group(array('dbtype','host','user','password','db','dbprefix'), 'Database Settings');
        $write_group(array('secret','error_reporting','tmp_path','log_path','force_ssl'), 'Server Settings');
        $write_group(array('lifetime','session_handler'), 'Session Settings');
        $write_group(array('mailer','mailfrom','fromname','sendmail','smtpauth','smtpuser','smtppass','smtphost','smtpport','smtpsecure'), 'Mail Settings');
        $write_group(array('caching','cachetime','cache_handler'), 'Cache Settings');
        $write_group(array('debug','debug_lang'), 'Debug Settings');
        $write_group(array('sef_rewrite'), 'Route Settings');
        $write_group(array_keys($data),'Other configurations');
        $fwrite("}");
        fclose($f);
    }    
}