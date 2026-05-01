<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class ContactMessageModel extends Model
{
    protected $table          = 'contact_messages';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $protectFields  = true;
    protected $allowedFields  = ['name', 'email', 'subject', 'body'];
    protected $useTimestamps  = true;
    protected $updatedField   = 'updated_at';
}
