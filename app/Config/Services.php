<?php

namespace Config;

use App\Libraries\ObjectStorage\AwsS3ObjectStorageClient;
use App\Libraries\ObjectStorage\ObjectStorageClientInterface;
use App\Libraries\Elastic\ElasticClient;
use App\Libraries\Elastic\JobSearchService;
use App\Libraries\FeatureFlags as FeatureFlagsLib;
use App\Libraries\PortalAuth;
use App\Libraries\PortalLocale;
use CodeIgniter\Config\BaseService;
use Elastic\Elasticsearch\ClientBuilder;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function portalAuth(bool $getShared = true): PortalAuth
    {
        if ($getShared) {
            return static::getSharedInstance('portalAuth');
        }

        return new PortalAuth();
    }

    public static function portalLocale(bool $getShared = true): PortalLocale
    {
        if ($getShared) {
            return static::getSharedInstance('portalLocale');
        }

        return new PortalLocale();
    }

    public static function objectStorage(bool $getShared = true): ObjectStorageClientInterface
    {
        if ($getShared) {
            return static::getSharedInstance('objectStorage');
        }

        return new AwsS3ObjectStorageClient(config(ObjectStorage::class));
    }

    /**
     * Official Elasticsearch PHP client ({@see https://github.com/elastic/elasticsearch-php}).
     */
    public static function elasticsearch(bool $getShared = true): \Elastic\Elasticsearch\Client
    {
        if ($getShared) {
            return static::getSharedInstance('elasticsearch');
        }

        return ClientBuilder::create()
            ->setHosts([rtrim(config(Elastic::class)->elasticsearchUrl, '/')])
            ->build();
    }

    public static function elasticClient(bool $getShared = true): ElasticClient
    {
        if ($getShared) {
            return static::getSharedInstance('elasticClient');
        }

        return new ElasticClient(static::elasticsearch(false));
    }

    public static function jobSearch(bool $getShared = true): JobSearchService
    {
        if ($getShared) {
            return static::getSharedInstance('jobSearch');
        }

        return new JobSearchService(static::elasticClient(), config(Elastic::class));
    }

    public static function featureFlags(bool $getShared = true): FeatureFlagsLib
    {
        if ($getShared) {
            return static::getSharedInstance('featureFlags');
        }

        return FeatureFlagsLib::fromConfig(config(FeatureFlags::class));
    }
}
