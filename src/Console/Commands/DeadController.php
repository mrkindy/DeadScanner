<?php

namespace Mrkindy\Deadscanner\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Route;

/**
 * The DeadController command.
 *
 * This command is used to find unused classes in the application.
 */
class DeadController extends Command
{
        /**
         * The default paths to search for classes.
         *
         * @var array
         */
        protected $defaultPaths = [];

        /**
         * The names of the classes.
         *
         * @var array
         */
        protected $classNames = [];

        /**
         * The names of the classes multidimensional array.
         *
         * @var array
         */
        protected $classNamesTable = [];

        /**
         * The names of the controller classes.
         *
         * @var array
         */
        protected $controllerNames = [];

        /**
         * A massive string.
         *
         * @var string
         */
        protected $massiveString = '';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mrkindy:deadcontroller {paths?*} {--dump-output} {--text-output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find dead controller';

    /**
     * Handle the command execution.
     *
     * This method is responsible for executing the command logic.
     * It calls several helper methods to populate controller names,
     * populate classes from paths, filter used classes, and output the results.
     *
     * @return mixed
     */
    public function handle()
    {
        // Sets the default paths for the command
        $this->setDefaultPaths();

        // Populate controller names from routes
        $this->populateControllerNamesFromRoutes();

        // Populate classes from paths
        $this->populateClassesFromPaths();

        // Filter used classes
        $this->filterUsedClasses();

        // Output the results
        $this->output();
    }

    /**
     * Sets the default paths for the command.
     *
     * @return void
     */
    private function setDefaultPaths()
    {
        // Check if the 'paths' argument is provided
        if ($this->argument('paths') != null)
        {
            // If provided, assign the value to the $paths variable
            $paths = $this->argument('paths');
        }
        else
        {
            // If not provided, set the default path to the 'app' directory
            $paths = [app_path("Http/Controllers")];
        }

        // Store the default paths in the $defaultPaths property
        $this->defaultPaths = collect($paths);
    }

    /**
     * This method is responsible for outputting the list of class names.
     *
     * @return void
     */
    private function output()
    {
        // Check if the 'dump-output' option is set
        if($this->option('dump-output'))
        {
            // Dump the classNames array
            return dump($this->classNames);
        }

        // Check if the 'text-output' option is set
        if($this->option('text-output'))
        {
            // Print the classNames array as text
            return $this->printText($this->classNames);
        }

        // Output the classNames array as a table
        $this->table(
            ['#', 'Name', 'Path'],
            $this->convertToTable($this->classNames)
        );
    }

    /**
     * Print the list of unused classes.
     *
     * @param array $classNames The array of class names.
     * @return void
     */
    private function printText($classNames)
    {
        // Print the header for the list of unused classes.
        $this->info("Unused classes:");

        // Print each class name on a new line.
        echo implode("\n", $classNames).PHP_EOL;
    }

    /**
     * Converts an array of class names and their paths into a table format.
     *
     * @param array $classNames An array of class names and their paths.
     * @return array The converted table.
     */
    private function convertToTable($classNames)
    {
        // Initialize an empty array to store the table.
        $table = [];
        // create a counter
        $counter = 1;
        // Iterate through each class name and path.
        foreach ($classNames as $name => $path) {
            // Add the class name and path as a row in the table.
            $table[] = [$counter, $name, $path];
            // Increment the counter.
            $counter++;
        }

        // Return the converted table.
        return $table;
    }

    /**
     * Filters out used classes from the list of class names.
     *
     * @return void
     */
    private function filterUsedClasses()
    {
        foreach ($this->classNames as $className => $files) {
            $matches = [];

            // Check if the class name is found in the massive string or if it is a registered controller
            if (preg_match("/$className/", $this->massiveString, $matches) === 1 or $this->isARegisteredController($className)) {
                // If the class name is found, remove it from the list of class names
                unset($this->classNames[$className]);
            }
        }
    }

    /**
     * Populates the class names and their corresponding file paths from the default paths.
     *
     * @return void
     */
    private function populateClassesFromPaths()
    {
        // Iterate over each default path
        $this->defaultPaths->each(function ($path) {
            // Get all PHP files in the current path
            collect(File::allFiles($path))->filter(fn ($filename) => Str::endsWith($filename, '.php'))->each(function ($phpFile) {
                // Read the contents of the PHP file
                $fileContents = file_get_contents($phpFile);
                // Check if the file contains a class definition
                if (preg_match('/class\s+(\w+)/', $fileContents, $className) === 1) {
                    // Store the class name and its corresponding file path
                    $this->classNames[$className[1]] = $phpFile->getPathName();
                    // Replace the class name with a random string
                    $fileContents = str_replace($className[1], Str::random(16), $fileContents);
                }
                // Append the file contents to the massive string
                $this->massiveString .= $fileContents;
            });
        });
    }

    /**
     * Populates the controller names from the routes.
     *
     * This method retrieves all the routes and extracts the controller names from the routes' actions.
     * It filters out routes that do not have a 'controller' key in the action or do not contain 'App' in the controller name.
     * The extracted controller names are stored in the $controllerNames array.
     *
     * @return void
     */
    private function populateControllerNamesFromRoutes()
    {
        // Get all registered routes
        $routes = Route::getRoutes();

        // Iterate over each route
        foreach ($routes as $route) {
            // Check if the route has a 'controller' key in its action and if the controller contains 'App'
            if (!array_key_exists('controller', $route->getAction()) || !str_contains($route->getAction()['controller'], 'App')) {
                // If the conditions are not met, skip to the next iteration
                continue;
            }

            // Extract the controller name from the 'controller' key
            [$controller] = explode('@', $route->getAction()['controller']);

            // Get the base name of the controller class and add it to the list of controller names
            $this->controllerNames[] = class_basename($controller);
        }
    }

    /**
     * Check if a class is a registered controller.
     *
     * @param string $className The name of the class to check.
     * @return bool Returns true if the class is a registered controller, false otherwise.
     */
    private function isARegisteredController($className)
    {
        return in_array($className, $this->controllerNames);
    }
}
