<?php
namespace Ramphor\Slug\Manager;

if (!defined('ABSPATH')) {
    exit('Are you cheating huh?');
}

class Plugin
{
    protected static $instance;

    protected $rewriteRules;

    const FILE_NAME_OPTION_KEY = 'ramphor_url_rewrite_rules_file_name';

    protected function __construct()
    {
        $this->bootstrap();
        $this->initHooks();
    }

    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function bootstrap()
    {
        $configFile = static::getRewriteRulesFilePath();
        if (!file_exists($configFile)) {
            return;
        }

        $configs = require $configFile;
        if (is_array($configs) && isset($configs['flushed'])) {
            $this->rewriteRules = $configs;
        }
    }

    public function initHooks()
    {
        register_activation_hook(RAMPHOR_SLUG_MANAGER_PLUGIN_FILE, [Installer::class, 'active']);
        register_deactivation_hook(RAMPHOR_SLUG_MANAGER_PLUGIN_FILE, [Installer::class, 'deactive']);

        add_action('init', [$this, 'init']);
    }

    public function init()
    {
        $this->flushRewriteRules();
    }

    public static function getRewriteRulesFileName()
    {
        $fileName = get_option(static::FILE_NAME_OPTION_KEY, null);
        if (is_null($fileName)) {
            $hash = hash('sha256', get_option('admin_email') . '_' . microtime());
            $fileName = sprintf('rewrite-rules-%s.php', substr($hash, 0, 8));
            update_option(static::FILE_NAME_OPTION_KEY, $fileName);
        }
        return $fileName;
    }

    public static function getRewriteRulesFilePath()
    {
        $upload_dir = wp_get_upload_dir();
        return sprintf(
            '%s/%s',
            array_get($upload_dir, 'basedir'),
            static::getRewriteRulesFileName()
        );
    }


    public function flushRewriteRules()
    {
        if (isset($this->rewriteRules['flushed']) && $this->rewriteRules['flushed'] === false) {
            flush_rewrite_rules(true);

            $this->rewriteRules['flushed'] = true;

            $configWriter = new ConfigWriter(static::getRewriteRulesFilePath(), $this->rewriteRules);
            $configWriter->write();
        }
    }
}
