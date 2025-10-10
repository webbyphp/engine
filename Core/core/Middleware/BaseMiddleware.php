<?php

/**
 * Base Middleware Class
 * 
 * Provides foundation for all middleware implementations
 *
 * @author  Kwame Oteng Appiah-Nti <developerkwame@gmail.com>
 * @license MIT
 */

namespace Base\Middleware;

abstract class BaseMiddleware implements MiddlewareInterface
{
    /**
     * Controller instance
     *
     * @var object
     */
    protected $controller;

    /**
     * CodeIgniter instance
     *
     * @var object
     */
    protected $app;

    public $userSessionKey = 'user';

    /**
     * Constructor
     *
     * @param object $controller
     * @param object $ci
     */
    public function __construct($controller = null, $ci = null)
    {
        $this->controller = $controller;
        $this->app = $ci ?? get_instance();
    }

    /**
     * Handle middleware logic
     * 
     * This method should be implemented by child classes
     *
     * @return void
     */
    abstract public function handle();

    /**
     * Always method - runs regardless of conditions
     * 
     * Override this in child classes if needed
     *
     * @return void
     */
    public function always()
    {
        // Override in child class if needed
    }

    /**
     * Helper method to redirect
     *
     * @param string $uri
     * @param int $code
     * @return void
     */
    protected function redirect($uri, $code = 302)
    {
        redirect($uri, 'location', $code);
    }

    /**
     * Helper method to abort with error
     *
     * @param int $code
     * @param string $message
     * @return void
     */
    protected function abort($code = 404, $message = null)
    {
        if ($code === 404) {
            show_404();
        } else {
            show_error($message ?? 'Access Denied', $code);
        }
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    protected function isAuthenticated()
    {
        $this->app->load->library('session');
        return $this->app->session->userdata('logged_in') === true;
    }

    /**
     * Get current user
     *
     * @return mixed
     */
    protected function user()
    {
        $this->app->load->library('session');
        return $this->app->session->userdata($this->userSessionKey);
    }

    /**
     * Check if user has role
     *
     * @param string|array $roles
     * @return bool
     */
    protected function hasRole($roles)
    {
        $user = $this->user();
        
        if (!$user) {
            return false;
        }

        $userRole = $user['role'] ?? null;
        
        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }

        return $userRole === $roles;
    }

    /**
     * Set flash message
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function flash($key, $value)
    {
        $this->app->load->library('session');
        $this->app->session->set_flashdata($key, $value);
    }
}
