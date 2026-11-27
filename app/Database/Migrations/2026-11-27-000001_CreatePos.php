<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class CreatePos extends Migration {
 public function up() {
  $id=['type'=>'INT','unsigned'=>true,'auto_increment'=>true];
  $date=['type'=>'DATETIME'];
  $this->forge->addField(['id'=>$id,'name'=>['type'=>'VARCHAR','constraint'=>100], 'price'=>['type'=>'DECIMAL','constraint'=>'10,2'], 'stock_quantity'=>['type'=>'INT','default'=>0], 'image'=>['type'=>'VARCHAR','constraint'=>255,'null'=>true], 'created_at'=>$date]);
  $this->forge->addKey('id',true); $this->forge->createTable('products',true,($this->db->DBDriver==='MySQLi'?['ENGINE'=>'InnoDB']:[]));
  $this->forge->addField(['id'=>$id,'full_name'=>['type'=>'VARCHAR','constraint'=>100], 'email'=>['type'=>'VARCHAR','constraint'=>100], 'phone'=>['type'=>'VARCHAR','constraint'=>20,'null'=>true], 'created_at'=>$date]);
  $this->forge->addKey('id',true); $this->forge->createTable('customers',true,($this->db->DBDriver==='MySQLi'?['ENGINE'=>'InnoDB']:[]));
  $this->forge->addField(['id'=>$id,'username'=>['type'=>'VARCHAR','constraint'=>50], 'full_name'=>['type'=>'VARCHAR','constraint'=>100], 'password'=>['type'=>'VARCHAR','constraint'=>255], 'avatar'=>['type'=>'VARCHAR','constraint'=>255,'null'=>true], 'created_at'=>$date]);
  $this->forge->addKey('id',true); $this->forge->addUniqueKey('username'); $this->forge->createTable('users',true,($this->db->DBDriver==='MySQLi'?['ENGINE'=>'InnoDB']:[]));
  $this->forge->addField(['id'=>$id,'product_id'=>['type'=>'INT','unsigned'=>true], 'customer_id'=>['type'=>'INT','unsigned'=>true,'null'=>true], 'sold_by'=>['type'=>'INT','unsigned'=>true], 'quantity'=>['type'=>'INT'], 'total_price'=>['type'=>'DECIMAL','constraint'=>'10,2'], 'created_at'=>$date]);
  $this->forge->addKey('id',true);
  $this->forge->addForeignKey('product_id','products','id','CASCADE','RESTRICT');
  $this->forge->addForeignKey('customer_id','customers','id','CASCADE','SET NULL');
  $this->forge->addForeignKey('sold_by','users','id','CASCADE','RESTRICT');
  $this->forge->createTable('sales',true,($this->db->DBDriver==='MySQLi'?['ENGINE'=>'InnoDB']:[]));
 }
 public function down() { foreach(['sales','users','customers','products'] as $t) $this->forge->dropTable($t,true); }
}

