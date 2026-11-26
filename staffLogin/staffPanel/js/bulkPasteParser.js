/**
 * DLHS Bulk Paste Question Parser
 * Handles parsing of text and rich HTML containing objective questions and options.
 * Preserves all formatting (bold, italics, underlines, superscripts, subscripts, math formulas, tables, etc.).
 */
var DLHSBulkParser = {
    /**
     * Cleans raw HTML while preserving genuine formatting tags and styles.
     * @param {string} html 
     * @returns {string}
     */
    cleanRawHtml: function(html) {
        if (!html) return '';
        var clean = html
            // Remove comments and Office/Word XML metadata
            .replace(/<!--[\s\S]*?-->/gi, '')
            .replace(/<!\[if[\s\S]*?<!\[endif\]>/gi, '')
            .replace(/<o:p[\s\S]*?<\/o:p>/gi, '')
            .replace(/<xml[\s\S]*?<\/xml>/gi, '')
            .replace(/<style[\s\S]*?<\/style>/gi, '')
            .replace(/<script[\s\S]*?<\/script>/gi, '')
            .replace(/<meta[\s\S]*?>/gi, '')
            .replace(/<link[\s\S]*?>/gi, '')
            // Strip bloated MSO styles and classes from Word
            .replace(/\s*mso-[^:"';\s]+:[^;"']+(;)?/gi, '')
            .replace(/\s*class\s*=\s*["']?Mso[a-zA-Z0-9]+["']?/gi, '')
            // Normalize non-breaking spaces
            .replace(/&nbsp;/gi, ' ')
            // Normalize Windows/Mac linebreaks
            .replace(/\r\n/g, '\n')
            .replace(/\r/g, '\n');

        // Insert newlines around block-level elements so line/question boundaries are clear
        clean = clean
            .replace(/<(?:p|div|h[1-6]|blockquote|tr|li)(?:\s+[^>]*)?>/gi, '\n$&')
            .replace(/<\/(?:p|div|h[1-6]|blockquote|tr|li)>/gi, '$&\n')
            .replace(/<br\s*\/?>/gi, '$&\n');

        return clean;
    },

    _scratchDiv: null,

    /**
     * Cleans an extracted HTML fragment: strips leading/trailing breaks, balances tags.
     * @param {string} html 
     * @returns {string}
     */
    cleanFragment: function(html) {
        if (!html) return '';
        var s = html.trim();
        // Remove leading/trailing breaks, spaces, newlines, and orphaned tags
        s = s.replace(/^(?:<\/(?:p|div|span|b|strong|i|em|u|font)>|\s*<br\s*\/?>|\s*&nbsp;|\s*\n)+/gi, '');
        s = s.replace(/(?:<\/(?:p|div|span|b|strong|i|em|u|font)>|\s*<br\s*\/?>|\s*&nbsp;|\s*\n)+$/gi, '');
        s = s.replace(/^(?:<(?:p|div|span|b|strong|i|em|u|font)[^>]*>|\s*<br\s*\/?>|\s*&nbsp;|\s*\n)+/gi, '');
        s = s.replace(/(?:<(?:p|div|span|b|strong|i|em|u|font)[^>]*>|\s*<br\s*\/?>|\s*&nbsp;|\s*\n)+$/gi, '');

        // If string ends with </p> but does not contain <p>, strip the trailing </p>
        if (!/<p(?:\s+[^>]*)?>/i.test(s) && /<\/p>$/i.test(s)) {
            s = s.replace(/<\/p>$/i, '');
        }

        // If the fragment is wrapped in a single <p>...</p>, unwrap it to keep inline styling clean
        if (/^<p(?:\s+[^>]*)?>/i.test(s) && /<\/p>$/i.test(s)) {
            var pOpenCount = (s.match(/<p(?:\s+[^>]*)?>/gi) || []).length;
            if (pOpenCount === 1) {
                s = s.replace(/^<p(?:\s+[^>]*)?>/i, '').replace(/<\/p>$/i, '').trim();
            }
        }

        // Strip leading question markers if still present (e.g. "1.", "Q1.")
        s = s.replace(/^\s*(?:<(?:b|strong|i|em|u|span|font)[^>]*>)*\s*(?:(?:Q|Question)\s*[\.\:\-]*)?(?:\(?\d+[\.\)\:\-]|\[\d+\]|\(\d+\))\s*(?:<\/(?:b|strong|i|em|u|span|font)>)*\s*/gi, '');

        // If plain text (no HTML tags) and contains newlines, convert \n to <br> for display
        if (!/<[a-z][\s\S]*>/i.test(s)) {
            s = s.replace(/\n/g, '<br>');
        }

        s = s.trim();

        // In browser: Setting innerHTML balances unclosed/misnested tags automatically
        // Re-use scratch div instead of allocating 600 distinct DOM elements
        if (/<[a-z]/i.test(s) && typeof document !== 'undefined' && document.createElement) {
            if (!this._scratchDiv) {
                this._scratchDiv = document.createElement('div');
            }
            this._scratchDiv.innerHTML = s;
            while (this._scratchDiv.firstChild && (
                (this._scratchDiv.firstChild.nodeType === 1 && this._scratchDiv.firstChild.tagName.toLowerCase() === 'br') ||
                (this._scratchDiv.firstChild.nodeType === 3 && this._scratchDiv.firstChild.nodeValue.trim() === '')
            )) {
                this._scratchDiv.removeChild(this._scratchDiv.firstChild);
            }
            while (this._scratchDiv.lastChild && (
                (this._scratchDiv.lastChild.nodeType === 1 && this._scratchDiv.lastChild.tagName.toLowerCase() === 'br') ||
                (this._scratchDiv.lastChild.nodeType === 3 && this._scratchDiv.lastChild.nodeValue.trim() === '')
            )) {
                this._scratchDiv.removeChild(this._scratchDiv.lastChild);
            }
            var res = this._scratchDiv.innerHTML.trim();
            this._scratchDiv.innerHTML = '';
            return res;
        }

        return s;
    },

    /**
     * Parses the raw HTML or text into an array of question objects.
     * @param {string} input 
     * @returns {Array}
     */
    parse: function(input) {
        if (!input || input.trim() === '') return [];

        var isHtml = /<[a-z][\s\S]*>/i.test(input);
        var clean = isHtml ? this.cleanRawHtml(input) : input.replace(/\r\n/g, '\n').replace(/\r/g, '\n');

        // Regex to find start of questions:
        // A question starts at start of input or after a newline/block break
        // followed by optional inline/block tags, question number, and optional closing tags
        var qSplitRegex = /(?:^|\n)\s*(?:<(?:p|div|b|strong|i|em|u|span|font)[^>]*>)*\s*(?:(?:Q|Question)\s*[\.\:\-]*)?(?:\(?\b\d+[\.\)\:\-]|\[\d+\]|\(\d+\))\s*(?:<\/(?:b|strong|i|em|u|span|font)>)*\s*/gi;

        var matches = [];
        var match;
        qSplitRegex.lastIndex = 0;
        while ((match = qSplitRegex.exec(clean)) !== null) {
            matches.push({
                index: match.index,
                length: match[0].length,
                fullMatch: match[0]
            });
        }

        var segments = [];
        if (matches.length > 0) {
            for (var i = 0; i < matches.length; i++) {
                var start = matches[i].index + matches[i].length;
                var end = (i + 1 < matches.length) ? matches[i + 1].index : clean.length;
                var segment = clean.substring(start, end).trim();
                if (segment) {
                    segments.push(segment);
                }
            }
        } else {
            // No question numbers detected; treat entire input as single question
            segments.push(clean.trim());
        }

        var parsedQuestions = [];
        var self = this;

        segments.forEach(function(segment) {
            var parsed = self.parseSingleQuestion(segment);
            if (parsed) {
                parsedQuestions.push(parsed);
            }
        });

        return parsedQuestions;
    },

    /**
     * Parses a single question segment into question text and options A-E.
     * @param {string} segment 
     * @returns {Object|null}
     */
    parseSingleQuestion: function(segment) {
        if (!segment) return null;

        // Option regex to find markers like [A], (A), A., A), A:, A -
        // optionally wrapped in tags like <b>A.</b>, <strong>[A]</strong>, etc.
        // preceded by newline, whitespace, bracket, or tag boundary
        var optRegex = /(?:^|\n|[ \t]{1,30})(?:<(?:p|div|b|strong|i|em|u|span|font)[^>]*>)*(?:\[([A-Ea-e])\]|\(([A-Ea-e])\)|(?:(?:Option|Opt)\s+)?([A-Ea-e])[\.\)\:\-])(?:<\/(?:b|strong|i|em|u|span|font)>)*(?:\s|&nbsp;|<br\s*\/?>)*/gi;

        var matches = [];
        var match;
        optRegex.lastIndex = 0;
        while ((match = optRegex.exec(segment)) !== null) {
            var letter = (match[1] || match[2] || match[3] || '').toUpperCase();
            matches.push({
                letter: letter,
                index: match.index,
                length: match[0].length,
                fullMatch: match[0]
            });
        }

        if (matches.length < 2) return null;

        // Find standard sequence of options: A, B, C, D, (E)
        var firstA = -1;
        for (var i = 0; i < matches.length; i++) {
            if (matches[i].letter === 'A') {
                firstA = i;
                break;
            }
        }

        if (firstA === -1) return null;

        var validSequence = [matches[firstA]];
        var expected = 'B';
        for (var j = firstA + 1; j < matches.length; j++) {
            if (matches[j].letter === expected) {
                validSequence.push(matches[j]);
                expected = String.fromCharCode(expected.charCodeAt(0) + 1);
                if (expected > 'E') break;
            }
        }

        // Must have at least options A and B
        if (validSequence.length < 2) return null;

        var questionHtml = this.cleanFragment(segment.substring(0, validSequence[0].index));
        var options = { A: '', B: '', C: '', D: '', E: '' };

        for (var k = 0; k < validSequence.length; k++) {
            var cur = validSequence[k];
            var start = cur.index + cur.length;
            var end = (k + 1 < validSequence.length) ? validSequence[k + 1].index : segment.length;
            var optContent = this.cleanFragment(segment.substring(start, end));
            options[cur.letter] = optContent;
        }

        if (questionHtml && options.A && options.B) {
            return {
                question: questionHtml,
                optionA: options.A,
                optionB: options.B,
                optionC: options.C,
                optionD: options.D,
                optionE: options.E,
                correctOption: 'A', // Default, teacher can change in preview
                mark: '1'
            };
        }

        return null;
    }
};

if (typeof module !== 'undefined' && module.exports) {
    module.exports = DLHSBulkParser;
}
