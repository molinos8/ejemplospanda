<?php

namespace OKNManager\BM\Validators\Features\Actions;

use App\BMFormatters\Interfaces\IBMAction;
use OKNManager\BM\Repositories\FeaturesRepository;
use OKNManager\BM\Repositories\FilesRepository;
use OKNManager\BM\Repositories\PlatformsRepository;
use OKNManager\BM\Validators\Features\FeaturesValidator;
use OKNManager\Libraries\Files\FileManager;

/**
 * Class to validate the input data of setFeatureData action in the Features Business model
 */
class SetFeatureDataValidator extends FeaturesValidator
{
    /**
     * Constant with all the codes generated by the validation process
     */
    const ACTION_CODES = [
        'missing_field_featureCode' => [
            'code' => '999005003',
            'title' => 'Validation of setFeatureData failed',
            'description' => 'Missing field `featureCode`'
        ],
        'missing_field_sourceFormat' => [
            'code' => '999005004',
            'title' => 'Validation of setFeatureData failed',
            'description' => 'Missing field `sourceFormat`'
        ],
        'missing_field_sourceData' => [
            'code' => '999005005',
            'title' => 'Validation of setFeatureData failed',
            'description' => 'Missing field `sourceData`'
        ],
        'invalid_sourceFormat' => [
            'code' => '999005006',
            'title' => 'Validation of setFeatureData failed',
            'description' => 'Field `sourceFormat` is invalid'
        ],
        'invalid_sourceData' => [
            'code' => '999005007',
            'title' => 'Validation of setFeatureData failed',
            'description' => 'Field `sourceData` cannot be empty'
        ],
        'negativesStats_sourceData' => [
            'code' => '999005008',
            'title' => 'Validation of setFeatureData failed',
            'description' => 'Stats values cannot be negative'
        ],
        'invalid_sourceData_allAndCodes' => [
            'code' => '999005009',
            'title' => 'Validation of setFeatureData failed',
            'description' => 'Source data cannot have the key "all" and the platform codes key in the same action'
        ],
        'not_found_file_blob' => [
            'code' => '999005010',
            'title' => 'Validation of setFeatureData failed',
            'description' => 'File/s `{{files}}` not found.'
        ],
        'empty_file_blob' => [
            'code' => '999005011',
            'title' => 'Validation of setFeatureData failed',
            'description' => 'Files `{{files}}` are empty.'
        ],
        'missing_platforms' => [
            'code' => '999005012',
            'title' => 'Validation of setFeatureData failed',
            'description' => 'Missing platforms `{{platforms}}` in the file.'
        ],
        'invalid_platforms' => [
            'code' => '999005013',
            'title' => 'Validation of setFeatureData failed',
            'description' => 'Invalid platforms `{{platforms}}` in the file.'
        ],
        'invalid_blob_row_format' => [
            'code' => '999005014',
            'title' => 'Validation of setFeatureData failed',
            'description' => 'Incorrect row format type of blob csv'
        ]
    ];

    /**
     * Constant with the available sourceFormat values
     */
    const FEATURES_SOURCE_FORMATS = [
        'inline',
        'blob'
    ];

    /**
     * The Feature repository to use to check against the persistance service
     *
     * @var \OKNManager\BM\Repositories\FeaturesRepository
     */
    private $featureRepo;
    /**
     * The Platform repository to use to check against the persistance service
     *
     * @var \OKNManager\BM\Repositories\PlatformsRepository
     */
    private $platformRepo;
    /**
     * The File repository to use to check againts the persistance layer
     *
     * @var \OKNManager\BM\Repositories\FilesRepository
     */
    private $fileRepository;
    /**
     * The File manager library to manage blob files
     *
     * @var \OKNManager\Libraries\Files\FileManager
     */
    private $fileManager;

    /**
     * Initialize the setFeatureData validator
     *
     * @param \OKNManager\BM\Repositories\FeaturesRepository  $featureRepo  The feature repositories to work with
     * @param \OKNManager\BM\Repositories\PlatformsRepository $platformRepo The platform repositories to work with
     * @param \OKNManager\BM\Repositories\FilesRepository     $fileRepo     The file repository to work with
     * @param \OKNManager\Libraries\Files\FileManager         $fileManager  The file manager library
     */
    public function __construct(FeaturesRepository $featureRepo, PlatformsRepository $platformRepo, FilesRepository $fileRepo, FileManager $fileManager)
    {
        if (\method_exists(parent::class, '__construct')) {
            parent::__construct();
        }
        $this->featureRepo = $featureRepo;
        $this->platformRepo = $platformRepo;
        $this->fileRepository = $fileRepo;
        $this->fileManager = $fileManager;
    }

    /**
     * Function to validate the action of setFeatureData
     *
     * @param \App\BMFormatters\Interfaces\IBMAction $actionParams The post input received in the request
     *
     * @return array Returns a list of BMValidationErrors, empty if no errors was found
     */
    protected function validateAction(IBMAction $actionParams):array
    {
        $params = $actionParams->getActionParams();
        $errorsBag = [];

        $this->checkRequestFeatureCode($params, $errorsBag);
        $this->checkSourceFormat($params, $errorsBag);
        $this->checkSourceData($params, $errorsBag);

        return $errorsBag;
    }

    /**
     * Checks the Source data field received in the input parameters
     *
     * @param array $params    The parameters received
     * @param array $errorsBag The list of errors, by reference, to add new errors if needed
     *
     * @return void
     */
    private function checkSourceData(array $params, array &$errorsBag):void
    {
        if (!isset($params['sourceData'])) {
            $this->addActionValidationError($errorsBag, 'missing_field_sourceData');
        }
        if (isset($params['sourceData'])) {
            $sourceData = '';
            $sourceData = \array_filter($params['sourceData']);
            if (empty($sourceData)) {
                $this->addActionValidationError($errorsBag, 'invalid_sourceData');
            }
            if (!empty($sourceData)) {
                $platformsCodesRaw = array_keys($params['sourceData']);
                $platformsCodes = \preg_grep('/all/', $platformsCodesRaw, PREG_GREP_INVERT);
                if (!empty($platformsCodes)) {
                    if (isset($params['sourceData']['all'])) {
                        $this->addActionValidationError($errorsBag, 'invalid_sourceData_allAndCodes');
                    }
                    $errorsBag = array_merge($this->checkPlatformCodes($this->platformRepo, $platformsCodes), $errorsBag);
                }

                $this->checkSourceDataValues($params, $errorsBag);
            }
        }
    }
    /**
     * Check that the data inside SourceData element is valid
     *
     * @param array $params    The action paramaters
     * @param array $errorsBag The list of the errors of the validate action
     *
     * @return void
     */
    private function checkSourceDataValues(array $params, array &$errorsBag)
    {
        if (!empty($params['sourceFormat'])) {
            if ($params['sourceFormat'] === 'inline') {
                $this->checkSourceDataInlineNegativeNumbers($params['sourceData'], $errorsBag);
            }
            if ($params['sourceFormat'] === 'blob') {
                $this->checkSourceDataBlobFile($params['sourceData'], $errorsBag);
            }
        }
    }

    /**
     * Check if the files received in the source data element are valid and well formatted
     *
     * @param array $sourceData The action paramaters
     * @param array $errorsBag  The list of the errors of the validate action
     *
     * @return void
     */
    private function checkSourceDataBlobFile(array $sourceData, array &$errorsBag)
    {
        $filesNotFound = [];
        $emptyFiles = [];
        $missingFiles = [];
        $notFoundInFile = [];
        foreach ($sourceData as $code => $file) {
            $exists = $this->fileRepository->existsFileByUrl($file);
            if (!$exists) {
                $filesNotFound[] = $file;
                continue;
            }
            $fileData = $this->fileRepository->getFileByUrl($file);
            $fileContent = $this->fileManager->getFileContents($fileData['id']);
            if (empty($fileContent)) {
                $emptyFiles[] = $file;
                continue;
            }
            $platformsIds = $this->platformRepo->getPlatformsIdsByCodeOrAll('all');
            $platforms = array_keys($platformsIds);
            $platformsCsv = $this->getPlatformsFromCsvBlob($fileContent);
            if ($code == 'all') {
                $missingPlatforms = array_diff($platforms, $platformsCsv);
                if (!empty($missingPlatforms)) {
                    $missingFiles = array_merge($missingPlatforms, $missingFiles);
                    continue;
                }
            }
            $notFoundPlatforms = array_diff($platformsCsv, $platforms);
            if (!empty($notFoundPlatforms)) {
                $notFoundInFile = array_merge($notFoundPlatforms, $notFoundInFile);
                continue;
            }
            $this->checkBlobCsvFormat($fileContent, $errorsBag);
        }
        $this->validateBlobArray($filesNotFound, 'files', 'not_found_file_blob', $errorsBag);
        $this->validateBlobArray($emptyFiles, 'files', 'empty_file_blob', $errorsBag);
        $this->validateBlobArray($missingFiles, 'platforms', 'missing_platforms', $errorsBag);
        $this->validateBlobArray($notFoundInFile, 'platforms', 'invalid_platforms', $errorsBag);
    }

    /**
     * Function to check if the format of the csv in the blob file is a valid input
     *
     * @param string $fileContent The csv file content
     * @param array  $errorsBag   The list of all errors
     *
     * @return void
     */
    private function checkBlobCsvFormat(string $fileContent, array &$errorsBag)
    {
        $error = false;
        $rows = explode("\n", $fileContent);
        $data = [];
        foreach ($rows as $row) {
            $rowArray = \str_getcsv($row, ';');
            if (count($rowArray) > 3) {
                $error = true;
                break;
            }
            if (\is_string($rowArray[0]) === false || \is_string($rowArray[1]) === false || $this->isPositiveInt($rowArray[2]) === false) {
                $error = true;
                break;
            }
        }
        if ($error !== false) {
            $this->addActionValidationError($errorsBag, 'invalid_blob_row_format');
        }

        return $data;
    }

    /**
     * Validate that blob array is empty or not. If not, add a validation error
     *
     * @param array  $toValidate  The array to validate
     * @param string $placeholder The placeholder to replace in the error description if there is som error
     * @param string $code        The code of the error to add if the array is not empty
     * @param array  $errorsBag   The bag of errors to add new validation errors if needed
     *
     * @return void
     */
    private function validateBlobArray(array $toValidate, string $placeholder, string $code, array &$errorsBag)
    {
        if (!empty($toValidate)) {
            $replaces = [
                'search' => ['{{'.$placeholder.'}}'],
                'replace' => [implode(',', $toValidate)]
            ];
            $this->addActionValidationErrorFormatted($errorsBag, $code, $replaces);
        }
    }

    /**
     * Get all the platform codes from a csv file content
     *
     * @param string $csvContent The csv content of the file to get the platforms
     *
     * @return array The list of platforms codes of the file
     */
    private function getPlatformsFromCsvBlob(string $csvContent):array
    {
        $rows = explode("\n", $csvContent);
        $platforms = [];
        foreach ($rows as $row) {
            list($platform) = \str_getcsv($row, ';');
            $platforms[] = $platform;
        }

        return array_unique($platforms);
    }

    /**
     * Checks if in source data of inline format the stats received are negativs numbers
     *
     * @param array $params    The parameters received
     * @param array $errorsBag The errors bag to add the possible validation errors
     *
     * @return void
     */
    private function checkSourceDataInlineNegativeNumbers(array $data, array &$errorsBag)
    {
        $valid = true;
        foreach ($data as $statValue) {
            if (!is_array($statValue) && $statValue < 0) {
                $valid = false;
            }
            if (is_array($statValue) && min($statValue) < 0) {
                $valid = false;
            }
        }
        if ($valid == false) {
            $this->addActionValidationError($errorsBag, 'negativesStats_sourceData');
        }
    }

    /**
     * Checks the Source format field received in the input parameters
     *
     * @param array $params    The parameters received
     * @param array $errorsBag The list of errors, by reference, to add new errors if needed
     *
     * @return void
     */
    private function checkSourceFormat(array $params, array &$errorsBag):void
    {
        if (empty($params['sourceFormat'])) {
            $this->addActionValidationError($errorsBag, 'missing_field_sourceFormat');
        }

        if (isset($params['sourceFormat']) && !in_array($params['sourceFormat'], self::FEATURES_SOURCE_FORMATS)) {
            $this->addActionValidationError($errorsBag, 'invalid_sourceFormat');
        }
    }

    /**
     * Checks the feature code field received in the input parameters
     *
     * @param array $params    The parameters received
     * @param array $errorsBag The list of errors, by reference, to add new errors if needed
     *
     * @return void
     */
    private function checkRequestFeatureCode(array $params, array &$errorsBag):void
    {
        if (empty($params['featureCode'])) {
            $this->addActionValidationError($errorsBag, 'missing_field_featureCode');
        }

        if (isset($params['featureCode'])) {
            $validateResult = $this->checkFeatureCode($this->featureRepo, $params['featureCode']);
            if ($validateResult) {
                $errorsBag[] = $validateResult;
            }
        }
    }
}
