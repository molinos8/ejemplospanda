<?php

namespace Tests;

use OKNManager\BM\Repositories\FeaturesRepository;
use Tests\Testbase\LaravelTestCase;

/**
 * Features repository test
 */
class FeaturesRepositoryTest extends LaravelTestCase
{

    /**
     * Fake valid period string.
     *
     * @var string
     */
    protected $period = '2018-12';

    /**
     * Repository object
     *
     * @var OKNManager\BM\Repositories\FeaturesRepository
     */
    private $repository;

    /**
     * class setup
     *
     * @return void
     */
    public function setup()
    {
        parent::setUp();
        $this->repository = new FeaturesRepository();
    }
    /**
     * test FeaturesRepository Try to instance class class exist and can be instanced
     *
     * @return void
     */
    public function test_FeaturesRepository_TryToInstanceClass_ClassExistAndCanBeInstanced()
    {
        $this->assertInstanceOf(FeaturesRepository::class, $this->repository, 'Class FeaturesRepository does not exist');
    }



    /**
     * Test getBillingPeriodByDate gets valid date return billing period id.
     *
     * @return void
     */
    public function test_getBillingPeriodByDate_GetsValidDate_ReturnBillingPeriodId()
    {
        $billingPeriod= factory(\App\Models\BillingPeriod::class)->create(['billing_period_date' => $this->period]);
        $billingPeriodId = $this->repository->getBillingPeriodByDate($this->period);
        $this->assertEquals($billingPeriodId, $billingPeriod->id);
    }

    /**
     * Test getPlatformDataForBillingReport gets platforms codes return valid data.
     *
     * @return void
     */
    public function test_getPlatformDataForBillingReport_getsPlatformsCodes_ReturnValidData()
    {
        $platformsCodes=['okn_fake3', 'okn_fake4'];
        $platform1 = factory(\App\Models\Platform::class)->create(['code' =>'okn_fake3', 'name' => 'pepe', 'storage' => 20, 'estimated_users'=>30]);
        $platform2 = factory(\App\Models\Platform::class)->create(['code' =>'okn_fake4', 'name' => 'pepa', 'storage' => 25, 'estimated_users'=>35]);
        $expectedResult=[$platform1->id =>['name'=>'pepe', 'storage' => 20, 'estimated_users'=>30], $platform2->id => ['name' => 'pepa', 'storage' => 25, 'estimated_users'=>35]];
        $realResult = $this->repository->getPlatformDataForBillingReport($platformsCodes);
        $this->assertEquals($realResult, $expectedResult);
    }

    /**
     * Test getReportsLiterals get literals codes returns literals translates.
     *
     * @return void
     */
    public function test_getReportsLiterals_getLiteralsCodes_ReturnsLiteralsTranslates()
    {
        $entityType = 'App\Models\Literal';
        $literal = factory(\App\Models\Literal::class)->create(['code'=>'fake-code']);
        $literal2 = factory(\App\Models\Literal::class)->create(['code'=>'fake-code2']);
        $this->setTranslationsBasics($literal, $literal2, $entityType);
        $this->literalsResults = $this->repository->getReportsLiterals($this->literalFakeCodes, $this->locale);
        $this->assertEquals($this->expectedArray, $this->literalsResults);
    }

    /**
     * Test getFatherCategories translates get categories codes returns categories translates.
     *
     * @return void
     */
    public function test_getFatherCategoriesTranslates_getCategoriesCodes_returnsCategoriesTranslates()
    {
        $entityType = 'App\Models\FeatureCategory';
        $literal = factory(\App\Models\FeatureCategory::class)->create(['code'=>'fake-code']);
        $literal2 = factory(\App\Models\FeatureCategory::class)->create(['code'=>'fake-code2']);
        $this->setTranslationsBasics($literal, $literal2, $entityType);
        $literalsResults = $this->repository->getFatherCategoriesTranslates($this->literalFakeCodes, $this->locale);
        $this->assertEquals($this->expectedArray, $literalsResults);
    }

    /**
     * Test getFeaturesBilled gets platforms and billed period return correct data.
     *
     * @return void
     */
    public function test_getFeaturesBilled_GetsPlatformsAndBilledPeriod_ReturnCorrectData()
    {
        $this->createTwoPlatformsFromFactory();
        $this->createFourFeatureSonWithDaddies();
        $this->createBillingPeriodFromFactory();


        $featureBilled1Son1 = factory(\App\Models\PlatformsFeaturesBilled::class)
            ->create(['platform_id' => $this->platform1->id, 'feature_id' => $this->featureSon->id, 'billing_period_id'=> $this->billingPeriod->id]);
        $featureBilled1Son2 = factory(\App\Models\PlatformsFeaturesBilled::class)
            ->create(['platform_id' => $this->platform1->id, 'feature_id' => $this->featureSon2->id, 'billing_period_id'=> $this->billingPeriod->id]);
        $featureBilled1Son3 = factory(\App\Models\PlatformsFeaturesBilled::class)
            ->create(['platform_id' => $this->platform1->id, 'feature_id' => $this->featureSon3->id, 'billing_period_id'=> $this->billingPeriod->id]);
        $featureBilled1Son4 = factory(\App\Models\PlatformsFeaturesBilled::class)
            ->create(['platform_id' => $this->platform1->id, 'feature_id' => $this->featureSon4->id, 'billing_period_id'=> $this->billingPeriod->id]);


        $featureBilled2Son1 = factory(\App\Models\PlatformsFeaturesBilled::class)
            ->create(['platform_id' => $this->platform2->id, 'feature_id' => $this->featureSon->id, 'billing_period_id'=> $this->billingPeriod->id]);
        $featureBilled2son2 = factory(\App\Models\PlatformsFeaturesBilled::class)
            ->create(['platform_id' => $this->platform2->id, 'feature_id' => $this->featureSon2->id, 'billing_period_id'=> $this->billingPeriod->id]);
        $featureBilled2Son3 = factory(\App\Models\PlatformsFeaturesBilled::class)
            ->create(['platform_id' => $this->platform2->id, 'feature_id' => $this->featureSon3->id, 'billing_period_id'=> $this->billingPeriod->id]);
        $featureBilled2Son4 = factory(\App\Models\PlatformsFeaturesBilled::class)
            ->create(['platform_id' => $this->platform2->id, 'feature_id' => $this->featureSon4->id, 'billing_period_id'=> $this->billingPeriod->id]);

        $platformsIDs = [$this->platform1->id, $this->platform2->id];
        $platform1Array = [$this->featureDad1->code=>[
                                $this->featureSon->code =>$featureBilled1Son1->value,
                                $this->featureSon2->code =>$featureBilled1Son2->value
                            ],
                            $this->featureDad2->code=>[
                                $this->featureSon3->code =>$featureBilled1Son3->value,
                                $this->featureSon4->code =>$featureBilled1Son4->value
                            ]];
        $platform2Array = [$this->featureDad1->code=>[
                                $this->featureSon->code =>$featureBilled2Son1->value,
                                $this->featureSon2->code =>$featureBilled2son2->value
                            ],
                            $this->featureDad2->code=>[
                                $this->featureSon3->code =>$featureBilled2Son3->value,
                                $this->featureSon4->code =>$featureBilled2Son4->value
                            ]];
        $expectedArray=[$this->platform1->name =>$platform1Array, $this->platform2->name=> $platform2Array];
        $result= $this->repository->getFeaturesBilled($platformsIDs, $this->billingPeriod->id);

        $this->assertEquals($result, $expectedArray);
    }

    /**
     * Test getFeaturesStats gets platforms and billed period return correct data.
     *\App\Models\Literal
     *
     * @return void
     */
    public function test_getFeaturesStats__GetsPlatformsAndBilledPeriod_ReturnCorrectData()
    {
        $this->createTwoPlatformsFromFactory();
        $this->createFourFeatureSonWithDaddies();
        $this->createBillingPeriodFromFactory();

        $featureStat1Son1 = factory(\App\Models\PlatformsFeaturesStat::class)
            ->create(['platform_id' => $this->platform1->id, 'feature_id' => $this->featureSon->id, 'billing_period_id'=> $this->billingPeriod->id]);
        $featureStat1Son2 = factory(\App\Models\PlatformsFeaturesStat::class)
            ->create(['platform_id' => $this->platform1->id, 'feature_id' => $this->featureSon2->id, 'billing_period_id'=> $this->billingPeriod->id]);
        $featureStat1Son3 = factory(\App\Models\PlatformsFeaturesStat::class)
            ->create(['platform_id' => $this->platform1->id, 'feature_id' => $this->featureSon3->id, 'billing_period_id'=> $this->billingPeriod->id]);
        $featureStat1Son4 = factory(\App\Models\PlatformsFeaturesStat::class)
            ->create(['platform_id' => $this->platform1->id, 'feature_id' => $this->featureSon4->id, 'billing_period_id'=> $this->billingPeriod->id]);


        $featureStat2Son1 = factory(\App\Models\PlatformsFeaturesStat::class)
            ->create(['platform_id' => $this->platform2->id, 'feature_id' => $this->featureSon->id, 'billing_period_id'=> $this->billingPeriod->id]);
        $featureStat2son2 = factory(\App\Models\PlatformsFeaturesStat::class)
            ->create(['platform_id' => $this->platform2->id, 'feature_id' => $this->featureSon2->id, 'billing_period_id'=> $this->billingPeriod->id]);
        $featureStat2Son3 = factory(\App\Models\PlatformsFeaturesStat::class)
            ->create(['platform_id' => $this->platform2->id, 'feature_id' => $this->featureSon3->id, 'billing_period_id'=> $this->billingPeriod->id]);
        $featureStat2Son4 = factory(\App\Models\PlatformsFeaturesStat::class)
            ->create(['platform_id' => $this->platform2->id, 'feature_id' => $this->featureSon4->id, 'billing_period_id'=> $this->billingPeriod->id]);

        $platformsIDs = [$this->platform1->id, $this->platform2->id];
        $platform1Array = [$this->featureDad1->code=>[
                                $this->featureSon->code =>$featureStat1Son1->value,
                                $this->featureSon2->code =>$featureStat1Son2->value
                            ],
                            $this->featureDad2->code=>[
                                $this->featureSon3->code =>$featureStat1Son3->value,
                                $this->featureSon4->code =>$featureStat1Son4->value
                            ]];
        $platform2Array = [$this->featureDad1->code=>[
                                $this->featureSon->code =>$featureStat2Son1->value,
                                $this->featureSon2->code =>$featureStat2son2->value
                            ],
                            $this->featureDad2->code=>[
                                $this->featureSon3->code =>$featureStat2Son3->value,
                                $this->featureSon4->code =>$featureStat2Son4->value
                            ]];
        $expectedArray=[$this->platform1->name =>$platform1Array, $this->platform2->name=> $platform2Array];
        $result= $this->repository->getFeaturesStats($platformsIDs, $this->billingPeriod->id);

        $this->assertEquals($result, $expectedArray);
    }

    /**
     * Create two platforms from factory.
     *
     * @return void
     */
    public function createTwoPlatformsFromFactory()
    {
        $this->platform1 = factory(\App\Models\Platform::class)->create();
        $this->platform2 = factory(\App\Models\Platform::class)->create();
    }

    /**
     * Create two features dad from factory.
     *
     * @return void
     */
    public function createTwoFeaturesDadFromFactory()
    {
        $this->featureDad1 = factory(\App\Models\FeatureCategory::class)->create();
        $this->featureDad2 = factory(\App\Models\FeatureCategory::class)->create();
    }

    /**
     * Create four feature son with daddies.
     *
     * @return void
     */
    public function createFourFeatureSonWithDaddies()
    {
        $this->createTwoFeaturesDadFromFactory();
        $this->featureSon = factory(\App\Models\Feature::class)->create(['feature_category_id'=>$this->featureDad1->id ]);
        $this->featureSon2 = factory(\App\Models\Feature::class)->create(['feature_category_id'=>$this->featureDad1->id ]);
        $this->featureSon3 = factory(\App\Models\Feature::class)->create(['feature_category_id'=>$this->featureDad2->id ]);
        $this->featureSon4 = factory(\App\Models\Feature::class)->create(['feature_category_id'=>$this->featureDad2->id ]);
    }

    /**
     * Create billing period from factor.
     *
     * @return void
     */
    public function createBillingPeriodFromFactory()
    {
        $this->billingPeriod = factory(\App\Models\BillingPeriod::class)->create(['billing_period_date' => $this->period]);
    }

    /**
     * Set translations basics
     *
     * @param mixed $literal
     * @param mixed $literal2
     * @param string$entityType
     *
     * @return void
     */
    public function setTranslationsBasics($literal, $literal2, string $entityType)
    {
        $this->locale= 'es_ES';
        $this->literalFakeCodes  = ['fake-code', 'fake-code2'];
        $this->expectedLiterals = ['fake-text', 'fake-text2'];
        $this->expectedArray = ['fake-code' => 'fake-text', 'fake-code2' => 'fake-text2'];
        $this->translation = factory(\App\Models\Translation::class)->create(['entity_id' => $literal->id, 'entity_type' =>$entityType]);
        $this->translation2 = factory(\App\Models\Translation::class)->create(['entity_id' => $literal2->id, 'entity_type' =>$entityType]);
        $this->translationFields = factory(\App\Models\TranslationField::class)->create(['code' =>'name', 'translation_id'=>$this->translation->id]);
        $this->translationFields2 = factory(\App\Models\TranslationField::class)->create(['code' =>'name', 'translation_id'=>$this->translation2->id]);
        factory(\App\Models\TranslationFieldI18n::class)->create(['field_id' => $this->translationFields->id, 'translation' =>$this->expectedLiterals[0]]);
        factory(\App\Models\TranslationFieldI18n::class)->create(['field_id' => $this->translationFields2->id, 'translation' =>$this->expectedLiterals[1]]);
    }

    /**
     * Checks if the class FeaturesCategory exists
     *
     * @return void
     */
    public function test_CheckIfClassExists()
    {
        $this->repository = new FeaturesRepository();
        $this->assertInstanceOf(\OKNManager\BM\Repositories\FeaturesRepository::class, $this->repository);
    }

    /**
     * Checks if the existsFeatureCode function returns true if the featurecode recieved does exists
     *
     * @return void
     */
    public function test_existsFeatureCode_givenAValidFeatureCode_returnsTrue()
    {
        // Arrange
        $code = 'test';
        factory(\App\Models\Feature::class)->create([
            'code' => $code
        ]);

        $result = $this->repository->existsFeatureCode($code);

        $this->assertTrue($result);
    }

    /**
     * Checks if the existsFeatureCode function returns false if the featurecode recieved doe not exists.
     *
     * @return void
     */
    public function test_existsFeatureCode_givenAnInvalidFeatureCode_returnsFalse()
    {
        $code = 'testNotFound';

        $result = $this->repository->existsFeatureCode($code);

        $this->assertFalse($result);
    }

    /**
     * Check if the function existsFeaturesCodes returns a valid array of true bools by code recieving a valid list of features codes
     *
     * @return void
     */
    public function test_existsFeaturesCodes_givenAValidFeatureCodesList_returnsArrayWithBoolsToTrue()
    {
        $codes = [
            'test',
            'test2',
            'test3'
        ];
        $expects = [
            'test' => true,
            'test2' => true,
            'test3' => true
        ];
        \array_map(function ($item) {
            factory(\App\Models\Feature::class)->create([
                'code' => $item
            ]);
        }, $codes);


        $result = $this->repository->existsFeaturesCodes($codes);

        $this->assertEquals($result, $expects);
    }

    /**
     * Check if the function existsFeaturesCodes returns a valid array of false elements by code, recieving a invalid list of features codes
     *
     * @return void
     */
    public function test_existsFeaturesCodes_givenAInvalidFeatureCodesList_returnsArrayWithBoolsToFalse()
    {
        $codes = [
            'testNotFound',
            'test2NotFound',
            'test3NotFound'
        ];
        $expects = [
            'testNotFound' => false,
            'test2NotFound' => false,
            'test3NotFound' => false
        ];

        $result = $this->repository->existsFeaturesCodes($codes);

        $this->assertEquals($result, $expects);
    }

    /**
     * Test that the functions existsBillingPeriod returns false if the recieved period date doesn't exists
     *
     * @return void
     */
    public function test_existsBillingPeriod_recievingANonExistingPeriod_returnsFalse()
    {
        $period = '2019-01';

        $result = $this->repository->existsBillingPeriod($period);

        $this->assertFalse($result);
    }

    /**
     * Test that createBillingPeriod function create a new billing period and is stored correctly
     *
     * @return void
     */
    public function test_createBillingPeriod_recievingValidParameters_storeNewBillingPeriod()
    {
        $periodDate = '2019-01';

        $result = $this->repository->createBillingPeriod($periodDate);
        $exists = $this->repository->existsBillingPeriod($periodDate);

        $this->assertNull($result);
        $this->assertTrue($exists);
    }

    /**
     * Test that the function setFeaturesStatsByyPlatforms store the data recieved
     *
     * @return void
     */
    public function test_setFeaturesStatsByPlatforms_GivenValidParameters_StoreTheData()
    {
        // arrange
        $code = 'test';
        $feature = factory(\App\Models\Feature::class)->create([
            'code' => $code
        ]);
        $platform = factory(\App\Models\Platform::class)->create();
        $billingPeriod = factory(\App\Models\BillingPeriod::class)->create();
        
        $featureData[$platform->getKey()] = [
            'feature_id' => $feature->getKey(),
            'code' => 'test',
            'value' => 100,
            'billing_period_id' => $billingPeriod->getKey(),
        ];
        $expected = $featureData[$platform->getKey()];
        $expected['platform_id'] = $platform->getKey();

        // action
        $result = $this->repository->setFeaturesStatsByPlatforms($featureData);
        
        $this->assertNull($result);
        $this->assertCustomDatabaseHas('platforms_features_stats', $expected);
    }


    /**
     * Test that getIdFromFeatureCode returns the id of the feature code specified
     *
     * @return void
     */
    public function test_getIdFromFeatureCode_GivenAFeatureCode_returnsTheCorrespondingId()
    {
        $code = 'test';
        $feature = factory(\App\Models\Feature::class)->create([
            'code' => $code
        ]);

        $result = $this->repository->getIdFromFeatureCode($code);

        $this->assertEquals($feature->getKey(), $result);
    }
}
