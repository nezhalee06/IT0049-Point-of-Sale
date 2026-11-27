<?php
namespace App\Controllers;
class Sales extends BaseController {
 protected $helpers=['url','form'];
 public function create() { return view('pos/sale',['title'=>'Record a sale','products'=>(new \App\Models\ProductModel())->orderBy('name')->findAll(),'customers'=>(new \App\Models\CustomerModel())->orderBy('full_name')->findAll()]); }
 public function store() {
  if (!$this->validateData($this->request->getPost(),['product_id'=>'required|is_natural_no_zero','customer_id'=>'permit_empty|is_natural_no_zero','quantity'=>'required|is_natural_no_zero|less_than_equal_to[2147483647]'])) return redirect()->back()->withInput()->with('error','Select a product and enter a positive whole quantity.');
  try { (new \App\Services\SaleRecorder())->record((int)$this->request->getPost('product_id'),$this->request->getPost('customer_id')?(int)$this->request->getPost('customer_id'):null,(int)session('staff_id'),(int)$this->request->getPost('quantity')); }
  catch (\DomainException $e) { return redirect()->back()->withInput()->with('error',$e->getMessage()); }
  catch (\Throwable $e) { log_message('error','Sale failed: {message}',['message'=>$e->getMessage()]); return redirect()->back()->withInput()->with('error','Sale could not be saved. No stock was deducted. Please try again.'); }
  return redirect()->to(site_url('sales'))->with('success','Sale recorded and stock updated.');
 }
 public function index() {
  $rows=db_connect()->table('sales s')->select('s.*, p.name product, c.full_name customer, u.full_name staff')->join('products p','p.id=s.product_id')->join('customers c','c.id=s.customer_id','left')->join('users u','u.id=s.sold_by')->orderBy('s.id','DESC')->get()->getResultArray();
  return view('pos/history',['title'=>'Sales history','rows'=>$rows]);
 }
}
