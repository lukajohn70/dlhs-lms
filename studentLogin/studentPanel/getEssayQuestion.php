<?php
session_start();
include "../../db_connection/dlhs_db_connection.php";

header('Content-Type: application/json; charset=utf-8');

$return_arr = array();

// Accept numeric testId only
$testId = isset($_POST['testId']) ? intval($_POST['testId']) : 0;

// Require a valid session student id
$studentId = isset($_SESSION['studentId']) ? intval($_SESSION['studentId']) : 0;

if ($testId > 0 && $studentId > 0) {
	// Lookup the tests row to get the examinees table name
	$stmtT = $connection->prepare("SELECT examineesTableName FROM tests WHERE testId = ? LIMIT 1");
	if ($stmtT) {
		$stmtT->bind_param('i', $testId);
		$stmtT->execute();
		$stmtT->bind_result($examineesTableName);
		if ($stmtT->fetch()) {
			$stmtT->close();
			// Basic validation of the table name (alphanumeric + underscore)
			if (!preg_match('/^[a-zA-Z0-9_]+$/', $examineesTableName)) {
				echo json_encode(array());
				exit;
			}

			// Check the examinees table to confirm this student is actually added to the test
			$safeTable = $examineesTableName; // validated above
			$checkSql = "SELECT 1 FROM `" . $safeTable . "` WHERE examineeUserId = ? LIMIT 1";
			$stmtC = $connection->prepare($checkSql);
			if ($stmtC) {
				$stmtC->bind_param('i', $studentId);
				$stmtC->execute();
				$stmtC->store_result();
				if ($stmtC->num_rows > 0) {
					// Student is authorized for this test — fetch the essay
					$stmtC->close();
					$stmtQ = $connection->prepare("SELECT question FROM essay_questions WHERE testId = ? LIMIT 1");
					if ($stmtQ) {
						$stmtQ->bind_param('i', $testId);
						$stmtQ->execute();
						$stmtQ->bind_result($raw_question);
						if ($stmtQ->fetch()) {
							$return_arr = array(array('question' => $raw_question));
						}
						$stmtQ->close();
					}
				} else {
					// Not authorized — no examinee row
					$stmtC->close();
					echo json_encode(array());
					exit;
				}
			} else {
				// Could not prepare check statement — fail closed
				echo json_encode(array());
				exit;
			}
		} else {
			$stmtT->close();
		}
	}
}

// Convert malformed input tags to proper img tags before sanitizing
function fix_input_tags_to_img($html) {
	// Convert <input type="image" ...> to <img ...>
	$html = preg_replace_callback(
		'#<input\s+([^>]*?)\s*type\s*=\s*["\']?image["\']?([^>]*)/?>#i',
		function($matches) {
			// Combine the attributes, removing 'type' attribute
			$attrs = $matches[1] . ' ' . $matches[2];
			// Remove duplicate/malformed attributes
			$attrs = preg_replace('/\s+type\s*=\s*["\']?image["\']/i', '', $attrs);
			// Clean up spacing
			$attrs = trim(preg_replace('/\s+/', ' ', $attrs));
			return '<img ' . $attrs . ' />';
		},
		$html
	);
	return $html;
}

// Convert old relative image paths to absolute paths for compatibility with AJAX loading
function fix_image_paths($html) {
	// Get application base path by going up 2 directory levels from current script
	// Current: /dlhs/studentLogin/studentPanel/getEssayQuestion.php
	// We want: /dlhs/
	$script_dir = dirname($_SERVER['SCRIPT_NAME']); // /dlhs/studentLogin/studentPanel
	$base_path = dirname(dirname($script_dir)) . '/'; // /dlhs/
	
	// Replace old relative paths with absolute paths
	// Pattern: src="../../questUploadImages/path/to/image.jpg" or any variant
	$html = preg_replace_callback(
		'#src\s*=\s*["\']?(?:\.\./)*questUploadImages/([^"\'>\s]+)["\']?#i',
		function($matches) use ($base_path) {
			$image_path = $matches[1]; // e.g., essayImages/1234.jpg
			return 'src="' . $base_path . 'questUploadImages/' . $image_path . '"';
		},
		$html
	);
	
	return $html;
}

// Simple HTML sanitizer: allow a set of formatting tags and preserve safe attributes for images
function sanitize_html($html) {
	$allowed_tags = [ 'p','br','strong','b','em','i','ul','ol','li','u','blockquote','h1','h2','h3','h4','h5','h6','img' ];
	// Safe attributes allowed for img tags
	$img_allowed_attrs = [ 'src','alt','width','height','style','class','title' ];
	// Safe attributes allowed for other tags (minimal set for formatting)
	$other_allowed_attrs = [ 'style','class' ];
	
	libxml_use_internal_errors(true);
	$doc = new DOMDocument();
	// Load as UTF-8
	$doc->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
	$body = $doc->getElementsByTagName('div')->item(0);

	$xpath = new DOMXPath($doc);
	// Remove script/style nodes
	foreach ($xpath->query('//script|//style') as $n) { $n->parentNode->removeChild($n); }

	// Traverse nodes and remove disallowed tags or attributes
	$nodes = $body->getElementsByTagName('*');
	// Collect nodes to process (can't modify live NodeList while iterating safely)
	$toProcess = [];
	foreach ($nodes as $node) { $toProcess[] = $node; }

	foreach ($toProcess as $node) {
		$tag = strtolower($node->nodeName);
		if (!in_array($tag, $allowed_tags)) {
			// unwrap node: move its children up
			while ($node->firstChild) {
				$node->parentNode->insertBefore($node->firstChild, $node);
			}
			$node->parentNode->removeChild($node);
			continue;
		}
		
		// Handle attributes based on tag type
		if ($node->hasAttributes()) {
			$attrs = [];
			foreach ($node->attributes as $attr) { $attrs[] = $attr->name; }
			
			// For img tags, allow specific safe attributes
			if ($tag === 'img') {
				foreach ($attrs as $a) {
					$attrLower = strtolower($a);
					if (!in_array($attrLower, $img_allowed_attrs)) {
						$node->removeAttribute($a);
					} else {
						// Sanitize src attribute to prevent XSS (only allow http, https, or relative paths)
						if ($attrLower === 'src') {
							$srcValue = $node->getAttribute($a);
							// Remove javascript: and data: protocols for security
							if (preg_match('/^(javascript|data):/i', $srcValue)) {
								$node->removeAttribute($a);
							}
						}
					}
				}
			} else {
				// For other tags, allow minimal safe attributes
				foreach ($attrs as $a) {
					$attrLower = strtolower($a);
					if (!in_array($attrLower, $other_allowed_attrs)) {
						$node->removeAttribute($a);
					}
				}
			}
		}
	}

	// Get innerHTML of the wrapper div
	$inner = '';
	foreach ($body->childNodes as $child) { $inner .= $doc->saveHTML($child); }
	// Trim and return
	return trim($inner);
}

// If we have a sanitized HTML, return it
if (!empty($return_arr)) {
	$html = $return_arr[0]['question'];
	// First convert malformed input tags to proper img tags
	$html = fix_input_tags_to_img($html);
	// Fix old relative image paths to absolute paths for existing essays
	$html = fix_image_paths($html);
	// Then sanitize
	$san = sanitize_html(html_entity_decode($html, ENT_QUOTES | ENT_HTML5));
	echo json_encode(array(array('question' => $san)));
} else {
	echo json_encode(array());
}
exit;
?>
