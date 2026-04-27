<?php

namespace Database\Seeders;

use App\Models\ErpNumberSequence;
use App\Models\ErpWorkflow;
use App\Models\ErpWorkflowStep;
use Illuminate\Database\Seeder;

class ErpWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workflows = [
            [
                'erp_workflow_name' => 'Contratos',
                'erp_workflow_slug' => 'contracts',
                'erp_workflow_description' => 'Flujo principal para contratos',
                'steps' => [
                    [
                        'name' => 'Creación por coordinador',
                        'slug' => 'coordinator_creation',
                        'role' => 'coordinator',
                        'order' => 1,
                        'requires_external_signature' => false,
                    ],
                    [
                        'name' => 'Revisión administrativa',
                        'slug' => 'administrative_review',
                        'role' => 'administrative',
                        'order' => 2,
                        'requires_external_signature' => false,
                    ],
                    [
                        'name' => 'Revisión jurídica',
                        'slug' => 'legal_review',
                        'role' => 'legal',
                        'order' => 3,
                        'requires_external_signature' => false,
                    ],
                    [
                        'name' => 'Aprobación dirección',
                        'slug' => 'direction_approval',
                        'role' => 'administrative_financial_direction',
                        'order' => 4,
                        'requires_approval' => true,
                        'requires_external_signature' => false,
                    ],
                    [
                        'name' => 'Firma contratista',
                        'slug' => 'contractor_signature',
                        'role' => 'coordinator',
                        'order' => 5,
                        'requires_external_signature' => true,
                    ],
                    [
                        'name' => 'Cierre coordinador',
                        'slug' => 'coordinator_close',
                        'role' => 'coordinator',
                        'order' => 6,
                        'is_final' => true,
                        'requires_external_signature' => false,
                    ],
                ],
            ],
            [
                'erp_workflow_name' => 'Otrosíes',
                'erp_workflow_slug' => 'contract_addendums',
                'erp_workflow_description' => 'Flujo principal para otrosíes de contrato',
                'steps' => [
                    [
                        'name' => 'Creación por coordinador',
                        'slug' => 'coordinator_creation',
                        'role' => 'coordinator',
                        'order' => 1,
                        'requires_external_signature' => false,
                    ],
                    [
                        'name' => 'Revisión administrativa',
                        'slug' => 'administrative_review',
                        'role' => 'administrative',
                        'order' => 2,
                        'requires_external_signature' => false,
                    ],
                    [
                        'name' => 'Revisión jurídica',
                        'slug' => 'legal_review',
                        'role' => 'legal',
                        'order' => 3,
                        'requires_external_signature' => false,
                    ],
                    [
                        'name' => 'Aprobación dirección',
                        'slug' => 'direction_approval',
                        'role' => 'administrative_financial_direction',
                        'order' => 4,
                        'requires_approval' => true,
                        'requires_external_signature' => false,
                    ],
                    [
                        'name' => 'Firma contratista',
                        'slug' => 'contractor_signature',
                        'role' => 'coordinator',
                        'order' => 5,
                        'requires_external_signature' => true,
                    ],
                    [
                        'name' => 'Cierre coordinador',
                        'slug' => 'coordinator_close',
                        'role' => 'coordinator',
                        'order' => 6,
                        'is_final' => true,
                        'requires_external_signature' => false,
                    ],
                ],
            ],
        ];

        foreach ($workflows as $workflowData) {
            $workflow = ErpWorkflow::query()->updateOrCreate(
                ['erp_workflow_slug' => $workflowData['erp_workflow_slug']],
                [
                    'erp_workflow_name' => $workflowData['erp_workflow_name'],
                    'erp_workflow_description' => $workflowData['erp_workflow_description'],
                    'erp_workflow_is_active' => true,
                ],
            );

            $newSlugs = array_column($workflowData['steps'], 'slug');

            // Deactivate old steps not in the new workflow
            ErpWorkflowStep::query()
                ->where('erp_workflow_step_workflow_id', $workflow->erp_workflow_id)
                ->whereNotIn('erp_workflow_step_slug', $newSlugs)
                ->update(['erp_workflow_step_is_active' => false]);

            foreach ($workflowData['steps'] as $stepData) {
                ErpWorkflowStep::query()->updateOrCreate(
                    [
                        'erp_workflow_step_workflow_id' => $workflow->erp_workflow_id,
                        'erp_workflow_step_slug' => $stepData['slug'],
                    ],
                    [
                        'erp_workflow_step_name' => $stepData['name'],
                        'erp_workflow_step_description' => $stepData['name'],
                        'erp_workflow_step_responsible_role_name' => $stepData['role'],
                        'erp_workflow_step_order' => $stepData['order'],
                        'erp_workflow_step_requires_approval' => $stepData['requires_approval'] ?? false,
                        'erp_workflow_step_is_final' => $stepData['is_final'] ?? false,
                        'erp_workflow_step_is_active' => true,
                        'erp_workflow_step_requires_external_signature' => $stepData['requires_external_signature'] ?? false,
                    ],
                );
            }
        }

        $numberSequences = [
            ['erp_number_sequence_key' => 'process', 'erp_number_sequence_prefix' => 'PR-', 'erp_number_sequence_padding' => 6],
            ['erp_number_sequence_key' => 'contract', 'erp_number_sequence_prefix' => 'CT-', 'erp_number_sequence_padding' => 5],
            ['erp_number_sequence_key' => 'contract_addendum', 'erp_number_sequence_prefix' => 'OT-', 'erp_number_sequence_padding' => 5],
        ];

        foreach ($numberSequences as $sequenceData) {
            ErpNumberSequence::query()->updateOrCreate(
                ['erp_number_sequence_key' => $sequenceData['erp_number_sequence_key']],
                [
                    'erp_number_sequence_prefix' => $sequenceData['erp_number_sequence_prefix'],
                    'erp_number_sequence_padding' => $sequenceData['erp_number_sequence_padding'],
                ],
            );
        }
    }
}
