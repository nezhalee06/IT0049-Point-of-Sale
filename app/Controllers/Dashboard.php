<?php
namespace App\Controllers;
class Dashboard extends BaseController {
 protected $helpers=['url','form'];
 public function index() {
  $db=db_connect();
  return view('pos/dashboard',['title'=>'Overview','products'=>$db->table('products')->countAllResults(),'customers'=>$db->table('customers')->countAllResults(),'staff'=>$db->table('users')->countAllResults(),'sales'=>$db->table('sales')->countAllResults(),'revenue'=>$db->table('sales')->selectSum('total_price')->get()->getRow()->total_price ?? 0,'low'=>$db->table('products')->where('stock_quantity <',6)->get()->getResultArray()]);
 }
}
