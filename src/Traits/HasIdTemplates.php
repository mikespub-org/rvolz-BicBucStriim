<?php

/**
 * BicBucStriim
 *
 * Copyright 2012-2014 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

namespace BicBucStriim\Traits;

use BicBucStriim\Models\Idtemplate;
use BicBucStriim\Models\R;
use Exception;

trait HasIdTemplates
{
    /**
     * Find all ID templates in the settings DB
     * @return array id templates
     */
    public function idTemplates()
    {
        return R::findAll('idtemplate', ' order by name');
    }

    /**
     * Find a specific ID template in the settings DB
     * @param string $name 	template name
     * @return ?Idtemplate	IdTemplate or null
     */
    public function idTemplate($name)
    {
        $template = R::findOne('idtemplate', ' name = :name', [':name' => $name]);
        if (!is_null($template)) {
            $template = Idtemplate::cast($template);
        }
        return $template;
    }

    /**
     * Add a new ID template
     * @param string $name 		unique template name
     * @param string $value 	URL template
     * @param string $label 	display label
     * @return Idtemplate template record or null if there was an error
     */
    public function addIdTemplate($name, $value, $label): Idtemplate
    {
        $template = Idtemplate::build($name, $value, $label);
        $id = R::store($template);
        return $template;
    }

    /**
     * Delete an ID template from the database
     * @param string $name 	template namne
     * @return bool true if template was deleted else false
     */
    public function deleteIdTemplate($name)
    {
        $template = $this->idTemplate($name);
        if (!is_null($template)) {
            R::trash($template);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update an existing ID template. The name cannot be changed.
     * @param string $name 		template name
     * @param string $value 	URL template
     * @param string $label 	display label
     * @return ?Idtemplate updated template or null if there was an error
     */
    public function changeIdTemplate($name, $value, $label)
    {
        $template = $this->idTemplate($name);
        if (!is_null($template)) {
            $template->val = $value;
            $template->label = $label;
            try {
                $id = R::store($template);
                return $template;
            } catch (Exception $e) {
                return null;
            }
        } else {
            return null;
        }
    }
}
