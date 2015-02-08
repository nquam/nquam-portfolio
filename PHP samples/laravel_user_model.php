<?php
/**
 * User Model
 * Notes:
 * - Return sentry errors as 'sentry' because it can conflict with the $error for validation.
 * 		This way we also know the error is from sentry making an attempt at something
 * - This model extends App_User which is used for custom definitions specific to the application. App_User extends Aware.
 *
 * @author Nathan Quam
 * @author redacted
 * @author redacted
 * @since October 3, 2012
 */
class User extends App_User
{
	/**
     * Name of the DB table associated with this model
     */
	public static $table = 'users';

	/**
     * Are standard timestamps(created_at & updated_at) used for this model/table?
     */
	public static $timestamps = true;

	/**
	 * Ignore these. We redefine the rules for POST & PUT in their respective method
	 * because they are not the same due to the password field being required for one
	 * but not the other.
	 */
	public static $rules = array();

	/**
     * What should be displayed if an object of this class is used as a string
     *
     * @return string
     */
    public function __toString()
    {
    	$return = '';
    	if (!is_null($this->metadata))
    	{
    		$return = $this->metadata->first_name.' '.$this->metadata->last_name;
    	}

        return $return;
    }

	/**
	 * Used to describe the relationship between User & User_Metadata
	 *
	 * @return relationship
	 */
	public function metadata()
	{
		return $this->has_one('User_Metadata');
	}

	/**
	 * Used to describe the relationship between Users & Groups
	 */
	public function groups()
	{
		return $this->has_many_and_belongs_to('Group', 'users_groups');
	}

	/**
	 * Used to describe the relationship between Users & Permissions
	 *
	 * @return relationship
	 */
	public function permissions()
	{
		return $this->has_many_and_belongs_to('Permission', 'user_permissions');
	}

	/**
	 * Used to describe the relationship between a User & Comments
	 *
	 * @return relationship
	 */
	public function comments()
	{
		return $this->has_many('Comment');
	}

    /**
     * Generate an anonymous user
     *
     * @return User
     */
    public static function new_anonymous_user()
    {
        $user = new User();
        $user->metadata = new User_Metadata;
        $user->metadata->first_name = ANON_USER_FIRST_NAME;
        $user->metadata->avatar = DEFAULT_AVATAR_IMAGE;

        return $user;
    }

	/**
	 * Return the currently logged in users id
	 *
	 * @return integer
	 */
	public static function current_user_id()
	{
		return Session::has(Config::get('sentry::sentry.session.user'))
			? Session::get(Config::get('sentry::sentry.session.user'))
			: 0;
	}

	/**
	 * Return the currently logged in user
	 *
	 * @return User
	 */
	public static function current_user()
	{
		$current_user_id = User::current_user_id();
		if (is_null($current_user_id))
		{
			return null;
		}

		if (!IoC::registered('current_user'))
		{
			IoC::singleton('current_user', function() use ($current_user_id)
			{
				$u = User::find($current_user_id);
				return $u;
			});
		}

		return IoC::resolve('current_user');
	}

	/**
	 * Recursively determine all (unique) permissions through the inheritance chain
	 *
	 * @return array of permissions
	 */
	public static function inherited_permissions($permissions, $final_permissions = array())
	{
		if (!is_array($permissions))
		{
			$permissions = array($permissions);
		}

		foreach ($permissions as $permission)
		{
			$final_permissions[$permission->ref_model][$permission->id] = $permission;

			if ($inheritance = $permission->inherited)
			{
				$final_permissions = User::inherited_permissions($inheritance, $final_permissions);
			}
		}

		return $final_permissions;
	}

	/**
	 * @todo add documentation
	 */
	public static function load_permissions($user_id = null)
	{
		// If $user_id is null test with the currently logged in user
		if (is_null($user_id) || $user_id == User::current_user_id())
		{
			$user = User::current_user();
		}
		else
		{
			$user = User::find($user_id);
		}

		if (!IoC::registered('permissions'))
		{
			IoC::singleton('permissions', function() use ($user)
			{
				// Get user permissions
				$user_permissions = $user->permissions()->get();

				// Get group permissions
				$user_groups = $user->groups()->get();

				$group_permissions = array();
				foreach ($user_groups as $user_group)
				{
					if (!empty($user_group->permissions))
					{
						$group_permissions[] = $user_group->permissions()->get();
					}
				}

				$combined_permissions = array_merge($user_permissions, $group_permissions);

				$permissions = User::inherited_permissions($combined_permissions);

				return $permissions;
			});
		}

		return IoC::resolve('permissions');
	}

	/**
	 * Does the user have permission to for this action?
	 *
	 * @param string $model
	 * @param string $action
 	 * @param integer $user_id
	 * @return boolean
	 */
	public static function has_permission($ref_model, $action, $identifying_column = FALSE, $identifying_value = FALSE, $user_id = NULL)
	{
		// If $user_id is null test with the currently logged in user
		if (is_null($user_id) || $user_id == User::current_user_id())
		{
			$user = User::current_user();
		}
		else
		{
			$user = User::find($user_id);
		}

		// If we couldn't find the user return false
		if (!($user instanceof User))
		{
			return FALSE;
		}

		// If the user belongs to the group "Admin", return true
		if (User::is_admin())
		{
			return TRUE;
		}

		// If the user has permission, return true
		$permissions = User::load_permissions();

		foreach ($permissions as $model => $model_permissions)
		{
			foreach ($model_permissions as $permission_id => $permission)
			{
				if ($ref_model == $model && $permission->action == $action)
				{
					// If FALSE is provided, it means we don't care about granularity
					if ($identifying_column === FALSE && $identifying_value === FALSE)
					{
						return TRUE;
					}

					// If null was requested, we explicitly look for entries that have null
					if (is_null($permission->identifying_column) && is_null($permission->identifying_value))
					{
						return TRUE;
					}
					else
					{
						if ($permission->identifying_column == $identifying_column && $permission->identifying_value == $identifying_value)
						{
							return TRUE;
						}
					}
				}
			}
		}

		return FALSE;
	}

	/**
	 * Save information from the edit page to the DB
	 *
	 * @return boolean was creation successful
	 */
	public function create_user_and_metadata($email = null, $password = null, $first_name = null, $last_name = null)
	{
		$this->email = is_null($email) ? Input::get('email') : $email;
		$this->password = is_null($password) ? Input::get('password') : $password;
		$this->password_confirmation = is_null($password) ? Input::get('password_confirmation') : $password;

		$metadata = new User_Metadata();
		$metadata->first_name = is_null($first_name) ? Input::get('first_name') : $first_name;
		$metadata->last_name = is_null($last_name) ? Input::get('last_name') : $last_name;

		$rules = array(
			'email'			=> 'required|email',
			'password'		=> 'required|confirmed|min:'.PASSWORD_MIN_LENGTH
		);

		$user_valid = $this->valid($rules);
		$metadata_valid = $metadata->valid();

		if ($user_valid === false || $metadata_valid === false)
		{
			$this->errors->messages = array_merge($this->errors->messages, $metadata->errors->messages);

			return false;
		}
		else
		{
			// Use Sentry to create the user so that our password is hashed properly
			$user_id = Sentry::user()->create(array(
				'email'		=> $this->email,
				'password'	=> $this->password,
				'metadata'	=> array(
					'first_name'	=> $metadata->first_name,
					'last_name'		=> $metadata->last_name
				)
			));

			$this->user_id = $user_id;

			return true;
		}
	}

	/**
	 * Save information from the edit page to the DB
	 *
	 * @return boolean was saving successful
	 */
	public function save_user_and_metadata()
	{
		$data = new stdClass;
		$data->email = $this->email = Input::get('email');
		$this->metadata->first_name = Input::get('first_name');
		$this->metadata->last_name = Input::get('last_name');

		$rules = array(
			'email'			=> 'required|email',
		);

		if (Input::has('password'))
		{
			$data->password = $this->password = Input::get('password');
			$this->password_confirmation = Input::get('password_confirmation');

			$rules['password']	= 'confirmed|min:'.PASSWORD_MIN_LENGTH;
		}

		$user_valid = $this->valid($rules);
		unset($this->password_confirmation); // Unset this so it doesnt fail our save.
		$metadata_valid = $this->metadata->valid();

		if ($user_valid === false || $metadata_valid === false)
		{
			$this->errors->messages = array_merge($this->errors->messages, $this->metadata->errors->messages);

			return false;
		}
		else
		{
			// Save the User object
			//$this->save();
			// @todo Figure this out!
			// For some reason $this->save() is attempting an insert rather than an UPDATE
			// To fix the situation, we run a fluent query instead
			DB::table('users')->where_id($this->id)->update((array) $data);

			// If the password is set, save it
			if (!empty($this->password))
			{
				// We use a Sentry user so that it will hash the password for us.
				$sentry_user = Sentry::user((int)$this->id);
				$sentry_user->update(array('password' => $this->password));
			}

			// Save the users Metadata object
			$this->metadata->save();

			// Get all groups & generate an array of group id's that were checked
			$groups = Group::all();
			$checked_arr = array();
			foreach ($groups as $group)
			{
				$checked = (int)Input::get('group_'.$group->id);
				if ($checked)
				{
					array_push($checked_arr, $group->id);
				}
			}
			$this->groups()->sync($checked_arr);

			// Get all permissions & generate an array of permission id's that were checked
			$permissions = Input::get('permissions');
			$checked_arr = array();
			foreach ($permissions as $permission_id => $val)
			{
				if ((int) $val)
				{
					array_push($checked_arr, $permission_id);
				}
			}
			$this->permissions()->sync($checked_arr);

			return true;
		}
	}

	/**
	 * Save information from the edit page to the DB
	 *
	 * @return boolean was saving successful
	 */
	public function save_user_profile()
	{
		$this->email = Input::get('email');
		$this->company = Input::get('company');
		$this->location = Input::get('location');
		$this->url = Input::get('url');
		$this->bio = Input::get('bio');

		$rules = array(
			'email'			=> 'required|email|unique:users,email,'.$this->id,
		);

		if (Input::has('password'))
		{
			$this->password = Input::get('password');
			$this->password_confirmation = Input::get('password_confirmation');

			$rules['password']	= 'confirmed|min:'.PASSWORD_MIN_LENGTH;
		}

		$user_valid = $this->valid($rules);
		unset($this->password_confirmation); // Unset this so it doesnt fail our save.

		$this->metadata->first_name = Input::get('first_name');
		$this->metadata->last_name = Input::get('last_name');
		if (Input::has('avatar'))
		{
			$this->metadata->avatar = Redacted::save_file('avatar');
		}
		$metadata_valid = $this->metadata->valid();

		if ($user_valid === false || $metadata_valid === false)
		{
			$this->errors->messages = array_merge($this->errors->messages, $this->metadata->errors->messages);

			return false;
		}
		else
		{
			$data = array(
				'email'		=> $this->email,
				'company'	=> $this->company,
				'location'	=> $this->location,
				'url'		=> $this->url,
				'bio'		=> $this->bio
			);
			// Save the User object
			// To fix the situation, we run a fluent query instead
			DB::table('users')->where_id($this->id)->update($data);

			// If the password is set, save it
			if (!empty($this->password))
			{
				// We use a Sentry user so that it will hash the password for us.
				$sentry_user = Sentry::user((int)$this->id);
				$sentry_user->update(array('password' => $this->password));
			}

			// Save the users Metadata object
			$this->metadata->save();

			return true;
		}
	}

	/**
	 * Try to log the user in and return errors
	 *
	 * @return array:mixed
	 */
	public static function try_login()
	{
		$username = Input::get('username');
		$password = Input::get('password');
		$remember = Input::get('remember');
		$login_user = self::where('email', '=', $username)->first();

		try
    	{
	    	$valid_login = Sentry::login($username, $password, $remember);
		    if ($valid_login)
		    {
		        // the user is now logged in - do your own logic
		        return $data = array('success' => TRUE);
		    }
		    else
		    {
				if (self::try_old_login($login_user, $password))
				{
					// Login the user
					User::force_login($username);
					return $data = array('success' => TRUE);
				}
				else
				{
			        // could not log the user in - do your bad login logic
			        return $data = array('success' => FALSE, 'sentry' => 'Username/Password incorrect');
			    }
		    }
		}
		catch (Sentry\SentryException $e)
		{
		    // issue logging in via Sentry - lets catch the sentry error thrown
		    // store/set and display caught exceptions such as a suspended user with limit attempts feature.
		    $errors = $e->getMessage();
	        return $data = array('success' => FALSE, 'sentry' => $errors);
		}
	}

	/**
	 * Try to log the user in with old system information
	 * If they are successful, update them to a new password hash
	 *
	 * @return array:mixed
	 */
	public static function try_old_login($login_user, $password)
	{
		$stored_hash = $login_user->old_site_password_hash;

		if (!empty($stored_hash) && !is_null($login_user))
		{
			$hash_encoded_with_old_method = self::_old_system_encode($password);
			if (crypt($hash_encoded_with_old_method, $stored_hash) === $stored_hash)
			{
				// The password given is a match for the given email and an old system hash
				// Let's update the hash for our new system and we should never have to come back down here.
				$hash_strategy = new Sentry\Sentry_Hash_Strategy_BCrypt(array('hashing_algorithm' => null));
				$new_hash = $hash_strategy->create_password($password);
				DB::table('users')->where('id', '=', $login_user->id)
					->update(array(
						'password'					=> $new_hash,
						'password_reset_hash'		=> null,
						'old_site_password_hash'	=> null,
						'updated_at'				=> DB::raw('NOW()')
					)
				);

				return true;
			}
		}

		return false;
	}

	/**
	 * Try to register the user and return errors
	 *
	 * @param array:mixed
	 * @param boolean
	 * @return array:mixed
	 */
	public static function try_registration($vars, $activation)
	{
		try
		{
			// If activation is required an array is returned, otherwise just the user_id integer
			$user_return = Sentry::user()->create($vars, $activation);
			$user_id = 0;
			$hash = '';
			if (is_array($user_return))
			{
				$user_id = $user_return['id'];
				$hash = isset($user_return['hash']) ? $user_return['hash'] : '';
			}
			else
			{
				$user_id = $user_return;
				$hash = '';
			}

		    if ($user_id)
		    {
		    	return $data = array(
		    		'success'	=> true,
		    		'user_id'	=> $user_id,
		    		'hash'		=> $hash
		    	);
		    }
		    else
		    {
		        // something went wrong - shouldn't really happen
		        return $data = array('success' => false, 'sentry' => 'The system encountered a major error.');
		    }
	    }
	    catch (Sentry\SentryException $e)
		{
		    $errors = $e->getMessage(); // catch errors such as user exists or bad fields
		    return $data = array('success' => false, 'sentry' => $errors);
		}
	}

	/**
	 * Check if the user is logged in
	 *
	 * @return bool
	 */
	public static function is_logged_in()
	{
		return Sentry::check();
	}

	/**
	 * Force log the user in based on their ID
	 *
	 * @param integer $user_id
	 * @return void
	 */
	public static function force_login($user_id)
	{
		Sentry::force_login($user_id);
	}

	/**
	 * Log a user out and redirect to the login page
	 *
	 * @return void
	 */
	public static function logout()
	{
		Sentry::logout();
		return Redirect::to('login')->with('logout', 'You are now logged out!');
	}

	/**
	 * Get the user with id equal to $id.
	 * If the user is the current user, use the IoC container to retrieve them.
	 *
	 * @param integer $id
	 * @return User
	 */
	public static function get_user($id)
	{
		$user = User::current_user();
		if ($id != $user->id)
		{
			$user = User::find($id);
		}

		return $user;
	}

	/**
	 * Is the given user in the admin group?
	 *
	 * @param User $user defaults to null which causes the method to check the currently logged in user
	 * @return boolean
	 */
	public static function is_admin($user = null)
	{
		// If no user is passed in, use the current user
		$user = is_null($user) ? User::current_user() : $user;

		return in_array('Admin', $user->groups()->lists('name'));
	}

	/*
	 * Function: _encode
	 * Modified for DX_Auth
	 * Original Author: FreakAuth_light 1.1
	 */
	public static function _old_system_encode($password)
	{
		$majorsalt = '';

		// if PHP5
		if (function_exists('str_split'))
		{
			$_pass = str_split($password);
		}
		// if PHP4
		else
		{
			$_pass = array();
			if (is_string($password))
			{
				for ($i = 0; $i < strlen($password); $i++)
				{
					array_push($_pass, $password[$i]);
				}
			}
		}

		// encrypts every single letter of the password
		foreach ($_pass as $_hashpass)
		{
			$majorsalt .= md5($_hashpass);
		}

		// encrypts the string combinations of every single encrypted letter
		// and finally returns the encrypted password
		return md5($majorsalt);
	}

	/**
	 * Save chef_bio data
	 *
	 * @return boolean was saving successful
	 */
	public function update_chef_bio()
	{
		$this->metadata->chef_bio = Input::get('chef_bio');
		return $this->metadata->save();
	}
}
