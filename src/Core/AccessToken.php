<?php

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * AccessToken.php.
 *
 * @author    overtrue <i@overtrue.me>
 * @copyright 2015 overtrue <i@overtrue.me>
 *
 * @link      https://github.com/overtrue
 * @link      http://overtrue.me
 */
namespace EasyWeChat\Core;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;
use EasyWeChat\Core\Exceptions\HttpException;

/**
 * Class AccessToken.
 */
class AccessToken
{
    /**
     * App ID.
     *
     * @var string
     */
    protected $appId;

    protected $auth_appid;
    protected $access_token;
    protected $component_token;
    protected $access_refresh_token;

    protected $expires_in;

    /**
     * App secret.
     *
     * @var string
     */
    protected $secret;

    /**
     * Cache.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Http instance.
     *
     * @var Http
     */
    protected $http;

    /**
     * Query name.
     *
     * @var string
     */
    protected $queryName = 'access_token';

    /**
     * Cache key prefix.
     *
     * @var string
     */
    protected $prefix = 'easywechat.common.access_token.';

    // API
    const API_TOKEN_GET = 'https://api.weixin.qq.com/cgi-bin/token';
    const API_COMMPENT_TOKEN_GET = 'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token';

    /**
     * Constructor.
     *
     * @param string $appId
     * @param string $secret
     * @param \Doctrine\Common\Cache\Cache $cache
     */
    public function __construct($appId, $secret, Cache $cache = null, $auth_info = null, $component_token = null)
    {
        $this->appId = $appId;
        $this->secret = $secret;
        $this->cache = $cache;
        $this->auth_appid = $auth_info->authorizer_appid;
        $this->access_token = $auth_info->authorizer_access_token;
        $this->access_refresh_token = $auth_info->authorizer_refresh_token;
        $this->expires_in = $auth_info->expires_in;
        $this->component_token = $component_token;
    }

    /**
     * Get token from WeChat API.
     *
     * @param bool $forceRefresh
     *
     * @return string
     */
    public function getToken($forceRefresh = false)
    {
        if ($this->checkExpires()) {
            return $this->access_token;
        } else {
            $token = $this->getTokenFromServer();
            return $token['authorizer_access_token'];
        }


//        $cacheKey = $this->prefix . $this->appId;
//
//        $cached = $this->getCache()->fetch($cacheKey);
//
//        if ($forceRefresh || empty($cached)) {
//
//            $token = $this->getTokenFromServer();
//
//            // XXX: T_T... 7200 - 1500
//            $this->getCache()->save($cacheKey, $token['access_token'], $token['expires_in'] - 1500);
//
//            return $token['access_token'];
//        }
//
//        return $cached;
    }

    /**
     * Return the app id.
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * Return the secret.
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * Set cache instance.
     *
     * @param \Doctrine\Common\Cache\Cache $cache
     *
     * @return AccessToken
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Return the cache manager.
     *
     * @return \Doctrine\Common\Cache\Cache
     */
    public function getCache()
    {
        return $this->cache ?: $this->cache = new FilesystemCache(sys_get_temp_dir());
    }

    /**
     * Set the query name.
     *
     * @param string $queryName
     *
     * @return $this
     */
    public function setQueryName($queryName)
    {
        $this->queryName = $queryName;

        return $this;
    }

    /**
     * Return the query name.
     *
     * @return string
     */
    public function getQueryName()
    {
        return $this->queryName;
    }

    /**
     * Return the API request queries.
     *
     * @return array
     */
    public function getQueryFields()
    {
        return [$this->queryName => $this->getToken()];
    }

    /**
     * Get the access token from WeChat server.
     *
     * @throws \EasyWeChat\Core\Exceptions\HttpException
     *
     * @return array|bool
     */
    public function getTokenFromServer()
    {
        $params = [
            'component_appid' => $this->appId,
            'authorizer_appid' => $this->auth_appid,
            'authorizer_refresh_token' => $this->access_refresh_token
        ];

        $http = $this->getHttp();

        //$token = $http->parseJSON($http->get(self::API_TOKEN_GET, $params));
        $token = $http->parseJSON($http->post(self::API_COMMPENT_TOKEN_GET . '?component_access_token=' . $this->component_token, json_encode($params)));

        if (empty($token['authorizer_access_token'])) {
            throw new HttpException('Request AccessToken fail. response: ' . json_encode($token, JSON_UNESCAPED_UNICODE));
        }

        return $token;
    }


    /**
     * Return the http instance.
     *
     * @return \EasyWeChat\Core\Http
     */
    public function getHttp()
    {
        return $this->http ?: $this->http = new Http();
    }

    /**
     * Set the http instance.
     *
     * @param \EasyWeChat\Core\Http $http
     *
     * @return $this
     */
    public function setHttp(Http $http)
    {
        $this->http = $http;

        return $this;
    }

    /**
     * 判断token是否过期
     * @return bool
     */
    public function checkExpires()
    {
        $expires_in = strtotime($this->expires_in);
        $now = time();
        return $now < $expires_in;
    }
}
