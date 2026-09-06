<?php
session_start();
include "../../db_connection/dlhs_db_connection.php";

header('Content-Type: application/json; charset=utf-8');

$testId = isset($_POST['testId']) ? intval($_POST['testId']) : 0;
$response = array();

function sanitize_html_local($html) {
	$allowed_tags = [ 'p','br','strong','b','em','i','ul','ol','li','u','blockquote','h1','h2','h3','h4','h5','h6','img' ];
	// Safe attributes allowed for img tags
	$img_allowed_attrs = [ 'src','alt','width','height','style','class','title' ];
	// Safe attributes allowed for other tags (minimal set for formatting)
	$other_allowed_attrs = [ 'style','class' ];
	
	libxml_use_internal_errors(true);
	$doc = new DOMDocument();
	$doc->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
	$body = $doc->getElementsByTagName('div')->item(0);
	$xpath = new DOMXPath($doc);
	foreach ($xpath->query('//script|//style') as $n) { $n->parentNode->removeChild($n); }
	$nodes = $body->getElementsByTagName('*');
	$toProcess = [];
	foreach ($nodes as $node) { $toProcess[] = $node; }
	foreach ($toProcess as $node) {
		$tag = strtolower($node->nodeName);
		if (!in_array($tag, $allowed_tags)) {
			while ($node->firstChild) { $node->parentNode->insertBefore($node->firstChild, $node); }
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
	$inner = '';
	foreach ($body->childNodes as $child) { $inner .= $doc->saveHTML($child); }
	return trim($inner);
}

if ($testId > 0) {
	$stmt = $connection->prepare("SELECT question FROM essay_questions WHERE testId = ? LIMIT 1");
	if ($stmt) {
		$stmt->bind_param('i', $testId);
		$stmt->execute();
		$stmt->bind_result($q);
		if ($stmt->fetch()) {
			$san = sanitize_html_local(html_entity_decode($q, ENT_QUOTES | ENT_HTML5));
			$response[] = array('question' => $san);
		}
		$stmt->close();
	}
}

echo json_encode($response);
exit;
?>
