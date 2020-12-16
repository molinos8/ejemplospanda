<?php

namespace OKNManager\BM\Validators\Features;

use \OKNManager\BM\Wrappers\BMValidationError;
use \OKNManager\Exceptions\ValidationException;
use App\BMFormatters\Interfaces\IBMAction;
use OKNManager\BM\Repositories\FeaturesRepository;
use OKNManager\BM\Repositories\PlatformsRepository;
use OKNManager\BM\Validators\IValidator;

/**
 * Base Feature validator class
 */
abstract class FeaturesValidator implements IValidator
{

    /**
     * PhP understandable format for period
     *
     * @var string
     */
    const ACTUAL_PERIOD_FORMAT = 'Y-m';

    /**
     * Constant with the general Features codes available
     */
    const CODES = [
        'not_found_featureCode' => [
            'code' => '999005001',
            'title' => 'Validation of Features failed',
            'description' => 'Feature code `{{featureCode}}` not found'
        ],
        'not_found_platformCode' => [
            'code' => '999005002',
            'title' => 'Validation of Features failed',
            'description' => 'Platform code `{{platformCode}}` not found'
        ],
        'missing_field_platformCode' => [
            'code' => '999005008',
            'title' => 'Validation of Features failed',
            'description' => 'Missing field `platformCode`'
        ],
        'bad_formated_platformCode' => [
            'code' => '999005009',
            'title' => 'Validation of Features failed',
            'description' => '`platformCode` filed is bad formated, must be array of platform codes or `all` string'
        ],
        'missing_field_period' => [
            'code' => '999005010',
            'title' => 'Validation of Features failed',
            'description' => 'Missing field `period`'
        ],
        'bad_formated_period' => [
            'code' => '999005011',
            'title' => 'Validation of Features failed',
            'description' => '`period` filed is bad formated, must be '.self::ACTUAL_PERIOD_FORMAT.' format'
        ],
        'period_does_not_exists' => [
            'code' => '999005012',
            'title' => 'Validation of Features failed',
            'description' => '`period` does not exists'
        ],
        'invalid_email_format' => [
            'code' => '999005015',
            'title' => 'Validation of SendCostsReportss failed',
            'description' => 'Email `{{email}}` bad formated'
        ]
    ];

    /**
     * Function that execute the action validation
     *
     * @param \App\BMFormatters\Interfaces\IBMAction $actionParams The input params needed by the action validator
     *
     * @throws \OKNManager\Exceptions\ValidationException If there is some BMValidationError after the validateAction call
     *
     * @return void
     */
    public function validate(IBMAction $actionParams)
    {
        $results = $this->validateAction($actionParams);
        if (!empty($results)) {
            $exception = new ValidationException();
            $exception->setValidationErrors($results);
            throw $exception;
        }
    }


    /**
     * Checks if the featureCode parameter exists and is a valid code
     *
     * @param \OKNManager\BM\Repositories\FeaturesRepository $featuresRepo The Features repository
     * @param string                                         $actionParams The feature code to check
     *
     * @throws \App\Exceptions\ValidationException If the feature code params is not valid
     *
     * @return \OKNManager\BM\Wrappers\BMValidationError|null Returns a ValidationError object or null if there is no error
     */
    public function checkFeatureCode(FeaturesRepository $featuresRepo, string $featureCode): ?BMValidationError
    {
        $existsFeature = $featuresRepo->existsFeatureCode($featureCode);
        if (!$existsFeature) {
            $replace = [
                'search' => ['{{featureCode}}'],
                'replace' => [$featureCode]
            ];

            return $this->getValidationError('not_found_featureCode', $replace);
        }

        return null;
    }

    /**
     * Function to check if exists a platform code
     *
     * @param \OKNManager\BM\Repositories\PlatformsRepository $platformsRepo The platform repository to use
     * @param string                                          $platformCode  The platform code to check
     *
     * @return \OKNManager\BM\Wrappers\BMValidationError|null A BMValidationError if the platform doesn't exists, null if no errors was found
     */
    public function checkPlatformCode(PlatformsRepository $platformsRepo, string $platformCode): ?BMValidationError
    {
        $existsPlatform = $platformsRepo->existsPlatformCode($platformCode);
        if (!$existsPlatform) {
            $replaces = [
                'search' => ['{{platformCode}}'],
                'replace' => [$platformCode]
            ];

            return $this->getValidationError('not_found_platformCode', $replaces);
        }

        return null;
    }

    /**
     * Check the feature codes from the list
     *
     * @param \OKNManager\BM\Repositories\FeaturesRepository $featuresRepo Repository of the features
     * @param array                                          $featureCodes List of the codes
     *
     * @return array Array of BM Validation errors found
     */
    public function checkFeaturesCodes(FeaturesRepository $featuresRepo, array $featureCodes): array
    {
        $existsFeatures = $featuresRepo->existsFeaturesCodes($featureCodes);
        $errorsBag = [];
        foreach ($existsFeatures as $feature => $exists) {
            if (!$exists) {
                $replace = [
                    'search' => [
                        '{{featureCode}}'
                    ],
                    'replace' => [
                        $feature
                    ]
                ];
                $errorsBag[] = $this->getValidationError('not_found_featureCode', $replace);
            }
        }

        return $errorsBag;
    }

    /**
     * Check if the given platform codes exists
     *
     * @param \OKNManager\BM\Repositories\PlatformsRepository $platformsRepository Repository of the platforms
     * @param array                                           $platformCodes       The platform codes to check
     *
     * @return array Array of BM Validation errors found
     */
    public function checkPlatformCodes(PlatformsRepository $platformsRepository, array $platformCodes):array
    {
        $existsPlatforms = $platformsRepository->existsPlatformCodes($platformCodes);
        $errorsBag = [];
        foreach ($existsPlatforms as $platformCode => $exists) {
            if (!$exists) {
                $replace = [
                    'search' => [
                        '{{platformCode}}'
                    ],
                    'replace' => [
                        $platformCode
                    ]
                ];
                $errorsBag[] = $this->getValidationError('not_found_platformCode', $replace);
            }
        }

        return $errorsBag;
    }

    /**
     * Function to validate an action on the action validator
     *
     * @param IBMAction $actionParams The post parameters received
     *
     * @return array An array with the errors found (can be an empty array)
     */
    abstract protected function validateAction(IBMAction $actionParams):array;


    /**
     * Function to add new BMValidationErrors to an array of errors
     *
     * @param array  $errorsBag The errors list to populate
     * @param string $code      The error code to use in the BMValidationError
     *
     * @return void
     */
    protected function addValidationError(array &$errorsBag, string $code):void
    {
        $errorsBag[] = new BMValidationError(static::ACTION_CODES[$code]['code'], static::ACTION_CODES[$code]['title'], static::ACTION_CODES[$code]['description'], []);
    }

    /**
     * Function to check platforms codes.
     *
     * @param array $params
     * @param array $errorsBag
     *
     * @return void
     */
    protected function checkingPlatformCodes(array $params, array &$errorsBag)
    {
        if (empty($params['platformCode'])) {
            $errorsBag[] = $this->getValidationError('missing_field_platformCode');
        }
        if (isset($params['platformCode'])) {
            if (!is_array($params['platformCode']) && $params['platformCode'] !== 'all') {
                $errorsBag[] = $this->getValidationError('bad_formated_platformCode');
            }
            if (is_array($params['platformCode'])) {
                $errors = $this->checkPlatformCodes($this->platformRepository, $params['platformCode']);
                if ($errors) {
                    $errorsBag = array_merge($errors, $errorsBag);
                }
            }
        }
    }
    /**
     * function to validate date in base of a format.
     *
     * @param string $date
     * @param string $format
     *
     * @return bool is or not valid date
     */
    protected function validateDate(string $date, string $format):bool
    {
        $dateResult = \DateTime::createFromFormat($format, $date);

        return $dateResult && $dateResult->format($format) === $date;
    }

    /**
     * Method to test period in action params
     *
     * @param array $params
     * @param array $errorsBag
     *
     * @return void
     */
    protected function checkingPeriodParam(array $params, array &$errorsBag)
    {
        if (empty($params['period'])) {
            $errorsBag[] = $this->getValidationError('missing_field_period');
        }
        if (isset($params['period'])) {
            if (!$this->validateDate($params['period'], self::ACTUAL_PERIOD_FORMAT)) {
                $errorsBag[] = $this->getValidationError('bad_formated_period');
            }
            $error = $this->featureRepository->getBillingPeriodByDate($params['period']);
            if (!$error) {
                $errorsBag[] = $this->getValidationError('period_does_not_exists');
            }
        }
    }

    /**
     * Function to validate mails
     *
     * @param string $email
     *
     * @return bool
     */
    protected function isValidEmail(string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return true;
    }

    /**
     * Function to add new BMValidationErrors to an array of errors
     *
     * @param array  $errorsBag The errors list to populate
     * @param string $code      The error code to use in the BMValidationError
     *
     * @return void
     */
    protected function addActionValidationError(array &$errorsBag, string $code):void
    {
        $errorsBag[] = new BMValidationError($this::ACTION_CODES[$code]['code'], $this::ACTION_CODES[$code]['title'], $this::ACTION_CODES[$code]['description'], []);
    }

    /**
     * Add a validationError with placeholder replacements
     *
     * @param array  $errorsBag The errors list to populate
     * @param string $code      The error code to use in the BMValidationError
     * @param array  $replaces  An array with the search and replace values from the description of the code
     *
     * @return void
     */
    protected function addActionValidationErrorFormatted(array &$errorsBag, string $code, array $replaces = [])
    {
        $description = $this::ACTION_CODES[$code]['description'];
        if (isset($replaces['search']) && isset($replaces['replace'])) {
            $description = \str_replace($replaces['search'], $replaces['replace'], $this::ACTION_CODES[$code]['description']);
        }
        $errorsBag[] = new BMValidationError($this::ACTION_CODES[$code]['code'], $this::ACTION_CODES[$code]['title'], $description, []);
    }

    /**
     * Function to return a new Validation error based on a error code
     *
     * @param string $code The code error to generate the validation error
     *
     * @return \OKNManager\BM\Wrappers\BMValidationError The validation error generated
     */
    protected function getValidationError(string $code, array $replaces = []):BMValidationError
    {
        $description = self::CODES[$code]['description'];
        if (isset($replaces['search']) && isset($replaces['replace'])) {
            $description = \str_replace($replaces['search'], $replaces['replace'], self::CODES[$code]['description']);
        }

        return new BMValidationError(self::CODES[$code]['code'], self::CODES[$code]['title'], $description, []);
    }

    /**
     * Check if the given parameter is a positive integer value
     *
     * @param mixed $integer The varaible to check if it's a valid positive integer value
     *
     * @return bool return true if it's a valid int, false otherwise
     */
    protected function isPositiveInt($integer):bool
    {
        return is_numeric($integer) && $integer > 0 && $integer == round($integer);
    }
}
