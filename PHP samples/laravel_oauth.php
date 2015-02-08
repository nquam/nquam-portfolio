<?php
/**
 * Controller used for authorization
 *
 * @author Nathan Quam <nquam@redacted.com>
 * @author redacted
 * @since October 2, 2012
 */
class Auth_Controller extends Base_Controller
{
	public $restful = true;

	public $layout = 'admin.layouts.master';

	public $oauth_providers = array();

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct()
	{
		// Call the parent constructor
        parent::__construct();

		if (Settings::where_bundle('laravel-oauth2')->where_name('Google OAuth')->only('on'))
		{
			array_push($this->oauth_providers, (object)array(
				'name'	=> 'Google',
				'link'	=> 'google',
				'image'	=> URL::base().'/images/signin_google.png'
			));
		}

		if (Settings::where_bundle('laravel-oauth2')->where_name('Facebook OAuth')->only('on'))
		{
			array_push($this->oauth_providers, (object)array(
				'name'	=> 'Facebook',
				'link'	=> 'facebook',
				'image'	=> URL::base().'/images/signin_facebook.png'
			));
		}

		// if (Settings::where_bundle('laravel-oauth2')->where_name('Twitter OAuth')->only('on'))
		// {
		// 	array_push($this->oauth_providers, (object)array(
		// 		'name'	=> 'Twitter',
		// 		'link'	=> 'twitter',
		// 		'image'	=> URL::base().'/images/signin_twitter.png'
		// 	));
		// }
	}

	/**
	 * Check if logged in and forward or return form
	 *
	 * @return View|Redirect
	 */
	public function get_login()
	{
		if (User::is_logged_in())
		{
			return Redirect::to(Redact::admin());
		}
		else
		{
			return View::make(Redact::admin().'auth.index')->with('oauth_providers', $this->oauth_providers);
		}
	}

	/**
	 * Run validation and login user or return errors
	 *
	 * @return Redirect
	 */
	public function post_login()
	{
		$rules = array(
			'username'	=> 'required|email|max:50',
			'password'	=> 'required'
		);

		$validation = Validator::make(Input::all(), $rules);

		if ($validation->fails())
		{
			return Redirect::to(Redact::admin().'login')
				->with_input()
				->with_errors($validation);
		}
		else
		{
			$data = User::try_login();

			// Login success
			if ($data['success'])
			{
				// Set KCFinder session variable
				session_start();
				$_SESSION['KCFINDER'] = array();
				$_SESSION['KCFINDER']['disabled'] = false;

				$reference = Session::get('reference');

				// If the user has a reference set, send them back to it.
				if (!in_array($reference, array(null, 'admin/logout', 'logout')))
				{
					return Redirect::to($reference);
				}
				// Send the user to the default index page
				else
				{
					return Redirect::to(Redact::admin());
				}
			}
			// Login fail
			else
			{
				return Redirect::to(Redact::admin().'login')
					->with_input()
					->with('sentry', $data['sentry']);
			}
		}
	}

	/**
	 * Check settings then show registration form
	 *
	 * @return View|Redirect
	 */
	public function get_register()
	{
		// check if registration is enabled
		if (Settings::where_bundle('Sentry')->where_name('Registration Allowed')->only('on'))
		{
			return View::make('auth.register');
		}
		else
		{
			return Redirect::to('login')->with('sentry', 'Registration is currently disabled');
		}
	}

	/**
	 * Validate the registration form and submit.
	 * Activation required -> send email
	 * Else -> force login and direct somewhere
	 *
	 * @return Redirect
	 */
	public function post_register()
	{
		$rules = array(
			'first_name'	=> 'required',
			'last_name'		=> 'required_with:first_name',
			'email'			=> 'required|email|unique:users|max:100',
			'password'		=> 'required|confirmed|min:'.PASSWORD_MIN_LENGTH
		);

		$validation = Validator::make(Input::all(), $rules);

		if ($validation->fails())
		{
			return Redirect::to('register')->with_input()->with_errors($validation);
		}
		else
		{
			$activation = Settings::where_bundle('Sentry')->only('on'); // activation required?
			$vars = array(
				'email'	=> Input::get('email'),
				'password' => Input::get('password'),
				'metadata' => array(
					'first_name' => Input::get('first_name'),
					'last_name'  => Input::get('last_name'),
					// add any other fields you want in your metadata here. ( must add to db table first )
				)
			);

			$data = User::try_registration($vars, $activation);
			if ($data['success'])
			{
				$group = Group::where('name', '=', 'Registered User')->first();
		        if (is_null($group))
		        {
		            $group = Group::create(array('name' => 'Registered User'));
		        }
				DB::table('users_groups')
                    ->insert(array(
                        'user_id'   => $data['user_id'],
                        'group_id'  => $group->id
                    )
                );

				// If the user has to activate
				if ($activation)
				{
					$email = Input::get('email');
					$activation_link = URL::base().Redact::admin().'/activate/'.$data['hash'];
					$body = Redact::email_template('activation', array('activation_link' => $activation_link));
					$subject = EMAIL_ACTIVATION_SUBJECT;
					$result = Redact::send_email($email, $body, $subject);

					// TODO: if ($result == 0) log error or something
					// TODO: remove this dd() after we figure out how to handle failed emails
					if ($result == 0)
						dd('Error sending activation email. Activation link: '.$activation_link);

					return Redirect::to('registered');
				}
				// If the user doesn't have to activate their account, log them in
				else
				{
					// Force login
					User::force_login($data['user_id']);

					return Redirect::to('index');
				}
			}
			else
			{
				return Redirect::to('register')
					->with_input()
					->with_errors($data['sentry']);
			}
		}
	}

	/**
	 * Page that tells the user they have registered but still need to activate their account
	 *
	 * @return View
	 */
	public function get_registered()
	{
		return View::make('auth/registered')
			->with('type', 'activated');
	}

	/**
	 * Activate the account tied to the given parameter
	 *
	 * @param string $email
	 * @param string $hash
	 * @return View
	 */
	public function get_activate($email, $hash)
	{
		// Active the user
		$activate_user = Sentry::activate_user($email, $hash);
		if (!$activate_user)
			die('User not activated. An unknown error was encountered.');

		// Force login - the $email parameter that is passed in here is base64_encoded
		User::force_login(base64_decode($email));

		return Redirect::to('/')
			->with('success', 'Your account is now active and you are logged in.');
	}

	/**
	 * Display the forgot password page
	 *
	 * @return View
	 */
	public function get_forgot_password()
	{
		return View::make(Redact::admin().'auth/forgot_password');
	}

	/**
	 * Reset the user's password
	 *
	 * @return View
	 */
	public function post_forgot_password()
	{
		$rules = array(
			'email'		=> 'required|email'
		);

		// Validate the input the user provided from the form in the view users.edit
		$validation = Validator::make(Input::all(), $rules);
    	if ($validation->fails())
    	{
    		return Redirect::to(Redact::admin().'forgot_password')
    			->with_input()
    			->with_errors($validation);
    	}
    	else
    	{
			$email = Input::get('email');
			$password = Redact::generate_random_password();

			try
			{
				// reset the password
				$reset = Sentry::reset_password($email, $password);
				if ($reset)
				{
					$link = URL::base().'/'.Redact::admin().'forgot_password_confirm/'.$reset['link'];
					// TODO email $link to $email

					$body = Redact::email_template('reset_password', array('link' => $link));
					$subject = EMAIL_RESET_PASSWORD_SUBJECT;
					$result = Redact::send_email($email, $body, $subject);

					// TODO: if ($result == 0) log error or something
					// TODO: remove this dd() after we figure out how to handle failed emails
					if ($result == 0) dd('Error sending password reset email. Reset link: '.$link);

					return Redirect::to(Redact::admin().'forgot_password_sent');
				}
				else
				{
					// password was not reset
					$errors = (object)array('messages' => array(array('An unknown error occurred while trying to reset your password.')));

					return Redirect::to(Redact::admin().'forgot_password')
						->with_errors($errors);
				}
			}
			catch (Sentry\SentryException $e)
			{
				$errors = (object)array('messages' => array(array($e->getMessage())));

				return Redirect::to(Redact::admin().'forgot_password')
					->with_errors($errors);
			}
		}
	}

	/**
	 * Tell to the user that the reset password request has been submitted.
	 * Provide them instructions on how to complete the process.
	 *
	 * @return View
	 */
	public function get_forgot_password_sent()
	{
		return View::make(Redact::admin().'auth.forgot_password_sent');
	}

	/**
	 * Confirm the resetting of a password
	 *
	 * @param string $email
	 * @param string $hash
	 * @return View
	 */
	public function get_forgot_password_confirm($email, $hash)
	{
		$confirm_reset = Sentry::reset_password_confirm($email, $hash);
		$email = base64_decode($email);
		if ($confirm_reset)
		{
			// $user = User::where_email($email)->first();
			// $user->force_change_password = 1;
			// $user->save();
			DB::table('users')
				->where('email', '=', $email)
				->update(array('force_change_password' => 1));


			// Force login - the $email parameter that is passed in here is base64_encoded
			User::force_login($email);

			return Redirect::to(Redact::admin().'change_password');
		}
		else
		{
			// password was not reset - bad login/hash combo
			return Redirect::to(Redact::admin().'forgot_password')
				->with('error', 'An unknown error has occurred while trying to reset your password.');
		}
	}

	/**
	 * Display change password form
	 *
	 * @return View
	 */
	public function get_change_password()
	{
		return View::make(Redact::admin().'auth.change_password');
	}

	/**
	 * Change the users password
	 *
	 * @return Redirect
	 */
	public function post_change_password()
	{
		$rules = array(
			'password'	=> 'required|confirmed|min:'.PASSWORD_MIN_LENGTH
		);

		$validation = Validator::make(Input::all(), $rules);
    	if ($validation->fails())
    	{
    		return Redirect::to(Redact::admin().'change_password')->with_errors($validation);
    	}
    	else
    	{
    		$user_id = (int)Session::get(Config::get('sentry::sentry.session.user'));
			$password = Input::get('password');

			// We use a Sentry user so that it will hash the password for us.
			$sentry_user = Sentry::user($user_id);
			$sentry_user->update(array('password' => $password));
			// $user = User::find($user_id);
			// $user->force_change_password = 0;
			// $user->save();

			DB::table('users')
				->where('id', '=', $user_id)
				->update(array(
					'force_change_password' => 0
				)
			);

			return Redirect::to(Redact::admin())->with('message', 'Your password has been successfully updated.');
		}
	}
}
