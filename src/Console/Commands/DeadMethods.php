<?php

namespace Mrkindy\Deadscanner\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * The DeadMethods command.
 *
 * This command is used to find unused methods in the application.
 */
class DeadMethods extends Command
{
    protected $defaultPaths = [];

    protected $functionNames = [];

    protected $massiveString = '';

    protected $crudNames = [
        'edit',
        'update',
        'create',
        'store',
        'destroy',
        'index',
        'show',
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mrkindy:deadmethods {paths?*} {--dump-output} {--text-output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find dead methods';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Sets the default paths for the command
        $this->setDefaultPaths();

        // Populate function names
        $this->populateFunctionNames();

        // Filter used functions
        $this->filterUsedFunctions();

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
            $paths = [app_path(), resource_path('views')];
        }

        // Store the default paths in the $defaultPaths property
        $this->defaultPaths = collect($paths);
    }

    /**
     * This method is responsible for outputting the list of function names.
     *
     * @return void
     */
    private function output()
    {
        // Check if the 'dump-output' option is set
        if($this->option('dump-output'))
        {
            // Dump the function names
            return dump($this->functionNames);
        }

        // Check if the 'text-output' option is set
        if($this->option('text-output'))
        {
            // Print the function names as text
            return $this->printText($this->functionNames);
        }

        // Output the function names as a table
        $this->table(
            ['#', 'Method', 'File'],
            $this->convertToTable($this->functionNames)
        );
    }

    /**
     * This method is responsible for printing the unused functions.
     *
     * @param array $functionNames The array of function names to be printed.
     * @return void
     */
    private function printText($functionNames)
    {
        // Print the header for the unused functions
        $this->info("Unused functions:");

        // Print the function names separated by new lines
        echo implode("\n", $functionNames).PHP_EOL;
    }

    /**
     * Converts an array of function names and their paths into a table format.
     *
     * @param array $functionNames An array of function names and their paths.
     * @return array The converted table.
     */
    private function convertToTable($functionNames)
    {
        // Initialize an empty array to store the table.
        $table = [];
        // create a counter
        $counter = 1;
        // Iterate through each function name and path.
        foreach ($functionNames as $method => $path) {
            // Add the function name and path as a row in the table.
            $table[] = [$counter, $method, $path[0]];
            // Increment the counter.
            $counter++;
        }

        // Return the converted table.
        return $table;
    }

    /**
     * Populates the function names by scanning PHP files in the specified paths.
     *
     * @return void
     */
    private function populateFunctionNames()
    {
        $this->defaultPaths->each(function ($path) {
            // Retrieve all PHP files in the given path
            collect(File::allFiles($path))->filter(function ($filename) {
                // Filter out files that do not have a .php extension and should be considered
                return Str::endsWith($filename, '.php') and $this->shouldConsider($filename->getPathName());
            })->each(function ($phpFile) {
                $fileContents = file_get_contents($phpFile);
                $this->massiveString .= $fileContents;
                $functionNames = [];
                // Extract function names using regular expression
                preg_match_all('/function\s+([^ ]+?)\s*\(/', $fileContents, $functionNames);
                if (count($functionNames) > 0) {
                    foreach ($functionNames[1] as $fName) {
                        if ($this->ignoreCommonStuff($fName, $phpFile->getPathName())) {
                            continue;
                        }
                        // Store the function name and its corresponding file path
                        $this->functionNames[$fName][] = $phpFile->getPathName();
                    }
                }
            });
        });
    }

    /**
     * Filters out used functions from the list of functions names.
     *
     * @return void
     */
    private function filterUsedFunctions()
    {
        foreach ($this->functionNames as $fName => $files) {
            $matches = [];
            $realFname = $this->mangleLaravelNames($fName);
            if (preg_match("/(->|::)$realFname/", $this->massiveString, $matches) === 1) {
                unset($this->functionNames[$fName]);
                continue;
            }
        }
    }

    /**
     * Check if the given function name and file name should be ignored based on certain conditions.
     *
     * @param string $funcName The name of the function to check.
     * @param string $fileName The name of the file to check.
     * @return bool Returns true if the function and file should be ignored, false otherwise.
     */
    private function ignoreCommonStuff($funcName, $fileName)
    {
        // Check if the function name is 'handle' and the file name contains 'Middleware', 'Listeners', or 'Commands'
        if ($funcName == 'handle' and preg_match('/(Middleware|Listeners|Commands)/', $fileName) === 1) {
            return true;
        }

        // Check if the function name is 'broadcastOn' and the file name contains 'Events'
        if ($funcName == 'broadcastOn' and preg_match('/Events/', $fileName) === 1) {
            return true;
        }

        // Check if the function name is in the list of CRUD names and the file name contains 'Controller'
        return in_array($funcName, $this->crudNames) and Str::contains($fileName, 'Controller');
    }

    /**
     * Determines whether a given filename should be considered for auditing.
     *
     * @param string $filename The name of the file to be checked.
     * @return bool Returns true if the filename should be considered, false otherwise.
     */
    private function shouldConsider($filename)
    {
        // Check if the filename contains 'ServiceProvider'
        if (Str::contains($filename, 'ServiceProvider')) {
            return false; // Exclude filenames containing 'ServiceProvider'
        }

        // Check if the filename contains 'Policies'
        if (Str::contains($filename, 'Policies')) {
            return false; // Exclude filenames containing 'Policies'
        }

        // Check if the filename contains 'Observers'
        if (Str::contains($filename, 'Observers')) {
            return false; // Exclude filenames containing 'Observers'
        }

        return true; // Include all other filenames
    }

    /**
     * This method mangles Laravel names.
     *
     * @param string $fName The name to be mangled.
     * @return string The mangled name.
     */
    private function mangleLaravelNames($fName)
    {
        // Check if the name starts with 'scope' followed by any characters
        $match = '';
        if (preg_match('/^scope(.+$)/', $fName, $match) === 1) {
            // If it matches, convert the captured group to camel case using Str::camel()
            return Str::camel($match[1]);
        }

        // Check if the name starts with 'get' or 'set', followed by any characters, and ends with 'Attribute'
        if (preg_match('/^(get|set)(.+)Attribute$/', $fName, $match) === 1) {
            // If it matches, convert the captured group to snake case using Str::snake()
            return Str::snake($match[2]);
        }

        // If none of the above conditions match, return the original name
        return $fName;
    }
}
