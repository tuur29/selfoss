<?php

require __DIR__ . '/common.php';

// Load custom language
$lang = $f3->get('language');
if ($lang != '0' && $lang != '') {
    $f3->set('LANGUAGE', $lang);
}

// init authentication
$f3->set('auth', new \helpers\Authentication());

/** @var stdClass JS client package manifest */
$clientPackage = json_decode(file_get_contents(__DIR__ . '/public/package.json'));

// define js files
$js = $clientPackage->extra->requiredFiles->js;
if (file_exists('user.js')) {
    $js[] = 'user.js';
}
$f3->set('js', $js);

// define css files
$css = $clientPackage->extra->requiredFiles->css;
if (file_exists('user.css')) {
    $css[] = 'user.css';
}
if (file_exists('public/css/theme.css')) {
    $css[] = 'public/css/theme.css';
}
$f3->set('css', $css);

// define routes

// all users
$f3->route('GET /', 'controllers\Index->home'); // html
$f3->route('GET /password', 'controllers\Index->password'); // html
$f3->route('POST /password', 'controllers\Index->password'); // html
$f3->route('GET /login', 'controllers\Index->login'); // json
$f3->route('POST /login', 'controllers\Index->login'); // json
$f3->route('GET /logout', 'controllers\Index->logout'); // json
$f3->route('GET /update', 'controllers\Index->update'); // text
$f3->route('GET /badge', 'controllers\Index->badge'); // xml
$f3->route('GET /win8notifs', 'controllers\Index->win8Notifications'); // xml

// only for loggedin users or on public mode
$f3->route('GET /rss', 'controllers\Rss->rss'); // rss
$f3->route('GET /feed', 'controllers\Rss->rss'); // rss
$f3->route('GET /items', 'controllers\Items->listItems'); // json
$f3->route('GET /tags', 'controllers\Tags->listTags'); // json
$f3->route('GET /tagslist', 'controllers\Tags->tagslist'); // html
$f3->route('GET /stats', 'controllers\Items->stats'); // json
$f3->route('GET /items/sync', 'controllers\Items->sync'); // json
$f3->route('GET /sources/stats', 'controllers\Sources->stats'); // json

// only loggedin users
$f3->route('POST /mark/@item', 'controllers\Items->mark'); // json
$f3->route('POST /mark', 'controllers\Items->mark'); // json
$f3->route('POST /unmark/@item', 'controllers\Items->unmark'); // json
$f3->route('POST /starr/@item', 'controllers\Items->starr'); // json
$f3->route('POST /unstarr/@item', 'controllers\Items->unstarr'); // json
$f3->route('POST /items/sync', 'controllers\Items->updateStatuses'); // json

$f3->route('GET /source/params', 'controllers\Sources->params'); // html
$f3->route('GET /sources', 'controllers\Sources->show'); // html
$f3->route('GET /source', 'controllers\Sources->add'); // html
$f3->route('GET /sources/list', 'controllers\Sources->listSources'); // json
$f3->route('GET /sources/sourcesStats', 'controllers\Sources->sourcesStats'); // json
$f3->route('POST /source/@id', 'controllers\Sources->write'); // json
$f3->route('POST /source', 'controllers\Sources->write'); // json
$f3->route('DELETE /source/@id', 'controllers\Sources->remove'); // json
$f3->route('POST /source/delete/@id', 'controllers\Sources->remove'); // json
$f3->route('POST /source/@id/update', 'controllers\Sources->update'); // json
$f3->route('GET /sources/spouts', 'controllers\Sources->spouts'); // json

$f3->route('POST /tags/color', 'controllers\Tags->color'); // json

$f3->route('GET /opml', 'controllers\Opml->show'); // html
$f3->route('POST /opml', 'controllers\Opml->add'); // html
$f3->route('GET /opmlexport', 'controllers\Opml->export'); // xml

// dispatch
$f3->run();
