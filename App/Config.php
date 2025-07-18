<?php

namespace App;

/**
 * Application configuration
 *
 * PHP version 7.4
 */
class Config
{
    /**
     * Database host
     * @var string
     */
    public static function getDbHost()
    {
        return getenv('DB_HOST') ?: 'db_dev';
    }

    /**
     * Database name
     * @var string
     */
    public static function getDbName()
    {
        return getenv('DB_NAME') ?: 'videgrenierenligne';
    }

    /**
     * Database user
     * @var string
     */
    public static function getDbUser()
    {
        return getenv('DB_USER') ?: 'webapplication';
    }

    /**
     * Database password
     * @var string
     */
    public static function getDbPassword()
    {
        return getenv('DB_PASSWORD') ?: '653rag9T';
    }

    /**
     * Show or hide error messages on screen
     * @var boolean
     */
    public static function showErrors()
    {
        return (getenv('ENVIRONMENT') ?: 'development') !== 'production';
    }

    const SHOW_ERRORS = true;
    /**
     * Get database configuration as array
     * @return array
     */
    public static function getDbConfig()
    {
        return [
            'host' => self::getDbHost(),
            'name' => self::getDbName(),
            'user' => self::getDbUser(),
            'password' => self::getDbPassword()
        ];
    }
}
