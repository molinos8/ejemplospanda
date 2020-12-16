<?php

namespace Tests;

use OKNManager\BM\Repositories\PlatformsRepository;
use Tests\Testbase\LaravelTestCase;

/**
 * Class to test the PlatformsRepository class
 */
class PlatformsRepositoryTest extends LaravelTestCase
{
    private $repository;

    public function setup()
    {
        parent::setup();
        $this->repository = new PlatformsRepository();
    }

    /**
     * Checks if the class FeaturesCategory exists
     *
     * @return void
     */
    public function test_CheckIfClassExists()
    {
        $this->repository = new PlatformsRepository();
        $this->assertInstanceOf(\OKNManager\BM\Repositories\PlatformsRepository::class, $this->repository);
    }

    /**
     * Checks if the existsPlatformCode function returns true if the featurecode recieved does exists
     *
     * @return void
     */
    public function test_existsPlatformCode_givenAValidPlatformCode_returnsTrue()
    {
        $code = 'test';
        factory(\App\Models\Platform::class)->create([
            'code' => $code
        ]);

        $result = $this->repository->existsPlatformCode($code);

        $this->assertTrue($result);
    }

    /**
     * Check if the function existsPlatformCode returns false when a invalid platform code is recieved in the input parameters
     *
     * @return void
     */
    public function test_existsPlatformCode_givenAInvalidPlatformCode_returnsFalse()
    {
        $code = 'testNotFound';

        $result = $this->repository->existsPlatformCode($code);

        $this->assertFalse($result);
    }

    /**
     * Check if the existsPlatformCodes function returns the correct array of results (all true) on valid items
     *
     * @return void
     */
    public function test_existsPlatformCodes_givenAValidPlatformCode_returnsArrayOfExistingResult()
    {
        $expected = [
            'test0' => true,
            'test1' => true,
            'test2' => true,
            'test3' => true
        ];
        $codes = [
            'test0',
            'test1',
            'test2',
            'test3'
        ];
        \array_map(function ($item) {
            factory(\App\Models\Platform::class)->create([
                'code' => $item
            ]);
        }, $codes);


        $result = $this->repository->existsPlatformCodes($codes);

        $this->assertSame($result, $expected);
    }

    /**
     * Check that the function existsPlatformCodes, when recieving a invalid input parameters, returns an associative array of false (by platformCode)
     *
     * @return void
     */
    public function test_existsPlatformCodes_givenAInvalidPlatformCode_returnsArrayOfNonExistingResult()
    {
        $codes = [
            'test0',
            'test1',
            'test2',
            'test3'
        ];
        $expected = [
            'test0' => false,
            'test1' => false,
            'test2' => false,
            'test3' => false
        ];

        $result = $this->repository->existsPlatformCodes($codes);

        $this->assertSame($result, $expected);
    }

    /**
     * Check if existsPlatformCodes returns correct information when an array of valids and invalids codes is given.
     *
     * @return void
     */
    public function test_existsPlatformCodes_givenAMixedPlatformCodes_returnsArrayOfExistingResult()
    {
        $expected = [
            'test0' => true,
            'test1' => true,
            'test2' => false,
            'test3' => false
        ];
        $codes = [
            'test0',
            'test1',
            'test2',
            'test3'
        ];
        $existingCodes = [
            'test0',
            'test1'
        ];
        \array_map(function ($item) {
            factory(\App\Models\Platform::class)->create([
                'code' => $item
            ]);
        }, $existingCodes);


        $result = $this->repository->existsPlatformCodes($codes);

        $this->assertSame($result, $expected);
    }
    /**
     * test getPlatformsIdsByCodeOrAll  Gets codes array returns id's
     *
     * @return void
     */
    public function test_getPlatformsIdsByCodeOrAll_GetsCodesArray_ReturnsIds()
    {
        $idsArray = [];
        $platformsCodes = ['okn_fake', 'okn_fake2'];
        $platform1 = factory(\App\Models\Platform::class)->create(['code' =>'okn_fake']);
        $platform2 = factory(\App\Models\Platform::class)->create(['code' =>'okn_fake2']);
        $idsArray['okn_fake'] = $platform1->id;
        $idsArray['okn_fake2'] = $platform2->id;
        $resultArray = $this->repository->getPlatformsIdsByCodeOrAll($platformsCodes);
        $this->assertEquals($resultArray, $idsArray);
    }

    /**
     * test getPlatformsIdsByCodeOrAll gets all string return all ids
     *
     * @return void
     */
    public function test_getPlatformsIdsByCodeOrAll_GetsAllString_ReturnAllIds()
    {
        factory(\App\Models\Platform::class)->create();
        $total = \App\Models\Platform::all()->count();
        $resultArray = $this->repository->getPlatformsIdsByCodeOrAll('all');
        $this->assertEquals($total, count($resultArray));
    }
}
