<?php

// app/Models/Image.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model {
    use HasFactory;

    protected $fillable = ['upload_id', 'path', 'size'];

    public function upload() {
        return $this->belongsTo(Upload::class);
    }
}