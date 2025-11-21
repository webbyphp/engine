<?php

/**
 * Simple Service Container for WebbyPHP and CodeIgniter 3
 * Provides modern dependency injection while 
 * maintaining CI3 compatibility
 */
class CI_ServiceContainer
{
    /**
     * Container bindings
     * @var array
     */
    protected $bindings = [];

    /**
     * Singleton instances
     * @var array
     */
    protected $instances = [];

    /**
     * Resolved instances cache
     * @var array
     */
    protected $resolved = [];

    /**
     * CI3 Controller instance
     * @var CI_Controller
     */
    protected $ci;

    public function __construct()
    {
        $this->ci = get_instance();
        $this->registerCoreServices();
    }

    /**
     * Bind a service to the container
     * 
     * @param string $abstract
     * @param callable|string $concrete
     * @return self
     */
    public function bind(string $abstract, callable|string $concrete): self
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => false
        ];

        // Remove any existing singleton instance
        unset($this->instances[$abstract], $this->resolved[$abstract]);

        return $this;
    }

    /**
     * Bind a singleton service to the container
     * 
     * @param string $abstract
     * @param callable|string $concrete
     * @return self
     */
    public function singleton(string $abstract, callable|string $concrete): self
    {
        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => true
        ];

        return $this;
    }

    /**
     * Get a service from the container
     * 
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     */
    public function get(string $abstract, array $parameters = []): mixed
    {
        // Return singleton instance if exists
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Check if we have a binding
        if (isset($this->bindings[$abstract])) {
            $binding = $this->bindings[$abstract];
            $concrete = $binding['concrete'];

            // Resolve the concrete implementation
            if (is_callable($concrete)) {
                $object = $concrete($this, $parameters);
            } else {
                $object = $this->build($concrete, $parameters);
            }

            // Store singleton instance
            if ($binding['singleton']) {
                $this->instances[$abstract] = $object;
            }

            return $object;
        }

        // Try to resolve from CI3 ecosystem
        return $this->resolveFromCI3($abstract, $parameters);
    }

    /**
     * Build a concrete class instance
     * 
     * @param string $class
     * @param array $parameters
     * @return object
     */
    protected function build(string $class, array $parameters): object
    {
        $reflection = new ReflectionClass($class);

        // Check if class is instantiable
        if (!$reflection->isInstantiable()) {
            throw new Exception("Class [{$class}] is not instantiable.");
        }

        $constructor = $reflection->getConstructor();

        // If no constructor, just create instance
        if (is_null($constructor)) {
            return new $class();
        }

        // Get constructor parameters
        $constructorParams = $constructor->getParameters();
        $dependencies = $this->resolveDependencies($constructorParams, $parameters);

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Check if service exists in container
     * 
     * @param string $abstract
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
            isset($this->instances[$abstract]) ||
            $this->canResolveFromCI($abstract);
    }

    /**
     * Resolve dependencies for constructor injection
     * 
     * @param array $parameters
     * @param array $primitives
     * @return array
     */
    protected function resolveDependencies(array $parameters, array $primitives = []): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();

            if ($dependency === null) {
                // Primitive parameter
                if (isset($primitives[$parameter->name])) {
                    $dependencies[] = $primitives[$parameter->name];
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("Unable to resolve primitive parameter [{$parameter->name}]");
                }
            } else {
                // Class dependency
                $dependencies[] = $this->get($dependency->name);
            }
        }

        return $dependencies;
    }

    /**
     * Try to resolve from CI3 ecosystem (models, libraries, etc.)
     * 
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     */
    protected function resolveFromCI3(string $abstract, array $parameters = []): mixed
    {
        // Check if already loaded in CI
        if (isset($this->ci->{$abstract})) {
            return $this->ci->{$abstract};
        }

        // Try to resolve as model
        if ($this->isModel($abstract)) {
            return $this->loadModel($abstract);
        }

        // Try to resolve as library
        if ($this->isLibrary($abstract)) {
            return $this->loadLibrary($abstract, $parameters);
        }

        // Try to resolve as helper
        if ($this->isHelper($abstract)) {
            $this->ci->load->helper($abstract);
            return true;
        }

        // Last resort: try to instantiate as regular class
        if (class_exists($abstract)) {
            return $this->build($abstract, $parameters);
        }

        throw new Exception("Unable to resolve [{$abstract}]");
    }

    /**
     * Check if can resolve from CI ecosystem
     * 
     * @param string $abstract
     * @return bool
     */
    protected function canResolveFromCI(string $abstract): bool
    {
        return isset($this->ci->{$abstract}) ||
            $this->isModel($abstract) ||
            $this->isLibrary($abstract) ||
            $this->isHelper($abstract) ||
            class_exists($abstract);
    }

    /**
     * Load CI model
     * 
     * @param string $model
     * @return object
     */
    protected function loadModel(string $model): object
    {

        if (class_exists($model)) {
            return new $model();
        }

        $this->ci->load->model($model);
        $modelName = $this->getModelName($model);

        if (!isset($this->ci->{$modelName})) {
            throw new Exception("Model [{$model}] could not be loaded");
        }

        return $this->ci->{$modelName};
    }

    /**
     * Load CI library
     * 
     * @param string $library
     * @param array $parameters
     * @return object
     */
    protected function loadLibrary(string $library, array $parameters = []): object
    {
        if (class_exists($library)) {
            return !empty($parameters) ? new $library($parameters) : new $library();
        }

        $this->ci->load->library($library, $parameters);
        $libraryName = $this->getLibraryName($library);

        if (!isset($this->ci->{$libraryName})) {
            throw new Exception("Library [{$library}] could not be loaded");
        }

        return $this->ci->{$libraryName};
    }

    /**
     * Check if string represents a model
     * 
     * @param string $name
     * @return bool
     */
    protected function isModel(string $name): bool
    {
        return str_ends_with(strtolower($name), '_model') ||
            str_ends_with(strtolower($name), '_m') ||
            str_contains($name, 'Model');
    }

    /**
     * Check if string represents a library
     * 
     * @param string $name
     * @return bool
     */
    protected function isLibrary(string $name): bool
    {
        $libraryPath = APPPATH . 'libraries/' . ucfirst($name) . '.php';
        $appLibraryPath = APPROOT . 'Libraries/' . ucfirst($name) . '.php';
        $systemLibraryPath = BASEPATH . 'libraries/' . ucfirst($name) . '.php';

        return
            file_exists($libraryPath)
            || file_exists($appLibraryPath)
            || file_exists($systemLibraryPath);
    }

    /**
     * Check if string represents a helper
     * 
     * @param string $name
     * @return bool
     */
    protected function isHelper(string $name): bool
    {
        $helperPath = APPPATH . 'helpers/' . $name . '_helper.php';
        $appHelperPath = APPROOT . 'Helpers/' . ucfirst($name) . '.php';
        $systemHelperPath = BASEPATH . 'helpers/' . $name . '_helper.php';

        return
            file_exists($helperPath)
            || file_exists($appHelperPath)
            || file_exists($systemHelperPath);
    }

    /**
     * Get model property name from model path
     * 
     * @param string $model
     * @return string
     */
    protected function getModelName(string $model): string
    {
        $parts = explode('/', $model);
        $modelName = end($parts);

        return strtolower($modelName);
    }

    /**
     * Get library property name from library path
     * 
     * @param string $library
     * @return string
     */
    protected function getLibraryName(string $library): string
    {
        $parts = explode('/', $library);
        $libraryName = end($parts);

        return strtolower($libraryName);
    }

    /**
     * Register core CI services
     * 
     * @return void
     */
    protected function registerCoreServices(): void
    {
        // Register database as singleton
        $this->singleton('database', function () {
            $this->ci->load->database();
            return $this->ci->db;
        });

        // Register other core services
        $this->singleton('load', function () {
            return $this->ci->load;
        });

        $this->singleton('input', function () {
            return $this->ci->input;
        });

        $this->singleton('output', function () {
            return $this->ci->output;
        });

        $this->singleton('config', function () {
            return $this->ci->config;
        });

        $this->singleton('session', function () {
            return $this->ci->session;
        });
    }

    /**
     * Flush all bindings and instances
     * 
     * @return void
     */
    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->resolved = [];
    }

    /**
     * Get all bindings
     * 
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}
