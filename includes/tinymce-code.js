// See: https://www.tinymce.com/docs/advanced/creating-a-plugin/

(function() {
    "use strict";
    const BUTTON_NAME = "jyn_code_button";
    const LANGUAGE_PREFIX = "language-";

    // available languages
    const languages = [
        {text: "Auto Detect",value: null},
        {text: "No Highlight",value: LANGUAGE_PREFIX+"nohighlight"},
    ].concat(
        Object.keys(jhighlight_languages).map(function(key) {
            return {text: jhighlight_languages[key],value: LANGUAGE_PREFIX+key};
        }
    ));

    tinymce.PluginManager.add(BUTTON_NAME,function(editor, url) {
        editor.addButton(BUTTON_NAME, {
            icon: false,
            text: "</>",
            tooltip: "Add SourceCode",
            onclick: function() {
                openCodeEditor(editor);
            }
        });

        return {
            getMetadata: function() {
                return {
                    name: "JHighlight Code Editor",
                };
            }
        }
    });

    function openCodeEditor(editor) {
        const node = tinymce.activeEditor.selection.getNode();
        let replace = false;
        let existingCode = null;
        let selectedLanguage = null;
        if(isCode(node)) {
            replace = true;
            existingCode = node.innerText;
            // Find language specification
            for(let c of [].slice.call(node.classList)) {
                // (^_^)< I choose a simple implementation.
                // It is possible that a language that does not exist may be chosen. But TinyMCE handles it well
                if(c.startsWith(LANGUAGE_PREFIX)) {
                    selectedLanguage = c; // language-foo -> foo
                    break;
                }
            }
        }

        // Open popup with Button click
        editor.windowManager.open({
            title: "JHighlight",
            body: [{
                type: "listbox",
                name: "language",
                label:"Language",
                values: languages,
                value: selectedLanguage
            },{
                type: "textbox",
                multiline: true,
                minWidth: 350,
                minHeight: 350,
                name: "code",
                label: "Code",
                value: existingCode,
            }],
            onsubmit: function(e) {
                // code nothing.
                if(e.data.code === "") {
                    return;
                }
                const code = htmlEscape(e.data.code);

                if(replace) {
                    node.innerHTML = code;

                    const list = node.classList;
                    if(selectedLanguage !== null) {
                        list.remove(selectedLanguage);
                    }
                    if(e.data.language !== null) {
                        list.add(e.data.language);
                    }
                    if(list.length === 0) {
                        // remove empty class(Keep HTML clean)
                        node.removeAttribute("class");
                    }
                } else {
                    let language = (e.data.language === null ? "": ` class="${e.data.language}"`);
                    editor.insertContent(`<pre><code${language}>${code}</code></pre>`);
                }
            }
        });
    }
    
    function isCode(node) {
        return node.nodeName.toLowerCase() === "code" &&
            node.parentNode.nodeName.toLowerCase() === "pre";
    }

    function htmlEscape(str) {
        if(typeof str !== "string")return str;

        const target = {
            "&": "&amp;",
            "'": "&#x27;",
            "`": "&#x60;",
            "\"": "&quot;",
            "<": "&lt;",
            ">": "&gt;",
        }

        return str.replace(/[&"`"<>]/g,function(match) {
            return target[match];
        });
    }
})();
