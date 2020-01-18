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
    public $name = 'Parse text with JSON';

    /** @var string description of this source type */
    public $description = 'Parse a JSON file';

    /**
     * config params
     * array of arrays with name, type, default value, required, validation type
     *
     * @var bool|mixed
     */
    public $params = [
        'jsonselector' => [
            'title' => 'Root array regex (optional)',
            'type' => 'text',
            'default' => '/var array = ([^;]+);/Um',
            'required' => false,
        ],
        'arrayselector' => [
            'title' => 'Array with posts Path',
            'type' => 'text',
            'default' => 'blogs.0.posts',
            'required' => false,
        ],
        'titleselector' => [
            'title' => 'Title Path',
            'type' => 'text',
            'default' => 'meta.title',
            'required' => true,
            'validation' => ['notempty']
        ],
        'linkselector' => [
            'title' => 'Link Path',
            'type' => 'text',
            'default' => 'meta.permalink',
            'required' => false
        ],
        'contentselector' => [
            'title' => 'Content selector',
            'type' => 'text',
            'default' => 'content.text',
            'required' => false
        ],
        'timestampselector' => [
            'title' => 'Timestamp selector',
            'type' => 'text',
            'default' => 'meta.timestamp',
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

        if (empty($params['jsonselector']))
            $json = json_decode($content, true);
        else {
            preg_match($params['jsonselector'], $content, $match);
            $json = json_decode($match[1], true);
        }

        // \F3::get('logger')->debug(print_r($json, true));

        if (empty($params['arrayselector'])) {
            $dataArray = $json;
        } else {
            $dataArray = $this->accessPath($params['arrayselector'], $json);
        }

        // get titles
        $titleNodes = [];
        $linkNodes = [];
        $contentNodes = [];
        $timestampNodes = [];
        foreach ($dataArray as $key => $value) {
            array_push($titleNodes, $this->accessPath($params['titleselector'], $dataArray[$key]));
            if (!empty($params['linkselector']))
                array_push($linkNodes, $this->accessPath($params['linkselector'], $dataArray[$key]));
            if (!empty($params['contentselector']))
                array_push($contentNodes, $this->accessPath($params['contentselector'], $dataArray[$key]));
            if (!empty($params['timestampselector']))
                array_push($timestampNodes, $this->accessPath($params['timestampselector'], $dataArray[$key]));
        }

        // $this->log($params, $content);
        // \F3::get('logger')->debug(print_r($titleNodes, true));

        // validation
        if (empty($titleNodes))
            throw new \Exception("Cannot find any posts with current title selector");

        if (!empty($params['linkselector']) && count($titleNodes) != count($linkNodes))
            throw new \Exception("Selectors don't return an equal amount of items! (titles != links)");

        if (!empty($params['contentselector']) && count($titleNodes) != count($contentNodes))
            throw new \Exception("Selectors don't return an equal amount of items! (titles != content)");

        if (!empty($params['timestampselector']) && count($titleNodes) != count($timestampNodes))
            throw new \Exception("Selectors don't return an equal amount of items! (titles != timestamp)");

        // parse and add items
        $array = array();
        for ($i=0; $i < count($titleNodes); $i++) {

            // prepend link
            $link = isset($linkNodes) ? $linkNodes[$i] : '';
            if (!empty($params['baseurl']))
                $link = $params['baseurl'] . $link;

            $array[$i] = [
                'title' => trim($titleNodes[$i]),
                'link' => $link,
                'content' => isset($contentNodes) ? trim($contentNodes[$i]) : '',
                'timestamp' => isset($timestampNodes) ? trim($timestampNodes[$i]) : ''
            ];
        }

        $this->items = $array;
    }

    private function accessPath($pathString, $object) {

        $path = explode(".", $pathString);
        $lastChild = $object;

        foreach ($path as $index => $key) {
            if (!array_key_exists($key ,$lastChild)) {
                throw new \Exception("No value found for key ".$key." in object ".print_r($lastChild, true));
            }
            $lastChild = $lastChild[$key];
        }

        if ($lastChild == $object)
            throw new \Exception("No value found for path ".$pathString);

        return $lastChild;
    }

}
