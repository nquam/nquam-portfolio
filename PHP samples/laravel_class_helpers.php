<?php
/**
 * Library for helper functions
 *
 * @author 
 * @since October 4, 2012
 */
class Redacted
{
    /**
     * This function generates a truly unique id/key.
     * We say "truly" unique because uniqueness is maintained across environments.
     * $db_name is optional - if it is null we will attempt to discover it from the DB configuration
     *
     * @param string $table_name
     * @param string $db_name
     * @return string
     */
    public static function generate_unique_id($table_name, $db_name = null)
    {
        return md5(uniqid().'-'.time().'-'.$table_name).'_'.(is_null($db_name) ? Config::get('database.connections.mysql.database') : $db_name);
    }

    /**
     * Send email
     *
     * @param string[] $to
     * @param string $body
     * @param string $subject
     * @param string[] $from
     * @param string $alt_body
     * @return integer number of successful recipients
     */
    public static function send_email($to, $body, $subject, $from = array(EMAIL_FROM_ADDRESS => EMAIL_FROM_NAME), $alt_body = null)
    {
        // If no alt body is given, produce it by stripping the HTML out of the body
        if (is_null($alt_body))
        {
            $alt_body = strip_tags($body);
        }

        switch (MAIL_SERVICE)
        {
            case 'SENDGRID':
                $sendgrid = new SendGrid();

            $mail = new SendGrid\Mail();

            if(is_array($to))
            {
                foreach($to as $to_email)
                {
                    $mail->addTo($to_email);
                }
            }
            else
            {
                $mail->addTo($to);
            }
            $mail->setFrom($from)
               ->setSubject($subject)
               ->setText($alt_body)
               ->setHtml($body);

                $result = $sendgrid->smtp->send($mail);
                break;
            case 'MAILGUN':
                $message = Mailgun::message(function($msg) use($to, $body, $subject, $from, $alt_body) {
                    if (is_array($from))
                    {
                        $from_email = current(array_keys($from));
                        $from_name = $from[$from_email];
                        $from = "{$from_name} <$from_email>";
                    }

                    $msg->from($from)
                        ->to($to)
                        ->subject($subject)
                        ->text($alt_body)
                        ->html($body);

                    return $msg;
                });

                $result = $message->deliver();
                break;
            default:
                // Get the Swift Mailer instance
                $mailer = IoC::resolve('mailer');

                // Get a new Swift_Message instance
                $message = Swift_Message::newInstance($subject)
                    ->setFrom($from)
                    ->setTo($to)
                    ->addPart($alt_body, 'text/plain')
                    ->setBody($body, 'text/html');

                $result = $mailer->send($message);
        }

        return $result;
    }

    /**
     * Render email templates and pass back the HTML
     *
     * @param string $template_name
     * @param string $data
     * @param string $path
     * @return string
     */
    public static function email_template($template_name, $data, $path = 'admin.email_templates.')
    {
        $view = $path.$template_name;

        return View::exists($view)
            ? render($view, $data)
            : false;
    }

    /**
     * Render email templates and pass back the HTML
     *
     * @param string $template_name
     * @return string
     */
    public static function generate_random_password($min_length = PASSWORD_MIN_LENGTH)
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $pass = array();
        for ($i = 0; $i < $min_length; $i++)
        {
            $n = rand(0, strlen($alphabet) - 1);
            $pass[$i] = $alphabet[$n];
        }

        return implode($pass);
    }

    /**
     * Make a furl and check its uniqueness against a given model
     *
     * @param string $str
     * @param string $model
     * @return boolean|string furl
     */
    public static function generate_furl($str, $model)
    {
        if (redacted::check_callable($model))
        {
            // Auto generate a furl based off a string
            $generated_furl = $original_furl = Str::slug($str);

            $i = 1;
            // See if the generated furl exists
            while (!is_null($furl_exists = $model::where_furl($generated_furl)->first()))
            {
                // Attach an incremented integer to the end for each occurance of an existing furl
                $generated_furl = $original_furl.'-'.$i;

                $i++;
            }

            return $generated_furl;
        }

        return false;
    }

    /**
     * If we are in /admin/ then return "admin/"
     *
     * @return string
     */
    public static function admin()
    {
        return URI::segment(1) == 'admin' ? 'admin/' : '';
    }

    /**
     * Recursive function to the heirarchy of sections
     * @todo It's possible to speed this up if we write straight SQL to grab all sections in one query,
     *      then we can traverse through the results to build a hierarchical array, rather than separate calls per node.
     *
     * @param array of objects typically starting with root level sections
     * @return array
     */
    public static function get_section_heirarchy($sections)
    {
        $heirarchy = false;
        if (!empty($sections))
        {
            foreach ($sections as $section)
            {
                $heirarchy[$section->name] = $section;

                $nested_sections = $section->sections;

                if (!empty($nested_sections))
                {
                    $heirarchy[$section->name]->nested = self::get_section_heirarchy($nested_sections);
                }
            }
        }

        return $heirarchy;
    }

    /**
     * Recursive function to nest categories and resources accordingly
     *
     * @param array of objects starting with categories
     * @return array
     */
    public static function get_heirarchy($categories)
    {
        $heirarchy = false;
        if (!empty($categories))
        {
            foreach ($categories as $category)
            {
                $heirarchy[$category->furl] = $category;

                $nested_resources = $category->resources()->order_by(Category::$join_table.'.sort')->get();

                if (!empty($nested_resources))
                {
                    $nested = array();

                    $class = get_class($nested_resources[0]);
                    $sort = $class::getContentSort();
                    $shouldSort = is_array($sort) && array_key_exists('column', $sort);

                    foreach ($nested_resources as $resource)
                    {
                        $content_check[$resource->furl] = FALSE;

                        // Attempt to grab different types of current content
                        if ($resource->alias_id)
                        {
                            if (!$content_check[$resource->furl] = (get_class($resource->alias->reference) == 'RrSection'))
                            {
                                if (!$content_check[$resource->furl] = !!$resource->alias->reference->link_id)
                                {
                                    $content_check[$resource->furl] = !!$resource->alias->reference->current_content;
                                }
                            }
                        }
                        elseif ($resource->link_id)
                        {
                            $content_check[$resource->furl] = TRUE;
                        }
                        else
                        {
                            $content_check[$resource->furl] = $resource->current_content;
                        }

                        // Check to make sure the current_content exists (by inspection above) before sending it out for display
                        if ($content_check[$resource->furl])
                        {
                            $resource->display = self::build_resource_display($resource);

                            $resource = self::build_resource_link_data($resource);

                            $nested[$resource->id] = $resource;
                        }

                        if ($shouldSort) {
                            $resource->sort_value = $resource->current_content->{$sort['column']};
                        }

                    }

                    if ($shouldSort) {
                        $nested = redacted::sort_resources_array($nested, $sort);
                    }

                    $heirarchy[$category->furl]->nested_resources = $nested;
                }

                $nested_categories = $category->categories()->get();

                if (!empty($nested_categories))
                {
                    $heirarchy[$category->furl]->categories = self::get_heirarchy($nested_categories);
                }
            }
        }

        return $heirarchy;
    }

    /**
     * Get all of categories for this resource type and put them into arrays based on their hierarchy.
     * Attach resources to the categories they belong to.
     *
     * @param string $model name of the model to get the resources from
     * @return array|boolean
     */
    public static function get_resources_categories($model)
    {
        // Make sure the model class passed in exists
        if (!class_exists($model))
        {
            Log::error('redacted::get_resources_categories() - The class provided for the $model in this function does not exist.');
            return false;
        }

        // Section
        $section = RrSection::where_name(Str::plural(Str::lower(str_replace('Resource_Structure_', '', $model))))->first();

        // Get an array of all ids for all resources under this model
        $all_resource_ids = $model::lists('id');

        // Get all resources attached to categories
        $attached_resource_ids = DB::table('category_resource')->where_ref_model($model)->lists('structure_id');

        // Compare the two to find out which resources are unattached
        $section_content_table = $section->content_model();

        $unattached_resource_ids = array_diff($all_resource_ids, $attached_resource_ids);
        $unattached_resources = (!empty($unattached_resource_ids))
            ? $model::with(array('alias', 'current_content'))->where_in('id', $unattached_resource_ids)->get()
            : array();

        // This is used to reduce the amount of content lookups
        if (!empty($unattached_resource_ids))
        {
            $resource_num_entries_raw = DB::query('SELECT id, (SELECT COUNT(*) FROM '.$section_content_table::$table.' WHERE structure_id = resource_structure.id) as num_entries FROM '.$model::$table.' as resource_structure WHERE id IN ('.implode(',', $unattached_resource_ids).')');

            if (!empty($resource_num_entries_raw))
            {
                foreach ($resource_num_entries_raw as $row)
                {
                    $resource_num_entries[$row->id] = $row->num_entries;
                }
            }

            foreach ($unattached_resources as $i => $resource)
            {
                $resource->display = self::build_resource_display($resource);

                $resource->num_entries = $resource_num_entries[$row->id];
                $resource->actions = self::resource_actions($section->furl, $resource);

                $data['unattached_resources'][] = $resource;
            }
        }

        // Get all categories that don't have parent_id & belong to this model
        $categories = Category::where_null('parent_id')
            ->where('ref_model', '=', $model)
            ->order_by('sort', 'asc')
            ->get();

        // Recursively nest all categories and resources
        $data['categorical_heirarchy'] = self::get_heirarchy($categories);

        return $data;
    }

    /**
     * Get all of this resource type and put them into arrays based on their hierarchy.
     * There very well might be a better way to do this besides hard coding the 3 possible
     * levels of hierarchy but this is very straight forward and easy for everyone to understand.
     *
     * @param string $model name of the model to get the resources from
     * @return array|boolean
     */
    public static function get_resources_sort($model)
    {
        // Make sure the model class passed in exists
        if (!class_exists($model))
        {
            Log::error('redacted::get_resources_sort() - The class provided for the $model in this function does not exist.');
            return false;
        }

        $resources = array();

        $section_furl = RrSection::where_name(Str::plural(Str::lower(str_replace('Resource_Structure_', '', $model))))->only('furl');

        if (self::admin() === '')
        {
            $root_resources = $model::with(array('alias'))
                ->where_null('parent_id')
                ->order_by('sort', 'asc')
                ->get();

            if (!empty($root_resources))
            {
                $resources = self::get_resources_heirarchy($model, $section_furl, $root_resources);
            }
        }
        else
        {
            $root_resources = $model::with(array('alias'))
                ->where_null('parent_id')
                ->order_by('sort', 'asc')
                ->get();

            if (!empty($root_resources))
            {
                $resources = self::get_resources_heirarchy($model, $section_furl, $root_resources);
            }
        }

        $data['sortable_heirarchy'] = $resources;

        return $data;
    }

    /**
     * Get all of this resource type and put them into arrays based on their hierarchy.
     * There very well might be a better way to do this besides hard coding the 3 possible
     * levels of hierarchy but this is very straight forward and easy for everyone to understand.
     *
     * @param string $model name of the model to get the resources from
     * @return array|boolean
     */
    public static function get_resources_forum($model)
    {
        return self::get_resources_sort($model);
    }

    /**
     * Recursive function to nest resources accordingly
     *
     * @param string $model
     * @param object[] $resources
     * @return array
     */
    public static function get_resources_heirarchy($model, $section_furl, $resources)
    {
        $heirarchy = array();

        if (!empty($resources))
        {
            $class = get_class($resources[0]);
            $sort = $class::getContentSort();
            $should_sort = is_array($sort) && array_key_exists('column', $sort);

            foreach ($resources as $resource)
            {
                $key = $resource->id;

                // These establish the relationships if they exist
                $resource->alias;
                $resource->current_content;

                // Check whether the content exists within the constraints of our filter
                // Typical for checking if a resource contains any published content
                if (empty($resource->alias) && empty($resource->current_content) && empty($resource->link_id))
                {
                    // Common format for display
                    continue;
                }

                $heirarchy[$key] = redacted::build_resource_link_data($resource);

                if ($should_sort)
                {
                    $resource->sort_value = $resource->current_content->{$sort['column']};
                }

                $heirarchy[$key]->actions = self::resource_actions($section_furl, $resource);

                // Special nesting requests for category resources
                if ($model  == 'Category')
                {
                    $nested_relationships = $resource->resources('Category_Resource')->get();

                    foreach ($nested_relationships as $pivot_id => $pivot_row)
                    {
                        $nested_resources[$pivot_id] = $pivot_row->resources($pivot_row->ref_model)->first();
                    }
                }
                else
                {
                    $nested_resources = $resource->resources($model)->order_by('sort', 'asc')->get();
                }

                if (!empty($nested_resources))
                {
                    // Do the recursion
                    $heirarchy[$key]->resources = self::get_resources_heirarchy($model, $section_furl, $nested_resources);
                }
            }

            if ($should_sort)
            {
                $heirarchy = redacted::sort_resources_array($heirarchy, $sort);
            }
        }

// dd($heirarchy);
        return $heirarchy;
    }

    /**
     * Sort a resources array based on sort parameters
     *
     * @param array $resources
     * @param array $sort
     * @return array
     */
    private static function sort_resources_array($resources, $sort)
    {
        usort($resources, 'redacted::cmp_resources');

        if (array_key_exists('direction', $sort) && $sort['direction'] == 'desc')
        {
            return array_reverse($resources, true);
        }
        else
        {
            return $resources;
        }
    }

    /**
     * Comparison function used for sorting a resources array
     *
     * @param resource $a
     * @param resource $b
     * @return -1, 0, or 1
     */
    private static function cmp_resources($a, $b)
    {
        switch (gettype($a->sort_value))
        {
            /*
             * if you need to define a case to sort "double" (aka floats)
             * you can't test for exact equality. you have to test that
             * two floats are close enough to be considered equal.
             * e.g, abs($x-$y) < $episilon where $epsilon = 0.000001
             */
            case "boolean":
            case "integer":
            case "NULL":
                if ($a == $b) {
                    return 0;
                }
                return ($a < $b) ? -1 : 1;
                break;
            case "string":
                return strcmp($a->sort_value, $b->sort_value);
                break;
            default:
                return 0;
                break;
        }
    }

    /**
     * Recursion for rendering a heirarichal list of mixed categories/resources
     *
     * @param objects[] array starting with categories as the root
     * @param string $section_furl
     * @param boolean $show_resources
     * @return string
     */
    public static function recurse_categorical_display($root_categories, $section_furl, $show_resources = TRUE)
    {
        // If we arent showing resources, we assume this list to be nestable. Classify accordingly.
        if (!$show_resources)
        {
            $ol_class = 'dd-list';
            $li_class ='dd-item';
            $content_class ='dd-content resource';
            $handle = '<div class="dd-handle">Drag</div>';
        }
        else
        {
            $li_class = 'item-container rounded';
            $content_class = 'category';
        }

        $output = '<ol class="'.@$ol_class.'">';

        foreach ($root_categories as $type => $category)
        {
            // If we are on a category object
            if (is_object($category))
            {
                if (!empty($category->name))
                {

                    $output .= '<li class="'.$li_class.'" data-type="category" data-id="'.$category->id.'">';

                        $output .= @$handle;

                        $output .= '<div class="'.$content_class.'">
                            '.$category->name.'
                            <span class="right">
                                '.HTML::make_icon('admin/categories/'.$category->id.'/edit/', 'edit')
                                .HTML::make_icon('admin/categories/'.$category->id, 'delete').'
                            </span>
                        </div>';

                        // Recurse through nested categories
                        $nested_categories = $category->categories;
                        if (!empty($nested_categories))
                        {
                            $output .= self::recurse_categorical_display($nested_categories, $section_furl, $show_resources);
                        }

                        // Render the resources in this depth
                        if ($show_resources)
                        {
                            $nested_resources = $category->nested_resources;

                            if (!empty($nested_resources))
                            {
                                $output .= '<ol>';
                                foreach ($nested_resources as $resource)
                                {
                                    $resource->display = self::build_resource_display($resource, 'current_content');

                                    if (is_null($resource->alias))
                                    {
                                        $resource_links = '';
                                    }

                                    $resource->actions = self::resource_actions($section_furl, $resource);

                                    $output .= '<li class="item rounded" data-type="resource" data-id="'.$resource->id.'">
                                        <div class="resource">
                                            <span class="edit-btn right">
                                                '.$resource->actions.'
                                            </span>
                                            '.$resource->display.'
                                        </div>
                                    </li>';
                                }
                                $output .= '</ol>';
                            }
                        }
                    $output .= '</li>';
                }
            }
        }
        $output .= '</ol>';

        return $output;
    }

    /**
     * Recursion for rendering a heirarichal list of mixed categories/resources
     *
     * @param objects[] array starting with categories as the root
     * @param string $section_furl
     * @return string
     */
    public static function recurse_sortable_display($resources, $section_furl)
    {
        $char_length = 30;

        $output = '<ol class="dd-list">';
        foreach ($resources as $resource)
        {
            $resource->display = self::build_resource_display($resource, 'current_content');

            $resource->actions = self::resource_actions($section_furl, $resource);

            $output .= '<li class="dd-item" data-id="'.$resource->id.'">
                <div class="dd-handle">Drag</div>
                <div class="dd-content resource">
                    <span class="right">
                        '.$resource->actions.'
                    </span>
                    '.Str::limit($resource->display, $char_length).'
                </div>
            ';

            // Recurse through nested resources
            if (!empty($resource->resources))
            {
                $output .= self::recurse_sortable_display($resource->resources, $section_furl);
            }
        }
        $output .= '</li>
        </ol>';

        return $output;
    }

    /**
     * Get all of this resource type.
     * If we are on the admin side, we return everything.
     * Otherwise, just the currently published items
     *
     * @param string $model name of the model to get the resources from
     * @return array|boolean
     */
    public static function get_resources_grid($model, $parent_id = NULL)
    {
        // Make sure the model class passed in exists
        if (!class_exists($model))
        {
            Log::error('redacted::get_resources_grid() - The class provided for the $model in this function does not exist.');
            return false;
        }

        if (!User::is_admin())
        {
            $allow_section = FALSE;

            $user_permissions = User::load_permissions();

            if (!empty($user_permissions[$model]))
            {
                $allow_section = TRUE;
                $allowable_resources_ids = array();

                foreach ($user_permissions[$model] as $permission)
                {
                    if (!is_null($permission->identifying_column) && $permission->identifying_column == 'id')
                    {
                        $allowable_resources_ids[] = (int) $permission->identifying_value;
                    }
                }

                $allowable_resources_ids = array_unique($allowable_resources_ids);
            }

            if (!$allow_section)
            {
                return Response::error('404');
            }
        }

        // Section
        $section = RrSection::where_name(Str::plural(Str::lower(str_replace('Resource_Structure_', '', $model))))->first();

        $resources = $model::with(array('current_content', 'categories' => function($query) use ($model)
        {
            $query->where('category_resource.ref_model', '=', $model);
        }));

        $filter_published = Session::get($model.'_filter_published', '');
        if ($filter_published === '0')
        {
            $resources = $resources->where(function($query)
            {
                $query->where('is_published', '!=', '1')
                    ->where(function($query)
                        {
                            $query->where('published_at', '<', DB::raw('NOW()'))
                                ->or_where_null('published_at');
                        });
            });
        }
        elseif ($filter_published == 1)
        {
            $resources = $resources->where(function($query)
            {
                $query->where('is_published', '=', '1')
                    ->or_where('published_at', '>', DB::raw('NOW()'));
            });
        }

        if (is_null($parent_id))
        {
            $resources = $resources->where_null('parent_id');
        }
        else
        {
            $resources = $resources->where_parent_id($parent_id);
        }

        if (isset($allowable_resources_ids))
        {
            // Grab all the resources respective to the permissions matching the resources or their parents
            $resources = $resources->where(function($q) use ($allowable_resources_ids) {
                $q->where_in('id', $allowable_resources_ids)->or_where_in('parent_id', $allowable_resources_ids);
            });
        }

        $results = $resources
            ->order_by($model::$table.'.published_at', 'desc')
            ->paginate(PAGINATION_PER_PAGE);

        return $results;
    }

    /**
     * Save sort position of all id's in the element with the provided id
     * @todo Rewrite this so that it uses recursion and can handle any depth
     *
     * @param mixed $model the model Class or object for which we are updating the sort order
     * @param int $parent_id the model
     * @param string $element_id id of the element container the JSON array of the order
     * @param string $sort_column which column for this model holds the sorting information
     * @return boolean return false on error
     */
    // @todo right now i made a quick fix for this to work for sorting resources within categories
    //         the fix allows mixed types to be passed through the first arg, not ideal
    //         we should probably update other calls to pass objects too or figure out a more ideal solution
    public static function update_sort_order($model, $parent_id = NULL, $element_id = 'nestable_output', $sort_column = 'sort')
    {
        // Make sure the model class passed in exists
        if (is_string($model) && !class_exists($model))
        {
            Log::error('redacted::update_sort_order() - The class provided for the $model in this function does not exist.');
            return false;
        }

        if (!User::has_permission($model, 'edit_all', 'id', $parent_id))
        {
            return Response::error('404');
        }

        $column_array = DB::query('SHOW columns FROM '.$model::$table.' like \'parent_id\';');
        $has_parent_id_column = count($column_array) ? true : false;

        $sort_order_array = json_decode(Input::get($element_id));

        foreach ($sort_order_array as $order => $sort_obj)
        {
            $parent = @get_class($model) == 'Category' || get_parent_class($model) == 'Category'
                ? Category_Resource::where('structure_id', '=', $sort_obj->id)
                    ->where('ref_model', '=', $model->ref_model)
                    ->where('category_id', '=', URI::segment(3))    // We can do this b/c to get here we will always be on /admin/categories/:category_id
                    ->first()
                : $model::find($sort_obj->id);

            if (!is_null($parent))
            {
                $parent->$sort_column = $order + 1;

                // Only null out parent_id if it is a property of this $model
                // We don't have to worry about this else where b/c if we don't have a parent_id
                // isset($sort_obj->children) will never be true
                if ($has_parent_id_column || $model == 'Category')
                {
                    $parent->parent_id = $parent_id;
                }

                $parent->save();
            }

            if (isset($sort_obj->children))
            {
                foreach ($sort_obj->children as $child_order => $child_sort_obj)
                {
                    $child = $model::find($child_sort_obj->id);
                    if (!is_null($child))
                    {
                        $child->$sort_column = $child_order + 1;
                        $child->parent_id = $parent->id;
                        $child->save();
                    }

                    if (isset($child_sort_obj->children))
                    {
                        foreach ($child_sort_obj->children as $grandchild_order => $grandchild_sort_obj)
                        {
                            $grandchild = $model::find($grandchild_sort_obj->id);
                            if (!is_null($grandchild))
                            {
                                $grandchild->$sort_column = $grandchild_order + 1;
                                $grandchild->parent_id = $child->id;
                                $grandchild->save();
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Save a file to the local file system
     *
     * @param string $field_name
     * @param string $path
     * @return boolean|string returns false on failure. Otherwise, returns the path to the saved file.
     */
    public static function save_file($field_name, $path = 'content/upload/')
    {
        // Get image data from $_FILES
        $file_data = Input::file($field_name);

        // Construct a file name. Prepend the date so the file name will be unique
        $file_name = date('Y_m_d_H_i_s_').$file_data['name'];
        $file = '';

        // If the directory doesn't exist, attempt to create it.
        if (!is_dir($path))
        {
            if (!mkdir($path))
            {
                Log::error('redacted::save_file() - Failed to create the directory, "'.$path.'".');
                return false;
            }

            if (!chmod($path, 0755))
            {
                Log::error('redacted::save_file() - Failed to create the directory, "'.$path.'".');
                return false;
            }
        }
        // Move the uploaded file. This is really just a wrapper around the PHP function move_uploaded_file()
        if (Input::upload($field_name, $path, $file_name))
        {
            $file = str_replace('content/', '', $path).$file_name;

            if (USE_CDN)
            {
                $save_array = explode('content/', $path);
                self::save_image_to_cdn($save_array[1].$file_name, $path.$file_name);
            }
        }

        // Attempt to change the permissions of the file that we just saved
        chmod(path('public').$file, 0755);

        return $file;
    }

    public static function save_image_to_cdn($image_name, $file)
    {
        if (USE_CDN)
        {
            $cloudfiles = Ioc::resolve('cloudfiles');

            return $cloudfiles->get_container(CDN_UPLOAD_NAME)
                ->create_object($image_name)
                ->load_from_filename($file);
        }
    }

    /**
     * @todo Add documentation
     *
     * @return void
     */
    private function delete_crop_from_cdn($image_name)
    {
        if(USE_CDN)
        {
            $cloudfiles = Ioc::resolve('cloudfiles');
            $cloudfiles->get_container(CDN_UPLOAD_NAME)->delete_object($image_name);
        }
    }

    /**
     * @todo Add documentation
     *
     * @return void
     */
    private function rename_cdn_images($current_name = FALSE, $dest_obj_name = FALSE)
    {
        if(USE_CDN)
        {
            if ($current_name && $dest_obj_name)
            {
                $cloudfiles = Ioc::resolve('cloudfiles');
                $container = $cloudfiles->get_container(CDN_UPLOAD_NAME);

                $container->move_object_to($obj, $container_target, $dest_obj_name);
            }
        }
    }

    /**
     * Check to make sure the class/method/function exists (may include namespacing), if not we log it cuz something has gone wrong mmkay?
     *
     * @param string $class
     * @param string $method
     * @return boolean (also logs results upon failure)
     */
    public static function check_callable($call_str)
    {
        if (is_callable($call_str) || class_exists($call_str) || method_exists($call_str) || function_exists($call_str))
        {
            return true;
        }

        Log::error('Attempt to call: $call_str was unsuccessful. Check that the namespace, class, method(s) exist');

        return false;
    }

    /**
     * Has the given resource been published?
     * NOTE: This returns true if called from the admin side no matter what.
     *
     * @param object(mixed) $resource
     * @return boolean
     */
    public static function published($resource)
    {
        // If on the admin side or "is_active" is true, return true
        if (redacted::admin() <> '' || $resource->is_published)
        {
            return true;
        }

        // Does the published_at date check out?
        $start = !is_null($resource->published_at) && strtotime($resource->published_at) <= time();

        // Does the published_until date check out
        $end = is_null($resource) || time() <= strtotime($resource->published_until);

        if ($start && $end)
        {
            return true;
        }

        return false;
    }

    /**
     * Format the given date/time in the display format
     *
     * @param string $datetime
     * @param string $format
     * @return string
     */
    public static function display_datetime_format($datetime, $format = DATETIME_DISPLAY_FORMAT, $use_time_ago = USE_TIME_AGO)
    {
        $unix = strtotime($datetime);

        $diff = floor((time() - $unix) / 3600);

        $measurement = ($diff == 1)
            ? 'hour'
            : 'hours';

        return ($diff < 24 && $use_time_ago)
            ? $diff.' '.$measurement.' ago'
            : self::datetime_format($datetime, $format);
    }

    /**
     * Format the given date/time in the save format
     *
     * @param string $datetime
     * @param string $format
     * @return string
     */
    public static function save_datetime_format($datetime, $format = DATETIME_SAVE_FORMAT)
    {
        if ($datetime <> '')
        {
            return self::datetime_format($datetime, $format);
        }

        return null;
    }

    /**
     * Format the given date/time in the given format
     *
     * @param string $datetime
     * @param string $format
     * @return string
     */
    public static function datetime_format($datetime, $format)
    {
        if (is_null($datetime))
        {
            return '';
        }

        return date($format, strtotime($datetime));
    }

    /**
     * This expands upon the idea of Laravel's native sync() method
     * The difference is that this method allows you to sync multiple ids
     * into a pivot table but also pass along persistent variables to
     * each pivot row. i.e. user_ids, lang_keys, etc.
     *
     * @param object $object
     * @param string $ref_table
     * @param integer[] $ids
     * @param string[] $static_vals
     * @return void
     */
    public static function sync_with_static($object, $ref_table, $ids, $static_vals = array(), $poly_clauses = array())
    {
        $current_objects = (array) $object->{$ref_table}()->where(function($q) use ($poly_clauses) {
            foreach ($poly_clauses as $col => $val)
            {
                $q->where($col, '=', $val);
            }
        })->get();

        $current = array();

        foreach ($current_objects as $obj)
        {
            $current[] = $obj->id;
        }

        foreach ($ids as $id)
        {
            if (!in_array($id, $current))
            {
                $object->{$ref_table}()->where(function($q) use ($poly_clauses) {
                    foreach ($poly_clauses as $col => $val)
                    {
                        $q->where($col, '=', $val);
                    }
                })->attach($id, $static_vals);
            }
        }

        $detach = array_diff($current, $ids);

        if (count($detach) > 0)
        {
            $object->{$ref_table}()->where(function($q) use ($poly_clauses) {
                foreach ($poly_clauses as $col => $val)
                {
                    $q->where($col, '=', $val);
                }
            })->detach($detach);
        }
    }

    /**
     * Generate an array that is used to produce our "create button" drop down
     *
     * @param string $model_uri
     * @return string[]
     */
    public static function create_button($section, $resource_id = NULL, $include_buttons = array('New Entry', 'New Internal Link', 'New External Link', 'New Category'))
    {
        $has_permission = empty($resource_id)
            ? User::has_permission($section->structure_model(), 'create', NULL, NULL)
            : User::has_permission($section->structure_model(), 'create', 'id', $resource_id);

        if ($has_permission)
        {
            if (!empty($resource_id))
            {
                $resource_specific = $resource_id.'/';
            }

            $buttons['New Entry'] = array(
                'link'        => '#',
                'modal'       => true,
                'reveal_id'   => 'reveal-modal-new-resource',
                'view'        => View::make('admin.layouts.modal')
                    ->with('reveal_id', 'reveal-modal-new-resource')
                    ->with('src', '/admin/'.$section->furl.'/'.@$resource_specific.'new')
                    ->with('size', 'large')
            );

            if (User::is_admin())
            {
                $buttons['New Internal Link'] = array(
                    'link'        => '#',
                    'modal'       => true,
                    'reveal_id'   => 'reveal-modal-new-alias',
                    'view'        => View::make('admin.layouts.modal')
                        ->with('reveal_id', 'reveal-modal-new-alias')
                        ->with('src', '/admin/'.$section->furl.'/aliases/new')
                );
            }

            $buttons['New External Link'] = array(
                'link'        => '#',
                'modal'       => true,
                'reveal_id'   => 'reveal-modal-new-link',
                'view'        => View::make('admin.layouts.modal')
                    ->with('reveal_id', 'reveal-modal-new-link')
                    ->with('src', '/admin/'.$section->furl.'/links/new')
            );

            if (in_array($section->layout, array('categories', 'categories_grid', 'forum')))
            {
                $buttons['New Category']    = array(
                    'link'        => '#',
                    'modal'       => true,
                    'reveal_id'   => 'reveal-modal-new-category',
                    'view'        => View::make('admin.layouts.modal')
                        ->with('reveal_id', 'reveal-modal-new-category')
                        ->with('src', '/admin/'.$section->furl.'/categories/new')
                );
            }
        }

        // Exclude buttons
        if (!empty($buttons))
        {
            foreach ($buttons as $button_name => $button)
            {
                if (!in_array($button_name, $include_buttons))
                {
                    unset($buttons[$button_name]);
                }
            }
        }

        return @$buttons;
    }

    /**
     * Format the given string so that it is "human readable"
     *
     * @param string $value
     * @return string
     */
    public static function humanize($value)
    {
        $value = str_replace('_', ' ', $value);
        $value = ucwords($value);

        return $value;
    }

    /**
     * Get a list of editable columns for the given table.
     * Use the IoC so that we do not have to continuously run the same SQL statement
     *
     * @param string $table
     * @return string[]
     */
    public static function editable_columns($table)
    {
        $ioc_key = $table.'_editable_columns';
        if (!IoC::registered($ioc_key))
        {
            IoC::singleton($ioc_key, function() use ($table)
            {
                $exclude_columns = array('id', 'module_installation_id', 'sort', 'created_at', 'updated_at');
                $all_columns = DB::query('SHOW FULL COLUMNS FROM `'.$table.'`');
                $editable_columns = array();
                foreach ($all_columns as $column)
                {
                    if (!in_array($column->field, $exclude_columns))
                    {
                        array_push($editable_columns, $column);
                    }
                }

                return $editable_columns;
            });
        }

        return IoC::resolve($ioc_key);
    }

    /**
     * Generate an options array for use with a select dropdown
     *
     * @param string $model
     * @param boolean $select_one_option
     * @return string[]
     */
    public static function options($model, $select_one_option = true)
    {
        $options = array();
        if ($select_one_option)
        {
            $options[''] = '- Select One -';
        }

        foreach ($model::all() as $option)
        {
            // If the object has a __toString method, use it
            if (method_exists($model, '__toString'))
            {
                $options[$option->id] = strip_tags($option);
            }
            else
            {
                $options[$option->id] = strip_tags($option->name);
            }
        }

        return $options;
    }

    /**
     * @todo Add documentation
     *
     * @param string $section_furl
     * @param string $resource
     * @return string
     */
    public static function resource_actions($section_furl, $resource)
    {
        $output = '';

        if ($resource->alias_id)
        {
            // Grab the section
            $resource->alias->reference->section;

            // Construct the alias link
            $link = get_class($resource->alias->reference) == 'Category'
                ? 'categories/'.$resource->alias->reference->id.'/edit'
                : (
                    !empty($resource->alias->reference->section)
                        ? $resource->alias->reference->section->furl.'/'.$resource->alias->reference->id.'/edit' // Resource
                        : $resource->alias->reference->furl // Section
                );

            $output .= '<a href="/admin/'.$link.'">Alias</a>';
            $output .= HTML::make_icon('admin/'.$section_furl.'/'.$resource->id.'/edit', 'edit');
        }
        elseif ($resource->link_id)
        {
            $resource->external_link;

            $output .= '<a href="'.$resource->external_link->link.'">External Link</a>';
            $output .= HTML::make_icon('admin/'.$section_furl.'/'.$resource->id.'/edit', 'edit');
        }
        else
        {
            $output .= HTML::make_icon('admin/'.$section_furl.'/'.$resource->id.'/current/edit', 'edit');
        }

        // Check for user permission
        $section = $resource->section;

        if (!$permissions['delete'] = User::has_permission($section->structure_model(), 'delete_all', 'id', $resource->id))
        {
            if ($is_creator = $resource->creator_id == User::current_user_id())
            {
                $permissions['delete'] = User::has_permission($section->structure_model(), 'delete_own', 'id', $resource->id);
            }
        }

        if ($resource->num_entries <= 1 && $permissions['delete'])
        {
            $output .= HTML::make_icon('admin/'.$section_furl.'/'.$resource->id, 'delete');
        }

        return $output;
    }

    /**
     * @todo add documentation
     *
     * @param RrSection|Category $section_or_category_object
     * @return void
     */
    public static function create_public_view($section_or_category_object)
    {
        if (is_array($section_or_category_object) || is_object($section_or_category_object))
        {
            $result = array();
            foreach ($section_or_category_object as $key => $value)
            {
                $result[$key] = $value;
            }
        }

        $pretty_name = Str::lower(str_replace(array(' ', '-'),'_',$section_or_category_object->name));

        $content = self::generate_public_view_contents($result);

        $file_name = $pretty_name.'.blade.html';
        $app_path = path('app');
        $view_path = $app_path.'views/'.$pretty_name;
        $full_path_to_file = $view_path.'/'.$file_name;
        if (!is_dir($view_path))
        {
            if (!mkdir($view_path))
            {
                echo 'Failed while trying to create the directory: '.$view_path.PHP_EOL;
                echo 'Please check your file system permissions.'.PHP_EOL;
            }
            else
            {
                if (file_exists($full_path_to_file))
                {
                    echo 'view already exists'.PHP_EOL;
                }
                else
                {
                    echo 'Generating file '.$full_path_to_file.PHP_EOL;
                    File::put($full_path_to_file, $content);
                }
            }
        }
    }

    /**
     * @todo add documentation
     *
     * @param string[] $data
     * @return string
     */
    private static function generate_public_view_contents($data)
    {
        return '@layout(\'layouts.master\')

@section(\'content\')
    <div>
        <pre>
            {{'.print_r($data, true).'}}
        </pre>
    </div>
@endsection
'.PHP_EOL;
    }

    /**
     * @todo add documentation
     *
     * @param RrSection $section
     * @return boolean
     */
    public static function create_public_controller($section)
    {
        // Create a base class controller for front end using this content
        $file_contents = self::generate_public_controller_contents($section->furl);

        $pretty_name = str_replace(' ', '_', $section->name);
        $pretty_name = str_replace('-', '_', $pretty_name);
        $pretty_name = strtolower($pretty_name);
        $file_name = $pretty_name.'.php';
        $file_path = path('app').'controllers';
        $full_path_to_file= $file_path.'/'.$file_name;

        if (file_exists($full_path_to_file))
        {
            echo 'Controller already exists'.PHP_EOL;
        }
        else
        {
            echo 'Generating file '.$full_path_to_file.PHP_EOL;
            File::put($full_path_to_file, $file_contents);

            return true;
        }

        return false;
    }

    /**
     * @todo add documentation
     *
     * @param string $furl
     * @return string
     */
    private static function generate_public_controller_contents($furl)
    {
        return '<?php
        /**
         *
         * @author rrd-framework
         */

        class '.ucfirst($furl).' extends Base_Resources_Controller
        {
            public $section_furl = \''.$furl.'\';

            public $restfull = true;

            public $layout = \'admin.layouts.master\';

            /**
             * Constructor
             */
            public function __construct()
            {
                // Call the parent constructor
                parent::__construct();
            }

        }
        '.PHP_EOL;
    }

    /**
     * Generates the display text to be used for any given resource
     * Typically used for links
     *
     * @param string $resource
     * @return string
     */
    public static function build_resource_display($resource)
    {
        return !empty($resource->alias_id)
            ? (
                !empty($resource->alias->display)
                    ? $resource->alias->display
                    : (
                        get_class($resource->alias->reference) == 'RrSection'
                            ? $resource->alias->reference->humanized
                            : (
                               $resource->alias->reference->link_id
                                   ? $resource->alias->reference->external_link->display
                                   : $resource->alias->reference->current_content->name
                            )
                    )
            )
            : (
                !empty($resource->link_id)
                    ? $resource->external_link->display
                    : $resource
            );
    }

    /**
     * Generates attributes for the object passed (by reference)
     * ->path an path array based on the current resource passed and its heirarchy
     * ->furl imploded by DS from ->path
     * ->target the link's target if it exists
     * Handles external links and alias references if encountered.
     *
     * @param object $origin_obj usually a resource
     * @return object $ref_obj a referenced object to process recursively
     */
    public static function build_resource_link_data($origin_obj, $ref_obj = false)
    {
        // If the origin_obj is a content entry,
        // we grab its resource structure to adhere to formats for this method
        // For example: This is found in search results where searching through
        // published content grabs content entries and then determines which resource it bleongs to after the fact
        if ($origin_obj->structure)
        {
            $origin_obj = $origin_obj->structure;
        }

        $curr_obj = $ref_obj ?: $origin_obj;

        $path_arr = array();
        $humanized_arr = array();

        if ($curr_obj->alias_id) // Is an alias
        {
            $curr_obj->alias->reference; // Grab the reference object

            if (preg_match('/RrSection/', get_class($curr_obj->alias->reference))) // Alias references a section
            {
                array_unshift($path_arr, $curr_obj->alias->reference->furl);
                array_unshift($humanized_arr, redacted::build_resource_display($curr_obj));
                $origin_obj->furl = '/'.$curr_obj->alias->reference->furl;
            }
            elseif (preg_match('/Resource_Structure_/', get_class($curr_obj->alias->reference))) // Alias references a resource
            {
                // Do some recursion. Helps with chained aliases and avoids redundant logic.
                return self::build_resource_link_data($origin_obj, $curr_obj->alias->reference);
            }
        }
        elseif ($curr_obj->link_id) // Is an external link
        {
            array_unshift($path_arr, $curr_obj->external_link->link);
            array_unshift($humanized_arr, $curr_obj->external_link->display);
            $origin_obj->furl = $curr_obj->external_link->link;
            $origin_obj->target = $curr_obj->external_link->target;

            return $origin_obj;
        }
        else
        {
            // Build a path by backtracking to the highest level section
            array_unshift($path_arr, $curr_obj->furl);
            array_unshift($humanized_arr, redacted::build_resource_display($curr_obj));

            $origin_obj->target = $curr_obj->target;
        }

        if ($curr_obj->categories)
        {
            $current_category = $curr_obj->categories[0];
            while ($current_category)
            {
                array_unshift($path_arr, $current_category->furl);
                array_unshift($humanized_arr, $current_category);
                $current_category = $current_category->parent;
            }
        }

        if ($curr_obj->parent)
        {
            $current_parent = $curr_obj->parent;
            while ($current_parent)
            {
                array_unshift($path_arr, $current_parent->furl);
                array_unshift($humanized_arr, $current_parent);
                $current_parent = $current_parent->parent;
            }
        }

        $curr_obj_parent_section = $curr_obj->section; // Grab the section

        if ($curr_obj_parent_section)
        {
            array_unshift($path_arr, $curr_obj_parent_section->furl);
            array_unshift($humanized_arr, redacted::build_resource_display($curr_obj_parent_section));

            $section_iterator = $curr_obj_parent_section;
        }
        else
        {
            // curr_obj must be a section
            $section_iterator = $curr_obj;
        }

        while ($section_iterator->parent)
        {
            array_unshift($path_arr, $section_iterator->parent->furl);
            array_unshift($humanized_arr, redacted::build_resource_display($section_iterator->parent));
            $section_iterator = $section_iterator->parent; // Grab the section
        }

        if (!empty($curr_obj->section))
        {
            // Check for exclusions, omit them if found
            if (in_array($curr_obj->section->name, Config::get('application.exclusion_section_furls')))
            {
                //Unset this item from the path attribute if found in the generated path
                if (($exclusion_key = array_search($curr_obj->section->furl, $path_arr)) !== FALSE)
                {
                    unset($path_arr[$exclusion_key]);
                    unset($humanized_arr[$exclusion_key]);
                }
            }
        }

        $origin_obj->furl = '/'.implode('/', $path_arr);

        $origin_obj->path = $path_arr;

        $origin_obj->breadcrumbs = $humanized_arr;

        return $origin_obj;
    }

    /**
     * Delete a cookie
     *
     * @param string $name
     * @return void
     */
    public static function cookie_delete($name, $domain = null, $path = '/')
    {
        if (!isset($domain))
        {
            $domain = URL::base();
        }

        $cookie = array(
            'name'      => $name,
            'value'     => null,
            'expire'    => '-3600',
            'path'      => $path,
            'domain'    => $domain
        );

        setcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain']);
    }

    /**
     * Set a cookie
     *
     * @param string $name
     * @param string|boolean $value
     * @return void
     */
    public static function cookie_set($name, $value = false, $expire = null, $domain = null, $path = '/')
    {
        if (!isset($domain))
        {
            $domain = URL::base();
        }

        if (!isset($expire) || empty($expire))
        {
            $expire = time() + 60 * 60 * 24 *2;
        }

        if ($value)
        {
            self::cookie_delete($name);

            $cookie = array(
                'name'      => $name,
                'value'     => $value,
                'expire'    => $expire,
                'path'      => $path,
                'domain'    => $domain
            );

            setcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain']);
        }
    }

    /**
     * Converts numbers (int) to their english words
     *
     * @param int $number
     * @return string
     */
    public static function numbers_to_words($number)
    {
        if (!is_int($number) && $number > 100 )
        {
            throw new Exception("$number must be an integer < 100", 1);
        }

        $result = array();
        $tens = floor($number / 10);
        $units = $number % 10;

        $words = array
        (
            'units' => array('', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eightteen', 'Nineteen'),
            'tens' => array('', '', 'Twenty', 'Thirty', 'Fourty', 'Fifty', 'Sixty', 'Seventy', 'Eigthy', 'Ninety')
        );

        if ($tens < 2)
        {
            $result[] = $words['units'][$tens * 10 + $units];
        }

        else
        {
            $result[] = $words['tens'][$tens];

            if ($units > 0)
            {
                $result[count($result) - 1] .= '-'.$words['units'][$units];
            }
        }

        if (empty($result[0]))
        {
            $result[0] = 'Zero';
        }

        return trim(implode(' ', $result));
    }

    /**
     * Get paginated resources for this $model
     *
     * @param string $model name of the model to get the resources from
     * @return array|boolean
     */
    public static function get_resources_categories_grid($model)
    {
        // Make sure the model class passed in exists
        if (!class_exists($model))
        {
            Log::error('redacted::get_resources_categories_grid() - The class provided for the $model in this function does not exist.');
            return false;
        }

        // Section
        $section = RrSection::where_name(Str::plural(Str::lower(str_replace('Resource_Structure_', '', $model))))->first();

        $page = Input::get('page', 1);
        $skip = ($page - 1) * PAGINATION_PER_PAGE;

        $content_model = $model::$content_model;
        $structure_table = $model::$table;
        $join_table = $content_model::$table;

        // We do this because when joining the category_resource table, structure.id
        // gets overwritten by category_resource.id
        // These should be all of the universal fields for structure objects
        $select_as = array(
            $structure_table.'.id AS id',
            $structure_table.'.parent_id AS parent_id',
            $structure_table.'.section_id AS section_id',
            $structure_table.'.creator_id AS creator_id',
            $structure_table.'.furl AS furl',
            $structure_table.'.alias_id AS alias_id',
            $structure_table.'.sort AS sort',
            $structure_table.'.link_id AS link_id',
            $structure_table.'.created_at AS created_at',
            $structure_table.'.updated_at AS updated_at',
            $structure_table.'.lang_key AS lang_key',
            $structure_table.'.is_published AS is_published',
            $structure_table.'.published_at AS published_at',
        );

        $resources = $model::with(array('categories' => function($query) use ($model)
        {
            $query->where('category_resource.ref_model', '=', $model);
        }));

        $filter_category = Session::get($model.'_filter_category', '');
        if (!empty($filter_category))
        {
            $resources = $resources->join('category_resource', 'category_resource.structure_id', '=', $structure_table.'.id')
                ->where('category_resource.category_id', '=', $filter_category);
        }

        $filter_published = Session::get($model.'_filter_published', '');
        if ($filter_published === '0')
        {
            $resources = $resources->where(function($query)
            {
                $query->where('is_published', '!=', '1')
                    ->where(function($query)
                        {
                            $query->where('published_at', '<', DB::raw('NOW()'))
                                ->or_where_null('published_at');
                        });
            });
        }
        elseif ($filter_published == 1)
        {
            $resources = $resources->where(function($query)
            {
                $query->where('is_published', '=', '1')
                    ->or_where('published_at', '>', DB::raw('NOW()'));
            });
        }

        $results = $resources->order_by($structure_table.'.published_at', 'desc')
            ->skip($skip)
            ->take(PAGINATION_PER_PAGE)
            ->get($select_as);

        $return = Paginator::make($results, $resources->count(), PAGINATION_PER_PAGE);

        return $return;
    }

    /**
     * Produce a CSV for the given array and the given field
     *
     * @param string[] $array
     * @param string $field
     * @return string
     */
    public static function print_array_property($array, $field = 'name', $delimiter = ', ')
    {
        $return_array = array();

        foreach ($array as $i => $item)
        {
            array_push($return_array, $item->name);
        }

        return implode($delimiter, $return_array);
    }

    /**
     * Produce a CSV for the given array and use the given method to print each
     *
     * @param string[] $array
     * @param string $method
     * @return string
     */
    public static function print_collection_via_function($array, $method, $delimiter = ', ')
    {
        $return_array = array();

        foreach ($array as $i => $item)
        {
            if (!method_exists($item, $method))
            {
                die('Attempting to call an unknow method for class '.gettype($this));
            }

            array_push($return_array, $item->$method());
        }

        return implode($delimiter, $return_array);
    }

    /**
     * Get the YouTube ID from the given url
     *
     * @todo use parse_url() & parse_str() to replace most of this code
     * @param string $url
     * @return string
     */
    public static function parse_youtube_url($url)
    {
        // If it's not a url, just return what was given
        if(!filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_QUERY_REQUIRED))
        {
            return $url;
        }

        $parsed_url = parse_url($url);
        $query = $parsed_url['query'];
        $path = $parsed_url['path'];
        $path_parts = explode('/', trim($path, '/'));
        parse_str($query, $query_parts);

        $v = isset($query_parts['v']) ? $query_parts['v'] : '';

        // Couldn't find out youtube_id in the query string, lets try to find it in the URI
        $next = false;
        while (empty($v))
        {
            $piece = array_shift($path_parts);

            if ($next)
            {
                $v = $piece;
            }
            elseif ($piece == 'v')
            {
                $next = true;
            }
        }

        return $v;
    }

    /**
     * Return a valid youtube.com video url
     *
     * @param string $youtube_id
     * @return string
     */
    public static function display_youtube_url($youtube_id)
    {
        return empty($youtube_id) ? '' : 'http://youtube.com/watch?v='.$youtube_id;
    }


    /**
     * Get the YouTube thumbnail from the given YouTube ID
     *
     * @param string $youtube_id
     * @param integer $size_int largest to smallest 0 - 4
     * @return string
     */
    public static function get_youtube_thumbnail($youtube_id, $size_int = 0)
    {
        return 'http://img.youtube.com/vi/'.$youtube_id.'/'.$size_int.'.jpg';
    }

    /**
     * Display an image. If it doesn't exist, display a place holder
     * Note: If you don't pass a width and height it will go to the fallback
     * Note: Default fall back is 'placeholder' & the only other option is "original"
     *
     * @param string $image_path
     * @param null|integer $width
     * @param null|integer $height
     * @param string $fallback
     * @return string
     */
    public static function display_image($image_path, $width = null, $height = null, $fallback = null)
    {
        $image = '';
        $cropped_name = str_replace('original', 'cropped/'.$width.'_'.$height, $image_path);

        if (is_file(path('public').$cropped_name) && !is_null($width) && !is_null($height))
        {
            if(USE_CDN)
            {
                $image = CROPPED_CDN_IMAGE.$cropped_name;
            }
            if(!self::remote_file_exists($image))
            {
                $image = '/'.$cropped_name;
            }
        }
        elseif ($fallback == 'original' || is_null($width) || is_null($height))
        {
            if(USE_CDN)
            {
                $image = ORIGINAL_CDN_IMAGE.$image_path;
            }
            if(!self::remote_file_exists($image))
            {
                $image = '/'.$image_path;
            }
        }
        else
        {
            $image = '/placeholder/'.$width.'x'.$height;
        }

        return $image;
    }

    /**
     * @todo add documentation
     */
    public static function remote_file_exists($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        // don't download content
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if(curl_exec($ch)!==FALSE)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Wrap all registration and trademarks in a sup tag
     *
     * @param string $str
     * @return string
     */
    public static function sup_marks($str)
    {
        $sup_string = preg_replace(array('/(™)/','/(&trade;)/','/(®)/', '/&reg;/'), '<sup>$1</sup>', $str);

        return $sup_string;
    }
}



