<?php

/**
 * Methods to enhance TEI with textrazor api.
 */

namespace App\Utils;

class TeiEnhancer
{
    protected $errors = [];
    protected $options = [];

    public function __construct($options = [])
    {
        $this->options = $options;

        if (array_key_exists('textRazorApiKey', $this->options)) {
            // TODO: inject a proper service instead
            \TextRazorSettings::setApiKey($this->options['textRazorApiKey']);
        }
    }

    public function addEntities($tei)
    {
        // we don't want \n within <p>
        $caller = &$this;

        $tei = preg_replace_callback('|(<p\b[^>]*>)([\s\S]*?)(</p>)|m', function ($matches) use ($caller) {
            return $matches[1] . $caller->stripWhitespace($matches[2]) . $matches[3];
        }, $tei);

        // mark named entities
        $fluidXml = (new \FluidXml\FluidXml(null))->addChild($tei);
        $fluidXml->namespace('tei', 'http://www.tei-c.org/ns/1.0', \FluidXml\FluidNamespace::MODE_IMPLICIT);

        foreach ([
            '/tei:TEI/tei:teiHeader/tei:fileDesc/tei:notesStmt/tei:note[@type="remarkDocument"]//text()',
            '/tei:TEI/tei:text/tei:body//text()',
        ] as $query) {
            $plainText = $this->extractText($fluidXml, $query);

            if (empty($plainText)) {
                continue;
            }

            $entities = $this->getEntitiesFromText($plainText);

            $matchPosition = 0;
            $caller = & $this;
            $fluidXml->query($query)
                ->each(function ($i, $domnode) use ($entities, &$matchPosition, $caller) {
                    $maxPosition = count($entities);

                    $rest = $caller->stripWhitespace($domnode->nodeValue);

                    $fragment = null;
                    while ($matchPosition < $maxPosition
                           && preg_match(
                               '/(' . preg_quote($search = $entities[$matchPosition]['matchedText'], '/') . ')/',
                               $rest,
                               $matches,
                               PREG_OFFSET_CAPTURE
                           )) {
                        // echo htmlspecialchars('Found ' . $search . ' in ' . $rest) . '<br />';
                        // var_dump($matches[1]);

                        if (is_null($fragment)) {
                            $doc = $domnode->ownerDocument;
                            $fragment = $doc->createDocumentFragment();
                        }

                        if (($start = $matches[1][1]) > 0) {
                            // there is some text before the match
                            $before = $doc->createTextNode(substr($rest, 0, $start));
                            $fragment->appendChild($before);
                        }

                        $element = $doc->createElement($entities[$matchPosition]['tagName'], $matches[1][0]);
                        $element->setAttribute('ref', $entities[$matchPosition]['ref']);
                        $fragment->appendChild($element);
                        $end = $start + strlen($matches[1][0]);
                        $rest = substr($rest, $end);
                        ++$matchPosition;
                    }

                    if (!is_null($fragment)) {
                        // we had at least one match, so replace $domnode with fragment

                        if (strlen($rest) > 0) {
                            // some text is left after the last match; add as textnode
                            $fragment->appendChild($doc->createTextNode($rest));
                        }

                        $domnode->parentNode->replaceChild($fragment, $domnode);
                    }
                });
        }

        return $fluidXml->xml();
    }

    protected function stripWhitespace($txt)
    {
        // remove new lines or multiple whitespaces in order to simplify matching
        $txt = preg_replace('/[\n\r]/', ' ', $txt);

        while (false !== strpos($txt, '  ')) {
            $txt = str_replace('  ', ' ', $txt);
        }

        return $txt;
    }

    protected function extractText($tei, $query)
    {
        $result = '';

        $caller = & $this;
        $tei->query($query)
            ->each(function ($i, $domnode) use (&$result, $caller) {
                $previousSibling = $domnode->previousSibling;
                if (!is_null($previousSibling) && $previousSibling instanceof \DOMElement) {
                    if ('lb' == $previousSibling->tagName) {
                        $result .= "\n";
                    }
                }

                $result .= $caller->stripWhitespace($domnode->nodeValue);

                if (in_array($domnode->parentNode->tagName, ['div', 'p'])) {
                    $result .= "\n";
                }
            });

        return $result;
    }

    protected function getEntitiesFromText($text)
    {
        $textrazor = new \TextRazor();
        $textrazor->addExtractor('entities');

        $response = $textrazor->analyze($text);

        $matches = [];

        $type2tag = [
            'Person' => 'persName',
            'Organisation' => 'orgName',
            'PopulatedPlace' => 'placeName',
        ];

        if (isset($response['response']['entities'])) {
            // need to sort be startingPos
            $entities = $response['response']['entities'];
            uasort($entities, function ($a, $b) {
                return $a['startingPos'] - $b['startingPos'];
            });

            $endingPos = 0;

            foreach ($entities as $entity) {
                if (!array_key_exists('type', $entity)) {
                    continue;
                }

                if ($entity['startingPos'] < $endingPos) {
                    // we drop overlapping entities
                    continue;
                }

                $tagName = null;
                foreach ($type2tag as $type => $tag) {
                    if (in_array($type, $entity['type'])) {
                        $tagName = $tag;
                        break;
                    }
                }

                if (is_null($tagName)) {
                    continue;
                }

                $matchedText = $entity['matchedText']; // $match = mb_substr($plainText, $entity['startingPos'], $entity['endingPos'] - $entity['startingPos'], 'UTF-8');

                if (array_key_exists('wikidataId', $entity) && !empty($entity['wikidataId'])) {
                    // if we don't want to link e.g. every instance of 'deutsch' to Q183 for Germany
                    $ignore = false;

                    if (array_key_exists('ignore', $this->options)
                        && array_key_exists($entity['wikidataId'], $this->options['ignore'])) {
                        foreach ($this->options['ignore'][$entity['wikidataId']] as $regexp) {
                            if (preg_match($regexp, $matchedText)) {
                                $ignore = true;
                                break;
                            }
                        }
                    }

                    if ($ignore) {
                        continue;
                    }

                    $uri = 'http://www.wikidata.org/entity/' . $entity['wikidataId'];

                    $matches[] = [
                        'matchedText' => $matchedText,
                        'tagName' => $tagName,
                        'ref' => $uri,
                    ];

                    $endingPos = $entity['endingPos'];
                }
            }
        }

        return $matches;
    }

    public function normalizeEntities($tei, $callback)
    {
        // mark named entities
        $fluidXml = (new \FluidXml\FluidXml(null))->addChild($tei);
        $fluidXml->namespace('tei', 'http://www.tei-c.org/ns/1.0', \FluidXml\FluidNamespace::MODE_IMPLICIT);

        foreach ([
            '/tei:TEI/tei:teiHeader/tei:fileDesc/tei:notesStmt/tei:note[@type="remarkDocument"]',
            '/tei:TEI/tei:text/tei:body',
        ] as $parentElementQuery) {
            $query = $parentElementQuery . '//*[self::tei:persName or self::tei:orgName or self::tei:placeName]';

            $fluidXml->query($query)
                ->each(function ($i, $domnode) use (&$result, $callback) {
                    switch ($domnode->tagName) {
                        case 'persName':
                            $type = 'person';
                            $attr = 'ref';
                            break;

                        case 'orgName':
                            $type = 'organization';
                            $attr = 'ref';
                            break;

                        case 'placeName':
                            $type = 'place';
                            $attr = 'ref';
                            break;

                        default:
                            // we should never get here
                            return;
                    }

                    if (!$domnode->hasAttribute($attr)) {
                        return;
                    }

                    $ref = trim($domnode->getAttribute($attr));
                    if ('' === $ref) {
                        return;
                    }

                    $uris = preg_split('/\s+/', $ref);
                    for ($i = 0; $i < count($uris); ++$i) {
                        $uris[$i] = $callback($uris[$i], $type);
                    }

                    $uris = array_unique($uris);
                    $refNew = join(' ', $uris);
                    if ($ref !== $refNew) {
                        $domnode->setAttribute($attr, $refNew);
                    }
                });
        }

        return $fluidXml->xml();
    }
}
