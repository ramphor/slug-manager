<?php
namespace Ramphor\Slug\Manager;

if (!defined('ABSPATH')) {
    exit('Are you cheating huh?');
}

class Installer
{
    protected static $configFile;

    protected static function getPublicPostTypes()
    {
        $publicPostTypes = get_post_types(array(
            'public' => true,
        ), 'objects');

        array_filter($publicPostTypes, function ($args) {
            return $args->rewrite !== false;
        });

        // Remove post and page post type
        unset(
            $publicPostTypes['post'],
            $publicPostTypes['page'],
            $publicPostTypes['attachment']
        );

        return apply_filters('ramphor/slug/manager/post_types', $publicPostTypes);
    }

    protected static function getPublicTaxonomies()
    {
        $taxonomies = get_taxonomies(array(
            'public' => true,
        ), 'objects');

        array_filter($taxonomies, function ($args) {
            return $args->rewrite !== false;
        });

        return apply_filters('ramphor/slug/manager/taxonomies', $taxonomies);
    }

    protected static function generatePulicPostTypeConfigs()
    {
        $postTypeRules = array();
        $postTypes = static::getPublicPostTypes();

        foreach ($postTypes as $postType => $args) {
            $isRewrite = $args->rewrite !== false;
            $slug      = $isRewrite ? trim(array_get($args->rewrite, 'slug'), '/') : '';
            $rules = array(
                'registered_slug' => $isRewrite ? array_get($args->rewrite, 'slug') : '',
                'rewrite' => $isRewrite,
                'format' => sprintf('%s/%s', $slug ? '/' . $slug : '', '%postname%'),
            );
            $postTypeRules[$postType] = $rules;
        }

        return $postTypeRules;
    }

    protected static function generatePublicTaxonomiesConfigs()
    {
        $taxonomyRules = array();
        $taxonomies    = static::getPublicTaxonomies();

        foreach ($taxonomies as $taxonomy => $args) {
            $isRewrite = $args->rewrite !== false;
            $slug      = $isRewrite ? trim(array_get($args->rewrite, 'slug'), '/') : '';
            $rules = array(
                'registered_slug' => $isRewrite ? array_get($args->rewrite, 'slug') : '',
                'rewrite' => $isRewrite,
                'format' => sprintf('%s/%s', $slug ? '/' . $slug : '', '%slug%'),
            );
            $taxonomyRules[$taxonomy] = $rules;
        }

        return $taxonomyRules;
    }

    protected static function generateConfigFiles()
    {
        $configFile = Plugin::getRewriteRulesFilePath();
        if (file_exists($configFile)) {
            return;
        }

        $configs = array(
            'post_types' => static::generatePulicPostTypeConfigs(),
            'taxonomies' => static::generatePublicTaxonomiesConfigs(),
            'flushed' => false,
        );

        $writer = new ConfigWriter($configFile, $configs);
        $writer->write();
    }

    public static function active()
    {
        static::generateConfigFiles();
    }

    public static function deactive()
    {
    }
}
