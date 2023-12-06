<?php
/**
 * BicBucStriim routes
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

require_once __DIR__ . '/Actions/main.php';
require_once __DIR__ . '/Actions/admin.php';
//require_once __DIR__ . '/Actions/authors.php';
require_once __DIR__ . '/Actions/metadata.php';
require_once __DIR__ . '/Actions/opds.php';
//require_once __DIR__ . '/Actions/series.php';
//require_once __DIR__ . '/Actions/tags.php';
//require_once __DIR__ . '/Actions/titles.php';

use BicBucStriim\Actions\AdminActions;
use BicBucStriim\Actions\MainActions;
use BicBucStriim\Actions\MetadataActions;
use BicBucStriim\Actions\OpdsActions;

###### Init routes for production
return function ($app) {
    MainActions::addRoutes($app);
    //$app->notFound('myNotFound');
    //$app->get('/', 'main');
    AdminActions::addRoutes($app, '/admin');
    /**
    $app->group('/admin', 'check_admin', function () use ($app) {
        $app->get('/', 'admin');
        $app->get('/configuration/', 'admin_configuration');
        $app->post('/configuration/', 'admin_change_json');
        $app->get('/idtemplates/', 'admin_get_idtemplates');
        $app->put('/idtemplates/:id/', 'admin_modify_idtemplate');
        $app->delete('/idtemplates/:id/', 'admin_clear_idtemplate');
        $app->get('/mail/', 'admin_get_smtp_config');
        $app->put('/mail/', 'admin_change_smtp_config');
        $app->get('/users/', 'admin_get_users');
        $app->post('/users/', 'admin_add_user');
        $app->get('/users/:id/', 'admin_get_user');
        $app->put('/users/:id/', 'admin_modify_user');
        $app->delete('/users/:id/', 'admin_delete_user');
        $app->get('/version/', 'admin_check_version');
    });
     */
    /**
    $app->get('/authors/:id/notes/', 'check_admin', 'authorNotes');
    #$app->post('/authors/:id/notes/', 'check_admin', 'authorNotesEdit');
    $app->get('/authors/:id/:page/', 'authorDetailsSlice');
    $app->get('/authorslist/:id/', 'authorsSlice');
    $app->get('/login/', 'show_login');
    $app->post('/login/', 'perform_login');
    $app->get('/logout/', 'logout');
     */
    MetadataActions::addRoutes($app, '/metadata');
    /**
    $app->group('/metadata', 'check_admin', function () use ($app) {
        $app->post('/authors/:id/thumbnail/', 'edit_author_thm');
        $app->delete('/authors/:id/thumbnail/', 'del_author_thm');
        $app->post('/authors/:id/notes/', 'edit_author_notes');
        $app->delete('/authors/:id/notes/', 'del_author_notes');
        $app->post('/authors/:id/links/', 'new_author_link');
        $app->delete('/authors/:id/links/:link_id/', 'del_author_link');
    });
     */
    /**
    $app->get('/search/', 'globalSearch');
    $app->get('/series/:id/:page/', 'seriesDetailsSlice');
    $app->get('/serieslist/:id/', 'seriesSlice');
    $app->get('/tags/:id/:page/', 'tagDetailsSlice');
    $app->get('/tagslist/:id/', 'tagsSlice');
    $app->get('/titles/:id/', 'title');
    $app->get('/titles/:id/cover/', 'cover');
    $app->get('/titles/:id/file/:file', 'book');
    $app->post('/titles/:id/kindle/:file', 'kindle');
    $app->get('/titles/:id/thumbnail/', 'thumbnail');
    $app->get('/titleslist/:id/', 'titlesSlice');
     */
    OpdsActions::addRoutes($app, '/opds');
    /**
    $app->group('/opds', function () use ($app) {
        $app->get('/', 'opdsRoot');
        $app->get('/newest/', 'opdsNewest');
        $app->get('/titleslist/:id/', 'opdsByTitle');
        $app->get('/authorslist/', 'opdsByAuthorInitial');
        $app->get('/authorslist/:initial/', 'opdsByAuthorNamesForInitial');
        $app->get('/authorslist/:initial/:id/:page/', 'opdsByAuthor');
        $app->get('/tagslist/', 'opdsByTagInitial');
        $app->get('/tagslist/:initial/', 'opdsByTagNamesForInitial');
        $app->get('/tagslist/:initial/:id/:page/', 'opdsByTag');
        $app->get('/serieslist/', 'opdsBySeriesInitial');
        $app->get('/serieslist/:initial/', 'opdsBySeriesNamesForInitial');
        $app->get('/serieslist/:initial/:id/:page/', 'opdsBySeries');
        $app->get('/opensearch.xml', 'opdsSearchDescriptor');
        $app->get('/searchlist/:id/', 'opdsBySearch');
        //$app->get('/titles/:id/', 'title');
        //$app->get('/titles/:id/cover/', 'cover');
        //$app->get('/titles/:id/file/:file', 'book');
        //$app->get('/titles/:id/thumbnail/', 'thumbnail');
        $app->get('/logout/', 'opdsLogout');
    });
     */
};
