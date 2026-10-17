/**
 * DLHS Bulk Paste Question Parser
 * Handles parsing of text containing objective questions and options.
 */
var DLHSBulkParser = {
    /**
     * Parses the raw text into an array of question objects.
     * @param {string} text 
     * @returns {Array}
     */
    parse: function(text) {
        if (!text || text.trim() === '') return [];

        // Normalize line endings and whitespace
        text = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
        
        // Split text by lines that look like a question number start: "1.", "1)", "(1)", "Q1."
        // We look for a digit at the start of a line or after a double newline
        var questionStarts = text.split(/\n(?=\s*\d+[\.\)])|^\s*\d+[\.\)]/m);
        
        var parsedQuestions = [];
        
        questionStarts.forEach(function(segment) {
            segment = segment.trim();
            if (!segment) return;
            
            // Try to extract question number if it was captured in the segment
            // (Since split might have removed it depending on the regex, 
            // but we use lookahead to keep it in the next segment)
            
            // 1. Identify the question text vs the options section
            // Options usually start with A. or [A] or (A)
            var optionRegex = /(?:\s|^|\[|\()([A-E])(?:[\.\)\]])\s+/i;
            var parts = segment.split(new RegExp('(?=' + optionRegex.source + ')', 'i'));
            
            if (parts.length < 2) return; // No options found
            
            var questionText = parts[0].replace(/^\s*\d+[\.\)]\s*/, '').trim();
            var options = {
                A: '', B: '', C: '', D: '', E: ''
            };
            
            for (var i = 1; i < parts.length; i++) {
                var optSegment = parts[i].trim();
                var match = optSegment.match(optionRegex);
                if (match) {
                    var letter = match[1].toUpperCase();
                    var content = optSegment.replace(match[0], '').trim();
                    // Clean up trailing options if they were bundled
                    // (e.g. "Option A [B] Option B")
                    options[letter] = content;
                }
            }
            
            if (questionText && options.A && options.B) {
                parsedQuestions.push({
                    question: questionText,
                    optionA: options.A,
                    optionB: options.B,
                    optionC: options.C,
                    optionD: options.D,
                    optionE: options.E,
                    correctOption: 'A', // Default, teacher will set in preview
                    mark: '1'
                });
            }
        });
        
        return parsedQuestions;
    }
};
