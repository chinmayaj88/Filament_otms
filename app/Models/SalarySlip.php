<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalarySlip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'issued_by',
        'ctc',
        'bonus',
        'tax',
        'in_hand'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
