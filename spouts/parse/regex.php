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
class regex extends \spouts\parse\feed {
    /** @var string name of spout */
    public $name = 'Parse text with Regex';

    /** @var string description of this source type */
    public $description = 'Parse an URL with Regex';

    /**
     * config params
     * array of arrays with name, type, default value, required, validation type
     *
     * @var bool|mixed
     */
    public $params = [
        'titleselector' => [
            'title' => 'Title selector',
            'type' => 'text',
            'default' => '/title/U',
            'required' => true,
            'validation' => ['notempty']
        ],
        'linkselector' => [
            'title' => 'Link selector',
            'type' => 'text',
            'default' => '/link/U',
            'required' => false
        ],
        'contentselector' => [
            'title' => 'Content selector',
            'type' => 'text',
            'default' => '/content/U',
            'required' => false
        ],
        'timestampselector' => [
            'title' => 'Timestamp selector',
            'type' => 'text',
            'default' => '/timestamp/U',
            'required' => false
        ]
    ];

    public function __construct() {
        // add default parsing settings
        $this->params = array_merge(parent::$firstParams, $this->params, parent::$lastParams);
    }

    /**
     * loads content for given source
     *
     * @param string $url
     *
     * @return void
     */
    public function load(array $params) {

        $content = parent::load($params);

        // get titles
        preg_match_all($params['titleselector'], $content, $titleNodes);
        $titleNodes = $titleNodes[1];

        if (!empty($params['linkselector'])) {
            preg_match_all($params['linkselector'], $content, $linkNodes);
            $linkNodes = $linkNodes[1];
        }
        if (!empty($params['contentselector'])) {
            preg_match_all($params['contentselector'], $content, $contentNodes);
            $contentNodes = $contentNodes[1];
        }
        if (!empty($params['timestampselector'])) {
            preg_match_all($params['timestampselector'], $content, $timestampNodes);
            $timestampNodes = $timestampNodes[1];
        }

        // $this->log($params, $content);
        // \F3::get('logger')->debug(print_r($titleNodes, true));

        // validate
        if (empty($titleNodes))
            throw new \Exception("Cannot find any posts with current title selector");

        if (isset($linkNodes) && count($titleNodes) != count($linkNodes) )
            throw new \Exception("Selectors don't return an equal amount of items! (titles != links)");

        if (isset($contentNodes) && count($titleNodes) != count($contentNodes) )
            throw new \Exception("Selectors don't return an equal amount of items! (titles != content)");

        if (isset($timestampNodes) && count($titleNodes) != count($timestampNodes) )
            throw new \Exception("Selectors don't return an equal amount of items! (titles != timestamp)");

        // parse and add items
        $array = array();
        for ($i=0; $i < count($titleNodes); $i++) {

            // prepend link
            $link = isset($linkNodes) ? $linkNodes[$i] : '';
            if (!empty($params['baseurl']))
                $link = $params['baseurl'] . $link;

            $array[$i] = [
                'title' => mb_strimwidth($titleNodes[$i], 0, 90),
                'link' => $link,
                'content' => isset($contentNodes) ? $contentNodes[$i] : '',
                'timestamp' => isset($timestampNodes) ? $timestampNodes[$i] : ''
            ];
        }

        $this->items = $array;
    }

}
