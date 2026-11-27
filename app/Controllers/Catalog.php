<?php
namespace App\Controllers;
use App\Libraries\ImageUpload;
class Catalog extends BaseController {
 protected $helpers=['url','form'];
 private function model(string $entity) { return match($entity) {'products'=>new \App\Models\ProductModel(),'customers'=>new \App\Models\CustomerModel(),'users'=>new \App\Models\StaffModel(),default=>throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound()}; }
 public function index(string $entity) { return view('pos/list',['title'=>$entity==='users'?'Staff':ucfirst($entity),'entity'=>$entity,'rows'=>$this->model($entity)->orderBy('id','DESC')->findAll()]); }
 public function form(string $entity, $id=null) {
  $row=$id?$this->model($entity)->find($id):[];
  if ($id && !$row) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
  return view('pos/form',['title'=>($id?'Edit ':'Add ').($entity==='users'?'staff member':rtrim($entity,'s')),'entity'=>$entity,'row'=>$row]);
 }
 public function save(string $entity) {
  $model=$this->model($entity); $id=$this->request->getPost('id');
  $existing=$id?$model->find((int)$id):null;
  if ($id && !$existing) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
  $rules=match($entity) {
   'products'=>['name'=>'required|max_length[100]','price'=>'required|decimal|greater_than_equal_to[0]|less_than_equal_to[99999999.99]|regex_match[/^\d+(\.\d{1,2})?$/]','stock_quantity'=>'required|is_natural|less_than_equal_to[2147483647]'],
   'customers'=>['full_name'=>'required|max_length[100]','email'=>'required|valid_email|max_length[100]','phone'=>'permit_empty|max_length[20]|regex_match[/^[0-9+() .-]+$/]'],
   'users'=>['username'=>'required|alpha_dash|min_length[3]|max_length[50]','full_name'=>'required|max_length[100]','password'=>($id?'permit_empty':'required').'|min_length[10]|max_length[72]']
  };
  if (!$this->validateData($this->request->getPost(),$rules)) return redirect()->back()->with('error',implode(' ',$this->validator->getErrors()));
  $data=array_intersect_key($this->request->getPost(),$rules);
  if ($entity==='users') {
   $duplicate=$model->where('username',$data['username']); if ($id) $duplicate->where('id !=',(int)$id);
   if ($duplicate->first()) return redirect()->back()->with('error','That username is already in use.');
   if ($data['password']==='') unset($data['password']); else $data['password']=password_hash($data['password'],PASSWORD_DEFAULT);
  }
  $imageField=match($entity) {'products'=>'image','users'=>'avatar',default=>null}; $newImage=null;
  try {
   if ($imageField) { $newImage=(new ImageUpload())->save($this->request->getFile($imageField)); if ($newImage) $data[$imageField]=$newImage; }
   if ($id) $ok=$model->update((int)$id,$data); else { $data['created_at']=date('Y-m-d H:i:s'); $ok=$model->insert($data); } if (!$ok) throw new \RuntimeException('Unable to save record.');
   if ($newImage && $existing) (new ImageUpload())->remove($existing[$imageField]??null);
  } catch (\Throwable $e) {
   if ($newImage) (new ImageUpload())->remove($newImage);
   log_message('error','Catalog save failed: {message}',['message'=>$e->getMessage()]);
   return redirect()->back()->with('error',$e instanceof \RuntimeException && !($e instanceof \CodeIgniter\Database\Exceptions\DatabaseException)?$e->getMessage():'Unable to save this record. Please check the fields and try again.');
  }
  return redirect()->to(site_url($entity))->with('success','Record saved.');
 }
 public function delete(string $entity, int $id) {
  $model=$this->model($entity); $row=$model->find($id);
  if (!$row) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
  if ($entity==='users' && $id==session('staff_id')) return redirect()->back()->with('error','You cannot delete your own signed-in account.');
  try { if (!$model->delete($id)) throw new \RuntimeException('Delete failed.'); } catch (\Throwable $e) { return redirect()->back()->with('error','This record is used by sales history and must be kept. You can still edit it.'); }
  (new ImageUpload())->remove($row[$entity==='products'?'image':'avatar']??null);
  return redirect()->to(site_url($entity))->with('success','Record deleted.');
 }
 public function media(string $name) {
  if (!preg_match('/^[a-f0-9]{32}\.jpg$/D',$name) || !is_file(WRITEPATH.'uploads/pos/'.$name)) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
  return $this->response->setContentType('image/jpeg')->setHeader('X-Content-Type-Options','nosniff')->setBody(file_get_contents(WRITEPATH.'uploads/pos/'.$name));
 }
}

