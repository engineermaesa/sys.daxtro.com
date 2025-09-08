<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\UserRole;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportLeadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_download_template(): void
    {
        $role = UserRole::create(['name' => 'Super Admin', 'code' => 'super_admin']);
        $user = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($user)->get('/leads/import/template');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $tmp = tmpfile();
        fwrite($tmp, $response->streamedContent());
        $meta = stream_get_meta_data($tmp);
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($meta['uri']);
        fclose($tmp);

        $this->assertEquals(
            ['Import', 'Lead Sources', 'Lead Segments', 'Regions', 'Sales NIP'],
            $spreadsheet->getSheetNames()
        );

        $header = $spreadsheet->getSheet(0)->rangeToArray('A1:I1', null, true, true, true)[1];
        $this->assertEquals(
            ['A' => 'source_id*', 'B' => 'segment_id*', 'C' => 'region_id*', 'D' => 'lead_name', 'E' => 'lead_email', 'F' => 'lead_phone', 'G' => 'lead_needs', 'H' => 'nip_sales', 'I' => 'published_at'],
            $header
        );
    }
}
