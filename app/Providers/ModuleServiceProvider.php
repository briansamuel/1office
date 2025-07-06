<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Work\Models\Task;
use App\Modules\Work\Observers\TaskObserver;
use App\Modules\Work\Repositories\TaskRepository;
use App\Modules\Work\Services\TaskService;

class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Work Module services
        $this->registerWorkModule();
        
        // Register HRM Module services
        $this->registerHRMModule();
        
        // Register CRM Module services
        $this->registerCRMModule();
        
        // Register Warehouse Module services
        $this->registerWarehouseModule();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register model observers
        Task::observe(TaskObserver::class);
    }

    /**
     * Register Work Module services
     */
    private function registerWorkModule(): void
    {
        $this->app->bind(TaskRepository::class, function ($app) {
            return new TaskRepository(new Task());
        });

        $this->app->bind(TaskService::class, function ($app) {
            return new TaskService($app->make(TaskRepository::class));
        });
    }

    /**
     * Register HRM Module services
     */
    private function registerHRMModule(): void
    {
        // TODO: Register HRM services when implemented
        // Example:
        // $this->app->bind(EmployeeRepository::class, function ($app) {
        //     return new EmployeeRepository(new Employee());
        // });
    }

    /**
     * Register CRM Module services
     */
    private function registerCRMModule(): void
    {
        // TODO: Register CRM services when implemented
        // Example:
        // $this->app->bind(CustomerRepository::class, function ($app) {
        //     return new CustomerRepository(new Customer());
        // });
    }

    /**
     * Register Warehouse Module services
     */
    private function registerWarehouseModule(): void
    {
        // TODO: Register Warehouse services when implemented
        // Example:
        // $this->app->bind(ProductRepository::class, function ($app) {
        //     return new ProductRepository(new Product());
        // });
    }
}
