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
                    ['name' => 'Crear solicitud', 'slug' => 'create_request', 'role' => 'Coordinador', 'order' => 1],
                    ['name' => 'Ingresar datos contratista', 'slug' => 'contractor_data', 'role' => 'Coordinador', 'order' => 2],
                    ['name' => 'Adjuntar documentos', 'slug' => 'upload_documents', 'role' => 'Coordinador', 'order' => 3],
                    ['name' => 'Revisar documentación', 'slug' => 'documentation_review', 'role' => 'Administrativo', 'order' => 4, 'requires_approval' => true],
                    ['name' => 'Elaborar o cargar contrato', 'slug' => 'prepare_contract', 'role' => 'Jurídica', 'order' => 5],
                    ['name' => 'Aprobar dirección', 'slug' => 'direction_approval', 'role' => 'Dirección administrativa y financiera', 'order' => 6, 'requires_approval' => true],
                    ['name' => 'Firma contratista', 'slug' => 'contractor_signature', 'role' => 'Contratista', 'order' => 7],
                    ['name' => 'Cargar contrato firmado', 'slug' => 'upload_signed_contract', 'role' => 'Coordinador', 'order' => 8],
                    ['name' => 'Proceso cerrado', 'slug' => 'closed', 'role' => 'Administrativo', 'order' => 9, 'is_final' => true],
                ],
            ],
            [
                'erp_workflow_name' => 'Otrosíes',
                'erp_workflow_slug' => 'contract_addendums',
                'erp_workflow_description' => 'Flujo principal para otrosíes de contrato',
                'steps' => [
                    ['name' => 'Buscar contrato existente', 'slug' => 'find_contract', 'role' => 'Coordinador', 'order' => 1],
                    ['name' => 'Seleccionar tipo de modificación', 'slug' => 'select_addendum_type', 'role' => 'Coordinador', 'order' => 2],
                    ['name' => 'Diligenciar cambios', 'slug' => 'fill_changes', 'role' => 'Coordinador', 'order' => 3],
                    ['name' => 'Adjuntar soportes', 'slug' => 'upload_supports', 'role' => 'Coordinador', 'order' => 4],
                    ['name' => 'Elaboración jurídica', 'slug' => 'legal_preparation', 'role' => 'Jurídica', 'order' => 5],
                    ['name' => 'Aprobación', 'slug' => 'approval', 'role' => 'Dirección administrativa y financiera', 'order' => 6, 'requires_approval' => true],
                    ['name' => 'Firma', 'slug' => 'signature', 'role' => 'Coordinador', 'order' => 7],
                    ['name' => 'Cierre', 'slug' => 'closed', 'role' => 'Administrativo', 'order' => 8, 'is_final' => true],
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
