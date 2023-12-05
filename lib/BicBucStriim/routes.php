<?php
/**
 * BicBucStriim routes
 *
 * Copyright 2012-2016 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

###### Init routes for production
return function ($app) {
    $app->notFound('myNotFound');
    $app->get('/', 'main');
    $app->get('/admin/', 'check_admin', 'admin');
    $app->get('/admin/configuration/', 'check_admin', 'admin_configuration');
    $app->post('/admin/configuration/', 'check_admin', 'admin_change_json');
    $app->get('/admin/idtemplates/', 'check_admin', 'admin_get_idtemplates');
    $app->put('/admin/idtemplates/:id/', 'check_admin', 'admin_modify_idtemplate');
    $app->delete('/admin/idtemplates/:id/', 'check_admin', 'admin_clear_idtemplate');
    $app->get('/admin/mail/', 'check_admin', 'admin_get_smtp_config');
    $app->put('/admin/mail/', 'check_admin', 'admin_change_smtp_config');
    $app->get('/admin/users/', 'check_admin', 'admin_get_users');
    $app->post('/admin/users/', 'check_admin', 'admin_add_user');
    $app->get('/admin/users/:id/', 'check_admin', 'admin_get_user');
    $app->put('/admin/users/:id/', 'check_admin', 'admin_modify_user');
    $app->delete('/admin/users/:id/', 'check_admin', 'admin_delete_user');
    $app->get('/admin/version/', 'check_admin', 'admin_check_version');
    $app->get('/authors/:id/notes/', 'check_admin', 'authorNotes');
    #$app->post('/authors/:id/notes/', 'check_admin', 'authorNotesEdit');
    $app->get('/authors/:id/:page/', 'authorDetailsSlice');
    $app->get('/authorslist/:id/', 'authorsSlice');
    $app->get('/login/', 'show_login');
    $app->post('/login/', 'perform_login');
    $app->get('/logout/', 'logout');
    $app->post('/metadata/authors/:id/thumbnail/', 'check_admin', 'edit_author_thm');
    $app->delete('/metadata/authors/:id/thumbnail/', 'check_admin', 'del_author_thm');
    $app->post('/metadata/authors/:id/notes/', 'check_admin', 'edit_author_notes');
    $app->delete('/metadata/authors/:id/notes/', 'check_admin', 'del_author_notes');
    $app->post('/metadata/authors/:id/links/', 'check_admin', 'new_author_link');
    $app->delete('/metadata/authors/:id/links/:link_id/', 'check_admin', 'del_author_link');
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
    $app->get('/opds/', 'opdsRoot');
    $app->get('/opds/newest/', 'opdsNewest');
    $app->get('/opds/titleslist/:id/', 'opdsByTitle');
    $app->get('/opds/authorslist/', 'opdsByAuthorInitial');
    $app->get('/opds/authorslist/:initial/', 'opdsByAuthorNamesForInitial');
    $app->get('/opds/authorslist/:initial/:id/:page/', 'opdsByAuthor');
    $app->get('/opds/tagslist/', 'opdsByTagInitial');
    $app->get('/opds/tagslist/:initial/', 'opdsByTagNamesForInitial');
    $app->get('/opds/tagslist/:initial/:id/:page/', 'opdsByTag');
    $app->get('/opds/serieslist/', 'opdsBySeriesInitial');
    $app->get('/opds/serieslist/:initial/', 'opdsBySeriesNamesForInitial');
    $app->get('/opds/serieslist/:initial/:id/:page/', 'opdsBySeries');
    $app->get('/opds/opensearch.xml', 'opdsSearchDescriptor');
    $app->get('/opds/searchlist/:id/', 'opdsBySearch');
    $app->get('/opds/titles/:id/', 'title');
    $app->get('/opds/titles/:id/cover/', 'cover');
    $app->get('/opds/titles/:id/file/:file', 'book');
    $app->get('/opds/titles/:id/thumbnail/', 'thumbnail');
};
