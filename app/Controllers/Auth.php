<?php
namespace App\Controllers;
class Auth extends BaseController {
 protected $helpers=['url','form'];
 public function login() { return view('pos/login',['title'=>'Staff sign in']); }
 public function authenticate() {
  $key='login_'.hash('sha256',$this->request->getIPAddress());
  if (!service('throttler')->check($key,10,300)) return redirect()->back()->with('error','Too many attempts. Try again in five minutes.');
  $user=(new \App\Models\StaffModel())->where('username',(string)$this->request->getPost('username'))->first();
  if (!$user || !password_verify((string)$this->request->getPost('password'),$user['password'])) return redirect()->back()->with('error','Incorrect username or password.');
  session()->regenerate(true); session()->set('staff_id',$user['id']);
  return redirect()->to(site_url('/'));
 }
 public function logout() { session()->destroy(); return redirect()->to(site_url('login')); }
}
