<?php

namespace Tests\Feature\Leads;

use App\Models\Leads\{Lead, LeadClaim};
use App\Models\Orders\{Quotation, TempQuotationItem, TempQuotationPaymentTerm};
use App\Models\{User, UserRole};
use Dompdf\{Dompdf, Options};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\Group('quotation-revision')]
class QuotationPdfRenderTest extends TestCase
{
    public function test_real_pdf_contains_a_valid_page_and_clickable_company_website(): void
    {
        // All document data stays in memory; font generation uses an isolated temporary directory.
        $lead = new Lead(['name' => 'PDF Customer', 'company' => 'Customer Company', 'phone' => '08123456789', 'company_address' => 'Jakarta']);
        $lead->id = 1;
        $sales = new User(['name' => 'PDF Sales']);
        $sales->setRelation('role', new UserRole(['name' => 'Sales']));
        $claim = new LeadClaim;
        $claim->setRelation('sales', $sales);
        $document = new Quotation([
            'quotation_no' => 'QT_DEAL_901', 'revision_no' => 1, 'revision_code' => 'QT_DEAL_901_R1',
            'subtotal' => 1000000, 'tax_pct' => 11, 'tax_total' => 110000, 'grand_total' => 1110000,
        ]);
        $item = new TempQuotationItem([
            'description' => 'PDF Snapshot Machine', 'qty' => 1, 'unit_price' => 1000000,
            'discount_pct' => 0, 'line_total' => 1000000, 'is_visible_pdf' => true,
        ]);
        $item->id = 1;
        $item->setRelation('product', null);
        $document->setRelation('lead', $lead)->setRelation('items', new Collection([$item]))
            ->setRelation('paymentTerms', new Collection([
                new TempQuotationPaymentTerm(['term_no' => 1, 'percentage' => 100, 'description' => 'Full payment']),
            ]));
        $html = view('pdfs.quotation_body', ['quotation' => $document, 'user' => $sales, 'claim' => $claim])->render();
        $this->assertStringContainsString('QT_DEAL_901_R1', $html);
        $this->assertStringContainsString('PDF Snapshot Machine', $html);
        $this->assertStringContainsString('PT Pandu Naradipta Danendra', $html);
        $this->assertStringContainsString('Your Reliable Business Partner', $html);

        $fontDirectory = sys_get_temp_dir().'/quotation-pdf-test-'.Str::uuid();
        $filesystem = new Filesystem;
        $filesystem->makeDirectory($fontDirectory);
        try {
            $options = new Options(config('dompdf.options', []));
            $options->setChroot(base_path());
            $options->setIsRemoteEnabled(false);
            $options->setFontDir($fontDirectory);
            $options->setFontCache($fontDirectory);
            $pdf = new Dompdf($options);
            $pdf->loadHtml($html);
            $pdf->setPaper('A4', 'portrait');
            $pdf->render();
            $output = $pdf->output();
            $this->assertStringStartsWith('%PDF-', $output);
            $this->assertSame(1, $pdf->getCanvas()->get_page_count());
            $this->assertStringContainsString('/URI', $output);
            $this->assertStringContainsString('https://daxtro.id/', $output);
        } finally {
            $filesystem->deleteDirectory($fontDirectory);
        }
    }
}
