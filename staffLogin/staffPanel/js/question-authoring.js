(function(window, $) {
    'use strict';

    function decodeHtml(html) {
        var textarea = document.createElement('textarea');
        textarea.innerHTML = html || '';
        return textarea.value;
    }

    function htmlToText(html) {
        var wrapper = document.createElement('div');
        wrapper.innerHTML = decodeHtml(html || '');

        Array.prototype.forEach.call(wrapper.querySelectorAll('br'), function(node) {
            node.parentNode.insertBefore(document.createTextNode('\n'), node);
        });

        Array.prototype.forEach.call(wrapper.querySelectorAll('p, div, li, tr, td, th, h1, h2, h3, h4, h5, h6'), function(node) {
            if (node.textContent && node.textContent.trim() !== '' && node.lastChild && node.lastChild.nodeType !== 3) {
                node.appendChild(document.createTextNode('\n'));
            } else if (node.textContent && node.textContent.trim() !== '' && !/\n\s*$/.test(node.textContent)) {
                node.appendChild(document.createTextNode('\n'));
            }
        });

        return (wrapper.textContent || wrapper.innerText || '')
            .replace(/\u00a0/g, ' ')
            .replace(/\r/g, '')
            .replace(/[ \t]+\n/g, '\n')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
    }

    function escapeHtml(text) {
        return $('<div>').text(text || '').html();
    }

    function textToEditorHtml(text) {
        if (!text) {
            return '';
        }

        return text
            .split(/\n{2,}/)
            .map(function(chunk) {
                return '<p>' + escapeHtml(chunk).replace(/\n/g, '<br>') + '</p>';
            })
            .join('');
    }

    function normalizeText(text) {
        return (text || '')
            .replace(/\u00a0/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function cleanWordPasteHtml(html) {
        if (!html) {
            return html;
        }

        var wrapper = document.createElement('div');
        wrapper.innerHTML = html;

        function stripLeadingListMarkerFromNode(textNode) {
            if (!textNode || textNode.nodeType !== Node.TEXT_NODE) {
                return false;
            }

            var original = textNode.nodeValue || '';
            var matched = original.match(/^\s*(?:\d+(?:\.\d+)*|[ivxlcdm]+|[A-Ea-e])(?:[\.\)\-:\]]+\s+|\s+)/);
            if (matched && matched[0].length < original.length) {
                textNode.nodeValue = original.slice(matched[0].length);
                return true;
            }
            return false;
        }

        var nodes = wrapper.querySelectorAll('p, li');
        Array.prototype.forEach.call(nodes, function(node) {
            var child = node.firstChild;
            while (child) {
                if (stripLeadingListMarkerFromNode(child)) {
                    break;
                }
                if (child.nodeType === Node.ELEMENT_NODE && child.firstChild) {
                    if (stripLeadingListMarkerFromNode(child.firstChild)) {
                        break;
                    }
                }
                child = child.nextSibling;
            }
        });

        return wrapper.innerHTML;
    }

    function looksLikePlaceholder(html) {
        var text = normalizeText(htmlToText(html)).toUpperCase().replace(/[^A-Z0-9]+/g, ' ').trim();
        return ['A', 'B', 'C', 'D', 'E', 'OPTION A', 'OPTION B', 'OPTION C', 'OPTION D', 'OPTION E'].indexOf(text) !== -1;
    }

    function parseOptionMarker(marker) {
        return (marker || '').replace(/[^A-E]/gi, '').toUpperCase();
    }

    function parseSplitOptions(text) {
        var sourceText = htmlToText(text);
        if (!sourceText) {
            return null;
        }

        sourceText = sourceText.replace(/\u00a0/g, ' ').replace(/\r/g, '');

        // Flexible regex: matches A. A) (A) [A] patterns (case-insensitive)
        // Does NOT require whitespace before the marker — handles start-of-line,
        // after punctuation, or after whitespace.
        var markerPattern = /(\([A-E]\)|\[[A-E]\]|(?:^|[\s\n])([A-E])[.\)\]:\-])\s*/gi;

        // Strategy 1: split() with a single capturing group
        var splitParts = sourceText.split(/(\([A-E]\)|\[[A-E]\]|[A-E][.\)])\s*/gi);

        // If split didn't produce enough parts, try alternate regex with looser rules
        if (splitParts.length < 9) {
            splitParts = sourceText.split(/(?:^|[\s\n])(\([A-E]\)|\[[A-E]\]|[A-E][.\)\]:\-])\s*/gi);
        }

        var questionText = (splitParts[0] || '').trim();
        if (!questionText || splitParts.length < 9) {
            return null;
        }

        var optionTextByKey = {};
        var markerOrder = [];

        for (var index = 1; index < splitParts.length; index += 2) {
            if (splitParts[index] === undefined) continue;
            var optionKey = parseOptionMarker(splitParts[index]);
            var optionText = (splitParts[index + 1] || '').trim();

            if (!optionKey || optionTextByKey[optionKey] !== undefined) {
                continue;
            }

            optionTextByKey[optionKey] = optionText;
            markerOrder.push(optionKey);
        }

        if (markerOrder.length < 4) {
            return null;
        }

        if (markerOrder.slice(0, 4).join('') !== 'ABCD') {
            return null;
        }

        if (!optionTextByKey.A || !optionTextByKey.B || !optionTextByKey.C || !optionTextByKey.D) {
            return null;
        }

        return {
            question_text: questionText,
            option_a: optionTextByKey.A,
            option_b: optionTextByKey.B,
            option_c: optionTextByKey.C,
            option_d: optionTextByKey.D,
            option_e: optionTextByKey.E || ''
        };
    }

    function handleSplitOptions(text, options) {
        var settings = options || {};
        var editor = settings.editor || settings.ckeditorInstance || null;
        var inputText = typeof text === 'string' ? text : (editor && typeof editor.getData === 'function' ? editor.getData() : '');
        var parsed = parseSplitOptions(inputText);

        if (!parsed) {
            var errorMessage = 'Could not split question text. Please include four options marked A-D using A., (A), or [A].';
            if (typeof settings.toast === 'function') {
                settings.toast(errorMessage);
            } else if (window.toastr && typeof window.toastr.error === 'function') {
                window.toastr.error(errorMessage);
            } else {
                console.error(errorMessage);
            }
            return null;
        }

        if (typeof settings.setFormData === 'function') {
            settings.setFormData(function(formData) {
                return $.extend({}, formData, {
                    question_text: parsed.question_text,
                    option_a: parsed.option_a,
                    option_b: parsed.option_b,
                    option_c: parsed.option_c,
                    option_d: parsed.option_d
                });
            });
        }

        if (editor && typeof editor.setData === 'function') {
            editor.setData(textToEditorHtml(parsed.question_text));
        }

        return parsed;
    }

    function parseQuestionBlock(html) {
        var splitParsed = parseSplitOptions(html);
        if (splitParsed) {
            return {
                questionHtml: textToEditorHtml(splitParsed.question_text),
                optionA: textToEditorHtml(splitParsed.option_a),
                optionB: textToEditorHtml(splitParsed.option_b),
                optionC: textToEditorHtml(splitParsed.option_c),
                optionD: textToEditorHtml(splitParsed.option_d),
                optionE: splitParsed.option_e ? textToEditorHtml(splitParsed.option_e) : ''
            };
        }

        var text = htmlToText(html);
        if (!text) {
            return null;
        }

        var lines = text
            .split('\n')
            .map(function(line) { return line.trim(); })
            .filter(function(line) { return line !== ''; });

        if (lines.length < 5) {
            return null;
        }

        var optionIndexes = {};
        var optionKeys = ['A', 'B', 'C', 'D', 'E'];
        var requiredKeys = ['A', 'B', 'C', 'D'];

        lines.forEach(function(line, index) {
            var match = line.match(/^(?:[\(\[\{]\s*([A-E])\s*[\)\]\}]|([A-E]))(?:[\.\:\-\)]|\])?\s+(.+)$/i);
            var optionKey = match ? (match[1] || match[2] || '').toUpperCase() : '';
            if (match && optionIndexes[optionKey] === undefined) {
                optionIndexes[optionKey] = {
                    index: index,
                    value: match[3].trim()
                };
            }
        });

        if (requiredKeys.some(function(key) { return !optionIndexes[key]; })) {
            return null;
        }

        var optionStart = Math.min(optionIndexes.A.index, optionIndexes.B.index, optionIndexes.C.index, optionIndexes.D.index);
        if (optionIndexes.E) {
            optionStart = Math.min(optionStart, optionIndexes.E.index);
        }
        if (optionStart <= 0) {
            optionStart = -1;
        }

        if (optionStart > 0) {
            return {
                questionHtml: textToEditorHtml(lines.slice(0, optionStart).join('\n')),
                optionA: textToEditorHtml(optionIndexes.A.value),
                optionB: textToEditorHtml(optionIndexes.B.value),
                optionC: textToEditorHtml(optionIndexes.C.value),
                optionD: textToEditorHtml(optionIndexes.D.value),
                optionE: optionIndexes.E ? textToEditorHtml(optionIndexes.E.value) : ''
            };
        }

        var fullText = normalizeText(text);
        var markers = ['A', 'B', 'C', 'D', 'E'].map(function(letter) {
            var pattern = new RegExp('(?:\\(' + letter + '\\)|\\[' + letter + '\\]|\\b' + letter + '[\\.)\\]:-])', 'i');
            var match = pattern.exec(fullText);
            return match ? { letter: letter, index: match.index, length: match[0].length } : null;
        });

        if (markers.slice(0, 4).some(function(item) { return !item; })) {
            return null;
        }

        markers = markers.filter(function(item) { return !!item; });

        markers.sort(function(left, right) {
            return left.index - right.index;
        });

        if (markers[0].letter !== 'A' || markers[1].letter !== 'B' || markers[2].letter !== 'C' || markers[3].letter !== 'D') {
            return null;
        }

        if (markers[0].index <= 0) {
            return null;
        }

        var questionText = fullText.slice(0, markers[0].index).trim();
        var optionAText = fullText.slice(markers[0].index + markers[0].length, markers[1].index).trim();
        var optionBText = fullText.slice(markers[1].index + markers[1].length, markers[2].index).trim();
        var optionCText = fullText.slice(markers[2].index + markers[2].length, markers[3].index).trim();
        var optionDText = '';
        var optionEText = '';

        if (markers[4]) {
            optionDText = fullText.slice(markers[3].index + markers[3].length, markers[4].index).trim();
            optionEText = fullText.slice(markers[4].index + markers[4].length).trim();
        } else {
            optionDText = fullText.slice(markers[3].index + markers[3].length).trim();
        }

        if (!questionText || !optionAText || !optionBText || !optionCText || !optionDText) {
            return null;
        }

        return {
            questionHtml: textToEditorHtml(questionText),
            optionA: textToEditorHtml(optionAText),
            optionB: textToEditorHtml(optionBText),
            optionC: textToEditorHtml(optionCText),
            optionD: textToEditorHtml(optionDText),
            optionE: optionEText ? textToEditorHtml(optionEText) : ''
        };
    }

    function uploadBase64Image(dataUrl, uploadUrl) {
        return $.ajax({
            url: uploadUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                imageData: dataUrl
            }
        });
    }

    function replaceInlineImages(editor, uploadUrl) {
        if (!editor || editor.status !== 'ready' || !editor.document) {
            return;
        }

        var images = editor.document.$.querySelectorAll('img[src^="data:image/"]');
        Array.prototype.forEach.call(images, function(imageNode) {
            if (imageNode.getAttribute('data-uploading') === '1') {
                return;
            }

            imageNode.setAttribute('data-uploading', '1');
            uploadBase64Image(imageNode.getAttribute('src'), uploadUrl)
                .done(function(response) {
                    if (response && response.uploaded && response.url) {
                        imageNode.setAttribute('src', response.url);
                        imageNode.removeAttribute('data-uploading');
                        editor.fire('change');
                    } else {
                        imageNode.removeAttribute('data-uploading');
                    }
                })
                .fail(function() {
                    imageNode.removeAttribute('data-uploading');
                });
        });
    }

    function attachClipboardImageSupport(editor, uploadUrl) {
        editor.on('paste', function(evt) {
            var dataTransfer = evt.data && evt.data.dataTransfer ? evt.data.dataTransfer : null;
            if (!dataTransfer || typeof dataTransfer.getFilesCount !== 'function') {
                setTimeout(function() {
                    replaceInlineImages(editor, uploadUrl);
                }, 300);
                return;
            }

            var htmlData = '';
            var textData = '';
            try {
                htmlData = dataTransfer.getData('text/html') || '';
            } catch (ignored) {
                htmlData = '';
            }
            try {
                textData = dataTransfer.getData('text/plain') || '';
            } catch (ignored) {
                textData = '';
            }

            var hasTextPaste = textData.trim() !== '';
            var hasRichHtmlPaste = htmlData.trim() !== '' && !/^<img\b/i.test(htmlData.trim());
            if (hasTextPaste || hasRichHtmlPaste) {
                setTimeout(function() {
                    replaceInlineImages(editor, uploadUrl);
                }, 300);
                return;
            }

            var file = null;
            for (var index = 0; index < dataTransfer.getFilesCount(); index += 1) {
                var currentFile = dataTransfer.getFile(index);
                if (currentFile && currentFile.type && currentFile.type.indexOf('image/') === 0) {
                    file = currentFile;
                    break;
                }
            }

            if (!file) {
                setTimeout(function() {
                    replaceInlineImages(editor, uploadUrl);
                }, 300);
                return;
            }

            evt.cancel();
            var placeholderId = 'dlhs-uploading-' + Date.now();
            editor.insertHtml('<p><img src="" data-placeholder-id="' + placeholderId + '" alt="Uploading image..." /></p>');

            var formData = new FormData();
            formData.append('upload', file);

            $.ajax({
                url: uploadUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function(response) {
                var placeholder = editor.document && editor.document.$
                    ? editor.document.$.querySelector('img[data-placeholder-id="' + placeholderId + '"]')
                    : null;

                if (!placeholder) {
                    return;
                }

                if (response && response.uploaded && response.url) {
                    placeholder.setAttribute('src', response.url);
                    placeholder.removeAttribute('data-placeholder-id');
                    editor.fire('change');
                } else {
                    placeholder.parentNode.removeChild(placeholder);
                    alert((response && response.message) ? response.message : 'The pasted image could not be uploaded.');
                }
            }).fail(function() {
                var placeholder = editor.document && editor.document.$
                    ? editor.document.$.querySelector('img[data-placeholder-id="' + placeholderId + '"]')
                    : null;
                if (placeholder && placeholder.parentNode) {
                    placeholder.parentNode.removeChild(placeholder);
                }
                alert('The pasted image could not be uploaded.');
            });
        });

        editor.on('afterPaste', function() {
            setTimeout(function() {
                var cleaned = cleanWordPasteHtml(editor.getData());
                if (cleaned !== editor.getData()) {
                    editor.setData(cleaned);
                }
                replaceInlineImages(editor, uploadUrl);
            }, 300);
        });

        editor.on('change', function() {
            if (editor._dlhsImageSyncTimer) {
                clearTimeout(editor._dlhsImageSyncTimer);
            }
            editor._dlhsImageSyncTimer = setTimeout(function() {
                replaceInlineImages(editor, uploadUrl);
            }, 500);
        });
    }

    function createEditor(id, uploadUrl, extraConfig) {
        var config = $.extend(true, {
            height: 260,
            filebrowserUploadUrl: uploadUrl,
            allowedContent: true,
            extraAllowedContent: 'img[*]{*}(*);table[*]{*}(*);span[*]{*}(*)',
            entities: false,
            basicEntities: false,
            entities_latin: false,
            entities_greek: false,
            pasteFromWordPromptCleanup: false,
            pasteFromWordRemoveFontStyles: true,
            pasteFromWordRemoveStyles: true,
            forcePasteAsPlainText: false,
            bodyClass: 'dlhs-editor-body',
            format_tags: 'p;h1;h2;h3;pre',
            removeDialogTabs: 'image:advanced;link:advanced',
            toolbar: [
                { name: 'clipboard', items: ['Undo', 'Redo', 'PasteText', 'PasteFromWord'] },
                { name: 'styles', items: ['Format', 'Font', 'FontSize'] },
                { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', 'RemoveFormat'] },
                { name: 'paragraph', items: ['NumberedList', 'BulletedList', 'Outdent', 'Indent', 'Blockquote'] },
                { name: 'insert', items: ['Image', 'Table', 'HorizontalRule', 'SpecialChar'] },
                { name: 'links', items: ['Link', 'Unlink'] },
                { name: 'colors', items: ['TextColor', 'BGColor'] },
                { name: 'document', items: ['Source'] }
            ]
        }, extraConfig || {});

        var editor = CKEDITOR.replace(id, config);
        editor.on('instanceReady', function() {
            attachClipboardImageSupport(editor, uploadUrl);
        });

        return editor;
    }

    window.DLHSQuestionAuthoring = {
        createEditor: createEditor,
        escapeHtml: escapeHtml,
        handleSplitOptions: handleSplitOptions,
        htmlToText: htmlToText,
        looksLikePlaceholder: looksLikePlaceholder,
        normalizeText: normalizeText,
        parseQuestionBlock: parseQuestionBlock,
        parseSplitOptions: parseSplitOptions,
        textToEditorHtml: textToEditorHtml
    };
    window.handleSplitOptions = handleSplitOptions;
})(window, jQuery);
