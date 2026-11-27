<?php
namespace App\Services;
class SaleRecorder {
 public function record(int $productId, ?int $customerId, int $staffId, int $quantity): void {
  if ($quantity<1 || $quantity>2147483647) throw new \DomainException('Quantity must be a positive whole number.');
  $db=db_connect(); $db->transBegin();
  try {
   // Lock before reading price/stock; SQLite serializes writes for the test suite.
   $sql='SELECT * FROM products WHERE id = ?'.($db->DBDriver==='MySQLi'?' FOR UPDATE':'');
   $p=$db->query($sql,[$productId])->getRowArray();
   if (!$p) throw new \DomainException('Choose an existing product.');
   if ($customerId && !$db->table('customers')->where('id',$customerId)->countAllResults()) throw new \DomainException('Choose an existing customer or walk-in.');
   if (!$db->table('users')->where('id',$staffId)->countAllResults()) throw new \DomainException('Your staff account no longer exists. Sign in again.');
   if ($quantity>(int)$p['stock_quantity']) throw new \DomainException('Sale rejected: only '.$p['stock_quantity'].' units are available.');
   $parts=explode('.',(string)$p['price']); $cents=((int)$parts[0]*100)+(int)str_pad($parts[1]??'',2,'0');
   if ($cents>0 && $quantity>intdiv(9999999999,$cents)) throw new \DomainException('Sale total exceeds the supported limit.');
   $total=$cents*$quantity; $price=intdiv($total,100).'.'.str_pad((string)($total%100),2,'0',STR_PAD_LEFT);
   $db->query('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?',[$quantity,$productId,$quantity]);
   if ($db->affectedRows()!==1) throw new \DomainException('Stock changed. Refresh the page and try again.');
   $db->table('sales')->insert(['product_id'=>$productId,'customer_id'=>$customerId,'sold_by'=>$staffId,'quantity'=>$quantity,'total_price'=>$price,'created_at'=>date('Y-m-d H:i:s')]);
   if (!$db->transStatus()) throw new \RuntimeException('Transaction failed.');
   $db->transCommit();
  } catch (\Throwable $e) { $db->transRollback(); throw $e; }
 }
}
