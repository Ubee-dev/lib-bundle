import SimpleMDE from 'simplemde';
import {slugify} from './helpers/stringHelpers';

const defaultInitOptions = {
    previewUrl: null
};

/**
 * Decorate text fields with markdown editor
 * Dependency on https://github.com/NextStepWebs/simplemde-markdown-editor.
 * Install via npm: npm install simplemde --save
 */
const Markdown = {
    editors: {},

    init: function (initOptions = defaultInitOptions) {
        const markdownAreas = document.querySelectorAll('.js-markdown');
        this.initOptions = initOptions;

        console.log("this.initOptions", this.initOptions)
        if (markdownAreas.length) {
            markdownAreas.forEach(area => {
                this.addEditor(area, this.initOptions);
            });
        }
    },

    hasEditor: function(markdownArea) {
        return this.editors[markdownArea.id] !== undefined;
    },

    changePreviewContent: function (plainText, preview) {

        console.log("bbbbbbbbbbbbbbbbbb");
        fetch(this.initOptions.previewUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `content=${encodeURIComponent(plainText)}`
        })
            .then(response => response.text())
            .then(data => {
                const iframe = document.createElement('iframe');
                iframe.style.width = '100%';
                iframe.style.height = '100%';
                iframe.style.border = '0';
                preview.innerHTML = '';
                preview.appendChild(iframe);
                iframe.contentWindow.document.open();
                iframe.contentWindow.document.write(data);
                iframe.contentWindow.document.close();
            })
            .catch(error => console.error('Error fetching preview:', error));
    },

    addEditor: function (markdownArea, overrideOptions = {}) {
        // Shortcircuit already active markdown elements.
        if (markdownArea.hasAttribute('data-markdown-active')) {
            return;
        }

        // Load default options.
        const options = this.defaultOptions(markdownArea);

        if(overrideOptions.length > 0) {
            console.log("overrideOptions", overrideOptions)

            Object.keys(overrideOptions).forEach((key) => {
                this.initOptions[key] = overrideOptions[key];
            });
        }

        // Add markdown textarea placeholder.
        if (markdownArea.hasAttribute('data-markdownPlaceholder')) {
            options.placeholder = markdownArea.getAttribute('data-markdownPlaceholder');
        }

        if (markdownArea.hasAttribute('data-nb-advised-words')) {
            const nbData = JSON.parse(markdownArea.getAttribute('data-nb-advised-words'));
            options.status = ["autosave", "lines", "words", "cursor", {
                className: "warning",
                defaultValue: function(el) {
                    markdownArea.keystrokes = 0;
                    el.innerHTML = "";
                },
                onUpdate: function(el) {
                    const nbWordsEl = document.getElementsByClassName('words')[0];
                    const nbWords = parseInt(nbWordsEl.innerHTML);
                    const message = nbData.message !== undefined ? nbData.message : `Attention ! Le nombre de mots conseillé doit-être compris entre ${nbData.min} et ${nbData.max}.`;

                    if(nbWords > nbData.max) {
                        el.classList.remove("alert-warning");
                        el.classList.add("alert", "alert-danger");
                        el.innerHTML = message;
                    } else if(nbWords < nbData.min && nbWords < nbData.max) {
                        el.classList.add("alert", "alert-danger");
                        el.innerHTML = message;
                    } else {
                        el.classList.remove("alert", "alert-danger");
                        el.innerHTML = '';
                    }
                }
            }];
        }

        // Load toolbar.
        options.toolbar = this.toolbar(markdownArea);

        console.log("this.initOptions.previewUrl", this.initOptions.previewUrl)
        if(this.initOptions.previewUrl) {

            console.log('previewUrl is set');
            options.previewRender = (plainText, preview) => {
                return this.changePreviewContent(plainText, preview) || 'Loading';
            };
        }

        // Decorate element.
        markdownArea.setAttribute('data-markdown-active', true);

        // Create and add editor to Markdown object's data.
        const editor = new SimpleMDE(options);

        const codemirror = editor.codemirror;
        const wrapper = codemirror.display.wrapper;
        const parent = wrapper.parentNode;
        const containsHTML = (str) => /(<\/?(?!br\s?\/?>).*>)/i.test(str);

        codemirror.on('renderLine', (instance, line, lineElement) => {
            if(containsHTML(line.text)) {
                lineElement.classList.add('invalid');

                // if there is html in editor add global error message else remove it
                if(!editor.hasHtml) {
                    const errorMessage = document.createElement('div');
                    errorMessage.className = 'alert alert-danger markdown-error';
                    errorMessage.textContent = 'Votre texte contient du HTML. Seules les balises <br> sont autorisées. Toutes les autres balises seront ignorées.';
                    wrapper.parentNode.insertBefore(errorMessage, wrapper);
                    wrapper.classList.add('invalid');
                    // set if there is html in editor property in order to do not check in the dom every time
                    editor.hasHtml = true;
                }
            } else {
                lineElement.classList.remove('invalid');
            }

            if(!containsHTML(editor.value())) {
                const errorMessage = parent.querySelector('.alert');
                if (errorMessage) {
                    parent.removeChild(errorMessage);
                }
                parent.querySelectorAll('.invalid').forEach(el => el.classList.remove('invalid'));
                editor.hasHtml = false;
            }
        });

        this.editors[markdownArea.id] = editor;
    },

    refreshEditors: function (initOptions = {}) {
        if(Object.keys(initOptions).length > 0) {
            this.initOptions = initOptions;
        }

        for (const textareaId in this.editors) {
            if (this.editors.hasOwnProperty(textareaId)) {
                const element = document.getElementById(textareaId);
                if (element && 'codemirror' in this.editors[textareaId]) {
                    this.editors[textareaId].codemirror.refresh();
                } else {
                    // Remove editor if the textarea is no longer in the page.
                    delete this.editors[textareaId];
                }
            }
        }
    },

    refreshPreviews: function () {
        for (const textareaId in this.editors) {
            if (this.editors.hasOwnProperty(textareaId)) {
                const element = document.getElementById(textareaId);
                if (element && 'codemirror' in this.editors[textareaId]) {
                    if(this.editors[textareaId].isPreviewActive()) {
                        this.togglePreview(this.editors[textareaId]);
                    }
                } else {
                    // Remove editor if the textarea is no longer in the page.
                    delete this.editors[textareaId];
                }
            }
        }
    },

    togglePreview: function (editor, addPreviewContent = true) {
        const cm = editor.codemirror;
        const wrapper = cm.getWrapperElement();
        const toolbar_div = wrapper.previousSibling;
        const toolbar = editor.options.toolbar ? editor.toolbarElements.preview : false;
        let preview = wrapper.lastChild;

        if(!preview || !/editor-preview/.test(preview.className)) {
            preview = document.createElement("div");
            preview.className = "editor-preview";
            wrapper.appendChild(preview);
        }

        if(/editor-preview-active/.test(preview.className)) {
            preview.className = preview.className.replace(
                /\s*editor-preview-active\s*/g, ""
            );
            if(toolbar) {
                toolbar.className = toolbar.className.replace(/\s*active\s*/g, "");
                toolbar_div.className = toolbar_div.className.replace(/\s*disabled-for-preview*/g, "");
            }
        } else {
            // When the preview button is clicked for the first time,
            // give some time for the transition from editor.css to fire and the view to slide from right to left,
            // instead of just appearing.
            setTimeout(function() {
                preview.className += " editor-preview-active";
            }, 1);
            if(toolbar) {
                toolbar.className += " active";
                toolbar_div.className += " disabled-for-preview";
            }
        }

        // Turn off side by side if needed
        const sidebyside = wrapper.nextSibling;
        if(sidebyside && /editor-preview-active-side/.test(sidebyside.className)) {
            this.toggleSideBySide(editor);
        }

        if(addPreviewContent) {
            preview.innerHTML = editor.options.previewRender(editor.value(), preview);
            this.togglePreview(editor, false);
        }
    },

    toggleSideBySide: function(editor) {
        // This function is not fully implemented in the original code
        // Adding a stub for completeness
        console.log("toggleSideBySide function was called");
    },

    defaultOptions: function (markdownTextarea) {
        return {
            element: markdownTextarea,
            autosave: {
                enabled: false
            },
            promptURLs: false,
            indentWithTabs: false,
            tabSize: 4,
            forceSync: true,
            spellChecker: false
        };
    },

    toolbar: function (markdownTextarea) {
        let toolbar;

        if (markdownTextarea.hasAttribute('data-markdown-restrictive-menu')) {
            toolbar = this.restrictedToolbar();
        } else if (markdownTextarea.hasAttribute('data-markdown-toolbar')) {
            const toolbarData = JSON.parse(markdownTextarea.getAttribute('data-markdown-toolbar'));
            toolbar = this.customToolbar(toolbarData);
        } else if (markdownTextarea.hasAttribute('data-markdown-live-toolbar')) {
            toolbar = this.liveToolBar();
        } else if (markdownTextarea.hasAttribute('data-markdown-full-toolbar')) {
            toolbar = this.fullToolBar();
        } else if (markdownTextarea.hasAttribute('data-markdown-email-toolbar')) {
            toolbar = this.emailToolbar();
        } else {
            toolbar = this.defaultToolbar();
        }

        // Append sticky tools that should be at the end of the toolbar.
        const stickyTools = ['preview'];

        if (markdownTextarea.hasAttribute('data-withoutMarkdownDocumentation')) {
            stickyTools.push('guide');
        }

        stickyTools.forEach(function (tool) {
            if (toolbar.indexOf(tool) === -1) {
                toolbar.push(tool);
            }
        });

        if (!markdownTextarea.hasAttribute('data-withoutMarkdownDocumentation')) {
            toolbar.push(this.guideTool());
        }

        return toolbar;
    },

    defaultToolbar: function () {
        const toolbar = [
            'bold',
            'italic',
            'heading-2',
            'heading-3',
            this.headingIdTool(),
            'quote',
            this.superscriptTool(),
            this.abbreviationTool(),
            this.footerNoteTool(),
            'unordered-list',
            'clean-block',
            'link',
            'image',
            'table',
            'horizontal-rule',
        ];
        toolbar.push(this.youtubeTool());
        toolbar.push(this.vimeoTool());

        return toolbar;
    },

    liveToolBar() {
        return [
            ...this.restrictedToolbar(),
            this.videoTimeTool(),
            'link',
        ];
    },

    fullToolBar: function () {
        const toolbar = this.defaultToolbar();
        toolbar.push(this.iframeTool());
        toolbar.push(this.buttonTool());
        return toolbar;
    },

    restrictedToolbar: function () {
        return [
            'bold',
            'italic',
            'heading-2',
            'heading-3'
        ];
    },

    emailToolbar: function () {
        return [
            'bold',
            'italic',
            'unordered-list',
            'link'
        ];
    },

    customToolbar: function (toolbarItems) {
        let i;

        if ((i = toolbarItems.indexOf('youtube')) > -1) {
            toolbarItems.splice(i, 1);
            toolbarItems.push(this.youtubeTool());
        }

        if ((i = toolbarItems.indexOf('vimeo')) > -1) {
            toolbarItems.splice(i, 1);
            toolbarItems.push(this.vimeoTool());
        }

        if ((i = toolbarItems.indexOf('strong')) > -1) {
            toolbarItems.splice(i, 1);
            toolbarItems.push(this.strongTool());
        }

        if ((i = toolbarItems.indexOf('iframe')) > -1) {
            toolbarItems.splice(i, 1);
            toolbarItems.push(this.iframeTool());
        }

        return toolbarItems;
    },

    addToolTag: function (params) {
        const { editor, str, tag } = params;

        const cm = editor.codemirror;
        const stat = editor.getState(cm);
        if (/editor-preview-active/.test(cm.getWrapperElement().lastChild.className))
            return;

        const startEnd = tag;
        let text;
        let start = startEnd[0];
        let end = startEnd[1];
        const startPoint = cm.getCursor("start");
        const endPoint = cm.getCursor("end");

        if (str) {
            end = end.replace("#str#", str);
        }

        if (stat.link) {
            text = cm.getLine(startPoint.line);
            start = text.slice(0, startPoint.ch);
            end = text.slice(startPoint.ch);
            cm.replaceRange(start + end, {
                line: startPoint.line,
                ch: 0
            });
        } else {
            cm.replaceSelection(start + end);
            startPoint.ch += start.length;
            if (startPoint !== endPoint) {
                endPoint.ch += start.length;
            }
        }

        cm.setSelection(startPoint, endPoint);
        cm.focus();
    },

    iframeTool: function () {
        const self = this;
        return {
            name: "iframe",
            action: function (editor) {
                let height = 500;
                const input = prompt("Copiez ici l'url ou le code de votre iframe");

                if (!input) return;

                let finalInput = input;

                if (/^\s*<iframe .*/.test(input)) {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(input, 'text/html');
                    const iframe = doc.querySelector('iframe');

                    if (iframe) {
                        const src = iframe.getAttribute('src');

                        if (iframe.style.height && parseInt(iframe.style.height)) {
                            height = parseInt(iframe.style.height);
                        } else if (iframe.getAttribute('height')) {
                            height = parseInt(iframe.getAttribute('height'));
                        }

                        if (src && src.length) {
                            finalInput = src;
                        }
                    }
                } else if (/^\s*http?s:\/\/.*\..*/.test(input)) {
                    // Intentionally left blank. Just continue;
                } else {
                    return;
                }

                const tag = ["{iframe:", "#str#,height:" + height + "}"];

                self.addToolTag({
                    editor,
                    tag,
                    str: finalInput
                });
            },
            className: "fa fa-file-code-o",
            title: 'Insert iframe (code or src)'
        };
    },

    buttonTool: function () {
        const self = this;
        return {
            name: "button",
            action: function (editor) {
                const input = prompt("Copiez ici l'url du lien");

                if (!input) return;

                const tag = ["{[Libellé du bouton]", "("+ input +")}"];

                self.addToolTag({
                    editor,
                    tag,
                    str: input
                });
            },
            className: "fa fa-arrow-right",
            title: 'Insérer un bouton'
        };
    },

    youtubeTool: function () {
        const self = this;
        return {
            name: "youtube",
            action: function customFunction(editor) {
                const url = prompt("Copiez ici l'url de votre vidéo Youtube");
                if (!url) return false;
                const tag = ["{youtube:", "#str#}"];
                self.addToolTag({
                    editor,
                    tag,
                    str: url
                });
            },
            className: "fa fa-video-camera",
            title: "Youtube video"
        };
    },

    vimeoTool: function () {
        const self = this;
        return {
            name: "vimeo",
            action: function customFunction(editor) {
                const url = prompt("Copiez ici l'url de votre vidéo Vimeo");
                if (!url) return false;
                const tag = ["{vimeo:", "#str#}"];
                self.addToolTag({
                    editor,
                    tag,
                    str: url
                });
            },
            className: "fa fa-vimeo",
            title: "Vimeo video"
        };
    },

    videoTimeTool: function () {
        const self = this;
        return {
            name: "videoTime",
            action: function customFunction(editor) {
                const url = prompt("Collez ici le chrono de la vidéo ex : 1:32:54");
                if (!url) return false;
                const tag = ["{videoTime:", "#str#}"];
                self.addToolTag({
                    editor,
                    tag,
                    str: url
                });
            },
            className: "fa fa-step-forward",
            title: "Video time link"
        };
    },

    strongTool: function () {
        const self = this;
        return {
            name: "strong",
            action: function customFunction(editor) {
                const input = prompt("Entez l'information clé (chiffre, statistique)");
                if (!input) {
                    return false;
                }
                const tag = ["{strong:", "#str#}"];
                self.addToolTag({
                    editor: editor,
                    str: input,
                    tag
                });
            },
            className: "fa fa-hashtag",
            title: "Number"
        };
    },

    guideTool: function () {
        return {
            name: "guide",
            action: function openlink() {
                const win = window.open('/admin/markdown-documentation', '_blank');
                win.focus();
            },
            className: "fa fa-markdown-guide__icon",
            title: "Guide",
        };
    },

    superscriptTool: function () {
        const self = this;
        return {
            name: "superscript",
            action: function customFunction(editor) {
                let selectTxt = editor.codemirror.getSelection();

                if(!selectTxt) {
                    selectTxt = prompt("Veuillez saisir l'indice.");
                    if (!selectTxt) {
                        return false;
                    }
                }

                const tag = ["^", "#str#^"];
                self.addToolTag({
                    editor,
                    tag,
                    str: selectTxt
                });
            },
            className: "fa fa-superscript",
            title: "Indice"
        };
    },

    abbreviationTool: function () {
        const self = this;
        return {
            name: "abbreviation",
            action: function customFunction(editor) {
                let selectTxt = editor.codemirror.getSelection();

                if(!selectTxt) {
                    selectTxt = prompt("Veuillez saisir l'abréviation.");
                    if (!selectTxt) {
                        return false;
                    }
                }

                const definition = prompt("Veuillez saisir la définition de \""+selectTxt+"\"");

                if (!definition) {
                    return false;
                }

                const tag = [selectTxt+"\n*["+selectTxt+"]: ", definition];
                self.addToolTag({
                    editor,
                    tag,
                });
            },
            className: "fa fa-info",
            title: "Abréviation"
        };
    },

    footerNoteTool: function () {
        const self = this;
        return {
            name: "footernote",
            action: function customFunction(editor) {
                let selectTxt = editor.codemirror.getSelection();
                const matches = Array.from(editor.value().matchAll(/\[\^(\d+)\]/g), (m) => parseInt(m[1]));
                const maxIndex = matches.length > 0 ? Math.max(...matches) : 0;
                const nextIndex = maxIndex + 1;

                if(!selectTxt) {
                    selectTxt = prompt("Veuillez saisir la note de bas de page.");
                    if (!selectTxt) {
                        return false;
                    }
                }

                const definition = prompt("Veuillez saisir le contenu de \""+selectTxt+"\"");

                if (!definition) {
                    return false;
                }

                const tag = [selectTxt+` [^${nextIndex}]\n`, `[^${nextIndex}]: `+definition];
                self.addToolTag({
                    editor,
                    tag,
                });
            },
            className: "fa fa-hand-o-down",
            title: "Note en bas de page"
        };
    },

    headingIdTool: function () {
        const self = this;
        return {
            name: "heading-id",
            action: function customFunction(editor) {
                const cm = editor.codemirror;
                const startPoint = cm.getCursor("start");
                const currentLineText = cm.getLine(startPoint.line).match(/^[^{]+/)[0].trim();

                const titleText = currentLineText.match(/^#+\s(.+)$/);
                // if is not a title : do nothing
                if(!titleText) {
                    cm.focus();
                    return false;
                }

                const titleId = slugify(titleText[1].trim());

                cm.replaceRange(` {#${titleId}}`, {
                    line: startPoint.line,
                    ch: currentLineText.length,
                }, {
                    line: startPoint.line,
                    ch: cm.getLine(startPoint.line).length,
                });
                cm.focus();
            },
            className: "fa fa-sharp",
            title: "Ancre de titre"
        };
    },
};

export default Markdown;