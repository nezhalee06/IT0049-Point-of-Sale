<?php
namespace App\Models;
class ProductModel extends \CodeIgniter\Model {
 protected $table='products';
 protected $allowedFields=['name', 'price', 'stock_quantity', 'image', 'created_at'];
 protected $returnType='array';
}
