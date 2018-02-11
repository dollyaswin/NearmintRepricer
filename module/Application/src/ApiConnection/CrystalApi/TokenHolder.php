<?php

namespace Application\ApiConnection\CrystalApi;

/*
 * This class is a singleton used to reduce the number of times Crystal Commerce API is authorization
 * */

class TokenHolder
{
    public static $token;

    /**
     * @param string $token
     *
     */
    public static function setToken(string $token)
    {
        self::$token = $token;
    }

    /**
     * @return mixed
     */
    public static function getToken()
    {
        if (isset(self::$token)) {
            return self::$token;
        }
        return false;
    }


}