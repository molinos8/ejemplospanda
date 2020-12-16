<?php

namespace Tests;

use \OKNManager\BM\Repositories\FeaturesRepository;
use \OKNManager\BM\Repositories\PlatformsRepository;
use \OKNManager\BM\Validators\Features\FeaturesValidator;
use \OKNManager\BM\Wrappers\BMValidationError;
use \OKNManager\Exceptions\ValidationException;
use App\BMFormatters\Requests\HttpJsonApi\ActionPostNormalizer;
use PHPUnit\Framework\TestCase;

/**
 * Test of the class FeaturesValidator, the base validator of the business model Features
 */
class FeaturesValidatorTest extends TestCase
{
    /**
     * The validator class to test
     *
     * @var FeaturesValidator
     */
    private $validator;

    public function setup()
    {
        $this->validator = $this->getMockForAbstractClass(FeaturesValidator::class);
    }

    /**
     * Check if class exists
     *
     * @return void
     */
    public function test_ClassExists()
    {
        $this->assertInstanceOf(\OKNManager\BM\Validators\Features\FeaturesValidator::class, $this->validator);
    }

    /**
     * Function to check if a missing feature code throws a validation exception
     *
     * @return void
     */
    public function test_checkFeatureCode_NotFoundFeatureCode_throwsValidationExceptionWithErrors()
    {
        $featureCode = 'testNotFound';
        $featureRepository = $this->getMockBuilder(FeaturesRepository::class)->getMock();
        $expected = new BMValidationError('999005001', 'Validation of Features failed', 'Feature code `testNotFound` not found', []);

        $featureRepository->expects($this->once())
                          ->method('existsFeatureCode')
                          ->will($this->returnValue(false));

        $result = $this->validator->checkFeatureCode($featureRepository, $featureCode);

        $this->assertEquals($result, $expected);
    }

    /**
     * Checks if the function checkFeatureCode works fine if a valid feature code is given
     *
     * @return void
     */
    public function test_checkFeatureCode_GivenAValidFeatureCode_notThrowsValidationException()
    {
        $featureCode = 'test';
        $featureRepository = $this->getMockBuilder(FeaturesRepository::class)->getMock();
        $featureRepository->expects($this->once())
                            ->method('existsFeatureCode')
                            ->will($this->returnValue(true));

        $result = $this->validator->checkFeatureCode($featureRepository, $featureCode);

        $this->assertNull($result, 'Unexpected result in checkFeatureCode function');
    }

    /**
     * Check if the function checkPlatformCode doen't throw a exception with valid inputs
     *
     * @return void
     */
    public function test_checkPlatformCode_GivenAValidPlatformCode_notThrowsAException()
    {
        $platformCode = 'test';
        $platformRepository = $this->getMockBuilder(PlatformsRepository::class)->getMock();
        $platformRepository->expects($this->once())
                            ->method('existsPlatformCode')
                            ->will($this->returnValue(true));

        $result = $this->validator->checkPlatformCode($platformRepository, $platformCode);

        $this->assertNull($result, 'Unexpected result in checkPlatformCode function');
    }

    /**
     * Checks if the function checkPlatformCode throws a validation exception if the code doesn't exists
     *
     * @return void
     */
    public function test_checkPlatformCode_GivenAInvalidPlatformCode_ReturnsAValidationError()
    {
        $platformCode = 'testNotFound';
        $expected = new BMValidationError('999005002', 'Validation of Features failed', 'Platform code `testNotFound` not found', []);
        $platformRepository = $this->getMockBuilder(PlatformsRepository::class)->getMock();
        $platformRepository->expects($this->once())
                            ->method('existsPlatformCode')
                            ->will($this->returnValue(false));

        $result = $this->validator->checkPlatformCode($platformRepository, $platformCode);

        $this->assertEquals($result, $expected);
    }

    /**
     * Test that given a valid array of featuresCodes the function checkFeaturesCodes returns an emtpy array
     *
     * @return void
     */
    public function test_checkFeaturesCodes_GivenAValidListOfFeaturesCodes_returnsEmtpyArray()
    {
        $featuresCodes = [
            'test',
            'test1',
            'test2',
            'test3'
        ];
        $repositoryResult = [
            'test' => true,
            'test1' => true,
            'test2' => true,
            'test3' => true
        ];
        //$expects = new BMValidationError('999005008', 'Validation of Features failed', 'Features codes not found', []);
        $featureRepository = $this->getMockBuilder(FeaturesRepository::class)->getMock();
        $featureRepository->expects($this->once())
                            ->method('existsFeaturesCodes')
                            ->will($this->returnValue($repositoryResult));

        $result = $this->validator->checkFeaturesCodes($featureRepository, $featuresCodes);

        $this->assertEmpty($result);
    }

    /**
     * Test that given a invalid array of featuresCodes the checkFeaturesCodes function returns an array of BMValidationErrors
     *
     * @return void
     */
    public function test_checkFeaturesCodes_GivenAInvalidListOfFeaturesCodes_returnsArrayOfValidationErrors()
    {
        $featuresCodes = [
            'test',
            'test1',
            'test2',
            'test3'
        ];
        $repositoryResult = [
            'test' => false,
            'test1' => false,
            'test2' => false,
            'test3' => false
        ];
        $expects = [
            new BMValidationError('999005001', 'Validation of Features failed', 'Feature code `test` not found', []),
            new BMValidationError('999005001', 'Validation of Features failed', 'Feature code `test1` not found', []),
            new BMValidationError('999005001', 'Validation of Features failed', 'Feature code `test2` not found', []),
            new BMValidationError('999005001', 'Validation of Features failed', 'Feature code `test3` not found', [])
        ];
        $featureRepository = $this->getMockBuilder(FeaturesRepository::class)->getMock();
        $featureRepository->expects($this->once())
                            ->method('existsFeaturesCodes')
                            ->will($this->returnValue($repositoryResult));

        $result = $this->validator->checkFeaturesCodes($featureRepository, $featuresCodes);

        $this->assertEquals($expects, $result);
    }

    /**
     * Test that given a valid input parameters the validate function returns doesn't throws any Exception
     *
     * @return void
     */
    public function test_validate_GivenValidArguments_notThrowsAValidationException()
    {
        $params = [
            'featureCode' => 'testNotFound',
            'sourceFormat' => 'inline',
            'sourceData' => [
                [
                    'test' => 'https://test.blob.core.windows.net/container/dir1/testfile.txt'
                ]
            ]
        ];
        $actionValidatorValue = [];
        $validAction = $this->setPostParams($params);

        $result = $this->validator->validate($validAction);

        $this->assertNull($result, 'Unexpected returned value of the validate function');
    }

    /**
     * Test that given a invalid action parameters, the function validate throws a ValidationException
     *
     * @return void
     */
    public function test_validate_GivenInvalidActionParams_ThrowsAValidationException()
    {
        $params = [
            'featureCode' => 'testNotFound',
            'sourceFormat' => 'inline',
            'sourceData' => [
                [
                    'test' => 'https://test.blob.core.windows.net/container/dir1/testfile.txt'
                ]
            ]
        ];
        $expectedValue = [
            new BMValidationError('999005008', 'Validation of Features failed', 'Feature code not found', [])
        ];
        $actionValidatorValue = [
            new BMValidationError('999005008', 'Validation of Features failed', 'Feature code not found', [])
        ];

        $validAction = $this->setPostParams($params);

        $this->validator->expects($this->once())
                        ->method('validateAction')
                        ->will($this->returnValue($actionValidatorValue));

        $result = null;
        try {
            $this->validator->validate($validAction);
        } catch (ValidationException $exception) {
            $result = $exception->getValidationErrors();
        }
        $this->assertEquals($result, $expectedValue);
    }

    /**
     * Function to check that checkPlatformCodes can check various platform codes
     *
     * @return void
     */
    public function test_checkPlatformCodes_GivenAValidListOfPlatformsCodes_returnsEmtpyArray()
    {
        $platformsCodes = [
            'test',
            'test1',
            'test2',
            'test3'
        ];
        $repositoryResult = [
            'test' => true,
            'test1' => true,
            'test2' => true,
            'test3' => true
        ];

        $platformRepository = $this->getMockBuilder(PlatformsRepository::class)->getMock();
        $platformRepository->expects($this->once())
                            ->method('existsPlatformCodes')
                            ->will($this->returnValue($repositoryResult));

        $result = $this->validator->checkPlatformCodes($platformRepository, $platformsCodes);

        $this->assertEmpty($result);
    }

    /**
     * Check if the function checkPlatformCodes returns an array of ValidationErrors when recieve a list of invalid platforms
     *
     * @return void
     */
    public function test_checkPlatformCodes_GivenAInvalidListOfPlatformCodes_returnsAnArrayOfValidationErrors()
    {
        $platformsCodes = [
            'test',
            'test1',
            'test2',
            'test3'
        ];
        $repositoryResult = [
            'test' => false,
            'test1' => false,
            'test2' => false,
            'test3' => false
        ];

        $expected = [
            new BMValidationError('999005002', 'Validation of Features failed', 'Platform code `test` not found', []),
            new BMValidationError('999005002', 'Validation of Features failed', 'Platform code `test1` not found', []),
            new BMValidationError('999005002', 'Validation of Features failed', 'Platform code `test2` not found', []),
            new BMValidationError('999005002', 'Validation of Features failed', 'Platform code `test3` not found', [])
        ];

        $platformRepository = $this->getMockBuilder(PlatformsRepository::class)->getMock();
        $platformRepository->expects($this->once())
                            ->method('existsPlatformCodes')
                            ->will($this->returnValue($repositoryResult));

        $result = $this->validator->checkPlatformCodes($platformRepository, $platformsCodes);

        $this->assertEquals($result, $expected);
    }

    /**
     * Helper to create a new ActionPostNormalizer fake, based on the given parameters
     *
     * @param array $params The params to add to the ActionPostNormalizer fake
     *
     * @return \App\BMFormatters\Requests\HttpJsonApi\ActionPostNormalizer The fake object created
     */
    private function setPostParams(array $params):ActionPostNormalizer
    {
        $validAction = new ActionPostNormalizer();
        $validAction->setActionName('setFeatureData');
        $validAction->setActionParams($params);

        return $validAction;
    }
}
