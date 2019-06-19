<?php

namespace spouts\parse;

/**
 * Spout for parsing a html page with XPath
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Daniel Seither <post@tiwoc.de>
 * @author     Tuur Lievens
 */
class xpath extends \spouts\parse\feed {
    /** @var string name of spout */
    public $name = 'Parse text with XPath';

    /** @var string description of this source type */
    public $description = 'Parse an URL with XPath';

    /**
     * config params
     * array of arrays with name, type, default value, required, validation type
     *
     * @var bool|mixed
     */
    public $params = [
        'url' => [
            'title' => 'URL',
            'type' => 'url',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ],
        'titleselector' => [
            'title' => 'Title selector',
            'type' => 'text',
            'default' => '//article//h1[@class=\'title\']',
            'required' => true,
            'validation' => ['notempty']
        ],
        'linkselector' => [
            'title' => 'Link selector',
            'type' => 'text',
            'default' => '//article//a',
            'required' => false
        ],
        'contentselector' => [
            'title' => 'Content selector',
            'type' => 'text',
            'default' => '//article',
            'required' => false
        ],
        'timestampselector' => [
            'title' => 'Timestamp selector',
            'type' => 'text',
            'default' => '//article//p[@class=\'timestamp\']',
            'required' => false
        ],
        'cookies' => [
            'title' => 'Cookies (optional)',
            'type' => 'text',
            'default' => '',
            'required' => false
        ],
        'proxy' => [
            'title' => 'SOCKS5 Proxy (optional, user:pass ; ip:port)',
            'type' => 'text',
            'default' => '',
            'required' => false
        ],
        'baseurl' => [
            'title' => 'Base url (optional, linkselector match gets appended)',
            'type' => 'text',
            'default' => '',
            'required' => false
        ],
        'iconurl' => [
            'title' => 'Manually set icon url',
            'type' => 'text',
            'default' => '',
            'required' => false
        ]
    ];

    /**
     * loads content for given source
     *
     * @param string $url
     *
     * @return void
     */
    public function load(array $params) {

        $content = parent::load($params);

        $dom = new \DOMDocument();
        @$dom->loadHTML($content);
        if (!$dom) {
            return false;
        }
        $xpath = new \DOMXPath($dom);

        // get titles
        $titleNodes = $xpath->query($params['titleselector']);
        if (!empty($params['linkselector']))
            $linkNodes = $xpath->query($params['linkselector']);
        if (!empty($params['contentselector']))
            $contentNodes = $xpath->query($params['contentselector']);
        if (!empty($params['timestampselector']))
            $timestampNodes = $xpath->query($params['timestampselector']);

        // $this->log($params, $content);
        // \F3::get('logger')->debug(print_r($titleNodes, true));

        // validate
        if ($titleNodes->length < 1)
            throw new \Exception("Cannot find any posts with current title selector");

        if (isset($linkNodes) && $titleNodes->length != $linkNodes->length )
            throw new \Exception("Selectors don't return an equal amount of items! (titles != links)");

        if (isset($contentNodes) && $titleNodes->length != $contentNodes->length )
            throw new \Exception("Selectors don't return an equal amount of items! (titles != content)");

        if (isset($timestampNodes) && $titleNodes->length != $timestampNodes->length )
            throw new \Exception("Selectors don't return an equal amount of items! (titles != timestamp)");

        // parse and add items
        $array = array();
        for ($i=0; $i < $titleNodes->length; $i++) {

            // prepend link
            $link = isset($linkNodes) ? $linkNodes[$i]->getAttribute('href') : "";
            if (!empty($params['baseurl']))
                $link = $params['baseurl'] . $link;

            $array[$i] = [
                'title' => mb_strimwidth($titleNodes[$i]->textContent, 0, 90),
                'link' => $link,
                'content' => isset($contentNodes) ? $contentNodes[$i]->C14N() : "",
                'timestamp' => isset($timestampNodes) ? $timestampNodes[$i]->textContent : ""
            ];
        }

        $this->items = $array;
    }

}
