<?php

namespace spouts\html;

/**
 * Spout for parsing a html page with XPath
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (https://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Daniel Seither <post@tiwoc.de>
 * @author     Tuur Lievens
 */
class parseregex extends \spouts\spout {
    /** @var string name of spout */
    public $name = 'Parse HTML with Regex';

    /** @var string description of this source type */
    public $description = 'Parse a html page with Regex';

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
        ]
    ];

    /** @var array|bool current fetched items */
    protected $items = false;

    /** @var string URL of the source */
    protected $htmlUrl = '';

    /** @var string URL of the favicon */
    protected $faviconUrl = null;

    /**
     * loads content for given source
     *
     * @param string $url
     *
     * @return void
     */
    public function load($params) {

        $this->htmlUrl = $params['url'];

        if (function_exists('curl_init') && !ini_get('open_basedir')) {
            $content = $this->file_get_contents_curl($this->htmlUrl, $params);
        } else {
            $content = @file_get_contents($this->htmlUrl);
        }

        if (empty($content))
            throw new \Exception("Empty or non-existant page! (Might also be a proxy error)");

        // get titles
        preg_match_all($params['titleselector'], $content, $titleNodes);
        if (!empty($params['linkselector']))
            preg_match_all($params['linkselector'], $content, $linkNodes);
        if (!empty($params['contentselector']))
            preg_match_all($params['contentselector'], $content, $contentNodes);
        if (!empty($params['timestampselector']))
            preg_match_all($params['timestampselector'], $content, $timestampNodes);

        // \F3::get('logger')->debug('regex '.$params['titleselector']);
        // \F3::get('logger')->debug('regex '.$params['linkselector']);
        // \F3::get('logger')->debug('regex '.$params['timestampselector']);
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

            \F3::get('logger')->debug('regex '. print_r($array[$i], true));
        }

        $this->items = $array;
    }

    //
    // Iterator Interface
    //

    /**
     * reset iterator
     *
     * @return void
     */
    public function rewind() {
        if ($this->items !== null) {
            reset($this->items);
        }
    }

    /**
     * receive current item
     *
     * @return \SimplePie_Item current item
     */
    public function current() {
        if ($this->items !== null) {
            return $this;
        }

        return false;
    }

    /**
     * receive key of current item
     *
     * @return mixed key of current item
     */
    public function key() {
        if ($this->items !== null) {
            return key($this->items);
        }

        return null;
    }

    /**
     * select next item
     *
     * @return \SimplePie_Item next item
     */
    public function next() {
        if ($this->items !== null) {
            next($this->items);
        }

        return $this;
    }

    /**
     * end reached
     *
     * @return bool false if end reached
     */
    public function valid() {
        if ($this->items !== null) {
            return current($this->items) !== false;
        }

        return false;
    }

    /**
     * returns an unique id for this item
     *
     * @return string id as hash
     */
    public function getId() {
        if ($this->items !== null && $this->valid()) {
            $id = md5(@current($this->items)['title']);
            return $id;
        }

        return false;
    }

    /**
     * returns the current title as string
     *
     * @return string title
     */
    public function getTitle() {
        if ($this->items !== null && $this->valid()) {
            return @current($this->items)['title'];
        }

        return false;
    }

    /**
     * returns the url
     *
     * @throws \GuzzleHttp\Exception\RequestException When an error is encountered
     *
     * @return string title
     */
    public function getHtmlUrl() {
        if (isset($this->htmlUrl)) {
            return $this->htmlUrl;
        }

        return false;
    }

    /**
     * returns the content of this item
     *
     * @throws \GuzzleHttp\Exception\RequestException When an error is encountered
     *
     * @return string content
     */
    public function getContent() {
        if ($this->items !== null && $this->valid()) {
            return @current($this->items)['content'];
        }

        return false;
    }

    /**
     * returns the icon of this item
     *
     * @return string icon url
     */
    public function getIcon() {
        if ($this->faviconUrl !== null) {
            return $this->faviconUrl;
        }

        try {
            $this->faviconUrl = false;
            $imageHelper = $this->getImageHelper();
            $htmlUrl = $this->getHtmlUrl();
            if ($htmlUrl && $imageHelper->fetchFavicon($htmlUrl, true)) {
                $this->faviconUrl = $imageHelper->getFaviconUrl();
                \F3::get('logger')->debug('icon: using feed homepage favicon: ' . $this->faviconUrl);
            }
        } catch (\Exception $e) {
            \F3::get('logger')->debug('icon: error', ['exception' => $e]);
        }

        return $this->faviconUrl;
    }

    /**
     * returns the link of this item
     *
     * @return string link
     */
    public function getLink() {
        if ($this->items !== null && $this->valid()) {
            if (!empty( @current($this->items)['link'])) {
                $url = @current($this->items)['link'];
                if (preg_match("/^((https?|ftp)\:\/\/)/", $url)) {
                    return $url;
                } else {
                    if (substr($url,0,1) === "/")
                        return parse_url($this->getHtmlUrl(), PHP_URL_SCHEME) . "://" . parse_url($this->getHtmlUrl(), PHP_URL_HOST). $url;

                    return dirname($this->getHtmlUrl()). "/" . $url;
                }
             } else {
                return $this->getHtmlUrl();
            }
        }

        return false;
    }

    /**
     * returns the date of this item
     *
     * @return string date
     */
    public function getDate() {
        if ($this->items !== null && $this->valid()) {
            if (!empty( @current($this->items)['timestamp'])) {
                $unix = strtotime(@current($this->items)['timestamp']);
                $timestamp = $this->convertTimestamp( !$unix ? time() : $unix );
            }
        }

        return isset($timestamp) ? $timestamp : $this->convertTimestamp(time());
    }

    private function convertTimestamp($input) {
        return date('Y-m-d H:i:s', $input);
    }

    /**
     * destroy the plugin (prevent memory issues)
     */
    public function destroy() {
        unset($this->items);
        $this->items = null;
    }

    /**
     * returns the xml feed url for the source
     *
     * @param mixed $params params for the source
     *
     * @return string url as xml
     */
    public function getXmlUrl($params) {
        return $this->getHtmlUrl();
    }

    // HELPERS

    private function file_get_contents_curl($url, $params) {
        $ch = curl_init();

        // some sites need a user-agent
        $agent= 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36';
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);

        if (!empty($params['cookies']))
        curl_setopt($ch, CURLOPT_COOKIE, $params['cookies']);
        
        if (!empty($params['proxy'])) {
            $temp = explode(" ; ", $params['proxy']); // 0: user:pass, 1: url
            curl_setopt($ch, CURLOPT_PROXY, $temp[1]);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $temp[0]);
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
        }

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        $data = @curl_exec($ch);
        curl_close($ch);

        return $data;
    }
}
