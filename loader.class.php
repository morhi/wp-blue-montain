<?php

namespace BlueMountain;

use Timber\Menu;
use Timber\Timber;

class Loader
{
    /**
     * The theme base directory
     *
     * @var string
     */
    protected $themePath;

    /**
     * The path to the views directory
     *
     * @var string
     */
    protected $viewsPath;

    /**
     * The name of the current template
     *
     * @var null|string
     */
    protected $currentTemplate = NULL;

    /**
     * The template if the requested template does not exist
     *
     * @var string
     */
    protected $fallbackTemplate = 'fallback';

    /**
     * The default wordpress template hierarchy.
     *
     * Calls the corresponding getter (is_template) to check whether
     * this template should be shown. Renders this template, if it exists
     * under views/templates, otherwise looks for the next template.
     *
     * @var array
     */
    protected $templateHierarchy = [
        'embed',
        '404',
        'search',
        'front_page',
        'home',
        'post_type_archive',
        'tax',
        'attachment',
        'single',
        'page',
        'singular',
        'category',
        'tag',
        'author',
        'date',
        'archive'
    ];

    /**
     * The Timber context array
     *
     * @var null|array
     */
    protected $context = NULL;

    /**
     * The content of the config file
     *
     * @var array
     */
    protected $config;


    /**
     * Bring the loader up! :)
     */
    public function __construct()
    {
        $this->registerPaths();
        $this->config = require($this->themePath . '/config.php');
    }

    /**
     * Call this hook before wordpress tries to call the actual template file (functions.php)
     */
    public function boot()
    {
        $this->setupPreRenderHooks();
        $this->registerRoutes();
    }

    /**
     * Call this hook in the main template file (index.php)
     */
    public function render()
    {
        $this->currentTemplate = $this->getCurrentTemplateName();
        $context = $this->getContextForTemplate($this->getCurrentTemplateName());

        Timber::render($this->getCurrentTemplatePath(), $context);
    }

    /**
     * Registers all menus from the config file.
     */
    public function registerMenus()
    {
        register_nav_menus($this->config['menus']);
    }

    /**
     * Checks whether the requests page has a custom page template set and
     * sets the $currentTemplate variable according to its value. If this
     * is the case the loader will not look for templates that could match
     * in the usual template hierarchy.
     *
     * This hook is automatically called by the 'template_include' filter.
     *
     * @param $path
     * @return mixed
     */
    public function initCustomPageTemplate($path)
    {
        global $post;

        if (!$post) {
            return $path;
        }

        $template = get_post_meta(
            $post->ID, '_wp_page_template', true
        );

        if (strlen($template) > 0 && $template !== 'default') {
            if ($this->templateExists($template)) {
                $this->currentTemplate = $template;
            } else {
                $this->currentTemplate = $this->fallbackTemplate;
            }
        }

        return $path;
    }

    /**
     * Get an array of the custom page templates located under 'views/pages'.
     *
     * Note, that the name of the template MUST be written in the first line,
     * otherwise it will be ignored:
     *
     *    {# Name: The template name #}
     *
     * @return array
     */
    public function getCustomPageTemplates()
    {
        $dir = $this->viewsPath . '/pages';
        $cacheKey = 'page-templates-' . md5($dir);

        $cache = wp_cache_get($cacheKey, 'themes');

        if ($cache) {
            return $cache;
        }

        $files = array_values(array_diff(scandir($dir), array('.', '..')));
        $templates = [];

        foreach ($files as $file) {
            $path = $dir . '/' . $file;

            if (!is_file($path)) {
                continue;
            }

            // Get the first line of the template. This syntax closes the file handle automatically
            // when it runs out of scope.
            $line = trim(fgets(fopen($path, 'r')));
            preg_match('/^{# Name:(.*)#}$/mi', $line, $matches);

            if (count($matches) < 2) {
                // Template name not found > Ignore this file :(
                continue;
            }

            $filename = explode('.', $file)[0];

            $templates[$filename] = trim($matches[1]);
        }

        if (!$cache) {
            wp_cache_delete($cacheKey, 'themes');
            wp_cache_add($cacheKey, $templates, 'themes', 100);
        }

        return $templates;
    }

    /**
     * Setup some pre-render hooks / actions / filters.
     */
    private function setupPreRenderHooks()
    {
        add_action('init', [$this, 'registerMenus']);

        add_action('after_setup_theme', function () {
            // Adds the templates to the template box
            add_filter('theme_page_templates', [$this, 'getCustomPageTemplates']);

            // Setups the $current_templates variable for the current page request.
            // It will be read later to render the template.
            add_filter('template_include', [$this, 'initCustomPageTemplate']);

            // Called when saving a new post (dunno why we register them here again, maybe for cache reasons).
            add_filter('wp_insert_post_data', function ($args) {
                $this->getCustomPageTemplates();
                return $args;
            });
        });

        $this->initCustomizer();
    }

    /**
     * Adds a section to the theme customizer so that the user
     * can edit some theme settings.
     */
    private function initCustomizer() {
        $css = file_get_contents(__DIR__ . '/customize.css');
        $regex = '|/\*(.+)\*/\n\s*([a-z\-]+):(.+);|';

        // Add color controls
        add_action('customize_register', function (\WP_Customize_Manager $wp_customize) use ($css, $regex) {
            $section = 'theme_section_css_settings';

            $wp_customize->add_section($section, array(
                'title' => __('Theme Settings'),
                'priority' => 30,
            ));

            preg_replace_callback($regex, function ($item) use ($wp_customize, $section) {
                $title = trim($item[1]);
                $id = strtolower(str_replace(' ', '-', $title));
                $property = trim($item[2]);
                $value = trim($item[3]);

                $wp_customize->add_setting($id, [
                    'default' => $value
                ]);

                if (!$wp_customize->get_control($id)) {
                    switch ($property) {
                        case 'color':
                        case 'background-color':
                            $wp_customize->add_control(new \WP_Customize_Color_Control($wp_customize, $id, array(
                                'label' => $title,
                                'section' => $section,
                                'settings' => $id,
                            )));
                            break;
                        default:
                            $wp_customize->add_control(new \WP_Customize_Control($wp_customize, $id, array(
                                'label' => $title,
                                'section' => $section,
                                'settings' => $id
                            )));
                            break;
                    }
                }

            }, $css);
        });

        // Replace css values
        add_action('wp_head', function () use ($css, $regex) {
            $css = preg_replace_callback($regex, function ($item) {
                $title = trim($item[1]);
                $id = strtolower(str_replace(' ', '-', $title));
                $property = trim($item[2]);
                $value = trim($item[3]);

                return $property . ':' . get_theme_mod($id, $value) . ';';
            }, $css);

            ?>
            <style type="text/css"><?php echo $css ?></style><?php
        });
    }

    /**
     * Generate directory paths.
     */
    private function registerPaths()
    {
        $this->themePath = get_template_directory();
        $this->viewsPath = $this->themePath . '/views';
    }

    /**
     * Create the Timber context.
     *
     * @return array
     */
    private function getContext()
    {
        if ($this->context === NULL) {
            $this->context = Timber::get_context();

            $this->context['styles'] = $this->config['styles'];
            $this->context['scripts'] = $this->config['scripts'];

            foreach ($this->config['menus'] as $menu => $translation) {
                $this->context['menus'][$menu] = new Menu($menu);
            }
        }

        return $this->context;
    }

    /**
     * Each template can have an additional context file to provide more
     * variables. The files must be located under 'context/'. The name must
     * match the template name, but with a capital letter at the beginning.
     *
     * Also, the class must be callable. Simply return the modified context
     * variable in the __invoke() method.
     *
     * @param $template
     * @return array
     */
    private function getContextForTemplate($template)
    {
        $context = $this->getContext();
        $class = ucfirst($template);
        $file = $this->themePath . '/context/' . $class . '.php';

        if (file_exists($file)) {
            require_once $file;

            $fullClassName = '\BlueMountain\Context\\' . $class;
            return (new $fullClassName())($context);
        }

        return $context;
    }

    /**
     * Get the current template name.
     *
     * @return string
     */
    private function getCurrentTemplateName()
    {
        if ($this->currentTemplate !== NULL) {
            return $this->currentTemplate;
        }

        foreach ($this->templateHierarchy as $type) {
            $result = call_user_func('is_' . $type);

            if ($result && $this->templateExists($type)) {
                return $type;
            }
        }

        return $this->fallbackTemplate;
    }

    /**
     * Get the full path of the given template.
     *
     * @param $template
     * @return string
     */
    private function getTemplatePath($template)
    {
        $pageTemplate = $this->viewsPath . '/pages/' . $template . '.twig';

        if (file_exists($pageTemplate)) {
            return $pageTemplate;
        }

        return $this->viewsPath . '/templates/' . $template . '.twig';
    }

    /**
     * Get the full path of the current template.
     *
     * @return string
     */
    private function getCurrentTemplatePath()
    {
        return $this->getTemplatePath($this->getCurrentTemplateName());
    }

    /**
     * Check, whether the given template exists on the filesystem.
     *
     * @param $template
     * @return bool
     */
    private function templateExists($template)
    {
        return file_exists($this->getTemplatePath($template));
    }

    /**
     * Register the routes file to provide custom HTTP routes.
     */
    private function registerRoutes()
    {
        require_once get_template_directory() . '/routes.php';
    }
}