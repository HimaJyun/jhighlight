<?php
namespace jhighlight;

class Options {
    CONST PAGE_NAME = "jhighlight_admin";
    CONST OPTION_GROUP = "jhighlight_option_group";
    CONST OPTION_NAME = "jhighlight_options"; // uninstall.php

    private $default_options = [
        "style" => "default",
        "tab_replace" => '\t',
        "combine_language" => false,
        "dynamic_loading" => true,
        "languages" => [
            // highlight.js common languages
            "apache", "bash", "cs", "cpp", "css", "coffeescript", "diff", "xml",
            "http", "ini", "json", "java", "javascript", "makefile", "markdown",
            "nginx", "objectivec", "php", "perl", "python", "ruby", "sql", "shell"
        ]
    ];
    public $options;

    public function __construct() {
        $this->options = get_option(self::OPTION_NAME,$this->default_options);
        add_action( "admin_menu", function() {
            add_options_page(
                "JHighlight Settings",
                "JHighlight",
                "manage_options",
                self::PAGE_NAME,
                [$this, "create_admin_page"]
            );
        });

        add_action( "admin_init", [$this, "page_init" ]);
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h2>JHighlight</h2>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( self::OPTION_GROUP );
                do_settings_sections( self::PAGE_NAME );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            self::OPTION_GROUP, // Option group
            self::OPTION_NAME, // Option name
            array( $this, "sanitize" ) // Sanitize
        );

        $basic_section = "jhighlight_section_basic";
        add_settings_section(
            $basic_section,
            "Basic Settings",
            function() {echo "Basic settings.";},
            self::PAGE_NAME
        );

        add_settings_field(
            "style",
            "Style",
            [$this,"callback_style"],
            self::PAGE_NAME,
            $basic_section
        );

        add_settings_field(
            "tab_replace",
            "Tab Replace",
            function() { $this->text_callback("tab_replace",$this->default_options["tab_replace"]);},
            self::PAGE_NAME,
            $basic_section
        );

        add_settings_field(
            "combine_language",
            "Combine language files",
            function() { $this->checkbox_callback("combine_language");},
            self::PAGE_NAME,
            $basic_section
        );

        add_settings_field(
            "dynamic_loading",
            "Load only when necessary",
            function() { $this->checkbox_callback("dynamic_loading");},
            self::PAGE_NAME,
            $basic_section
        );

        $lang_section = "jhighlight_section_lang";
        add_settings_section(
            $lang_section,
            "Language Selection",
            function() {echo "Select language to use.";},
            self::PAGE_NAME
        );

        add_settings_field(
            "languages",
            "Languages",
            [$this,"callback_languages"],
            self::PAGE_NAME,
            $lang_section
        );
    }

    public function callback_style() {
        echo "<select name=\"".self::OPTION_NAME."[style]\">";

        $current = $this->options["style"];
        foreach (self::STYLES as $key => $value) {
            $selected = selected($key,$current,false);
            echo "<option value=\"${key}\" ${selected}>${value}</option>";
        }

        echo "</select>";
    }

    public function callback_languages() {
        $selected = $this->options["languages"];
        foreach (self::LANGUAGES as $key => $value) {
            $checked = in_array($key,$selected) ? "checked=\"checked\"" : "";
            echo "<label><input type=\"checkbox\" name=\"".self::OPTION_NAME."[languages][]\" value=\"${key}\" ${checked}/>${value}</label><br>";
        }
    }

    private function text_callback($id, $default = "") {
        $attr = esc_attr($this->options[$id] ?? $default);
        echo "<input type=\"text\" name=\"".self::OPTION_NAME."[${id}]\" value=\"${attr}\" />";
    }

    private function checkbox_callback($id, $default = 0) {
        $checked = checked(true, $this->options[$id], false);
        echo "<input type=\"checkbox\" name=\"".self::OPTION_NAME."[${id}]\" value=\"1\" ${checked}/>";
    }

    public function sanitize( $input )
    {
        $js_dir = __DIR__."/../highlight-js";
        $path = "${js_dir}/highlight.combined.js";

        $new_input = $this->default_options;
        if(isset($input["style"]) && isset(self::STYLES[$input["style"]])) {
            $new_input["style"] = $input["style"];
        }

        if(isset($input["tab_replace"])) {
            $new_input["tab_replace"] = $input["tab_replace"];
        }

        if(isset($input["combine_language"])) {
            $new_input["combine_language"] = (boolean)$input["combine_language"];
            // writable check
            if($new_input["combine_language"]) {
                if((file_exists($path) && !is_writable($path)) || !is_writable($js_dir)) {
                    $js_dir = realpath($js_dir);
                    add_settings_error(
                        self::OPTION_GROUP,
                        "combine-language",
                        "Can not write to '${js_dir}'"
                    );
                    $new_input["combine_language"] = false;
                }
            }
        }

        if(isset($input["dynamic_loading"])) {
            $new_input["dynamic_loading"] = (boolean)$input["dynamic_loading"];
        }

        if(isset($input["languages"]) && is_array($input["languages"])) {
            $new_input["languages"] = [];
            foreach ($input["languages"] as $key) {
                if(isset(self::LANGUAGES[$key])) {
                    $new_input["languages"][] = $key;
                }
            }
        }

        // file combine
        if($new_input["combine_language"]) {
            $fp = fopen($path,"wb");
            fwrite($fp,file_get_contents("${js_dir}/highlight.min.js"));
            foreach($new_input["languages"] as $key) {
                fwrite($fp,file_get_contents("${js_dir}/languages/{$key}.min.js"));
            }
            fclose($fp);
        }
        return $new_input;
    }

    // very long const area
    CONST STYLES = [
        "default" => "Default",
        "agate" => "Agate",
        "androidstudio" => "Androidstudio",
        "arduino-light" => "Arduino Light",
        "arta" => "Arta",
        "ascetic" => "Ascetic",
        "atelier-cave-dark" => "Atelier Cave Dark",
        "atelier-cave-light" => "Atelier Cave Light",
        "atelier-dune-dark" => "Atelier Dune Dark",
        "atelier-dune-light" => "Atelier Dune Light",
        "atelier-estuary-dark" => "Atelier Estuary Dark",
        "atelier-estuary-light" => "Atelier Estuary Light",
        "atelier-forest-dark" => "Atelier Forest Dark",
        "atelier-forest-light" => "Atelier Forest Light",
        "atelier-heath-dark" => "Atelier Heath Dark",
        "atelier-heath-light" => "Atelier Heath Light",
        "atelier-lakeside-dark" => "Atelier Lakeside Dark",
        "atelier-lakeside-light" => "Atelier Lakeside Light",
        "atelier-plateau-dark" => "Atelier Plateau Dark",
        "atelier-plateau-light" => "Atelier Plateau Light",
        "atelier-savanna-dark" => "Atelier Savanna Dark",
        "atelier-savanna-light" => "Atelier Savanna Light",
        "atelier-seaside-dark" => "Atelier Seaside Dark",
        "atelier-seaside-light" => "Atelier Seaside Light",
        "atelier-sulphurpool-dark" => "Atelier Sulphurpool Dark",
        "atelier-sulphurpool-light" => "Atelier Sulphurpool Light",
        "atom-one-dark" => "Atom One Dark",
        "atom-one-light" => "Atom One Light",
        "brown-paper" => "Brown Paper",
        "codepen-embed" => "Codepen Embed",
        "color-brewer" => "Color Brewer",
        "dark" => "Dark",
        "darkula" => "Darkula",
        "docco" => "Docco",
        "dracula" => "Dracula",
        "far" => "Far",
        "foundation" => "Foundation",
        "github-gist" => "Github Gist",
        "github" => "Github",
        "googlecode" => "Googlecode",
        "grayscale" => "Grayscale",
        "gruvbox-dark" => "Gruvbox Dark",
        "gruvbox-light" => "Gruvbox Light",
        "hopscotch" => "Hopscotch",
        "hybrid" => "Hybrid",
        "idea" => "Idea",
        "ir-black" => "Ir Black",
        "kimbie.dark" => "Kimbie Dark",
        "kimbie.light" => "Kimbie Light",
        "magula" => "Magula",
        "mono-blue" => "Mono Blue",
        "monokai-sublime" => "Monokai Sublime",
        "monokai" => "Monokai",
        "obsidian" => "Obsidian",
        "ocean" => "Ocean",
        "paraiso-dark" => "Paraiso Dark",
        "paraiso-light" => "Paraiso Light",
        "pojoaque" => "Pojoaque",
        "purebasic" => "Purebasic",
        "qtcreator_dark" => "Qtcreator Dark",
        "qtcreator_light" => "Qtcreator Light",
        "railscasts" => "Railscasts",
        "rainbow" => "Rainbow",
        "routeros" => "Routeros",
        "school-book" => "School Book",
        "solarized-dark" => "Solarized Dark",
        "solarized-light" => "Solarized Light",
        "sunburst" => "Sunburst",
        "tomorrow-night-blue" => "Tomorrow Night Blue",
        "tomorrow-night-bright" => "Tomorrow Night Bright",
        "tomorrow-night-eighties" => "Tomorrow Night Eighties",
        "tomorrow-night" => "Tomorrow Night",
        "tomorrow" => "Tomorrow",
        "vs" => "Vs", "vs2015" => "Vs 2015",
        "xcode" => "Xcode",
        "xt256" => "Xt 256",
        "zenburn" => "Zenburn",
    ];

    CONST LANGUAGES = [
        "1c" => "1C:Enterprise (v7, v8)",
        "accesslog" => "Access log",
        "actionscript" => "ActionScript",
        "ada" => "Ada",
        "apache" => "Apache",
        "applescript" => "AppleScript",
        "arduino" => "Arduino",
        "armasm" => "ARM Assembly",
        "asciidoc" => "AsciiDoc",
        "aspectj" => "AspectJ",
        "abnf" => "Augmented Backus-Naur Form",
        "autohotkey" => "AutoHotkey",
        "autoit" => "AutoIt",
        "avrasm" => "AVR Assembler",
        "awk" => "Awk",
        "axapta" => "Axapta",
        "bnf" => "Backus-Naur Form",
        "bash" => "Bash",
        "basic" => "Basic",
        "brainfuck" => "Brainfuck",
        "cs" => "C#",
        "cal" => "C/AL",
        "cpp" => "C++",
        "cos" => "Cache Object Script",
        "capnproto" => "Cap'n Proto",
        "ceylon" => "Ceylon",
        "clean" => "Clean",
        "clojure" => "Clojure",
        "clojure-repl" => "Clojure REPL",
        "cmake" => "CMake",
        "coffeescript" => "CoffeeScript",
        "coq" => "Coq",
        "crmsh" => "crmsh",
        "crystal" => "Crystal",
        "csp" => "CSP",
        "css" => "CSS",
        "d" => "D",
        "dart" => "Dart",
        "delphi" => "Delphi",
        "dts" => "Device Tree",
        "diff" => "Diff",
        "django" => "Django",
        "dns" => "DNS Zone file",
        "dockerfile" => "Dockerfile",
        "dos" => "DOS .bat",
        "dsconfig" => "dsconfig",
        "dust" => "Dust",
        "elixir" => "Elixir",
        "elm" => "Elm",
        "erb" => "ERB (Embedded Ruby)",
        "erlang" => "Erlang",
        "erlang-repl" => "Erlang REPL",
        "excel" => "Excel",
        "ebnf" => "Extended Backus-Naur Form",
        "fsharp" => "F#",
        "fix" => "FIX",
        "flix" => "Flix",
        "fortran" => "Fortran",
        "gams" => "GAMS",
        "gauss" => "GAUSS",
        "gcode" => "G-code (ISO 6983)",
        "gherkin" => "Gherkin",
        "glsl" => "GLSL",
        "go" => "Go",
        "golo" => "Golo",
        "gradle" => "Gradle",
        "groovy" => "Groovy",
        "haml" => "Haml",
        "handlebars" => "Handlebars",
        "haskell" => "Haskell",
        "haxe" => "Haxe",
        "hsp" => "HSP",
        "xml" => "HTML, XML",
        "htmlbars" => "HTMLBars",
        "http" => "HTTP",
        "hy" => "Hy",
        "inform7" => "Inform 7",
        "ini" => "Ini",
        "x86asm" => "Intel x86 Assembly",
        "irpf90" => "IRPF90",
        "java" => "Java",
        "javascript" => "JavaScript",
        "jboss-cli" => "jboss-cli",
        "json" => "JSON",
        "julia" => "Julia",
        "julia-repl" => "Julia REPL",
        "kotlin" => "Kotlin",
        "lasso" => "Lasso",
        "ldif" => "LDIF",
        "leaf" => "Leaf",
        "less" => "Less",
        "lsl" => "Linden Scripting Language",
        "lisp" => "Lisp",
        "livecodeserver" => "LiveCode",
        "livescript" => "LiveScript",
        "llvm" => "LLVM IR",
        "lua" => "Lua",
        "makefile" => "Makefile",
        "markdown" => "Markdown",
        "mathematica" => "Mathematica",
        "matlab" => "Matlab",
        "maxima" => "Maxima",
        "mel" => "MEL",
        "mercury" => "Mercury",
        "routeros" => "Microtik RouterOS script",
        "mipsasm" => "MIPS Assembly",
        "mizar" => "Mizar",
        "mojolicious" => "Mojolicious",
        "monkey" => "Monkey",
        "moonscript" => "MoonScript",
        "n1ql" => "N1QL",
        "nginx" => "Nginx",
        "nimrod" => "Nimrod",
        "nix" => "Nix",
        "nsis" => "NSIS",
        "objectivec" => "Objective-C",
        "ocaml" => "OCaml",
        "openscad" => "OpenSCAD",
        "ruleslanguage" => "Oracle Rules Language",
        "oxygene" => "Oxygene",
        "parser3" => "Parser3",
        "perl" => "Perl",
        "pf" => "pf",
        "php" => "PHP",
        "pony" => "Pony",
        "powershell" => "PowerShell",
        "processing" => "Processing",
        "prolog" => "Prolog",
        "protobuf" => "Protocol Buffers",
        "puppet" => "Puppet",
        "purebasic" => "PureBASIC",
        "python" => "Python",
        "profile" => "Python profile",
        "q" => "Q",
        "qml" => "QML",
        "r" => "R",
        "rib" => "RenderMan RIB",
        "rsl" => "RenderMan RSL",
        "roboconf" => "Roboconf",
        "ruby" => "Ruby",
        "rust" => "Rust",
        "scala" => "Scala",
        "scheme" => "Scheme",
        "scilab" => "Scilab",
        "scss" => "SCSS",
        "shell" => "Shell Session",
        "smali" => "Smali",
        "smalltalk" => "Smalltalk",
        "sml" => "SML",
        "sqf" => "SQF",
        "sql" => "SQL",
        "stan" => "Stan",
        "stata" => "Stata",
        "step21" => "STEP Part 21",
        "stylus" => "Stylus",
        "subunit" => "SubUnit",
        "swift" => "Swift",
        "taggerscript" => "Tagger Script",
        "tcl" => "Tcl",
        "tap" => "Test Anything Protocol",
        "tex" => "TeX",
        "thrift" => "Thrift",
        "tp" => "TP",
        "twig" => "Twig",
        "typescript" => "TypeScript",
        "vala" => "Vala",
        "vbnet" => "VB.NET",
        "vbscript" => "VBScript",
        "vbscript-html" => "VBScript in HTML",
        "verilog" => "Verilog",
        "vhdl" => "VHDL",
        "vim" => "Vim Script",
        "xl" => "XL",
        "xquery" => "XQuery",
        "yaml" => "YAML",
        "zephir" => "Zephir",
    ];
}
