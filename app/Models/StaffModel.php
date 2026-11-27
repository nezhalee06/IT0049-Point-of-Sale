<?php
namespace App\Models;
class StaffModel extends \CodeIgniter\Model {
 protected $table='users';
 protected $allowedFields=['username', 'full_name', 'password', 'avatar', 'created_at'];
 protected $returnType='array';
}
