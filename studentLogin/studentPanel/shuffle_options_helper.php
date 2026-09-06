<?php
/**
 * ANSWER OPTION SHUFFLING HELPER
 * 
 * This file contains functions to safely shuffle answer options (A, B, C, D, E)
 * while maintaining grading accuracy.
 * 
 * HOW IT WORKS:
 * - Uses student ID + question ID as seed for deterministic shuffling
 * - Same student always sees same option order for a given question
 * - Different students see different option orders
 * - Correct answer position is automatically updated
 * - Grading remains 100% accurate
 */

/**
 * Shuffle answer options for a student
 * 
 * @param int $studentId - Student's ID (for consistent shuffling)
 * @param int $questionId - Question's ID (for consistent shuffling)
 * @param string $optionA - Original option A text
 * @param string $optionB - Original option B text
 * @param string $optionC - Original option C text
 * @param string $optionD - Original option D text
 * @param string $optionE - Original option E text
 * @param string $correctOption - Original correct option (A, B, C, D, or E)
 * 
 * @return array Associative array with shuffled options and new correct answer
 *               ['optionA' => 'shuffled text', 'optionB' => ..., 'correctOption' => 'new letter']
 */
function shuffleAnswerOptions($studentId, $questionId, $optionA, $optionB, $optionC, $optionD, $optionE, $correctOption) {
    // Create original options array with their positions (only non-empty options)
    $options = [];
    $optionLetters = ['A', 'B', 'C', 'D', 'E'];
    $optionValues = [$optionA, $optionB, $optionC, $optionD, $optionE];
    
    // Only include non-empty options
    for ($i = 0; $i < 5; $i++) {
        if (!empty(trim($optionValues[$i]))) {
            $options[$optionLetters[$i]] = $optionValues[$i];
        }
    }
    
    // If less than 2 options, don't shuffle (no point)
    if (count($options) < 2) {
        return [
            'optionA' => $optionA,
            'optionB' => $optionB,
            'optionC' => $optionC,
            'optionD' => $optionD,
            'optionE' => $optionE,
            'correctOption' => $correctOption
        ];
    }
    
    // Get just the values (option texts) and letters
    $optionTexts = array_values($options);
    $availableLetters = array_keys($options);
    $numOptions = count($options);
    
    // Use student ID + question ID as seed for consistent shuffling
    // Same student + same question = always same shuffle
    $seed = $studentId * 1000000 + $questionId;
    mt_srand($seed);
    
    // Create array of indices to shuffle
    $indices = range(0, $numOptions - 1);
    
    // Fisher-Yates shuffle with seeded random
    for ($i = count($indices) - 1; $i > 0; $i--) {
        $j = mt_rand(0, $i);
        // Swap
        $temp = $indices[$i];
        $indices[$i] = $indices[$j];
        $indices[$j] = $temp;
    }
    
    // Find where the correct option ended up
    $correctIndex = array_search($correctOption, $availableLetters);
    if ($correctIndex !== false) {
        $newCorrectPosition = array_search($correctIndex, $indices);
        $newCorrectOption = $availableLetters[$newCorrectPosition];
    } else {
        $newCorrectOption = $correctOption; // Fallback
    }
    
    // Build shuffled options array - assign to first N positions
    $shuffled = [
        'optionA' => '',
        'optionB' => '',
        'optionC' => '',
        'optionD' => '',
        'optionE' => '',
        'correctOption' => $newCorrectOption
    ];
    
    $outputLetters = ['A', 'B', 'C', 'D', 'E'];
    for ($i = 0; $i < $numOptions; $i++) {
        $shuffled['option' . $outputLetters[$i]] = $optionTexts[$indices[$i]];
    }
    
    // Reset RNG to avoid affecting other random operations
    mt_srand();
    
    return $shuffled;
}

/**
 * REVERSE-TRANSLATE a displayed option letter to original option letter
 * 
 * This is CRITICAL for grading! When a student selects an answer from their
 * shuffled options, we need to translate it back to the original option letter
 * before saving, so that grading works correctly.
 * 
 * @param int $studentId - Student's ID
 * @param int $questionId - Question's ID
 * @param string $displayedOption - The option letter the student selected (A-E)
 * @param string $optionA - Original option A text
 * @param string $optionB - Original option B text
 * @param string $optionC - Original option C text
 * @param string $optionD - Original option D text
 * @param string $optionE - Original option E text
 * 
 * @return string The ORIGINAL option letter that corresponds to the displayed selection
 * 
 * Example:
 * - Original: A=Paris✓, B=London, C=Berlin, D=Madrid, E=Rome
 * - Student sees (shuffled): A=Berlin, B=Paris✓, C=Madrid, D=London, E=Rome
 * - Student selects: "B" (Paris, which is correct)
 * - This function returns: "A" (the original letter for Paris)
 * - We save "A" so grading compares "A" with original correct answer "A" ✓
 */
function reverseTranslateOption($studentId, $questionId, $displayedOption, $optionA, $optionB, $optionC, $optionD, $optionE) {
    // If no displayed option, return empty
    if (empty($displayedOption)) {
        return '';
    }
    
    // Create original options array (only non-empty)
    $options = [];
    $optionLetters = ['A', 'B', 'C', 'D', 'E'];
    $optionValues = [$optionA, $optionB, $optionC, $optionD, $optionE];
    
    for ($i = 0; $i < 5; $i++) {
        if (!empty(trim($optionValues[$i]))) {
            $options[$optionLetters[$i]] = $optionValues[$i];
        }
    }
    
    // If less than 2 options, no shuffling happened
    if (count($options) < 2) {
        return $displayedOption;
    }
    
    $optionTexts = array_values($options);
    $availableLetters = array_keys($options);
    $numOptions = count($options);
    
    // Use SAME seed as shuffleAnswerOptions
    $seed = $studentId * 1000000 + $questionId;
    mt_srand($seed);
    
    // Create array of indices to shuffle (SAME algorithm)
    $indices = range(0, $numOptions - 1);
    
    // Fisher-Yates shuffle with seeded random (SAME as shuffleAnswerOptions)
    for ($i = count($indices) - 1; $i > 0; $i--) {
        $j = mt_rand(0, $i);
        $temp = $indices[$i];
        $indices[$i] = $indices[$j];
        $indices[$j] = $temp;
    }
    
    // Reset RNG
    mt_srand();
    
    // Build the shuffle mapping
    // $indices[i] tells us which original option is now at position i
    $outputLetters = ['A', 'B', 'C', 'D', 'E'];
    
    // Find which position the student's displayed option is
    $displayedPosition = array_search($displayedOption, $outputLetters);
    
    // If invalid position or beyond available options, return as-is
    if ($displayedPosition === false || $displayedPosition >= $numOptions) {
        return $displayedOption;
    }
    
    // Find which original option is at that displayed position
    $originalIndex = $indices[$displayedPosition];
    $originalLetter = $availableLetters[$originalIndex];
    
    return $originalLetter;
}

/**
 * Translate a stored ORIGINAL option letter back to the displayed shuffled letter.
 *
 * This is used when loading previously saved answers for a student. Answers are
 * stored using the original option letters for accurate grading, but the UI must
 * highlight the shuffled letter that the student actually saw.
 *
 * @param int $studentId
 * @param int $questionId
 * @param string $originalOption
 * @param string $optionA
 * @param string $optionB
 * @param string $optionC
 * @param string $optionD
 * @param string $optionE
 *
 * @return string
 */
function translateOriginalOptionToDisplayed($studentId, $questionId, $originalOption, $optionA, $optionB, $optionC, $optionD, $optionE) {
    if (empty($originalOption)) {
        return '';
    }

    $options = [];
    $optionLetters = ['A', 'B', 'C', 'D', 'E'];
    $optionValues = [$optionA, $optionB, $optionC, $optionD, $optionE];

    for ($i = 0; $i < 5; $i++) {
        if (!empty(trim($optionValues[$i]))) {
            $options[$optionLetters[$i]] = $optionValues[$i];
        }
    }

    if (count($options) < 2) {
        return $originalOption;
    }

    $availableLetters = array_keys($options);
    $numOptions = count($options);

    $seed = $studentId * 1000000 + $questionId;
    mt_srand($seed);

    $indices = range(0, $numOptions - 1);
    for ($i = count($indices) - 1; $i > 0; $i--) {
        $j = mt_rand(0, $i);
        $temp = $indices[$i];
        $indices[$i] = $indices[$j];
        $indices[$j] = $temp;
    }

    mt_srand();

    $originalIndex = array_search($originalOption, $availableLetters, true);
    if ($originalIndex === false) {
        return $originalOption;
    }

    $displayedPosition = array_search($originalIndex, $indices, true);
    if ($displayedPosition === false) {
        return $originalOption;
    }

    $outputLetters = ['A', 'B', 'C', 'D', 'E'];
    return isset($outputLetters[$displayedPosition]) ? $outputLetters[$displayedPosition] : $originalOption;
}

/**
 * Example usage:
 * 
 * $result = shuffleAnswerOptions(
 *     707,           // Student ID
 *     101,           // Question ID
 *     'Paris',       // Option A
 *     'London',      // Option B
 *     'Berlin',      // Option C
 *     'Madrid',      // Option D
 *     'Rome',        // Option E
 *     'A'            // Correct answer (Paris)
 * );
 * 
 * // Student 707 might see:
 * // A. Berlin    (was C)
 * // B. Paris     (was A) ← Now correct answer is B
 * // C. Madrid    (was D)
 * // D. London    (was B)
 * // E. Rome      (was E)
 * // Correct: B
 * 
 * // Student selects "B" (Paris)
 * $originalOption = reverseTranslateOption(707, 101, 'B', 'Paris', 'London', 'Berlin', 'Madrid', 'Rome');
 * // Returns: "A" (the original letter for Paris)
 * // Save "A" to database for accurate grading!
 * 
 * // Student 708 would see a different shuffle
 * // But student 707 would ALWAYS see this same shuffle for question 101
 */
?>
