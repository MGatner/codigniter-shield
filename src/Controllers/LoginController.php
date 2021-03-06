<?php namespace Sparks\Shield\Controllers;

use CodeIgniter\Controller;

class LoginController extends Controller
{
	/**
	 * @var \Sparks\Shield\Config\Auth
	 */
	protected $config;

	public function __construct()
	{
		$this->config = config('Auth');
	}

	/**
	 * Displays the form the login to the site.
	 */
	public function loginView()
	{
		echo view($this->config->views['login']);
	}

	/**
	 * Attempts to log the user in.
	 */
	public function loginAction()
	{
		$credentials             = $this->request->getPost($this->config->validFields);
		$credentials             = array_filter($credentials);
		$credentials['password'] = $this->request->getPost('password');
		$remember                = (bool)$this->request->getPost('remember');

		// Attempt to login
		$result = auth('session')->remember($remember)->attempt($credentials);
		if (! $result->isOK())
		{
			return redirect()->route('login')->withInput()->with('error', $result->reason());
		}

		$user = $result->extraInfo();

		return redirect()->to($this->getLoginRedirect($user));
	}

	/**
	 * Logs the current user out.
	 *
	 * @return \CodeIgniter\HTTP\RedirectResponse
	 */
	public function logoutAction()
	{
		$user = auth()->user();

		auth()->logout();

		return redirect()->to($this->getLogoutRedirect($user));
	}

	/**
	 * Returns the URL that a user should be redirected
	 * to after a successful login.
	 *
	 * @param $user
	 *
	 * @return string
	 */
	protected function getLoginRedirect($user)
	{
		$url = config('Auth')->redirects['login'];

		return strpos($url, 'http') === 0
			? $url
			: rtrim(site_url($url), '/ ');
	}

	/**
	 * Returns the URL that a user should be redirected
	 * to after they are logged out.
	 *
	 * @param $user
	 *
	 * @return string
	 */
	protected function getLogoutRedirect($user)
	{
		$url = config('Auth')->redirects['logout'];

		return strpos($url, 'http') === 0
			? $url
			: rtrim(site_url($url), '/ ');
	}
}
