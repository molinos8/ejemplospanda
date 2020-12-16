<?php

namespace OKNManager\BM\Repositories;

use App\Models\BillingPeriod;
use App\Models\Feature;
use App\Models\FeatureCategory;
use App\Models\Literal;
use App\Models\Platform;
use App\Models\PlatformsFeaturesBilled;
use App\Models\PlatformsFeaturesStat;
use Carbon\Carbon;

/**
 * Features repository
 */
class FeaturesRepository
{

    /**
     * Function to return billing period id getting period date
     * Date must be correct at this point because it's previously validated by BM
     *
     * @param string $date "YYYY-mm" format
     *
     * @return int|null $periodId
     */
    public function getBillingPeriodByDate(string $date):?int
    {
        $periodId = BillingPeriod::where('billing_period_date', $date)->value('id');

        return $periodId;
    }

    /**
     * Function to return custom info  about platforms needed in billing reports
     *
     * @param array $platforms
     *
     * @return array $result [$platformID =>['name'=>'name', 'storage' => <int>, 'estimated_users'=><int>],...]
     */
    public function getPlatformDataForBillingReport(array $platforms):array
    {
        $platformsID = Platform::select('id', 'name', 'storage', 'estimated_users')->whereIn('code', $platforms)->get()->toArray();
        $result = [];
        foreach ($platformsID as $value) {
            $result[$value['id']] = ['name' => $value['name'], 'storage' => $value['storage'], 'estimated_users'=>$value['estimated_users']];
        }

        return $result;
    }

    /**
     * Function to return array of codes:literals of literal table
     * Be careful, this function obviate  translation_fields code.
     *
     * @param array  $literalCodes
     * @param string $locale
     *
     * @return array $translations ['code'=>'translation','code2'=>'translation2'...]
     */
    public function getReportsLiterals(array $literalCodes, string $locale):array
    {
        $literalCollections= Literal::whereIn('code', $literalCodes)->get();
        $translations=[];
        foreach ($literalCollections as $literal) {
            $literalLanguages =$literal->getI18n();
            $translations[$literal->getCode()] =array_values($literalLanguages[$locale])[0];
        }


        return $translations;
    }

    /**
     * Function to return array of codes:literals of feature categories table
     * Be careful, this function obviate  translation_fields code.
     *
     * @param array  $literalCodes
     * @param string $locale
     *
     * @return array $translations ['code'=>'translation','code2'=>'translation2'...]
     */
    public function getFatherCategoriesTranslates(array $literalCodes, string $locale):array
    {
        $literalCollections= FeatureCategory::whereIn('code', $literalCodes)->get();
        $translations=[];
        foreach ($literalCollections as $literal) {
            $literalLanguages =$literal->getI18n();
            $translations[$literal->getCode()] =array_values($literalLanguages[$locale])[0];
        }


        return $translations;
    }
    /**
     * Function to get all features billed value for platforms list in a specific time period
     *
     * @param array $platformsIDs
     * @param int   $periodId
     *
     * @return array $result  ['platform_name' => [categories_dad =>[categories_son => value]],...]
     */
    public function getFeaturesBilled(array $platformsIDs, int $periodId):array
    {
        $platFeaturesBilled = PlatformsFeaturesBilled::with(['feature', 'platform', 'feature.category'])->whereIn('platform_id', $platformsIDs)->where('billing_period_id', $periodId)->get();
        $result=[];
        foreach ($platFeaturesBilled as $platFeatureBilled) {
            $feature = $platFeatureBilled->feature;
            $platform = $platFeatureBilled->platform;
            $featureDad = $feature->category;
            $result[$platform->name][$featureDad->code][$feature->code] = $platFeatureBilled->value;
        }

        return $result;
    }

    /**
     * Function to get all features stats value for platforms list in a specific time period
     *
     * @param array $platformsIDs
     * @param int   $periodId
     *
     * @return array $result  ['platform_name' => [categories_dad =>[categories_son => value]],...]
     */
    public function getFeaturesStats(array $platformsIDs, int $periodId):array
    {
        $platFeaturesStat = PlatformsFeaturesStat::with(['feature', 'platform', 'feature.category'])->whereIn('platform_id', $platformsIDs)->where('billing_period_id', $periodId)->get();
        $result=[];
        foreach ($platFeaturesStat as $platFeatureStat) {
            $feature = $platFeatureStat->feature;
            $platform = $platFeatureStat->platform;
            $featureDad = $feature->category;
            $result[$platform->name][$featureDad->code][$feature->code] = $platFeatureStat->value;
        }

        return $result;
    }
    /*
     * Check if the given code exists
     *
     * @param string $featureCode The featureCode to find
     *
     * @return bool Return true if exists
     */
    public function existsFeatureCode(string $featureCode):bool
    {
        $exists = Feature::where('code', $featureCode)->exists();

        return $exists;
    }

    /**
     * Determine if the given features codes exists or not
     *
     * @param array $featureCodes The codes list to check
     *
     * @return array array with elements like: "featuresCode" => true|false
     */
    public function existsFeaturesCodes(array $featureCodes):array
    {
        $existing = Feature::whereIn('code', $featureCodes)->pluck('code')->all();
        $return = [];
        \array_map(function ($featureCode) use (&$return, $existing) {
            $return[$featureCode] = \in_array($featureCode, $existing);
        }, $featureCodes);

        return $return;
    }

    /**
     * Create a new billing period
     *
     * @param string $periodDate Date of the period to be persisted
     *
     * @return void
     */
    public function createBillingPeriod(string $periodDate):void
    {
        $billingPeriod = [
            'billing_period_date' => $periodDate,
            'created_at' => Carbon::now()
        ];

        BillingPeriod::create($billingPeriod);
    }

    /**
     * Returns if the given period date exists or not
     *
     * @param string $periodDate The period date to check
     *
     * @return bool Returns true if the period exists, false otherwise
     */
    public function existsBillingPeriod(string $periodDate):bool
    {
        $exists = BillingPeriod::where('billing_period_date', $periodDate)->exists();

        return $exists;
    }

    /**
     * Function to store in the platform_feature_stats table the information of the feature by platform
     *
     * @param array $featureStats An array, key by platformId field, with the needed data to store the new record
     *
     * @return void
     */
    public function setFeaturesStatsByPlatforms(array $featureStats):void
    {
        $toInsert = [];
        foreach ($featureStats as $platformId => $statData) {
            $toInsert[] = [
                'platform_id' => $platformId,
                'feature_id' => $statData['feature_id'],
                'code' => $statData['code'],
                'value' => $statData['value'],
                'billing_period_id' => $statData['billing_period_id'],
                'created_at' => Carbon::now()
            ];
        }
        if (!empty($toInsert)) {
            PlatformsFeaturesStat::insert($toInsert);
        }
    }

    /**
     * Returns the id of the feature, by the feature code specified.
     *
     * @param string $featureCode The feature code to search the id
     *
     * @return int|null The feature id or null if no features was found
     */
    public function getIdFromFeatureCode(string $featureCode): ?int
    {
        $feature = Feature::where(['code' => $featureCode])->first();
        if (!$feature instanceof Feature) {
            return null;
        }

        return $feature->getKey();
    }
}
