<?php
namespace Ramphor\Slug\Manager;

use Exception;

class ConfigWriter
{
    protected $filePath;
    protected $configs;

    public function __construct($filePath, $configs)
    {
        $this->filePath = $filePath;
        $this->configs = $configs;
    }

    public function write()
    {
        try {
            if (file_exists($this->filePath)) {
                @unlink($this->filePath);
            }

            $h = fopen($this->filePath, 'w+');
            fwrite($h, '<?php' . PHP_EOL . PHP_EOL);
            fwrite($h, 'if (!defined(\'ABSPATH\')) {' . PHP_EOL);
            fwrite($h, "    exit('Are you cheating huh?');" . PHP_EOL);
            fwrite($h, '}' . PHP_EOL . PHP_EOL);
            fwrite($h, 'return ');
            fwrite($h, var_export($this->configs, true));
            fwrite($h, ';');
            fclose($h);
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }
}
