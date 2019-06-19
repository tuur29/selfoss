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
class feed extends \spouts\spout {
    /** @var string name of spout */
    public $name = '';

    /** @var string description of this source type */
    public $description = '';

    
    /**
     * config params
     * array of arrays with name, type, default value, required, validation type
     *
     * @var bool|mixed
     */
    public $params = [];

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

        return $content;
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
            } else if ($htmlUrl && $imageHelper->fetchFavicon($this->getRootUrl($htmlUrl), true)) {
                $this->faviconUrl = $imageHelper->getFaviconUrl();
                \F3::get('logger')->debug('icon: using root domain icon: ' . $this->faviconUrl);
            } else if ($htmlUrl && $imageHelper->fetchFavicon($params['baseurl'], true)) {
                $this->faviconUrl = $imageHelper->getFaviconUrl();
                \F3::get('logger')->debug('icon: using baseurl icon: ' . $this->faviconUrl);
            }
        } catch (\Exception $e) {
            \F3::get('logger')->debug('icon: error', ['exception' => $e]);
        }

        return $this->faviconUrl;
    }

    protected function getRootUrl($url) {
        return parse_url($url, PHP_URL_SCHEME) . "://" . parse_url($url, PHP_URL_HOST) . (!empty(parse_url($url, PHP_URL_PORT)) ? ":" . parse_url($url, PHP_URL_PORT) : "" ) . "/";
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

    protected function convertTimestamp($input) {
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
    public function getXmlUrl(array $params) {
        return $this->getHtmlUrl();
    }

    // HELPERS

    protected function file_get_contents_curl($url, $params) {
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

        if (!empty($params['cookies']))
            sleep(0.5);


        // \F3::get('logger')->debug('received data: ' . $data);
        return $data;
    }

    protected function log($params, $content) {
        \F3::get('logger')->debug($this->title." data: \n"
            .$params['titleselector']."\n"
            .$params['linkselector']."\n"
            .$params['timestampselector']."\n"
            .$params['contentselector']."\n"
            .$content
        );
    }

}