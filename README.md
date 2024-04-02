
# DeadScanner: Uncover Unused Controller in Your Laravel Projects

**Efficiently identify and prune dead methods and Controllers to keep your codebase clean and maintainable.**

## Features

-   **Pinpoints Unused Methods and Controllers:**  Detects methods and Controllers that aren't actively utilized within your project.
-   **Customizable Scanning:**  Target specific paths for analysis and exclude namespaces as needed.
-   **Flexible Output Formats:**  View results concisely in text format or obtain a detailed dump for in-depth analysis.
-   **Easy Integration:**  Seamlessly integrates into your Laravel workflow with straightforward installation and usage.

## Installation

Add DeadScanner to your project using Composer:

``` bash
composer require mrkindy/deadscanner
```
## Usage

Run the following commands to scan your project for dead code:

-   **Find dead Controllers:**
        
    ``` bash
    php artisan mrkindy:deadcontroller
    ```
    
-   **Find dead methods:**
        
    ``` bash
    php artisan mrkindy:deadmethods
    ```
    
**Arguments:**

-   `paths`: Optional list of specific paths to scan.

**Options:**

-   `--dump-output`: Outputs a detailed dump of dead code findings for comprehensive analysis.
-   `--text-output`: Presents results in a concise text format for quick overview.

## Contributing

We appreciate contributions! Please refer to the `CONTRIBUTING.md` file for details on how to get involved.

## Credits

- [Ibrahim Abotaleb](https://github.com/mrkindy)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.