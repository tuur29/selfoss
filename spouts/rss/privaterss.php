<?php

namespace spouts\rss;

/**
 * Spout for fetching a private rss feed
 *
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 * @author     Tuur Lievens
 */
class privaterss extends feed {
    /** @var string name of source */
    public $name = 'RSS Feed (with cookie)';

    /** @var string description of this source type */
    public $description = 'Fetch a private RSS feed by also sending a cookie';

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
        'cookie' => [
            'title'      => 'Cookie',
            'type'       => 'text',
            'default'    => '',
            'required'   => true,
            'validation' => ['notempty']
        ]
    ];

    /**
     * loads content for given source with cookie
     * I supress all Warnings of SimplePie for ensuring
     * working plugin in PHP Strict mode
     *
     * @param mixed $params the params of this source
     *
     * @return void
     */
    public function load($params) {
        // initialize simplepie feed loader
        $this->feed = @new \SimplePie();
        @$this->feed->set_cache_location(\F3::get('cache'));
        @$this->feed->set_cache_duration(1800);
        @$this->feed->set_feed_url(htmlspecialchars_decode($params['url']));
        @$this->feed->set_autodiscovery_level(SIMPLEPIE_LOCATOR_AUTODISCOVERY | SIMPLEPIE_LOCATOR_LOCAL_EXTENSION | SIMPLEPIE_LOCATOR_LOCAL_BODY);
        $this->feed->set_useragent(\helpers\WebClient::getUserAgent(['SimplePie/' . SIMPLEPIE_VERSION])."\r\nCookie: ".$params['cookie']);

        // fetch items
        @$this->feed->init();

        // on error retry with force_feed
        if (@$this->feed->error()) {
            @$this->feed->set_autodiscovery_level(SIMPLEPIE_LOCATOR_NONE);
            @$this->feed->force_feed(true);
            @$this->feed->init();
        }

        // check for error
        if (@$this->feed->error()) {
            throw new \Exception($this->feed->error());
        } else {
            // save fetched items
            $this->items = @$this->feed->get_items();
        }

        // set html url
        $this->htmlUrl = @$this->feed->get_link();

        $this->spoutTitle = $this->feed->get_title();
    }

}
