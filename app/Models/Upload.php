<?php

// app/Models/Upload.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Upload extends Model {
    use HasFactory;

    protected $fillable = ['filename', 'checksum', 'completed'];

    public function images() {
        return $this->hasMany(Image::class);
    }
}