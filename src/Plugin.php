<?php
namespace Ramphor\Slug\Manager;

use Ramphor\Slug\Manager\Rewrite\PostRewrite;
use Ramphor\Slug\Manager\Rewrite\TaxonomyRewrite;

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

        add_action('init', [$this, 'rewrite'], 99);
        add_action('init', [$this, 'init'], 999);
    }

    public function rewrite()
    {
        if (empty($this->rewriteRules)) {
            return;
        }

        $postRewrite = new PostRewrite(array_get($this->rewriteRules, 'post_types'));
        $postRewrite->rewrite();

        $taxomyRewrite = new TaxonomyRewrite(array_get($this->rewriteRules, 'taxonomies'));
        $taxomyRewrite->rewrite();
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

    protected function convertUrlFormatToRegex($format)
    {
        $regex = str_replace(['/', '.'], ['\/', '\.'], $format);

        $regex = preg_replace('/\/\%(postname|slug)\%/', '(\/[^\/]*){1,}', $regex);
        $regex = preg_replace('/\%[^\%]+\%/', '[^\/]{1,}', $regex);
        if (strpos($regex, '\(\/') === 0) {
            $regex = substr($regex, 1);
        }

        $regex = str_replace('\(', '(', $regex);
        $regex = '/^' . $regex;

        return $regex . '$/';
    }

    protected function generateURLRegexRules($rules)
    {
        // Post type
        foreach (array_get($rules, 'post_types') as $post_type => $rule) {
            if (!isset($rule['format'])) {
                continue;
            }

            $rule['regex']     = $this->convertUrlFormatToRegex(array_get($rule, 'format'));
            $rules['post_types'][$post_type] = $rule;
        }

        // Post type
        foreach (array_get($rules, 'taxonomies') as $taxonomy => $rule) {
            if (!isset($rule['format'])) {
                continue;
            }

            $rule['regex']     = $this->convertUrlFormatToRegex(array_get($rule, 'format'));
            $rules['taxonomies'][$taxonomy] = $rule;
        }

        return $rules;
    }


    public function flushRewriteRules()
    {
        if (isset($this->rewriteRules['flushed']) && $this->rewriteRules['flushed'] === false) {
            flush_rewrite_rules(true);

            $this->rewriteRules['flushed'] = true;

            $configWriter = new ConfigWriter(
                static::getRewriteRulesFilePath(),
                $this->generateURLRegexRules($this->rewriteRules)
            );
            $configWriter->write();
        }
    }
}
