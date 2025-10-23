<?php

namespace Base\Admin\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature   = 'admin:install';
    protected $description = 'Install the admin package';
    protected $directory   = '';
    protected $routesPath  = '';

    public function handle()
    {
        $this->initDatabase();
        $this->initAdminDirectory();
        \Illuminate\Support\Facades\Artisan::call('vendor:publish', [
            '--tag'   => 'base-admin-assets',
            '--force' => true,
        ]);
    }

    public function initDatabase()
    {
        $this->call('migrate');
        $userModel = config('backend.database.users_model');
        if($userModel::count() == 0) {
            $this->call('db:seed', ['--class' => \Base\Admin\Auth\Database\AdminTablesSeeder::class]);
        }
    }

    protected function initAdminDirectory()
    {
        $this->directory  = config('backend.directory');
        $this->routesPath = base_path();
        if(!is_dir($this->directory)) {
            $this->makeDir('/');
            $this->line('<info>Admin directory was created:</info> ' . str_replace(base_path(), '', $this->directory));
        } else {
            $this->line("<comment>{$this->directory} directory already exists, skipped creation.</comment>");
        }
        $this->createHomeController();
        $this->createAuthController();
        $this->createBootstrapFile();
        $this->createRoutesFile();
    }

    protected function makeDir($path = '')
    {
        $this->laravel['files']->makeDirectory("{$this->directory}/$path", 0755, true, true);
    }

    public function createHomeController()
    {
        $homeController = $this->directory . '/HomeController.php';
        $contents       = $this->getStub('HomeController');
        $this->laravel['files']->put(
            $homeController,
            str_replace('DummyNamespace', config('backend.route.namespace'), $contents)
        );
        $this->line('<info>HomeController file was created:</info> ' . str_replace(base_path(), '', $homeController));
    }

    protected function getStub($name)
    {
        return $this->laravel['files']->get(__DIR__ . "/stubs/$name.stub");
    }

    public function createAuthController()
    {
        $authController = $this->directory . '/AuthController.php';
        $contents       = $this->getStub('AuthController');
        $this->laravel['files']->put(
            $authController,
            str_replace('DummyNamespace', config('backend.route.namespace'), $contents)
        );
        $this->line('<info>AuthController file was created:</info> ' . str_replace(base_path(), '', $authController));
    }

    protected function createBootstrapFile()
    {
        $file     = $this->routesPath . '/bootstrap/backend.php';
        $contents = $this->getStub('bootstrap');
        $this->laravel['files']->put($file, $contents);
        $this->line('<info>Bootstrap file was created:</info> ' . str_replace(base_path(), '', $file));
    }

    protected function createRoutesFile()
    {
        $file     = $this->routesPath . '/routes/backend.php';
        $contents = $this->getStub('routes');
        $this->laravel['files']->put($file, str_replace('DummyNamespace', config('backend.route.namespace'), $contents));
        $this->line('<info>Routes file was created:</info> ' . str_replace(base_path(), '', $file));
        $webFile     = base_path('routes/web.php');
        $webContents = $this->laravel['files']->get($webFile);
        if(!preg_match("/require\s*__DIR__\s*\.\s*['\"]\/backend\.php['\"]\s*;?/", $webContents)) {
            $this->laravel['files']->append($webFile, PHP_EOL . "require __DIR__.'/backend.php';" . PHP_EOL);
            $this->line('<info>Added require to routes/web.php</info>');
        } else {
            $this->line('<comment>routes/web.php already contains require backend.php</comment>');
        }
        $this->callSilent('route:clear');
        $this->line('<info>Route cache cleared</info>');
    }
}
