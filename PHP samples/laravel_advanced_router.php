<?php
/**
 * Application Routes
 *
 * @author redacted
 * @author Nathan Quam <nquam@redacted.com>
 * @author redacted
 * @since October 1, 2012
 */


// Public Design Templates
//Route::controller('templates'); // Registering the "home" controller with the Router:
Route::get('templates/(:any?)', 'templates@index');
Route::get('templates/migrate', 'templates@migrate');

// Public Home / You do NOT need to be logged in here
Route::get('', 'fhome@index');





if (file_exists( __DIR__.DS.'routes/admin_overrides.php'))
{
	require_once __DIR__.DS.'routes/admin_overrides.php';
}

// Language change
Route::get('languages/(:any)', 'languages@change');

// Image Placeholder
Route::get('placeholder/(:all)', 'placeholder@show');

// Public Home / You do NOT need to be logged in here
Route::get('', 'home@public_landing');

// Search Feature
//Route::get('search', 'search@index');
Route::post('search', 'search@all');
Route::post('admin/search', 'search@admin_index');
Route::get('search/(:any)/(:all)', 'search@by_section');

Route::get('contact-form', 'contact@contact_form');
Route::post('contact-form', 'contact@contact_form');
Route::post('contact/heal-form', 'contact@heal_form');
Route::post('ambassador-form', 'contact@ambassador_form');

// Frontend login landing. Must be logged in. Not tied to the admin side.
Route::get('landing', array('before' => 'auth', 'uses' => 'home@frontend_landing'));

// Admin & Section Landing Pages
Route::get('admin', 'sections@index');
Route::get('admin/sections', 'sections@index');

// Admin Login/Logout
Route::get('admin/login', 'auth@login');
Route::post('admin/login', array('before' => 'csrf', 'uses' => 'auth@login'));
Route::get('admin/logout', function()
{
	Sentry::logout();
	return Redirect::to('admin/login')->with('logout', 'You are now logged out!');
});

// Frontend Login/logout
Route::get('login', 'auth@login');
Route::post('login', array('before' => 'csrf', 'uses' => 'auth@login'));
Route::get('logout', function()
{
	return User::logout();
});

// Registration
Route::get('register', 'auth@register');
Route::post('register', array('before' => 'csrf', 'uses' => 'auth@register'));
Route::get('registered', 'auth@registered');
Route::get('activate/(:all)/(:all)', 'auth@activate');

// Forgot Password
Route::get('forgot_password', 'auth@forgot_password');
Route::post('forgot_password', array('before' => 'csrf', 'uses' => 'auth@forgot_password'));
Route::get('forgot_password_sent', 'auth@forgot_password_sent');
Route::get('forgot_password_confirm/(:all)/(:all)', 'auth@forgot_password_confirm');
Route::get('change_password', array('before' => 'auth', 'uses' => 'auth@change_password'));
Route::post('change_password', 'auth@change_password');

// Admin Forgot Password
Route::get('admin/forgot_password', 'auth@forgot_password');
Route::post('admin/forgot_password', array('before' => 'csrf', 'uses' => 'auth@forgot_password'));
Route::get('admin/forgot_password_sent', 'auth@forgot_password_sent');
Route::get('admin/forgot_password_confirm/(:all)/(:all)', 'auth@forgot_password_confirm');
Route::get('admin/change_password', array('before' => 'auth', 'uses' => 'auth@change_password'));
Route::post('admin/change_password', 'auth@change_password');

// Admin Tags
Route::get('admin/tags', array('before' => 'auth', 'uses' => 'tags@index'));
Route::post('admin/tags', array('before' => 'auth', 'uses' => 'tags@create'));
Route::put('admin/tags', array('before' => 'auth', 'uses' => 'tags@index'));
Route::post('admin/tags/suggestions', array('before' => 'auth', 'uses' => 'tags@suggestions'));
Route::post('admin/tags/attached', array('before' => 'auth', 'uses' => 'tags@attached'));
Route::get('admin/tags/new', array('before' => 'auth', 'uses' => 'tags@new'));
Route::delete('admin/tags/(:num)', 'tags@destroy');

// Admin Settings Panel
Route::get('admin/settings', array('before' => 'auth', 'uses' => 'settings@index'));
Route::put('admin/settings', array('before' => 'auth', 'uses' => 'settings@index'));

// Admin console
Route::get('admin/console', 'console@index');
Route::post('admin/console', array('before' => 'csrf', 'uses' => 'console@index'));

// Images manipulator
Route::get('admin/crop-tool', 'crop_tool@index');
Route::get('admin/crop-tool/cropper', 'crop_tool@cropper');
Route::post('admin/crop-tool', 'crop_tool@cropper');
Route::put('admin/crop-tool', 'crop_tool@cropper');
// Route::post('admin/console', array('before' => 'csrf', 'uses' => 'console@index'));

// OAuth2
Route::any('oauth/(:all)', 'oauth@session');
Route::any('oauth/register', 'oauth@register');

// // Categories
// Route::get('admin/(:any)/categories', 'categories@index');
// Route::get('admin/(:any)/categories/new', 'categories@new');
// Route::post('admin/(:any)/categories', array('before' => 'csrf', 'uses' => 'categories@create'));
// Route::put('admin/(:any)/categories', array('before' => 'csrf', 'uses' => 'categories@sort'));
// Route::get('admin/categories/(:num)', 'categories@show');
// Route::get('admin/categories/(:num)/edit', 'categories@edit');
// Route::put('admin/categories/(:num)', array('before' => 'csrf', 'uses' => 'categories@update'));
// Route::delete('admin/categories/(:num)', 'categories@destroy');

// Aliases
Route::get('admin/(:any)/(:num)/aliases', 'aliases@index');
Route::get('admin/(:any)/aliases/new', 'aliases@new');
Route::post('admin/(:any)/aliases', array('before' => 'csrf', 'uses' => 'aliases@create'));
/* Some routes to do with aliases are inherited through the resource route handlers since aliases are inheritly resources */

// Links
Route::get('admin/(:any)/(:num)/links', 'links@index');
Route::get('admin/(:any)/links/new', 'links@new');
Route::post('admin/(:any)/links', array('before' => 'csrf', 'uses' => 'links@create'));
/* Some routes to do with links are inherited through the resource route handlers since links are inheritly resources */

// Nested resources
Route::get('admin/(:any)/(:num)/resources', 'base_resources_admin@resources');
Route::get('admin/(:any)/(:num)/resources/new', 'base_resources_admin@new');
// Update the sorting of resources
Route::put('admin/(:any)/(:num)/resources', array('before' => 'csrf', 'uses' => 'base_resources_admin@sort'));

// Comments
Route::get('admin/comments', 'comments@index');
Route::post('admin/comments/(:num)/(:any)/(:num)', 'comments@action');
Route::delete('admin/comments/(:num)', 'comments@destroy');

// Dynamically include all files in the routes directory

foreach (new DirectoryIterator(__DIR__.DS.'routes') as $file)
{
	if (!$file->isDot() && !$file->isDir() && $file->getFilename() != '.gitignore' )

	{
		require_once __DIR__.DS.'routes'.DS.$file->getFilename();
	}
}

// Generic admin routes
// Show a list of all resources
Route::get('admin/(:any)', 'base_resources_admin@index');
// For creating a new resource, the 2nd param is for specifying a parent resource id used to create nested resources
Route::get('admin/(:any)/new', 'base_resources_admin@new');
Route::get('admin/(:any)/(:num)/new', 'base_resources_admin@new');
// Post a filter change for the index page
Route::post('admin/(:any)/filters', 'base_resources_admin@filters');
// For saving the new resource
Route::post('admin/(:any)/(:num?)', array('before' => 'csrf', 'uses' => 'base_resources_admin@create_resource'));
// Update the sorting of resources
Route::put('admin/(:any)', array('before' => 'csrf', 'uses' => 'base_resources_admin@sort'));
Route::put('admin/(:any)/(:num)/resources', array('before' => 'csrf', 'uses' => 'base_resources_admin@sort'));
// Show a list of all content entries of a resource
Route::get('admin/(:any)/(:num)', 'base_resources_admin@show_all_content');
// Edit a specific resource
Route::get('admin/(:any)/(:num)/edit', 'base_resources_admin@edit_resource');
// Update a specific resource
Route::put('admin/(:any)/(:num)', array('before' => 'csrf', 'uses' => 'base_resources_admin@update_resource'));
// Update a specific resource's social metadata
Route::put('admin/(:any)/(:num)/social', array('before' => 'csrf', 'uses' => 'base_resources_admin@update_resource_social'));
// Delete a specific resource
Route::delete('admin/(:any)/(:num)', 'base_resources_admin@destroy_resource');
// Show a specific content entry
Route::get('admin/(:any)/(:num)/(:num)', 'base_resources_admin@show');
// For duplicating an existing content entry
Route::get('admin/(:any)/(:num)/(:num)/new', 'base_resources_admin@new_content');
// For saving the new content entry, be it a duplicate or from scratch
Route::post('admin/(:any)/(:num)', array('before' => 'csrf', 'uses' => 'base_resources_admin@create_content'));
// Edit a specific content entry
// We use (:any) for the content_id b/c we may pass 'current' instead of an integer
Route::get('admin/(:any)/(:num)/(:any)/edit', 'base_resources_admin@edit_content');
// Update a specific content entry
Route::put('admin/(:any)/(:num)/(:num)', array('before' => 'csrf', 'uses' => 'base_resources_admin@update_content'));
// Delete a specific content entry
Route::delete('admin/(:any)/(:num)/(:num)', 'base_resources_admin@destroy_content');
// Edit a sections index content
Route::get('admin/(:any)/content/(:any)/edit', 'base_resources_admin@edit_section_content');
// Update a sections index content
Route::put('admin/(:any)/content/(:any)', array('before' => 'csrf', 'uses' => 'base_resources_admin@update_section_content'));

/** IMAGE SIZES **/
// List a sections image sizes
Route::get('admin/(:any)/sizes', 'image_sizes@index');
// List a sections image sizes
Route::post('admin/(:any)/sizes', 'image_sizes@create');
// Edit a section image size
Route::get('admin/(:any)/sizes/(:num)/edit', 'image_sizes@edit');
// Update a sections index content
Route::put('admin/(:any)/sizes/(:num)', array('before' => 'csrf', 'uses' => 'image_sizes@update'));
// Update a sections index content
Route::delete('admin/(:any)/sizes/(:num)', 'image_sizes@destroy');

/** MODULES & MODULE INSTALLATIONS **/
// List a sections modules
Route::get('admin/(:any)/modules', 'modules@index');
// Display all current installations of this module on this section and a HTML form to create a new one
// This page also has the create form
Route::get('admin/(:any)/modules/(:num)/installations', 'modules@list');
// Create a module installation
Route::post('admin/(:any)/modules/(:num)/installations/new', 'modules@create');
// Edit HTML form for a specific module installation
Route::get('admin/(:any)/modules/(:num)/installations/(:num)/edit', 'modules@edit');
// Update a specific module installation
Route::put('admin/(:any)/modules/(:num)/installations/(:num)', 'modules@update');
// Create a module installation
Route::delete('admin/(:any)/modules/(:num)/installations/(:num)', 'modules@destroy');

/** MODULE INSTALLATION CONTENT **/
// List current module installation content items and show a form for creating a new one
Route::get('admin/(:any)/(:num)/module_installations/(:num)', 'module_installation_content@index');
Route::get('admin/(:any)/module_installations/(:num)', 'module_installation_content@index_section');

// Create a specific module installation content item
Route::post('admin/(:any)/(:num)/module_installations/(:num)/new', 'module_installation_content@create');
Route::post('admin/(:any)/module_installations/(:num)/new', 'module_installation_content@create_section');

// Edit HTML form for a specific module installation content item
Route::get('admin/(:any)/(:num)/module_installations/(:num)/(:num)/edit', 'module_installation_content@edit');
Route::get('admin/(:any)/module_installations/(:num)/(:num)/edit', 'module_installation_content@edit_section');

// Update a specific module installation content item
Route::put('admin/(:any)/(:num)/module_installations/(:num)/(:num)', 'module_installation_content@update');
Route::put('admin/(:any)/module_installations/(:num)/(:num)', 'module_installation_content@update_section');

// Delete a specific module installation content item
Route::delete('admin/(:any)/(:num)/module_installations/(:num)/(:num)', 'module_installation_content@destroy');
Route::delete('admin/(:any)/module_installations/(:num)/(:num)', 'module_installation_content@destroy_section');

// Update sort order of module installation content items
Route::put('admin/(:any)/(:num)/module_installations/(:num)', 'module_installation_content@sort');
Route::put('admin/(:any)/module_installations/(:num)', 'module_installation_content@sort_section');

/** RELATIONSHIPS **/
// List a sections relationships
Route::get('admin/(:any)/relationships', 'relationships@index');

// Form to display Related data to choose from
// Optionally has sortable form
Route::get('admin/(:any)/relationships/(:any)', 'relationships@choose_sort');
Route::put('admin/(:any)/relationships/(:any)', 'relationships@sort');
Route::put('admin/(:any)/relationships/(:any)/attach', 'relationships@attach');

/*
|--------------------------------------------------------------------------
| Composers
|--------------------------------------------------------------------------
|
| Define composers here.
| Each time a view is created, its "composer" event will be fired. You can
| listen for this event and use it to bind assets and common data to the
| view each time it is created.
|
| NOTE: sometimes we have to define "admin." & "admin/" because sometimes
| we hardcode "admin." into $view_name on the View::make($view_name) call.
| Othertimes, we use the Redacted library function "Redacted::admin()" to
| dynamically determine which side we are on (ie. admin or frontend).
|
*/

View::composer('admin.sections.index', function($view)
{
	View::share('active_nav_item', 'Sections');
});

View::composer('admin.settings.index', function($view)
{
	View::share('active_nav_item', 'Settings');
});

View::composer(array(
	'admin.tags.index',
	'admin/tags.index',
	'admin.tags.new',
	'admin/tags.new',
	'admin.comments.index',
	'admin.stats.index',
), function($view)
{
	View::share('active_nav_item', 'Global Content');
});

View::composer(array(
	'admin.users.index',
	'admin/users.index',
	'admin.users.new',
	'admin/users.new',
	'admin.groups.index',
	'admin/groups.index',
	'admin.groups.new',
	'admin/groups.new'
), function($view)
{
	View::share('active_nav_item', 'Users');
});

View::composer(array(
	'admin.users.edit',
	'admin/users.edit'
), function($view)
{
	if (User::current_user_id() == $view->data['user']->id)
	{
		View::share('active_nav_item', 'My Profile');
	}
	else
	{
		View::share('active_nav_item', 'Users');
	}
});



/*
|--------------------------------------------------------------------------
| Application 404 & 500 Error Handlers
|--------------------------------------------------------------------------
|
| To centralize and simplify 404 handling, Laravel uses an awesome event
| system to retrieve the response. Feel free to modify this function to
| your tastes and the needs of your application.
|
| Similarly, we use an event to handle the display of 500 level errors
| within the application. These errors are fired when there is an
| uncaught exception thrown in the application.
|
*/

Event::listen('404', function()
{
	return Response::error('404');
});

Event::listen('500', function()
{
	return Response::error('500');
});

/*
|--------------------------------------------------------------------------
| Route Filters
|--------------------------------------------------------------------------
|
| Filters provide a convenient method for attaching functionality to your
| routes. The built-in before and after filters are called before and
| after every request to your application, and you may even create
| other filters that can be attached to individual routes.
|
| Let's walk through an example...
|
| First, define a filter:
|
|		Route::filter('filter', function()
|		{
|			return 'Filtered!';
|		});
|
| Next, attach the filter to a route:
|
|		Router::register('GET /', array('before' => 'filter', function()
|		{
|			return 'Hello World!';
|		}));
|
*/

Route::filter('before', function()
{
	/**
	 * Do stuff before every request to your application...
	 */

	// By pass all normal security checks for admin/console
	$uri = URI::current();
	$secure_segments = array('login', 'register', 'forgot_password', 'forgot_password_sent', 'console');
	$pattern = '#^(admin|protected)($|(?!/login|/register|/forgot_password|/forgot_password_sent|/console)/.*)$#';

	// Load application specific secure paths
	$secure_paths = Config::get('application.secure_paths');

	// Defaultly set this to false, then explicitly set it to true when necessary
	$force_ssl = FALSE;

	if (!empty($secure_paths))
	{
		foreach ($secure_paths as $secure_pattern)
		{
			if ($force_ssl = preg_match('~'.$secure_pattern.'~', Request::uri()))
			{
				break;
			}
		}
	}

	// Force SSL based on conditions
	if (Request::env() == 'production' && Config::get('application.ssl') && !Request::secure() && $force_ssl)
	{
		return Redirect::to_secure($uri);
	}

	// Build an array of paths to not save references for. Helps to avoid infinite redirects
	$non_reference_paths = array_merge($secure_segments, array('oauth(.*)?', 'uploads(.*)?'));

	foreach ($non_reference_paths as $non_ref_pattern)
	{
		if ($dont_reference = preg_match('~'.$non_ref_pattern.'~', Request::uri()))
		{
			break;
		}
	}

	// Save a reference so we can always get back to where we left off if the user decides to login, logout, etc.
	if (!@$dont_reference && !Request::ajax() && Request::uri() != 'logout')
	{
		Session::put('reference', Request::uri());
	}

	if (preg_match($pattern, $uri) || (URI::segment(1) == 'admin' && !in_array(URI::segment(2), $secure_segments)))
	{
		// If force_change_password is set and we are not on the change_password page,
		// redirect the user to the change_password page
		if (User::is_logged_in())
		{
			$user = User::current_user();
			if ($user->force_change_password && !in_array(URI::current(), array('change_password', 'admin/change_password', 'logout', 'admin/logout')))
			{
				return Redirect::to(Redacted::admin().'change_password');
			}

			$group_ids = $user->groups()->pivot()->lists('group_id');
			$user_permissions = User::load_permissions();

			if (!in_array(1, $group_ids) && empty($user_permissions))
			{
				return Redirect::to('/');
			}
		}

		// Require the current user to be logged in if they are visiting a page with "admin" or "protected" at the beginning of the URI
		if (!User::is_logged_in())
		{
			// Save the current URI so we can take the user back there after they have logged in
			Session::put('reference', $uri);

			// Send them to the login page
			return Redirect::to('admin/login');
		}
	}
});

Route::filter('after', function($response)
{
	/**
	 * Do stuff after every request to your application...
	 *
	 * TODO logging stuff goes here
	 */
});

Route::filter('csrf', function()
{
	if (Request::forged()) return Response::error('500');
});

Route::filter('auth', function()
{
	if (!User::is_logged_in())
	{
		// Save the current URI so we can take the user back there after they have logged in
		Session::put('reference', URI::current());

		// Send them to the admin login page
		return Redirect::to('admin/login');
	}
});

Route::filter('frontend-auth', function()
{
	if (!User::is_logged_in())
	{
		// Save the current URI so we can take the user back there after they have logged in
		Session::put('reference', URI::current());

		// Send them to the login page
		return Redirect::to('/login');
	}
});
