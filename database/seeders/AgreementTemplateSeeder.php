<?php

namespace Database\Seeders;

use App\Models\Aftersales\RefAgreementTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgreementTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            'A' => ['Scope of Work', [
                'Daxtro technicians only carry out work in accordance with the agreed Work Order, Work Instruction, or Purchase Order.',
                'Work outside the agreed scope will only be carried out after written approval from the Customer.',
                'If additional damage is found, the Technician may pause work and submit an additional quotation.',
            ]],
            'B' => ['Site Access', [
                'The Customer must provide safe working access to the Technician.',
                'The work area must be free from hazards that could endanger the Technician.',
                'Required access permits must be prepared before the scheduled visit.',
            ]],
            'C' => ['Work Safety', [
                'All parties must comply with site safety procedures.',
                'Technicians will use the required personal protective equipment.',
                'Unsafe work may be suspended until the hazard is resolved.',
            ]],
            'D' => ['Unit Disassembly', [
                'The Customer authorizes the disassembly of the unit where required for inspection or repair.',
                'Hidden damage may only be identified after disassembly and will be reported before further work.',
                'Components removed during the work will be documented and returned on request.',
            ]],
            'E' => ['Spare Parts', [
                'Replacement parts require Customer or Supervisor approval before installation.',
                'Part availability may affect the completion schedule.',
                'Replaced parts are covered by the warranty terms of the part supplier.',
            ]],
            'F' => ['Functional Test', [
                'Operational testing is performed after the work is completed.',
                'Customer representatives may witness the final test.',
                'Test results will be recorded in the Service Form.',
            ]],
            'G' => ['Work Warranty', [
                'Warranty follows the approved work scope and is limited to the work performed.',
                'Consumables and damage from external causes are excluded unless stated otherwise.',
                'Warranty claims require the related service document.',
            ]],
            'H' => ['Customer Responsibility', [
                'The Customer provides the utilities required for the work (electricity, water, drainage) in accordance with the machine specification.',
            ]],
            'I' => ['Limitation of Liability', [
                'Daxtro is not responsible for conditions outside the agreed work scope, including external site conditions and production losses.',
            ]],
            'J' => ['Additional Work', [
                'Additional costs for work not included in the original scope require approval before execution.',
            ]],
            'K' => ['Work Cancellation', [
                'Cancellation after the Technician has arrived on site may incur transport and standby charges.',
            ]],
            'L' => ['Force Majeure', [
                'Neither party is liable for delays caused by events beyond reasonable control.',
            ]],
            'M' => ['Documentation', [
                'Technical readings may be recorded for service documentation.',
                'Machine photos may be attached to the service report.',
                'Recorded data is used only for service and quality purposes.',
            ]],
            'N' => ['Work Result Approval', [
                'The Customer confirms the machine condition after service, and any remaining issue is recorded before approval.',
            ]],
            'O' => ['Dispute Resolution', [
                'Disputes are resolved by discussion first; unresolved disputes follow the applicable agreement between the parties.',
            ]],
        ];

        $content = [];
        foreach ($sections as $code => [$title, $clauses]) {
            $content[] = [
                'code' => $code,
                'title' => $title,
                'clauses' => collect($clauses)->map(fn ($text, $i) => ['key' => $code . ($i + 1), 'text' => $text])->all(),
            ];
        }

        $template = RefAgreementTemplate::firstOrCreate(
            ['version' => 1],
            [
                'title' => 'Service / Repair Work Agreement Sheet',
                'sections' => $content,
                'closing_statement' => 'By signing this document, the Customer declares that they have read, understood, and agree to all the terms and conditions stated in this Service / Repair Work Agreement.',
                'is_active' => true,
                'status' => 'active',
                'activated_at' => now(),
            ]
        );
        if ($template->wasRecentlyCreated) {
            DB::table('agreement_template_state')->where('id', 1)->whereNull('active_template_id')->update(['active_template_id' => $template->id]);
        }
    }
}
