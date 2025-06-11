<?php

declare(strict_types=1);

namespace App\Repositories\MedicalEvents;

use App\Models\MedicalEvents\Sql\DiagnosticReportPerformer;
use App\Models\MedicalEvents\Sql\DiagnosticReportResultsInterpreter;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DiagnosticReportRepository extends BaseRepository
{
    /**
     * Store condition in DB.
     *
     * @param  array  $data
     * @param  int  $createdEncounterId
     * @return void
     * @throws Throwable
     */
    public function store(array $data, int $createdEncounterId): void
    {
        DB::transaction(function () use ($data, $createdEncounterId) {
            try {
                foreach ($data as $datum) {
                    $code = Repository::identifier()->store($datum['code']['identifier']['value']);
                    Repository::codeableConcept()->attach($code, $datum['code']);

                    if (isset($datum['conclusionCode'])) {
                        $conclusionCode = Repository::codeableConcept()->store($datum['conclusionCode']);
                    }

                    $recordedBy = Repository::identifier()->store($datum['recordedBy']['identifier']['value']);
                    Repository::codeableConcept()->attach($recordedBy, $datum['recordedBy']);

                    $encounter = Repository::identifier()->store($datum['encounter']['identifier']['value']);
                    Repository::codeableConcept()->attach($encounter, $datum['encounter']);

                    $managingOrganization = Repository::identifier()->store(
                        $datum['managingOrganization']['identifier']['value']
                    );
                    Repository::codeableConcept()->attach($managingOrganization, $datum['managingOrganization']);

                    $division = Repository::identifier()->store($datum['division']['identifier']['value']);
                    Repository::codeableConcept()->attach($division, $datum['division']);

                    if (isset($datum['reportOrigin'])) {
                        $reportOrigin = Repository::codeableConcept()->store($datum['reportOrigin']);
                    }

                    $diagnosticReport = $this->model::create([
                        'uuid' => $datum['uuid'] ?? $datum['id'],
                        'encounter_internal_id' => $createdEncounterId,
                        'status' => $datum['status'],
                        'code_id' => $code->id,
                        'issued' => $datum['issued'],
                        'conclusion' => $datum['conclusion'] ?? null,
                        'conclusion_code_id' => $conclusionCode->id ?? null,
                        'recorded_by_id' => $recordedBy->id,
                        'encounter_id' => $encounter->id,
                        'primary_source' => $datum['primarySource'],
                        'managing_organization_id' => $managingOrganization->id,
                        'division_id' => $division->id,
                        'report_origin_id' => $reportOrigin->id ?? null
                    ]);

                    if (isset($datum['paperReferral'])) {
                        $diagnosticReport->paperReferral()->create([
                            'requisition' => $datum['paperReferral']['requisition'] ?? null,
                            'requester_legal_entity_name' => $datum['paperReferral']['requesterLegalEntityName'] ?? null,
                            'requester_legal_entity_edrpou' => $datum['paperReferral']['requesterLegalEntityEdrpou'],
                            'requester_employee_name' => $datum['paperReferral']['requesterEmployeeName'],
                            'service_request_date' => $datum['paperReferral']['serviceRequestDate'],
                            'note' => $datum['paperReferral']['note'] ?? null
                        ]);
                    }

                    $categoryIds = [];
                    foreach ($datum['category'] as $categoryData) {
                        $category = Repository::codeableConcept()->store($categoryData);

                        $categoryIds[] = $category->id;
                    }

                    $diagnosticReport->category()->attach($categoryIds);

                    $diagnosticReport->effectivePeriod()->create([
                        'start' => $datum['effectivePeriod']['start'],
                        'end' => $datum['effectivePeriod']['end']
                    ]);

                    if (isset($datum['performer'])) {
                        if (isset($datum['performer']['reference'])) {
                            $reference = Repository::identifier()->store(
                                $datum['performer']['reference']['identifier']['value']
                            );
                            Repository::codeableConcept()->attach($reference, $datum['performer']['reference']);
                        }

                        DiagnosticReportPerformer::create([
                            'diagnostic_report_id' => $diagnosticReport->id,
                            'reference_id' => $reference->id ?? null,
                            'text' => $datum['performer']['text'] ?? null
                        ]);
                    }

                    if (isset($datum['resultsInterpreter'])) {
                        if (isset($datum['resultsInterpreter']['reference'])) {
                            $reference = Repository::identifier()->store(
                                $datum['resultsInterpreter']['reference']['identifier']['value']
                            );
                            Repository::codeableConcept()->attach(
                                $reference,
                                $datum['resultsInterpreter']['reference']
                            );
                        }

                        DiagnosticReportResultsInterpreter::create([
                            'diagnostic_report_id' => $diagnosticReport->id,
                            'reference_id' => $reference->id ?? null,
                            'text' => $datum['resultsInterpreter']['text'] ?? null
                        ]);
                    }
                }
            } catch (Exception $e) {
                Log::channel('db_errors')->error('Error saving condition', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);

                throw $e;
            }
        });
    }

    /**
     * Get condition data that is related to the encounter.
     *
     * @param  int  $encounterId
     * @return array|null
     */
    public function get(int $encounterId): ?array
    {
        $results = $this->model::with([
            'basedOn.type.coding',
            'paperReferral',
            'code.type.coding',
            'category.coding',
            'effectivePeriod',
            'conclusionCode.coding',
            'recordedBy.type.coding',
            'encounter.type.coding',
            'managingOrganization.type.coding',
            'division.type.coding',
            'performer.reference',
            'reportOrigin.coding',
            'resultsInterpreter.reference'
        ])
            ->where('encounter_internal_id', $encounterId)
            ->get()
            ->toArray();

        // Hide array of relationship data, accessories are used
        return array_map(static fn (array $item) => Arr::except($item, ['effectivePeriod']), $results);
    }

    /**
     * Formatting to show on the frontend.
     *
     * @param  array  $diagnosticReports
     * @return array
     */
    public function formatForView(array $diagnosticReports): array
    {
        return array_map(static function (array $diagnosticReport) {
            // Set value to checkbox isReferralAvailable
            if (empty($diagnosticReport['basedOn']) && empty($diagnosticReport['paperReferral'])) {
                $diagnosticReport['isReferralAvailable'] = false;
            } else {
                $diagnosticReport['isReferralAvailable'] = true;
            }

            // Set referral type if referral is available
            if ($diagnosticReport['isReferralAvailable']) {
                $diagnosticReport['referralType'] = !empty($diagnosticReport['basedOn']) ? 'electronic' : 'paper';
            }

            // Set default value to avoid error
            if (empty($diagnosticReport['reportOrigin'])) {
                $diagnosticReport['reportOrigin'] = [
                    'coding' => [
                        ['code' => '']
                    ]
                ];
            }

            return $diagnosticReport;
        }, $diagnosticReports);
    }
}
