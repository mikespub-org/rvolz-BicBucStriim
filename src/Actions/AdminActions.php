<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2023 Rainer Volz
 * Copyright 2023-     mikespub
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Actions;

use BicBucStriim\AppData\Settings;
use BicBucStriim\Calibre\Calibre;
use BicBucStriim\Calibre\Language;
use BicBucStriim\Calibre\Tag;
use BicBucStriim\Utilities\InputUtil;
use BicBucStriim\Utilities\Mailer;
use BicBucStriim\Utilities\RouteUtil;
use Psr\Http\Message\ResponseInterface as Response;
use Exception;

/*********************************************************************
 * Admin actions
 ********************************************************************/
class AdminActions extends DefaultActions
{
    public const PREFIX = '/admin';

    /**
     * Add routes for admin actions
     */
    public static function addRoutes($app, $prefix = self::PREFIX, $gatekeeper = null)
    {
        $self = static::class;
        $routes = static::getRoutes($self, $gatekeeper);
        // use $gatekeeper for all actions in this group
        $app->group($prefix, function (\Slim\Routing\RouteCollectorProxy $group) use ($routes) {
            RouteUtil::mapRoutes($group, $routes);
        })->add($gatekeeper);
    }

    /**
     * Get routes for admin actions
     * @param self|class-string $self
     * @param ?object $gatekeeper (optional)
     * @return array<mixed> list of [method(s), path, ...middleware(s), callable] for each action
     */
    public static function getRoutes($self, $gatekeeper = null)
    {
        return [
            // name => method(s), path, ...middleware(s), callable
            'admin-home' => ['GET', '/', [$self, 'admin']],
            'admin-config' => ['GET', '/configuration/', [$self, 'configuration']],
            'admin-config-post' => ['POST', '/configuration/', [$self, 'changeJson']],
            'admin-idtemplates' => ['GET', '/idtemplates/', [$self, 'getIdTemplates']],
            'admin-idtemplate-put' => ['PUT', '/idtemplates/{id}/', [$self, 'modifyIdTemplate']],
            'admin-idtemplate-delete' => ['DELETE', '/idtemplates/{id}/', [$self, 'clearIdTemplate']],
            'admin-smtp-config' => ['GET', '/mail/', [$self, 'getSmtpConfig']],
            'admin-smtp-config-put' => ['PUT', '/mail/', [$self, 'changeSmtpConfig']],
            'admin-users' => ['GET', '/users/', [$self, 'getUsers']],
            'admin-users-post' => ['POST', '/users/', [$self, 'addUser']],
            'admin-user' => ['GET', '/users/{id}/', [$self, 'getUser']],
            'admin-user-put' => ['PUT', '/users/{id}/', [$self, 'modifyUser']],
            'admin-user-delete' => ['DELETE', '/users/{id}/', [$self, 'deleteUser']],
            'admin-check-version' => ['GET', '/version/', [$self, 'checkVersion']],
        ];
    }

    /**
     * Generate the admin page -> /admin/
     * @return Response
     */
    public function admin()
    {
        return $this->render('admin.twig', [
            'page' => $this->buildPage('admin', 0, 1),
            'isadmin' => $this->requester->isAdmin()]);
    }

    public function buildMailers()
    {
        $e0 = new ConfigMailer();
        $e0->key = Mailer::SMTP;
        $e0->text = $this->getMessageString('admin_mailer_smtp');
        $e1 = new ConfigMailer();
        $e1->key = Mailer::SENDMAIL;
        $e1->text = $this->getMessageString('admin_mailer_sendmail');
        $e2 = new ConfigMailer();
        $e2->key = Mailer::MAIL;
        $e2->text = $this->getMessageString('admin_mailer_mail');
        return [$e0, $e1, $e2];
    }

    public function buildTemplatesDirs()
    {
        $e = new ConfigTemplatesDir();
        $e->key = '';
        $e->text = 'templates (default)';
        $options = [$e];
        $templatesDir = realpath('templates');
        $subDirs = glob($templatesDir . '/*', GLOB_ONLYDIR);
        foreach ($subDirs as $subDir) {
            $e = new ConfigTemplatesDir();
            $e->key = str_replace($templatesDir, 'templates', $subDir);
            $e->text = str_replace($templatesDir, 'templates', $subDir);
            $options[] = $e;
        }
        return $options;
    }

    public function buildTitleTimeSortOptions()
    {
        $e0 = new ConfigTtsOption();
        $e0->key = Settings::TITLE_TIME_SORT_TIMESTAMP;
        $e0->text = $this->getMessageString('admin_tts_by_timestamp');
        $e1 = new ConfigTtsOption();
        $e1->key = Settings::TITLE_TIME_SORT_PUBDATE;
        $e1->text = $this->getMessageString('admin_tts_by_pubdate');
        $e2 = new ConfigTtsOption();
        $e2->key = Settings::TITLE_TIME_SORT_LASTMODIFIED;
        $e2->text = $this->getMessageString('admin_tts_by_lastmodified');
        return [$e0, $e1, $e2];
    }

    /**
     * Generate the configuration page -> GET /admin/configuration/
     * @return Response
     */
    public function configuration()
    {
        return $this->render('admin_configuration.twig', [
            'page' => $this->buildPage('admin', 0, 2),
            'mailers' => $this->buildMailers(),
            'ttss' => $this->buildTitleTimeSortOptions(),
            'templates_dirs' => $this->buildTemplatesDirs(),
            'isadmin' => $this->requester->isAdmin()]);
    }

    /**
     * Generate the ID templates page -> GET /admin/idtemplates/
     * @return Response
     */
    public function getIdTemplates()
    {
        $idtemplates = $this->bbs()->idTemplates();
        $idtypes = $this->calibre()->idTypes();
        $ids2add = [];
        foreach ($idtypes as $idtype) {
            if (empty($idtemplates)) {
                array_push($ids2add, $idtype['type']);
            } else {
                $found = false;
                foreach ($idtemplates as $idtemplate) {
                    if ($idtype['type'] === $idtemplate->name) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    array_push($ids2add, $idtype['type']);
                }
            }
        }
        foreach ($ids2add as $id2add) {
            $ni = new IdUrlTemplate();
            $ni->name = $id2add;
            $ni->val = '';
            $ni->label = '(Calibre)';
            array_push($idtemplates, $ni);
        }
        $this->log()->debug('admin_getIdTemplates ' . json_encode($idtemplates));
        return $this->render('admin_idtemplates.twig', [
            'page' => $this->buildPage('admin_idtemplates', 0, 2),
            'templates' => $idtemplates,
            'isadmin' => $this->requester->isAdmin()]);
    }

    /**
     * Modify an ID template page -> PUT /admin/idtemplates/{id}/
     * @return Response
     */
    public function modifyIdTemplate($id)
    {
        // parameter checking
        if (!preg_match('/^\w+$/u', $id)) {
            $this->log()->warning('admin_modifyIdTemplate: invalid template id ' . $id);
            return $this->responder->error(400, "Invalid ID for template: " . $id);
        }

        $template_data = $this->requester->post();
        $this->log()->debug('admin_modifyIdTemplate: ' . var_export($template_data, true));
        try {
            $template = $this->bbs()->idTemplate($id);
            if (is_null($template)) {
                $ntemplate = $this->bbs()->addIdTemplate($id, $template_data['url'], $template_data['label']);
            } else {
                $ntemplate = $this->bbs()->changeIdTemplate($id, $template_data['url'], $template_data['label']);
            }
        } catch (Exception $e) {
            $this->log()->error('admin_modifyIdTemplate: error while adding template' . var_export($template_data, true));
            $this->log()->error('admin_modifyIdTemplate: exception ' . $e->getMessage());
            $ntemplate = null;
        }
        if (!is_null($ntemplate)) {
            $msg = $this->getMessageString('admin_modified');
            $data = ['template' => $ntemplate->unbox()->getProperties(), 'msg' => $msg];
            return $this->responder->json($data);
        } else {
            $message = $this->getMessageString('admin_modify_error');
            return $this->responder->error(500, $message);
        }
        #$this->log()->debug('admin_modifyIdTemplate 2: '.var_export($ntemplate, true));
    }

    /**
     * Clear an ID template page -> DELETE /admin/idtemplates/{id}/
     * @return Response
     */
    public function clearIdTemplate($id)
    {
        // parameter checking
        if (!preg_match('/^\w+$/u', $id)) {
            $this->log()->warning('admin_clearIdTemplate: invalid template id ' . $id);
            return $this->responder->error(400, "Invalid ID for template: " . $id);
        }

        $this->log()->debug('admin_clearIdTemplate: ' . var_export($id, true));
        $success = $this->bbs()->deleteIdTemplate($id);
        if ($success) {
            $msg = $this->getMessageString('admin_modified');
            $data = ['msg' => $msg];
            return $this->responder->json($data);
        } else {
            $message = $this->getMessageString('admin_modify_error');
            return $this->responder->error(404, $message);
        }
    }

    /**
     * Generate the SMTP configuration page -> GET /admin/mail/
     * @return Response
     */
    public function getSmtpConfig()
    {
        $settings = $this->settings();
        $mail = [
            'username' => $settings[Settings::SMTP_USER],
            'password' => $settings[Settings::SMTP_PASSWORD],
            'smtpserver' => $settings[Settings::SMTP_SERVER],
            'smtpport' => $settings[Settings::SMTP_PORT],
            'smtpenc' => $settings[Settings::SMTP_ENCRYPTION],
        ];
        return $this->render('admin_mail.twig', [
            'page' => $this->buildPage('admin_mail', 0, 2),
            'mail' => $mail,
            'encryptions' => $this->buildEncryptions(),
            'isadmin' => $this->requester->isAdmin()]);
    }

    public function buildEncryptions()
    {
        $e0 = new Encryption();
        $e0->key = 0;
        $e0->text = $this->getMessageString('admin_smtpenc_none');
        $e1 = new Encryption();
        $e1->key = 1;
        $e1->text = $this->getMessageString('admin_smtpenc_ssl');
        $e2 = new Encryption();
        $e2->key = 2;
        $e2->text = $this->getMessageString('admin_smtpenc_tls');
        return [$e0, $e1, $e2];
    }

    /**
     * Change the SMTP configuration -> PUT /admin/mail/
     * @return Response
     */
    public function changeSmtpConfig()
    {
        $mail_data = $this->requester->post();
        $this->log()->debug('admin_changeSmtpConfiguration: ' . var_export($mail_data, true));
        $mail_config = [
            Settings::SMTP_USER => $mail_data['username'],
            Settings::SMTP_PASSWORD => $mail_data['password'],
            Settings::SMTP_SERVER => $mail_data['smtpserver'],
            Settings::SMTP_PORT => $mail_data['smtpport'],
            Settings::SMTP_ENCRYPTION => $mail_data['smtpenc'],
        ];
        $this->bbs()->saveConfigs($mail_config);
        return $this->render('admin_mail.twig', [
            'page' => $this->buildPage('admin_smtp', 0, 2),
            'mail' => $mail_data,
            'encryptions' => $this->buildEncryptions(),
            'isadmin' => $this->requester->isAdmin()]);
    }


    /**
     * Generate the users overview page -> GET /admin/users/
     * @return Response
     */
    public function getUsers()
    {
        $users = $this->bbs()->users();
        return $this->render('admin_users.twig', [
            'page' => $this->buildPage('admin_users', 0, 2),
            'users' => $users,
            'isadmin' => $this->requester->isAdmin()]);
    }


    /**
     * Generate the single user page -> GET /admin/users/{id}/
     * @return Response
     */
    public function getUser($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('admin_getUser: invalid user id ' . $id);
            return $this->badParameter();
        }

        $user = $this->bbs()->user($id);
        $languages = $this->calibre()->languages();
        foreach ($languages as $language) {
            $language->key = $language->lang_code;
        }
        $nl = new Language();
        $nl->lang_code = $this->getMessageString('admin_no_selection');
        $nl->key = '';
        array_unshift($languages, $nl);
        $tags = $this->calibre()->tags();
        foreach ($tags as $tag) {
            $tag->key = $tag->name;
        }
        $nt = new Tag();
        $nt->name = $this->getMessageString('admin_no_selection');
        $nt->key = '';
        array_unshift($tags, $nt);
        $this->log()->debug('admin_getUser: ' . json_encode($user));
        return $this->render('admin_user.twig', [
            'page' => $this->buildPage('admin_users', 0, 3),
            'user' => $user,
            'languages' => $languages,
            'tags' => $tags,
            'isadmin' => $this->requester->isAdmin()]);
    }

    /**
     * Add a user -> POST /admin/users/ (JSON)
     * @return Response
     */
    public function addUser()
    {
        $user_data = $this->requester->post();
        $this->log()->debug('admin_addUser: ' . var_export($user_data, true));
        try {
            $user = $this->bbs()->addUser($user_data['username'], $user_data['password']);
        } catch (Exception $e) {
            $this->log()->error('admin_addUser: error for adding user ' . var_export($user_data, true));
            $this->log()->error('admin_addUser: exception ' . $e->getMessage());
            $user = null;
        }
        if (isset($user)) {
            $msg = $this->getMessageString('admin_modified');
            $data = ['user' => $user->unbox()->getProperties(), 'msg' => $msg];
            return $this->responder->json($data);
        } else {
            $message = $this->getMessageString('admin_modify_error');
            return $this->responder->error(500, $message);
        }
    }

    /**
     * Delete a user -> DELETE /admin/users/{id}/ (JSON)
     * @return Response
     */
    public function deleteUser($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('admin_deleteUser: invalid user id ' . $id);
            return $this->badParameter();
        }

        $this->log()->debug('admin_deleteUser: ' . var_export($id, true));
        $success = $this->bbs()->deleteUser($id);
        if ($success) {
            $msg = $this->getMessageString('admin_modified');
            $data = ['msg' => $msg];
            return $this->responder->json($data);
        } else {
            $message = $this->getMessageString('admin_modify_error');
            return $this->responder->error(500, $message);
        }
    }

    /**
     * Modify a user -> PUT /admin/users/{id}/ (JSON)
     * @return Response
     */
    public function modifyUser($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warning('admin_modifyUser: invalid user id ' . $id);
            return $this->badParameter();
        }

        $user_data = $this->requester->post();
        $this->log()->debug('admin_modifyUser: ' . var_export($user_data, true));
        $user = $this->bbs()->changeUser(
            $id,
            $user_data['password'],
            $user_data['languages'],
            $user_data['tags'],
            $user_data['role']
        );
        $this->log()->debug('admin_modifyUser: ' . json_encode($user));
        if (isset($user)) {
            $msg = $this->getMessageString('admin_modified');
            $data = ['user' => $user->unbox()->getProperties(), 'msg' => $msg];
            return $this->responder->json($data);
        } else {
            $message = $this->getMessageString('admin_modify_error');
            return $this->responder->error(500, $message);
        }
    }


    /**
     * Processes changes in the admin page -> POST /admin/configuration/
     * @return Response
     */
    public function changeJson()
    {
        $settings = $this->settings();
        $this->log()->debug('admin_change: started');
        # Check access permission
        if (!$this->requester->isAdmin()) {
            $this->log()->warning('admin_change: no admin permission');
            return $this->render('admin_configuration.twig', [
                'page' => $this->buildPage('admin'),
                'messages' => [$this->getMessageString('invalid_password')],
                'isadmin' => false]);
        }
        $nconfigs = [];
        $req_configs = $this->requester->post();
        $errors = [];
        $messages = [];
        $this->log()->debug('admin_change: ' . var_export($req_configs, true));

        ## Check for consistency - calibre directory
        # Calibre dir is still empty and no change in sight --> error
        if (!$this->hasValidCalibreDir() && empty($req_configs[Settings::CALIBRE_DIR])) {
            array_push($errors, 1);
        }
        # Calibre dir changed, check it for existence, delete thumbnails of old calibre library
        elseif (array_key_exists(Settings::CALIBRE_DIR, $req_configs)) {
            $req_calibre_dir = $req_configs[Settings::CALIBRE_DIR];
            if ($req_calibre_dir != $settings->calibre_dir) {
                if (!Calibre::checkForCalibre($req_calibre_dir)) {
                    array_push($errors, 1);
                } elseif ($this->thumbs()->clearThumbnails()) {
                    $this->log()->info('admin_change: Lib changed, deleted existing thumbnails.');
                } else {
                    $this->log()->info('admin_change: Lib changed, deletion of existing thumbnails failed.');
                }
            }
        }
        ## More consistency checks - kindle feature
        # Switch off Kindle feature, if no valid email address supplied
        if ($req_configs[Settings::KINDLE] == "1") {
            if (empty($req_configs[Settings::KINDLE_FROM_EMAIL])) {
                array_push($errors, 5);
            } elseif (!InputUtil::isEMailValid($req_configs[Settings::KINDLE_FROM_EMAIL])) {
                array_push($errors, 5);
            }
        }

        ## Check for a change in the thumbnail generation method
        if ($req_configs[Settings::THUMB_GEN_CLIPPED] != $settings->thumb_gen_clipped) {
            $this->log()->info('admin_change: Thumbnail generation method changed. Existing Thumbnails will be deleted.');
            # Delete old thumbnails if necessary
            if ($this->thumbs()->clearThumbnails()) {
                $this->log()->info('admin_change: Deleted exisiting thumbnails.');
            } else {
                $this->log()->info('admin_change: Deletion of exisiting thumbnails failed.');
            }
        }

        ## Check for a change in page size, min 1, max 100
        if ($req_configs[Settings::PAGE_SIZE] != $settings->page_size) {
            if ($req_configs[Settings::PAGE_SIZE] < 1 || $req_configs[Settings::PAGE_SIZE] > 100) {
                $this->log()->warning('admin_change: Invalid page size requested: ' . $req_configs[Settings::PAGE_SIZE]);
                array_push($errors, 4);
            }
        }

        # Don't save just return the error status
        if (count($errors) > 0) {
            $this->log()->error('admin_change: ended with error ' . var_export($errors, true));
            return $this->render('admin_configuration.twig', [
                'page' => $this->buildPage('admin'),
                'isadmin' => true,
                'errors' => $errors]);
        } else {
            ## Apply changes
            foreach ($req_configs as $key => $value) {
                if (!isset($settings[$key]) || $value != $settings[$key]) {
                    $nconfigs[$key] = $value;
                    $settings[$key] = $value;
                    $this->log()->debug('admin_change: ' . $key . ' changed: ' . $value);
                }
            }
            # Save changes
            if (count($nconfigs) > 0) {
                $this->bbs()->saveConfigs($nconfigs);
                $this->log()->debug('admin_change: changes saved');
                $this->setSettings($settings);
            }
            $this->log()->debug('admin_change: ended');
            return $this->render('admin_configuration.twig', [
                'page' => $this->buildPage('admin', 0, 2),
                'messages' => [$this->getMessageString('changes_saved')],
                'mailers' => $this->buildMailers(),
                'ttss' => $this->buildTitleTimeSortOptions(),
                'templates_dirs' => $this->buildTemplatesDirs(),
                'isadmin' => true,
            ]);
        }
    }

    /**
     * Get the new version info and compare it to our version -> GET /admin/version/
     * @return Response
     */
    public function checkVersion()
    {
        $settings = $this->settings();
        $versionAnswer = [];
        $contents = file_get_contents(Settings::VERSION_URL);
        if ($contents == false) {
            $versionClass = 'error';
            $versionAnswer = sprintf($this->getMessageString('admin_new_version_error'), $settings['version']);
        } else {
            $versionInfo = json_decode($contents);
            $version = $settings['version'];
            if (strpos($settings['version'], '-') === false) {
                $v = preg_split('/-/', $settings['version']);
                $version = $v[0];
            }
            $result = version_compare($version, $versionInfo->{'version'});
            if ($result === -1) {
                $versionClass = 'success';
                $msg1 = sprintf($this->getMessageString('admin_new_version'), $versionInfo->{'version'}, $settings['version']);
                $msg2 = sprintf("<a href=\"%s\">%s</a>", $versionInfo->{'url'}, $versionInfo->{'url'});
                $msg3 = sprintf($this->getMessageString('admin_check_url'), $msg2);
                $versionAnswer = $msg1 . '. ' . $msg3;
            } else {
                $versionClass = '';
                $versionAnswer = sprintf($this->getMessageString('admin_no_new_version'), $settings['version']);
            }
        }
        return $this->render('admin_version.twig', [
            'page' => $this->buildPage('admin_check_version', 0, 2),
            'versionClass' => $versionClass,
            'versionAnswer' => $versionAnswer,
            'isadmin' => true,
        ]);
    }

    /*********************************************************************
     * Utility and helper functions, private
     ********************************************************************/

    /**
     * Is there a valid - existing - Calibre directory?
     * @return boolean    true if available
     */
    public function hasValidCalibreDir()
    {
        $settings = $this->settings();
        return !empty($settings->calibre_dir) &&
            Calibre::checkForCalibre($settings->calibre_dir);
    }
}
