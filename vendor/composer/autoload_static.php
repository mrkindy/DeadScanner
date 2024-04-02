<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitfb4b95b60b90094e68d5b2cf217615fe
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'Mrkindy\\Deadscanner\\' => 20,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Mrkindy\\Deadscanner\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitfb4b95b60b90094e68d5b2cf217615fe::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitfb4b95b60b90094e68d5b2cf217615fe::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitfb4b95b60b90094e68d5b2cf217615fe::$classMap;

        }, null, ClassLoader::class);
    }
}