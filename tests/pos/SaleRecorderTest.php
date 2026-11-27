<?php
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Services\SaleRecorder;

final class SaleRecorderTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    protected $namespace = 'App';
    protected $seed = 'App\Database\Seeds\PosSeeder';

    protected function setUp(): void
    {
        putenv('POS_ADMIN_PASSWORD=' . bin2hex(random_bytes(16)));
        parent::setUp();
    }

    public function testSaleStoresExactTotalAndDeductsStock(): void
    {
        (new SaleRecorder())->record(3, 1, 1, 3);
        $this->assertSame(17, (int) $this->db->table('products')->where('id', 3)->get()->getRow()->stock_quantity);
        $sale = $this->db->table('sales')->get()->getRowArray();
        $this->assertSame('748.50', number_format((float) $sale['total_price'], 2, '.', ''));
        $this->assertSame(1, (int) $sale['customer_id']);
        $this->assertSame(1, (int) $sale['sold_by']);
    }

    public function testOversellChangesNeitherStockNorHistory(): void
    {
        try {
            (new SaleRecorder())->record(1, null, 1, 51);
            $this->fail('Oversell was accepted.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('only 50', $e->getMessage());
        }
        $this->assertSame(50, (int) $this->db->table('products')->where('id', 1)->get()->getRow()->stock_quantity);
        $this->assertSame(0, $this->db->table('sales')->countAllResults());
    }

    public function testExactStockAndWalkInAreAccepted(): void
    {
        (new SaleRecorder())->record(1, null, 1, 50);
        $this->assertSame(0, (int) $this->db->table('products')->where('id', 1)->get()->getRow()->stock_quantity);
        $this->assertNull($this->db->table('sales')->get()->getRow()->customer_id);
        $this->expectException(DomainException::class);
        (new SaleRecorder())->record(1, null, 1, 1);
    }

    public function testInvalidCustomerAndStaffCannotCreateSale(): void
    {
        foreach ([[1,999,1,1],[1,null,999,1],[999,null,1,1],[1,null,1,0],[1,null,1,-1]] as $args) {
            try { (new SaleRecorder())->record(...$args); $this->fail('Invalid sale accepted.'); }
            catch (DomainException $e) { $this->assertNotEmpty($e->getMessage()); }
        }
        $this->assertSame(0, $this->db->table('sales')->countAllResults());
        $this->assertSame(50, (int) $this->db->table('products')->where('id', 1)->get()->getRow()->stock_quantity);
    }

    public function testDatabaseFailureRollsBackStock(): void
    {
        $this->db->query("CREATE TRIGGER reject_sale BEFORE INSERT ON sales BEGIN SELECT RAISE(ABORT, 'simulated failure'); END");
        try { (new SaleRecorder())->record(1, null, 1, 1); $this->fail('Failure not detected.'); }
        catch (Throwable $e) { $this->assertSame(0, $this->db->table('sales')->countAllResults()); }
        finally { $this->db->query('DROP TRIGGER reject_sale'); $this->db->resetTransStatus(); }
        $this->assertSame(50, (int) $this->db->table('products')->where('id', 1)->get()->getRow()->stock_quantity);
    }
}
