<?php

use App\Models\ErpContract;
use App\Models\ErpThirdParty;
use Database\Seeders\ErpWorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a contract with its process and generated codes', function () {
    $this->seed(ErpWorkflowSeeder::class);

    $thirdParty = ErpThirdParty::query()->create([
        'erp_third_party_name' => 'Contratista Demo',
    ]);

    $response = $this
        ->withHeader('X-Workspace-User-Id', '10')
        ->postJson('/api/erp/contracts', [
            'erp_contract_contractor_id' => $thirdParty->erp_third_party_id,
            'erp_contract_title' => 'Contrato inicial',
            'erp_contract_object' => 'Prestación de servicios',
            'erp_contract_total_value' => 1000000,
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.erp_contract_code', 'CT-00001')
        ->assertJsonPath('data.process.erp_process_code', 'PR-000001');

    expect(ErpContract::query()->count())->toBe(1);
    expect($response->json('data.process.erp_process_status'))->toBe('draft');
});

it('creates a contract addendum with its own process and sequence', function () {
    $this->seed(ErpWorkflowSeeder::class);

    $thirdParty = ErpThirdParty::query()->create([
        'erp_third_party_name' => 'Contratista Demo',
    ]);

    $contractResponse = $this
        ->withHeader('X-Workspace-User-Id', '10')
        ->postJson('/api/erp/contracts', [
            'erp_contract_contractor_id' => $thirdParty->erp_third_party_id,
            'erp_contract_title' => 'Contrato base',
            'erp_contract_object' => 'Prestación de servicios',
        ]);

    $contractId = $contractResponse->json('data.erp_contract_id');

    $response = $this
        ->withHeader('X-Workspace-User-Id', '11')
        ->postJson('/api/erp/contract-addendums', [
            'erp_contract_addendum_contract_id' => $contractId,
            'erp_contract_addendum_title' => 'Otrosí de valor',
            'erp_contract_addendum_description' => 'Ajuste presupuestal',
            'erp_contract_addendum_type' => 'value',
        ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.erp_contract_addendum_code', 'OT-00001')
        ->assertJsonPath('data.process.erp_process_code', 'PR-000002');
});
