<?php
namespace App\Database\Seeds;
class PosSeeder extends \CodeIgniter\Database\Seeder {
 public function run() {
  $now=date('Y-m-d H:i:s');
  if (!$this->db->table('users')->countAllResults()) {
   $password=(string)env('POS_ADMIN_PASSWORD', '');
   if (strlen($password)<10 || strlen($password)>72) throw new \RuntimeException('Set POS_ADMIN_PASSWORD to 10-72 characters before seeding production.');
   $this->db->table('users')->insert(['username'=>'admin','full_name'=>'Store Administrator','password'=>password_hash($password,PASSWORD_DEFAULT),'created_at'=>$now]);
  }
  if (!$this->db->table('products')->countAllResults()) foreach ([['Notebook',65,50],['Ballpoint Pen',15,100],['Tumbler',249.50,20]] as [$n,$p,$s]) $this->db->table('products')->insert(['name'=>$n,'price'=>$p,'stock_quantity'=>$s,'created_at'=>$now]);
  if (!$this->db->table('customers')->countAllResults()) $this->db->table('customers')->insert(['full_name'=>'Sample Customer','email'=>'customer@example.com','phone'=>'09123456789','created_at'=>$now]);
 }
}
