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
class json extends \spouts\parse\feed {
    /** @var string name of spout */
    public $name = 'Parse JSON';

    /** @var string description of this source type */
    public $description = 'Parse a JSON file';

    /**
     * config params
     * array of arrays with name, type, default value, required, validation type
     *
     * @var bool|mixed
     */
    public $params = [
        'arraypath' => [
            'title' => 'Array path',
            'type' => 'text',
            'default' => '',
            'required' => false,
        ],
        'url' => [
            'title' => 'URL',
            'type' => 'url',
            'default' => '',
            'required' => true,
            'validation' => ['notempty']
        ],
        'titleselector' => [
            'title' => 'Title Path',
            'type' => 'text',
            'default' => '.title',
            'required' => true,
            'validation' => ['notempty']
        ],
        'linkselector' => [
            'title' => 'Link Path',
            'type' => 'text',
            'default' => '.link',
            'required' => false
        ],
        'contentselector' => [
            'title' => 'Content selector',
            'type' => 'text',
            'default' => '.description',
            'required' => false
        ],
        'timestampselector' => [
            'title' => 'Timestamp selector',
            'type' => 'text',
            'default' => '.date',
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

        $this->htmlUrl = $params['url'];
        if (!empty($params['iconurl'])) {
            $this->faviconUrl = $params['iconurl'];
        }

        if (function_exists('curl_init') && !ini_get('open_basedir')) {
            $content = $this->file_get_contents_curl($this->htmlUrl, $params);
        } else {
            $content = @file_get_contents($this->htmlUrl);
        }

        if (empty($content))
            throw new \Exception("Empty or non-existant page! (Might also be a proxy error)");

        // TODO: change to JSON parsing
        // TODO: extract helpers to superclass
        // get titles
        preg_match_all($params['titleselector'], $content, $titleNodes);
        if (!empty($params['linkselector']))
            preg_match_all($params['linkselector'], $content, $linkNodes);
        if (!empty($params['contentselector']))
            preg_match_all($params['contentselector'], $content, $contentNodes);
        if (!empty($params['timestampselector']))
            preg_match_all($params['timestampselector'], $content, $timestampNodes);

        // \F3::get('logger')->debug('json '.$params['titleselector']);
        // \F3::get('logger')->debug('json '.$params['linkselector']);
        // \F3::get('logger')->debug('json '.$params['timestampselector']);
        // \F3::get('logger')->debug('content '.$content);

        // validation
        if (empty($titleNodes))
            throw new \Exception("Cannot find any posts with current title selector");

        if (isset($linkNodes) && $titleNodes->length != $linkNodes->length )
            throw new \Exception("Selectors don't return an equal amount of items! (titles != links)");

        if (isset($contentNodes) && $titleNodes->length != $contentNodes->length )
            throw new \Exception("Selectors don't return an equal amount of items! (titles != content)");

        if (isset($timestampNodes) && $titleNodes->length != $timestampNodes->length )
            throw new \Exception("Selectors don't return an equal amount of items! (titles != timestamp)");

        // parse and add items
        $array = array();
        for ($i=0; $i < sizeof($titleNodes[1]); $i++) {

            // prepend link
            $link = isset($linkNodes) ? $linkNodes[1][$i] : '';
            if (!empty($params['baseurl']))
                $link = $params['baseurl'] . $link;

            $array[$i] = [
                'title' => $titleNodes[1][$i],
                'link' => $link,
                'content' => isset($contentNodes) ? $contentNodes[1][$i] : '',
                'timestamp' => isset($timestampNodes) ? $timestampNodes[1][$i] : ''
            ];
        }

        $this->items = $array;
    }

}
