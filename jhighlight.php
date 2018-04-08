<?php
/*
Plugin Name: JHighlight
Description: Code Highlight for WordPress
Version: 1.0.0
Author: HimaJyun
Author URI: https://jyn.jp/
License: zlib License
*/
namespace jhighlight;
defined("ABSPATH") || die();

require_once(__DIR__."/includes/options.php");

class JHighlight {

    private $url;
    private $options;

    private $loaded = false;

    public function __construct() {
        $this->url = plugins_url("",__FILE__);
        $this->options = (new Options())->options;
        
        add_action("admin_head",[$this,"add_tinymce_button"]);
        add_action("wp_enqueue_scripts",[$this,"enqueue_scripts"]);

        // async is combined only
        if($this->options["combine_language"]) {
            add_filter('script_loader_tag', [$this,"async_load"], 10, 2);
        }
        if($this->options["dynamic_loading"]) {
            add_filter('the_content',[$this,"dynamic_loading"]);
        }

        foreach(["post.php","post-new.php"] as $hook) {
            add_action("admin_head-".$hook, [$this,"editor_languages"]);
        }
    }

    public function add_tinymce_button() {
        if(!user_can_richedit()) return;

        add_filter( "mce_external_plugins", function($plugins) {
            $plugins["jyn_code_button"] = "{$this->url}/includes/tinymce-code.js";
            return $plugins;
        });
        add_filter( "mce_buttons", function($buttons) {
            $buttons[] = "jyn_code_button";
            return $buttons;
        });
    }

    public function editor_languages() {
        // create languege list
        $languages = [];
        foreach ($this->options["languages"] as $key) {
            $languages[$key] = Options::LANGUAGES[$key];
        }

        // add header
        ?>
        <script type="text/javascript">
            var jhighlight_languages = <?=json_encode($languages)?>;
        </script>
        <?php
    }

    public function enqueue_scripts() {
        $prefix = "min";
        $ver = "9.12.0";
        if($this->options["combine_language"]) {
            $prefix = "combined";
            $ver .= "-".filemtime(__DIR__."/highlight-js/highlight.combined.js");
        }

        wp_register_script(
            "jhighlight-js",
            "{$this->url}/highlight-js/highlight.{$prefix}.js",
            array(),
            $ver,
            true // in footer
        );
        wp_register_style(
            "jhighlight-style",
            "{$this->url}/highlight-js/styles/{$this->options['style']}.min.css",
            array(),
            $ver
        );

        if(!$this->options["dynamic_loading"]) {
            $this->script_load();
        }
    }

    private function script_load() {
        $handle = "jhighlight-js";
        wp_enqueue_script($handle);
        wp_enqueue_style("jhighlight-style");

        $code = "hljs.configure({tabReplace: '{$this->options["tab_replace"]}'});";
        $code .= "hljs.initHighlightingOnLoad();";
        if($this->options["combine_language"]) {
            // replace to async load waiting code
            $code = "document.getElementById('jhighlight-js').addEventListener('load',function(){{$code}});";
        } else {
            // load all languages
            foreach($this->options["languages"] as $key) {
                $handle = "jhighlight-{$key}-js";
                wp_enqueue_script(
                    $handle,
                    "{$this->url}/highlight-js/languages/{$key}.min.js",
                    ["jhighlight-js"],
                    false,
                    true
                );
            }
        }

        wp_add_inline_script($handle,$code);
    }

    public function async_load($tag,$handle) {
        if("jhighlight-js" !== $handle) {
            return $tag;
        }
        
        return str_replace(" src", " id=\"{$handle}\" async src",$tag);
    }

    public function dynamic_loading($content) {
        // Load only once
        if(!$this->loaded && preg_match("/<pre.*>\s*<code.*>/i",$content)) {
            $this->script_load();
            $this->loaded = true;
        }
        return $content;
    }
}
new JHighlight();
