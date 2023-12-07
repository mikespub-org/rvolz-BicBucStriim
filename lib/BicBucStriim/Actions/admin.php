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

use BicBucStriim\Calibre\Calibre;
use BicBucStriim\Calibre\Language;
use BicBucStriim\Calibre\Tag;
use Exception;
use ConfigMailer;
use Mailer;
use ConfigTtsOption;
use IdUrlTemplate;
use Encryption;
use Utilities;

/*********************************************************************
 * Admin actions
 ********************************************************************/
class AdminActions extends DefaultActions
{
    /**
     * Add routes for admin actions
    */
    public static function addRoutes($app, $prefix = '/admin')
    {
        $self = new self($app);
        $app->group($prefix, [$self, 'check_admin'], function () use ($app, $self) {
            $app->get('/', [$self, 'admin']);
            $app->get('/configuration/', [$self, 'configuration']);
            $app->post('/configuration/', [$self, 'change_json']);
            $app->get('/idtemplates/', [$self, 'get_idtemplates']);
            $app->put('/idtemplates/:id/', [$self, 'modify_idtemplate']);
            $app->delete('/idtemplates/:id/', [$self, 'clear_idtemplate']);
            $app->get('/mail/', [$self, 'get_smtp_config']);
            $app->put('/mail/', [$self, 'change_smtp_config']);
            $app->get('/users/', [$self, 'get_users']);
            $app->post('/users/', [$self, 'add_user']);
            $app->get('/users/:id/', [$self, 'get_user']);
            $app->put('/users/:id/', [$self, 'modify_user']);
            $app->delete('/users/:id/', [$self, 'delete_user']);
            $app->get('/version/', [$self, 'check_version']);
        });
    }

    /**
     * Generate the admin page -> /admin/
     */
    public function admin()
    {
        $this->render('admin.html', [
            'page' => $this->mkPage('admin', 0, 1),
            'isadmin' => $this->is_admin()]);
    }

    public function mkMailers()
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


    public function mkTitleTimeSortOptions()
    {
        $e0 = new ConfigTtsOption();
        $e0->key = TITLE_TIME_SORT_TIMESTAMP;
        $e0->text = $this->getMessageString('admin_tts_by_timestamp');
        $e1 = new ConfigTtsOption();
        $e1->key = TITLE_TIME_SORT_PUBDATE;
        $e1->text = $this->getMessageString('admin_tts_by_pubdate');
        $e2 = new ConfigTtsOption();
        $e2->key = TITLE_TIME_SORT_LASTMODIFIED;
        $e2->text = $this->getMessageString('admin_tts_by_lastmodified');
        return [$e0, $e1, $e2];
    }

    /**
     * Generate the configuration page -> GET /admin/configuration/
     */
    public function configuration()
    {
        $this->render('admin_configuration.html', [
            'page' => $this->mkPage('admin', 0, 2),
            'mailers' => $this->mkMailers(),
            'ttss' => $this->mkTitleTimeSortOptions(),
            'isadmin' => $this->is_admin()]);
    }

    /**
     * Generate the ID templates page -> GET /admin/idtemplates/
     */
    public function get_idtemplates()
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
            $ni->label = '';
            array_push($idtemplates, $ni);
        }
        $this->log()->debug('admin_get_idtemplates ' . json_encode($idtemplates));
        $this->render('admin_idtemplates.html', [
            'page' => $this->mkPage('admin_idtemplates', 0, 2),
            'templates' => $idtemplates,
            'isadmin' => $this->is_admin()]);
    }

    public function modify_idtemplate($id)
    {
        // parameter checking
        if (!preg_match('/^\w+$/u', $id)) {
            $this->log()->warn('admin_modify_idtemplate: invalid template id ' . $id);
            $this->mkError(400, "Invalid ID for template: " . $id);
            return;
        }

        $template_data = $this->request()->put();
        $this->log()->debug('admin_modify_idtemplate: ' . var_export($template_data, true));
        try {
            $template = $this->bbs()->idTemplate($id);
            if (is_null($template)) {
                $ntemplate = $this->bbs()->addIdTemplate($id, $template_data['url'], $template_data['label']);
            } else {
                $ntemplate = $this->bbs()->changeIdTemplate($id, $template_data['url'], $template_data['label']);
            }
        } catch (Exception $e) {
            $this->log()->error('admin_modify_idtemplate: error while adding template' . var_export($template_data, true));
            $this->log()->error('admin_modify_idtemplate: exception ' . $e->getMessage());
            $ntemplate = null;
        }
        if (!is_null($ntemplate)) {
            $msg = $this->getMessageString('admin_modified');
            $answer = json_encode(['template' => $ntemplate->unbox()->getProperties(), 'msg' => $msg]);
            $this->mkResponse($answer, 'application/json', 200);
        } else {
            $answer = $this->getMessageString('admin_modify_error');
            $this->mkResponse($answer, 'text/plain', 500);
        }
        #$this->log()->debug('admin_modify_idtemplate 2: '.var_export($ntemplate, true));
    }

    public function clear_idtemplate($id)
    {
        // parameter checking
        if (!preg_match('/^\w+$/u', $id)) {
            $this->log()->warn('admin_clear_idtemplate: invalid template id ' . $id);
            $this->mkError(400, "Invalid ID for template: " . $id);
            return;
        }

        $this->log()->debug('admin_clear_idtemplate: ' . var_export($id, true));
        $success = $this->bbs()->deleteIdTemplate($id);
        if ($success) {
            $msg = $this->getMessageString('admin_modified');
            $answer = json_encode(['msg' => $msg]);
            $this->mkResponse($answer, 'application/json', 200);
        } else {
            $answer = $this->getMessageString('admin_modify_error');
            $this->mkResponse($answer, 'text/plain', 404);
        }
    }

    /**
     * Generate the SMTP configuration page -> GET /admin/mail/
     */
    public function get_smtp_config()
    {
        $globalSettings = $this->settings();
        $mail = ['username' => $globalSettings[SMTP_USER],
            'password' => $globalSettings[SMTP_PASSWORD],
            'smtpserver' => $globalSettings[SMTP_SERVER],
            'smtpport' => $globalSettings[SMTP_PORT],
            'smtpenc' => $globalSettings[SMTP_ENCRYPTION]];
        $this->render('admin_mail.html', [
            'page' => $this->mkPage('admin_mail', 0, 2),
            'mail' => $mail,
            'encryptions' => $this->mkEncryptions(),
            'isadmin' => $this->is_admin()]);
    }

    public function mkEncryptions()
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
     */
    public function change_smtp_config()
    {
        $mail_data = $this->request()->put();
        $this->log()->debug('admin_change_smtp_configuration: ' . var_export($mail_data, true));
        $mail_config = [SMTP_USER => $mail_data['username'],
            SMTP_PASSWORD => $mail_data['password'],
            SMTP_SERVER => $mail_data['smtpserver'],
            SMTP_PORT => $mail_data['smtpport'],
            SMTP_ENCRYPTION => $mail_data['smtpenc']];
        $this->bbs()->saveConfigs($mail_config);
        $this->render('admin_mail.html', [
            'page' => $this->mkPage('admin_smtp', 0, 2),
            'mail' => $mail_data,
            'encryptions' => $this->mkEncryptions(),
            'isadmin' => $this->is_admin()]);
    }


    /**
     * Generate the users overview page -> GET /admin/users/
     */
    public function get_users()
    {
        $users = $this->bbs()->users();
        $this->render('admin_users.html', [
            'page' => $this->mkPage('admin_users', 0, 2),
            'users' => $users,
            'isadmin' => $this->is_admin()]);
    }


    /**
     * Generate the single user page -> GET /admin/users/:id/
     */
    public function get_user($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warn('admin_get_user: invalid user id ' . $id);
            $this->mkError(400, "Bad parameter");
            return;
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
        $this->log()->debug('admin_get_user: ' . json_encode($user));
        $this->render('admin_user.html', [
            'page' => $this->mkPage('admin_users', 0, 3),
            'user' => $user,
            'languages' => $languages,
            'tags' => $tags,
            'isadmin' => $this->is_admin()]);
    }

    /**
     * Add a user -> POST /admin/users/ (JSON)
     */
    public function add_user()
    {
        $user_data = $this->request()->post();
        $this->log()->debug('admin_add_user: ' . var_export($user_data, true));
        try {
            $user = $this->bbs()->addUser($user_data['username'], $user_data['password']);
        } catch (Exception $e) {
            $this->log()->error('admin_add_user: error for adding user ' . var_export($user_data, true));
            $this->log()->error('admin_add_user: exception ' . $e->getMessage());
            $user = null;
        }
        if (isset($user) && !is_null($user)) {
            $msg = $this->getMessageString('admin_modified');
            $answer = json_encode(['user' => $user->unbox()->getProperties(), 'msg' => $msg]);
            $this->mkResponse($answer, 'application/json', 200);
        } else {
            $answer = $this->getMessageString('admin_modify_error');
            $this->mkResponse($answer, 'text/plain', 500);
        }
    }

    /**
     * Delete a user -> DELETE /admin/users/:id/ (JSON)
     */
    public function delete_user($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warn('admin_delete_user: invalid user id ' . $id);
            $this->mkError(400, "Bad parameter");
            return;
        }

        $this->log()->debug('admin_delete_user: ' . var_export($id, true));
        $success = $this->bbs()->deleteUser($id);
        if ($success) {
            $msg = $this->getMessageString('admin_modified');
            $answer = json_encode(['msg' => $msg]);
            $this->mkResponse($answer, 'application/json', 200);
        } else {
            $answer = $this->getMessageString('admin_modify_error');
            $this->mkResponse($answer, 'text/plain', 500);
        }
    }

    /**
     * Modify a user -> PUT /admin/users/:id/ (JSON)
     */
    public function modify_user($id)
    {
        // parameter checking
        if (!is_numeric($id)) {
            $this->log()->warn('admin_modify_user: invalid user id ' . $id);
            $this->mkError(400, "Bad parameter");
            return;
        }

        $user_data = $this->request()->put();
        $this->log()->debug('admin_modify_user: ' . var_export($user_data, true));
        $user = $this->bbs()->changeUser(
            $id,
            $user_data['password'],
            $user_data['languages'],
            $user_data['tags'],
            $user_data['role']
        );
        $this->log()->debug('admin_modify_user: ' . json_encode($user));
        if (isset($user) && !is_null($user)) {
            $msg = $this->getMessageString('admin_modified');
            $answer = json_encode(['user' => $user->unbox()->getProperties(), 'msg' => $msg]);
            $this->mkResponse($answer, 'application/json', 200);
        } else {
            $answer = $this->getMessageString('admin_modify_error');
            $this->mkResponse($answer, 'text/plain', 500);
        }
    }


    /**
     * Processes changes in the admin page -> POST /admin/configuration/
     */
    public function change_json()
    {
        $globalSettings = $this->settings();
        $this->log()->debug('admin_change: started');
        # Check access permission
        if (!$this->is_admin()) {
            $this->log()->warn('admin_change: no admin permission');
            $this->render('admin_configuration.html', [
                'page' => $this->mkPage('admin'),
                'messages' => [$this->getMessageString('invalid_password')],
                'isadmin' => false]);
            return;
        }
        $nconfigs = [];
        $req_configs = $this->request()->post();
        $errors = [];
        $messages = [];
        $this->log()->debug('admin_change: ' . var_export($req_configs, true));

        ## Check for consistency - calibre directory
        # Calibre dir is still empty and no change in sight --> error
        if (!$this->has_valid_calibre_dir() && empty($req_configs[CALIBRE_DIR])) {
            array_push($errors, 1);
        }
        # Calibre dir changed, check it for existence, delete thumbnails of old calibre library
        elseif (array_key_exists(CALIBRE_DIR, $req_configs)) {
            $req_calibre_dir = $req_configs[CALIBRE_DIR];
            if ($req_calibre_dir != $globalSettings[CALIBRE_DIR]) {
                if (!Calibre::checkForCalibre($req_calibre_dir)) {
                    array_push($errors, 1);
                } elseif ($this->bbs()->clearThumbnails()) {
                    $this->log()->info('admin_change: Lib changed, deleted existing thumbnails.');
                } else {
                    $this->log()->info('admin_change: Lib changed, deletion of existing thumbnails failed.');
                }
            }
        }
        ## More consistency checks - kindle feature
        # Switch off Kindle feature, if no valid email address supplied
        if ($req_configs[KINDLE] == "1") {
            if (empty($req_configs[KINDLE_FROM_EMAIL])) {
                array_push($errors, 5);
            } elseif (Utilities::isEMailValid($req_configs[KINDLE_FROM_EMAIL])) {
                array_push($errors, 5);
            }
        }

        ## Check for a change in the thumbnail generation method
        if ($req_configs[THUMB_GEN_CLIPPED] != $globalSettings[THUMB_GEN_CLIPPED]) {
            $this->log()->info('admin_change: Thumbnail generation method changed. Existing Thumbnails will be deleted.');
            # Delete old thumbnails if necessary
            if ($this->bbs()->clearThumbnails()) {
                $this->log()->info('admin_change: Deleted exisiting thumbnails.');
            } else {
                $this->log()->info('admin_change: Deletion of exisiting thumbnails failed.');
            }
        }

        ## Check for a change in page size, min 1, max 100
        if ($req_configs[PAGE_SIZE] != $globalSettings[PAGE_SIZE]) {
            if ($req_configs[PAGE_SIZE] < 1 || $req_configs[PAGE_SIZE] > 100) {
                $this->log()->warn('admin_change: Invalid page size requested: ' . $req_configs[PAGE_SIZE]);
                array_push($errors, 4);
            }
        }

        # Don't save just return the error status
        if (count($errors) > 0) {
            $this->log()->error('admin_change: ended with error ' . var_export($errors, true));
            $this->render('admin_configuration.html', [
                'page' => $this->mkPage('admin'),
                'isadmin' => true,
                'errors' => $errors]);
        } else {
            ## Apply changes
            foreach ($req_configs as $key => $value) {
                if (!isset($globalSettings[$key]) || $value != $globalSettings[$key]) {
                    $nconfigs[$key] = $value;
                    $globalSettings[$key] = $value;
                    $this->log()->debug('admin_change: ' . $key . ' changed: ' . $value);
                }
            }
            # Save changes
            if (count($nconfigs) > 0) {
                $this->bbs()->saveConfigs($nconfigs);
                $this->log()->debug('admin_change: changes saved');
                $this->settings($globalSettings);
            }
            $this->log()->debug('admin_change: ended');
            $this->render('admin_configuration.html', [
                'page' => $this->mkPage('admin', 0, 2),
                'messages' => [$this->getMessageString('changes_saved')],
                'mailers' => $this->mkMailers(),
                'ttss' => $this->mkTitleTimeSortOptions(),
                'isadmin' => true,
            ]);
        }
    }

    /**
     * Get the new version info and compare it to our version -> GET /admin/version/
     */
    public function check_version()
    {
        $globalSettings = $this->settings();
        $versionAnswer = [];
        $contents = file_get_contents(VERSION_URL);
        if ($contents == false) {
            $versionClass = 'error';
            $versionAnswer = sprintf($this->getMessageString('admin_new_version_error'), $globalSettings['version']);
        } else {
            $versionInfo = json_decode($contents);
            $version = $globalSettings['version'];
            if (strpos($globalSettings['version'], '-') === false) {
                $v = preg_split('/-/', $globalSettings['version']);
                $version = $v[0];
            }
            $result = version_compare($version, $versionInfo->{'version'});
            if ($result === -1) {
                $versionClass = 'success';
                $msg1 = sprintf($this->getMessageString('admin_new_version'), $versionInfo->{'version'}, $globalSettings['version']);
                $msg2 = sprintf("<a href=\"%s\">%s</a>", $versionInfo->{'url'}, $versionInfo->{'url'});
                $msg3 = sprintf($this->getMessageString('admin_check_url'), $msg2);
                $versionAnswer = $msg1 . '. ' . $msg3;
            } else {
                $versionClass = '';
                $versionAnswer = sprintf($this->getMessageString('admin_no_new_version'), $globalSettings['version']);
            }
        }
        $this->render('admin_version.html', [
            'page' => $this->mkPage('admin_check_version', 0, 2),
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
    public function has_valid_calibre_dir()
    {
        $globalSettings = $this->settings();
        return (!empty($globalSettings[CALIBRE_DIR]) &&
            Calibre::checkForCalibre($globalSettings[CALIBRE_DIR]));
    }
}
