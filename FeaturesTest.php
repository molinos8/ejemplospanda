<?php

namespace Tests;

use App\BMFormatters\Requests\HttpJsonApi\ActionPostNormalizer;
use OKNManager\BM\Features;
use OKNManager\BM\Repositories\FeaturesRepository;
use OKNManager\BM\Repositories\FilesRepository;
use OKNManager\BM\Repositories\PlatformsRepository;
use OKNManager\BM\Wrappers\BMActionError;
use OKNManager\BM\Wrappers\BMActionOk;
use OKNManager\Libraries\Files\FileManager;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for Feature BM.
 */
class FeaturesTest extends TestCase
{

    /**
     * Valid categories translations.
     *
     * @var array
     */
    protected $categoriesTranslations= ['users' =>'usuarios', 'storage' =>'alamacenamiento'];
    /**
     * Valid literals array.
     *
     * @var array
     */
    protected $reportLiterals = [
        'general-nombre-manager'=>'OKNLearning',
        'features-report-coste-servicio' => 'Coste servicio',
        'features-report-plataforma' =>'Plataforma',
        'features-report-periodo' =>'peirodo',
        'features-report-total-contratado' => 'Total contratado',
        'features-report-total' => 'total',
        'features-report-activos' => 'activos',
        'features-report-coste'=>'coste',
        'features-report-inactivos' =>'inactivos',
        'features-report-borrados'=> 'borrados',
        'features-report-total-coste' =>'total coste',
        'features-report-base-de-datos'=>'bases de datos',
        'features-report-contenido'=>'contenido',
        'features-report-backups' =>'backups',
        'features-report-coste-servicio' =>'Coste del servicio'
    ];

    /**
     * valid features stat data.
     *
     * @var array
     */
    protected $featuresStatsData = [
        'platform1' => [
            'users' => ['active_users'=> 1000, 'inactive_users' => 2000, 'deleted_users' =>2500],
            'storage' => ['database' => 1500, 'content' => 2500, 'primary_backup' => 350, 'secondary_backup' => 300, 'sql_backup' => 250]
            ]
        ];
    /**
     * Valid features billed data.
     *
     * @var array
     */
    protected $featuresBilledData = ['platform1' => [
                                        'users' => ['active_users'=> 100, 'inactive_users' => 200, 'deleted_users' =>250],
                                        'storage' => ['database' => 150, 'storage' => 250]]];
    /**
     * Valid array to use in some responses.
     *
     * @var array
     */
    protected $validArray = ['fakeArray'];

    /**
     * Valid period id.
     *
     * @var int
     */
    protected $validPeriodId =1;

    /**
     * valid expected translations array.
     *
     * @var array
     */
    protected $expectedResultTranslations =['one literal', 'other literal'];
    /**
     * Valid literals response.
     *
     * @var array
     */
    protected $expectResultLiterals=['one literal', 'other', 'other-literal', 'one literal', 'other-literal', 'one literal', 'other-literal', 'one-more', 'one-fucking-literal', 'oh-my-god', 'one more literal', 'last ltieral'];

    /**
     * Valid platform Id's array.
     *
     * @var array
     */
    protected $platformsIds = [1, 2];

    /**
     * valid fake platform codes.
     *
     * @var array
     */
    protected $platformCodes = ['okn_fake', 'okn_refake'];

    /**
     * Valid period string.
     *
     * @var string
     */
    protected $period = '2018-12';
    /*
     * Valid platform data.
     *
     * @var array
     */
    protected $fakePlatformData =['name'=>'platform1', 'storage' => 20, 'estimated_users'=>30];

    /**
     * The file manager stub
     *
     * @var \OKNManager\Libraries\Files\FileManager
     */
    private $fileManagerStub;
    /**
     * The features repository mock
     *
     * @var \OKNManager\BM\Repositories\FeaturesRepository
     */
    private $repositoryStub;
    /**
     * The platforms repository mock
     *
     * @var \OKNManager\BM\Repositories\PlatformsRepository
     */
    private $platformRepositoryStub;
    /**
     * The features Business model object
     *
     * @var \OKNManager\BM\Features
     */
    private $features;

    /**
     * The file repository mock
     *
     * @var \OKNManager\BM\Repositories\FilesRepository
     */
    private $fileRepository;

    /**
     * Test setup.
     *
     * @return void
     */
    public function setup()
    {
        $this->fileManagerStub = $this->getMockBuilder(FileManager::class)->disableOriginalConstructor()->getMock();
        $this->repositoryStub = $this->getMockBuilder(FeaturesRepository::class)->getMock();
        $this->platformRepositoryStub = $this->getMockBuilder(PlatformsRepository::class)->getMock();
        $this->fileRepository = $this->getMockBuilder(FilesRepository::class)->getMock();
        $this->features =  new Features($this->repositoryStub, $this->platformRepositoryStub, $this->fileManagerStub, $this->fileRepository);
    }

    /**
     * Test generateCostsReports exist effectively exists.
     *
     * @return void
     */
    public function test_generateCostsReports_exist_effectivelyExists()
    {
        $this->assertTrue(
            method_exists($this->features, 'generateCostsReports'),
            'Class does not have method generateCostsReports'
        );
    }


    /**
     * Test generateCostsReports  gets platform id  and sets it
     *
     * @return void
     */
    public function test_generateCostsReports_getsPlatformId_setsIt()
    {
        $this->setsGenerateCostReportsWithMockedDependencies();
        $this->assertEquals($this->platformsIds, $this->features->platformsIds);
    }

    /**
     * Test generateCostsReports gets period sets period.
     *
     * @return void
     */
    public function test_generateCostsReports_getsPeriod_setsPeriod()
    {
        $this->setsGenerateCostReportsWithMockedDependencies();
        $this->assertEquals($this->period, $this->features->period);
    }

    /**
     * Test generate costs reports calls repository getPlatformData for billing report and sets platform data.
     *
     * @return void
     */
    public function test_generateCostsReports_callsRepositoryGetPlatformDataForBillingReport_SetsPlatformData()
    {
        $expectedResult=[1 =>['name'=>'pepe', 'storage' => 20, 'estimated_users'=>30], 2 => ['name' => 'pepa', 'storage' => 25, 'estimated_users'=>35]];

        $this->repositoryStub->method('getPlatformDataForBillingReport')
            ->willReturn($expectedResult);

        $this->setsGenerateCostReportsWithMockedDependencies();
        $this->assertEquals($expectedResult, $this->features->platformReportData);
    }

    /**
     * Test generateCostsReports calls repository getReportsLiterals and get less literals than expected throws unexpected argument value exception.
     *
     * @return void
     */
    public function test_generateCostsReports_callsRepositoryGetReportsLiteralsAndGetLessLiteralsThanExpected_throwsUnexpectedArgumentValueException()
    {
        $expectedResult=['one literal', 'other-literal'];
        $this->repositoryStub->method('getReportsLiterals')
            ->willReturn($expectedResult);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Reports literals missing.');
        $this->setsGenerateCostReportsWithMockedDependencies();
    }

    /**
     * Test generateCostsReports calls repository getFatherCategoriesTranslates and get less translations than expected throws unexpected argument value exception.
     *
     * @return void
     */
    public function test_generateCostsReports_callsRepositoryGetFatherCategoriesTranslatesAndGetLessTranslationsThanExpected_throwsUnexpectedArgumentValueException()
    {
        $expectedResult=['one literal'];
        $this->repositoryStub->method('getFatherCategoriesTranslates')
            ->willReturn($expectedResult);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Father categories translations missing.');
        $this->setsGenerateCostReportsWithMockedDependencies();
    }

    /**
     * Test generateCostsReports calls repository getReportsLiterals sets it.
     *
     * @return void
     */
    public function test_generateCostsReports_callsRepositoryGetReportsLiterals_setsIt()
    {
        $this->setsGenerateCostReportsWithMockedDependencies();
        $this->assertEquals($this->features->reportLiterals, $this->expectResultLiterals);
    }

    /**
     * Test generateCostsReports calls repository getReportsLiterals sets it.
     *
     * @return void
     */
    public function test_generateCostsReports_callsRepositoryGetFatherCategoriesTranslates_setsIt()
    {
        $this->setsGenerateCostReportsWithMockedDependencies();
        $this->assertEquals($this->features->categoriesTranslations, $this->expectedResultTranslations);
    }

    /**
     * Test generateCostReports calls repository getBillingPeriodByDate obtain empty data returns BMActionError.
     *
     * @return void
     */
    public function test_generateCostReports_callsRepositoryGetBillingPeriodByDateObtainEmptyData_returnsBMActionError()
    {
        $code = '002005001';
        $this->setRepositoryLiteralsStub();
        $this->makeBillingPeriodStub(null);
        $this->createActionPostNormalizer();
        $this->setRepositoryTranslationsStub();
        $result =$this->features->generateCostsReports($this->ActionPostNormalizer);
        $this->assertInstanceOf(BMActionError::class, $result);
        $this->assertEquals($code, $result->getCode());
    }

    /**
     * Test generateCostReports calls repository getBillingPeriodByDate obtain id sets id.
     *
     * @return void
     */
    public function test_generateCostReports_callsRepositoryGetBillingPeriodByDateObtainId_setsId()
    {
        $periodID=1;
        $this->repositoryStub->method('getBillingPeriodByDate')
            ->willReturn($periodID);
        $this->setsGenerateCostReportsWithMockedDependencies();
        $this->assertEquals($this->features->periodId, $periodID);
    }

    /**
     * Test generateCostReports calls repository getFeaturesBilled obtainEmptyData returns BMActionError
     *
     * @return void
     */
    public function test_generateCostReports_callsRepositoryGetFeaturesBilledObtainEmptyData_returnsBMActionError()
    {
        $code = '002005000';
        $this->setRepositoryLiteralsStub();
        $this->makeBillingPeriodStub($this->validPeriodId);
        $this->makeFeaturesBilledStub([]);
        $this->createActionPostNormalizer();
        $this->setRepositoryTranslationsStub();
        $result =$this->features->generateCostsReports($this->ActionPostNormalizer);
        $this->assertInstanceOf(BMActionError::class, $result);
        $this->assertEquals($result->getCode(), $code);
    }

    /**
     * Test generateCostsReports calls repository getFeaturesBilled obtain data sets data.
     *
     * @return void
     */
    public function test_generateCostsReports_callsRepositoryGetFeaturesBilledObtainData_setsData()
    {
        $data=['fakeArray'];
        $this->makeFeaturesBilledStub($this->validArray);
        $this->makeBillingPeriodStub($this->validPeriodId);
        $this->repositoryStub->method('getFeaturesBilled')
            ->willReturn($data);
        $this->setsGenerateCostReportsWithMockedDependencies();
        $this->assertEquals($this->features->featuresBilledData, $data);
    }

    /**
     * Test generateCostReports calls repository getFeaturesStats obtain empty data returns BMActionError.
     *
     * @return void
     */
    public function test_generateCostReports_callsRepositoryGetFeaturesStatsObtainEmptyData_returnsBMActionError()
    {
        $expectedCode = '002005002';
        $this->setRepositoryLiteralsStub();
        $this->makeBillingPeriodStub($this->validPeriodId);
        $this->makeFeaturesBilledStub($this->validArray);
        $this->makeFeatureStatsStub([]);
        $this->createActionPostNormalizer();
        $this->setRepositoryTranslationsStub();
        $result =$this->features->generateCostsReports($this->ActionPostNormalizer);
        $this->assertInstanceOf(BMActionError::class, $result);
        $this->assertEquals($expectedCode, $result->getCode());
    }

    /**
     * Test generateCostsReports calls repository getFeaturesStats obtain data sets data.
     *
     * @return void
     */
    public function test_generateCostsReports_callsRepositoryGetFeaturesStatsObtainData_setsData()
    {
        $data=['fakeArray'];
        $this->makeFeaturesBilledStub($this->validArray);
        $this->makeBillingPeriodStub($this->validPeriodId);
        $this->repositoryStub->method('getFeaturesStats')
            ->willReturn($data);
        $this->setsGenerateCostReportsWithMockedDependencies();
        $this->assertEquals($this->features->featuresStatsData, $data);
    }

    /**
     * Test generateReport gets platform data sets creator.
     *
     * @return void
     */
    public function test_generateReport_getsPlatformData_SetsCreator()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getProperties()->getCreator(), $this->features->reportLiterals['general-nombre-manager']);
    }

    /**
     * Test generateReport gets platform data sets title.
     *
     * @return void
     */
    public function test_generateReport_getsPlatformData_SetsTitle()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getProperties()->getTitle(),
            $this->fakePlatformData['name'].' '.$this->features->reportLiterals['features-report-coste-servicio'].' '.
            $this->features->period
        );
    }

    /**
     * Test generateReport getsPlatformData sets platform literal.
     *
     * @return void
     */
    public function test_generateReport_getsPlatformData_SetsPlatformLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('A2')->getValue(),
            ucwords($this->features->reportLiterals['features-report-plataforma'], '')
        );
    }

    /**
     * Test generateReport gets platform data sets platform name.
     *
     * @return void
     */
    public function test_generateReport_getsPlatformData_SetsPlatformName()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getActiveSheet()->getCell('A3')->getValue(), $this->fakePlatformData['name']);
    }

    /**
     * Test generateReport gets platform data sets period literal.
     *
     * @return void
     */
    public function test_generateReport_getsPlatformData_SetsPeriodLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getActiveSheet()->getCell('B2')->getValue(), ucwords($this->features->reportLiterals['features-report-periodo'], ''));
    }

    /**
     * Test generateReport gets platform data sets period.
     *
     * @return void
     */
    public function test_generateReport_getsPlatformData_SetsPeriod()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getActiveSheet()->getCell('B3')->getValue(), $this->features->period);
    }

    /**
     * Test generateReports build excel report merge user header cells.
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_MergeUserHeaderCells()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getActiveSheet()->getMergeCells()['C1:K1'], 'C1:K1');
    }

    /**
     * Test generateReports build excel report merge hosting header cells.
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_MergeHostingHeaderCells()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getActiveSheet()->getMergeCells()['L1:Q1'], 'L1:Q1');
    }

    /**
     * Test generateReports build excel report default horizontal alignment is horizontal.
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_defaultHorizontalAlignmentIsHorizontal()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getDefaultStyle()->getAlignment()->getHorizontal(), 'center');
    }

    /**
     * Test generateReports build excel report default font size is 10.
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_defaultFontSizeIs10()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getDefaultStyle()->getFont()->getSize(), 10);
    }

    /**
     * Test generate reports build excel report sets users feature header.
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_setsUsersFeatureHeader()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getActiveSheet()->getCell('C1')->getValue(), strtoupper($this->features->categoriesTranslations['users']));
    }

    /**
     * Test generate reports build excel report sets storage feature header.
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_setsStorageFeatureHeader()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('L1')->getValue(),
            strtoupper($this->features->categoriesTranslations['storage'])
        );
    }

    /**
     * Test generate reports build excel report sets users cost like currency.
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_setsUsersCostLikeCurrency()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getStyle('G3')->getNumberFormat()->getFormatCode(),
            '#,##0.00_-"€"'
        );
    }

    /**
     * Test generateReports build excel report sets active users cost like currency.
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_setsActiveUsersCostLikeCurrency()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getStyle('E3')->getNumberFormat()->getFormatCode(),
            '#,##0.00_-"€"'
        );
    }

    /**
     * Test generateReports build excel report sets deleted users cost like currency.
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_setsDeletedUsersCostLikeCurrency()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getStyle('I3')->getNumberFormat()->getFormatCode(),
            '#,##0.00_-"€"'
        );
    }

    /**
     * Test generateReports build excel report sets total users cost like currency.
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_setsTotalUsersCostLikeCurrency()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getStyle('K3')->getNumberFormat()->getFormatCode(),
            '#,##0.00_-"€"'
        );
    }

    /**
     * Test generateReports build excel report sets total storage cost like currency.
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_setsTotalStorageCostLikeCurrency()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getStyle('Q3')->getNumberFormat()->getFormatCode(),
            '#,##0.00_-"€"'
        );
    }

    /**
     * Test generateReports build excel report sets total service cost like currency.
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_setsTotalServiceCostLikeCurrency()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getStyle('R3')->getNumberFormat()->getFormatCode(),
            '#,##0.00_-"€"'
        );
    }

    /**
     * Test generateReports build excel report sets total storage hired like MB
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_setsTotalStorageHiredLikeMB()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getStyle('L3')->getNumberFormat()->getFormatCode(),
            $this->features::BILLING_SIZE_FORMAT
        );
    }

    /**
     * Test generateReports build excel report sets total data base storage hired like MB
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_setsTotalDatabaseStorageLikeMB()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getStyle('M3')->getNumberFormat()->getFormatCode(),
            $this->features::BILLING_SIZE_FORMAT
        );
    }

    /**
     * Test generateReports build excel report sets total content storage hired like MB
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_setsTotalContentStorageLikeMB()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getStyle('N3')->getNumberFormat()->getFormatCode(),
            $this->features::BILLING_SIZE_FORMAT
        );
    }

    /**
     * Test generateReports build excel report sets total backuplike MB
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_setsTotalBackupStorageLikeMB()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getStyle('O3')->getNumberFormat()->getFormatCode(),
            $this->features::BILLING_SIZE_FORMAT
        );
    }

    /**
     * Test generate reports build excel report sets total storage like MB.
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_setsTotalStorageLikeMB()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getStyle('P3')->getNumberFormat()->getFormatCode(),
            $this->features::BILLING_SIZE_FORMAT
        );
    }

    /**
     * Test generate reports build excel report sets storage feature header.
     *
     * @return void
     */
    public function test_generateReports_buildExcelReport_setsTotalLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getActiveSheet()->getCell('R1')->getValue(), strtoupper($this->features->reportLiterals['features-report-total']));
    }

    /**
     * Test generateReport gets platform data sets total contracted literal.
     *
     * @return void
     */
    public function test_generateReport_getsPlatformData_SetsTotalContractedLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getActiveSheet()->getCell('C2')->getValue(), ucwords($this->features->reportLiterals['features-report-total-contratado'], ''));
    }

    /**
     * Test generateReport build excel report sets headers bold.
     *
     * @return void
     */
    public function test_generateReport_buildExcelReport_SetsHeadersBold()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getActiveSheet()->getStyle('C1:R1')->getFont()->getBold(), true);
    }

    /**
     * Test generateReport build excel report sets titles bold.
     *
     * @return void
     */
    public function test_generateReport_buildExcelReport_SetsTitlesBold()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getActiveSheet()->getStyle('A2:R2')->getFont()->getBold(), true);
    }

    /**
     * Test generateReport gets platform data sets total users hired.
     *
     * @return void
     */
    public function test_generateReport_getsPlatformData_setsTotalUsersHired()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getActiveSheet()->getCell('C3')->getValue(), $this->fakePlatformData['estimated_users']);
    }

    /**
     * Test generateReport gets platform data sets total contracted literal.
     *
     * @return void
     */
    public function test_generateReport_getsPlatformData_SetsActiveUserLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getActiveSheet()->getCell('D2')->getValue(), ucwords($this->features->reportLiterals['features-report-activos'], ''));
    }

    /**
     * Test generateReport gets stat data sets active users stat data.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_setsActiveUsersStatData()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getActiveSheet()->getCell('D3')->getValue(), $this->features->featuresStatsData[$this->fakePlatformData['name']]['users']['active_users']);
    }

    /**
     * Test generateReport gets stat data set cost literal.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_setCostLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getActiveSheet()->getCell('E2')->getValue(), ucwords($this->features->reportLiterals['features-report-coste'], ''));
    }

    /**
     * Test generateReport gets billing data sets active users billing data.
     *
     * @return void
     */
    public function test_generateReport_getsBillingData_setsActiveUsersBillingData()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('E3')->getValue(),
            $this->features->featuresBilledData[$this->fakePlatformData['name']]['users']['active_users']
        );
    }

    /**
     * Test generateReport gets stat data set inactive literal.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_setInactiveLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getActiveSheet()->getCell('F2')->getValue(), ucwords($this->features->reportLiterals['features-report-inactivos'], ''));
    }

    /**
     * Test generateReport gets stat data set inactive users value.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_setInactiveUsersValue()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('F3')->getValue(),
            $this->features->featuresStatsData[$this->fakePlatformData['name']]['users']['inactive_users']
        );
    }

    /**
     * Test generateReport gets stat data set inactive cost literal.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_setInactiveCostLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('G2')->getValue(),
            ucwords($this->features->reportLiterals['features-report-coste'], '')
        );
    }

    /**
     * Test generateReport gets billing data sets inactive users billing data.
     *
     * @return void
     */
    public function test_generateReport_getsBillingData_setsInactiveUsersBillingData()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('G3')->getValue(),
            $this->features->featuresBilledData[$this->fakePlatformData['name']]['users']['inactive_users']
        );
    }

    /**
     * Test generateReport gets stat data set deleted literal.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_setDeletedLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('H2')->getValue(),
            ucwords($this->features->reportLiterals['features-report-borrados'], '')
        );
    }

    /**
     * Test generateReport gets stat data set deleted users value.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_setDeletedUsersValue()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('H3')->getValue(),
            $this->features->featuresStatsData[$this->fakePlatformData['name']]['users']['deleted_users']
        );
    }

    /**
     * Test generateReport gets stat data set deleted cost literal.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_setDeletedCostLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('I2')->getValue(),
            ucwords($this->features->reportLiterals['features-report-coste'], '')
        );
    }

    /**
     * Test generateReport gets billing data sets deleted users billing data.
     *
     * @return void
     */
    public function test_generateReport_getsBillingData_setsDeletedUsersBillingData()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('I3')->getValue(),
            $this->features->featuresBilledData[$this->fakePlatformData['name']]['users']['deleted_users']
        );
    }

    /**
     * Test generateReport gets stat data set total users literal.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_setTotalUsersLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('J2')->getValue(),
            ucwords($this->features->reportLiterals['features-report-total'].' '.$this->features->categoriesTranslations['users'], '')
        );
    }

    /**
     * Test generateReport gets stat data set total user value.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_setTotalUserValue()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('J3')->getValue(),
            $this->features->featuresStatsData[$this->fakePlatformData['name']]['users']['active_users']+
            $this->features->featuresStatsData[$this->fakePlatformData['name']]['users']['inactive_users']+
            $this->features->featuresStatsData[$this->fakePlatformData['name']]['users']['deleted_users']
        );
    }

    /**
     * Test generateReport gets stat data set total cost literal.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_setTotalCostLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('K2')->getValue(),
            ucwords($this->features->reportLiterals['features-report-total-coste'], '')
        );
    }

    /**
     * Test generateReport gets billing data set total user value.
     *
     * @return void
     */
    public function test_generateReport_getsBillingData_setTotalUserValue()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('K3')->getValue(),
            $this->features->featuresBilledData[$this->fakePlatformData['name']]['users']['active_users']+
            $this->features->featuresBilledData[$this->fakePlatformData['name']]['users']['inactive_users']+
            $this->features->featuresBilledData[$this->fakePlatformData['name']]['users']['deleted_users']
        );
    }

    /**
     * Test generateReport gets platform data sets total storage contracted literal.
     *
     * @return void
     */
    public function test_generateReport_getsPlatformData_SetsTotalStorageContractedLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals($this->features->spreadsheet->getActiveSheet()->getCell('L2')->getValue(), ucwords($this->features->reportLiterals['features-report-total-contratado'], ''));
    }

    /**
     * Test generateReport gets platform data sets total storage hired.
     *
     * @return void
     */
    public function test_generateReport_getsPlatformData_setsTotalStorageHired()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('L3')->getValue(),
            $this->fakePlatformData['storage']
        );
    }

    /**
     * Test generateReport gets stat data set data base literal.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_setDataBaseLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('M2')->getValue(),
            ucwords($this->features->reportLiterals['features-report-base-de-datos'], '')
        );
    }

    /**
     * Test generateReport gets stats data sets database storage stat data.
     *
     * @return void
     */
    public function test_generateReport_getsStatsData_setsDatabaseStorageStatData()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('M3')->getValue(),
            $this->features->featuresStatsData[$this->fakePlatformData['name']]['storage']['database']
        );
    }

    /**
     * Test generateReport gets stat data set content literal.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_setContentLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('N2')->getValue(),
            ucwords($this->features->reportLiterals['features-report-contenido'], '')
        );
    }

    /**
     * Test generateReport gets stats data sets content storage stat data.
     *
     * @return void
     */
    public function test_generateReport_getsStatsData_setsContentStorageStatData()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('N3')->getValue(),
            $this->features->featuresStatsData[$this->fakePlatformData['name']]['storage']['content']
        );
    }

    /**
     * Test generateReport gets stat data set backups literal.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_setBackupsLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('O2')->getValue(),
            ucwords($this->features->reportLiterals['features-report-backups'], '')
        );
    }

    /**
     * Test generateReport gets stat data backups sum total value.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_backupsSumTotalValue()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('O3')->getValue(),
            $this->features->featuresStatsData[$this->fakePlatformData['name']]['storage']['primary_backup']+
            $this->features->featuresStatsData[$this->fakePlatformData['name']]['storage']['secondary_backup']+
            $this->features->featuresStatsData[$this->fakePlatformData['name']]['storage']['sql_backup']
        );
    }

    /**
     * Test generateReport gets stat data set storage total cost literal.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_setStorageTotalCostLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('Q2')->getValue(),
            ucwords($this->features->reportLiterals['features-report-total-coste'], '')
        );
    }

    /**
     * Test generateReport gets stat data sets total stats storage literal.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_SetsTotalStatsStorageLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('P2')->getValue(),
            ucwords($this->features->reportLiterals['features-report-total'], '')
        );
    }

    /**
     * Test generate report gets stat data sets total stats storage value.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_SetsTotalStatsStorageValue()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('P3')->getValue(),
            $this->features->featuresStatsData[$this->fakePlatformData['name']]['storage']['primary_backup']+
            $this->features->featuresStatsData[$this->fakePlatformData['name']]['storage']['secondary_backup']+
            $this->features->featuresStatsData[$this->fakePlatformData['name']]['storage']['sql_backup']+
            $this->features->featuresStatsData[$this->fakePlatformData['name']]['storage']['content']+
            $this->features->featuresStatsData[$this->fakePlatformData['name']]['storage']['database']
        );
    }

    /**
     * Test generateReport gets billing data sets total storage billing data.
     *
     * @return void
     */
    public function test_generateReport_getsBillingData_setsTotalStorageBillingData()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('Q3')->getValue(),
            $this->features->featuresBilledData[$this->fakePlatformData['name']]['storage']['storage']
        );
    }

    /**
     * Test generateReport gets stat data set total billing cost literal.
     *
     * @return void
     */
    public function test_generateReport_getsStatData_setTotalBillingCostLiteral()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('R2')->getValue(),
            ucwords($this->features->reportLiterals['features-report-coste-servicio'], '')
        );
    }

    /**
     * Test generateReport gets billing data set total billed value.
     *
     * @return void
     */
    public function test_generateReport_getsBillingData_setTotalBilledValue()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getCell('R3')->getValue(),
            $this->features->featuresBilledData[$this->fakePlatformData['name']]['users']['active_users']+
            $this->features->featuresBilledData[$this->fakePlatformData['name']]['users']['inactive_users']+
            $this->features->featuresBilledData[$this->fakePlatformData['name']]['users']['deleted_users']+
            $this->features->featuresBilledData[$this->fakePlatformData['name']]['storage']['storage']
        );
    }

    /**
     * Test generateReport create report has header borders.
     *
     * @return void
     */
    public function test_generateReport_createReport_hasHeaderBorders()
    {
        $this->prepareReportFakeData();
        $this->assertEquals(
            $this->features->spreadsheet->getActiveSheet()->getStyle('C1:R1')->getBorders()->getBottom()->getBorderStyle(),
            'thin'
        );
    }

    /**
     * Test generateCosts reports works fine return BMAction OK
     *
     * @return void
     */
    public function test_generateCostsReports_worksFine_ReturnBMActionOK()
    {
        $result = $this->prepareReportFakeData();
        $this->assertInstanceOf(BMActionOk::class, $result);
    }

    /**
     * Test generateCost reports works fine and report file was created.
     *
     * @return void
     */
    public function test_generateCostsReports_worksFine_ReportFileWasCreated()
    {
        $this->prepareReportFakeData();
        $this->assertFileExists(env('CACHE_FOLDER').'platform1-2018-12.xlsx');
    }

    /**
     * Test generateCost reports works fine and report file was deleted.
     *
     * @return void
     */
    public function test_generateCostsReports_getsDeleteFileOption_ReportFileWasDeleted()
    {
        $this->prepareReportFakeData(true);
        $this->assertFileNotExists(env('CACHE_FOLDER').'platform1-2018-12.xlsx');
    }

    /**
     * Common functions.
     */


    /**
     * Function to prepare fake data for report
     *
     * @param bool $delete delete or no files in bmaction.
     *
     * @return mixed
     */
    public function prepareReportFakeData(bool $delete = false)
    {
        $expectedResult = [
            1 =>['name' => 'platform1', 'storage' => 20, 'estimated_users' => 30]
        ];
        $this->setEnv();
        $this->platformRepositoryStub->method('getPlatformsIdsByCodeOrAll')
            ->willReturn($this->platformsIds);
        $this->repositoryStub->method('getPlatformDataForBillingReport')
            ->willReturn($expectedResult);
        $this->makeFeatureStatsStub($this->featuresStatsData);
        $this->makeBillingPeriodStub(1);
        $this->repositoryStub->method('getFatherCategoriesTranslates')
            ->willReturn($this->categoriesTranslations);
        $this->repositoryStub->method('getReportsLiterals')
            ->willReturn($this->reportLiterals);
        $this->makeFeaturesBilledStub($this->featuresBilledData);
        $this->createActionPostNormalizer();
        if ($delete) {
            $this->createActionPostNormalizerDelete();
        }
        $result = $this->features->generateCostsReports($this->ActionPostNormalizer);

        return $result;
    }

    /**
     * Features mocked basics.
     *
     * @return mixed
     */
    public function mockedFeatureBasics()
    {
        $this->createActionPostNormalizer();
        $this->setRepositoryLiteralsStub();
        $result  = $this->mockFeatures->generateCostsReports($this->ActionPostNormalizer);

        return $result;
    }

    /**
     * Mock some features methods.
     *
     * @param array $methods
     */
    public function mockSomeFeaturesMethods(array $methods)
    {
        $this->mockFeatures = $this->getMockBuilder(Features::class)
            ->setConstructorArgs([$this->repositoryStub, $this->fileManagerStub])
            ->setMethods($methods)->getMock();
    }

    /**
     * Function to set generateCostsReports with mocked dependencies.
     *
     * @return mixed (mostly BMActions)
     */
    public function setsGenerateCostReportsWithMockedDependencies()
    {
        $this->setEnv();
        $this->platformRepositoryStub->method('getPlatformsIdsByCodeOrAll')
            ->willReturn($this->platformsIds);
        $this->setRepositoryLiteralsStub();
        $this->setRepositoryTranslationsStub();
        $this->repositoryStub->method('getFeaturesBilled')
            ->willReturn([]);
        $this->createActionPostNormalizer();
        $result = $this->features->generateCostsReports($this->ActionPostNormalizer);

        return $result;
    }

    /**
     * Setting environment.
     *
     * @return void
     */
    public function setEnv()
    {
        putenv('JSON_STATUS_PATH=/var/www/oknmanager/src/OKNManager/ResponseStatuses/statuses.json');
        putenv('DEFAULT_LANGUAGE=es_ES');
        putenv('CACHE_FOLDER=/var/okn_tmp/');
    }
    /**
     * Create ActionPostNormalizer.
     *
     * @return void
     */
    public function createActionPostNormalizer()
    {
        $this->ActionPostNormalizer = new ActionPostNormalizer();
        $this->ActionPostNormalizer->setActionName('generateCostReport');
        $this->ActionPostNormalizer->setActionParams(['platformCode' => $this->platformCodes, 'period' => $this->period, 'deleteTempFiles'=>false]);
    }

    /*
     * Create ActionPostNormalizer with deleted false.
     *
     * @return void
     */
    public function createActionPostNormalizerDelete()
    {
        $this->ActionPostNormalizer = new ActionPostNormalizer();
        $this->ActionPostNormalizer->setActionName('generateCostReport');
        $this->ActionPostNormalizer->setActionParams(['platformCode' => $this->platformCodes, 'period' => $this->period, 'deleteTempFiles' => true]);
    }
    /**
     * Undocumented function
     *
     * @return void
     */
    public function setRepositoryLiteralsStub()
    {
        $this->repositoryStub->method('getReportsLiterals')
            ->willReturn($this->expectResultLiterals);
    }

    /**
     * Set repository translations stub.
     *
     * @return void
     */
    public function setRepositoryTranslationsStub()
    {
        $this->repositoryStub->method('getFatherCategoriesTranslates')
            ->willReturn($this->expectedResultTranslations);
    }

    /**
     * Make billing period stub.
     *
     * @param int $value
     *
     * @return void
     */
    public function makeBillingPeriodStub(?int $value)
    {
        $this->repositoryStub->method('getBillingPeriodByDate')
            ->willReturn($value);
    }

    /**
     * Make features billed stub
     *
     * @param array $value
     *
     * @return void
     */
    public function makeFeaturesBilledStub(?array $value)
    {
        $this->repositoryStub->method('getFeaturesBilled')
            ->willReturn($value);
    }

    /**
     * Make feature stats stub.
     *
     * @param array $value
     *
     * @return void
     */
    public function makeFeatureStatsStub(?array $value)
    {
        $this->repositoryStub->method('getFeaturesStats')
            ->willReturn($value);
    }

    /**
     * Test that function setFeatureData create the billing period if not exists
     *
     * @return void
     */
    public function test_setFeatureData_WhenBillingPeriodDoesntExists_ShouldCreateTheBillingPeriodAndReturnOkResponse()
    {
        $params = [
            'featureCode' => 'storage',
            'sourceFormat' => 'inline',
            'sourceData' => [
                'okn_test' => [
                    'backup1' => 100,
                    'backup2' => 100,
                    'gluster' => 100
                ]
            ]
        ];
        $code = '000005000';
        $expects = new BMActionOk('setFeatureData', $code, 'Set feature data action completed', 'All the data was stored.', false);

        $validAction = new ActionPostNormalizer();
        $validAction->setActionName('setFeatureData');
        $validAction->setActionParams($params);

        $featuresRepository = $this->getMockBuilder(FeaturesRepository::class)->getMock();
        $featuresRepository->expects($this->once())
                            ->method('existsBillingPeriod')
                            ->willReturn(false);
        
        $featuresRepository->expects($this->once())
                            ->method('createBillingPeriod')
                            ->willReturn(null);

        $featuresRepository->expects($this->once())
                            ->method('getBillingPeriodByDate')
                            ->willReturn(1);
        
        $featuresRepository->expects($this->once())
                            ->method('getIdFromFeatureCode')
                            ->with('storage')
                            ->willReturn(1);

        $features = new Features($featuresRepository, $this->platformRepositoryStub, $this->fileManagerStub, $this->fileRepository);
        $result = $features->setFeatureData($validAction);

        $this->assertEquals($expects, $result);
    }

    /**
     * Test that function setFeatureData create the feature data when receive a single and multiples stats for n platforms in source data with inline format
     *
     * @return void
     */
    public function test_setFeatureData_GivenAValidSourceDataWithInlineFormat_createTheFeatureData()
    {
        $platformIdByCode = [
            'okn_test-fake-1' => 1,
            'okn_test-fake-2' => 2
        ];
        $featureId = 2;
        $billingPeriod = 3;
        $params = [
            'featureCode' => 'test-fake-feature',
            'sourceFormat' => 'inline',
            'sourceData' => [
                'okn_test-fake-1' => 100,
                'okn_test-fake-2' => 150
            ]
        ];
        $rowToCreate = [
            [
                'platform_id' => 1,
                'feature_id' => $featureId,
                'billing_period_id' => $billingPeriod,
                'code' => 'test-fake-feature',
                'value' => 100
            ],
            [
                'platform_id' => 2,
                'feature_id' => $featureId,
                'billing_period_id' => $billingPeriod,
                'code' => 'test-fake-feature',
                'value' => 150
            ]
        ];

        $validAction = new ActionPostNormalizer();
        $validAction->setActionName('setFeatureData');
        $validAction->setActionParams($params);

        $platformRepository = $this->getMockBuilder(PlatformsRepository::class)->getMock();
        $platformRepository->expects($this->once())
                            ->method('getPlatformsIdsByCodeOrAll')
                            ->willReturn($platformIdByCode);

        $featuresRepository = $this->getMockBuilder(FeaturesRepository::class)->getMock();
        $featuresRepository->expects($this->once())
                            ->method('existsBillingPeriod')
                            ->willReturn(true);
        
        $featuresRepository->expects($this->once())
                            ->method('getBillingPeriodByDate')
                            ->willReturn($billingPeriod);

        $featuresRepository->expects($this->once())
                            ->method('getIdFromFeatureCode')
                            ->with('test-fake-feature')
                            ->willReturn($featureId);

        $featuresRepository->expects($this->once())
                            ->method('setFeaturesStatsByPlatforms')
                            ->with($rowToCreate);

        $features = new Features($featuresRepository, $platformRepository, $this->fileManagerStub, $this->fileRepository);
        $features->setFeatureData($validAction);
    }

    /**
     * Test that function setFeatureData create the feature data when receive all instead of code in source data
     *
     * @return void
     */
    public function test_setFeatureData_GivenAValidSourceDataWithAllKeyInsteadOfCodeWithInlineFormat_createTheFeatureData()
    {
        $platformIdByCode = [
            'okn_test-fake-1' => 1,
            'okn_test-fake-2' => 2
        ];
        $featureId = 2;
        $billingPeriod = 3;
        $params = [
            'featureCode' => 'test-fake-feature',
            'sourceFormat' => 'inline',
            'sourceData' => [
                'all' => 100
            ]
        ];
        $rowToCreate = [
            [
                'platform_id' => 1,
                'feature_id' => $featureId,
                'billing_period_id' => $billingPeriod,
                'code' => 'test-fake-feature',
                'value' => 100
            ],
            [
                'platform_id' => 2,
                'feature_id' => $featureId,
                'billing_period_id' => $billingPeriod,
                'code' => 'test-fake-feature',
                'value' => 100
            ]
        ];

        $validAction = new ActionPostNormalizer();
        $validAction->setActionName('setFeatureData');
        $validAction->setActionParams($params);

        $platformRepository = $this->getMockBuilder(PlatformsRepository::class)->getMock();
        $platformRepository->expects($this->once())
                            ->method('getPlatformsIdsByCodeOrAll')
                            ->willReturn($platformIdByCode);

        $featuresRepository = $this->getMockBuilder(FeaturesRepository::class)->getMock();
        $featuresRepository->expects($this->once())
                            ->method('existsBillingPeriod')
                            ->willReturn(true);
        
        $featuresRepository->expects($this->once())
                            ->method('getBillingPeriodByDate')
                            ->willReturn($billingPeriod);

        $featuresRepository->expects($this->once())
                            ->method('getIdFromFeatureCode')
                            ->with('test-fake-feature')
                            ->willReturn($featureId);

        $featuresRepository->expects($this->once())
                            ->method('setFeaturesStatsByPlatforms')
                            ->with($rowToCreate);

        $features = new Features($featuresRepository, $platformRepository, $this->fileManagerStub, $this->fileRepository);
        $features->setFeatureData($validAction);
    }

    /**
     * Test that function setFeatureData create the feature data when receive a single file in sourceData with blob format
     *
     * @return void
     *
     * @dataProvider setFeatureDataBlobProviderSingleFile
     */
    public function test_setFeatureData_GivenAValidSingleFileInBlobFormat_createsTheFeatureStats(int $featureId, int $billingPeriod, string $fileContent, array $params, array $rowExpected, array $platforms)
    {
        $validAction = new ActionPostNormalizer();
        $validAction->setActionName('setFeatureData');
        $validAction->setActionParams($params);

        $features = $this->prepareSetFeatureDataBlobSingleFile($fileContent, $rowExpected, $billingPeriod, $featureId, $platforms);
        $features->setFeatureData($validAction);
    }

    /**
    * Test that function setFeatureData create the feature data when receive multiples files in sourceData with blob format
    *
    * @return void
    */
    public function test_setFeatureData_GivenAValidMultiplesFilesInBlobFormat_createsTheFeatureStats()
    {
        $featureId= 2;
        $billingPeriod = 3;
        $fileContent = [
            'okn_test-fake-1;test-fake-feature;150',
            'okn_test-fake-2;test-fake-feature;100'
        ];
        $fileData = [
            [
                'id' => 1,
                'path' => 'test',
                'filename' => 'test.csv',
                'size' => 100
            ],
            [
                'id' => 1,
                'path' => 'test',
                'filename' => 'test_2.csv',
                'size' => 120
            ]
        ];
        $params = [
            'featureCode' => 'test-fake-feature',
            'sourceFormat' => 'blob',
            'sourceData' => [
                'okn_test-fake-1' => 'httpS://testfake.test/test/test.csv',
                'okn_test-fake-2' => 'httpS://testfake.test/test/test_2.csv'
            ]
        ];
        $rowExpected = [
            [
                'platform_id' => 1,
                'feature_id' => 2,
                'billing_period_id' => 3,
                'code' => 'test-fake-feature',
                'value' => 150
            ],
            [
                'platform_id' => 2,
                'feature_id' => 2,
                'billing_period_id' => 3,
                'code' => 'test-fake-feature',
                'value' => 100
            ]
        ];
        $platforms = [
            'okn_test-fake-1' => 1,
            'okn_test-fake-2' => 2
        ];

        $validAction = new ActionPostNormalizer();
        $validAction->setActionName('setFeatureData');
        $validAction->setActionParams($params);

        $features = $this->prepareSetFeatureDataBlobMultiFile($fileData, $fileContent, $rowExpected, $billingPeriod, $featureId, $platforms);
        $features->setFeatureData($validAction);
    }

    /**
     * Provider different inputs of the blob file
     *
     * @return void
     */
    public function setFeatureDataBlobProviderSingleFile()
    {
        return [
            [
                2,
                3,
                'okn_test-fake-1;test-fake-feature;150',
                [
                    'featureCode' => 'test-fake-feature',
                    'sourceFormat' => 'blob',
                    'sourceData' => [
                        'okn_test-fake-1' => 'httpS://testfake.test/test/test.csv'
                    ]
                ],
                [
                    [
                        'platform_id' => 1,
                        'feature_id' => 2,
                        'billing_period_id' => 3,
                        'code' => 'test-fake-feature',
                        'value' => 150
                    ]
                ],
                [
                    'okn_test-fake-1' => 1
                ]
            ],
            [
                2,
                3,
                "okn_test-fake-1;test-fake-stat1;150\nokn_test-fake-1;test-fake-stat2;100\nokn_test-fake-1;test-fake-stat3;110",
                [
                    'featureCode' => 'test-fake-feature',
                    'sourceFormat' => 'blob',
                    'sourceData' => [
                        'okn_test-fake-1' => 'httpS://testfake.test/test/test.csv'
                    ]
                ],
                [
                    [
                        'platform_id' => 1,
                        'feature_id' => 2,
                        'billing_period_id' => 3,
                        'code' => 'test-fake-stat1',
                        'value' => 150
                    ],
                    [
                        'platform_id' => 1,
                        'feature_id' => 2,
                        'billing_period_id' => 3,
                        'code' => 'test-fake-stat2',
                        'value' => 100
                    ],
                    [
                        'platform_id' => 1,
                        'feature_id' => 2,
                        'billing_period_id' => 3,
                        'code' => 'test-fake-stat3',
                        'value' => 110
                    ]
                ],
                [
                    'okn_test-fake-1' => 1
                ]
            ],
            [
                2,
                3,
                "okn_test-fake-1;test-fake-stat1;150\nokn_test-fake-2;test-fake-stat1;100\nokn_test-fake-3;test-fake-stat1;110",
                [
                    'featureCode' => 'test-fake-feature',
                    'sourceFormat' => 'blob',
                    'sourceData' => [
                        'all' => 'httpS://testfake.test/test/test.csv'
                    ]
                ],
                [
                    [
                        'platform_id' => 1,
                        'feature_id' => 2,
                        'billing_period_id' => 3,
                        'code' => 'test-fake-stat1',
                        'value' => 150
                    ],
                    [
                        'platform_id' => 2,
                        'feature_id' => 2,
                        'billing_period_id' => 3,
                        'code' => 'test-fake-stat1',
                        'value' => 100
                    ],
                    [
                        'platform_id' => 3,
                        'feature_id' => 2,
                        'billing_period_id' => 3,
                        'code' => 'test-fake-stat1',
                        'value' => 110
                    ]
                ],
                [
                    'okn_test-fake-1' => 1,
                    'okn_test-fake-2' => 2,
                    'okn_test-fake-3' => 3
                ]
            ]
        ];
    }
    
    /**
     * Function to mock basic functionality and returns a Features BM to test for the single blob tests
     *
     * @param string $fileContent      The file content of the csv inside of the blob
     * @param array  $rowExpected      The expected array result for the given input
     * @param int    $billingPeriod    The billing period id of the feature
     * @param int    $featureId        The feature id of the test
     * @param array  $platformIdByCode List of the platforms ids key by it's code
     *
     * @return \OKNManager\BM\Features The business model with the mocked dependencies
     */
    private function prepareSetFeatureDataBlobSingleFile(string $fileContent, array $rowExpected, int $billingPeriod, int $featureId, array $platformIdByCode):Features
    {
        $rowToCreate = $rowExpected;

        $fileData = [
            'id' => 1,
            'path' => '/tmp_path',
            'filename' => 'test.csv',
            'size' => 100
        ];
        
        $platformRepository = $this->getMockBuilder(PlatformsRepository::class)->getMock();
        $platformRepository->expects($this->once())
                            ->method('getPlatformsIdsByCodeOrAll')
                            ->willReturn($platformIdByCode);

        $featuresRepository = $this->getMockBuilder(FeaturesRepository::class)->getMock();
        $featuresRepository->expects($this->once())
                            ->method('existsBillingPeriod')
                            ->willReturn(true);
        
        $featuresRepository->expects($this->once())
                            ->method('getBillingPeriodByDate')
                            ->willReturn($billingPeriod);

        $featuresRepository->expects($this->once())
                            ->method('getIdFromFeatureCode')
                            ->with('test-fake-feature')
                            ->willReturn($featureId);
                            
        $filesRepository = $this->getMockBuilder(FilesRepository::class)->getMock();
        $filesRepository->expects($this->once())
                        ->method('getFileByUrl')
                        ->willReturn($fileData);

        $fileManager = $this->getMockBuilder(FileManager::class)->disableOriginalConstructor()->getMock();
        $fileManager->expects($this->once())
                    ->method('getFileContents')
                    ->with($fileData['id'])
                    ->willReturn($fileContent);

        $featuresRepository->expects($this->once())
                            ->method('setFeaturesStatsByPlatforms')
                            ->with($rowToCreate);

        $features = new Features($featuresRepository, $platformRepository, $fileManager, $filesRepository);

        return $features;
    }
    
    /**
    * Function to mock basic functionality and returns a Features BM to test for the single blob tests
    *
    * @param array  $fileData         An array of files data, for multiple files input
    * @param string $fileContent      The file content of the csv inside of the blob
    * @param array  $rowExpected      The expected array result for the given input
    * @param int    $billingPeriod    The billing period id of the feature
    * @param int    $featureId        The feature id of the test
    * @param array  $platformIdByCode List of the platforms ids key by it's code
    *
    * @return \OKNManager\BM\Features The business model with the mocked dependencies
    */
    private function prepareSetFeatureDataBlobMultiFile(array $fileData, array $fileContent, array $rowExpected, int $billingPeriod, int $featureId, array $platformIdByCode):Features
    {
        $rowToCreate = $rowExpected;

        $platformRepository = $this->getMockBuilder(PlatformsRepository::class)->getMock();
        $platformRepository->expects($this->once())
                            ->method('getPlatformsIdsByCodeOrAll')
                            ->willReturn($platformIdByCode);

        $featuresRepository = $this->getMockBuilder(FeaturesRepository::class)->getMock();
        $featuresRepository->expects($this->once())
                            ->method('existsBillingPeriod')
                            ->willReturn(true);
        
        $featuresRepository->expects($this->once())
                            ->method('getBillingPeriodByDate')
                            ->willReturn($billingPeriod);

        $featuresRepository->expects($this->once())
                            ->method('getIdFromFeatureCode')
                            ->with('test-fake-feature')
                            ->willReturn($featureId);
                            
        $filesRepository = $this->getMockBuilder(FilesRepository::class)->getMock();
        $filesRepository->expects($this->exactly(2))
                        ->method('getFileByUrl')
                        ->willReturnOnConsecutiveCalls($fileData[0], $fileData[1]);

        $fileManager = $this->getMockBuilder(FileManager::class)->disableOriginalConstructor()->getMock();
        $fileManager->expects($this->exactly(2))
                    ->method('getFileContents')
                    ->withConsecutive([$fileData[0]['id']], [$fileData[1]['id']])
                    ->willReturnOnConsecutiveCalls($fileContent[0], $fileContent[1]);

        $featuresRepository->expects($this->once())
                            ->method('setFeaturesStatsByPlatforms')
                            ->with($rowToCreate);

        $features = new Features($featuresRepository, $platformRepository, $fileManager, $filesRepository);

        return $features;
    }
}
