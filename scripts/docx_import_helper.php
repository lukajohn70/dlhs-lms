<?php

require_once __DIR__ . '/question_authoring_helper.php';

if (!function_exists('dlhsDocxRelationshipMap')) {
    function dlhsDocxRelationshipMap(ZipArchive $zip)
    {
        $map = array();
        $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
        if ($relsXml === false) {
            return $map;
        }

        $rels = new DOMDocument();
        if (!@$rels->loadXML($relsXml)) {
            return $map;
        }

        $xpath = new DOMXPath($rels);
        $xpath->registerNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');

        foreach ($xpath->query('//rel:Relationship') as $relationshipNode) {
            /** @var DOMElement $relationshipNode */
            $id = $relationshipNode->getAttribute('Id');
            $target = $relationshipNode->getAttribute('Target');
            if ($id !== '' && $target !== '') {
                $map[$id] = 'word/' . ltrim($target, '/');
            }
        }

        return $map;
    }
}

if (!function_exists('dlhsSaveDocxImageToPublicPath')) {
    function dlhsSaveDocxImageToPublicPath(ZipArchive $zip, $zipPath, $uploadDir, $publicPrefix)
    {
        $binary = $zip->getFromName($zipPath);
        if ($binary === false) {
            return '';
        }

        $extension = strtolower(pathinfo($zipPath, PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = 'png';
        }

        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $fileName = 'word_' . date('YmdHis') . '_' . mt_rand(1000, 999999) . '.' . $extension;
        $fullPath = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;
        if (@file_put_contents($fullPath, $binary) === false) {
            return '';
        }

        return rtrim($publicPrefix, '/\\') . '/' . $fileName;
    }
}

if (!function_exists('dlhsDocxNodeChildrenToHtml')) {
    function dlhsDocxNodeChildrenToHtml(DOMNode $node, DOMXPath $xpath, array $relationshipMap, $zip, $uploadDir, $publicPrefix)
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $html .= htmlspecialchars(dlhsNormalizeImportedText($child->nodeValue), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
                continue;
            }

            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            switch ($child->localName) {
                case 't':
                    $html .= htmlspecialchars(dlhsNormalizeImportedText($child->textContent), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
                    break;

                case 'tab':
                    $html .= '&nbsp;&nbsp;&nbsp;&nbsp;';
                    break;

                case 'br':
                case 'cr':
                    $html .= '<br>';
                    break;

                case 'drawing':
                    $blip = $xpath->query('.//a:blip', $child)->item(0);
                    if ($blip instanceof DOMElement) {
                        $embedId = $blip->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'embed');
                        if ($embedId !== '' && isset($relationshipMap[$embedId])) {
                            $imageUrl = dlhsSaveDocxImageToPublicPath($zip, $relationshipMap[$embedId], $uploadDir, $publicPrefix);
                            if ($imageUrl !== '') {
                                $html .= '<img src="' . htmlspecialchars($imageUrl, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8') . '" alt="Imported image">';
                            }
                        }
                    }
                    break;

                case 'oMath':
                case 'oMathPara':
                    $equationText = trim(dlhsNormalizeImportedText($child->textContent));
                    if ($equationText !== '') {
                        $html .= '<span class="math-equation">' . htmlspecialchars($equationText, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8') . '</span>';
                    }
                    break;

                default:
                    $html .= dlhsDocxNodeChildrenToHtml($child, $xpath, $relationshipMap, $zip, $uploadDir, $publicPrefix);
                    break;
            }
        }

        return $html;
    }
}

if (!function_exists('dlhsImportDocxHtml')) {
    function dlhsImportDocxHtml($filePath, $uploadDir, $publicPrefix)
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return array(
                'success' => false,
                'message' => 'The Word document could not be opened. Please upload a .docx file.'
            );
        }

        $documentXml = $zip->getFromName('word/document.xml');
        if ($documentXml === false) {
            $zip->close();
            return array(
                'success' => false,
                'message' => 'The Word document contents could not be read.'
            );
        }

        $document = new DOMDocument();
        if (!@$document->loadXML($documentXml)) {
            $zip->close();
            return array(
                'success' => false,
                'message' => 'The Word document contents are not valid XML.'
            );
        }

        $relationshipMap = dlhsDocxRelationshipMap($zip);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
        $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $xpath->registerNamespace('m', 'http://schemas.openxmlformats.org/officeDocument/2006/math');

        $body = $xpath->query('/w:document/w:body')->item(0);
        if (!$body) {
            $zip->close();
            return array(
                'success' => false,
                'message' => 'The Word document body could not be found.'
            );
        }

        $htmlParts = array();
        foreach ($body->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            if ($child->localName === 'p') {
                $paragraphHtml = dlhsDocxNodeChildrenToHtml($child, $xpath, $relationshipMap, $zip, $uploadDir, $publicPrefix);
                if (trim(strip_tags(html_entity_decode($paragraphHtml, ENT_QUOTES | ENT_HTML5, 'UTF-8'))) !== '' || strpos($paragraphHtml, '<img') !== false) {
                    $htmlParts[] = '<p>' . $paragraphHtml . '</p>';
                }
            } elseif ($child->localName === 'tbl') {
                $tableHtml = '<table>';
                foreach ($xpath->query('./w:tr', $child) as $rowNode) {
                    $tableHtml .= '<tr>';
                    foreach ($xpath->query('./w:tc', $rowNode) as $cellNode) {
                        $cellHtml = dlhsDocxNodeChildrenToHtml($cellNode, $xpath, $relationshipMap, $zip, $uploadDir, $publicPrefix);
                        $tableHtml .= '<td>' . $cellHtml . '</td>';
                    }
                    $tableHtml .= '</tr>';
                }
                $tableHtml .= '</table>';
                $htmlParts[] = $tableHtml;
            }
        }

        $zip->close();

        return array(
            'success' => true,
            'html' => implode('', $htmlParts)
        );
    }
}

