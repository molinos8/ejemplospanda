<?php

namespace OKNManager\BM;

use App\BMFormatters\Interfaces\IBMAction;
use App\BMFormatters\Interfaces\IModels;
use App\BMFormatters\Requests\HttpJsonApi\ActionPostNormalizer;
use OKNManager\BM\Repositories\FeaturesRepository;
use OKNManager\BM\Repositories\FilesRepository;
use OKNManager\BM\Repositories\PlatformsRepository;
use OKNManager\BM\Wrappers\BMActionError;
use OKNManager\BM\Wrappers\BMActionOk;
use OKNManager\Libraries\Exceptions\FileManagerException;
use OKNManager\Libraries\Files\FileManager;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 *  Features represents ...
 */
class Features implements IModels
{

    /**
     * Report format extension.
     *
     * @var string
     */
    const REPORT_EXTENSION = '.xlsx';

    /**
     * Billing size format.
     *
     * @var string
     */
    const BILLING_SIZE_FORMAT = '#,##0.00_-"MB"';

    /**
     * Especial period date format.
     */
    const FORMAT_DATE_YYYYMM = 'yyyy-mm';

    /**
     * Cant persists message.
     *
     * @var string
     */
    const CANT_PERSISTS = "Can't persist report.";

    /**
     * File not found message
     *
     * @var string
     */
    const FILE_NOT_FOUND = 'File not found.';

    /**
     * Platform default name
     *
     * @var string
     */
    const PLATFORM_NAME = 'OKNLearning';


    /**
     * Text for BMActionError data not found.
     *
     * @var string
     */
    const DATA_NOT_FOUND = 'Data not found';

    /**
     * Not valid spreadsheet error message.
     *
     * @var string
     */
    const NOT_VALID_SPREADSHEET = 'No valid spreadsheet generated.';


    /**
     * Features categories translations codes.
     *
     * @var array
     */
    const CATEGORIES_TRANSLATES =['users', 'storage'];

    /**
     * Report literals translations codes
     *
     * @var array
     */
    const REPORT_LITERALS = ['features-report-periodo', 'features-report-total-contratado', 'features-report-coste', 'features-report-total',
        'features-report-total-coste', 'features-report-backups', 'features-report-coste-servicio', 'features-report-activos', 'features-report-inactivos',
        'features-report-borrados', 'features-report-base-de-datos', 'features-report-contenido'];


    /**
     * Status codes
     *
     * @var array
     */
    const STATUS_CODES = [
        'setFeaturedDataOk' => '000005000',
        'cantPersistsError' => '004005000',
        'fileNotFound' => '000005001',
        'periodNotFound' => '002005001',
        'billedDataNotFound' => '002005000',
        'billedStatNotFound' => '002005002',
        'spreadSheetCreation' => '000005002',
        'generateCostsReportsOk' => '000005003'
    ];

    /**
     * Status code number, unique for each Business model
     *
     * @var string
     */
    public $bModelIndex = '005';

    /**
     * Id of the Business Model
     *
     * @var int
     */
    private $entityId;

    /**
     * Name of the Business Model
     *
     * @var string
     */
    private $entityType = 'Features';

    /**
     * The features repository
     *
     * @var \OKNManager\BM\Repositories\FeaturesRepository
     */
    private $repository;

    /**
     * The file manager library to manage features files
     *
     * @var \OKNManager\Libraries\Files\FileManager
     */
    private $fileManager;

    /**
     * The platforms repository to query the persistance layer
     *
     * @var \OKNManager\BM\Repositories\PlatformsRepository
     */
    private $platformRepository;

    /**
     * The files repository
     *
     * @var \OKNManager\BM\Repositories\FilesRepository
     */
    private $fileRepository;

    /**
     * Build a new Features Business model
     *
     * @param \OKNManager\BM\Repositories\FeaturesRepository  $repository         The feature repository, to query the persistance layer
     * @param \OKNManager\BM\Repositories\PlatformsRepository $platformRepository The platform repository, to query the persistance layer
     * @param \OKNManager\Libraries\Files\FileManager         $fileManager        The file manager library
     * @param \OKNManager\BM\Repositories\FilesRepository     $fileRepository     The file repository, to query the persistance layer
     */
    public function __construct(FeaturesRepository $repository, PlatformsRepository $platformRepository, FileManager $fileManager, FilesRepository $fileRepository)
    {
        $this->repository = $repository;
        $this->fileManager = $fileManager;
        $this->platformRepository = $platformRepository;
        $this->fileRepository = $fileRepository;
    }

    /**
     * Return the main id of the Features model
     *
     * @return int
     */
    public function getId():int
    {
        return $this->entityId;
    }

    /**
     * Returns the type of the Business model.
     *
     * @return string
     */
    public function getType():string
    {
        return $this->entityType;
    }

    /**
     * Returns if entity id exists.
     *
     * @param int $entityId
     *
     * @return bool
     */
    public function checkId(int $featureId):bool
    {
        return true;
    }

    /**
     * Find all Features models
     *
     * @param array $parameters An array of filters to apply on the search
     * @param array $include    An array of included relationships
     *
     * @return array
     */
    public function get(array $parameters = [], array $include = []):array
    {
    }

    /**
     * Find one Features by the id field
     *
     * @param int   $entityId   Id to search for
     * @param array $parameters An array of filters to apply on the search
     *
     * @return \App\BMFormatters\Interfaces\IModels
     */
    public function getById(int $entityId, array $parameters = []):IModels
    {
    }

    /**
     * Generate cost reports is function responsible of generate features costs reports.
     * This is the end-point entrance but to use functionality in a execution line you must call costsReports.
     *
     * @param \App\BMFormatters\Interfaces\IBMAction $data
     *
     * @return BMActionError $error
     */
    public function generateCostsReports(IBMAction $data)
    {
        $data = $data->getActionParams();

        $error = $this->costsReports($data['platformCode'], $data['period']);
        if ($error instanceof BMActionError) {
            return $error;
        }
        if ($data['deleteTempFiles']) {
            $this->deleteTempFiles();
        }
        $actionOk = new BMActionOk('generateCostsReports', self::STATUS_CODES['generateCostsReportsOk'], 'Result Action completed', 'The result action was completed.', false);

        return $actionOk;
    }

    /**
     * Action to store the feature data received
     *
     * @param \App\BMFormatters\Requests\HttpJsonApi\ActionPostNormalizer $actionParams The neede parameter to execute the action
     *
     * @return void
     */
    public function setFeatureData(ActionPostNormalizer $actionParams)
    {
        $params = $actionParams->getActionParams();
        
        $data = $this->getStatDataFromParams($params);
        $this->repository->setFeaturesStatsByPlatforms($data);

        $actionResponse = new BMActionOk('setFeatureData', self::STATUS_CODES['setFeaturedDataOk'], 'Set feature data action completed', 'All the data was stored.', false);

        return $actionResponse;
    }

    /**
     * Returns the data row to instert of the features stats given in the params of the action
     *
     * @param array $params The params of the action
     *
     * @return array The array formatted ready to be recorded
     */
    private function getStatDataFromParams(array $params):array
    {
        $data = [];
        $billingPeriodId = $this->getCurrentBillingPeriod();
        $featureId = $this->repository->getIdFromFeatureCode($params['featureCode']);
        $platformsIdByCode = $this->getPlatformIdByCodesFromSourceData($params);
        if ($params['sourceFormat'] === 'inline') {
            $data = $this->getInlineData($params['sourceData'], $featureId, $params['featureCode'], $billingPeriodId, $platformsIdByCode);

            return $data;
        }

        $data = $this->getBlobData($params['sourceData'], $platformsIdByCode, $featureId, $billingPeriodId);

        return $data;
    }

    /**
     * Get the data object to store for a blob format data source
     *
     * @param array  $sourceData        The source data array received
     * @param int    $featureId         The Id of the feature received in the request
     * @param string $featureCode       The code of the feature received in the request
     * @param int    $billingPeriodId   The current billing period id
     * @param array  $platformsIdByCode The list of the platforms ids, key by code
     *
     * @return array The data array, ready to be stored in the persistance layer
     */
    private function getInlineData(array $sourceData, int $featureId, string $featureCode, int $billingPeriodId, array $platformsIdByCode):array
    {
        $data = [];
        $platformStats = [];
        $platformStats = $this->generateStatsFromInlineSourceData($sourceData, $platformsIdByCode);
        $data = $this->formatStatData($platformStats, $platformsIdByCode, $featureId, $billingPeriodId, $featureCode);

        return $data;
    }

    /**
     * Get the data object to store for a blob format data source
     *
     * @param array $sourceData        The source data array received
     * @param array $platformsIdByCode The list of the platforms ids, key by code
     * @param int   $featureId         The Id of the feature received in the request
     * @param int   $billingPeriodId   The current billing period id
     *
     * @return array The data array, ready to be stored in the persistance layer
     */
    private function getBlobData(array $sourceData, array $platformsIdByCode, int $featureId, int $billingPeriodId):array
    {
        $data = [];
        foreach ($sourceData as $platformCode => $file) {
            $fileData = $this->fileRepository->getFileByUrl($file);
            $csvRaw = explode("\n", $this->fileManager->getFileContents($fileData['id']));
            foreach ($csvRaw as $row) {
                list($platformCode, $statCode, $value) = \str_getcsv($row, ';');
                $data[] = $this->getFeatureStatData($platformsIdByCode[$platformCode], $featureId, $billingPeriodId, $statCode, $value);
            }
        }

        return $data;
    }

    /**
     * Generate the array of stats from the source data with inline format
     *
     * @param array $sourceData        The source data element of the parameters
     * @param array $platformsIdByCode A list of platforms id, key by code, needed to generate the stats array
     *
     * @return array The formated array generated by the source data
     */
    private function generateStatsFromInlineSourceData(array $sourceData, array $platformsIdByCode):array
    {
        $platformStats = $sourceData;
        if (isset($platformStats['all'])) {
            $allValue = $platformStats['all'];
            $platformStats = [];
            $platformsCodes = array_keys($platformsIdByCode);
            array_map(function ($item) use ($allValue, $platformsIdByCode, &$platformStats) {
                $platformStats[$item] = $allValue;
            }, $platformsCodes);
        }

        return $platformStats;
    }

    /**
     * Format the stat to be stored by the setFeatureStat action
     *
     * @param array  $platformStats     List of feature stats, key by platform code
     * @param array  $platformsIdByCode List of all platforms id affected, key by platform code
     * @param int    $featureId         The feature id to add the stats
     * @param int    $billingPeriodId   The current billing period id
     * @param string $featureCode       The feature code to add the stats
     *
     * @return array The stats object formatted to be recorded to the persistance layer
     */
    private function formatStatData(array $platformStats, array $platformsIdByCode, int $featureId, int $billingPeriodId, string $featureCode):array
    {
        $data = [];
        foreach ($platformStats as $platformCode => $featureStat) {
            if (!empty($platformsIdByCode[$platformCode]) && is_array($featureStat)) {
                $data = array_merge($data, $this->getFeatureStatsFromList($featureStat, $platformsIdByCode[$platformCode], $featureId, $billingPeriodId));
            }
            if (!is_array($featureStat)) {
                $data[] = $this->getFeatureStatData($platformsIdByCode[$platformCode], $featureId, $billingPeriodId, $featureCode, $featureStat);
            }
        }

        return $data;
    }

    /**
     * Returns the feature stats from an array, to store it in the persistance layer
     *
     * @param array $featureStat     The array of stats of the feature received
     * @param int   $platformId      The platform id of the stats
     * @param int   $featureId       The feature id of the stats
     * @param int   $billingPeriodId The billing period id of the stats
     *
     * @return array An array ready to be saved
     */
    private function getFeatureStatsFromList(array $featureStat, int $platformId, int $featureId, int $billingPeriodId): array
    {
        $data = [];
        
        foreach ($featureStat as $stat => $statValue) {
            $data[] = $this->getFeatureStatData($platformId, $featureId, $billingPeriodId, $stat, $statValue);
        }

        return $data;
    }

    /**
     * Returns the object ready to send to persistance to store the feature stats data
     *
     * @param int    $platformId      Platform id of the stat
     * @param int    $featureId       Feature id of the stat
     * @param int    $billingPeriodId The billing period id of the stat
     * @param string $stat            The code string of the stat
     * @param int    $statValue       The value of the stat
     *
     * @return array The array to store
     */
    private function getFeatureStatData(int $platformId, int $featureId, int $billingPeriodId, string $stat, int $statValue):array
    {
        return [
            'platform_id' => $platformId,
            'feature_id' => $featureId,
            'billing_period_id' => $billingPeriodId,
            'code' => $stat,
            'value' => $statValue
        ];
    }

    /**
     * Returns the platforms id key by code
     *
     * @param array $params The params element of the action params
     *
     * @return array The list of platforms ids by code
     */
    private function getPlatformIdByCodesFromSourceData(array $params):array
    {
        $platformCodes = array_keys($params['sourceData']);
        $platformCodes = array_unique($platformCodes);
        
        $platformsIdByCode = $this->platformRepository->getPlatformsIdsByCodeOrAll($platformCodes);

        return $platformsIdByCode;
    }

    /**
     * Returns the current billing period id
     *
     * @return int
     */
    private function getCurrentBillingPeriod():int
    {
        $billingPeriodDate = \date('Y-m');
        $existsBillingPeriod = $this->repository->existsBillingPeriod($billingPeriodDate);
        if (!$existsBillingPeriod) {
            $this->repository->createBillingPeriod($billingPeriodDate);
        }
        $billingPeriodId = $this->repository->getBillingPeriodByDate($billingPeriodDate);

        return $billingPeriodId;
    }

    /**
     * Generate object spreadsheet
     *
     * @param array $platformData
     *
     * @return void
     */
    private function generateReport(array $platformData)
    {
        $spreadsheet= new Spreadsheet();
        $this->createSpreadSheetMetaData($spreadsheet, $platformData);
        $this->createReportHeaders($spreadsheet);
        $this->createReportGeneralData($spreadsheet, $platformData);
        $this->createReportUserData($spreadsheet, $platformData);
        $this->createReportStorageData($spreadsheet, $platformData);
        $this->createReportTotalCost($spreadsheet, $platformData);
        $this->createReportFormat($spreadsheet);

        if (!$spreadsheet instanceof Spreadsheet) {
            $error = $this->createBMActionError(self::STATUS_CODES['spreadSheetCreation'], self::NOT_VALID_SPREADSHEET, 'Not Instance of spreadsheet');

            return $error;
        }

        $this->spreadsheet=$spreadsheet;
    }

    /**
     * Function to launch BMActionError.
     *
     * @param string $type
     * @param string $code
     * @param string $text
     * @param string $description
     *
     * @return BMActionError $errors
     */
    private function createBMActionError(string $code, string $text, string $description)
    {
        $errors = new BMActionError(
            'generateCostsReports',
            $code,
            $text,
            $description,
            [],
            false,
            []
        );

        return $errors;
    }

    /**
     * Function to persist report
     *
     * @param array  $platformData
     * @param string $period
     *
     * @return mixed
     */
    private function persistReport(array $platformData, string $period)
    {
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->spreadsheet);
        $name= $platformData['name'].'-'.$period.'.xlsx';
        $writer->save(env('CACHE_FOLDER').$name);
        $isFile = $this->checkFile($name);
        if ($isFile instanceof BMActionError) {
            return $isFile;
        }
        try {
            $this->fileManager->saveFile('billingReports/'.$name, $isFile, 'features', 'azure-blob', request()->session()->get('personID'));
        } catch (FileManagerException $e) {
            $error = $this->createBMActionError(self::STATUS_CODES['cantPersistsError'], self::CANT_PERSISTS, $e->getMessage());

            return $error;
        }
    }

    /**
     * Checks if file exists
     *
     * @param string $file
     *
     * @return mixed
     */
    private function checkFile(string $file)
    {
        if (!is_file($file)) {
            $error = $this->createBMActionError(self::STATUS_CODES['fileNotFound'], self::FILE_NOT_FOUND, "File not found or can't be reached.");

            return $error;
        }
        $content = file_get_contents($file);

        return $content;
    }

    /**
     * Function to prepare data for costs reports.
     *
     * @param array                                   $platformsIds
     * @param string                                  $period
     * @param \OKNManager\Libraries\Files\FileManager $fileManager
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet   $spreadSheet
     * @param \PhpOffice\PhpSpreadsheet\Writer\Xlsx   $xlsx
     *
     * @return BMActionError|null
     */
    private function prepareDataForCostsReports(array $platformsCodes, string $period)
    {
        $this->platformsIds=$this->platformRepository->getPlatformsIdsByCodeOrAll($platformsCodes);
        $this->period = $period;
        $this->platformReportData = $this->repository->getPlatformDataForBillingReport($this->platformsIds);
        $this->reportLiterals = $this->repository->getReportsLiterals(self::REPORT_LITERALS, env('DEFAULT_LANGUAGE'));
        if (count($this->reportLiterals) < count(self::REPORT_LITERALS)) {
            throw new \UnexpectedValueException('Reports literals missing.', 500);
        }
        $this->categoriesTranslations = $this->repository->getFatherCategoriesTranslates(self::CATEGORIES_TRANSLATES, env('DEFAULT_LANGUAGE'));
        if (count($this->categoriesTranslations) < count(self::CATEGORIES_TRANSLATES)) {
            throw new \UnexpectedValueException('Father categories translations missing.', 500);
        }
        $this->periodId = $this->repository->getBillingPeriodByDate($period);
        if (is_null($this->periodId)) {
            $error = $this->createBMActionError(self::STATUS_CODES['periodNotFound'], self::DATA_NOT_FOUND, 'Can\'t find period with '.$period.' date');

            return $error;
        }
        $this->featuresBilledData = $this->repository->getFeaturesBilled($this->platformsIds, $this->periodId);
        if (empty($this->featuresBilledData)) {
            $error = $this->createBMActionError(self::STATUS_CODES['billedDataNotFound'], self::DATA_NOT_FOUND, 'Can\'t find billed data for '.print_r($this->platformsIds, true).' platforms');

            return $error;
        }
        $this->featuresStatsData = $this->repository->getFeaturesStats($this->platformsIds, $this->periodId);
        if (empty($this->featuresStatsData)) {
            $error = $this->createBMActionError(self::STATUS_CODES['billedStatNotFound'], self::DATA_NOT_FOUND, 'Can\'t find billed data for '.print_r($this->platformsIds, true).' platforms');

            return $error;
        }

        return null;
    }

    /**
     * Create report spreadsheet meta data
     *
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadSheet
     * @param array                                 $platformData
     *
     * @return void
     */
    private function createSpreadSheetMetaData(Spreadsheet &$spreadSheet, array $platformData)
    {
        $spreadSheet->getProperties()
            ->setCreator($this->reportLiterals['general-nombre-manager'])
            ->setTitle($platformData['name'].' '.$this->reportLiterals['features-report-coste-servicio'].' '.$this->period);
    }

    /**
     * Create report headers
     *
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadSheet
     *
     * @return void
     */
    private function createReportHeaders(Spreadsheet &$spreadSheet)
    {
        $spreadSheet->getActiveSheet()
            ->setCellValue('C1', strtoupper($this->categoriesTranslations['users']))
            ->setCellValue('L1', strtoupper($this->categoriesTranslations['storage']))
            ->setCellValue('R1', strtoupper($this->reportLiterals['features-report-total']));
    }

    /**
     * CreateReport general data
     *
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadSheet
     * @param array                                 $platformData
     *
     * @return void
     */
    private function createReportGeneralData(Spreadsheet &$spreadSheet, array $platformData)
    {
        $spreadSheet->getActiveSheet()
            ->setCellValue('A2', $this->reportLiterals['features-report-plataforma'])
            ->setCellValue('A3', $platformData['name'])
            ->setCellValue('B2', $this->reportLiterals['features-report-periodo'])
            ->setCellValue('B3', $this->period);
    }

    /**
     * Create report format
     *
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadSheet
     *
     * @return void
     */
    private function createReportFormat(Spreadsheet &$spreadSheet)
    {
        $styleArray = [
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'font'  => [
                'size'  => 10
            ]
        ];
        $spreadSheet->getActiveSheet()->getStyle('C1:K1')->applyFromArray($styleArray);
        $spreadSheet->getActiveSheet()->getStyle('L1:Q1')->applyFromArray($styleArray);
        $spreadSheet->getActiveSheet()->getStyle('R1')->applyFromArray($styleArray);
        $spreadSheet->getDefaultStyle()->getAlignment()->setHorizontal('center');
        $spreadSheet->getActiveSheet()->getStyle('A2:R2')->getFont()->setBold(true)->setSize(10);
        $spreadSheet->getActiveSheet()->getStyle('C1:R1')->getFont()->setBold(true)->setSize(10);
        $spreadSheet->getActiveSheet()->mergeCells('C1:K1');
        $spreadSheet->getActiveSheet()->mergeCells('L1:Q1');
        $spreadSheet->getActiveSheet()->getStyle('E3')->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
        $spreadSheet->getActiveSheet()->getStyle('G3')->getNumberFormat()
             ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
        $spreadSheet->getActiveSheet()->getStyle('I3')->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
        $spreadSheet->getActiveSheet()->getStyle('K3')->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
        $spreadSheet->getActiveSheet()->getStyle('Q3')->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
        $spreadSheet->getActiveSheet()->getStyle('R3')->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE);
        $spreadSheet->getActiveSheet()->getStyle('L3')->getNumberFormat()->setFormatCode(self::BILLING_SIZE_FORMAT);
        $spreadSheet->getActiveSheet()->getStyle('M3')->getNumberFormat()->setFormatCode(self::BILLING_SIZE_FORMAT);
        $spreadSheet->getActiveSheet()->getStyle('N3')->getNumberFormat()->setFormatCode(self::BILLING_SIZE_FORMAT);
        $spreadSheet->getActiveSheet()->getStyle('O3')->getNumberFormat()->setFormatCode(self::BILLING_SIZE_FORMAT);
        $spreadSheet->getActiveSheet()->getStyle('P3')->getNumberFormat()->setFormatCode(self::BILLING_SIZE_FORMAT);
        $spreadSheet->getActiveSheet()->getStyle('A3:R3')->getFont()->setSize(10);
        $spreadSheet->getDefaultStyle()->getFont()->setSize(10);
        $spreadSheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(16);
        $spreadSheet->getActiveSheet()->getStyle('C3')->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
        $spreadSheet->getActiveSheet()->getStyle('D3')->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
        $spreadSheet->getActiveSheet()->getStyle('F3')->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
        $spreadSheet->getActiveSheet()->getStyle('H3')->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
        $spreadSheet->getActiveSheet()->getStyle('J3')->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER);
        $spreadSheet->getActiveSheet()->getStyle('A3')->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        $spreadSheet->getActiveSheet()->getStyle('B3')->getNumberFormat()
            ->setFormatCode(self::FORMAT_DATE_YYYYMM);

        $spreadSheet->getActiveSheet()->getStyle('A3:R3')->getAlignment()->setHorizontal('center');
    }

    /**
     * function to prepare report total costs
     *
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadSheet
     * @param array                                 $platformData
     *
     * @return void
     */
    private function createReportTotalCost(Spreadsheet &$spreadSheet, array $platformData)
    {
        $spreadSheet->getActiveSheet()
        ->setCellValue('R2', $this->reportLiterals['features-report-coste-servicio'])
        ->setCellValue('R3', $this->featuresBilledData[$platformData['name']]['users']['active_users']+
            $this->featuresBilledData[$platformData['name']]['users']['inactive_users']+
            $this->featuresBilledData[$platformData['name']]['users']['deleted_users']+
            $this->featuresBilledData[$platformData['name']]['storage']['storage']);
    }

    /**
     * Function to create storage section of report.
     *
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadSheet
     * @param array                                 $platformData
     *
     * @return void
     */
    private function createReportStorageData(Spreadsheet &$spreadSheet, array $platformData)
    {
        $spreadSheet->getActiveSheet()
            ->setCellValue('L2', ucwords($this->reportLiterals['features-report-total-contratado'], ''))
            ->setCellValue('L3', $platformData['storage'])
            ->setCellValue('M2', ucwords($this->reportLiterals['features-report-base-de-datos'], ''))
            ->setCellValue('M3', $this->featuresStatsData[$platformData['name']]['storage']['database'])
            ->setCellValue('N2', ucwords($this->reportLiterals['features-report-contenido'], ''))
            ->setCellValue('N3', $this->featuresStatsData[$platformData['name']]['storage']['content'])
            ->setCellValue('O2', ucwords($this->reportLiterals['features-report-backups'], ''))
            ->setCellValue('O3', $this->featuresStatsData[$platformData['name']]['storage']['primary_backup']+
                $this->featuresStatsData[$platformData['name']]['storage']['secondary_backup']+
                $this->featuresStatsData[$platformData['name']]['storage']['sql_backup'])
            ->setCellValue('P2', ucwords($this->reportLiterals['features-report-total'], ''))
            ->setCellValue('P3', $this->featuresStatsData[$platformData['name']]['storage']['primary_backup']+
                $this->featuresStatsData[$platformData['name']]['storage']['secondary_backup']+
                $this->featuresStatsData[$platformData['name']]['storage']['sql_backup']+
                $this->featuresStatsData[$platformData['name']]['storage']['content']+
                $this->featuresStatsData[$platformData['name']]['storage']['database'])
            ->setCellValue('Q2', ucwords($this->reportLiterals['features-report-total-coste'], ''))
            ->setCellValue('Q3', $this->featuresBilledData[$platformData['name']]['storage']['storage']);
    }

    /**
     * Function to prepare report user data.
     *
     * @param \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadSheet
     * @param array                                 $platformData
     *
     * @return void
     */
    private function createReportUserData(Spreadsheet &$spreadSheet, array $platformData)
    {
        $spreadSheet->getActiveSheet()
            ->setCellValue('A2', ucwords($this->reportLiterals['features-report-plataforma'], ''))
            ->setCellValue('A3', $platformData['name'])
            ->setCellValue('B2', ucwords($this->reportLiterals['features-report-periodo'], ''))
            ->setCellValue('B3', $this->period)
            ->setCellValue('C2', ucwords($this->reportLiterals['features-report-total-contratado'], ''))
            ->setCellValue('C3', $platformData['estimated_users'])
            ->setCellValue('D2', ucwords($this->reportLiterals['features-report-activos'], ''))
            ->setCellValue('D3', $this->featuresStatsData[$platformData['name']]['users']['active_users'])
            ->setCellValue('E2', ucwords($this->reportLiterals['features-report-coste'], ''))
            ->setCellValue('E3', $this->featuresBilledData[$platformData['name']]['users']['active_users'])
            ->setCellValue('F2', ucwords($this->reportLiterals['features-report-inactivos'], ''))
            ->setCellValue('F3', $this->featuresStatsData[$platformData['name']]['users']['inactive_users'])
            ->setCellValue('G2', ucwords($this->reportLiterals['features-report-coste'], ''))
            ->setCellValue('G3', $this->featuresBilledData[$platformData['name']]['users']['inactive_users'])
            ->setCellValue('H2', ucwords($this->reportLiterals['features-report-borrados'], ''))
            ->setCellValue('H3', $this->featuresStatsData[$platformData['name']]['users']['deleted_users'])
            ->setCellValue('I2', ucwords($this->reportLiterals['features-report-coste'], ''))
            ->setCellValue('I3', $this->featuresBilledData[$platformData['name']]['users']['deleted_users'])
            ->setCellValue('J2', ucwords($this->reportLiterals['features-report-total'].' '.$this->categoriesTranslations['users'], ''))
            ->setCellValue('J3', $this->featuresStatsData[$platformData['name']]['users']['active_users']+
                $this->featuresStatsData[$platformData['name']]['users']['inactive_users']+
                $this->featuresStatsData[$platformData['name']]['users']['deleted_users'])
            ->setCellValue('K2', ucwords($this->reportLiterals['features-report-total-coste'], ''))
            ->setCellValue('K3', $this->featuresBilledData[$platformData['name']]['users']['active_users']+
                $this->featuresBilledData[$platformData['name']]['users']['inactive_users']+
                $this->featuresBilledData[$platformData['name']]['users']['deleted_users']);
    }

    /**
     * Function with the action logic, you can call this function to execute action logic without an end-point.
     *
     * @param array  $platformsCodes
     * @param string $period
     *
     * @return void
     */
    private function costsReports(array $platformsCodes, string $period)
    {
        $error = $this->prepareDataForCostsReports($platformsCodes, $period);
        if ($error instanceof BMActionError) {
            return $error;
        }
        foreach ($this->platformReportData as $platformData) {
            $this->generateReport($platformData);
            if ($this->spreadsheet instanceof BMActionError) {
                return $this->spreadsheet;
            }
            $this->persistReport($platformData, $period);
        }
    }


    private function deleteTempFiles()
    {
        foreach ($this->platformReportData as $platformData) {
            unlink(env('CACHE_FOLDER').$platformData['name'].'-'.$this->period.self::REPORT_EXTENSION);
        }
    }
}
