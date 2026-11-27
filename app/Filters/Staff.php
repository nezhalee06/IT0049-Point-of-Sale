<?php
namespace App\Filters;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
class Staff implements FilterInterface {
    public function before(RequestInterface $request, $arguments = null) {
        $id=session('staff_id');
        if (!$id || !(new \App\Models\StaffModel())->find($id)) {
            session()->remove('staff_id');
            return redirect()->to(site_url('login'));
        }
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {
        $response->setHeader('Cache-Control','no-store, private');
    }
}
